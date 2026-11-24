-- =====================================================================
-- Évolution n° 1 de la base clinique_lfph (Sprint 4)
-- Ajoute à la table service une colonne est_medical :
--   1 = service médical, proposé dans la pré-admission
--   0 = service interne (rattachement du personnel), jamais proposé
--
-- À exécuter UNE fois, en root, dans phpMyAdmin (base clinique_lfph, onglet SQL),
-- AVANT de déployer la version de l'application qui utilise cette colonne.
-- Pas besoin de nouveaux droits pour le compte applicatif preadmission_app.
-- =====================================================================

ALTER TABLE service
  ADD COLUMN est_medical TINYINT(1) NOT NULL DEFAULT 1
  COMMENT '1 = service médical proposé dans la pré-admission, 0 = service interne'
  AFTER localisation;

UPDATE service SET est_medical = 0
WHERE nom_service IN ('Administration', 'Systèmes d''information');

-- Vérification : Administration et Systèmes d'information doivent être à 0
SELECT id_service, nom_service, est_medical FROM service ORDER BY id_service;
