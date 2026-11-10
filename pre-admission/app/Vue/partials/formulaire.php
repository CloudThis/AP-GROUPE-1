<?php
/**
 * Outils d'affichage des formulaires (VUE uniquement).
 * Toutes les valeurs sont échappées avec e() avant d'être écrites dans le HTML.
 */

/** Champ de saisie : libellé + <input> + message d'erreur éventuel. */
function champ(string $nom, string $libelle, array $valeurs, array $erreurs, array $options = []): string
{
    $type        = $options['type'] ?? 'text';
    $obligatoire = !empty($options['obligatoire']);
    $attributs   = '';
    foreach ($options['attributs'] ?? [] as $attribut => $valeur) {
        $attributs .= ' ' . e($attribut) . '="' . e((string) $valeur) . '"';
    }
    $valeur = is_scalar($valeurs[$nom] ?? null) ? (string) $valeurs[$nom] : '';
    if ($type === 'time') {
        $valeur = substr($valeur, 0, 5);
    }

    return '<div class="champ' . (isset($erreurs[$nom]) ? ' en-erreur' : '') . '">'
        . '<label for="' . e($nom) . '">' . e($libelle) . ($obligatoire ? ' <span class="etoile">*</span>' : '') . '</label>'
        . '<input type="' . e($type) . '" id="' . e($nom) . '" name="' . e($nom) . '" value="' . e($valeur) . '"'
        . ($obligatoire ? ' required' : '') . $attributs . '>'
        . message_erreur($nom, $erreurs)
        . '</div>';
}

/**
 * Liste déroulante.
 * @param array $choix [valeur => libellé affiché]
 */
function liste(string $nom, string $libelle, array $choix, array $valeurs, array $erreurs, bool $obligatoire = false): string
{
    $actuel = is_scalar($valeurs[$nom] ?? null) ? (string) $valeurs[$nom] : '';
    $html = '<div class="champ' . (isset($erreurs[$nom]) ? ' en-erreur' : '') . '">'
        . '<label for="' . e($nom) . '">' . e($libelle) . ($obligatoire ? ' <span class="etoile">*</span>' : '') . '</label>'
        . '<select id="' . e($nom) . '" name="' . e($nom) . '"' . ($obligatoire ? ' required' : '') . '>'
        . '<option value="">— Choisir —</option>';
    foreach ($choix as $valeur => $texte) {
        $html .= '<option value="' . e((string) $valeur) . '"' . ((string) $valeur === $actuel ? ' selected' : '') . '>'
            . e((string) $texte) . '</option>';
    }
    return $html . '</select>' . message_erreur($nom, $erreurs) . '</div>';
}

/** Boutons radio (un seul choix possible). */
function boutons_radio(string $nom, string $libelle, array $choix, array $valeurs, array $erreurs, bool $obligatoire = false): string
{
    $actuel = is_scalar($valeurs[$nom] ?? null) ? (string) $valeurs[$nom] : '';
    $html = '<fieldset class="champ' . (isset($erreurs[$nom]) ? ' en-erreur' : '') . '">'
        . '<legend>' . e($libelle) . ($obligatoire ? ' <span class="etoile">*</span>' : '') . '</legend><div class="radios">';
    foreach ($choix as $valeur => $texte) {
        $html .= '<label class="radio"><input type="radio" name="' . e($nom) . '" value="' . e((string) $valeur) . '"'
            . ((string) $valeur === $actuel ? ' checked' : '') . ($obligatoire ? ' required' : '') . '> '
            . e((string) $texte) . '</label>';
    }
    return $html . '</div>' . message_erreur($nom, $erreurs) . '</fieldset>';
}

function case_a_cocher(string $nom, string $libelle, array $valeurs): string
{
    return '<label class="case"><input type="checkbox" name="' . e($nom) . '" value="1"'
        . (!empty($valeurs[$nom]) ? ' checked' : '') . '> ' . e($libelle) . '</label>';
}

function message_erreur(string $nom, array $erreurs): string
{
    return isset($erreurs[$nom]) ? '<p class="erreur-champ">' . e($erreurs[$nom]) . '</p>' : '';
}

/** Transforme ['a', 'b'] en ['a' => 'a', 'b' => 'b'] pour les listes dont la valeur est le libellé. */
function choix_simples(array $valeurs): array
{
    return array_combine($valeurs, $valeurs);
}

/** Date AAAA-MM-JJ affichée JJ/MM/AAAA. */
function date_fr(?string $date): string
{
    $d = $date ? DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10)) : false;
    return $d ? $d->format('d/m/Y') : '';
}
