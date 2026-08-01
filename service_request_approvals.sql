-- Update script for Service Request Approval Flow & Company Payment Details

USE `service_provider`;

-- 1. Add approval columns to service_requests table if not exist
ALTER TABLE `service_requests`
  ADD COLUMN `is_quotation_approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `request_quotation_no`,
  ADD COLUMN `quotation_approved_at` DATETIME DEFAULT NULL AFTER `is_quotation_approved`,
  ADD COLUMN `is_invoice_approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `request_invoice_no`,
  ADD COLUMN `invoice_approved_at` DATETIME DEFAULT NULL AFTER `is_invoice_approved`;

-- 2. Add payment details and QR code columns to company_profile table if not exist
ALTER TABLE `company_profile`
  ADD COLUMN `upi_id` VARCHAR(100) DEFAULT 'serviceprovider@upi' AFTER `email`,
  ADD COLUMN `bank_account_no` VARCHAR(50) DEFAULT '987654321098' AFTER `upi_id`,
  ADD COLUMN `ifsc_code` VARCHAR(20) DEFAULT 'SBIN0001234' AFTER `bank_account_no`,
  ADD COLUMN `bank_name` VARCHAR(100) DEFAULT 'State Bank of India' AFTER `ifsc_code`,
  ADD COLUMN `account_holder` VARCHAR(150) DEFAULT 'tech-xpert Services' AFTER `bank_name`,
  ADD COLUMN `qr_code_image` VARCHAR(255) DEFAULT NULL AFTER `account_holder`;
