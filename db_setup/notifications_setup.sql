-- Adildata: Notifications System Tables Setup
-- Run this in cPanel phpMyAdmin on your database: adiliqgs_adildata

-- 1. Dashboard Notifications table (in-app bell notifications)
CREATE TABLE IF NOT EXISTS notifications_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    target ENUM('all','specific') DEFAULT 'all',
    target_email VARCHAR(255) NULL,
    created_by VARCHAR(255) NULL,
    is_read_by LONGTEXT NULL DEFAULT '[]',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add missing columns if table already exists (safe to run):
ALTER TABLE notifications_tbl ADD COLUMN IF NOT EXISTS is_read_by LONGTEXT NULL DEFAULT '[]' AFTER created_by;
ALTER TABLE notifications_tbl ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER status;

-- 2. Email Notification logs table
CREATE TABLE IF NOT EXISTS email_notifications_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target ENUM('all','specific') DEFAULT 'all',
    target_email VARCHAR(255) NULL,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    sent_by VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
