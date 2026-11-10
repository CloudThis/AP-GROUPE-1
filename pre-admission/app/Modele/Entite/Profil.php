<?php
namespace App\Modele\Entite;

/**
 * Les 4 profils. La valeur correspond à la colonne habilitation.code_profil en base.
 */
enum Profil: string
{
    case SECRETAIRE    = 'SECRETAIRE';
    case MEDECIN       = 'MEDECIN';
    case ADMINISTRATIF = 'ADMINISTRATIF';
    case INFORMATICIEN = 'INFORMATICIEN';

    public function libelle(): string
    {
        return match ($this) {
            self::SECRETAIRE    => 'Secrétaire médicale',
            self::MEDECIN       => 'Médecin',
            self::ADMINISTRATIF => 'Département administratif',
            self::INFORMATICIEN => 'Informaticien admin',
        };
    }

    /** Page d'accueil propre au profil (paramètre ?page=...). */
    public function pageAccueil(): string
    {
        return 'accueil_' . strtolower($this->value);
    }
}
