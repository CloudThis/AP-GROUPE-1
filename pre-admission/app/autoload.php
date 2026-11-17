<?php
// Chargement automatique des classes : App\Modele\Service\AuthService -> app/Modele/Service/AuthService.php
spl_autoload_register(function (string $classe): void {
    if (str_starts_with($classe, 'App\\')) {
        $fichier = __DIR__ . '/' . str_replace('\\', '/', substr($classe, 4)) . '.php';
        if (is_file($fichier)) {
            require $fichier;
        }
    }
});
