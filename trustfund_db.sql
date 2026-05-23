-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2026 at 09:55 PM
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
  `status` enum('pending','paid','late') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`group_id`, `group_name`, `description`, `contribution_amount`, `frequency`, `cycle_length`, `invite_code`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'damnation', 'for cellphone', 1000.00, 'monthly', 10, NULL, 1, 5, '2026-05-20 17:13:22'),
(2, 'exel', 'TrustFund Paluwagan Savings Pool Group Circle.', 500.00, 'weekly', 0, 'AE6AC1', 1, 5, '2026-05-23 16:47:44'),
(3, 'fool', 'TrustFund Paluwagan Savings Pool Group Circle.', 500.00, 'weekly', 5, '7F8CB4', 1, 5, '2026-05-23 16:48:21');

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
(1, 5, 1, 'active', '2026-05-20 17:13:22'),
(2, 5, 2, 'active', '2026-05-23 16:47:44'),
(3, 5, 3, 'active', '2026-05-23 16:48:21');

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
  `status` enum('pending','released') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `created_at`) VALUES
(3, 'mell', 'MELISSA', 'TORRENTE ACANTILADO', 'melissaacantilado69@ggmail.com', '09943536404', '$2y$10$bseVfLPasjYiaAWso8mLXux6BC9RIMT9Q2agciKf/iF', 'member', '2026-05-19 03:30:06'),
(4, 'Ace ', 'aceneil', 'melo', 'aceneilmelo69@gmail.com', '09943536404', '$2y$10$49wy27lqFvuFWbyU5H8Nq.2UhTatidlDaYJMtrXdwgRuzjemDTCO2', 'member', '2026-05-19 03:36:32'),
(5, 'arvin', 'arvin', 'melo', 'arvinmelo27@gmail.com', '09943536404', '$2y$10$fhRoPmAMDpDJ78RwyZf/NOKoUUoLup0G7QUrLTnQnis.JpHMgxc.O', 'admin', '2026-05-20 16:44:56'),
(9, 'admin', 'System', 'Admin', 'admin@trustfund.com', '09123456789', '$2y$10$mR3MKBgZJm2g6Y2E5C9AeuK/7w1pGfKxX1Y.e37yK7gq6O2X8zW6i', 'admin', '2026-05-23 17:02:06');

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
  MODIFY `contribution_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cycles`
--
ALTER TABLE `cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `payout_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT;

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
