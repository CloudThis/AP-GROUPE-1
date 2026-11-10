<?php
use App\Modele\Service\ValidationPreadmission;
use PHPUnit\Framework\TestCase;

/** Contrôles de saisie côté serveur de la création d'une pré-admission. */
final class ValidationPreadmissionTest extends TestCase
{
    private DateTimeImmutable $aujourdhui;

    protected function setUp(): void
    {
        $this->aujourdhui = new DateTimeImmutable('2026-11-10');
    }

    /** Saisie valide de l'étape 1 (données fictives de la maquette). */
    public static function patientValide(): array
    {
        return [
            'civilite' => 'Mme', 'nom_usage' => 'DUPONT', 'nom_naissance' => 'MARTIN', 'prenom' => 'Claire',
            'date_naissance' => '1978-04-12', 'sexe' => 'F',
            'numero_securite_sociale' => '2 78 04 69 123 456', 'cle_nir' => '47',
            'code_postal' => '69003', 'ville' => 'Lyon',
            'tel_portable' => '06 12 34 56 78', 'email' => 'claire.dupont@mail.fr',
            'id_organisme' => '1', 'regime' => 'Régime général',
            'id_mutuelle' => '1', 'numero_adherent' => 'HM-00458812',
            'consentement_sms' => '1',
        ];
    }

    /** Saisie valide de l'étape 2. */
    public static function sejourValide(): array
    {
        return [
            'motif_hospitalisation' => 'Cholécystectomie par coelioscopie',
            'type_sejour' => 'HOSPITALISATION_COMPLETE', 'id_service' => '1', 'id_personnel_responsable' => '2',
            'date_prevue_admission' => '2026-11-24', 'heure_prevue_admission' => '07:30',
            'duree_prevue_nuits' => '2', 'chambre_particuliere' => '1',
            'contact_nom' => 'DUPONT', 'contact_prenom' => 'Marc', 'contact_lien' => 'Conjoint',
            'contact_telephone' => '06 98 76 54 32', 'contact_a_prevenir' => '1',
        ];
    }

    private function erreursPatient(array $modifications): array
    {
        return ValidationPreadmission::patient(array_merge(self::patientValide(), $modifications), [1, 2], [1, 2], $this->aujourdhui)[1];
    }

    private function erreursSejour(array $modifications): array
    {
        return ValidationPreadmission::sejour(array_merge(self::sejourValide(), $modifications), [1, 2], [2], $this->aujourdhui)[1];
    }

    // ------------------------------------------------------------ étape 1 : patient

    public function testPatientValideAccepteEtNettoye(): void
    {
        [$donnees, $erreurs] = ValidationPreadmission::patient(self::patientValide(), [1, 2], [1, 2], $this->aujourdhui);
        $this->assertSame([], $erreurs);
        $this->assertSame('2780469123456', $donnees['numero_securite_sociale']);   // espaces retirés
        $this->assertSame('0612345678', $donnees['tel_portable']);
        $this->assertSame(1, $donnees['id_organisme']);
        $this->assertTrue($donnees['consentement_sms']);
        $this->assertFalse($donnees['consentement_dmp']);
    }

    public function testChampsObligatoiresDuPatient(): void
    {
        $obligatoires = ['nom_usage', 'prenom', 'date_naissance', 'sexe', 'numero_securite_sociale',
                         'cle_nir', 'tel_portable', 'id_organisme', 'regime'];
        foreach ($obligatoires as $champ) {
            $this->assertArrayHasKey($champ, $this->erreursPatient([$champ => '']), $champ);
        }
    }

    public function testNumeroDeSecuriteSocialeInvalideRefuse(): void
    {
        foreach (['123', '9780469123456', '27804691234567', 'ABCDEFGHIJKLM'] as $nir) {
            $this->assertArrayHasKey('numero_securite_sociale', $this->erreursPatient(['numero_securite_sociale' => $nir]), $nir);
        }
        $this->assertArrayHasKey('cle_nir', $this->erreursPatient(['cle_nir' => '4']));
        $this->assertArrayHasKey('cle_nir', $this->erreursPatient(['cle_nir' => '99']));
    }

    public function testNumeroDeSecuriteSocialeCorseAccepte(): void
    {
        $this->assertSame([], $this->erreursPatient(['numero_securite_sociale' => '178042A123456']));
    }

    public function testDateDeNaissanceInvalideOuFutureRefusee(): void
    {
        $this->assertArrayHasKey('date_naissance', $this->erreursPatient(['date_naissance' => '12/04/1978']));
        $this->assertArrayHasKey('date_naissance', $this->erreursPatient(['date_naissance' => '1978-02-30']));
        $this->assertArrayHasKey('date_naissance', $this->erreursPatient(['date_naissance' => '2030-01-01']));
    }

