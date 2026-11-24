# Retours du Product Owner — Sprint Review du Sprint 3

AP « Pré-admission LPFH » — Groupe 1. Review du 1er incrément (version `v0.1`).

| # | Retour du Product Owner | Décision | Réalisation |
|---|---|---|---|
| 1 | Retirer les services non médicaux (Administration, Systèmes d'information) de la liste des services de la pré-admission, en gardant l'intégrité des données | À faire évoluer | Colonne booléenne `service.est_medical` (script `pre-admission/bdd/evolution_01_service_medical.sql`) ; la pré-admission ne propose que les services médicaux. Les services internes restent en base pour le rattachement du personnel. |
| 2 | Refuser les dates de naissance trop anciennes et dans le futur | À faire évoluer | Contrôle côté serveur : pas de date dans le futur, âge maximal 120 ans ; mêmes bornes dans le champ de saisie. |
| 3 | Vérifier qu'une session inactive est déconnectée automatiquement, sinon la mettre en place | À vérifier | Déconnexion après 30 min d'inactivité (déjà en place) ; durée de conservation PHP alignée sur 30 min et message « déconnecté après 30 minutes d'inactivité » sur la page de connexion. |
| 4 | Renommer « Langue parlée » en « Langue maternelle » | À faire évoluer | Libellé modifié dans l'écran patient (la colonne `langue` de la base est inchangée). |
| 5 | Renommer « Lieu de naissance » en « Ville de naissance » | À faire évoluer | Libellé modifié dans l'écran patient (la colonne `lieu_naissance` de la base est inchangée). |

## Non terminé au Sprint 3

- Prise de rendez-vous et pièces justificatives (maquette 2) : non développées, renvoyées au Product Backlog.
