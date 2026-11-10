<?php
namespace App\Modele\Service;

use App\Modele\Entite\Profil;
use App\Modele\Entite\Utilisateur;

/**
 * Règles d'accès : quelle page est réservée à quel profil.
 * Vérifiées CÔTÉ SERVEUR par le contrôleur frontal avant d'afficher une page :
 * masquer un lien ne suffit pas.
 */
final class ControleAcces
{
    public const AUTORISE     = 'AUTORISE';
    public const NON_CONNECTE = 'NON_CONNECTE';
    public const INTERDIT     = 'INTERDIT';

    /** page protégée => profil autorisé. Ajouter ici chaque nouvelle page protégée. */
    public const PAGES_PROTEGEES = [
        'accueil_secretaire'    => Profil::SECRETAIRE,
        'accueil_medecin'       => Profil::MEDECIN,
        'accueil_administratif' => Profil::ADMINISTRATIF,
        'accueil_informaticien' => Profil::INFORMATICIEN,
    ];

    public static function estProtegee(string $page): bool
    {
        return array_key_exists($page, self::PAGES_PROTEGEES);
    }

    public static function verifier(?Utilisateur $utilisateur, string $page): string
    {
        if ($utilisateur === null) {
            return self::NON_CONNECTE;
        }
        $profilAutorise = self::PAGES_PROTEGEES[$page] ?? null;
        if ($profilAutorise !== $utilisateur->profil) {
            return self::INTERDIT;
        }
        return self::AUTORISE;
    }
}
