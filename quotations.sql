-- Quotations Schema for Tech-xpert Admin Panel
USE `service_provider`;

CREATE TABLE IF NOT EXISTS `quotations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quotation_number` VARCHAR(50) NOT NULL UNIQUE,
  `service_request_id` VARCHAR(50) NOT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_mobile` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(255) NULL,
  `service_name` VARCHAR(255) NOT NULL,
  `current_version` INT NOT NULL DEFAULT 1,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('draft', 'sent', 'accepted', 'rejected', 'revised') DEFAULT 'sent',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_req_id` (`service_request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quotation_versions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quotation_id` INT NOT NULL,
  `version_number` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `revision_notes` TEXT NULL,
  `created_by` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`quotation_id`) REFERENCES `quotations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `quotation_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `version_id` INT NOT NULL,
  `item_description` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`version_id`) REFERENCES `quotation_versions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Sample Quotation for REQ-1001 with 2 Versions
INSERT INTO `quotations` (`id`, `quotation_number`, `service_request_id`, `customer_name`, `customer_mobile`, `customer_email`, `service_name`, `current_version`, `total_amount`, `status`)
VALUES
(1, 'QUO-2026-001', 'REQ-1001', 'Rahul Sharma', '9876543210', 'rahul@example.com', 'AC Maintenance & Deep Cleaning', 2, 2950.00, 'revised')
ON DUPLICATE KEY UPDATE `current_version` = 2;

-- Version 1
INSERT INTO `quotation_versions` (`id`, `quotation_id`, `version_number`, `subtotal`, `discount`, `tax`, `total_amount`, `revision_notes`, `created_by`)
VALUES
(1, 1, 1, 2000.00, 0.00, 360.00, 2360.00, 'Initial quotation created for AC servicing', 'Admin')
ON DUPLICATE KEY UPDATE `version_number` = 1;

INSERT INTO `quotation_items` (`version_id`, `item_description`, `quantity`, `unit_price`, `total_price`)
VALUES
(1, 'Split AC Cleaning & Filter Wash', 2, 1000.00, 2000.00);

-- Version 2 (Revised after customer requested gas refilling)
INSERT INTO `quotation_versions` (`id`, `quotation_id`, `version_number`, `subtotal`, `discount`, `tax`, `total_amount`, `revision_notes`, `created_by`)
VALUES
(2, 1, 2, 2500.00, 0.00, 450.00, 2950.00, 'Added R-32 Gas Refilling per customer request', 'Admin')
ON DUPLICATE KEY UPDATE `version_number` = 2;

INSERT INTO `quotation_items` (`version_id`, `item_description`, `quantity`, `unit_price`, `total_price`)
VALUES
(2, 'Split AC Cleaning & Filter Wash', 2, 1000.00, 2000.00),
(2, 'R-32 Gas Top-Up', 1, 500.00, 500.00);
