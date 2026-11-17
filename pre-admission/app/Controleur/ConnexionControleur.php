<?php
namespace App\Controleur;

use App\Modele\Service\AuthService;

/** CONTRÔLEUR de la connexion : affiche le formulaire (GET) et traite la saisie (POST). */
final class ConnexionControleur
{
    /** @param \Closure(): AuthService $fabriqueAuth crée le service seulement si besoin (la base n'est ouverte qu'au POST) */
    public function __construct(private readonly \Closure $fabriqueAuth) {}

    public function afficher(): void
    {
        // Déjà connecté ? On renvoie directement vers son écran.
        $utilisateur = Session::utilisateur();
        if ($utilisateur !== null) {
            Rendu::rediriger($utilisateur->profil->pageAccueil());
        }
        Rendu::vue('connexion', [
            'csrf'        => Session::jetonCsrf(),
            'deconnecte'  => isset($_GET['deconnecte']),
        ]);
    }

    public function traiter(): void
    {
        $identifiant = $_POST['identifiant'] ?? '';

        if (!Session::verifierCsrf($_POST['csrf'] ?? null)) {
            Rendu::vue('connexion', [
                'erreur'      => 'Session expirée, veuillez réessayer.',
                'identifiant' => $identifiant,
                'csrf'        => Session::jetonCsrf(),
            ], 400);
            return;
        }

        try {
            $utilisateur = ($this->fabriqueAuth)()->authentifier($identifiant, $_POST['mot_de_passe'] ?? null);
        } catch (\PDOException $e) {
            Rendu::vue('connexion', [
                'erreur' => 'Base de données inaccessible. Vérifiez que MySQL est démarré.',
                'csrf'   => Session::jetonCsrf(),
            ], 500);
            return;
        }

        if ($utilisateur === null) {
            Rendu::vue('connexion', [
                'erreur'      => AuthService::MESSAGE_ECHEC,
                'identifiant' => $identifiant,   // on garde l'identifiant, jamais le mot de passe
                'csrf'        => Session::jetonCsrf(),
            ], 401);
            return;
        }

        Session::connecter($utilisateur);
        Rendu::rediriger($utilisateur->profil->pageAccueil());
    }
}
