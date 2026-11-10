<?php
use App\Modele\Entite\Profil;
use App\Modele\Entite\Utilisateur;
use App\Modele\Service\ControleAcces;
use PHPUnit\Framework\TestCase;

/** Protection des pages : session obligatoire et profil autorisé. */
final class ControleAccesTest extends TestCase
{
    private static function utilisateur(Profil $p): Utilisateur
    {
        return new Utilisateur(1, 'test', 'NOM', 'Prenom', $p);
    }

    public function testPageProtegeeSansSessionRefusee(): void
    {
        foreach (array_keys(ControleAcces::PAGES_PROTEGEES) as $page) {
            $this->assertSame(ControleAcces::NON_CONNECTE, ControleAcces::verifier(null, $page), $page);
        }
    }

    public function testChaqueProfilAccedeSeulementASonEcran(): void
    {
        foreach (Profil::cases() as $profil) {
            foreach (Profil::cases() as $autre) {
                $attendu = $profil === $autre ? ControleAcces::AUTORISE : ControleAcces::INTERDIT;
                $this->assertSame($attendu,
                    ControleAcces::verifier(self::utilisateur($profil), $autre->pageAccueil()),
                    "{$profil->value} sur {$autre->pageAccueil()}");
            }
        }
    }

    public function testSecretaireNePeutPasOuvrirEcranMedecin(): void
    {
        $this->assertSame(ControleAcces::INTERDIT,
            ControleAcces::verifier(self::utilisateur(Profil::SECRETAIRE), 'accueil_medecin'));
    }

    public function testConnexionEtDeconnexionNeSontPasProtegees(): void
    {
        $this->assertFalse(ControleAcces::estProtegee('connexion'));
        $this->assertFalse(ControleAcces::estProtegee('deconnexion'));
    }
}
