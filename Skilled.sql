-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2025 at 01:43 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skilled`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_categorie` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` text DEFAULT NULL,
  `type` enum('standard','emergency') DEFAULT 'standard'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id_categorie`, `nom`, `description`, `icone`, `type`) VALUES
(1, 'General Masonry', 'Structural masonry and foundational work', 'construction.svg', 'standard'),
(2, 'Tiling & Flooring', 'Tile installation and floor finishing', 'construction.svg', 'standard'),
(3, 'Thermal Insulation', 'Home insulation for sound and temperature', 'construction.svg', 'standard'),
(4, 'Light Demolition', 'Wall removal and small structure demolitions', 'construction.svg', 'standard'),
(5, 'Interior Carpentry', 'Woodworks for interiors', 'carpentry.svg', 'standard'),
(6, 'Door & Window Installation', 'Fitting wooden or PVC doors and windows', 'carpentry.svg', 'standard'),
(7, 'Custom Furniture', 'Made-to-measure furniture building', 'carpentry.svg', 'standard'),
(8, 'General Electrical Work', 'Household or office wiring and repair', 'electricity.svg', 'standard'),
(9, 'Lighting Installation', 'LED, ceiling lights, and fixtures', 'electricity.svg', 'standard'),
(10, 'TV / Satellite Setup', 'TV mounting and satellite dish setup', 'electricity.svg', 'standard'),
(11, 'General Plumbing', 'Water lines, leaks, and repairs', 'plumbing.svg', 'standard'),
(12, 'Sanitary Installation', 'Toilets, sinks, and bathroom fittings', 'plumbing.svg', 'standard'),
(13, 'Unclogging & Leak Repair', 'Drain unblocking and pipe repair', 'plumbing.svg', 'standard'),
(14, 'Interior Painting', 'Wall and ceiling painting', 'painting.svg', 'standard'),
(15, 'Humidity Treatment', 'Mold & damp-proof coating', 'painting.svg', 'standard'),
(16, 'Wallpaper & Decorative Coverings', 'Decorative wall finish installation', 'painting.svg', 'standard'),
(17, 'Air Conditioning & Ventilation', 'AC units and ventilation systems', 'ac.svg', 'standard'),
(18, 'Post-Construction Cleaning', 'Deep cleaning after renovation', 'cleaning.svg', 'standard'),
(19, 'Welding & Metal Fabrication', 'Doors, windows, custom metal structures', 'metalwork.svg', 'standard'),
(20, 'Aluminum Window & Door Installation', 'Glass and aluminum frame fitting', 'aluminum.svg', 'standard'),
(21, 'Landscaping & Planting', 'Garden setup, trees and plants', 'gardening.svg', 'standard'),
(22, 'Synthetic Grass Installation', 'Artificial lawn installation', 'gardening.svg', 'standard'),
(23, 'Alarms & Surveillance Cameras', 'Security system setup', 'security.svg', 'standard'),
(24, 'Repairs & Multiservices', 'General repairs and maintenance', 'handyman.svg', 'standard'),
(25, 'Water Leak', 'Burst pipes, leaking taps or toilets', 'water_leakage.svg', 'emergency'),
(26, 'Power Outage', 'Sudden blackouts or electrical shutdown', 'power_outage.svg', 'emergency'),
(27, 'Gas Leak Detection', 'Suspicious gas smell, urgent leak', 'gas_leak.svg', 'emergency'),
(28, 'Broken Lock / Blocked Door', 'Key stuck or broken, can’t open the door', 'broken_lock.svg', 'emergency'),
(29, 'Toilet Overflow', 'Clogged or overflowing toilet', 'toilet_overflow.svg', 'emergency'),
(30, 'Roof Leak', 'Water dripping from ceiling or roof', 'roof_leak.svg', 'emergency'),
(31, 'Electrical Short Circuit', 'Sparks or burning smell from outlets', 'short_circuit.svg', 'emergency'),
(32, 'Appliance Repair', 'Fridge, washing machine, water heater', 'appliance_repair.svg', 'emergency'),
(33, 'Pest Infestation', 'Urgent insect or rodent control', 'pest_infestation.svg', 'emergency'),
(34, 'Emergency Cleaning', 'Flood, fire, or disaster cleanup', 'emergency_cleaning.svg', 'emergency');

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `id_client` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `telephone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`id_client`, `id_utilisateur`, `telephone`) VALUES
(1, 2, '0696469788'),
(2, 3, '0696469786'),
(3, 4, '0696469782'),
(4, 5, '06490868823'),
(5, 6, '0638564667'),
(6, 7, '0659547890'),
(7, 9, '0696469789'),
(8, 12, '06111111111');

-- --------------------------------------------------------

--
-- Table structure for table `devis`
--

CREATE TABLE `devis` (
  `id_devis` int(11) NOT NULL,
  `id_reservation` int(11) NOT NULL,
  `id_prestataire` int(11) NOT NULL,
  `date_debut_travaux` date NOT NULL,
  `date_fin_travaux` date NOT NULL,
  `cout_total` decimal(10,2) NOT NULL,
  `tarif_journalier` decimal(10,2) DEFAULT NULL,
  `acompte` decimal(10,2) NOT NULL,
  `statut` enum('pending','accepted','rejected','edit_requested','paid','pending_payment','meeting_confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `date_paiement_effectue` datetime DEFAULT NULL,
  `client_meeting_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `artisan_meeting_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `client_confirmation_deadline` datetime DEFAULT NULL,
  `artisan_confirmation_deadline` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devis`
