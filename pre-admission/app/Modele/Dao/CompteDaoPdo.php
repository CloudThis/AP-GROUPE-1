<?php
namespace App\Modele\Dao;

use App\Modele\Entite\Compte;
use App\Modele\Entite\Profil;
use PDO;

/** Implémentation PDO — uniquement des requêtes préparées (anti-injection SQL). */
final class CompteDaoPdo implements CompteDao
{
    // Le compte + son habilitation encore valide (non expirée).
    private const SQL_COMPTE = <<<SQL
        SELECT p.id_personnel, p.identifiant, p.nom, p.prenom,
               p.mot_de_passe_hash, p.compte_actif, h.code_profil
        FROM personnel p
        JOIN possede po     ON po.id_personnel = p.id_personnel
        JOIN habilitation h ON h.id_habilitation = po.id_habilitation
        WHERE p.identifiant = :identifiant
          AND (po.date_expiration IS NULL OR po.date_expiration >= :aujourdhui)
        LIMIT 1
        SQL;

    private const SQL_DERNIERE_CONNEXION =
        'UPDATE personnel SET derniere_connexion = :maintenant WHERE id_personnel = :id';

    public function __construct(private readonly PDO $pdo) {}

    public function trouverParIdentifiant(string $identifiant): ?Compte
    {
        $requete = $this->pdo->prepare(self::SQL_COMPTE);
        $requete->execute([
            'identifiant' => $identifiant,
            'aujourdhui'  => date('Y-m-d'),
        ]);
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);
        if ($ligne === false) {
            return null;
        }
        return new Compte(
            (int) $ligne['id_personnel'],
            $ligne['identifiant'],
            $ligne['nom'],
            $ligne['prenom'],
            $ligne['mot_de_passe_hash'],
            (bool) $ligne['compte_actif'],
            Profil::from($ligne['code_profil']),
        );
    }

    public function enregistrerConnexion(int $idPersonnel): void
    {
        $requete = $this->pdo->prepare(self::SQL_DERNIERE_CONNEXION);
        $requete->execute(['maintenant' => date('Y-m-d H:i:s'), 'id' => $idPersonnel]);
    }
}
