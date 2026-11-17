<?php
/** Échappe une valeur avant de l'afficher dans le HTML (protection XSS). À utiliser dans toutes les vues. */
function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}
