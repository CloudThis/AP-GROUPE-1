<?php
/**
 * VUE : création d'une pré-admission — étape 2 « Pré-admission ».
 * Affichage uniquement, aucune requête SQL ici.
 *
 * @var array  $ref       listes déroulantes (services, praticiens)
 * @var array  $patient   données validées de l'étape 1
 * @var string $organisme nom de l'organisme d'assurance maladie du patient
 * @var array  $valeurs   valeurs à réafficher
 * @var array  $erreurs   [champ => message]
 */
use App\Modele\Service\ValidationPreadmission as V;

$titre = 'Nouvelle pré-admission — Séjour';
require __DIR__ . '/../partials/haut.php';
require __DIR__ . '/../partials/entete.php';
require_once __DIR__ . '/../partials/formulaire.php';

$services   = array_column($ref['services'], 'nom', 'id');
$praticiens = [];
foreach ($ref['praticiens'] as $p) {
    $praticiens[$p['id']] = 'Dr ' . $p['prenom'] . ' ' . $p['nom'];
}
$nomComplet = trim(($patient['civilite'] ?? '') . ' ' . $patient['prenom'] . ' ' . $patient['nom_usage']);
if (!empty($patient['nom_naissance']) && $patient['nom_naissance'] !== $patient['nom_usage']) {
    $nomComplet .= ' (né(e) ' . $patient['nom_naissance'] . ')';
}
?>
<main class="large">
  <div class="titre-page">
    <div>
      <h1>Nouvelle pré-admission</h1>
      <p class="sous-titre">Séjour et personne de confiance</p>
    </div>
    <ol class="etapes">
      <li class="faite"><span>1</span> Patient</li>
      <li class="active"><span>2</span> Pré-admission</li>
    </ol>
  </div>

  <div class="bandeau-patient">
    <div>
      <strong><?= e($nomComplet) ?></strong><br>
      <?= e($patient['sexe']) ?> · <?= e(date_fr($patient['date_naissance'])) ?>
      · NIR <?= e($patient['numero_securite_sociale']) ?> <?= e($patient['cle_nir']) ?>
      · <?= e($organisme) ?>
    </div>
    <a class="lien-bouton" href="index.php?page=preadmission_patient">Modifier</a>
  </div>

  <?php if (!empty($erreur)): ?>
    <div class="message erreur" role="alert"><?= e($erreur) ?></div>
  <?php endif; ?>

  <form method="post" action="index.php?page=preadmission_sejour" novalidate>
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <div class="colonnes">
      <section class="bloc">
        <h2>1. Hospitalisation</h2>
        <?= champ('motif_hospitalisation', 'Motif d\'hospitalisation', $valeurs, $erreurs, ['obligatoire' => true, 'attributs' => ['maxlength' => 255]]) ?>
        <?= boutons_radio('type_sejour', 'Type de séjour', V::TYPES_SEJOUR, $valeurs, $erreurs, true) ?>
        <div class="ligne">
          <?= liste('id_service', 'Service concerné', $services, $valeurs, $erreurs, true) ?>
          <?= liste('id_personnel_responsable', 'Praticien responsable', $praticiens, $valeurs, $erreurs, true) ?>
        </div>
        <div class="ligne">
          <?= champ('date_prevue_admission', 'Date d\'entrée prévue', $valeurs, $erreurs, ['type' => 'date', 'obligatoire' => true, 'attributs' => ['min' => date('Y-m-d')]]) ?>
          <?= champ('heure_prevue_admission', 'Heure d\'arrivée', $valeurs, $erreurs, ['type' => 'time', 'obligatoire' => true]) ?>
        </div>
        <div class="ligne">
          <?= champ('duree_prevue_nuits', 'Durée prévue (nuits)', $valeurs, $erreurs, ['type' => 'number', 'attributs' => ['min' => 0, 'max' => V::DUREE_MAX_NUITS]]) ?>
          <div class="champ"><span class="faux-libelle">Chambre particulière</span><?= case_a_cocher('chambre_particuliere', 'Souhaitée', $valeurs) ?></div>
        </div>
      </section>

      <section class="bloc">
        <h2>2. Personne de confiance</h2>
        <div class="ligne">
          <?= champ('contact_nom', 'Nom', $valeurs, $erreurs, ['obligatoire' => true, 'attributs' => ['maxlength' => 100, 'autocomplete' => 'off']]) ?>
          <?= champ('contact_prenom', 'Prénom', $valeurs, $erreurs, ['obligatoire' => true, 'attributs' => ['maxlength' => 100, 'autocomplete' => 'off']]) ?>
        </div>
        <div class="ligne">
          <?= liste('contact_lien', 'Lien avec le patient', choix_simples(V::LIENS), $valeurs, $erreurs, true) ?>
          <?= champ('contact_telephone', 'Téléphone', $valeurs, $erreurs, ['type' => 'tel', 'obligatoire' => true, 'attributs' => ['placeholder' => '06 98 76 54 32']]) ?>
        </div>
        <?= case_a_cocher('contact_a_prevenir', 'Est aussi la personne à prévenir', $valeurs) ?>
        <p class="note">Formulaire de désignation : signature à recueillir à l'admission.</p>
      </section>
    </div>

    <p class="note"><span class="etoile">*</span> Champs obligatoires. Le volet médical est saisi par le médecin, pas sur cet écran.</p>

    <div class="actions">
      <a class="lien-bouton" href="index.php?page=preadmission_patient">Retour : Patient</a>
      <button type="submit" name="action" value="brouillon" class="bouton-secondaire">Enregistrer brouillon</button>
      <button type="submit" name="action" value="valider">Valider la pré-admission</button>
    </div>
  </form>
</main>
<?php require __DIR__ . '/../partials/bas.php'; ?>
