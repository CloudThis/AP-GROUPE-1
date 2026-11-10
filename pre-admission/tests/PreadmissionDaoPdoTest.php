<?php
use App\Modele\Dao\PreadmissionDaoPdo;
use App\Modele\Service\ValidationPreadmission;
use PHPUnit\Framework\TestCase;

/** Enregistrement d'une pré-admission sur une base SQLite en mémoire (mêmes tables que MySQL, sans XAMPP). */
final class PreadmissionDaoPdoTest extends TestCase
{
    private PDO $pdo;
    private PreadmissionDaoPdo $dao;
    private array $patient;
    private array $sejour;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('CREATE TABLE service (id_service INTEGER PRIMARY KEY, nom_service TEXT)');
        $this->pdo->exec('CREATE TABLE organisme_amo (id_organisme INTEGER PRIMARY KEY, nom_organisme TEXT, code_caisse TEXT)');
        $this->pdo->exec('CREATE TABLE mutuelle (id_mutuelle INTEGER PRIMARY KEY, nom_mutuelle TEXT)');
        $this->pdo->exec('CREATE TABLE personnel (id_personnel INTEGER PRIMARY KEY, nom TEXT, prenom TEXT, compte_actif INTEGER)');
        $this->pdo->exec('CREATE TABLE habilitation (id_habilitation INTEGER PRIMARY KEY, code_profil TEXT)');
        $this->pdo->exec('CREATE TABLE possede (id_personnel INTEGER, id_habilitation INTEGER)');
        $this->pdo->exec('CREATE TABLE patient (id_patient INTEGER PRIMARY KEY AUTOINCREMENT, civilite TEXT, nom_naissance TEXT,
            nom_usage TEXT NOT NULL, prenom TEXT NOT NULL, date_naissance TEXT NOT NULL, sexe TEXT NOT NULL, lieu_naissance TEXT,
            nationalite TEXT, numero_securite_sociale TEXT NOT NULL UNIQUE, cle_nir TEXT NOT NULL, adresse TEXT, code_postal TEXT,
            ville TEXT, tel_portable TEXT NOT NULL, tel_fixe TEXT, email TEXT, medecin_traitant TEXT, situation_familiale TEXT,
            profession TEXT, langue TEXT, besoin_interprete INTEGER, consentement_sms INTEGER, consentement_dmp INTEGER,
            opposition_presence INTEGER)');
        $this->pdo->exec('CREATE TABLE est_couvert_par (id_patient INTEGER REFERENCES patient, id_organisme INTEGER REFERENCES organisme_amo,
            regime TEXT NOT NULL, exoneration_ald TEXT, date_debut_droits TEXT, date_fin_droits TEXT, date_lecture_vitale TEXT,
            PRIMARY KEY (id_patient, id_organisme))');
        $this->pdo->exec('CREATE TABLE est_affilie (id_patient INTEGER REFERENCES patient, id_mutuelle INTEGER REFERENCES mutuelle,
            numero_adherent TEXT, PRIMARY KEY (id_patient, id_mutuelle))');
        $this->pdo->exec('CREATE TABLE preadmission (id_preadmission INTEGER PRIMARY KEY AUTOINCREMENT, date_saisie TEXT,
            motif_hospitalisation TEXT NOT NULL, type_sejour TEXT NOT NULL, date_prevue_admission TEXT NOT NULL,
            heure_prevue_admission TEXT NOT NULL, duree_prevue_nuits INTEGER, chambre_particuliere INTEGER, statut TEXT,
            id_patient INTEGER NOT NULL REFERENCES patient, id_personnel_saisie INTEGER NOT NULL REFERENCES personnel,
            id_personnel_responsable INTEGER NOT NULL REFERENCES personnel, id_service INTEGER NOT NULL REFERENCES service)');
        $this->pdo->exec('CREATE TABLE personne_contact (id_contact INTEGER PRIMARY KEY AUTOINCREMENT, nom TEXT, prenom TEXT, telephone TEXT)');
        $this->pdo->exec('CREATE TABLE designe (id_preadmission INTEGER REFERENCES preadmission, id_contact INTEGER REFERENCES personne_contact,
            lien_patient TEXT, est_personne_confiance INTEGER, est_personne_a_prevenir INTEGER, formulaire_signe INTEGER)');

        $this->pdo->exec("INSERT INTO service VALUES (1, 'Chirurgie digestive'), (2, 'Cardiologie')");
        $this->pdo->exec("INSERT INTO organisme_amo VALUES (1, 'CPAM du Rhône', '691'), (2, 'CPAM de Paris', '751')");
        $this->pdo->exec("INSERT INTO mutuelle VALUES (1, 'Harmonie Mutuelle'), (2, 'MGEN')");
        $this->pdo->exec("INSERT INTO habilitation VALUES (1, 'SECRETAIRE'), (2, 'MEDECIN')");
        $this->pdo->exec("INSERT INTO personnel VALUES (1, 'MARTIN', 'Julie', 1), (2, 'LEROY', 'Sophie', 1), (3, 'ANCIEN', 'Paul', 0)");
        $this->pdo->exec('INSERT INTO possede VALUES (1, 1), (2, 2), (3, 2)');

