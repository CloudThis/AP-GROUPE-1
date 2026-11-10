<?php
namespace App\Modele\Dao;

use PDO;

/**
 * Accès aux données de la création d'une pré-admission.
 * Uniquement des requêtes préparées : aucune saisie n'est concaténée dans le SQL.
 *
 * Premier enregistrement (séance 7) : le patient et sa pré-admission.
 * Reste à faire : couverture sociale, personne de confiance, patient déjà connu.
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

    // ------------------------------------------------------------ enregistrement

    /**
     * Enregistre le patient et sa pré-admission dans UNE transaction :
     * si une requête échoue, rien n'est enregistré.
     *
     * @param array $patient données validées de l'étape 1
     * @param array $sejour  données validées de l'étape 2
     * @return int numéro de la pré-admission créée
     */
    public function enregistrer(array $patient, array $sejour, int $idPersonnelSaisie, string $statut): int
    {
        $this->pdo->beginTransaction();
        try {
            $idPatient = $this->creerPatient($patient);
            $idPreadmission = $this->creerPreadmission($idPatient, $sejour, $idPersonnelSaisie, $statut);
            $this->pdo->commit();
            return $idPreadmission;
        } catch (\Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
    }

    private function creerPatient(array $patient): int
    {
        $valeurs = [];
        foreach (self::COLONNES_PATIENT as $colonne) {
            $valeur = $patient[$colonne] ?? null;
            $valeurs[$colonne] = is_bool($valeur) ? (int) $valeur : $valeur;
        }
        $colonnes  = implode(', ', self::COLONNES_PATIENT);
        $marqueurs = implode(', ', array_map(fn($c) => ":$c", self::COLONNES_PATIENT));
        $this->pdo->prepare("INSERT INTO patient ($colonnes) VALUES ($marqueurs)")->execute($valeurs);
        return (int) $this->pdo->lastInsertId();
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
}
