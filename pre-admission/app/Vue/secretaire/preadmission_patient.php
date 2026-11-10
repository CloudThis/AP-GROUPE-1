<?php
/**
 * VUE : création d'une pré-admission — étape 1 « Patient ».
 * Affichage uniquement, aucune requête SQL ici.
 *
 * @var array $ref      listes déroulantes (organismes, mutuelles)
 * @var array $valeurs  valeurs à réafficher
 * @var array $erreurs  [champ => message]
 */
use App\Modele\Service\ValidationPreadmission as V;

$titre = 'Nouvelle pré-admission — Patient';
require __DIR__ . '/../partials/haut.php';
require __DIR__ . '/../partials/entete.php';
require_once __DIR__ . '/../partials/formulaire.php';

$organismes = [];
foreach ($ref['organismes'] as $o) {
    $organismes[$o['id']] = $o['nom'] . ($o['code_caisse'] ? ' (' . $o['code_caisse'] . ')' : '');
}
$mutuelles = array_column($ref['mutuelles'], 'nom', 'id');
?>
<main class="large">
  <div class="titre-page">
    <div>
      <h1>Nouvelle pré-admission</h1>
      <p class="sous-titre">Identification et coordonnées du patient</p>
    </div>
    <ol class="etapes">
      <li class="active"><span>1</span> Patient</li>
      <li><span>2</span> Pré-admission</li>
    </ol>
  </div>

  <?php if (!empty($erreur)): ?>
    <div class="message erreur" role="alert"><?= e($erreur) ?></div>
  <?php endif; ?>

    <form method="post" action="index.php?page=preadmission_patient" novalidate>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <div class="colonnes">
      <section class="bloc">
        <h2>1. Identité du patient</h2>
        <div class="ligne ligne-1-3">
          <?= liste('civilite', 'Civilité', choix_simples(V::CIVILITES), $valeurs, $erreurs) ?>
          <?= champ('nom_usage', 'Nom d\'usage', $valeurs, $erreurs, ['obligatoire' => true, 'attributs' => ['maxlength' => 100, 'autocomplete' => 'off']]) ?>
        </div>
        <div class="ligne">
          <?= champ('nom_naissance', 'Nom de naissance', $valeurs, $erreurs, ['attributs' => ['maxlength' => 100, 'autocomplete' => 'off']]) ?>
          <?= champ('prenom', 'Prénom(s)', $valeurs, $erreurs, ['obligatoire' => true, 'attributs' => ['maxlength' => 100, 'autocomplete' => 'off']]) ?>
        </div>
        <div class="ligne">
          <?= champ('date_naissance', 'Date de naissance', $valeurs, $erreurs, ['type' => 'date', 'obligatoire' => true, 'attributs' => ['max' => date('Y-m-d')]]) ?>
          <?= boutons_radio('sexe', 'Sexe administratif', V::SEXES, $valeurs, $erreurs, true) ?>
        </div>
        <div class="ligne">
          <?= champ('lieu_naissance', 'Lieu de naissance', $valeurs, $erreurs, ['attributs' => ['maxlength' => 100]]) ?>
          <?= champ('nationalite', 'Nationalité', $valeurs, $erreurs, ['attributs' => ['maxlength' => 50]]) ?>
        </div>
        <div class="ligne ligne-3-1">
          <?= champ('numero_securite_sociale', 'N° Sécurité Sociale (NIR)', $valeurs, $erreurs, ['obligatoire' => true, 'attributs' => ['inputmode' => 'numeric', 'placeholder' => '2 78 04 69 123 456', 'autocomplete' => 'off']]) ?>
          <?= champ('cle_nir', 'Clé', $valeurs, $erreurs, ['obligatoire' => true, 'attributs' => ['inputmode' => 'numeric', 'maxlength' => 2, 'autocomplete' => 'off']]) ?>
        </div>
      </section>

      <section class="bloc">
        <h2>3. Couverture sociale</h2>
        <?= liste('id_organisme', 'Organisme d\'Assurance Maladie', $organismes, $valeurs, $erreurs, true) ?>
        <div class="ligne">
          <?= liste('regime', 'Régime', choix_simples(V::REGIMES), $valeurs, $erreurs, true) ?>
          <?= champ('exoneration_ald', 'Exonération / ALD', $valeurs, $erreurs, ['attributs' => ['maxlength' => 100, 'placeholder' => 'Aucune']]) ?>
        </div>
        <div class="ligne">
          <?= champ('date_fin_droits', 'Droits ouverts jusqu\'au', $valeurs, $erreurs, ['type' => 'date']) ?>
          <?= champ('date_lecture_vitale', 'Carte Vitale lue le', $valeurs, $erreurs, ['type' => 'date', 'attributs' => ['max' => date('Y-m-d')]]) ?>
        </div>
        <div class="ligne">
          <?= liste('id_mutuelle', 'Mutuelle / Complémentaire', $mutuelles, $valeurs, $erreurs) ?>
          <?= champ('numero_adherent', 'N° Adhérent mutuelle', $valeurs, $erreurs, ['attributs' => ['maxlength' => 50, 'autocomplete' => 'off']]) ?>
        </div>
        <?= champ('medecin_traitant', 'Médecin traitant', $valeurs, $erreurs, ['attributs' => ['maxlength' => 150]]) ?>
      </section>

      <section class="bloc">
        <h2>2. Coordonnées &amp; contacts</h2>
        <?= champ('adresse', 'Adresse postale', $valeurs, $erreurs, ['attributs' => ['maxlength' => 255]]) ?>
        <div class="ligne ligne-1-3">
          <?= champ('code_postal', 'Code postal', $valeurs, $erreurs, ['attributs' => ['inputmode' => 'numeric', 'maxlength' => 5]]) ?>
          <?= champ('ville', 'Ville', $valeurs, $erreurs, ['attributs' => ['maxlength' => 100]]) ?>
        </div>
        <div class="ligne">
          <?= champ('tel_portable', 'Téléphone portable', $valeurs, $erreurs, ['type' => 'tel', 'obligatoire' => true, 'attributs' => ['placeholder' => '06 12 34 56 78']]) ?>
          <?= champ('tel_fixe', 'Téléphone fixe', $valeurs, $erreurs, ['type' => 'tel', 'attributs' => ['placeholder' => '04 xx xx xx xx']]) ?>
        </div>
        <?= champ('email', 'Adresse e-mail', $valeurs, $erreurs, ['type' => 'email', 'attributs' => ['maxlength' => 100]]) ?>
      </section>

      <section class="bloc">
        <h2>4. Informations complémentaires</h2>
        <div class="ligne">
          <?= liste('situation_familiale', 'Situation familiale', choix_simples(V::SITUATIONS), $valeurs, $erreurs) ?>
          <?= champ('profession', 'Profession', $valeurs, $erreurs, ['attributs' => ['maxlength' => 100]]) ?>
        </div>
        <div class="ligne">
          <?= champ('langue', 'Langue parlée', $valeurs, $erreurs, ['attributs' => ['maxlength' => 50]]) ?>
          <div class="champ"><span class="faux-libelle">Interprète</span><?= case_a_cocher('besoin_interprete', 'Besoin d\'un interprète', $valeurs) ?></div>
        </div>
        <fieldset class="champ">
          <legend>Consentements</legend>
          <?= case_a_cocher('consentement_sms', 'Le patient accepte l\'envoi de SMS de rappel', $valeurs) ?>
          <?= case_a_cocher('consentement_dmp', 'Accès au Dossier Médical Partagé (DMP) autorisé', $valeurs) ?>
          <?= case_a_cocher('opposition_presence', 'Opposition à la communication de sa présence', $valeurs) ?>
        </fieldset>
      </section>
    </div>

    <p class="note"><span class="etoile">*</span> Champs obligatoires. Seules les données administratives sont saisies ici.</p>

    <div class="actions">
      <button type="submit" name="action" value="annuler" class="bouton-secondaire" formnovalidate>Annuler</button>
      <button type="submit" name="action" value="suivant">Suivant : Pré-admission</button>
    </div>
  </form>
</main>
<?php require __DIR__ . '/../partials/bas.php'; ?>