        $this->dao = new PreadmissionDaoPdo($this->pdo);
        $jour = new DateTimeImmutable('2026-11-10');
        $this->patient = ValidationPreadmission::patient(ValidationPreadmissionTest::patientValide(), [1, 2], [1, 2], $jour)[0];
        $this->sejour  = ValidationPreadmission::sejour(ValidationPreadmissionTest::sejourValide(), [1, 2], [2], $jour)[0];
    }

    private function compter(string $table): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }

    public function testEnregistrementCompletAvecLiaisons(): void
    {
        $id = $this->dao->enregistrer($this->patient, $this->sejour, 1, PreadmissionDaoPdo::STATUT_EN_ATTENTE);

        $pa = $this->pdo->query("SELECT * FROM preadmission WHERE id_preadmission = $id")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('EN_ATTENTE_VALIDATION', $pa['statut']);
        $this->assertEquals(1, $pa['id_service']);                 // liaison vers le service
        $this->assertEquals(2, $pa['id_personnel_responsable']);   // praticien
        $this->assertEquals(1, $pa['id_personnel_saisie']);        // secrétaire connectée

        $this->assertSame(1, $this->compter('patient'));
        $this->assertSame(1, $this->compter('est_couvert_par'));   // liaison vers la couverture
        $this->assertSame(1, $this->compter('est_affilie'));
        $this->assertSame(1, $this->compter('personne_contact'));
        $designe = $this->pdo->query('SELECT * FROM designe')->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $designe['est_personne_confiance']);
        $this->assertEquals(1, $designe['est_personne_a_prevenir']);
    }

    public function testResumeReluEnBase(): void
    {
        $id = $this->dao->enregistrer($this->patient, $this->sejour, 1, PreadmissionDaoPdo::STATUT_BROUILLON);
        $resume = $this->dao->resume($id);
        $this->assertSame('DUPONT', $resume['nom_usage']);
        $this->assertSame('Chirurgie digestive', $resume['nom_service']);
        $this->assertSame('LEROY', $resume['praticien_nom']);
        $this->assertSame('BROUILLON', $resume['statut']);
        $this->assertNull($this->dao->resume(999));
    }

    public function testPatientDejaConnuMisAJourSansDoublon(): void
    {
        $this->dao->enregistrer($this->patient, $this->sejour, 1, PreadmissionDaoPdo::STATUT_EN_ATTENTE);
        $modifie = ['ville' => 'Villeurbanne', 'id_organisme' => 2, 'id_mutuelle' => null, 'numero_adherent' => null] + $this->patient;
        $this->dao->enregistrer($modifie, $this->sejour, 1, PreadmissionDaoPdo::STATUT_EN_ATTENTE);

        $this->assertSame(1, $this->compter('patient'));           // même NIR : pas de doublon
        $this->assertSame(2, $this->compter('preadmission'));
        $this->assertSame('Villeurbanne', $this->pdo->query('SELECT ville FROM patient')->fetchColumn());
        $this->assertEquals(2, $this->pdo->query('SELECT id_organisme FROM est_couvert_par')->fetchColumn());
        $this->assertSame(0, $this->compter('est_affilie'));
    }

    public function testEchecAnnuleToutLEnregistrement(): void
    {
        $sejour = ['id_service' => 999] + $this->sejour;   // service inexistant : la clé étrangère refuse
        try {
            $this->dao->enregistrer($this->patient, $sejour, 1, PreadmissionDaoPdo::STATUT_EN_ATTENTE);
            $this->fail('Une exception était attendue.');
        } catch (PDOException) {
            $this->assertSame(0, $this->compter('patient'));        // transaction annulée
            $this->assertSame(0, $this->compter('est_couvert_par'));
            $this->assertSame(0, $this->compter('preadmission'));
        }
    }

    public function testInjectionSqlEnregistreeCommeDuTexte(): void
    {
        $sejour = ['motif_hospitalisation' => "x'); DROP TABLE patient; --"] + $this->sejour;
        $id = $this->dao->enregistrer($this->patient, $sejour, 1, PreadmissionDaoPdo::STATUT_EN_ATTENTE);
        $this->assertSame("x'); DROP TABLE patient; --", $this->dao->resume($id)['motif_hospitalisation']);
        $this->assertSame(1, $this->compter('patient'));
    }

    public function testRecherchePatientParNumeroDeSecuriteSociale(): void
    {
        $this->dao->enregistrer($this->patient, $this->sejour, 1, PreadmissionDaoPdo::STATUT_EN_ATTENTE);
        $trouve = $this->dao->trouverPatientParNir('2780469123456');
        $this->assertSame('DUPONT', $trouve['nom_usage']);
        $this->assertEquals(1, $trouve['id_organisme']);
        $this->assertNull($this->dao->trouverPatientParNir('1000000000000'));
        $this->assertNull($this->dao->trouverPatientParNir("' OR '1'='1"));
    }

    public function testPraticiensSontLesMedecinsActifs(): void
    {
        $praticiens = $this->dao->referentiels()['praticiens'];
        $this->assertCount(1, $praticiens);                         // ni la secrétaire, ni le compte désactivé
        $this->assertSame('LEROY', $praticiens[0]['nom']);
    }
}
