<?php
// Paramètres de connexion à la base — à adapter à votre environnement.
// En dev avec XAMPP : utilisateur root sans mot de passe.
// En test / recette : le compte applicatif fourni par le groupe SISR.
return [
    'db' => [
        'dsn'      => 'mysql:host=localhost;port=3306;dbname=clinique_lfph;charset=utf8mb4',
        'user'     => 'root',
        'password' => '',
    ],
];
