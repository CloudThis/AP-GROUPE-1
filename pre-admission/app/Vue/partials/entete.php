<?php /** @var App\Modele\Entite\Utilisateur $utilisateur  @var string $csrf */ ?>
<header class="barre">
  <strong>Pré-admission LPFH — <?= e($utilisateur->profil->libelle()) ?></strong>
  <div class="barre-droite">
    <span><?= e($utilisateur->prenom) ?> <?= e($utilisateur->nom) ?></span>
    <form method="post" action="index.php?page=deconnexion">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <button type="submit" class="bouton-clair">Se déconnecter</button>
    </form>
  </div>
</header>
