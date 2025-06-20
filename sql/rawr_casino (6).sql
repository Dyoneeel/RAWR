-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2025 at 05:17 AM
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
-- Database: `rawr_casino`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_log`
--

CREATE TABLE `admin_audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL,
  `target_type` enum('user','item','kyc','game','purchase') NOT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `details` text DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `session_id` varchar(128) NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','admin','moderator') NOT NULL DEFAULT 'admin',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `full_name`, `role`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$TxZ5w1d3c0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7s8t9u0v1w2x3y4z', 'Administrator', 'superadmin', NULL, '2025-06-12 14:47:38');

-- --------------------------------------------------------

--
-- Table structure for table `challenge_progress`
--

CREATE TABLE `challenge_progress` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `challenge_id` tinyint(3) UNSIGNED NOT NULL,
  `progress` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `reward_claimed` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `challenge_types`
--

CREATE TABLE `challenge_types` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `reward_type` enum('tickets','rawr','item') NOT NULL,
  `reward_value` int(10) UNSIGNED NOT NULL,
  `target_value` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `challenge_types`
--

INSERT INTO `challenge_types` (`id`, `name`, `description`, `reward_type`, `reward_value`, `target_value`) VALUES
(1, 'Daily Login', 'Log in 3 days in a row', 'tickets', 100, 3),
(2, 'Mining Master', 'Mine 500 RAWR tokens', 'rawr', 50, 500),
(3, 'Game Enthusiast', 'Play 10 games', 'tickets', 150, 10),
(4, 'Big Spender', 'Spend 1000 tickets in the shop', 'item', 1, 1000);

-- --------------------------------------------------------

--
-- Table structure for table `conversion_logs`
--

CREATE TABLE `conversion_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `rawr_amount` decimal(18,8) NOT NULL,
  `tickets_received` int(10) UNSIGNED NOT NULL,
  `converted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_results`
--

CREATE TABLE `game_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `game_type_id` tinyint(3) UNSIGNED NOT NULL,
  `bet_amount` int(10) UNSIGNED NOT NULL,
  `payout` int(10) UNSIGNED NOT NULL,
  `outcome` enum('win','loss') NOT NULL,
  `played_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `game_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`game_details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_types`
--

CREATE TABLE `game_types` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_types`
--

INSERT INTO `game_types` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Dice', 'dice', 'High-Low dice game'),
(2, 'Card Flip', 'card-flip', 'Guess the card suit or value'),
(3, 'Slot Machine', 'slots', 'Classic 3-reel slot machine'),
(4, 'Russian Roulette', 'roulette', 'Safe visual roulette game'),
(5, 'Jungle Jackpot', 'jackpot', 'Reaction-tap jackpot game');

-- --------------------------------------------------------

--
-- Table structure for table `kyc_requests`
--

CREATE TABLE `kyc_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `id_image_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_streaks`
--

CREATE TABLE `login_streaks` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `current_streak` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `longest_streak` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `last_login_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_streaks`
--

INSERT INTO `login_streaks` (`user_id`, `current_streak`, `longest_streak`, `last_login_date`) VALUES
(1, 2, 5, '2025-06-11'),
(2, 0, 0, '2025-06-13'),
(3, 0, 0, '2025-06-13'),
(4, 1, 1, '2025-06-19'),
(5, 1, 1, '2025-06-19');

-- --------------------------------------------------------

--
-- Table structure for table `mining_data`
--

CREATE TABLE `mining_data` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `last_mined_at` timestamp NULL DEFAULT NULL,
  `boost_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `total_mined` decimal(18,8) NOT NULL DEFAULT 0.00000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mining_data`
--

INSERT INTO `mining_data` (`user_id`, `last_mined_at`, `boost_level`, `total_mined`) VALUES
(1, NULL, 1, 0.00000000),
(2, NULL, 1, 0.00000000),
(3, NULL, 1, 0.00000000),
(4, NULL, 1, 0.00000000),
(5, NULL, 1, 0.00000000);

-- --------------------------------------------------------

--
-- Table structure for table `mining_logs`
--

CREATE TABLE `mining_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(18,8) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mining_upgrades`
--

