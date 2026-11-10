<?php
namespace App\Controleur;

/** Affiche une vue (fichier de app/Vue) avec les données fournies par le contrôleur. */
final class Rendu
{
    public static function vue(string $nom, array $donnees = [], int $codeHttp = 200): void
    {
        http_response_code($codeHttp);
        header('Content-Type: text/html; charset=utf-8');
        extract($donnees, EXTR_SKIP);
        require __DIR__ . '/../Vue/' . $nom . '.php';
    }

    public static function rediriger(string $page, array $parametres = []): void
    {
        header('Location: index.php?' . http_build_query(['page' => $page] + $parametres));
        exit;
    }
}
