<?php
namespace App\Modele\Dao;

use PDO;

/**
 * Accès aux données de la création d'une pré-admission.
 * Uniquement des requêtes préparées : aucune saisie n'est concaténée dans le SQL.
 */
final class PreadmissionDaoPdo
{
    public const STATUT_BROUILLON  = 'BROUILLON';
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE_VALIDATION';

    /** Colonnes de la table patient remplies par l'écran (noms fixes, jamais issus de la saisie). */
    private const COLONNES_PATIENT = [
        'civilite', 'nom_naissance', 'nom_usage', 'prenom', 'date_naissance', 'sexe', 'lieu_naissance',
        'nationalite', 'numero_securite_sociale', 'cle_nir', 'adresse', 'code_postal', 'ville',
        'tel_portable', 'tel_fixe', 'email', 'medecin_traitant', 'situation_familiale', 'profession',
        'langue', 'besoin_interprete', 'consentement_sms', 'consentement_dmp', 'opposition_presence',
    ];

    public function __construct(private readonly PDO $pdo) {}

    // ------------------------------------------------------------ listes déroulantes

    /** @return array{organismes: array, mutuelles: array, services: array, praticiens: array} */
    public function referentiels(): array
    {
        return [
            'organismes' => $this->pdo->query(
                'SELECT id_organisme AS id, nom_organisme AS nom, code_caisse FROM organisme_amo ORDER BY nom_organisme'
            )->fetchAll(PDO::FETCH_ASSOC),
            'mutuelles' => $this->pdo->query(
                'SELECT id_mutuelle AS id, nom_mutuelle AS nom FROM mutuelle ORDER BY nom_mutuelle'
            )->fetchAll(PDO::FETCH_ASSOC),
            'services' => $this->pdo->query(
                'SELECT id_service AS id, nom_service AS nom FROM service ORDER BY nom_service'
            )->fetchAll(PDO::FETCH_ASSOC),
            // Praticiens = personnel actif qui possède l'habilitation MEDECIN (rien de codé en dur)
            'praticiens' => $this->pdo->query(
                "SELECT p.id_personnel AS id, p.nom, p.prenom
                 FROM personnel p
                 JOIN possede po     ON po.id_personnel = p.id_personnel
                 JOIN habilitation h ON h.id_habilitation = po.id_habilitation
                 WHERE h.code_profil = 'MEDECIN' AND p.compte_actif = 1
                 ORDER BY p.nom, p.prenom"
            )->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    // ------------------------------------------------------------ patient déjà connu

    /** Patient déjà enregistré (recherche par numéro de sécurité sociale), avec sa couverture. */
    public function trouverPatientParNir(string $nir): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT p.*, c.id_organisme, c.regime, c.exoneration_ald,
                    c.date_fin_droits, c.date_lecture_vitale, a.id_mutuelle, a.numero_adherent
             FROM patient p
             LEFT JOIN est_couvert_par c ON c.id_patient = p.id_patient
             LEFT JOIN est_affilie a     ON a.id_patient = p.id_patient
             WHERE p.numero_securite_sociale = :nir
             LIMIT 1'
        );
        $requete->execute(['nir' => $nir]);
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);
        return $ligne === false ? null : $ligne;
    }

    // ------------------------------------------------------------ enregistrement

    /**
     * Enregistre le dossier complet dans UNE transaction : patient, couverture sociale,
     * pré-admission (liée au service et au praticien) et personne de confiance.
     * Si une requête échoue, rien n'est enregistré.
     *
     * @param array $patient données validées de l'étape 1
     * @param array $sejour  données validées de l'étape 2
     * @return int numéro de la pré-admission créée
     */
    public function enregistrer(array $patient, array $sejour, int $idPersonnelSaisie, string $statut): int
    {
        $this->pdo->beginTransaction();
        try {
            $idPatient = $this->enregistrerPatient($patient);
            $this->enregistrerCouverture($idPatient, $patient);
            $idPreadmission = $this->creerPreadmission($idPatient, $sejour, $idPersonnelSaisie, $statut);
            $this->enregistrerPersonneDeConfiance($idPreadmission, $sejour);
            $this->pdo->commit();
            return $idPreadmission;
        } catch (\Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
    }

    /** Crée le patient, ou met à jour sa fiche s'il existe déjà (même numéro de sécurité sociale). */
    private function enregistrerPatient(array $patient): int
    {
        $valeurs = [];
        foreach (self::COLONNES_PATIENT as $colonne) {
            $valeur = $patient[$colonne] ?? null;
            $valeurs[$colonne] = is_bool($valeur) ? (int) $valeur : $valeur;
        }

        $recherche = $this->pdo->prepare('SELECT id_patient FROM patient WHERE numero_securite_sociale = :nir');
        $recherche->execute(['nir' => $valeurs['numero_securite_sociale']]);
        $idExistant = $recherche->fetchColumn();

        if ($idExistant !== false) {
            $affectations = implode(', ', array_map(fn($c) => "$c = :$c", self::COLONNES_PATIENT));
            $requete = $this->pdo->prepare("UPDATE patient SET $affectations WHERE id_patient = :id_patient");
            $requete->execute($valeurs + ['id_patient' => (int) $idExistant]);
            return (int) $idExistant;
        }

        $colonnes  = implode(', ', self::COLONNES_PATIENT);
        $marqueurs = implode(', ', array_map(fn($c) => ":$c", self::COLONNES_PATIENT));
        $this->pdo->prepare("INSERT INTO patient ($colonnes) VALUES ($marqueurs)")->execute($valeurs);
        return (int) $this->pdo->lastInsertId();
    }

    /** Organisme d'assurance maladie (obligatoire) et mutuelle (facultative) du patient. */
    private function enregistrerCouverture(int $idPatient, array $patient): void
    {
        $this->pdo->prepare('DELETE FROM est_couvert_par WHERE id_patient = :id')->execute(['id' => $idPatient]);
        $this->pdo->prepare(
            'INSERT INTO est_couvert_par (id_patient, id_organisme, regime, exoneration_ald, date_fin_droits, date_lecture_vitale)
             VALUES (:id_patient, :id_organisme, :regime, :exoneration_ald, :date_fin_droits, :date_lecture_vitale)'
        )->execute([
            'id_patient'          => $idPatient,
            'id_organisme'        => $patient['id_organisme'],
            'regime'              => $patient['regime'],
            'exoneration_ald'     => $patient['exoneration_ald'] ?? null,
            'date_fin_droits'     => $patient['date_fin_droits'] ?? null,
            'date_lecture_vitale' => $patient['date_lecture_vitale'] ?? null,
        ]);

        $this->pdo->prepare('DELETE FROM est_affilie WHERE id_patient = :id')->execute(['id' => $idPatient]);
        if (($patient['id_mutuelle'] ?? null) !== null) {
            $this->pdo->prepare(
                'INSERT INTO est_affilie (id_patient, id_mutuelle, numero_adherent)
                 VALUES (:id_patient, :id_mutuelle, :numero_adherent)'
            )->execute([
                'id_patient'      => $idPatient,
                'id_mutuelle'     => $patient['id_mutuelle'],
                'numero_adherent' => $patient['numero_adherent'] ?? null,
            ]);
        }
    }

    private function creerPreadmission(int $idPatient, array $sejour, int $idPersonnelSaisie, string $statut): int
    {
        $this->pdo->prepare(
            'INSERT INTO preadmission (date_saisie, motif_hospitalisation, type_sejour, date_prevue_admission,
                    heure_prevue_admission, duree_prevue_nuits, chambre_particuliere, statut,
                    id_patient, id_personnel_saisie, id_personnel_responsable, id_service)
             VALUES (:date_saisie, :motif, :type_sejour, :date_prevue, :heure_prevue, :duree, :chambre, :statut,
                    :id_patient, :id_saisie, :id_responsable, :id_service)'
        )->execute([
            'date_saisie'    => date('Y-m-d H:i:s'),
            'motif'          => $sejour['motif_hospitalisation'],
            'type_sejour'    => $sejour['type_sejour'],
            'date_prevue'    => $sejour['date_prevue_admission'],
            'heure_prevue'   => $sejour['heure_prevue_admission'],
            'duree'          => $sejour['duree_prevue_nuits'] ?? null,
            'chambre'        => (int) !empty($sejour['chambre_particuliere']),
            'statut'         => $statut,
            'id_patient'     => $idPatient,
            'id_saisie'      => $idPersonnelSaisie,
            'id_responsable' => $sejour['id_personnel_responsable'],
            'id_service'     => $sejour['id_service'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    private function enregistrerPersonneDeConfiance(int $idPreadmission, array $sejour): void
    {
        $this->pdo->prepare(
            'INSERT INTO personne_contact (nom, prenom, telephone) VALUES (:nom, :prenom, :telephone)'
        )->execute([
            'nom'       => $sejour['contact_nom'],
            'prenom'    => $sejour['contact_prenom'],
            'telephone' => $sejour['contact_telephone'],
        ]);
        $idContact = (int) $this->pdo->lastInsertId();

        $this->pdo->prepare(
            'INSERT INTO designe (id_preadmission, id_contact, lien_patient, est_personne_confiance,
                    est_personne_a_prevenir, formulaire_signe)
             VALUES (:id_preadmission, :id_contact, :lien, 1, :a_prevenir, 0)'
        )->execute([
            'id_preadmission' => $idPreadmission,
            'id_contact'      => $idContact,
            'lien'            => $sejour['contact_lien'],
            'a_prevenir'      => (int) !empty($sejour['contact_a_prevenir']),
        ]);
    }

}
