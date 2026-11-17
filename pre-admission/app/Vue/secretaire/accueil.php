<?php
/**
 * VUE : accueil — Espace secrétaire médicale (tableau de bord).
 * @var array $preadmissions dernières pré-admissions saisies, la plus récente en premier
 */
use App\Modele\Service\ValidationPreadmission as V;

$titre = 'Espace secrétaire médicale';
require __DIR__ . '/../partials/haut.php';
require __DIR__ . '/../partials/entete.php';
require_once __DIR__ . '/../partials/formulaire.php';

$statuts = [
    'BROUILLON'             => 'Brouillon',
    'EN_ATTENTE_VALIDATION' => 'En attente de validation',
    'VALIDEE'               => 'Validée',
    'REJETEE'               => 'Rejetée',
];
?>
<main class="large">
  <div class="titre-page">
    <div>
      <h1>Espace secrétaire médicale</h1>
      <p class="sous-titre">Création et suivi des pré-admissions (données administratives).</p>
    </div>
    <a class="lien-bouton principal" href="index.php?page=preadmission_patient">Nouvelle pré-admission</a>
  </div>

  <section class="bloc">
    <h2>Pré-admissions saisies (<?= count($preadmissions) ?>)</h2>
    <?php if ($preadmissions === []): ?>
      <p class="note">Aucune pré-admission pour l'instant.</p>
    <?php else: ?>
      <div class="tableau-defilant">
        <table class="tableau">
          <thead>
            <tr>
              <th scope="col">N°</th>
              <th scope="col">Patient</th>
              <th scope="col">Motif</th>
              <th scope="col">Service</th>
              <th scope="col">Type de séjour</th>
              <th scope="col">Entrée prévue</th>
              <th scope="col">Statut</th>
              <th scope="col">Saisie le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($preadmissions as $pa): ?>
              <tr>
                <td><a href="index.php?page=preadmission_confirmation&amp;id=<?= e((string) $pa['id_preadmission']) ?>"><?= e((string) $pa['id_preadmission']) ?></a></td>
                <td><?= e(trim(($pa['civilite'] ?? '') . ' ' . $pa['prenom'] . ' ' . $pa['nom_usage'])) ?></td>
                <td><?= e($pa['motif_hospitalisation']) ?></td>
                <td><?= e($pa['nom_service']) ?></td>
                <td><?= e(V::TYPES_SEJOUR[$pa['type_sejour']] ?? $pa['type_sejour']) ?></td>
                <td><?= e(date_fr($pa['date_prevue_admission'])) ?> à <?= e(substr((string) $pa['heure_prevue_admission'], 0, 5)) ?></td>
                <td><span class="statut statut-<?= e(strtolower($pa['statut'])) ?>"><?= e($statuts[$pa['statut']] ?? $pa['statut']) ?></span></td>
                <td><?= e(date_fr($pa['date_saisie'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/../partials/bas.php'; ?>
