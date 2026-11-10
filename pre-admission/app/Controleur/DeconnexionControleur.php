<?php
namespace App\Controleur;

/** CONTRÔLEUR de la déconnexion : détruit la session puis retour à la connexion. */
final class DeconnexionControleur
{
    public function deconnecter(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifierCsrf($_POST['csrf'] ?? null)) {
            Rendu::rediriger('connexion');
        }
        Session::detruire();
        Rendu::rediriger('connexion', ['deconnecte' => 1]);
    }
}
