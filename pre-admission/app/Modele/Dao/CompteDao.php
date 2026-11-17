<?php
namespace App\Modele\Dao;

use App\Modele\Entite\Compte;

/** Accès aux comptes du personnel (interface : permet de tester sans base). */
interface CompteDao
{
    public function trouverParIdentifiant(string $identifiant): ?Compte;

    public function enregistrerConnexion(int $idPersonnel): void;
}
