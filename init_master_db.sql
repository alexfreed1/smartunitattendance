-- SUAS - Smart Unit Attendance System
-- Master Database
-- Stores super admin and registered institutions

DROP DATABASE IF EXISTS hlsuas_master;
CREATE DATABASE hlsuas_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hlsuas_master;

-- Super Admins (system-level administrators)
CREATE TABLE super_admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registered Institutions
CREATE TABLE institutions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(50) NOT NULL UNIQUE,
  db_name VARCHAR(100) NOT NULL UNIQUE,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Super Admin (username: superadmin, password: super123)
INSERT INTO super_admins (username, password) VALUES ('superadmin', 'super123');
