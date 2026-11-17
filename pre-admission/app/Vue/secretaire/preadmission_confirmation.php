<?php
/**
 * VUE : confirmation après l'enregistrement d'une pré-admission.
 * @var array $resume pré-admission relue en base
 */
use App\Modele\Service\ValidationPreadmission as V;

$titre = 'Pré-admission enregistrée';
require __DIR__ . '/../partials/haut.php';
require __DIR__ . '/../partials/entete.php';
require_once __DIR__ . '/../partials/formulaire.php';

$brouillon = $resume['statut'] === 'BROUILLON';
?>
<main>
  <div class="message info" role="status">
    <?= $brouillon
        ? 'Brouillon enregistré.'
        : 'Pré-admission enregistrée : elle est en attente de validation par le département administratif.' ?>
  </div>

  <section class="bloc">
    <h2>Pré-admission n° <?= e((string) $resume['id_preadmission']) ?></h2>
    <dl class="resume">
      <dt>Patient</dt>
      <dd><?= e(trim(($resume['civilite'] ?? '') . ' ' . $resume['prenom'] . ' ' . $resume['nom_usage'])) ?>
          — né(e) le <?= e(date_fr($resume['date_naissance'])) ?></dd>
      <dt>Motif</dt>
      <dd><?= e($resume['motif_hospitalisation']) ?></dd>
      <dt>Type de séjour</dt>
      <dd><?= e(V::TYPES_SEJOUR[$resume['type_sejour']] ?? $resume['type_sejour']) ?></dd>
      <dt>Service</dt>
      <dd><?= e($resume['nom_service']) ?></dd>
      <dt>Praticien responsable</dt>
      <dd>Dr <?= e($resume['praticien_prenom'] . ' ' . $resume['praticien_nom']) ?></dd>
      <dt>Entrée prévue</dt>
      <dd>le <?= e(date_fr($resume['date_prevue_admission'])) ?> à <?= e(substr((string) $resume['heure_prevue_admission'], 0, 5)) ?>
        <?php if ($resume['duree_prevue_nuits'] !== null): ?>
          — <?= e((string) $resume['duree_prevue_nuits']) ?> nuit(s)
        <?php endif; ?>
      </dd>
      <dt>Statut</dt>
      <dd><?= $brouillon ? 'Brouillon' : 'En attente de validation' ?></dd>
    </dl>
  </section>

  <div class="actions">
    <a class="lien-bouton" href="index.php?page=accueil_secretaire">Retour à l'accueil</a>
    <a class="lien-bouton principal" href="index.php?page=preadmission_patient">Nouvelle pré-admission</a>
  </div>
</main>
<?php require __DIR__ . '/../partials/bas.php'; ?>
