-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2026 at 02:49 PM
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
CREATE DATABASE IF NOT EXISTS `trustfund_db`;
USE `trustfund_db`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','activated','suspended','denied') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `privacy` enum('public','private') DEFAULT 'public',
  `contribution_amount` decimal(10,2) DEFAULT NULL,
  `max_members` int(11) NOT NULL DEFAULT 5,
  `frequency` enum('weekly','biweekly','monthly') DEFAULT 'monthly',
  `cycle_length` int(11) DEFAULT NULL,
  `current_cycle` int(11) NOT NULL DEFAULT 1,
  `invite_code` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','active','completed','cancelled','finished','closed') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `invite_code` (`invite_code`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `position` int(11) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`member_id`),
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cycles`
--

CREATE TABLE `cycles` (
  `cycle_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `cycle_number` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ongoing','completed') DEFAULT 'ongoing',
  `payout_member_id` int(11) DEFAULT NULL,
  `payout_status` enum('pending','released') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`cycle_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contributions`
--

CREATE TABLE `contributions` (
  `contribution_id` int(11) NOT NULL AUTO_INCREMENT,
  `cycle_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `paid_at` date DEFAULT NULL,
  `status` enum('pending','paid','late') DEFAULT 'pending',
  PRIMARY KEY (`contribution_id`),
  KEY `cycle_id` (`cycle_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payouts`
--

CREATE TABLE `payouts` (
  `payout_id` int(11) NOT NULL AUTO_INCREMENT,
  `cycle_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payout_date` date DEFAULT NULL,
  `status` enum('pending','released') DEFAULT 'pending',
  PRIMARY KEY (`payout_id`),
  KEY `cycle_id` (`cycle_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_creation_history`
--

CREATE TABLE `user_creation_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `original_user_id` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `verification_status` varchar(50) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `verification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `document` varchar(100) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`verification_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_requests`
-- Required relation referenced by audit indexes & rules
--

CREATE TABLE `wallet_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdraw') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(60) DEFAULT NULL,
  `account_details` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_wallet_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions` (for OLAP / analytics)
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('contribution','payout') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 1. PERFORMANCE INDEXES (From improvements.sql)
-- ============================================================

ALTER TABLE `contributions`
  ADD INDEX `idx_contributions_cycle_status` (`cycle_id`, `status`),
  ADD INDEX `idx_contributions_member` (`member_id`);

ALTER TABLE `cycles`
  ADD INDEX `idx_cycles_group_number` (`group_id`, `cycle_number`),
  ADD INDEX `idx_cycles_payout_status` (`payout_status`),
  ADD INDEX `idx_cycles_group_payout` (`group_id`, `payout_status`);

ALTER TABLE `group_members`
  ADD INDEX `idx_group_members_group_status` (`group_id`, `status`),
  ADD INDEX `idx_group_members_group_position` (`group_id`, `position`),
  ADD INDEX `idx_group_members_user_group` (`user_id`, `group_id`);

ALTER TABLE `transactions`
  ADD INDEX `idx_transactions_date_group` (`transaction_date`, `group_id`),
  ADD INDEX `idx_transactions_type` (`transaction_type`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `cycle_id` (`cycle_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `recorded_by` (`recorded_by`);

ALTER TABLE `wallet_requests`
  ADD INDEX `idx_wallet_user_status` (`user_id`, `status`),
  ADD INDEX `idx_wallet_status` (`status`);

-- Upgrade for older installs: add any missing columns (safe if already present)
ALTER TABLE `wallet_requests`
  ADD COLUMN IF NOT EXISTS `account_details` text DEFAULT NULL AFTER `payment_method`,
  ADD COLUMN IF NOT EXISTS `attachment` varchar(255) DEFAULT NULL AFTER `account_details`,
  ADD COLUMN IF NOT EXISTS `reviewed_by` int(11) DEFAULT NULL AFTER `attachment`,
  ADD COLUMN IF NOT EXISTS `reviewed_at` datetime DEFAULT NULL AFTER `reviewed_by`;

ALTER TABLE `groups`
  ADD INDEX `idx_groups_status` (`status`),
  ADD INDEX `idx_groups_created_by` (`created_by`);


-- ============================================================
-- 2. FOREIGN KEY CONSTRAINTS WITH CASCADES
-- ============================================================

ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `cycles`
  ADD CONSTRAINT `cycles_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cycles_payout_member_fk` FOREIGN KEY (`payout_member_id`) REFERENCES `group_members` (`member_id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `contributions`
  ADD CONSTRAINT `contributions_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `contributions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `group_members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `payouts`
  ADD CONSTRAINT `payouts_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payouts_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `group_members` (`member_id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `user_verifications`
  ADD CONSTRAINT `user_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `cycles` (`cycle_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `group_members` (`member_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_5` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE;


-- ============================================================
-- 3. CHECK CONSTRAINTS (Database-level business rules)
-- ============================================================

ALTER TABLE `contributions` ADD CONSTRAINT `chk_contribution_amount_positive` CHECK (`amount` > 0);
ALTER TABLE `payouts`       ADD CONSTRAINT `chk_payout_amount_positive`       CHECK (`amount` > 0);
ALTER TABLE `transactions`  ADD CONSTRAINT `chk_transaction_amount_positive`  CHECK (`amount` > 0);
ALTER TABLE `wallet_requests` ADD CONSTRAINT `chk_wallet_amount_positive`     CHECK (`amount` > 0);
ALTER TABLE `cycles`        ADD CONSTRAINT `chk_cycle_number_positive`        CHECK (`cycle_number` > 0);


-- ============================================================
-- 4. TRANSACTION AUDIT / LOGGING INFRASTRUCTURE
-- ============================================================

CREATE TABLE IF NOT EXISTS `failed_transaction_logs` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `reason` TEXT NOT NULL,
  `script` VARCHAR(255) DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_failed_tx_user` (`user_id`),
  KEY `idx_failed_tx_created` (`created_at`),
  CONSTRAINT `fk_failed_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `transaction_audit` (
  `audit_id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `table_name` VARCHAR(64) NOT NULL,
  `operation` ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  `record_id` INT NOT NULL,
  `group_id` INT DEFAULT NULL,
  `amount` DECIMAL(10,2) DEFAULT NULL,
  `details` JSON NULL,
  `performed_by` INT DEFAULT NULL,
  `performed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_audit_table_op` (`table_name`, `operation`),
  KEY `idx_audit_group` (`group_id`),
  KEY `idx_audit_time` (`performed_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`performed_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 5. TRIGGERS FOR AUTOMATIC AUDIT LOGGING
-- ============================================================

DELIMITER $$

-- Contributions audit
DROP TRIGGER IF EXISTS trg_contributions_audit_insert$$
CREATE TRIGGER trg_contributions_audit_insert
AFTER INSERT ON contributions
FOR EACH ROW
BEGIN
    INSERT INTO transaction_audit (table_name, operation, record_id, group_id, amount, details, performed_at)
    VALUES (
        'contributions', 'INSERT', NEW.contribution_id,
        (SELECT g.group_id FROM cycles c JOIN groups g ON g.group_id = c.group_id WHERE c.cycle_id = NEW.cycle_id),
        NEW.amount,
        JSON_OBJECT(
            'cycle_id', NEW.cycle_id,
            'member_id', NEW.member_id,
            'status', NEW.status
        ),
        NOW()
    );
END$$

-- Payouts audit
DROP TRIGGER IF EXISTS trg_payouts_audit_insert$$
CREATE TRIGGER trg_payouts_audit_insert
AFTER INSERT ON payouts
FOR EACH ROW
BEGIN
    INSERT INTO transaction_audit (table_name, operation, record_id, group_id, amount, details, performed_at)
    VALUES (
        'payouts', 'INSERT', NEW.payout_id,
        (SELECT c.group_id FROM cycles c WHERE c.cycle_id = NEW.cycle_id),
        NEW.amount,
        JSON_OBJECT(
            'cycle_id', NEW.cycle_id,
            'member_id', NEW.member_id,
            'status', NEW.status
        ),
        NOW()
    );
END$$

-- Transactions audit
DROP TRIGGER IF EXISTS trg_transactions_audit_insert$$
CREATE TRIGGER trg_transactions_audit_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    INSERT INTO transaction_audit (table_name, operation, record_id, group_id, amount, details, performed_at)
    VALUES (
        'transactions', 'INSERT', NEW.transaction_id,
        NEW.group_id,
        NEW.amount,
        JSON_OBJECT(
            'cycle_id', NEW.cycle_id,
            'member_id', NEW.member_id,
            'transaction_type', NEW.transaction_type,
            'recorded_by', NEW.recorded_by
        ),
        NOW()
    );
END$$

-- Wallet requests audit
DROP TRIGGER IF EXISTS trg_wallet_requests_audit_insert$$
CREATE TRIGGER trg_wallet_requests_audit_insert
AFTER INSERT ON wallet_requests
FOR EACH ROW
BEGIN
    INSERT INTO transaction_audit (table_name, operation, record_id, group_id, amount, details, performed_at)
    VALUES (
        'wallet_requests', 'INSERT', NEW.request_id, NULL, NEW.amount,
        JSON_OBJECT(
            'user_id', NEW.user_id,
            'type', NEW.type,
            'status', NEW.status,
            'payment_method', NEW.payment_method
        ),
        NOW()
    );
END$$

DELIMITER ;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;