<?php
namespace App\Modele\Service;

use App\Modele\Dao\CompteDao;
use App\Modele\Entite\Utilisateur;

/** Vérifie un identifiant / mot de passe contre le hash bcrypt stocké en base. */
final class AuthService
{
    /** Message unique : on ne dit jamais si c'est l'identifiant ou le mot de passe qui est faux. */
    public const MESSAGE_ECHEC = 'Identifiant ou mot de passe incorrect.';

    // Hash factice utilisé quand l'identifiant n'existe pas : la réponse prend le même temps,
    // on ne peut donc pas deviner quels identifiants existent.
    private const HASH_FACTICE = '$2y$10$ukeAMXfUz0AzGgIZ8fVBL.zOpB09vCFBneku5luTkSgt9CqUDKMb2';

    public function __construct(private readonly CompteDao $compteDao) {}

    /**
     * @return Utilisateur|null l'utilisateur connecté, ou null si l'authentification échoue
     *                          (identifiant inconnu, mauvais mot de passe ou compte désactivé).
     */
    public function authentifier(?string $identifiant, ?string $motDePasse): ?Utilisateur
    {
        $identifiant = trim((string) $identifiant);
        if ($identifiant === '' || $motDePasse === null || $motDePasse === '') {
            return null;
        }

        $compte = $this->compteDao->trouverParIdentifiant($identifiant);
        $motDePasseOk = password_verify($motDePasse, $compte?->motDePasseHash ?? self::HASH_FACTICE);

        if ($compte === null || !$motDePasseOk || !$compte->actif) {
            return null;
        }

        $this->compteDao->enregistrerConnexion($compte->id);
        return $compte->versUtilisateur();
    }
}
