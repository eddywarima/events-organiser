-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2026 at 12:23 PM
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
-- Database: `event_booking_system`
--
CREATE DATABASE IF NOT EXISTS `event_booking_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `event_booking_system`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `tickets` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_status` enum('confirmed','cancelled') DEFAULT 'confirmed',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `event_id`, `tickets`, `total_amount`, `booking_status`, `booking_date`) VALUES
(2, 4, 1, 2, 50.00, 'confirmed', '2026-02-02 15:04:05'),
(3, 3, 2, 1, 1000.00, 'confirmed', '2026-03-05 12:33:18'),
(4, 3, 2, 10, 10000.00, 'cancelled', '2026-03-05 12:33:57'),
(5, 3, 2, 1, 1000.00, 'confirmed', '2026-03-05 12:41:54'),
(6, 3, 2, 1, 1000.00, 'confirmed', '2026-03-05 12:54:39'),
(7, 5, 1, 1, 25.00, 'confirmed', '2026-03-05 13:15:44'),
(8, 5, 1, 10, 250.00, 'confirmed', '2026-03-05 13:17:11'),
(9, 3, 2, 1, 1000.00, 'confirmed', '2026-03-18 11:33:12'),
(10, 3, 3, 1, 1500.00, 'cancelled', '2026-03-18 11:33:49'),
(11, 3, 2, 1, 1000.00, 'confirmed', '2026-03-18 12:45:52'),
(12, 3, 8, 10, 20000.00, 'cancelled', '2026-04-04 10:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(200) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `total_tickets` int(11) NOT NULL,
  `available_tickets` int(11) NOT NULL,
  `ticket_price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `location`, `event_date`, `event_time`, `total_tickets`, `available_tickets`, `ticket_price`, `image`, `status`, `created_at`) VALUES
(1, 'Sample Event', 'A great event', 'Downtown', '2026-02-15', '18:00:00', 100, 89, 25.00, NULL, 'active', '2026-02-02 14:55:50'),
(2, 'Mourine Birthday', 'rtyhbb', '3456', '2026-02-14', '00:00:21', 200, 195, 1000.00, NULL, 'active', '2026-02-02 15:00:50'),
(3, 'nineteeeth day', 'all red', '3456', '2026-03-19', '00:00:12', 1, 1, 1500.00, NULL, 'active', '2026-03-18 11:19:04'),
(4, 'race event', 'casual wear .you are all welcomed', '3456', '2026-03-21', '00:00:08', 1, 1, 20.00, NULL, 'active', '2026-03-18 11:31:23'),
(5, 'easter party', 'to celebrate death and resurrection of jesus', '5678', '2026-04-04', '00:00:10', 20, 20, 20.00, NULL, 'active', '2026-04-04 06:27:19'),
(6, 'Graduation', 'graduation party', 'Nairobi', '2026-04-29', '00:00:02', 100, 100, 1000.00, NULL, 'active', '2026-04-04 08:24:03'),
(7, 'Aniversary', '10 years Wedding aniversary', 'Nairobi', '2026-04-25', '00:00:04', 1000, 1000, 1500.00, NULL, 'active', '2026-04-04 08:26:01'),
(8, 'cultural day', 'celebrate our culture', 'Nairobi', '2026-04-30', '00:00:14', 1000, 1000, 2000.00, NULL, 'active', '2026-04-04 10:15:04');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_method` enum('mpesa','card','cash') NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verification_expires` datetime DEFAULT NULL,
  `email_verification_attempts` int(11) DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `email_verified`, `email_verification_token`, `email_verification_expires`, `email_verification_attempts`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Admin', 'admin@events.com', 0, NULL, NULL, 0, '$2y$10$14qT.gQCw8gd7M4wawfBGuHetBrf2gNTmxZFbRHitwRyYoRMEwR6i', 'admin', 'active', '2026-02-02 14:46:05'),
(3, '', 'user@events.com', 0, NULL, NULL, 0, '$2y$10$1DNO1jAfsMW98w1QORkPbO9ZfezEWk.DOsyf97Z7dbTXQTJBKdYE.', 'user', 'active', '2026-02-02 15:02:10'),
(4, 'Test User', 'user@example.com', 0, NULL, NULL, 0, '\\.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', '2026-02-02 15:03:38'),
(5, 'Warima', 'warimaedgar@gmail.com', 0, NULL, NULL, 0, '$2y$10$glnCyW13pPRK.cgITo84WukUZ27ZOCR3fT6t2VOVPAl1fVEPQc3.G', 'user', 'active', '2026-03-05 13:11:47'),
(6, 'Maureen', 'maureenndunge505@gmail.com', 0, NULL, NULL, 0, '$2y$10$UzVqM.rAJg/6.095IPbZ9.q77SQ54tQps5VijIbuINuwkfawqIVZe', 'user', 'active', '2026-04-04 08:22:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_admin_logs` (`admin_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_date` (`event_date`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_booking` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `fk_admin_logs` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
