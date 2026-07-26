-- Database: `service_provider`
-- SQL Schema Script for Daily Expenses Module

USE `service_provider`;

CREATE TABLE IF NOT EXISTS `daily_expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `expense_type` VARCHAR(100) NOT NULL,
  `employee_id` INT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `expense_date` DATE NOT NULL,
  `notes` TEXT NULL,
  `created_by` VARCHAR(100) DEFAULT 'Admin',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_exp_date` (`expense_date`),
  INDEX `idx_exp_type` (`expense_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Seed Data
INSERT INTO `daily_expenses` (`id`, `expense_type`, `amount`, `expense_date`, `notes`, `created_by`)
VALUES
(1, 'Travel & Fuel', 450.00, '2026-07-26', 'Petrol for site inspection visits', 'Admin'),
(2, 'Office Supplies', 1200.00, '2026-07-26', 'Printer paper rims and stationery items', 'Admin')
ON DUPLICATE KEY UPDATE `amount` = VALUES(`amount`);
