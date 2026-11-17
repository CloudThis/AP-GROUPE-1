<?php
/** VUE : formulaire de connexion. Affichage uniquement, aucune requête SQL ici. */
$titre = 'Connexion';
require __DIR__ . '/partials/haut.php';
?>
<div class="centre">
  <form class="carte" method="post" action="index.php?page=connexion" novalidate>
    <h1>Clinique LPFH</h1>
    <p class="sous-titre">Application de pré-admission</p>

    <?php if (!empty($erreur)): ?>
      <div class="message erreur" role="alert"><?= e($erreur) ?></div>
    <?php elseif (!empty($deconnecte)): ?>
      <div class="message info">Vous êtes déconnecté.</div>
    <?php endif; ?>

    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

    <label for="identifiant">Identifiant</label>
    <input type="text" id="identifiant" name="identifiant" required autofocus
           autocomplete="username" value="<?= e($identifiant ?? '') ?>">

    <label for="mot_de_passe">Mot de passe</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">

    <button type="submit">Se connecter</button>
  </form>
</div>
<?php require __DIR__ . '/partials/bas.php'; ?>
