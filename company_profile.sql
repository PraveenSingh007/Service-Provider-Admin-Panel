-- Database: `service_provider`
-- SQL Schema Script for Company Profile Module

USE `service_provider`;

CREATE TABLE IF NOT EXISTS `company_profile` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(255) NOT NULL,
  `registration_no` VARCHAR(100) NULL,
  `gst_no` VARCHAR(50) NULL,
  `address` TEXT NULL,
  `phone` VARCHAR(50) NULL,
  `fax` VARCHAR(50) NULL,
  `email` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Company Seed Record
INSERT INTO `company_profile` (`id`, `company_name`, `registration_no`, `gst_no`, `address`, `phone`, `fax`, `email`)
VALUES
(1, 'Sneat Services Pvt Ltd', 'REG-2026-987654', '27AAACS1234F1Z5', '123 Business Tower, Tech Park Road, Mumbai, Maharashtra 400001', '+91 98765 43210', '022-12345678', 'contact@sneatservices.com')
ON DUPLICATE KEY UPDATE `company_name` = VALUES(`company_name`);