--

INSERT INTO `devis` (`id_devis`, `id_reservation`, `id_prestataire`, `date_debut_travaux`, `date_fin_travaux`, `cout_total`, `tarif_journalier`, `acompte`, `statut`, `date_paiement_effectue`, `client_meeting_confirmed`, `artisan_meeting_confirmed`, `client_confirmation_deadline`, `artisan_confirmation_deadline`) VALUES
(13, 18, 2, '2025-06-20', '2025-06-23', 3500.00, 450.00, 175.00, 'paid', '2025-06-20 23:34:04', 1, 1, '2025-06-23 23:34:04', '2025-06-23 23:34:04'),
(14, 20, 4, '2025-06-21', '2025-06-29', 1200.00, 200.00, 60.00, 'paid', '2025-06-21 22:50:21', 0, 0, '2025-06-24 22:50:21', '2025-06-24 22:50:21');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation`
--

CREATE TABLE `evaluation` (
  `id_evaluation` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_prestataire` int(11) NOT NULL,
  `note` decimal(2,1) NOT NULL CHECK (`note` >= 0 and `note` <= 5),
  `commentaire` text DEFAULT NULL,
  `date_evaluation` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluation`
--

INSERT INTO `evaluation` (`id_evaluation`, `id_client`, `id_prestataire`, `note`, `commentaire`, `date_evaluation`) VALUES
(3, 7, 2, 5.0, 'Bon Service Top', '2025-06-20');

-- --------------------------------------------------------

--
-- Table structure for table `experience_prestataire`
--

CREATE TABLE `experience_prestataire` (
  `id_experience` int(11) NOT NULL,
  `id_prestataire` int(11) NOT NULL,
  `titre_experience` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date_project` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `experience_prestataire`
--

INSERT INTO `experience_prestataire` (`id_experience`, `id_prestataire`, `titre_experience`, `description`, `date_project`) VALUES
(4, 1, 'Change The Status', 'Hello This Is the exeperience of the prestataire', '2022'),
(5, 2, 'I Have Worked with 10 Yaers ', 'I Have Worked with 10 Yaers ', '2022'),
(6, 3, 'Description 1', 'Create Description For User 1', '2024'),
(7, 4, 'Description 2', 'Detailed Description 2', '2024'),
(8, 4, 'Hadi Bach ne3erfo wach bsaah Kijib akhir exeperience based 3la date', 'Hadi Bach ne3erfo wach bsaah Kijib akhir exeperience based 3la date', '2025'),
(9, 5, 'Skilled_Experience_test', 'Skilled_Experience_test_1', '2021'),
(10, 6, 'Skilled_Experience_test', 'Skilled_Experience_test_DESC', '2022'),
(11, 7, 'Skilled_Experience_test', 'Skilled_Experience_test_72', '2025'),
(12, 8, 'Skilled_Experience_test', 'Skilled_Experience_test_ASident', '2025'),
(13, 10, 'Skilled_Experience_test', 'Skilled_Experience_test_6', '2020'),
(14, 11, 'Skilled_Experience_test', 'Skilled_Experience_test_3', '2025'),
(15, 12, 'Skilled_Experience_test', 'Skilled_Experience_test_4', '2019'),
(16, 13, 'Skilled_Experience_test', 'Skilled_Experience_test_Test', '2018');

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `id_log` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `date_action` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_experience`
--

CREATE TABLE `media_experience` (
  `id_media` int(11) NOT NULL,
  `id_experience` int(11) NOT NULL,
  `type_contenu` enum('image','video') NOT NULL,
  `chemin_fichier` text NOT NULL,
  `description_media` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media_experience`
