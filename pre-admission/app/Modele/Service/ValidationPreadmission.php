<?php
namespace App\Modele\Service;

use DateTimeImmutable;

/**
 * Contrôles de saisie CÔTÉ SERVEUR de la création d'une pré-admission.
 * Les contrôles du navigateur (required, type=date...) ne suffisent pas : tout est revérifié ici.
 *
 * Chaque méthode renvoie [données nettoyées, erreurs] ; erreurs = [nom du champ => message].
 * Aucune base de données ici : la classe se teste seule.
 */
final class ValidationPreadmission
{
    public const CIVILITES  = ['M.', 'Mme'];
    public const SEXES      = ['F' => 'F', 'M' => 'M', 'Autre' => 'Autre'];
    public const REGIMES    = ['Régime général', 'Régime agricole (MSA)', 'Régime local Alsace-Moselle', 'Autre régime'];
    public const SITUATIONS = ['Célibataire', 'Marié(e)', 'Pacsé(e)', 'Divorcé(e)', 'Veuf(ve)'];
    public const LIENS      = ['Conjoint', 'Parent', 'Enfant', 'Frère / sœur', 'Ami(e)', 'Autre'];
    public const TYPES_SEJOUR = [
        'AMBULATOIRE'              => 'Ambulatoire',
        'HOSPITALISATION_COMPLETE' => 'Hospitalisation complète',
    ];
    public const DUREE_MAX_NUITS = 90;

    /**
     * Étape 1 : identité, coordonnées, couverture sociale, informations complémentaires.
     *
     * @param int[] $idsOrganismes organismes d'assurance maladie existants en base
     * @param int[] $idsMutuelles  mutuelles existantes en base
     */
    public static function patient(array $saisie, array $idsOrganismes, array $idsMutuelles, ?DateTimeImmutable $aujourdhui = null): array
    {
        $aujourdhui ??= new DateTimeImmutable('today');
        $e = [];
        $d = [];

        // --- Identité ---
        $d['civilite']       = self::choix($saisie, 'civilite', self::CIVILITES, false, $e);
        $d['nom_usage']      = self::texte($saisie, 'nom_usage', 100, true, $e);
        $d['nom_naissance']  = self::texte($saisie, 'nom_naissance', 100, false, $e);
        $d['prenom']         = self::texte($saisie, 'prenom', 100, true, $e);
        $d['sexe']           = self::choix($saisie, 'sexe', array_keys(self::SEXES), true, $e);
        $d['lieu_naissance'] = self::texte($saisie, 'lieu_naissance', 100, false, $e);
        $d['nationalite']    = self::texte($saisie, 'nationalite', 50, false, $e);

        $naissance = self::date($saisie, 'date_naissance', true, $e);
        if ($naissance !== null && $naissance > $aujourdhui) {
            $e['date_naissance'] = 'La date de naissance ne peut pas être dans le futur.';
        }
        $d['date_naissance'] = $naissance?->format('Y-m-d');

        // --- Numéro de sécurité sociale : 13 caractères + clé de 2 chiffres ---
        $nir = strtoupper(self::sansEspaces($saisie['numero_securite_sociale'] ?? ''));
        if ($nir === '') {
            $e['numero_securite_sociale'] = 'Ce champ est obligatoire.';
        } elseif (!preg_match('/^[12]\d{4}(\d{2}|2A|2B)\d{6}$/', $nir)) {
            $e['numero_securite_sociale'] = 'Numéro invalide : 13 chiffres attendus, commençant par 1 ou 2.';
        }
        $d['numero_securite_sociale'] = $nir;

        $cle = self::sansEspaces($saisie['cle_nir'] ?? '');
        if ($cle === '') {
            $e['cle_nir'] = 'Ce champ est obligatoire.';
        } elseif (!preg_match('/^\d{2}$/', $cle) || (int) $cle < 1 || (int) $cle > 97) {
            $e['cle_nir'] = 'Clé invalide : 2 chiffres entre 01 et 97.';
        }
        $d['cle_nir'] = $cle;

        // --- Coordonnées ---
        $d['adresse'] = self::texte($saisie, 'adresse', 255, false, $e);
        $d['ville']   = self::texte($saisie, 'ville', 100, false, $e);

        $cp = self::sansEspaces($saisie['code_postal'] ?? '');
        if ($cp !== '' && !preg_match('/^\d{5}$/', $cp)) {
            $e['code_postal'] = 'Code postal invalide : 5 chiffres attendus.';
        }
        $d['code_postal'] = $cp === '' ? null : $cp;

        $d['tel_portable'] = self::telephone($saisie, 'tel_portable', true, '/^0[67]\d{8}$/',
            'Téléphone portable invalide : 10 chiffres commençant par 06 ou 07.', $e);
        $d['tel_fixe'] = self::telephone($saisie, 'tel_fixe', false, '/^0[1-59]\d{8}$/',
            'Téléphone fixe invalide : 10 chiffres commençant par 01 à 05 ou 09.', $e);

        $email = self::texte($saisie, 'email', 100, false, $e);
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $e['email'] = 'Adresse e-mail invalide.';
        }
        $d['email'] = $email;

