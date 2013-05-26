-- phpMyAdmin SQL Dump
-- version 3.5.8.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 27, 2013 at 02:15 AM
-- Server version: 5.5.31-0ubuntu0.13.04.1
-- PHP Version: 5.4.9-4ubuntu2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `razorpay_api`
--

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE IF NOT EXISTS `cards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(32) NOT NULL,
  `number` varchar(200) NOT NULL,
  `expiry_month` int(10) unsigned DEFAULT NULL,
  `expiry_year` int(10) unsigned DEFAULT NULL,
  `cvv` int(10) unsigned DEFAULT NULL,
  `cardtype_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `address_line1` varchar(200) DEFAULT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `address_state` varchar(200) DEFAULT NULL,
  `address_zip` int(10) unsigned DEFAULT NULL,
  `address_country` varchar(200) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cards_cardtype_id_foreign` (`cardtype_id`),
  KEY `cards_user_id_foreign` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `cards`
--

INSERT INTO `cards` (`id`, `token`, `number`, `expiry_month`, `expiry_year`, `cvv`, `cardtype_id`, `name`, `address_line1`, `address_line2`, `address_state`, `address_zip`, `address_country`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'crd_db2ddc1820371e909462cbd8d79b', '1234567887654321', NULL, NULL, NULL, NULL, 'ABHISHEK DAS', NULL, NULL, NULL, NULL, NULL, NULL, 1369598614, 1369598614);

-- --------------------------------------------------------

--
-- Table structure for table `cardtypes`
--

CREATE TABLE IF NOT EXISTS `cardtypes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `keys`
--

CREATE TABLE IF NOT EXISTS `keys` (
  `key` varchar(32) NOT NULL,
  `merchant_id` int(10) unsigned DEFAULT NULL,
  `secret` tinyint(1) NOT NULL,
  `live` tinyint(1) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `keys_merchant_id_foreign` (`merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `laravel_migrations`
--

CREATE TABLE IF NOT EXISTS `laravel_migrations` (
  `bundle` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`bundle`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `laravel_migrations`
--

INSERT INTO `laravel_migrations` (`bundle`, `name`, `batch`) VALUES
('application', '2013_03_30_204840_create_users', 1),
('application', '2013_03_30_204843_create_status', 1),
('application', '2013_03_30_204849_create_cardtypes', 1),
('application', '2013_03_30_204851_create_merchants', 1),
('application', '2013_03_30_204855_create_cards', 1),
('application', '2013_03_30_204909_create_transactions', 1),
('application', '2013_04_23_221828_create_keys', 1),
('application', '2013_05_21_060208_create_transactions_foreign_keys', 1);

-- --------------------------------------------------------

--
-- Table structure for table `merchants`
--

CREATE TABLE IF NOT EXISTS `merchants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(256) NOT NULL,
  `pwd` varchar(200) NOT NULL,
  `hash` varchar(200) NOT NULL,
  `key` varchar(200) NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merchants_email_unique` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `merchants`
--

INSERT INTO `merchants` (`id`, `email`, `pwd`, `hash`, `key`, `created_at`, `updated_at`) VALUES
(1, 'das.abhshk@gmail.com', 'helloworld', '$2y$10$/uIDLD6QA6i4cc/MHHjRyehD78MZsEZ6/s/vJveYxXiqC55Rvzmue', '', 1369396136, 1369396136),
(2, 'abc@gmail.com', '12345678', '$2y$10$bta.7xljzmWCMR0bLJmjpe6lSUyNtE7JlX6Zi0MYI/wyrKEvoQ/ne', '', 1369396275, 1369396275);

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE IF NOT EXISTS `status` (
  `code` int(10) unsigned NOT NULL,
  `description` varchar(200) NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(32) NOT NULL,
  `merchant_id` int(10) unsigned NOT NULL,
  `amount` float NOT NULL,
  `card_id` int(10) unsigned DEFAULT NULL,
  `status_code` int(10) unsigned DEFAULT NULL,
  `bankresponse` varchar(200) DEFAULT NULL,
  `currency` varchar(200) DEFAULT 'INR',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_merchant_id_foreign` (`merchant_id`),
  KEY `transactions_card_id_foreign` (`card_id`),
  KEY `transactions_status_code_foreign` (`status_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `token`, `merchant_id`, `amount`, `card_id`, `status_code`, `bankresponse`, `currency`, `created_at`, `updated_at`) VALUES
(7, 'txn_8683a40436f7ed2e5d4e946e79fa', 1, 10, 1, NULL, NULL, 'INR', 1369600374, 1369600374),
(8, 'txn_62c0308d6593fad527dab2985ad4', 1, 10, 1, NULL, NULL, 'INR', 1369600552, 1369600552);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `email` varchar(256) NOT NULL,
  `password` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cards`
--
ALTER TABLE `cards`
  ADD CONSTRAINT `cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cards_cardtype_id_foreign` FOREIGN KEY (`cardtype_id`) REFERENCES `cardtypes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `keys`
--
ALTER TABLE `keys`
  ADD CONSTRAINT `keys_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_status_code_foreign` FOREIGN KEY (`status_code`) REFERENCES `status` (`code`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_card_id_foreign` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
