-- Database: `service_provider`
-- SQL Schema Script for `service_requests` Table

USE `service_provider`;

CREATE TABLE IF NOT EXISTS `service_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `service_request_no` VARCHAR(50) NOT NULL UNIQUE,
  
  -- Customer Details
  `customer_name` VARCHAR(150) NOT NULL,
  `request_by_mobile_no` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(100) DEFAULT NULL,
  
  -- Service Classification (CCTV, Computer Hardware, AMC)
  `service_id` INT(11) DEFAULT NULL,
  `service_name` VARCHAR(255) NOT NULL,
  `service_category` ENUM('cctv_camera', 'computer_hardware', 'amc_contract', 'other') NOT NULL DEFAULT 'other',
  `request_type` ENUM('fresh_installation', 'repair_service', 'hardware_purchase', 'amc_new_booking', 'amc_periodic_service', 'callback_request') NOT NULL DEFAULT 'repair_service',
  `description` TEXT DEFAULT NULL,
  `device_details` TEXT DEFAULT NULL,
  
  -- Service Location Details (Pincode strictly references service_areas)
  `request_address` TEXT NOT NULL,
  `request_city` VARCHAR(100) NOT NULL DEFAULT 'Raipur',
  `request_state` VARCHAR(100) NOT NULL DEFAULT 'Chhattisgarh',
  `request_pincode` VARCHAR(20) NOT NULL,
  `landmark` VARCHAR(150) DEFAULT NULL,
  
  -- Schedule & Visit Preferences
  `request_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `preferred_visit_date` DATE DEFAULT NULL,
  `preferred_time_slot` ENUM('morning', 'afternoon', 'evening', 'anytime') DEFAULT 'anytime',
  `site_inspection_required` TINYINT(1) DEFAULT 0,
  
  -- Priority & Status Management
  `priority` ENUM('low', 'medium', 'high', 'emergency') DEFAULT 'medium',
  `request_status` ENUM('pending', 'assigned', 'in_progress', 'quotation_sent', 'invoice_generated', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `request_status_notes` TEXT DEFAULT NULL,
  
  -- Assignment & Accounting Links
  `assign_to` VARCHAR(150) DEFAULT NULL,
  `assigned_employee_id` INT(11) DEFAULT NULL,
  `amc_contract_number` VARCHAR(50) DEFAULT NULL,
  `request_quotation_no` VARCHAR(50) DEFAULT NULL,
  `request_invoice_no` VARCHAR(50) DEFAULT NULL,
  
  -- Audit Timestamps
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  INDEX `idx_request_no` (`service_request_no`),
  INDEX `idx_mobile` (`request_by_mobile_no`),
  INDEX `idx_status` (`request_status`),
  INDEX `idx_pincode` (`request_pincode`),
  INDEX `idx_service_category` (`service_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean existing sample data to re-seed with valid pincodes matching service_areas
TRUNCATE TABLE `service_requests`;

-- Sample Seed Data (Pincodes present in service_areas: 492001, 492006)
INSERT INTO `service_requests` (
  `service_request_no`, `customer_name`, `request_by_mobile_no`, `customer_email`, 
  `service_name`, `service_category`, `request_type`, `description`, `device_details`, 
  `request_address`, `request_city`, `request_state`, `request_pincode`, 
  `preferred_visit_date`, `preferred_time_slot`, `site_inspection_required`, 
  `priority`, `request_status`, `assign_to`, `assigned_employee_id`, `request_quotation_no`
) VALUES 
(
  'REQ-2026-001', 'Amit Verma', '9876543210', 'amit.verma@example.com',
  'CCTV Camera Fresh Installation', 'cctv_camera', 'fresh_installation',
  'Need 8 IP Cameras installation for 2-floor commercial office space with DVR setup.', '8 CP-Plus 5MP IP Cameras, 8-Ch NVR, 2TB HDD',
  'Plot 42, Civil Lines Main Road', 'Raipur', 'Chhattisgarh', '492001',
  '2026-08-05', 'morning', 1,
  'high', 'quotation_sent', 'Rajesh Kumar', 1, 'QUO-2026-001'
),
(
  'REQ-2026-002', 'Priya Sharma', '9123456789', 'priya.s@example.com',
  'Computer Hardware AMC Service', 'amc_contract', 'amc_periodic_service',
  'Quarterly maintenance visit for office desktops and server maintenance under active AMC.', '15 Dell Desktops & 1 HP Server',
  'Suite 301, Telibandha Business Center', 'Raipur', 'Chhattisgarh', '492006',
  '2026-08-02', 'afternoon', 0,
  'medium', 'assigned', 'Suresh Patel', 2, NULL
);
