-- =====================================================================
--  Base clinique_lfph — V2 (Sprint 3, Mission 13)
--  MariaDB / MySQL — moteur InnoDB — encodage utf8mb4
--
--  ⚠ Ce script SUPPRIME puis recrée la base clinique_lfph.
--
--  Comptes de test (mot de passe en clair UNIQUEMENT ici, en base = hash bcrypt) :
--    secretaire     / Secret2026!   -> Secrétaire médicale
--    medecin        / Medecin2026!  -> Médecin
--    administratif  / Admin2026!    -> Département administratif
--    informaticien  / Info2026!     -> Informaticien admin
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS clinique_lfph;
CREATE DATABASE clinique_lfph CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE clinique_lfph;

-- =====================================================================
-- 1. STRUCTURE ET PERSONNEL (authentification, habilitations)
-- =====================================================================

CREATE TABLE service (
  id_service     INT AUTO_INCREMENT PRIMARY KEY,
  nom_service    VARCHAR(100) NOT NULL,
  localisation   VARCHAR(150) NULL
) ENGINE=InnoDB;

CREATE TABLE chambre (
  numero_chambre VARCHAR(20) PRIMARY KEY,
  type_chambre   VARCHAR(50) NULL,              -- individuelle / double
  nb_lits        TINYINT NOT NULL DEFAULT 1,    -- NOUVEAU : utile au tableau d'occupation
  id_service     INT NOT NULL,                  -- CONTIENT
  CONSTRAINT fk_chambre_service FOREIGN KEY (id_service) REFERENCES service(id_service)
) ENGINE=InnoDB;

CREATE TABLE habilitation (
  id_habilitation INT AUTO_INCREMENT PRIMARY KEY,
  code_profil     VARCHAR(30)  NOT NULL UNIQUE, -- NOUVEAU : SECRETAIRE, MEDECIN, ADMINISTRATIF, INFORMATICIEN
  libelle_role    VARCHAR(100) NOT NULL,
  description     TEXT NULL,
  niveau_acces    INT NULL
) ENGINE=InnoDB;

