<?php
use App\Modele\Dao\CompteDaoPdo;
use App\Modele\Entite\Profil;
use PHPUnit\Framework\TestCase;

/** Test du DAO sur une petite base SQLite en mémoire (mêmes tables que MySQL, sans XAMPP). */
final class CompteDaoPdoTest extends TestCase
{
    private PDO $pdo;
    private CompteDaoPdo $dao;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('CREATE TABLE personnel (id_personnel INTEGER PRIMARY KEY, nom TEXT, prenom TEXT,
            identifiant TEXT UNIQUE, mot_de_passe_hash TEXT, compte_actif INTEGER, derniere_connexion TEXT)');
        $this->pdo->exec('CREATE TABLE habilitation (id_habilitation INTEGER PRIMARY KEY, code_profil TEXT)');
        $this->pdo->exec('CREATE TABLE possede (id_personnel INTEGER, id_habilitation INTEGER, date_expiration TEXT)');
        $this->pdo->exec("INSERT INTO habilitation VALUES (1, 'SECRETAIRE'), (2, 'MEDECIN')");
        $this->pdo->exec("INSERT INTO personnel VALUES (1, 'MARTIN', 'Julie', 'secretaire', 'hash1', 1, NULL),
                                                       (2, 'LEROY', 'Sophie', 'medecin', 'hash2', 1, NULL)");
        $this->pdo->exec("INSERT INTO possede VALUES (1, 1, NULL), (2, 2, '2000-01-01')");  // médecin : habilitation expirée
        $this->dao = new CompteDaoPdo($this->pdo);
    }

    public function testTrouveUnCompteAvecSonProfil(): void
    {
        $c = $this->dao->trouverParIdentifiant('secretaire');
        $this->assertNotNull($c);
        $this->assertSame(Profil::SECRETAIRE, $c->profil);
        $this->assertTrue($c->actif);
    }

    public function testIdentifiantInconnuRenvoieNull(): void
    {
        $this->assertNull($this->dao->trouverParIdentifiant('inconnu'));
    }

    public function testHabilitationExpireeRefusee(): void
    {
        $this->assertNull($this->dao->trouverParIdentifiant('medecin'));
    }

    public function testInjectionSqlSansEffet(): void
    {
        $this->assertNull($this->dao->trouverParIdentifiant("' OR '1'='1"));
    }

    public function testEnregistreLaDerniereConnexion(): void
    {
        $this->dao->enregistrerConnexion(1);
        $date = $this->pdo->query('SELECT derniere_connexion FROM personnel WHERE id_personnel = 1')->fetchColumn();
        $this->assertNotNull($date);
    }
}
