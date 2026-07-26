-- Invoices Schema for Service Provider Admin Panel
USE `service_provider`;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `quotation_id` INT NULL,
  `service_request_id` VARCHAR(50) NOT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_mobile` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(255) NULL,
  `service_name` VARCHAR(255) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('unpaid', 'partially_paid', 'paid') DEFAULT 'unpaid',
  `payment_method` VARCHAR(50) DEFAULT 'Cash',
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_uniq_service_req` (`service_request_id`),
  FOREIGN KEY (`quotation_id`) REFERENCES `quotations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `item_description` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Sample Invoice generated from Quotation #1 (REQ-1001)
INSERT INTO `invoices` (`id`, `invoice_number`, `quotation_id`, `service_request_id`, `customer_name`, `customer_mobile`, `customer_email`, `service_name`, `subtotal`, `discount`, `tax`, `total_amount`, `payment_status`, `payment_method`, `invoice_date`, `due_date`, `notes`)
VALUES
(1, 'INV-2026-001', 1, 'REQ-1001', 'Rahul Sharma', '9876543210', 'rahul@example.com', 'AC Maintenance & Deep Cleaning', 2500.00, 0.00, 450.00, 2950.00, 'paid', 'UPI', '2026-07-26', '2026-08-02', 'Tax Invoice generated from Quotation QUO-2026-001 (Version 2)')
ON DUPLICATE KEY UPDATE `payment_status` = 'paid';

INSERT INTO `invoice_items` (`invoice_id`, `item_description`, `quantity`, `unit_price`, `total_price`)
VALUES
(1, 'Split AC Cleaning & Filter Wash', 2, 1000.00, 2000.00),
(1, 'R-32 Gas Top-Up', 1, 500.00, 500.00);
