<?php
use App\Modele\Dao\CompteDao;
use App\Modele\Entite\Compte;
use App\Modele\Entite\Profil;
use App\Modele\Service\AuthService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** DAO en mémoire avec les 4 comptes de test du script SQL (mêmes hash bcrypt). */
final class CompteDaoMemoire implements CompteDao
{
    /** @var array<string, Compte> */
    public array $comptes = [];
    public ?int $derniereConnexion = null;

    public function __construct()
    {
        $this->ajouter(new Compte(1, 'secretaire', 'MARTIN', 'Julie',
            '$2y$10$ukeAMXfUz0AzGgIZ8fVBL.zOpB09vCFBneku5luTkSgt9CqUDKMb2', true, Profil::SECRETAIRE));
        $this->ajouter(new Compte(2, 'medecin', 'LEROY', 'Sophie',
            '$2y$10$4R/C5.1EjDyK8rdNaoGpHOLutWPZyADo5iViekP27/ct4ujEdIjqy', true, Profil::MEDECIN));
        $this->ajouter(new Compte(3, 'administratif', 'QUIRION', 'Camille',
            '$2y$10$OS2EYwqX6SSD5Im0lSUDj.7nAyhcQcRBwGhw64AO.HpyEN46bUmx2', true, Profil::ADMINISTRATIF));
        $this->ajouter(new Compte(4, 'informaticien', 'DURAND', 'Thomas',
            '$2y$10$aFyvJMllYPYAymn.Je/1ZOe779giMgWLFKNqI7OrfjmXapp7nNq7q', true, Profil::INFORMATICIEN));
    }

    public function ajouter(Compte $c): void { $this->comptes[$c->identifiant] = $c; }

    public function trouverParIdentifiant(string $identifiant): ?Compte
    {
        return $this->comptes[$identifiant] ?? null;
    }

    public function enregistrerConnexion(int $idPersonnel): void { $this->derniereConnexion = $idPersonnel; }
}

final class AuthServiceTest extends TestCase
{
    private CompteDaoMemoire $dao;
    private AuthService $service;

    protected function setUp(): void
    {
        $this->dao = new CompteDaoMemoire();
        $this->service = new AuthService($this->dao);
    }

    public static function comptesDeTest(): array
    {
        return [
            'secrétaire'    => ['secretaire',    'Secret2026!',  Profil::SECRETAIRE,    'accueil_secretaire'],
            'médecin'       => ['medecin',       'Medecin2026!', Profil::MEDECIN,       'accueil_medecin'],
            'administratif' => ['administratif', 'Admin2026!',   Profil::ADMINISTRATIF, 'accueil_administratif'],
            'informaticien' => ['informaticien', 'Info2026!',    Profil::INFORMATICIEN, 'accueil_informaticien'],
        ];
    }

    #[DataProvider('comptesDeTest')]
    public function testConnexionReussiePourChaqueProfil(string $id, string $mdp, Profil $profil, string $accueil): void
    {
        $u = $this->service->authentifier($id, $mdp);

        $this->assertNotNull($u);
        $this->assertSame($profil, $u->profil);
        $this->assertSame($accueil, $u->profil->pageAccueil());
        $this->assertSame($u->id, $this->dao->derniereConnexion);
    }

    public function testRefusAvecMauvaisMotDePasse(): void
    {
        $this->assertNull($this->service->authentifier('secretaire', 'mauvais'));
        $this->assertNull($this->dao->derniereConnexion);
    }

    public function testRefusAvecIdentifiantInconnu(): void
    {
        $this->assertNull($this->service->authentifier('inconnu', 'Secret2026!'));
    }

    public function testRefusSiCompteDesactive(): void
    {
        $c = $this->dao->comptes['medecin'];
        $this->dao->ajouter(new Compte($c->id, $c->identifiant, $c->nom, $c->prenom, $c->motDePasseHash, false, $c->profil));

        $this->assertNull($this->service->authentifier('medecin', 'Medecin2026!'));
    }

    public function testRefusSiChampsVides(): void
    {
        $this->assertNull($this->service->authentifier('', 'Secret2026!'));
        $this->assertNull($this->service->authentifier('secretaire', ''));
        $this->assertNull($this->service->authentifier(null, null));
    }

    public function testMessageErreurGenerique(): void
    {
        $this->assertSame('Identifiant ou mot de passe incorrect.', AuthService::MESSAGE_ECHEC);
    }
}
