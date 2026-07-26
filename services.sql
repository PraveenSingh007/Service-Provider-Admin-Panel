-- Database: `service_provider`
-- SQL Schema Script for `services` Table

CREATE DATABASE IF NOT EXISTS `service_provider` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `service_provider`;

-- Table structure for table `services`
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `service_name` VARCHAR(255) NOT NULL,
  `service_image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Initial Data
INSERT INTO `services` (`service_name`, `service_image`) VALUES
('Home Deep Cleaning', NULL),
('AC Installation & Repair', NULL),
('Electrical & Plumbing Maintenance', NULL);
