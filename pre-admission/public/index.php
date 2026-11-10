<?php
/**
 * CONTRÔLEUR FRONTAL : toutes les pages passent par ce fichier (index.php?page=...).
 * C'est ici que la protection CÔTÉ SERVEUR est appliquée, avant tout affichage.
 */
declare(strict_types=1);

require __DIR__ . '/../app/autoload.php';
require __DIR__ . '/../app/fonctions.php';

use App\Controleur\AccueilControleur;
use App\Controleur\ConnexionControleur;
use App\Controleur\DeconnexionControleur;
use App\Controleur\PreadmissionControleur;
use App\Controleur\Rendu;
use App\Controleur\Session;
use App\Modele\Dao\CompteDaoPdo;
use App\Modele\Dao\Database;
use App\Modele\Dao\PreadmissionDaoPdo;
use App\Modele\Service\AuthService;
use App\Modele\Service\ControleAcces;

Session::demarrer();
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'connexion';

// 1. Protection des pages : session + profil vérifiés côté serveur
if (ControleAcces::estProtegee($page)) {
    switch (ControleAcces::verifier(Session::utilisateur(), $page)) {
        case ControleAcces::NON_CONNECTE:
            Rendu::rediriger('connexion');
        case ControleAcces::INTERDIT:
            Rendu::vue('erreur403', [], 403);
            exit;
    }
    header('Cache-Control: no-store');   // pas de page en cache après déconnexion
}

// 2. Aiguillage vers le bon contrôleur
switch ($page) {
    case 'connexion':
        $controleur = new ConnexionControleur(fn() => new AuthService(new CompteDaoPdo(Database::connexion())));
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $controleur->traiter() : $controleur->afficher();
        break;

    case 'deconnexion':
        (new DeconnexionControleur())->deconnecter();
        break;

    case 'accueil_secretaire':
    case 'accueil_medecin':
    case 'accueil_administratif':
    case 'accueil_informaticien':
        (new AccueilControleur())->afficher($page);
        break;

    case 'preadmission_patient':
    case 'preadmission_sejour':
    case 'preadmission_confirmation':
        try {
            $controleur = new PreadmissionControleur(new PreadmissionDaoPdo(Database::connexion()));
            match ($page) {
                'preadmission_patient'      => $controleur->patient(),
                'preadmission_sejour'       => $controleur->sejour(),
                'preadmission_confirmation' => $controleur->confirmation(),
            };
        } catch (\PDOException $e) {
            error_log('Base de données inaccessible : ' . $e->getMessage());
            Rendu::vue('erreur500', [], 500);
        }
        break;

    default:
        Rendu::vue('erreur404', [], 404);
}
