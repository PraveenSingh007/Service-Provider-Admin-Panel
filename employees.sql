-- Database: `service_provider`
-- SQL Schema Script for Employees, Attendance, and Salary Generation

CREATE DATABASE IF NOT EXISTS `service_provider` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `service_provider`;

-- 1. Table: `employees`
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `emp_code` VARCHAR(50) NOT NULL,
  `emp_name` VARCHAR(255) NOT NULL,
  `emp_email` VARCHAR(255) NOT NULL,
  `emp_mobile` VARCHAR(20) NOT NULL,
  `emp_address` TEXT NOT NULL,
  `emp_role` VARCHAR(100) NOT NULL,
  `emp_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `emp_photo` VARCHAR(255) NULL,
  `joining_date` DATE NOT NULL,
  `status` ENUM('active', 'inactive', 'terminated') NOT NULL DEFAULT 'active',
  `status_change_date` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_code` (`emp_code`),
  UNIQUE KEY `uk_emp_email` (`emp_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: `employee_attendance`
CREATE TABLE IF NOT EXISTS `employee_attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('present', 'absent', 'half_day', 'leave') NOT NULL DEFAULT 'present',
  `check_in_time` TIME NULL,
  `check_out_time` TIME NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_attn_date` (`employee_id`, `attendance_date`),
  CONSTRAINT `fk_attn_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: `employee_salaries`
CREATE TABLE IF NOT EXISTS `employee_salaries` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `salary_month` VARCHAR(7) NOT NULL,
  `base_salary` DECIMAL(10,2) NOT NULL,
  `total_days` INT(11) NOT NULL DEFAULT 30,
  `present_days` INT(11) NOT NULL DEFAULT 0,
  `absent_days` INT(11) NOT NULL DEFAULT 0,
  `half_days` INT(11) NOT NULL DEFAULT 0,
  `leave_days` INT(11) NOT NULL DEFAULT 0,
  `calculated_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `bonus` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `deductions` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
  `payment_date` DATE NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_emp_salary_month` (`employee_id`, `salary_month`),
  CONSTRAINT `fk_salary_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Initial Employees
INSERT INTO `employees` (`emp_code`, `emp_name`, `emp_email`, `emp_mobile`, `emp_address`, `emp_role`, `emp_salary`, `joining_date`, `status`) VALUES
('EMP-1001', 'Rahul Sharma', 'rahul.sharma@example.com', '9876543210', 'Devendra Nagar, Raipur, Chhattisgarh', 'Senior Technician', 25000.00, '2025-01-15', 'active'),
('EMP-1002', 'Priya Verma', 'priya.verma@example.com', '9876543211', 'Shankar Nagar, Raipur, Chhattisgarh', 'Service Supervisor', 32000.00, '2024-06-10', 'active'),
('EMP-1003', 'Amit Kumar', 'amit.kumar@example.com', '9876543212', 'Pandri, Raipur, Chhattisgarh', 'Field Specialist', 22000.00, '2025-03-01', 'active');