--

INSERT INTO `media_experience` (`id_media`, `id_experience`, `type_contenu`, `chemin_fichier`, `description_media`) VALUES
(22, 4, 'image', 'uploads/media/6855d99f3efad_pexels-tima-miroshnichenko-6474202.jpg', NULL),
(23, 4, 'image', 'uploads/media/6855d99f4359a_pexels-mesut-yalcin-1233429888-27301298.jpg', NULL),
(24, 4, 'image', 'uploads/media/6855d99f473f0_pexels-tima-miroshnichenko-6474301.jpg', NULL),
(25, 4, 'image', 'uploads/media/6855d99f4abf2_collage_2.png', NULL),
(26, 5, 'image', 'uploads/media/6855e04b1fec3_pexels-mesut-yalcin-1233429888-27301298.jpg', NULL),
(27, 5, 'image', 'uploads/media/6855e04b2664b_pexels-tiam-tarran-18652977-12891709.jpg', NULL),
(28, 5, 'image', 'uploads/media/6855e04b2a0e0_pexels-myatezhny39-2209529.jpg', NULL),
(29, 5, 'image', 'uploads/media/6855e04b2dde2_pexels-tima-miroshnichenko-6474202.jpg', NULL),
(30, 6, 'image', 'uploads/media/6856a12ebd37e_pexels-tima-miroshnichenko-6474202.jpg', NULL),
(31, 6, 'image', 'uploads/media/6856a12ec1694_pexels-tiam-tarran-18652977-12891709.jpg', NULL),
(32, 6, 'image', 'uploads/media/6856a12ec58b3_pexels-bulat369-1243575272-32391497.jpg', NULL),
(33, 6, 'image', 'uploads/media/6856a12ec94a0_pexels-tima-miroshnichenko-6474301.jpg', NULL),
(34, 7, 'image', 'uploads/media/6856a1b8c90c4_pexels-mesut-yalcin-1233429888-27301298.jpg', NULL),
(35, 7, 'image', 'uploads/media/6856a1b8cccec_pexels-tiam-tarran-18652977-12891709.jpg', NULL),
(36, 7, 'image', 'uploads/media/6856a1b8d09bf_pexels-myatezhny39-2209529.jpg', NULL),
(37, 7, 'image', 'uploads/media/6856a1b8d3955_pexels-tima-miroshnichenko-6474202.jpg', NULL),
(38, 8, 'image', 'uploads/media/6856bbcbc35a0_editable ads2 for amtar3.png', NULL),
(39, 8, 'image', 'uploads/media/6856bbcbc39bb_télécharger (37).jpg', NULL),
(40, 8, 'image', 'uploads/media/6856bbcbc6cc6_Side Stitch_ What Causes It and How to Prevent It.jpg', NULL),
(41, 9, 'image', 'uploads/media/68574be09665a_Gemütliches graues Badezimmer mit Holzelementen.jpg', NULL),
(42, 9, 'image', 'uploads/media/68574be09aaab_télécharger (39).jpg', NULL),
(43, 9, 'image', 'uploads/media/68574be09ea71_Metal sanitary module for suspended toilets_ QR-INOX by OLI.jpg', NULL),
(44, 9, 'image', 'uploads/media/68574be0a2767_img_collage_2_3.png', NULL),
(45, 10, 'image', 'uploads/media/68574c6663882_img_collage_2_3.png', NULL),
(46, 10, 'image', 'uploads/media/68574c6663dbc_img_collage_2_2.png', NULL),
(47, 10, 'image', 'uploads/media/68574c6664280_télécharger (42).jpg', NULL),
(48, 10, 'image', 'uploads/media/68574c6668b97_img_collage_7.png', NULL),
(50, 11, 'image', 'uploads/media/68574cba4c6d1_télécharger (39).jpg', NULL),
(51, 11, 'image', 'uploads/media/68574cba51429_Gemütliches graues Badezimmer mit Holzelementen.jpg', NULL),
(52, 11, 'image', 'uploads/media/68574cba54d18_Hexagon LED Garage.jpg', NULL),
(53, 12, 'image', 'uploads/media/68574d3e6d5dd_Explaination_image.png', NULL),
(54, 12, 'image', 'uploads/media/68574d3e6db23_télécharger (39).jpg', NULL),
(55, 12, 'image', 'uploads/media/68574d3e71d54_télécharger (42).jpg', NULL),
(56, 12, 'image', 'uploads/media/68574d3e7585a_Epoxy Garage Floor Idea.jpg', NULL),
(57, 13, 'image', 'uploads/media/685751cee56e1_banner_1.png', NULL),
(58, 13, 'image', 'uploads/media/685751cee5fd1_img_collage_2.png', NULL),
(59, 13, 'image', 'uploads/media/685751cee64bd_Capture d\'écran 2025-05-23 181456 1.png', NULL),
(60, 13, 'image', 'uploads/media/685751cee691f_back_skilled_2.png', NULL),
(63, 15, 'image', 'uploads/media/685752d867406_img_collage_2_1.png', NULL),
(64, 15, 'image', 'uploads/media/685752d867ba8_img_collage_2_2.png', NULL),
(65, 15, 'image', 'uploads/media/685752d868171_Design sans titre - 2025-06-21T222033.053.png', NULL),
(66, 15, 'image', 'uploads/media/685752d8687bd_Metal sanitary module for suspended toilets_ QR-INOX by OLI.jpg', NULL),
(67, 15, 'image', 'uploads/media/685752d86dc7b_Black With Gold And White Marble Texture Wallpaper, Gold Cracks on Black Marble Peel and Stick Wallpaper, Removable Self Adhesive Wall Mural.jpg', NULL),
(68, 14, 'image', 'uploads/media/6857531df3b88_télécharger (43).jpg', NULL),
(69, 14, 'image', 'uploads/media/6857531e054d7_img_collage_6.png', NULL),
(70, 14, 'image', 'uploads/media/6857531e05b44_img_collage_2_1.png', NULL),
(71, 14, 'image', 'uploads/media/6857531e06032_télécharger (38).jpg', NULL),
(72, 14, 'image', 'uploads/media/6857531e09662_Benefits of Automatic Sliding Doors to Install At Commercial Place.jpg', NULL),
(73, 11, 'image', 'uploads/media/68575349625de_img_collage_5.png', NULL),
(74, 16, 'image', 'uploads/media/685753ff4e73e_Black With Gold And White Marble Texture Wallpaper, Gold Cracks on Black Marble Peel and Stick Wallpaper, Removable Self Adhesive Wall Mural.jpg', NULL),
(75, 16, 'image', 'uploads/media/685753ff52d1b_Media circus.jpg', NULL),
(76, 16, 'image', 'uploads/media/685753ff56410_Benefits of Automatic Sliding Doors to Install At Commercial Place.jpg', NULL),
(77, 16, 'image', 'uploads/media/685753ff59618_Hexagon LED Garage.jpg', NULL),
(78, 16, 'image', 'uploads/media/685753ff5c83a_img_collage_2.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `paiement`
--

CREATE TABLE `paiement` (
  `id_paiement` int(11) NOT NULL,
  `id_devis` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `type_paiement` enum('acompte','par_jour','global') NOT NULL,
  `methode_paiement` enum('stripe') NOT NULL,
  `date_paiement` datetime NOT NULL DEFAULT current_timestamp(),
  `statut_paiement` enum('en_attente','effectué','échoué') NOT NULL,
  `reference_transaction` varchar(255) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paiement`
--

INSERT INTO `paiement` (`id_paiement`, `id_devis`, `montant`, `type_paiement`, `methode_paiement`, `date_paiement`, `statut_paiement`, `reference_transaction`, `stripe_payment_intent_id`) VALUES
(14, 13, 175.00, 'acompte', '', '2025-06-20 23:34:04', 'effectué', NULL, 'pi_3RcDVXGonfD7cy551OJitS33'),
(15, 14, 60.00, 'acompte', '', '2025-06-21 22:48:56', 'effectué', NULL, 'pi_3RcZHOGonfD7cy551PC8lEC7'),
(16, 14, 60.00, 'acompte', '', '2025-06-21 22:49:48', 'effectué', NULL, 'pi_3RcZIFGonfD7cy551GuIfYMh'),
(17, 14, 60.00, 'acompte', '', '2025-06-21 22:50:21', 'effectué', NULL, 'pi_3RcZInGonfD7cy551PzkQ5wq');

-- --------------------------------------------------------

--
-- Table structure for table `prestataire`
--

CREATE TABLE `prestataire` (
  `id_prestataire` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_categorie` int(11) NOT NULL,
  `photo` text NOT NULL,
  `specialite` varchar(100) NOT NULL,
  `pays` varchar(100) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `tarif_journalier` decimal(10,2) NOT NULL,
  `accepte_budget_global` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prestataire`
--

INSERT INTO `prestataire` (`id_prestataire`, `id_utilisateur`, `id_categorie`, `photo`, `specialite`, `pays`, `ville`, `telephone`, `tarif_journalier`, `accepte_budget_global`) VALUES
(1, 1, 4, 'uploads/68557d07a1766_Group (2).png', '', 'Morocco', 'Tanger ', '0695490856', 200.00, 0),
(2, 8, 12, 'uploads/6855e018c0d19_Group (2).png', '', 'Morocco', 'Tanger ', '0695490835', 300.00, 0),
(3, 10, 1, 'uploads/68569fc2b0ef3_Group (2).png', '', 'Morocco', 'Tanger ', '0695490846', 300.00, 0),
(4, 11, 9, 'uploads/6856a6d8d5a96_Group (2).png', '', 'Morocco', 'Casablanca', '0695490577', 400.00, 0),
(5, 13, 2, 'uploads/68574bbc16529_Group (2).png', '', 'Spain', 'Barcelona', '06111111111', 100.00, 0),
(6, 14, 8, 'uploads/68574c4bb82cf_profil_prestataire.png', '', 'Belgium', 'Antwerp', '06111111134', 100.00, 0),
(7, 15, 2, 'uploads/68574ca0f1917_profil_prestataire.png', '', 'France', 'Lyon', '06111111135', 300.00, 0),
(8, 16, 6, 'uploads/68574d174361e_télécharger (38).jpg', '', 'Netherlands', 'Rotterdam', '06111111189', 200.00, 0),
(10, 17, 3, 'uploads/685751a606875_Group (2).png', '', 'Spain', 'Madrid', '06111111188', 200.00, 0),
(11, 18, 3, 'uploads/685752312e246_img_collage_2_3.png', '', 'Spain', 'Madrid', '0696469789', 300.00, 0),
(12, 19, 4, 'uploads/685752b78bcc4_profil.png', '', 'Morocco', 'Casablanca', '0611111192', 300.00, 0),
(13, 20, 4, 'uploads/685753cc67778_Group (2).png', '', 'France', 'Marseille', '0611111189', 300.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `id_reservation` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_prestataire` int(11) NOT NULL,
  `description_service` text NOT NULL,
  `budget_total` decimal(10,2) DEFAULT NULL,
  `tarif_par_jour` decimal(10,2) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `nb_jours_estime` int(11) NOT NULL,
  `statut` varchar(50) NOT NULL,
  `can_contact` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`id_reservation`, `id_client`, `id_prestataire`, `description_service`, `budget_total`, `tarif_par_jour`, `date_debut`, `nb_jours_estime`, `statut`, `can_contact`) VALUES
(18, 7, 2, 'I Want To Change My Room Style', 3000.00, NULL, '2025-06-20', 2, 'cancelled_by_artisan', 0),
(19, 7, 1, 'Demande De Service 1', 400.00, NULL, '2025-06-22', 4, 'cancelled', 0),
(20, 7, 4, 'Demande De Service 1', 400.00, NULL, '2025-06-22', 4, 'quoted', 0);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('client','prestataire','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `email`, `nom`, `prenom`, `mot_de_passe`, `role`) VALUES
(1, 'mohamed2000haltout@gmail.com', 'Haltout', 'Mohamed', '$2y$10$uIHAo/SPR.sv90siia1CDe/ne/FXQEkyOPhpsgNbCBZfEFCjMYBMa', 'prestataire'),
(2, 'mohamed1000haltout@gmail.com', 'Haltout', 'Mohamed', '$2y$10$SXBA.8vgHzYX/e6/Ubj2JOxpA5u00y7gxlo02xgLaLZg4BbRqkOhy', 'client'),
(3, 'mohamed3000haltout@gmail.com', 'Haltout', 'Mohamed', '$2y$10$mX4a88DZYbCKA5EiZxXb1.97Oc.03J5huW2tg8PiE7fY/QxM6E24y', 'client'),
(4, 'mohamed4000haltout@gmail.com', 'Haltout', 'Mohamed', '$2y$10$150oacB1E8znehFj2IY.LuKBDJVU/Lx0aJeTqZAdmlYW9TJr11rdK', 'client'),
(5, 'hamdi@gmail.com', 'Bamdi', 'Hamdi', '$2y$10$Ln5UxZsPooEA7dHU2Ux9QORYnVUKCsWhVEfTR2.q/.daw96CBw/ty', 'client'),
(6, 'mojahid@gmail.com', 'Media', 'Soso', '$2y$10$nDFJ8x8mMTZNgmllGUlxzugeYq9EGAzDJ3T3VeYD6UlWSra/7hEEi', 'client'),
(7, 'rodix@gmail.com', 'media', 'rodix', '$2y$10$is6tq/p3Eq.h1HFmAXC7GuFBM8GTVfohbhTwHd/UnCpua56Tab8ky', 'client'),
(8, 'mohamed6000haltout@gmail.com', 'Haltout', 'Mohamed', '$2y$10$KLl0eYTIYg61ILXWI9SFeeaJo1XZTCkkzPH0UdHs4berRSKTbKr5K', 'prestataire'),
(9, 'mohamed7000haltout@gmail.com', 'Haltout', 'Mohamed', '$2y$10$DvIBuMMoEYrJQXgaeFi7Guei0eadX8Js1Cm4qlz8PodzJ9DgXg0v6', 'client'),
(10, 'mohamed0001haltout@gmail.co', 'Haltout', 'Mohamed', '$2y$10$HzUOV8yesOEvrK4v7S6yxelPfc8ZA5KDKqr9JBnQqGk7UNJSCaWYC', 'prestataire'),
(11, 'mohamed0002haltout@gmail.co', 'Haltout', 'Mohamed', '$2y$10$gwdciVFGvcIT0mgBw2x49egatmd3TeJ0XnXVhRGq3ZLbkH2sah/Ca', 'prestataire'),
(12, 'skilled_1@gmail.com', 'test_1', 'skilled', '$2y$10$NMUQD0ijnj9ijTAaWoIHPutQS/bOpCR/mh12y2sTUGm6g/h4/W1DW', 'client'),
(13, 'skilled_2@gmail.com', 'test_2', 'Skilled', '$2y$10$V9R1tocqal5yNZXmWcPFUeBKnnEzZk2BFYtLpZdUMeIJFTbosqWGC', 'prestataire'),
(14, 'skilled_3@gmail.com', 'test_3', 'Skilled', '$2y$10$Cj/umWR2EehoHgdFj1ycXO8BZ5B2/UzBle26os0x3S1jvmTINs72m', 'prestataire'),
(15, 'skilled_4@gmail.com', 'test_4', 'Skilled', '$2y$10$YqgAWdB/7ntsNFcNJN8Kj.CLB9gSL.bZU6CpFBTAayWJzZTTZnjIS', 'prestataire'),
(16, 'skilled_5@gmail.com', 'test_5', 'Skilled', '$2y$10$bej//bLKMpWWWi9FyzEqaewkyLThyfxog9FWQe3TZT0Caf/.3vede', 'prestataire'),
(17, 'skilled_6@gmail.com', 'test_6', 'Skilled', '$2y$10$MKOOIdXWn8Gtzg5cwyye0.pURcjSuLCE3cJBrZMyQy7oRZ9frTRjS', 'prestataire'),
(18, 'skilled_7@gmail.com', 'test_7', 'Skilled', '$2y$10$G5QQuEIvgyDXjWpDNjLwluwtcBwfDa7yScHa7EOtb4tvvcKIk46jW', 'prestataire'),
(19, 'skilled_8@gmail.com', 'test_8', 'Skilled', '$2y$10$T2U/0soerqaFjt1PhlW/ueg2/YKDobmFxo3CwnrUGJPsEJjpvi5g6', 'prestataire'),
(20, 'skilled_9@gmail.com', 'test_9', 'Skilled', '$2y$10$64uOKKwkSyGNDjiL4JrCMuFUJ6XkZ03rJqrcbOz09OW4K3Uj/q7ia', 'prestataire');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_categorie`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id_client`),
  ADD UNIQUE KEY `telephone` (`telephone`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `devis`
--
ALTER TABLE `devis`
  ADD PRIMARY KEY (`id_devis`),
  ADD UNIQUE KEY `id_reservation` (`id_reservation`),
  ADD KEY `id_prestataire` (`id_prestataire`);

--
-- Indexes for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD PRIMARY KEY (`id_evaluation`),
  ADD UNIQUE KEY `id_client` (`id_client`,`id_prestataire`,`date_evaluation`),
  ADD KEY `id_prestataire` (`id_prestataire`);

--
-- Indexes for table `experience_prestataire`
--
ALTER TABLE `experience_prestataire`
  ADD PRIMARY KEY (`id_experience`),
  ADD KEY `id_prestataire` (`id_prestataire`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `media_experience`
--
ALTER TABLE `media_experience`
  ADD PRIMARY KEY (`id_media`),
  ADD KEY `id_experience` (`id_experience`);

--
-- Indexes for table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`id_paiement`),
  ADD KEY `id_devis` (`id_devis`);

--
-- Indexes for table `prestataire`
--
ALTER TABLE `prestataire`
  ADD PRIMARY KEY (`id_prestataire`),
  ADD UNIQUE KEY `telephone` (`telephone`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `id_categorie` (`id_categorie`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id_reservation`),
  ADD KEY `id_client` (`id_client`),
  ADD KEY `id_prestataire` (`id_prestataire`);

--
-- Indexes for table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `devis`
--
ALTER TABLE `devis`
  MODIFY `id_devis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `evaluation`
--
ALTER TABLE `evaluation`
  MODIFY `id_evaluation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `experience_prestataire`
--
ALTER TABLE `experience_prestataire`
  MODIFY `id_experience` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_experience`
--
ALTER TABLE `media_experience`
  MODIFY `id_media` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `id_paiement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `prestataire`
--
ALTER TABLE `prestataire`
  MODIFY `id_prestataire` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Constraints for table `client`
--
ALTER TABLE `client`
  ADD CONSTRAINT `client_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Constraints for table `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `devis_ibfk_1` FOREIGN KEY (`id_reservation`) REFERENCES `reservation` (`id_reservation`),
  ADD CONSTRAINT `devis_ibfk_2` FOREIGN KEY (`id_prestataire`) REFERENCES `prestataire` (`id_prestataire`);

--
-- Constraints for table `evaluation`
--
ALTER TABLE `evaluation`
  ADD CONSTRAINT `evaluation_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`),
  ADD CONSTRAINT `evaluation_ibfk_2` FOREIGN KEY (`id_prestataire`) REFERENCES `prestataire` (`id_prestataire`);

--
-- Constraints for table `experience_prestataire`
--
ALTER TABLE `experience_prestataire`
  ADD CONSTRAINT `experience_prestataire_ibfk_1` FOREIGN KEY (`id_prestataire`) REFERENCES `prestataire` (`id_prestataire`);

--
-- Constraints for table `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `log_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`);

--
-- Constraints for table `media_experience`
--
ALTER TABLE `media_experience`
  ADD CONSTRAINT `media_experience_ibfk_1` FOREIGN KEY (`id_experience`) REFERENCES `experience_prestataire` (`id_experience`);

--
-- Constraints for table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`id_devis`) REFERENCES `devis` (`id_devis`);

--
-- Constraints for table `prestataire`
--
ALTER TABLE `prestataire`
  ADD CONSTRAINT `prestataire_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`),
  ADD CONSTRAINT `prestataire_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`);

--
-- Constraints for table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_client`),
  ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`id_prestataire`) REFERENCES `prestataire` (`id_prestataire`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
