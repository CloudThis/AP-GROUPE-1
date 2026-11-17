<?php
namespace App\Modele\Dao;

use PDO;

/** Fournit une connexion PDO unique à la base clinique_lfph. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connexion(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../../../config/config.php';
            self::$pdo = new PDO($config['db']['dsn'], $config['db']['user'], $config['db']['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,   // vraies requêtes préparées
            ]);
        }
        return self::$pdo;
    }
}
