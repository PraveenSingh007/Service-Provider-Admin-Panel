-- Database: `service_provider`
-- SQL Schema Script for `callback_requests` Table

USE `service_provider`;

CREATE TABLE IF NOT EXISTS `callback_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `callback_no` VARCHAR(50) NOT NULL UNIQUE,
  `customer_name` VARCHAR(150) NOT NULL,
  `mobile_no` VARCHAR(20) NOT NULL,
  `service_category` VARCHAR(100) DEFAULT 'other',
  `preferred_time_slot` VARCHAR(50) DEFAULT 'anytime',
  `note` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_callback_no` (`callback_no`),
  INDEX `idx_mobile` (`mobile_no`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
