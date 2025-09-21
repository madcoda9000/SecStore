-- SecStore Database Schema
-- Generated: 2025-09-20 06:28:53
-- Database: secstore_test1222
-- Description: Complete database structure for SecStore

-- Note: This file contains only the structure (CREATE TABLE statements)
-- For default data, see default_data.sql

-- ===================================
-- Table: failed_logins
-- ===================================
CREATE TABLE `failed_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_attempt` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Table: logs
-- ===================================
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datum_zeit` datetime DEFAULT current_timestamp(),
  `type` enum('ERROR','AUDIT','REQUEST','SYSTEM','MAIL','SQL','SECURITY') NOT NULL,
  `user` varchar(255) NOT NULL,
  `context` text NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Table: roles
-- ===================================
CREATE TABLE `roles` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `roleName` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Table: users
-- ===================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) DEFAULT '',
  `lastname` varchar(255) DEFAULT '',
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `roles` varchar(255) NOT NULL,
  `reset_token` varchar(255) DEFAULT '',
  `reset_token_expires` datetime DEFAULT NULL,
  `mfaStartSetup` int(11) NOT NULL DEFAULT 0,
  `mfaEnabled` int(11) NOT NULL DEFAULT 0,
  `mfaEnforced` int(11) NOT NULL DEFAULT 0,
  `mfaSecret` varchar(2500) NOT NULL DEFAULT '',
  `ldapEnabled` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `activeSessionId` varchar(255) DEFAULT '',
  `lastKnownIp` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Additional Indexes for Performance
-- ===================================
-- These indexes improve query performance
-- but are not strictly required for basic functionality

CREATE INDEX IF NOT EXISTS `idx_users_status` ON `users` (`status`);
CREATE INDEX IF NOT EXISTS `idx_users_roles` ON `users` (`roles`);
CREATE INDEX IF NOT EXISTS `idx_users_created_at` ON `users` (`created_at`);
CREATE INDEX IF NOT EXISTS `idx_logs_type_date` ON `logs` (`type`, `datum_zeit`);
CREATE INDEX IF NOT EXISTS `idx_logs_user_date` ON `logs` (`user`, `datum_zeit`);
CREATE INDEX IF NOT EXISTS `idx_failed_logins_ip_time` ON `failed_logins` (`ip_address`, `last_attempt`);
