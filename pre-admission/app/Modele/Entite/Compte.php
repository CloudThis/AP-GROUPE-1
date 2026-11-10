<?php
namespace App\Modele\Entite;

/**
 * Compte tel que lu en base pour vérifier le mot de passe.
 * Reste dans le modèle : il n'est jamais mis en session.
 */
final class Compte
{
    public function __construct(
        public readonly int $id,
        public readonly string $identifiant,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly string $motDePasseHash,
        public readonly bool $actif,
        public readonly Profil $profil,
    ) {}

    public function versUtilisateur(): Utilisateur
    {
        return new Utilisateur($this->id, $this->identifiant, $this->nom, $this->prenom, $this->profil);
    }
}