        // --- Couverture sociale ---
        $d['id_organisme']    = self::identifiant($saisie, 'id_organisme', $idsOrganismes, true, $e);
        $d['regime']          = self::choix($saisie, 'regime', self::REGIMES, true, $e);
        $d['exoneration_ald'] = self::texte($saisie, 'exoneration_ald', 100, false, $e);
        $d['date_fin_droits']     = self::date($saisie, 'date_fin_droits', false, $e)?->format('Y-m-d');
        $lecture = self::date($saisie, 'date_lecture_vitale', false, $e);
        if ($lecture !== null && $lecture > $aujourdhui) {
            $e['date_lecture_vitale'] = 'La date de lecture de la carte Vitale ne peut pas être dans le futur.';
        }
        $d['date_lecture_vitale'] = $lecture?->format('Y-m-d');

        $d['id_mutuelle']     = self::identifiant($saisie, 'id_mutuelle', $idsMutuelles, false, $e);
        $d['numero_adherent'] = self::texte($saisie, 'numero_adherent', 50, false, $e);
        if ($d['numero_adherent'] !== null && $d['id_mutuelle'] === null && !isset($e['id_mutuelle'])) {
            $e['id_mutuelle'] = 'Choisissez la mutuelle correspondant au numéro d\'adhérent.';
        }
        $d['medecin_traitant'] = self::texte($saisie, 'medecin_traitant', 150, false, $e);

        // --- Informations complémentaires ---
        $d['situation_familiale'] = self::choix($saisie, 'situation_familiale', self::SITUATIONS, false, $e);
        $d['profession']          = self::texte($saisie, 'profession', 100, false, $e);
        $d['langue']              = self::texte($saisie, 'langue', 50, false, $e);
        foreach (['besoin_interprete', 'consentement_sms', 'consentement_dmp', 'opposition_presence'] as $case) {
            $d[$case] = !empty($saisie[$case]);
        }

