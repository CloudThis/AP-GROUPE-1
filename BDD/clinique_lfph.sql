-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 13 oct. 2026 à 08:52
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `clinique_lfph`
--

-- --------------------------------------------------------

--
-- Structure de la table `allergie`
--

CREATE TABLE `allergie` (
  `id_allergie` int(11) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `type_allergene` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `atteint_de`
--

CREATE TABLE `atteint_de` (
  `id_patient` int(11) NOT NULL,
  `id_pathologie` int(11) NOT NULL,
  `date_diagnostic` date DEFAULT NULL,
  `statut` varchar(50) DEFAULT NULL,
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `chambre`
--

CREATE TABLE `chambre` (
  `numero_chambre` varchar(20) NOT NULL,
  `type_chambre` varchar(50) DEFAULT NULL,
  `id_service` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `concerne_allergene`
--

CREATE TABLE `concerne_allergene` (
  `id_medicament` int(11) NOT NULL,
  `id_allergie` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `est_affilie`
--

CREATE TABLE `est_affilie` (
  `id_patient` int(11) NOT NULL,
  `id_mutuelle` int(11) NOT NULL,
  `numero_adherent` varchar(50) DEFAULT NULL,
  `taux_prise_en_charge` decimal(5,2) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `habilitation`
--

CREATE TABLE `habilitation` (
  `id_habilitation` int(11) NOT NULL,
  `libelle_role` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `niveau_acces` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `medicament`
--

CREATE TABLE `medicament` (
  `id_medicament` int(11) NOT NULL,
  `nom_commercial` varchar(100) NOT NULL,
  `dci` varchar(100) DEFAULT NULL,
  `forme_pharmacologique` varchar(100) DEFAULT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `contre_indications` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mutuelle`
--

CREATE TABLE `mutuelle` (
  `id_mutuelle` int(11) NOT NULL,
  `nom_mutuelle` varchar(100) NOT NULL,
  `code_teletransmission` varchar(50) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `occupation`
--

CREATE TABLE `occupation` (
  `id_occupation` int(11) NOT NULL,
  `date_entree` datetime NOT NULL,
  `date_sortie` datetime DEFAULT NULL,
  `numero_lit` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  `numero_chambre` varchar(20) NOT NULL,
  `id_preadmission` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pathologie`
--

CREATE TABLE `pathologie` (
  `id_pathologie` int(11) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `code_cim10` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `patient`
--

CREATE TABLE `patient` (
  `id_patient` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `numero_securite_sociale` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `personnel`
--

CREATE TABLE `personnel` (
  `id_personnel` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `fonction` varchar(100) DEFAULT NULL,
  `id_personnel_encadrant` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `possede`
--

CREATE TABLE `possede` (
  `id_personnel` int(11) NOT NULL,
  `id_habilitation` int(11) NOT NULL,
  `date_attribution` date DEFAULT NULL,
  `date_expiration` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `preadmission`
--

CREATE TABLE `preadmission` (
  `id_preadmission` int(11) NOT NULL,
  `date_saisie` datetime NOT NULL,
  `date_prevue_admission` datetime NOT NULL,
  `statut` varchar(50) DEFAULT NULL,
  `id_patient` int(11) NOT NULL,
  `id_personnel` int(11) NOT NULL,
  `id_service` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `prescrit`
--

CREATE TABLE `prescrit` (
  `id_personnel` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_medicament` int(11) NOT NULL,
  `date_prescription` datetime NOT NULL,
  `posologie` varchar(100) DEFAULT NULL,
  `duree_traitement` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `service`
--

CREATE TABLE `service` (
  `id_service` int(11) NOT NULL,
  `nom_service` varchar(100) NOT NULL,
  `localisation` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `souffre_de`
--

CREATE TABLE `souffre_de` (
  `id_patient` int(11) NOT NULL,
  `id_allergie` int(11) NOT NULL,
  `date_diagnostic` date DEFAULT NULL,
  `niveau_gravite` varchar(50) DEFAULT NULL,
  `statut` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `traite`
--

CREATE TABLE `traite` (
  `id_pathologie` int(11) NOT NULL,
  `id_medicament` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `valide`
--

CREATE TABLE `valide` (
  `id_personnel` int(11) NOT NULL,
  `id_service` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `allergie`
--
ALTER TABLE `allergie`
  ADD PRIMARY KEY (`id_allergie`);

--
-- Index pour la table `atteint_de`
--
ALTER TABLE `atteint_de`
  ADD PRIMARY KEY (`id_patient`,`id_pathologie`),
  ADD KEY `fk_atteint_pathologie` (`id_pathologie`);

--
-- Index pour la table `chambre`
--
ALTER TABLE `chambre`
  ADD PRIMARY KEY (`numero_chambre`),
  ADD KEY `fk_chambre_service` (`id_service`);

--
-- Index pour la table `concerne_allergene`
--
ALTER TABLE `concerne_allergene`
  ADD PRIMARY KEY (`id_medicament`,`id_allergie`),
  ADD KEY `fk_concerne_allergie` (`id_allergie`);

--
-- Index pour la table `est_affilie`
--
ALTER TABLE `est_affilie`
  ADD PRIMARY KEY (`id_patient`,`id_mutuelle`),
  ADD KEY `fk_affilie_mutuelle` (`id_mutuelle`);

--
-- Index pour la table `habilitation`
--
ALTER TABLE `habilitation`
  ADD PRIMARY KEY (`id_habilitation`);

--
-- Index pour la table `medicament`
--
ALTER TABLE `medicament`
  ADD PRIMARY KEY (`id_medicament`);

--
-- Index pour la table `mutuelle`
--
ALTER TABLE `mutuelle`
  ADD PRIMARY KEY (`id_mutuelle`);

--
-- Index pour la table `occupation`
--
ALTER TABLE `occupation`
  ADD PRIMARY KEY (`id_occupation`),
  ADD UNIQUE KEY `id_preadmission` (`id_preadmission`),
  ADD KEY `fk_occupation_patient` (`id_patient`),
  ADD KEY `fk_occupation_service` (`id_service`),
  ADD KEY `fk_occupation_chambre` (`numero_chambre`);

--
-- Index pour la table `pathologie`
--
ALTER TABLE `pathologie`
  ADD PRIMARY KEY (`id_pathologie`);

--
-- Index pour la table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`id_patient`),
  ADD UNIQUE KEY `numero_securite_sociale` (`numero_securite_sociale`);

--
-- Index pour la table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id_personnel`),
  ADD KEY `fk_personnel_encadre` (`id_personnel_encadrant`);

--
-- Index pour la table `possede`
--
ALTER TABLE `possede`
  ADD PRIMARY KEY (`id_personnel`,`id_habilitation`),
  ADD KEY `fk_possede_habilitation` (`id_habilitation`);

--
-- Index pour la table `preadmission`
--
ALTER TABLE `preadmission`
  ADD PRIMARY KEY (`id_preadmission`),
  ADD KEY `fk_preadmission_patient` (`id_patient`),
  ADD KEY `fk_preadmission_personnel` (`id_personnel`),
  ADD KEY `fk_preadmission_service` (`id_service`);

--
-- Index pour la table `prescrit`
--
ALTER TABLE `prescrit`
  ADD PRIMARY KEY (`id_personnel`,`id_patient`,`id_medicament`,`date_prescription`),
  ADD KEY `fk_prescrit_patient` (`id_patient`),
  ADD KEY `fk_prescrit_medicament` (`id_medicament`);

--
-- Index pour la table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id_service`);

--
-- Index pour la table `souffre_de`
--
ALTER TABLE `souffre_de`
  ADD PRIMARY KEY (`id_patient`,`id_allergie`),
  ADD KEY `fk_souffre_allergie` (`id_allergie`);

--
-- Index pour la table `traite`
--
ALTER TABLE `traite`
  ADD PRIMARY KEY (`id_pathologie`,`id_medicament`),
  ADD KEY `fk_traite_medicament` (`id_medicament`);

--
-- Index pour la table `valide`
--
ALTER TABLE `valide`
  ADD PRIMARY KEY (`id_personnel`,`id_service`),
  ADD KEY `fk_valide_service` (`id_service`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `allergie`
--
ALTER TABLE `allergie`
  MODIFY `id_allergie` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `habilitation`
--
ALTER TABLE `habilitation`
  MODIFY `id_habilitation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `medicament`
--
ALTER TABLE `medicament`
  MODIFY `id_medicament` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mutuelle`
--
ALTER TABLE `mutuelle`
  MODIFY `id_mutuelle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `occupation`
--
ALTER TABLE `occupation`
  MODIFY `id_occupation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pathologie`
--
ALTER TABLE `pathologie`
  MODIFY `id_pathologie` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `patient`
--
ALTER TABLE `patient`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id_personnel` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `preadmission`
--
ALTER TABLE `preadmission`
  MODIFY `id_preadmission` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `service`
--
ALTER TABLE `service`
  MODIFY `id_service` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `atteint_de`
--
ALTER TABLE `atteint_de`
  ADD CONSTRAINT `fk_atteint_pathologie` FOREIGN KEY (`id_pathologie`) REFERENCES `pathologie` (`id_pathologie`),
  ADD CONSTRAINT `fk_atteint_patient` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`);

--
-- Contraintes pour la table `chambre`
--
ALTER TABLE `chambre`
  ADD CONSTRAINT `fk_chambre_service` FOREIGN KEY (`id_service`) REFERENCES `service` (`id_service`);

--
-- Contraintes pour la table `concerne_allergene`
--
ALTER TABLE `concerne_allergene`
  ADD CONSTRAINT `fk_concerne_allergie` FOREIGN KEY (`id_allergie`) REFERENCES `allergie` (`id_allergie`),
  ADD CONSTRAINT `fk_concerne_medoc` FOREIGN KEY (`id_medicament`) REFERENCES `medicament` (`id_medicament`);

--
-- Contraintes pour la table `est_affilie`
--
ALTER TABLE `est_affilie`
  ADD CONSTRAINT `fk_affilie_mutuelle` FOREIGN KEY (`id_mutuelle`) REFERENCES `mutuelle` (`id_mutuelle`),
  ADD CONSTRAINT `fk_affilie_patient` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`);

--
-- Contraintes pour la table `occupation`
--
ALTER TABLE `occupation`
  ADD CONSTRAINT `fk_occupation_chambre` FOREIGN KEY (`numero_chambre`) REFERENCES `chambre` (`numero_chambre`),
  ADD CONSTRAINT `fk_occupation_patient` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`),
  ADD CONSTRAINT `fk_occupation_preadmission` FOREIGN KEY (`id_preadmission`) REFERENCES `preadmission` (`id_preadmission`),
  ADD CONSTRAINT `fk_occupation_service` FOREIGN KEY (`id_service`) REFERENCES `service` (`id_service`);

--
-- Contraintes pour la table `personnel`
--
ALTER TABLE `personnel`
  ADD CONSTRAINT `fk_personnel_encadre` FOREIGN KEY (`id_personnel_encadrant`) REFERENCES `personnel` (`id_personnel`);

--
-- Contraintes pour la table `possede`
--
ALTER TABLE `possede`
  ADD CONSTRAINT `fk_possede_habilitation` FOREIGN KEY (`id_habilitation`) REFERENCES `habilitation` (`id_habilitation`),
  ADD CONSTRAINT `fk_possede_personnel` FOREIGN KEY (`id_personnel`) REFERENCES `personnel` (`id_personnel`);

--
-- Contraintes pour la table `preadmission`
--
ALTER TABLE `preadmission`
  ADD CONSTRAINT `fk_preadmission_patient` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`),
  ADD CONSTRAINT `fk_preadmission_personnel` FOREIGN KEY (`id_personnel`) REFERENCES `personnel` (`id_personnel`),
  ADD CONSTRAINT `fk_preadmission_service` FOREIGN KEY (`id_service`) REFERENCES `service` (`id_service`);

--
-- Contraintes pour la table `prescrit`
--
ALTER TABLE `prescrit`
  ADD CONSTRAINT `fk_prescrit_medicament` FOREIGN KEY (`id_medicament`) REFERENCES `medicament` (`id_medicament`),
  ADD CONSTRAINT `fk_prescrit_patient` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`),
  ADD CONSTRAINT `fk_prescrit_personnel` FOREIGN KEY (`id_personnel`) REFERENCES `personnel` (`id_personnel`);

--
-- Contraintes pour la table `souffre_de`
--
ALTER TABLE `souffre_de`
  ADD CONSTRAINT `fk_souffre_allergie` FOREIGN KEY (`id_allergie`) REFERENCES `allergie` (`id_allergie`),
  ADD CONSTRAINT `fk_souffre_patient` FOREIGN KEY (`id_patient`) REFERENCES `patient` (`id_patient`);

--
-- Contraintes pour la table `traite`
--
ALTER TABLE `traite`
  ADD CONSTRAINT `fk_traite_medicament` FOREIGN KEY (`id_medicament`) REFERENCES `medicament` (`id_medicament`),
  ADD CONSTRAINT `fk_traite_pathologie` FOREIGN KEY (`id_pathologie`) REFERENCES `pathologie` (`id_pathologie`);

--
-- Contraintes pour la table `valide`
--
ALTER TABLE `valide`
  ADD CONSTRAINT `fk_valide_personnel` FOREIGN KEY (`id_personnel`) REFERENCES `personnel` (`id_personnel`),
  ADD CONSTRAINT `fk_valide_service` FOREIGN KEY (`id_service`) REFERENCES `service` (`id_service`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
