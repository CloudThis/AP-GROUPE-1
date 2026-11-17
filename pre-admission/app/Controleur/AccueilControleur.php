<?php
namespace App\Controleur;

/**
 * CONTRÔLEUR des écrans d'accueil. Quand on arrive ici, le contrôleur frontal
 * (public/index.php) a DÉJÀ vérifié la session et le profil.
 */
final class AccueilControleur
{
    public function afficher(string $page): void
    {
        $utilisateur = Session::utilisateur();
        $dossier = strtolower($utilisateur->profil->value);   // secretaire, medecin, ...
        Rendu::vue($dossier . '/accueil', [
            'utilisateur' => $utilisateur,
            'csrf'        => Session::jetonCsrf(),
        ]);
    }
}