CREATE TABLE `mining_upgrades` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `shovel_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `energy_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `pickaxe_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mining_upgrades`
--

INSERT INTO `mining_upgrades` (`user_id`, `shovel_level`, `energy_level`, `pickaxe_level`) VALUES
(4, 1, 1, 1),
(5, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `purchase_price` int(10) UNSIGNED NOT NULL,
  `purchased_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `referrer_id` int(10) UNSIGNED NOT NULL,
  `referred_id` int(10) UNSIGNED NOT NULL,
  `referred_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kyc_approved_at` timestamp NULL DEFAULT NULL,
  `bonus_awarded` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`id`, `referrer_id`, `referred_id`, `referred_at`, `kyc_approved_at`, `bonus_awarded`) VALUES
(1, 4, 5, '2025-06-16 21:08:03', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reward_logs`
--

CREATE TABLE `reward_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('daily','challenge','referral') NOT NULL,
  `rawr_amount` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `ticket_amount` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_categories`
--

CREATE TABLE `shop_categories` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shop_categories`
--

INSERT INTO `shop_categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Mining Boosts', 'boosts', 'Upgrades to improve your mining efficiency'),
(2, 'Cosmetics', 'cosmetics', 'Visual upgrades for your profile'),
(3, 'Utility', 'utility', 'Functional items to enhance gameplay');

-- --------------------------------------------------------

--
-- Table structure for table `shop_items`
--

CREATE TABLE `shop_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` int(10) UNSIGNED NOT NULL,
  `category_id` tinyint(3) UNSIGNED NOT NULL,
  `boost_multiplier` decimal(3,2) DEFAULT NULL,
  `item_type` enum('boost','cosmetic','utility') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shop_items`
--

INSERT INTO `shop_items` (`id`, `name`, `description`, `price`, `category_id`, `boost_multiplier`, `item_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Bronze Pickaxe', 'Increases mining speed by 10%', 500, 1, 1.10, 'boost', 1, '2025-06-12 14:47:38', NULL),
(2, 'Silver Pickaxe', 'Increases mining speed by 25%', 1200, 1, 1.25, 'boost', 1, '2025-06-12 14:47:38', NULL),
(3, 'Golden Pickaxe', 'Increases mining speed by 50%', 2500, 1, 1.50, 'boost', 1, '2025-06-12 14:47:38', NULL),
(4, 'Lion Avatar', 'Exclusive lion avatar for your profile', 800, 2, NULL, 'cosmetic', 1, '2025-06-12 14:47:38', NULL),
(5, 'Jungle Theme', 'Special jungle-themed UI skin', 1500, 2, NULL, 'cosmetic', 1, '2025-06-12 14:47:38', NULL),
(6, 'Lucky Charm', 'Slightly increases game win chances', 2000, 3, NULL, 'utility', 1, '2025-06-12 14:47:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rawr_balance` decimal(18,8) NOT NULL DEFAULT 0.00000000,
  `ticket_balance` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `referral_code` varchar(10) NOT NULL,
  `referred_by` int(10) UNSIGNED DEFAULT NULL,
  `kyc_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `rawr_balance`, `ticket_balance`, `referral_code`, `referred_by`, `kyc_status`, `is_banned`, `created_at`, `last_login`) VALUES
(1, 'testuser', 'user@example.com', '$2y$10$TxZ5w1d3c0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7s8t9u0v1w2x3y4z', 125.75000000, 500, 'REF123456', NULL, 'approved', 0, '2025-06-12 14:47:38', NULL),
(2, 'nelll', 'jonelandamon06@gmail.com', '$2y$10$e3OhhvMRd6byp5E2yFmQmOfPl5setHJsxAbyronryxuGuujewn7Oi', 0.00000000, 0, 'MB4U7T2N', NULL, 'pending', 0, '2025-06-12 23:40:43', '2025-06-13 01:56:00'),
(3, 'danzle', 'd@gmail.com', '$2y$10$49VDemodOY5A5aKWzh6Uu.IHW/Tm0lli.oFgupHcLV16J5vEON1TW', 0.00000000, 0, '2YNNAZ9L', NULL, 'pending', 0, '2025-06-13 03:09:44', '2025-06-13 07:12:13'),
(4, 'nelie', 'ja@gmail.com', '$2y$10$uwhcnGe78lUr8i70D7LiVO2sWyem8DTxgm.ILP.ryEdzoYQvfe79m', 160.00000000, 2, 'QJU9ZRT4', NULL, 'pending', 0, '2025-06-13 07:20:08', '2025-06-18 19:11:56'),
(5, 'eric', 'diaz@gmail.com', '$2y$10$o6HZ3AXJo0RhqSO1Nk1WGuOkkrgMHcNfGVuIHNodxQDV6VS.gTt0q', 55.00000000, 1, 'T825BBXB', 4, 'pending', 0, '2025-06-16 21:08:02', '2025-06-18 18:12:12');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `challenge_progress`
--
ALTER TABLE `challenge_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_challenge` (`user_id`,`challenge_id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indexes for table `challenge_types`
--
ALTER TABLE `challenge_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversion_logs`
--
ALTER TABLE `conversion_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `game_results`
--
ALTER TABLE `game_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_type_id` (`game_type_id`),
  ADD KEY `played_at` (`played_at`);

--
-- Indexes for table `game_types`
--
ALTER TABLE `game_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `kyc_requests`
--
ALTER TABLE `kyc_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`ip_address`);

--
-- Indexes for table `login_streaks`
--
ALTER TABLE `login_streaks`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `mining_data`
--
ALTER TABLE `mining_data`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `mining_logs`
--
ALTER TABLE `mining_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mining_upgrades`
--
ALTER TABLE `mining_upgrades`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referrer_id`,`referred_id`),
  ADD UNIQUE KEY `referred_id` (`referred_id`),
  ADD KEY `referrer_id` (`referrer_id`);

--
-- Indexes for table `reward_logs`
--
ALTER TABLE `reward_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `shop_categories`
--
ALTER TABLE `shop_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `shop_items`
--
ALTER TABLE `shop_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD KEY `referred_by` (`referred_by`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `challenge_progress`
--
ALTER TABLE `challenge_progress`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `challenge_types`
--
ALTER TABLE `challenge_types`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `conversion_logs`
--
ALTER TABLE `conversion_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_results`
--
ALTER TABLE `game_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_types`
--
ALTER TABLE `game_types`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kyc_requests`
--
ALTER TABLE `kyc_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mining_logs`
--
ALTER TABLE `mining_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward_logs`
--
ALTER TABLE `reward_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shop_categories`
--
ALTER TABLE `shop_categories`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shop_items`
--
ALTER TABLE `shop_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  ADD CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `fk_session_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `challenge_progress`
--
ALTER TABLE `challenge_progress`
  ADD CONSTRAINT `fk_challenge_type` FOREIGN KEY (`challenge_id`) REFERENCES `challenge_types` (`id`),
  ADD CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `conversion_logs`
--
ALTER TABLE `conversion_logs`
  ADD CONSTRAINT `fk_conversion_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_results`
--
ALTER TABLE `game_results`
  ADD CONSTRAINT `fk_game_type` FOREIGN KEY (`game_type_id`) REFERENCES `game_types` (`id`),
  ADD CONSTRAINT `fk_game_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kyc_requests`
--
ALTER TABLE `kyc_requests`
  ADD CONSTRAINT `fk_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_streaks`
--
ALTER TABLE `login_streaks`
  ADD CONSTRAINT `fk_streak_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mining_data`
--
ALTER TABLE `mining_data`
  ADD CONSTRAINT `fk_mining_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mining_logs`
--
ALTER TABLE `mining_logs`
  ADD CONSTRAINT `fk_mininglog_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchase_item` FOREIGN KEY (`item_id`) REFERENCES `shop_items` (`id`),
  ADD CONSTRAINT `fk_purchase_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `fk_referred` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reward_logs`
--
ALTER TABLE `reward_logs`
  ADD CONSTRAINT `reward_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `shop_items`
--
ALTER TABLE `shop_items`
  ADD CONSTRAINT `fk_item_category` FOREIGN KEY (`category_id`) REFERENCES `shop_categories` (`id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
