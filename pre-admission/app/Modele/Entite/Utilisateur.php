<?php
namespace App\Modele\Entite;

/**
 * Utilisateur connecté, stocké en session.
 * Ne contient JAMAIS le mot de passe ni son hash.
 */
final class Utilisateur
{
    public function __construct(
        public readonly int $id,
        public readonly string $identifiant,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly Profil $profil,
    ) {}
}
