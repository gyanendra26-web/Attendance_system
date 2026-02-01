-- Complete Database Schema with Nepali Festivals
-- Run this file in phpMyAdmin or MySQL command line
-- Migration: add start_date and end_date to holidays for multi-day ranges
ALTER TABLE holidays
  ADD COLUMN IF NOT EXISTS start_date DATE NULL,
  ADD COLUMN IF NOT EXISTS end_date DATE NULL;

-- Optional: populate start_date for existing single-day holidays
UPDATE holidays SET start_date = holiday_date WHERE start_date IS NULL;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:45";

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `attendance_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `attendance_system`;

-- Table structure for employees
CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for attendance
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','leave','holiday','half_day') DEFAULT 'absent',
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `late_minutes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for holidays
CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `holiday_date` date NOT NULL,
  `holiday_type` enum('national','festival','weekly') NOT NULL,
  `recurring_year` year(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for leave_requests
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('casual','sick','paid','unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for overtime
CREATE TABLE `overtime` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `overtime_date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `rate_multiplier` decimal(3,2) DEFAULT 1.50,
  `reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for system_settings
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for users (admin)
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','supervisor') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for tables
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_dept` (`department`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`employee_id`,`attendance_date`),
  ADD KEY `idx_date` (`attendance_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `employee_id` (`employee_id`);

ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_holiday` (`holiday_date`,`holiday_name`);

ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

ALTER TABLE `overtime`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_date` (`overtime_date`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

-- AUTO_INCREMENT for tables
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `overtime`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

-- Foreign key constraints
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

ALTER TABLE `overtime`
  ADD CONSTRAINT `overtime_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `overtime_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

-- Insert default admin (password: admin123)
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMye3sCxJY7Y6jYvLqZ9pUQJv6H6z5KQbW2', 'admin');

-- Insert system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('working_hours_start', '09:00:00', 'Default check-in time'),
('working_hours_end', '17:00:00', 'Default check-out time'),
('working_hours_threshold', '6', 'Minimum hours for full day (less = half day)'),
('late_threshold_minutes', '15', 'Late if check-in after this'),
('overtime_rate', '1.5', 'Overtime rate multiplier'),
('auto_attendance', '1', 'Auto-mark attendance (1=enabled, 0=disabled)'),
('casual_leave_days', '15', 'Casual leaves per year per employee'),
('sick_leave_days', '10', 'Sick leaves per year per employee');

-- Insert Nepali festivals and holidays for 2024-2025
INSERT INTO `holidays` (`holiday_name`, `holiday_date`, `holiday_type`, `description`) VALUES
-- National Holidays
('New Year\'s Day', '2024-01-01', 'national', 'International New Year'),
('Martyrs\' Day', '2024-01-16', 'national', 'Martyrs\' Day'),
('Democracy Day', '2024-02-19', 'national', 'National Democracy Day'),
('International Women\'s Day', '2024-03-08', 'national', 'International Women\'s Day'),

-- Festivals
('Maha Shivaratri', '2024-03-08', 'festival', 'Maha Shivaratri'),
('Holi', '2024-03-25', 'festival', 'Festival of Colors'),
('Ram Navami', '2024-04-17', 'festival', 'Ram Navami'),
('Buddha Jayanti', '2024-05-23', 'festival', 'Birthday of Lord Buddha'),
('Krishna Janmashtami', '2024-08-26', 'festival', 'Krishna Janmashtami'),
('Ganesh Chaturthi', '2024-09-07', 'festival', 'Ganesh Chaturthi'),
('Dashain (Ghatasthapana)', '2024-10-10', 'festival', 'Dashain begins'),
('Dashain (Fulpati)', '2024-10-15', 'festival', 'Fulpati'),
('Dashain (Maha Asthami)', '2024-10-16', 'festival', 'Maha Asthami'),
('Dashain (Maha Navami)', '2024-10-17', 'festival', 'Maha Navami'),
('Dashain (Vijaya Dashami)', '2024-10-18', 'festival', 'Vijaya Dashami'),
('Tihar (Kaag Tihar)', '2024-11-01', 'festival', 'Kaag Tihar'),
('Tihar (Kukur Tihar)', '2024-11-02', 'festival', 'Kukur Tihar'),
('Tihar (Laxmi Puja)', '2024-11-03', 'festival', 'Laxmi Puja'),
('Tihar (Gobardhan Puja)', '2024-11-04', 'festival', 'Gobardhan Puja'),
('Tihar (Bhai Tika)', '2024-11-05', 'festival', 'Bhai Tika'),
('Chhath Parva', '2024-11-07', 'festival', 'Chhath Parva'),
('Christmas', '2024-12-25', 'festival', 'Christmas Day'),

-- 2025 Holidays
('New Year\'s Day 2025', '2025-01-01', 'national', 'International New Year'),
('Martyrs\' Day 2025', '2025-01-16', 'national', 'Martyrs\' Day'),
('Maha Shivaratri 2025', '2025-02-26', 'festival', 'Maha Shivaratri'),
('Holi 2025', '2025-03-14', 'festival', 'Festival of Colors'),
('Buddha Jayanti 2025', '2025-05-12', 'festival', 'Birthday of Lord Buddha'),
('Dashain 2025', '2025-09-30', 'festival', 'Dashain begins'),
('Tihar 2025', '2025-11-20', 'festival', 'Tihar begins');

-- Insert sample employees
INSERT INTO `employees` (`employee_id`, `name`, `email`, `phone`, `department`, `position`, `password`) VALUES
('EMP2024001', 'Sudarsan Hamal', 'sudarsan@company.com', '9766235206', 'IT', 'Software Developer', '$2y$10$N9qo8uLOickgx2ZMRZoMye3sCxJY7Y6jYvLqZ9pUQJv6H6z5KQbW2'),
('EMP2024002', 'Gyanendra Gharti Magar', 'gyanendra@company.com', '9742970079', 'Web Development', 'Web Developer', '$2y$10$N9qo8uLOickgx2ZMRZoMye3sCxJY7Y6jYvLqZ9pUQJv6H6z5KQbW2'),
('EMP2024003', 'Raj Kumar Chaudhary', 'raj@company.com', '9812841517', 'Web Development', 'Web Developer', '$2y$10$N9qo8uLOickgx2ZMRZoMye3sCxJY7Y6jYvLqZ9pUQJv6H6z5KQbW2'),
('EMP2024004', 'Nitesh Giri', 'nitesh@company.com', '9762595947', 'Marketing', 'Digital Marketing Executive', '$2y$10$N9qo8uLOickgx2ZMRZoMye3sCxJY7Y6jYvLqZ9pUQJv6H6z5KQbW2'),
('EMP2024005', 'Ganesh Bhandari', 'ganesh@company.com', '9809838642', 'Frontend', 'Senior Frontend Developer/ UI/UX Designer', '$2y$10$N9qo8uLOickgx2ZMRZoMye3sCxJY7Y6jYvLqZ9pUQJv6H6z5KQbW2');

COMMIT;