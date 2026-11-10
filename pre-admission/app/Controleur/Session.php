<?php
namespace App\Controleur;

use App\Modele\Entite\Utilisateur;

/** Gestion de la session PHP : utilisateur connecté, jeton CSRF, expiration. */
final class Session
{
    private const CLE_UTILISATEUR = 'utilisateur';
    private const CLE_ACTIVITE    = 'derniere_activite';
    private const CLE_CSRF        = 'csrf';
    private const DUREE_MAX       = 1800; // 30 min d'inactivité -> déconnexion

    public static function demarrer(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name('PREADMISSION');
        session_set_cookie_params([
            'httponly' => true,                       // cookie illisible par JavaScript
            'samesite' => 'Strict',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();

        // Expiration après inactivité
        $derniere = $_SESSION[self::CLE_ACTIVITE] ?? null;
        if ($derniere !== null && time() - $derniere > self::DUREE_MAX) {
            self::detruire();
            session_start();
        }
        $_SESSION[self::CLE_ACTIVITE] = time();
    }

    public static function utilisateur(): ?Utilisateur
    {
        $u = $_SESSION[self::CLE_UTILISATEUR] ?? null;
        return $u instanceof Utilisateur ? $u : null;
    }

    public static function connecter(Utilisateur $utilisateur): void
    {
        session_regenerate_id(true);   // nouvel identifiant de session (anti-fixation)
        $_SESSION[self::CLE_UTILISATEUR] = $utilisateur;
        unset($_SESSION[self::CLE_CSRF]);
    }

    /** Déconnexion : destruction complète de la session et du cookie. */
    public static function detruire(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ---- Protection CSRF des formulaires ----

    public static function jetonCsrf(): string
    {
        return $_SESSION[self::CLE_CSRF] ??= bin2hex(random_bytes(32));
    }

    public static function verifierCsrf(?string $jeton): bool
    {
        return is_string($jeton) && isset($_SESSION[self::CLE_CSRF])
            && hash_equals($_SESSION[self::CLE_CSRF], $jeton);
    }
}
