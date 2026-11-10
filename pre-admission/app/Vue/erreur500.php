<?php
/** VUE : erreur technique (base de données inaccessible). Aucun détail technique n'est affiché. */
$titre = 'Erreur';
require __DIR__ . '/partials/haut.php';
?>
<div class="centre">
  <div class="carte">
    <h1>Service indisponible</h1>
    <p class="sous-titre">La base de données est inaccessible. Vérifiez que MySQL est démarré, puis réessayez.</p>
    <a href="index.php">Retour</a>
  </div>
</div>
<?php require __DIR__ . '/partials/bas.php'; ?>
