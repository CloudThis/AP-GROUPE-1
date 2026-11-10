# Pré-admission LPFH — Authentification et création d'une pré-admission (Sprint 3)

Application web PHP 8 en MVC, sans framework, base MariaDB/MySQL (XAMPP).

## Installation (XAMPP)
1. Copier le dossier du projet dans `C:\xampp\htdocs\pre-admission`.
2. Démarrer **Apache** et **MySQL** dans XAMPP.
3. Dans phpMyAdmin (*Importer*), importer `bdd/clinique_lfph_v2.sql`.
4. Vérifier `config/config.php` (avec XAMPP : `root` sans mot de passe).
5. Ouvrir **http://localhost/pre-admission/**

Comptes de test : `secretaire / Secret2026!`, `medecin / Medecin2026!`,
`administratif / Admin2026!`, `informaticien / Info2026!`.

## Organisation du code (MVC)

```
pre-admission/
├── public/                       seul dossier ouvert au navigateur
│   ├── index.php                 CONTRÔLEUR FRONTAL : toutes les pages passent ici (?page=...)
│   │                             -> vérifie session + profil AVANT d'afficher une page protégée
│   └── css/style.css
├── app/
│   ├── Modele/                   M — MODÈLE : données et règles métier
│   │   ├── Entite/               Profil, Utilisateur, Compte
│   │   ├── Dao/                  Database (PDO), CompteDao, CompteDaoPdo (requêtes préparées)
│   │   └── Service/              AuthService (password_verify), ControleAcces (page -> profil autorisé)
│   ├── Controleur/               C — CONTRÔLEUR : reçoit la requête, appelle le modèle, choisit la vue
│   │   ├── ConnexionControleur   formulaire (GET) + vérification et session (POST)
│   │   ├── DeconnexionControleur destruction de la session
│   │   ├── AccueilControleur     écran d'accueil de chaque profil
│   │   ├── Session               session PHP sécurisée, jeton CSRF, expiration 30 min
│   │   └── Rendu                 affichage d'une vue, redirection
│   ├── Vue/                      V — VUE : HTML uniquement, aucune requête SQL
│   │   ├── connexion.php
│   │   ├── secretaire/ medecin/ administratif/ informaticien/  accueil.php
│   │   ├── erreur403.php, erreur404.php
│   │   └── partials/             haut, bas, entête avec bouton de déconnexion
│   ├── autoload.php
│   └── fonctions.php             e() : échappement HTML (anti-XSS)
├── config/config.php             paramètres de la base
├── bdd/clinique_lfph_v2.sql      script de la base + 4 comptes de test
└── tests/                        tests unitaires PHPUnit
```

Règles pour garder le MVC :
- une **vue** n'accède jamais à la base ; elle affiche les variables données par le contrôleur, toujours avec `e()` ;
- un **contrôleur** ne contient pas de SQL, il appelle le modèle ;
- le **modèle** ne fait aucun affichage.

### Ajouter une page (ex. création de pré-admission)
1. Dans `app/Modele/Service/ControleAcces.php`, ajouter la page dans `PAGES_PROTEGEES`
   avec le profil autorisé : `'preadmission_nouvelle' => Profil::SECRETAIRE,`
2. Créer son contrôleur dans `app/Controleur/` et sa vue dans `app/Vue/secretaire/`.
3. Ajouter le `case 'preadmission_nouvelle':` dans `public/index.php`.
La protection (session + profil) est alors automatique.

## Sécurité
- Mots de passe vérifiés avec `password_verify()` contre le hash bcrypt (jamais en clair).
- Message d'erreur **générique** : « Identifiant ou mot de passe incorrect. »
- Requêtes **préparées** PDO (`ATTR_EMULATE_PREPARES = false`) : anti-injection SQL.
- Session : `session_regenerate_id()` à la connexion, cookie `HttpOnly` + `SameSite=Strict`,
  expiration après 30 min d'inactivité, destruction complète à la déconnexion.
- Jeton **CSRF** sur les formulaires de connexion et de déconnexion.
- Toute sortie HTML échappée avec `e()` (anti-XSS).
- Dossiers `app/`, `config/`, `tests/`, `bdd/` inaccessibles depuis le navigateur (`.htaccess`).

## Tests unitaires (PHPUnit)
Une seule fois, dans le dossier du projet : `composer install`
(Composer : https://getcomposer.org — ou dans PhpStorm/VS Code avec l'extension PHP).
Puis : `vendor/bin/phpunit` (Windows : `vendor\bin\phpunit`) — 19 tests.
PHP doit avoir les extensions `pdo_mysql` et `pdo_sqlite` (c'est le cas du PHP de XAMPP : `C:\xampp\php\php.exe vendor\bin\phpunit`).

| Test | Ce qui est vérifié |
|---|---|
| `AuthServiceTest` | connexion réussie des 4 profils + bonne page ; refus mauvais mot de passe, identifiant inconnu, compte désactivé, champs vides ; message générique |
| `ControleAccesTest` | page protégée sans session → refus ; chaque profil n'accède qu'à son écran ; secrétaire → écran médecin interdit |
| `CompteDaoPdoTest` | requête préparée sur une base SQLite en mémoire : compte trouvé, inconnu, habilitation expirée, tentative d'injection SQL sans effet |


## Création d'une pré-admission (secrétaire médicale)
Pages `?page=preadmission_patient` → `preadmission_sejour`, réservées au profil secrétaire
(les autres profils reçoivent « Accès interdit », sans session : renvoi à la connexion).

1. **Patient** : identité, coordonnées, couverture sociale, informations complémentaires.
2. **Pré-admission** : hospitalisation (motif, type de séjour, service, praticien, date et heure) et personne de confiance.

- Contrôles de saisie (champs obligatoires, formats) vérifiés par `ValidationPreadmission` ; en cas d'erreur, rien n'est enregistré.
- **Premier enregistrement** en base avec des requêtes préparées (`PreadmissionDaoPdo`) : tables `patient` et `preadmission`.
- Données administratives uniquement : le volet médical relève du profil médecin (sprint suivant).
- Reste à faire (séance 8) : enregistrement de la couverture sociale et de la personne de confiance,
  patient déjà connu, écran de confirmation, tests unitaires de la pré-admission.

## Les 5 points de contrôle (fiches de recette)
| # | Action | Résultat attendu |
|---|---|---|
| 1 | Ouvrir http://localhost/pre-admission/ | Formulaire identifiant / mot de passe |
| 2 | Se connecter avec chacun des 4 comptes | Chacun arrive sur son espace (titre + bandeau du profil) |
| 3 | `secretaire` + mauvais mot de passe | « Identifiant ou mot de passe incorrect. » |
| 4 | Bouton « Se déconnecter » | Retour à la connexion avec « Vous êtes déconnecté. » |
| 5 | Fenêtre privée : `.../public/index.php?page=accueil_secretaire` | Renvoi vers la page de connexion |

Bonus : connecté en secrétaire, ouvrir `?page=accueil_medecin` → « Accès interdit » (403).