    public function testTelephoneInvalideRefuse(): void
    {
        $this->assertArrayHasKey('tel_portable', $this->erreursPatient(['tel_portable' => '0412345678']));  // pas un portable
        $this->assertArrayHasKey('tel_portable', $this->erreursPatient(['tel_portable' => '06 12 34']));
        $this->assertArrayHasKey('tel_fixe', $this->erreursPatient(['tel_fixe' => 'abc']));
        $this->assertSame([], $this->erreursPatient(['tel_fixe' => '04.72.00.00.00']));
    }

    public function testEmailEtCodePostalInvalidesRefuses(): void
    {
        $this->assertArrayHasKey('email', $this->erreursPatient(['email' => 'claire.dupont']));
        $this->assertArrayHasKey('code_postal', $this->erreursPatient(['code_postal' => '6900']));
    }

    public function testOrganismeOuRegimeHorsListeRefuse(): void
    {
        $this->assertArrayHasKey('id_organisme', $this->erreursPatient(['id_organisme' => '999']));
        $this->assertArrayHasKey('id_organisme', $this->erreursPatient(['id_organisme' => '1 OR 1=1']));
        $this->assertArrayHasKey('regime', $this->erreursPatient(['regime' => 'Inventé']));
        $this->assertArrayHasKey('id_mutuelle', $this->erreursPatient(['id_mutuelle' => '999']));
    }

    public function testMutuelleFacultativeMaisRequiseAvecNumeroAdherent(): void
    {
        $this->assertSame([], $this->erreursPatient(['id_mutuelle' => '', 'numero_adherent' => '']));
        $this->assertArrayHasKey('id_mutuelle', $this->erreursPatient(['id_mutuelle' => '']));
    }

    public function testTexteTropLongRefuse(): void
    {
        $this->assertArrayHasKey('nom_usage', $this->erreursPatient(['nom_usage' => str_repeat('A', 101)]));
    }

    // ------------------------------------------------------------ étape 2 : séjour

    public function testSejourValideAccepte(): void
    {
        [$donnees, $erreurs] = ValidationPreadmission::sejour(self::sejourValide(), [1, 2], [2], $this->aujourdhui);
        $this->assertSame([], $erreurs);
        $this->assertSame(2, $donnees['duree_prevue_nuits']);
        $this->assertSame('0698765432', $donnees['contact_telephone']);
        $this->assertTrue($donnees['chambre_particuliere']);
    }

    public function testChampsObligatoiresDuSejour(): void
    {
        $obligatoires = ['motif_hospitalisation', 'type_sejour', 'id_service', 'id_personnel_responsable',
                         'date_prevue_admission', 'heure_prevue_admission',
                         'contact_nom', 'contact_prenom', 'contact_lien', 'contact_telephone'];
        foreach ($obligatoires as $champ) {
            $this->assertArrayHasKey($champ, $this->erreursSejour([$champ => '']), $champ);
        }
    }

    public function testDateEntreePasseeOuInvalideRefusee(): void
    {
        $this->assertArrayHasKey('date_prevue_admission', $this->erreursSejour(['date_prevue_admission' => '2026-11-09']));
        $this->assertArrayHasKey('date_prevue_admission', $this->erreursSejour(['date_prevue_admission' => '24/11/2026']));
        $this->assertSame([], $this->erreursSejour(['date_prevue_admission' => '2026-11-10']));   // aujourd'hui accepté
    }

    public function testHeureEtDureeInvalidesRefusees(): void
    {
        $this->assertArrayHasKey('heure_prevue_admission', $this->erreursSejour(['heure_prevue_admission' => '25:00']));
        $this->assertArrayHasKey('duree_prevue_nuits', $this->erreursSejour(['duree_prevue_nuits' => '-1']));
        $this->assertArrayHasKey('duree_prevue_nuits', $this->erreursSejour(['duree_prevue_nuits' => '500']));
    }

    public function testAmbulatoireSansNuit(): void
    {
        $this->assertArrayHasKey('duree_prevue_nuits', $this->erreursSejour(['type_sejour' => 'AMBULATOIRE']));
        $this->assertSame([], $this->erreursSejour(['type_sejour' => 'AMBULATOIRE', 'duree_prevue_nuits' => '']));
    }

    public function testServiceOuPraticienHorsListeRefuse(): void
    {
        $this->assertArrayHasKey('id_service', $this->erreursSejour(['id_service' => '999']));
        $this->assertArrayHasKey('id_personnel_responsable', $this->erreursSejour(['id_personnel_responsable' => '1']));  // pas un médecin
        $this->assertArrayHasKey('type_sejour', $this->erreursSejour(['type_sejour' => 'AUTRE']));
    }
}