        return [$d, $e];
    }

    /**
     * Étape 2 : hospitalisation et personne de confiance.
     *
     * @param int[] $idsServices   services existants en base
     * @param int[] $idsPraticiens médecins existants en base
     */
    public static function sejour(array $saisie, array $idsServices, array $idsPraticiens, ?DateTimeImmutable $aujourdhui = null): array
    {
        $aujourdhui ??= new DateTimeImmutable('today');
        $e = [];
        $d = [];

        // --- Hospitalisation ---
        $d['motif_hospitalisation']    = self::texte($saisie, 'motif_hospitalisation', 255, true, $e);
        $d['type_sejour']              = self::choix($saisie, 'type_sejour', array_keys(self::TYPES_SEJOUR), true, $e);
        $d['id_service']               = self::identifiant($saisie, 'id_service', $idsServices, true, $e);
        $d['id_personnel_responsable'] = self::identifiant($saisie, 'id_personnel_responsable', $idsPraticiens, true, $e);

        $entree = self::date($saisie, 'date_prevue_admission', true, $e);
        if ($entree !== null && $entree < $aujourdhui) {
            $e['date_prevue_admission'] = 'La date d\'entrée prévue ne peut pas être dans le passé.';
        }
        $d['date_prevue_admission'] = $entree?->format('Y-m-d');

        $heure = trim((string) ($saisie['heure_prevue_admission'] ?? ''));
        if ($heure === '') {
            $e['heure_prevue_admission'] = 'Ce champ est obligatoire.';
        } elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heure)) {
            $e['heure_prevue_admission'] = 'Heure invalide : format HH:MM attendu.';
        }
        $d['heure_prevue_admission'] = $heure;

        $duree = trim((string) ($saisie['duree_prevue_nuits'] ?? ''));
        if ($duree === '') {
            $d['duree_prevue_nuits'] = null;
        } elseif (!ctype_digit($duree) || (int) $duree > self::DUREE_MAX_NUITS) {
            $e['duree_prevue_nuits'] = 'Durée invalide : nombre de nuits entre 0 et ' . self::DUREE_MAX_NUITS . '.';
            $d['duree_prevue_nuits'] = null;
        } else {
            $d['duree_prevue_nuits'] = (int) $duree;
        }
        if ($d['type_sejour'] === 'AMBULATOIRE' && ($d['duree_prevue_nuits'] ?? 0) > 0) {
            $e['duree_prevue_nuits'] = 'Un séjour ambulatoire ne comporte aucune nuit.';
        }
        $d['chambre_particuliere'] = !empty($saisie['chambre_particuliere']);

        // --- Personne de confiance ---
        $d['contact_nom']       = self::texte($saisie, 'contact_nom', 100, true, $e);
        $d['contact_prenom']    = self::texte($saisie, 'contact_prenom', 100, true, $e);
        $d['contact_lien']      = self::choix($saisie, 'contact_lien', self::LIENS, true, $e);
        $d['contact_telephone'] = self::telephone($saisie, 'contact_telephone', true, '/^0[1-9]\d{8}$/',
            'Téléphone invalide : 10 chiffres commençant par 0.', $e);
        $d['contact_a_prevenir'] = !empty($saisie['contact_a_prevenir']);

        return [$d, $e];
    }

    // ---------------------------------------------------------------- outils

    private static function sansEspaces(mixed $valeur): string
    {
        return is_string($valeur) ? preg_replace('/[\s.\-]/u', '', $valeur) : '';
    }

    private static function texte(array $saisie, string $cle, int $max, bool $obligatoire, array &$e): ?string
    {
        $valeur = is_string($saisie[$cle] ?? null) ? trim($saisie[$cle]) : '';
        if ($valeur === '') {
            if ($obligatoire) {
                $e[$cle] = 'Ce champ est obligatoire.';
            }
            return null;
        }
        if (mb_strlen($valeur) > $max) {
            $e[$cle] = "Texte trop long : $max caractères maximum.";
        }
        return $valeur;
    }

    private static function choix(array $saisie, string $cle, array $valeursPermises, bool $obligatoire, array &$e): ?string
    {
        $valeur = is_string($saisie[$cle] ?? null) ? trim($saisie[$cle]) : '';
        if ($valeur === '') {
            if ($obligatoire) {
                $e[$cle] = 'Ce champ est obligatoire.';
            }
            return null;
        }
        if (!in_array($valeur, $valeursPermises, true)) {
            $e[$cle] = 'Valeur non proposée dans la liste.';
            return null;
        }
        return $valeur;
    }

    /** Identifiant choisi dans une liste déroulante : doit exister en base. */
    private static function identifiant(array $saisie, string $cle, array $idsPermis, bool $obligatoire, array &$e): ?int
    {
        $valeur = is_scalar($saisie[$cle] ?? null) ? trim((string) $saisie[$cle]) : '';
        if ($valeur === '') {
            if ($obligatoire) {
                $e[$cle] = 'Ce champ est obligatoire.';
            }
            return null;
        }
        if (!ctype_digit($valeur) || !in_array((int) $valeur, array_map('intval', $idsPermis), true)) {
            $e[$cle] = 'Valeur non proposée dans la liste.';
            return null;
        }
        return (int) $valeur;
    }

    /** Date au format AAAA-MM-JJ (celui envoyé par un champ <input type="date">). */
    private static function date(array $saisie, string $cle, bool $obligatoire, array &$e): ?DateTimeImmutable
    {
        $valeur = is_string($saisie[$cle] ?? null) ? trim($saisie[$cle]) : '';
        if ($valeur === '') {
            if ($obligatoire) {
                $e[$cle] = 'Ce champ est obligatoire.';
            }
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);
        if ($date === false || $date->format('Y-m-d') !== $valeur) {
            $e[$cle] = 'Date invalide.';
            return null;
        }
        return $date;
    }

    /** Téléphone : espaces, points et tirets acceptés à la saisie, 10 chiffres enregistrés. */
    private static function telephone(array $saisie, string $cle, bool $obligatoire, string $format, string $message, array &$e): ?string
    {
        $valeur = self::sansEspaces($saisie[$cle] ?? '');
        if ($valeur === '') {
            if ($obligatoire) {
                $e[$cle] = 'Ce champ est obligatoire.';
            }
            return null;
        }
        if (!preg_match($format, $valeur)) {
            $e[$cle] = $message;
        }
        return $valeur;
    }
}
