-- SUAS - Smart Unit Attendance System
-- Institution Database Schema
-- One database per institution

-- Departments Table
CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classes Table
CREATE TABLE IF NOT EXISTS classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  department_id INT NOT NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students Table
CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  admission_number VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  class_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Units Table
CREATE TABLE IF NOT EXISTS units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trainers Table
CREATE TABLE IF NOT EXISTS trainers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  department_id INT,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Accounts Table
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class Units Assignment Table
CREATE TABLE IF NOT EXISTS class_units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  unit_id INT NOT NULL,
  trainer_id INT NOT NULL,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
  FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
  UNIQUE KEY unique_assignment (class_id, unit_id, trainer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance Records Table
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  unit_id INT NOT NULL,
  trainer_id INT NOT NULL,
  lesson ENUM('L1','L2','L3','L4') NOT NULL,
  week INT NOT NULL,
  status ENUM('present','absent') NOT NULL,
  attendance_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
  FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERT SEED DATA

-- Insert Departments
INSERT INTO departments (name) VALUES
('Electrical'),
('Mechanical'),
('Civil');

-- Insert Classes
INSERT INTO classes (name, department_id) VALUES
('ELECT-1', 1),
('ELECT-2', 1),
('MECH-1', 2);

-- Insert Students (with default password '123456')
INSERT INTO students (full_name, admission_number, email, password, class_id) VALUES
('Alice Mwangi', 'E001', 'alice@example.com', '123456', 1),
('Brian Otieno', 'E002', 'brian@example.com', '123456', 1),
('Catherine Njoroge', 'E003', 'catherine@example.com', '123456', 2),
('Daniel Kimani', 'M001', 'daniel@example.com', '123456', 3);

-- Insert Units
INSERT INTO units (code, name) VALUES
('EE101', 'Circuit Theory'),
('EE102', 'Digital Systems'),
('ME101', 'Engineering Drawing');

-- Insert Trainers (with plain passwords for legacy compatibility)
INSERT INTO trainers (name, username, password, department_id) VALUES
('John Trainer', 'john', 'john123', 1),
('Mary Trainer', 'mary', 'mary123', 2);

-- Assign Units to Classes with Trainers
INSERT INTO class_units (class_id, unit_id, trainer_id) VALUES
(1, 1, 1),
(1, 2, 1),
(2, 2, 1),
(3, 3, 2);

-- Insert Admin Account (with plain password for legacy compatibility)
INSERT INTO admins (username, password) VALUES
('admin', 'admin123');
