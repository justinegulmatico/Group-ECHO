-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 06, 2026 at 09:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trustfund_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `contributions`
--

CREATE TABLE `contributions` (
  `contribution_id` int(11) NOT NULL,
  `cycle_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `paid_at` date DEFAULT NULL,
  `status` enum('pending','paid','late') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'Cash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contributions`
--

INSERT INTO `contributions` (`contribution_id`, `cycle_id`, `member_id`, `amount`, `due_date`, `paid_at`, `status`, `payment_method`) VALUES
(1, 1, 10, 500.00, '2026-06-12', NULL, 'paid', 'Cash'),
(2, 2, 10, 500.00, '2026-06-19', NULL, 'pending', 'Cash'),
(3, 3, 10, 500.00, '2026-06-26', NULL, 'pending', 'Cash'),
(4, 4, 10, 500.00, '2026-07-03', NULL, 'pending', 'Cash'),
(5, 5, 10, 500.00, '2026-07-10', '2026-06-05', 'paid', 'Cash'),
(7, 7, 11, 300.00, '2026-06-13', NULL, 'pending', 'Cash'),
(8, 8, 11, 300.00, '2026-06-20', NULL, 'pending', 'Cash'),
(9, 9, 11, 300.00, '2026-06-27', NULL, 'pending', 'Cash'),
(10, 10, 12, 300.00, '2026-06-13', NULL, 'pending', 'Cash'),
(11, 11, 12, 300.00, '2026-06-20', NULL, 'pending', 'Cash'),
(12, 12, 12, 300.00, '2026-06-27', NULL, 'pending', 'Cash'),
(13, 13, 13, 500.00, '2026-06-13', '2026-06-06', 'paid', 'Cash'),
(14, 14, 13, 500.00, '2026-06-20', '2026-06-06', 'paid', 'Cash'),
(15, 15, 13, 500.00, '2026-06-27', '2026-06-06', 'paid', 'Cash'),
(16, 16, 13, 500.00, '2026-07-04', NULL, 'pending', 'Cash'),
(17, 17, 13, 500.00, '2026-07-11', NULL, 'pending', 'Cash'),
(18, 13, 14, 500.00, '2026-06-13', '2026-06-06', 'paid', 'Cash'),
(19, 14, 14, 500.00, '2026-06-20', '2026-06-06', 'paid', 'Cash'),
(20, 15, 14, 500.00, '2026-06-27', NULL, 'pending', 'Cash'),
(21, 16, 14, 500.00, '2026-07-04', NULL, 'pending', 'Cash'),
(22, 17, 14, 500.00, '2026-07-11', NULL, 'pending', 'Cash'),
(23, 18, 15, 500.00, '2026-06-13', '2026-06-06', 'paid', 'Cash'),
(24, 19, 15, 500.00, '2026-06-20', NULL, 'pending', 'Cash'),
(25, 20, 15, 500.00, '2026-06-27', NULL, 'pending', 'Cash'),
(26, 18, 16, 500.00, '2026-06-13', '2026-06-06', 'paid', 'Cash'),
(27, 19, 16, 500.00, '2026-06-20', NULL, 'pending', 'Cash'),
(28, 20, 16, 500.00, '2026-06-27', NULL, 'pending', 'Cash'),
(29, 18, 17, 500.00, '2026-06-13', '2026-06-06', 'paid', 'Cash'),
(30, 19, 17, 500.00, '2026-06-20', NULL, 'pending', 'Cash'),
(31, 20, 17, 500.00, '2026-06-27', NULL, 'pending', 'Cash'),
(32, 21, 16, 500.00, '2026-06-13', NULL, 'pending', 'Cash'),
(33, 21, 17, 500.00, '2026-06-13', NULL, 'pending', 'Cash'),
(34, 22, 18, 600.00, '2026-07-06', '2026-06-06', 'paid', 'Cash'),
(35, 23, 18, 600.00, '2026-08-06', '2026-06-06', 'paid', 'Cash'),
(36, 22, 19, 600.00, '2026-07-06', '2026-06-06', 'paid', 'Cash'),
(37, 23, 19, 600.00, '2026-08-06', '2026-06-06', 'paid', 'Cash'),
(38, 24, 20, 400.00, '2026-07-06', NULL, 'pending', 'Cash'),
(39, 25, 20, 400.00, '2026-08-06', NULL, 'pending', 'Cash');

-- --------------------------------------------------------

--
-- Table structure for table `cycles`
--

CREATE TABLE `cycles` (
  `cycle_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `cycle_number` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ongoing','completed') DEFAULT 'ongoing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cycles`
--

INSERT INTO `cycles` (`cycle_id`, `group_id`, `cycle_number`, `start_date`, `end_date`, `status`) VALUES
(1, 8, 1, '2026-06-05', '2026-06-12', 'ongoing'),
(2, 8, 2, '2026-06-12', '2026-06-19', 'ongoing'),
(3, 8, 3, '2026-06-19', '2026-06-26', 'ongoing'),
(4, 8, 4, '2026-06-26', '2026-07-03', 'ongoing'),
(5, 8, 5, '2026-07-03', '2026-07-10', 'ongoing'),
(7, 9, 1, '2026-06-06', '2026-06-13', 'completed'),
(8, 9, 2, '2026-06-13', '2026-06-20', 'ongoing'),
(9, 9, 3, '2026-06-20', '2026-06-27', 'ongoing'),
(10, 10, 1, '2026-06-06', '2026-06-13', 'completed'),
(11, 10, 2, '2026-06-13', '2026-06-20', 'ongoing'),
(12, 10, 3, '2026-06-20', '2026-06-27', 'ongoing'),
(13, 11, 1, '2026-06-06', '2026-06-13', 'completed'),
(14, 11, 2, '2026-06-13', '2026-06-20', 'completed'),
(15, 11, 3, '2026-06-20', '2026-06-27', 'ongoing'),
(16, 11, 4, '2026-06-27', '2026-07-04', 'ongoing'),
(17, 11, 5, '2026-07-04', '2026-07-11', 'ongoing'),
(18, 12, 1, '2026-06-06', '2026-06-13', 'completed'),
(19, 12, 2, '2026-06-13', '2026-06-20', 'ongoing'),
(20, 12, 3, '2026-06-20', '2026-06-27', 'ongoing'),
(21, 12, 2, '2026-06-06', '2026-06-13', 'completed'),
(22, 13, 1, '2026-06-06', '2026-07-06', 'completed'),
(23, 13, 2, '2026-07-06', '2026-08-06', 'ongoing'),
(24, 14, 1, '2026-06-06', '2026-07-06', 'ongoing'),
(25, 14, 2, '2026-07-06', '2026-08-06', 'ongoing');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `contribution_amount` decimal(10,2) DEFAULT NULL,
  `frequency` enum('weekly','monthly') DEFAULT 'monthly',
  `cycle_length` int(11) DEFAULT NULL,
  `invite_code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','pending','finished','closed','denied') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`group_id`, `group_name`, `description`, `contribution_amount`, `frequency`, `cycle_length`, `invite_code`, `is_active`, `created_by`, `created_at`, `status`) VALUES
(6, 'testrun3', 'TrustFund Paluwagan Savings Pool Group Circle.', 500.00, 'monthly', 4, '06C14F', 1, 10, '2026-06-04 20:17:01', ''),
(7, 'testrun4', 'TrustFund Paluwagan Savings Pool Group Circle.', 800.00, 'weekly', 5, '9EFDC8', 1, 14, '2026-06-05 16:14:06', 'active'),
(8, 'testrun5', 'TrustFund Paluwagan Savings Pool Group Circle.', 500.00, 'weekly', 5, 'EA4A46', 1, 14, '2026-06-05 17:46:53', 'active'),
(9, 'testrun6', 'TrustFund Paluwagan Savings Pool Group Circle.', 300.00, 'weekly', 3, '37C685', 1, 13, '2026-06-06 14:58:33', ''),
(10, 'testrun6', 'TrustFund Paluwagan Savings Pool Group Circle.', 300.00, 'weekly', 3, '47081A', 0, 13, '2026-06-06 14:58:33', ''),
(11, 'pool', 'TrustFund Paluwagan Savings Pool Group Circle.', 500.00, 'weekly', 5, 'F94099', 1, 16, '2026-06-06 15:14:15', ''),
(12, 'testrun', 'TrustFund Paluwagan Savings Pool Group Circle.', 500.00, 'weekly', 3, '5CEC40', 1, 10, '2026-06-06 15:32:20', ''),
(13, 'confirm', 'TrustFund Paluwagan Savings Pool Group Circle.', 600.00, 'monthly', 2, '4F1238', 1, 16, '2026-06-06 15:59:03', ''),
(14, 'testrun7', 'TrustFund Paluwagan Savings Pool Group Circle.', 400.00, 'monthly', 2, '0E6550', 1, 16, '2026-06-06 18:36:21', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `member_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`member_id`, `user_id`, `group_id`, `status`, `joined_at`) VALUES
(8, 10, 6, 'active', '2026-06-04 20:17:01'),
(11, 13, 9, 'active', '2026-06-06 14:58:33'),
(12, 13, 10, 'active', '2026-06-06 14:58:33'),
(13, 16, 11, 'active', '2026-06-06 15:14:15'),
(14, 13, 11, 'active', '2026-06-06 15:16:41'),
(15, 10, 12, 'active', '2026-06-06 15:32:20'),
(16, 16, 12, 'active', '2026-06-06 15:33:37'),
(17, 13, 12, 'active', '2026-06-06 15:34:10'),
(18, 16, 13, 'active', '2026-06-06 15:59:03'),
(19, 13, 13, 'active', '2026-06-06 16:01:03'),
(20, 16, 14, 'active', '2026-06-06 18:36:21');

-- --------------------------------------------------------

--
-- Table structure for table `payouts`
--

CREATE TABLE `payouts` (
  `payout_id` int(11) NOT NULL,
  `cycle_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payout_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payouts`
--

INSERT INTO `payouts` (`payout_id`, `cycle_id`, `member_id`, `amount`, `payout_date`, `status`) VALUES
(1, 1, 10, 2500.00, '2026-06-12', 'pending'),
(2, 2, NULL, 2500.00, '2026-06-19', 'pending'),
(3, 3, NULL, 2500.00, '2026-06-26', 'pending'),
(4, 4, NULL, 2500.00, '2026-07-03', 'pending'),
(5, 5, NULL, 2500.00, '2026-07-10', 'pending'),
(7, 7, 11, 900.00, '2026-06-06', 'released'),
(8, 8, NULL, 900.00, '2026-06-20', 'pending'),
(9, 9, NULL, 900.00, '2026-06-27', 'pending'),
(10, 10, 12, 900.00, '2026-06-06', 'released'),
(11, 11, NULL, 900.00, '2026-06-20', 'pending'),
(12, 12, NULL, 900.00, '2026-06-27', 'pending'),
(13, 13, 13, 2500.00, '2026-06-06', 'released'),
(14, 14, NULL, 2500.00, '2026-06-06', 'released'),
(15, 15, NULL, 2500.00, '2026-06-27', 'pending_approval'),
(16, 16, NULL, 2500.00, '2026-07-04', 'pending'),
(17, 17, NULL, 2500.00, '2026-07-11', 'pending'),
(18, 18, 15, 1500.00, '2026-06-06', 'released'),
(19, 19, NULL, 1500.00, '2026-06-20', 'pending'),
(20, 20, NULL, 1500.00, '2026-06-27', 'pending'),
(21, 21, 17, 1000.00, '2026-06-06', 'released'),
(22, 22, 18, 1200.00, '2026-06-06', 'released'),
(23, 23, NULL, 1200.00, '2026-08-06', 'pending_approval'),
(24, 24, 20, 800.00, '2026-07-06', 'pending'),
(25, 25, NULL, 800.00, '2026-08-06', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `status` enum('pending','active','suspended','denied') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `occupation` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `status`, `created_at`, `occupation`, `address`) VALUES
(3, 'mell', 'MELISSA', 'TORRENTE ACANTILADO', 'melissaacantilado69@ggmail.com', '09943536404', '$2y$10$bseVfLPasjYiaAWso8mLXux6BC9RIMT9Q2agciKf/iF', 'member', 'pending', '2026-05-19 03:30:06', NULL, NULL),
(4, 'Ace ', 'aceneil', 'melo', 'aceneilmelo69@gmail.com', '09943536404', '$2y$10$49wy27lqFvuFWbyU5H8Nq.2UhTatidlDaYJMtrXdwgRuzjemDTCO2', 'member', 'pending', '2026-05-19 03:36:32', NULL, NULL),
(5, 'arvin', 'arvin', 'melo', 'arvinmelo27@gmail.com', '09943536404', '$2y$10$fhRoPmAMDpDJ78RwyZf/NOKoUUoLup0G7QUrLTnQnis.JpHMgxc.O', 'admin', 'pending', '2026-05-20 16:44:56', NULL, NULL),
(9, 'admin', 'System', 'Admin', 'admin@trustfund.com', '09123456789', '$2y$10$mR3MKBgZJm2g6Y2E5C9AeuK/7w1pGfKxX1Y.e37yK7gq6O2X8zW6i', 'admin', 'pending', '2026-05-23 17:02:06', NULL, NULL),
(10, 'gojo', 'Gojo', 'Saturo', 'gojo@gmail.com', '123456789', '$2y$10$xeJyqjFkXtfwTMaWrCEyPedc/1EWQ0OdaHrq.DVyKubBNMQNgcA96', 'admin', 'active', '2026-06-04 03:43:32', 'sorccer', 'San Juan 2 Noveleta Cavite'),
(12, 'Jogo', 'none', 'User', 'admin@gmail.com', '09123456789', '0192023a7bbd73250516f069df18b500', 'admin', 'pending', '2026-06-04 03:50:32', 'Administrator', 'System Admin Address'),
(13, 'lanz', 'lanz', 'monte', 'lanz@gmail.com', '36754857663', '$2y$10$mZ2FKsW3xgJdK0OINtK5nedhbD.ujKxUKGYL751c5byKK5JEne5wW', 'member', 'active', '2026-06-04 15:25:11', 'cook', 'San Juan 2 Noveleta Cavite'),
(15, 'sam', 'sam', 'winters', 'sam@gmail.com', '2837463233', '$2y$10$QshBq18fidSqklE.QnZhvOI.LZZWxFXEc/oOF7glG2RDd3GFjCRFi', 'member', 'active', '2026-06-04 22:08:22', 'nurse', 'San Juan 2 Noveleta Cavite'),
(16, 'jack', 'jack', 'dawson', 'jack@gmail.com', '1283747583', '$2y$10$2qtk4ZkKrmwrDDtQdO.aOutEMWW1Li1Y5D3sNx6jC/DHeLTttjLYC', 'member', 'active', '2026-06-06 15:12:28', 'seaman', 'San Juan 2 Noveleta Cavite'),
(17, 'donny', 'donny', 'sol', 'donny@gmail.com', '24576556775', '$2y$10$8MhzbCvraUy9py9YcbaCieYcIjZJS8w8QbHaGuYr/4.qXPThdkHxm', 'member', 'active', '2026-06-06 15:27:45', 'cook', 'San Juan 2 Noveleta Cavite');

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `verification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `document` varchar(100) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_verifications`
--

INSERT INTO `user_verifications` (`verification_id`, `user_id`, `document`, `status`, `verified_at`) VALUES
(1, 15, NULL, 'verified', '2026-06-04'),
(2, 14, NULL, 'verified', '2026-06-05'),
(3, 13, NULL, 'verified', '2026-06-05'),
(4, 16, NULL, 'verified', '2026-06-06'),
(5, 17, NULL, 'verified', '2026-06-06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contributions`
--
ALTER TABLE `contributions`
  ADD PRIMARY KEY (`contribution_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `cycles`
--
ALTER TABLE `cycles`
  ADD PRIMARY KEY (`cycle_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `invite_code` (`invite_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`payout_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contributions`
--
ALTER TABLE `contributions`
  MODIFY `contribution_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `cycles`
--
ALTER TABLE `cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `payout_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contributions`
--
ALTER TABLE `contributions`
  ADD CONSTRAINT `contributions_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`),
  ADD CONSTRAINT `contributions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `group_members` (`member_id`);

--
-- Constraints for table `cycles`
--
ALTER TABLE `cycles`
  ADD CONSTRAINT `cycles_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`);

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`);

--
-- Constraints for table `payouts`
--
ALTER TABLE `payouts`
  ADD CONSTRAINT `payouts_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`),
  ADD CONSTRAINT `payouts_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `group_members` (`member_id`);

--
-- Constraints for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `user_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
