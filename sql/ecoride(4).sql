-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 28 mars 2026 à 18:08
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
-- Base de données : `ecoride`
--
CREATE DATABASE IF NOT EXISTS `ecoride` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ecoride`;

--
-- Déchargement des données de la table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `ride_id`, `booking_date`, `status`, `created_at`) VALUES
(3, 2, 1, '2026-03-12 14:24:19', 'cancelled', '2026-03-12 22:47:17'),
(4, 2, 2, '2026-03-12 14:29:00', 'cancelled', '2026-03-12 22:47:17'),
(5, 6, 3, '2026-03-19 12:35:39', 'problem', '2026-03-19 12:35:39'),
(6, 6, 5, '2026-03-28 17:21:06', 'validated', '2026-03-28 17:21:06');

--
-- Déchargement des données de la table `preferences`
--

INSERT INTO `preferences` (`id`, `user_id`, `smoking`, `animals`, `custom_preference`) VALUES
(1, 7, 0, 1, 'Musique douce acceptée');

--
-- Déchargement des données de la table `reviews`
--

INSERT INTO `reviews` (`id`, `ride_id`, `author_id`, `driver_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(1, 1, 2, 1, 5, '1234', 'approved', '2026-03-12 23:20:32'),
(2, 3, 6, 2, 5, 'test', 'approved', '2026-03-19 12:36:41');

--
-- Déchargement des données de la table `rides`
--

INSERT INTO `rides` (`id`, `driver_id`, `vehicle_id`, `departure_city`, `arrival_city`, `departure_time`, `arrival_time`, `price`, `available_seats`, `ecological`, `duration`, `created_at`, `status`) VALUES
(1, 1, 1, 'Marseille', 'Nice', '2025-03-20 08:00:00', '2025-03-20 10:00:00', 10, 2, 1, NULL, '2026-03-12 22:47:17', 'pending'),
(2, 2, 2, 'marseille', 'nice', '2026-03-12 12:00:00', '2026-03-12 15:00:00', 5, 2, 0, NULL, '2026-03-12 22:47:17', 'cancelled'),
(3, 2, 2, 'marseille', 'nice', '2026-03-15 10:00:00', '2026-03-15 12:00:00', 10, 2, 1, NULL, '2026-03-12 23:22:57', 'completed'),
(4, 4, 3, 'Paris', 'Marseille', '2026-03-15 06:00:00', '2026-03-15 19:00:00', 100, 1, 1, NULL, '2026-03-12 23:43:15', 'completed'),
(5, 7, 1, 'Marseille', 'Nice', '2026-04-15 08:00:00', '2026-04-15 10:00:00', 10, 3, 0, NULL, '2026-03-26 17:31:24', 'completed'),
(6, 7, 1, 'Marseille', 'Lyon', '2026-04-20 07:00:00', '2026-04-20 11:00:00', 15, 2, 0, NULL, '2026-03-26 17:31:24', 'pending'),
(7, 7, 2, 'Nice', 'Paris', '2026-05-01 06:00:00', '2026-05-01 14:00:00', 25, 1, 0, NULL, '2026-03-26 17:31:24', 'completed');

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `pseudo`, `email`, `password`, `credits`, `role`, `created_at`, `suspended`, `is_driver`, `is_passenger`, `firstname`, `lastname`, `phone`, `photo`) VALUES
(1, 'test', 'test@test.com', '123', 20, 'user', '2026-03-12 12:09:05', 0, 0, 1, NULL, NULL, NULL, NULL),
(2, 'Evoluti62', 'teddy.developpeur@gmail.com', '$2y$10$i7oKMEesAqFagz8aLOo1x.5acXnaed9ZobU.NgpUFw8y3K.LGxfYm', 15, 'employee', '2026-03-12 12:25:56', 1, 0, 1, NULL, NULL, NULL, NULL),
(3, 'Admin', 'admin@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'admin', '2026-03-12 22:43:54', 0, 0, 1, NULL, NULL, NULL, NULL),
(4, 'TestUser', 'test2@test.fr', '$2y$10$J.y0Fjz7.Hf2TWVv4pl9d.2hfhMJtumR.tcxAU59LwaHsv4WQU9sy', 20, 'user', '2026-03-12 23:34:16', 0, 1, 0, '', '', '', NULL),
(5, 'ttttt', 'tttt@llll.fr', '$2y$10$6uOdLsMMqwK52BMw7nJkMO/qccB/gG2.QGImNuc2Pqvt6BLaFjWqe', 20, 'user', '2026-03-12 23:35:38', 0, 0, 1, NULL, NULL, NULL, NULL),
(6, 'Passager1', 'diane@test.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 20, 'user', '2026-03-19 11:21:58', 0, 0, 1, 'Diane', 'Leroy', NULL, NULL),
(7, 'TeslaDriver', 'carlos@test.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 66, 'user', '2026-03-26 17:31:24', 0, 1, 1, 'Carlos', 'Gomez', NULL, NULL),
(8, 'EmployeEco', 'employe@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 'employee', '2026-03-28 17:27:47', 0, 0, 0, NULL, NULL, NULL, NULL);

--
-- Déchargement des données de la table `vehicles`
--

INSERT INTO `vehicles` (`id`, `user_id`, `brand`, `model`, `color`, `registration`, `seats`, `energy`, `first_registration_date`) VALUES
(1, 1, 'Tesla', 'Model 3', 'Noir', 'AA-123-BB', 4, 'electrique', NULL),
(2, 2, 'Renault', 'Clio', 'Bleu', 'AB-123-CD', 4, 'essence', NULL),
(3, 4, 'renault', 'megane', 'rouge', 'CD-123-AB', 2, 'hybride', '2014-03-07'),
(4, 7, 'Tesla', 'Model 3', 'Noir', 'AA-123-BB', 4, 'electrique', '2022-06-15'),
(5, 7, 'Renault', 'Clio', 'Bleu', 'AB-456-CD', 4, 'essence', '2019-03-10');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
