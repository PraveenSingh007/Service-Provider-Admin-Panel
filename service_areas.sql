-- Database: `service_provider`
-- SQL Schema & Initial Data Script for Service Areas in Raipur, Chhattisgarh, India

CREATE DATABASE IF NOT EXISTS `service_provider` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `service_provider`;

-- Table structure for table `service_areas`
CREATE TABLE IF NOT EXISTS `service_areas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `area_name` VARCHAR(255) NOT NULL,
  `pincode` VARCHAR(20) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Refresh service_areas table with Raipur, Chhattisgarh records
TRUNCATE TABLE `service_areas`;

-- Insert Service Areas for Raipur, Chhattisgarh, India
INSERT INTO `service_areas` (`area_name`, `pincode`, `city`, `state`) VALUES
('Civil Lines', '492001', 'Raipur', 'Chhattisgarh'),
('Sadar Bazar', '492001', 'Raipur', 'Chhattisgarh'),
('Samta Colony', '492001', 'Raipur', 'Chhattisgarh'),
('GE Road / GPO', '492001', 'Raipur', 'Chhattisgarh'),
('Bhanpuri', '492003', 'Raipur', 'Chhattisgarh'),
('Urla Industrial Area', '492003', 'Raipur', 'Chhattisgarh'),
('Pandri', '492004', 'Raipur', 'Chhattisgarh'),
('Devendra Nagar', '492004', 'Raipur', 'Chhattisgarh'),
('Kapa', '492005', 'Raipur', 'Chhattisgarh'),
('Telibandha', '492006', 'Raipur', 'Chhattisgarh'),
('New Rajendra Nagar', '492006', 'Raipur', 'Chhattisgarh'),
('Shankar Nagar', '492007', 'Raipur', 'Chhattisgarh'),
('Kachna', '492007', 'Raipur', 'Chhattisgarh'),
('Gudhiyari', '492009', 'Raipur', 'Chhattisgarh'),
('Amanaka', '492010', 'Raipur', 'Chhattisgarh'),
('Labhandi', '492012', 'Raipur', 'Chhattisgarh'),
('Phundhar', '492012', 'Raipur', 'Chhattisgarh'),
('Dharampura', '492013', 'Raipur', 'Chhattisgarh'),
('Changora Bhata', '492013', 'Raipur', 'Chhattisgarh'),
('Santoshi Nagar', '492015', 'Raipur', 'Chhattisgarh'),
('VIP Road', '492015', 'Raipur', 'Chhattisgarh'),
('Mana Camp / Airport Area', '492015', 'Raipur', 'Chhattisgarh'),
('Boriyakala', '492015', 'Raipur', 'Chhattisgarh'),
('Nava Raipur (Atal Nagar)', '492018', 'Raipur', 'Chhattisgarh'),
('Tatibandh', '492099', 'Raipur', 'Chhattisgarh'),
('Hirapur', '492099', 'Raipur', 'Chhattisgarh'),
('Birgaon', '493221', 'Raipur', 'Chhattisgarh'),
('Rawabhata', '493221', 'Raipur', 'Chhattisgarh');