-- NOUVEAU : modules de l'application (maquette admin « matrice des habilitations »)
CREATE TABLE module (
  id_module   INT AUTO_INCREMENT PRIMARY KEY,
  libelle     VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- NOUVEAU : droits d'une habilitation sur un module (lecture / écriture / admin)
CREATE TABLE droit (
  id_habilitation INT NOT NULL,
  id_module       INT NOT NULL,
  lecture         BOOLEAN NOT NULL DEFAULT FALSE,
  ecriture        BOOLEAN NOT NULL DEFAULT FALSE,
  administration  BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (id_habilitation, id_module),
  CONSTRAINT fk_droit_habilitation FOREIGN KEY (id_habilitation) REFERENCES habilitation(id_habilitation),
  CONSTRAINT fk_droit_module       FOREIGN KEY (id_module)       REFERENCES module(id_module)
) ENGINE=InnoDB;

CREATE TABLE personnel (
  id_personnel            INT AUTO_INCREMENT PRIMARY KEY,
  nom                     VARCHAR(100) NOT NULL,
  prenom                  VARCHAR(100) NOT NULL,
  email                   VARCHAR(100) NULL,
  telephone               VARCHAR(20)  NULL,
  fonction                VARCHAR(100) NULL,
  identifiant             VARCHAR(50)  NOT NULL UNIQUE,   -- NOUVEAU : login
  mot_de_passe_hash       VARCHAR(255) NOT NULL,          -- NOUVEAU : hash bcrypt (jamais le mot de passe en clair)
  compte_actif            BOOLEAN NOT NULL DEFAULT TRUE,  -- NOUVEAU : maquette admin « compte actif / révoquer »
  derniere_connexion      DATETIME NULL,                  -- NOUVEAU : traçabilité
  id_service              INT NULL,                       -- RATTACHÉ_À
  id_personnel_encadrant  INT NULL,                       -- ENCADRE (réflexive)
  CONSTRAINT fk_personnel_service  FOREIGN KEY (id_service) REFERENCES service(id_service),
  CONSTRAINT fk_personnel_encadre  FOREIGN KEY (id_personnel_encadrant) REFERENCES personnel(id_personnel)
) ENGINE=InnoDB;

CREATE TABLE possede (
  id_personnel     INT NOT NULL,
  id_habilitation  INT NOT NULL,
  date_attribution DATE NULL,
  date_expiration  DATE NULL,
  PRIMARY KEY (id_personnel, id_habilitation),
  CONSTRAINT fk_possede_personnel    FOREIGN KEY (id_personnel)    REFERENCES personnel(id_personnel),
  CONSTRAINT fk_possede_habilitation FOREIGN KEY (id_habilitation) REFERENCES habilitation(id_habilitation)
) ENGINE=InnoDB;

-- =====================================================================
-- 2. PATIENT ET COUVERTURE SOCIALE
-- =====================================================================

-- PATIENT : uniquement l'identité, les coordonnées et les infos personnelles.
-- RETIRÉS : organisme_AMO, régime, exonération_ALD, date_fin_droits
--           -> déplacés dans EST_COUVERT_PAR (couverture AMO).
CREATE TABLE patient (
  id_patient               INT AUTO_INCREMENT PRIMARY KEY,
  civilite                 VARCHAR(10)  NULL,
  nom_naissance            VARCHAR(100) NULL,
  nom_usage                VARCHAR(100) NOT NULL,
  prenom                   VARCHAR(100) NOT NULL,
  date_naissance           DATE NOT NULL,
  sexe                     ENUM('F','M','Autre') NOT NULL,
  lieu_naissance           VARCHAR(100) NULL,
  nationalite              VARCHAR(50)  NULL,
  numero_securite_sociale  CHAR(13) NOT NULL UNIQUE,     -- NIR sans la clé
  cle_nir                  CHAR(2)  NOT NULL,
  adresse                  VARCHAR(255) NULL,
  code_postal              VARCHAR(10)  NULL,
  ville                    VARCHAR(100) NULL,
  tel_portable             VARCHAR(20)  NOT NULL,
  tel_fixe                 VARCHAR(20)  NULL,
  email                    VARCHAR(100) NULL,
  medecin_traitant         VARCHAR(150) NULL,
  situation_familiale      VARCHAR(30)  NULL,
  profession               VARCHAR(100) NULL,
  langue                   VARCHAR(50)  NULL,
  besoin_interprete        BOOLEAN NOT NULL DEFAULT FALSE,
  consentement_sms         BOOLEAN NOT NULL DEFAULT FALSE,
  consentement_dmp         BOOLEAN NOT NULL DEFAULT FALSE,
  opposition_presence      BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB;

-- NOUVEAU : caisse d'Assurance Maladie Obligatoire (ex. CPAM du Rhône)
CREATE TABLE organisme_amo (
  id_organisme   INT AUTO_INCREMENT PRIMARY KEY,
  nom_organisme  VARCHAR(100) NOT NULL,
  code_caisse    VARCHAR(10)  NULL
) ENGINE=InnoDB;

-- NOUVEAU : association PATIENT —EST_COUVERT_PAR— ORGANISME_AMO
-- (même logique que EST_AFFILIÉ pour la mutuelle ; garde l'historique des droits)
CREATE TABLE est_couvert_par (
  id_patient         INT NOT NULL,
  id_organisme       INT NOT NULL,
  regime             VARCHAR(50) NOT NULL,           -- régime général, agricole…
  exoneration_ald    VARCHAR(100) NULL,              -- ⚠ donnée sensible RGPD (révèle une maladie)
  date_debut_droits  DATE NULL,
  date_fin_droits    DATE NULL,
  date_lecture_vitale DATE NULL,
  PRIMARY KEY (id_patient, id_organisme),
  CONSTRAINT fk_couvert_patient   FOREIGN KEY (id_patient)   REFERENCES patient(id_patient),
  CONSTRAINT fk_couvert_organisme FOREIGN KEY (id_organisme) REFERENCES organisme_amo(id_organisme)
) ENGINE=InnoDB;

CREATE TABLE mutuelle (
  id_mutuelle            INT AUTO_INCREMENT PRIMARY KEY,
  nom_mutuelle           VARCHAR(100) NOT NULL,
  code_teletransmission  VARCHAR(50)  NULL,
  telephone              VARCHAR(20)  NULL,
  email                  VARCHAR(100) NULL
) ENGINE=InnoDB;

CREATE TABLE est_affilie (
  id_patient            INT NOT NULL,
  id_mutuelle           INT NOT NULL,
  numero_adherent       VARCHAR(50)  NULL,
  taux_prise_en_charge  DECIMAL(5,2) NULL,
  date_debut            DATE NULL,
  date_fin              DATE NULL,
  PRIMARY KEY (id_patient, id_mutuelle),
  CONSTRAINT fk_affilie_patient  FOREIGN KEY (id_patient)  REFERENCES patient(id_patient),
  CONSTRAINT fk_affilie_mutuelle FOREIGN KEY (id_mutuelle) REFERENCES mutuelle(id_mutuelle)
) ENGINE=InnoDB;

-- =====================================================================
-- 3. PRÉ-ADMISSION
-- =====================================================================

CREATE TABLE preadmission (
  id_preadmission          INT AUTO_INCREMENT PRIMARY KEY,
  date_saisie              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  motif_hospitalisation    VARCHAR(255) NOT NULL,
  type_sejour              ENUM('AMBULATOIRE','HOSPITALISATION_COMPLETE') NOT NULL,
  date_prevue_admission    DATE NOT NULL,
  heure_prevue_admission   TIME NOT NULL,
  duree_prevue_nuits       TINYINT NULL,
  chambre_particuliere     BOOLEAN NOT NULL DEFAULT FALSE,
  statut_accord_mutuelle   ENUM('EN_ATTENTE','ACCORDE','REFUSE') NOT NULL DEFAULT 'EN_ATTENTE',
  statut                   ENUM('BROUILLON','EN_ATTENTE_VALIDATION','VALIDEE','REJETEE') NOT NULL DEFAULT 'BROUILLON',
  id_patient               INT NOT NULL,   -- CONCERNE
  id_personnel_saisie      INT NOT NULL,   -- SAISIT (secrétaire)
  id_personnel_responsable INT NOT NULL,   -- EST_RESPONSABLE (praticien)
  id_service               INT NOT NULL,   -- VISE
  CONSTRAINT fk_preadm_patient     FOREIGN KEY (id_patient)               REFERENCES patient(id_patient),
  CONSTRAINT fk_preadm_saisie      FOREIGN KEY (id_personnel_saisie)      REFERENCES personnel(id_personnel),
  CONSTRAINT fk_preadm_responsable FOREIGN KEY (id_personnel_responsable) REFERENCES personnel(id_personnel),
  CONSTRAINT fk_preadm_service     FOREIGN KEY (id_service)               REFERENCES service(id_service)
) ENGINE=InnoDB;

-- MODIFIÉ : VALIDE relie maintenant PERSONNEL (département administratif) à PRÉADMISSION
-- (avant : PERSONNEL—OCCUPATION dans le MCD, PERSONNEL—SERVICE dans la base)
CREATE TABLE valide (
  id_preadmission  INT PRIMARY KEY,           -- 0,1 côté pré-admission
  id_personnel     INT NOT NULL,
  date_validation  DATETIME NOT NULL,
  decision         ENUM('VALIDEE','REJETEE') NOT NULL,
  numero_pec       VARCHAR(50) NULL,          -- n° d'accord de prise en charge mutuelle
  motif_rejet      VARCHAR(255) NULL,
  CONSTRAINT fk_valide_preadm    FOREIGN KEY (id_preadmission) REFERENCES preadmission(id_preadmission),
  CONSTRAINT fk_valide_personnel FOREIGN KEY (id_personnel)    REFERENCES personnel(id_personnel)
) ENGINE=InnoDB;

CREATE TABLE personne_contact (
  id_contact  INT AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(100) NOT NULL,
  prenom      VARCHAR(100) NOT NULL,
  telephone   VARCHAR(20)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE designe (
  id_preadmission           INT NOT NULL,
  id_contact                INT NOT NULL,
  lien_patient              VARCHAR(50) NOT NULL,
  est_personne_confiance    BOOLEAN NOT NULL DEFAULT FALSE,
  est_personne_a_prevenir   BOOLEAN NOT NULL DEFAULT FALSE,
  formulaire_signe          BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (id_preadmission, id_contact),
  CONSTRAINT fk_designe_preadm  FOREIGN KEY (id_preadmission) REFERENCES preadmission(id_preadmission),
  CONSTRAINT fk_designe_contact FOREIGN KEY (id_contact)      REFERENCES personne_contact(id_contact)
) ENGINE=InnoDB;

CREATE TABLE document (
  id_document      INT AUTO_INCREMENT PRIMARY KEY,
  type_document    VARCHAR(100) NOT NULL,     -- pièce d'identité, carte vitale, carte mutuelle…
  statut           ENUM('MANQUANT','RECU') NOT NULL DEFAULT 'MANQUANT',
  date_reception   DATE NULL,
  id_preadmission  INT NOT NULL,              -- JOINT
  CONSTRAINT fk_document_preadm FOREIGN KEY (id_preadmission) REFERENCES preadmission(id_preadmission)
) ENGINE=InnoDB;

CREATE TABLE creneau (
  id_creneau    INT AUTO_INCREMENT PRIMARY KEY,
  date_creneau  DATE NOT NULL,
  heure_debut   TIME NOT NULL,
  heure_fin     TIME NOT NULL,
  disponible    BOOLEAN NOT NULL DEFAULT TRUE,
  id_personnel  INT NOT NULL,                 -- DISPOSE_DE (praticien)
  CONSTRAINT fk_creneau_personnel FOREIGN KEY (id_personnel) REFERENCES personnel(id_personnel)
) ENGINE=InnoDB;

CREATE TABLE rendez_vous (
  id_rdv           INT AUTO_INCREMENT PRIMARY KEY,
  type_rdv         ENUM('ANESTHESIE','CHIRURGIEN','EXAMENS_PREOP') NOT NULL,
  lieu             VARCHAR(150) NULL,
  statut           VARCHAR(30) NOT NULL DEFAULT 'PLANIFIE',
  id_preadmission  INT NOT NULL,              -- PLANIFIE
  id_creneau       INT NOT NULL UNIQUE,       -- RÉSERVE (un créneau = un seul RDV)
  CONSTRAINT fk_rdv_preadm  FOREIGN KEY (id_preadmission) REFERENCES preadmission(id_preadmission),
  CONSTRAINT fk_rdv_creneau FOREIGN KEY (id_creneau)      REFERENCES creneau(id_creneau)
) ENGINE=InnoDB;

-- RETIRÉ : id_service (redondant, le service se retrouve via la chambre)
CREATE TABLE occupation (
  id_occupation    INT AUTO_INCREMENT PRIMARY KEY,
  date_entree      DATETIME NOT NULL,
  date_sortie      DATETIME NULL,
  numero_lit       TINYINT NOT NULL,
  id_patient       INT NOT NULL,              -- EST_LE_PATIENT_DE
  numero_chambre   VARCHAR(20) NOT NULL,      -- A_LIEU_DANS
  id_preadmission  INT NULL UNIQUE,           -- GÉNÈRE
  CONSTRAINT fk_occup_patient FOREIGN KEY (id_patient)      REFERENCES patient(id_patient),
  CONSTRAINT fk_occup_chambre FOREIGN KEY (numero_chambre)  REFERENCES chambre(numero_chambre),
  CONSTRAINT fk_occup_preadm  FOREIGN KEY (id_preadmission) REFERENCES preadmission(id_preadmission)
) ENGINE=InnoDB;

-- =====================================================================
-- 4. VOLET MÉDICAL (profil médecin — sprint suivant)
-- =====================================================================

CREATE TABLE pathologie (
  id_pathologie INT AUTO_INCREMENT PRIMARY KEY,
  libelle       VARCHAR(150) NOT NULL,
  code_cim10    VARCHAR(20) NULL,
  description   TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE atteint_de (            -- antécédents
  id_patient      INT NOT NULL,
  id_pathologie   INT NOT NULL,
  date_diagnostic DATE NULL,
  statut          VARCHAR(50) NULL,
  commentaire     TEXT NULL,
  PRIMARY KEY (id_patient, id_pathologie),
  CONSTRAINT fk_atteint_patient    FOREIGN KEY (id_patient)    REFERENCES patient(id_patient),
  CONSTRAINT fk_atteint_pathologie FOREIGN KEY (id_pathologie) REFERENCES pathologie(id_pathologie)
) ENGINE=InnoDB;

CREATE TABLE allergie (
  id_allergie    INT AUTO_INCREMENT PRIMARY KEY,
  libelle        VARCHAR(150) NOT NULL,
  type_allergene VARCHAR(100) NULL,
  description    TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE souffre_de (
  id_patient      INT NOT NULL,
  id_allergie     INT NOT NULL,
  date_diagnostic DATE NULL,
  niveau_gravite  VARCHAR(50) NULL,
  statut          VARCHAR(50) NULL,
  PRIMARY KEY (id_patient, id_allergie),
  CONSTRAINT fk_souffre_patient  FOREIGN KEY (id_patient)  REFERENCES patient(id_patient),
  CONSTRAINT fk_souffre_allergie FOREIGN KEY (id_allergie) REFERENCES allergie(id_allergie)
) ENGINE=InnoDB;

CREATE TABLE medicament (
  id_medicament         INT AUTO_INCREMENT PRIMARY KEY,
  nom_commercial        VARCHAR(100) NOT NULL,
  dci                   VARCHAR(100) NULL,
  forme_pharmacologique VARCHAR(100) NULL,
  dosage                VARCHAR(50)  NULL,
  contre_indications    TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE traite (
  id_pathologie INT NOT NULL,
  id_medicament INT NOT NULL,
  PRIMARY KEY (id_pathologie, id_medicament),
  CONSTRAINT fk_traite_pathologie FOREIGN KEY (id_pathologie) REFERENCES pathologie(id_pathologie),
  CONSTRAINT fk_traite_medicament FOREIGN KEY (id_medicament) REFERENCES medicament(id_medicament)
) ENGINE=InnoDB;

CREATE TABLE concerne_allergene (
  id_medicament INT NOT NULL,
  id_allergie   INT NOT NULL,
  PRIMARY KEY (id_medicament, id_allergie),
  CONSTRAINT fk_concerne_medoc    FOREIGN KEY (id_medicament) REFERENCES medicament(id_medicament),
  CONSTRAINT fk_concerne_allergie FOREIGN KEY (id_allergie)   REFERENCES allergie(id_allergie)
) ENGINE=InnoDB;

-- PRESCRIT = traitements en cours (maquette volet médical)
CREATE TABLE prescrit (
  id_personnel      INT NOT NULL,
  id_patient        INT NOT NULL,
  id_medicament     INT NOT NULL,
  date_prescription DATETIME NOT NULL,
  posologie         VARCHAR(100) NULL,
  frequence         VARCHAR(50)  NULL,       -- NOUVEAU (ex. 3x/j)
  voie              VARCHAR(50)  NULL,       -- NOUVEAU (orale, IV…)
  duree_traitement  VARCHAR(50)  NULL,
  PRIMARY KEY (id_personnel, id_patient, id_medicament, date_prescription),
  CONSTRAINT fk_prescrit_personnel  FOREIGN KEY (id_personnel)  REFERENCES personnel(id_personnel),
  CONSTRAINT fk_prescrit_patient    FOREIGN KEY (id_patient)    REFERENCES patient(id_patient),
  CONSTRAINT fk_prescrit_medicament FOREIGN KEY (id_medicament) REFERENCES medicament(id_medicament)
) ENGINE=InnoDB;

-- NOUVEAU : avis pré-opératoire du médecin sur une pré-admission (maquette volet médical, bloc 7)
CREATE TABLE avis_preop (
  id_preadmission INT PRIMARY KEY,
  id_personnel    INT NOT NULL,
  avis_global     ENUM('FAVORABLE','A_DISCUTER','DEFAVORABLE') NOT NULL,
  avis_detaille   TEXT NULL,
  date_avis       DATETIME NOT NULL,
  CONSTRAINT fk_avis_preadm    FOREIGN KEY (id_preadmission) REFERENCES preadmission(id_preadmission),
  CONSTRAINT fk_avis_personnel FOREIGN KEY (id_personnel)    REFERENCES personnel(id_personnel)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 5. JEU DE DONNÉES DE TEST
-- =====================================================================

INSERT INTO service (id_service, nom_service, localisation) VALUES
 (1, 'Chirurgie digestive',     'Bâtiment A — 1er étage'),
 (2, 'Cardiologie',             'Bâtiment A — 2e étage'),
 (3, 'Orthopédie',              'Bâtiment B — 1er étage'),
 (4, 'Anesthésie',              'Bâtiment B — 2e étage'),
 (5, 'Administration',          'Bâtiment C — RDC'),
 (6, 'Systèmes d''information', 'Bâtiment C — sous-sol');

INSERT INTO chambre (numero_chambre, type_chambre, nb_lits, id_service) VALUES
 ('A101', 'Individuelle', 1, 1), ('A102', 'Double', 2, 1),
 ('A201', 'Individuelle', 1, 2), ('A202', 'Double', 2, 2),
 ('B101', 'Individuelle', 1, 3), ('B102', 'Double', 2, 3);

INSERT INTO habilitation (id_habilitation, code_profil, libelle_role, description, niveau_acces) VALUES
 (1, 'SECRETAIRE',    'Secrétaire médicale',       'Création et suivi des pré-admissions (données administratives)', 1),
 (2, 'MEDECIN',       'Médecin',                   'Accès au volet médical',                                         2),
 (3, 'ADMINISTRATIF', 'Département administratif', 'Validation de la prise en charge, occupation des chambres',      2),
 (4, 'INFORMATICIEN', 'Informaticien admin',       'Gestion des comptes et des habilitations',                       3);

INSERT INTO module (id_module, libelle) VALUES
 (1, 'Gestion des comptes & droits'),
 (2, 'Pré-admissions (administratif)'),
 (3, 'Dossier médical'),
 (4, 'Prise en charge & facturation'),
 (5, 'Gestion des lits');

INSERT INTO droit (id_habilitation, id_module, lecture, ecriture, administration) VALUES
 (1, 2, TRUE,  TRUE,  FALSE),
 (2, 2, TRUE,  FALSE, FALSE), (2, 3, TRUE, TRUE, FALSE),
 (3, 2, TRUE,  FALSE, FALSE), (3, 4, TRUE, TRUE, FALSE), (3, 5, TRUE, TRUE, FALSE),
 (4, 1, TRUE,  TRUE,  TRUE);

-- Hash bcrypt générés avec password_hash() (PHP) — compatibles bcrypt dans tous les langages
INSERT INTO personnel (id_personnel, nom, prenom, email, telephone, fonction, identifiant, mot_de_passe_hash, id_service) VALUES
 (1, 'MARTIN',  'Julie',   'j.martin@clinique-lfph.fr',  '0400000001', 'Secrétaire médicale', 'secretaire',
     '$2y$10$ukeAMXfUz0AzGgIZ8fVBL.zOpB09vCFBneku5luTkSgt9CqUDKMb2', 1),
 (2, 'LEROY',   'Sophie',  's.leroy@clinique-lfph.fr',   '0400000002', 'Chirurgien',          'medecin',
     '$2y$10$4R/C5.1EjDyK8rdNaoGpHOLutWPZyADo5iViekP27/ct4ujEdIjqy', 1),
 (3, 'QUIRION', 'Camille', 'c.quirion@clinique-lfph.fr', '0400000003', 'Agent administratif', 'administratif',
     '$2y$10$OS2EYwqX6SSD5Im0lSUDj.7nAyhcQcRBwGhw64AO.HpyEN46bUmx2', 5),
 (4, 'DURAND',  'Thomas',  't.durand@clinique-lfph.fr',  '0400000004', 'Informaticien',       'informaticien',
     '$2y$10$aFyvJMllYPYAymn.Je/1ZOe779giMgWLFKNqI7OrfjmXapp7nNq7q', 6);

INSERT INTO possede (id_personnel, id_habilitation, date_attribution) VALUES
 (1, 1, '2026-09-01'), (2, 2, '2026-09-01'), (3, 3, '2026-09-01'), (4, 4, '2026-09-01');

INSERT INTO organisme_amo (id_organisme, nom_organisme, code_caisse) VALUES
 (1, 'CPAM du Rhône', '691'), (2, 'CPAM de Paris', '751'), (3, 'MSA Ain-Rhône', NULL);

INSERT INTO mutuelle (id_mutuelle, nom_mutuelle, code_teletransmission) VALUES
 (1, 'Harmonie Mutuelle', 'HM01'), (2, 'MGEN', 'MG01'), (3, 'Malakoff Humanis', 'MH01');

INSERT INTO patient (id_patient, civilite, nom_naissance, nom_usage, prenom, date_naissance, sexe, lieu_naissance,
                     nationalite, numero_securite_sociale, cle_nir, adresse, code_postal, ville, tel_portable,
                     email, medecin_traitant, situation_familiale, profession, langue) VALUES
 (1, 'Mme', 'MARTIN', 'DUPONT', 'Claire', '1978-04-12', 'F', 'Lyon (69)', 'Française', '2780469123456', '47',
     '12 rue des Lilas', '69003', 'Lyon', '0612345678', 'claire.dupont@mail.fr',
     'Dr Laurent BERNARD — Lyon 3e', 'Mariée', 'Enseignante', 'Français');

INSERT INTO est_couvert_par (id_patient, id_organisme, regime, exoneration_ald, date_fin_droits, date_lecture_vitale) VALUES
 (1, 1, 'Régime général', NULL, '2026-12-31', '2026-11-03');

INSERT INTO est_affilie (id_patient, id_mutuelle, numero_adherent) VALUES
 (1, 1, 'HM-00458812');
