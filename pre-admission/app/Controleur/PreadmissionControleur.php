<?php
namespace App\Controleur;

use App\Modele\Dao\PreadmissionDaoPdo;
use App\Modele\Service\ValidationPreadmission;

/**
 * CONTRÔLEUR de la création d'une pré-admission (profil secrétaire médicale).
 * Quand on arrive ici, le contrôleur frontal a DÉJÀ vérifié la session et le profil.
 *
 * Étape 1 « Patient » -> gardée en session -> étape 2 « Pré-admission » -> enregistrement.
 */
final class PreadmissionControleur
{
    private const CLE_PATIENT = 'preadmission_patient';

    public function __construct(private readonly PreadmissionDaoPdo $dao) {}

    // ------------------------------------------------------------ étape 1 : patient

    public function patient(): void
    {
        $ref = $this->dao->referentiels();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->vuePatient($ref, Session::lire(self::CLE_PATIENT) ?? []);
            return;
        }
        if (!Session::verifierCsrf($_POST['csrf'] ?? null)) {
            $this->vuePatient($ref, $_POST, [], 'Session expirée, veuillez réessayer.', 400);
            return;
        }
        if (($_POST['action'] ?? '') === 'annuler') {
            Session::oublier(self::CLE_PATIENT);
            Rendu::rediriger('accueil_secretaire');
        }

        [$donnees, $erreurs] = ValidationPreadmission::patient(
            $_POST, array_column($ref['organismes'], 'id'), array_column($ref['mutuelles'], 'id')
        );
        if ($erreurs !== []) {
            $this->vuePatient($ref, $_POST, $erreurs, 'Le formulaire contient des erreurs : rien n\'a été enregistré.', 422);
            return;
        }
        Session::ecrire(self::CLE_PATIENT, $donnees);
        Rendu::rediriger('preadmission_sejour');
    }

    private function vuePatient(array $ref, array $valeurs, array $erreurs = [], ?string $erreur = null, int $code = 200): void
    {
        Rendu::vue('secretaire/preadmission_patient', [
            'utilisateur' => Session::utilisateur(),
            'csrf'        => Session::jetonCsrf(),
            'ref'         => $ref,
            'valeurs'     => $valeurs,
            'erreurs'     => $erreurs,
            'erreur'      => $erreur,
        ], $code);
    }

    // ------------------------------------------------------------ étape 2 : pré-admission

    public function sejour(): void
    {
        $patient = Session::lire(self::CLE_PATIENT);
        if (!is_array($patient)) {
            Rendu::rediriger('preadmission_patient');   // l'étape 1 est obligatoire
        }
        $ref = $this->dao->referentiels();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->vueSejour($ref, $patient, []);
            return;
        }
        if (!Session::verifierCsrf($_POST['csrf'] ?? null)) {
            $this->vueSejour($ref, $patient, $_POST, [], 'Session expirée, veuillez réessayer.', 400);
            return;
        }

        [$donnees, $erreurs] = ValidationPreadmission::sejour(
            $_POST, array_column($ref['services'], 'id'), array_column($ref['praticiens'], 'id')
        );
        if ($erreurs !== []) {
            $this->vueSejour($ref, $patient, $_POST, $erreurs, 'Le formulaire contient des erreurs : rien n\'a été enregistré.', 422);
            return;
        }

        $statut = ($_POST['action'] ?? '') === 'brouillon'
            ? PreadmissionDaoPdo::STATUT_BROUILLON
            : PreadmissionDaoPdo::STATUT_EN_ATTENTE;

        try {
            $id = $this->dao->enregistrer($patient, $donnees, Session::utilisateur()->id, $statut);
        } catch (\PDOException $e) {
            error_log('Pré-admission non enregistrée : ' . $e->getMessage());
            $this->vueSejour($ref, $patient, $_POST, [], 'Erreur lors de l\'enregistrement : rien n\'a été enregistré. Réessayez.', 500);
            return;
        }

        Session::oublier(self::CLE_PATIENT);
        Rendu::rediriger('accueil_secretaire', ['enregistree' => $id]);
    }

    private function vueSejour(array $ref, array $patient, array $valeurs, array $erreurs = [], ?string $erreur = null, int $code = 200): void
    {
        $organisme = '';
        foreach ($ref['organismes'] as $o) {
            if ((int) $o['id'] === (int) $patient['id_organisme']) {
                $organisme = $o['nom'];
            }
        }
        Rendu::vue('secretaire/preadmission_sejour', [
            'utilisateur' => Session::utilisateur(),
            'csrf'        => Session::jetonCsrf(),
            'ref'         => $ref,
            'patient'     => $patient,
            'organisme'   => $organisme,
            'valeurs'     => $valeurs,
            'erreurs'     => $erreurs,
            'erreur'      => $erreur,
        ], $code);
    }
}
