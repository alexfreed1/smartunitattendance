-- SUAS - Smart Unit Attendance System
-- Supabase Institution Database Schema
-- One database per institution

-- Departments Table
CREATE TABLE IF NOT EXISTS departments (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Classes Table
CREATE TABLE IF NOT EXISTS classes (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name VARCHAR(100) NOT NULL,
  department_id UUID NOT NULL REFERENCES departments(id) ON DELETE CASCADE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Students Table
CREATE TABLE IF NOT EXISTS students (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  full_name VARCHAR(200) NOT NULL,
  admission_number VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  class_id UUID NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Units Table
CREATE TABLE IF NOT EXISTS units (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Trainers Table
CREATE TABLE IF NOT EXISTS trainers (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name VARCHAR(200) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  department_id UUID REFERENCES departments(id) ON DELETE SET NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Admin Accounts Table
CREATE TABLE IF NOT EXISTS admins (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Class Units Assignment Table
CREATE TABLE IF NOT EXISTS class_units (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  class_id UUID NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
  unit_id UUID NOT NULL REFERENCES units(id) ON DELETE CASCADE,
  trainer_id UUID NOT NULL REFERENCES trainers(id) ON DELETE CASCADE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT unique_assignment UNIQUE (class_id, unit_id, trainer_id)
);

-- Attendance Records Table
CREATE TABLE IF NOT EXISTS attendance (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  student_id UUID NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  unit_id UUID NOT NULL REFERENCES units(id) ON DELETE CASCADE,
  trainer_id UUID NOT NULL REFERENCES trainers(id) ON DELETE CASCADE,
  lesson VARCHAR(2) NOT NULL CHECK (lesson IN ('L1', 'L2', 'L3', 'L4')),
  week INTEGER NOT NULL,
  status VARCHAR(10) NOT NULL CHECK (status IN ('present', 'absent')),
  attendance_date TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_classes_department ON classes(department_id);
CREATE INDEX IF NOT EXISTS idx_students_class ON students(class_id);
CREATE INDEX IF NOT EXISTS idx_trainers_department ON trainers(department_id);
CREATE INDEX IF NOT EXISTS idx_class_units_class ON class_units(class_id);
CREATE INDEX IF NOT EXISTS idx_class_units_unit ON class_units(unit_id);
CREATE INDEX IF NOT EXISTS idx_class_units_trainer ON class_units(trainer_id);
CREATE INDEX IF NOT EXISTS idx_attendance_student ON attendance(student_id);
CREATE INDEX IF NOT EXISTS idx_attendance_unit ON attendance(unit_id);
CREATE INDEX IF NOT EXISTS idx_attendance_trainer ON attendance(trainer_id);
CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(attendance_date);

-- INSERT SEED DATA

-- Insert Departments
INSERT INTO departments (name) VALUES
('Electrical'),
('Mechanical'),
('Civil')
ON CONFLICT (name) DO NOTHING;

-- Insert Classes (using INSERT ... ON CONFLICT DO NOTHING with id or check existence)
INSERT INTO classes (name, department_id) 
SELECT 'ELECT-1', id FROM departments WHERE name = 'Electrical'
ON CONFLICT DO NOTHING;

INSERT INTO classes (name, department_id) 
SELECT 'ELECT-2', id FROM departments WHERE name = 'Electrical'
ON CONFLICT DO NOTHING;

INSERT INTO classes (name, department_id) 
SELECT 'MECH-1', id FROM departments WHERE name = 'Mechanical'
ON CONFLICT DO NOTHING;

-- Insert Students (with default password '123456')
INSERT INTO students (full_name, admission_number, email, password, class_id)
SELECT 'Alice Mwangi', 'E001', 'alice@example.com', '123456', id FROM classes WHERE name = 'ELECT-1'
ON CONFLICT (admission_number) DO NOTHING;

INSERT INTO students (full_name, admission_number, email, password, class_id)
SELECT 'Brian Otieno', 'E002', 'brian@example.com', '123456', id FROM classes WHERE name = 'ELECT-1'
ON CONFLICT (admission_number) DO NOTHING;

INSERT INTO students (full_name, admission_number, email, password, class_id)
SELECT 'Catherine Njoroge', 'E003', 'catherine@example.com', '123456', id FROM classes WHERE name = 'ELECT-2'
ON CONFLICT (admission_number) DO NOTHING;

INSERT INTO students (full_name, admission_number, email, password, class_id)
SELECT 'Daniel Kimani', 'M001', 'daniel@example.com', '123456', id FROM classes WHERE name = 'MECH-1'
ON CONFLICT (admission_number) DO NOTHING;

-- Insert Units
INSERT INTO units (code, name) VALUES
('EE101', 'Circuit Theory'),
('EE102', 'Digital Systems'),
('ME101', 'Engineering Drawing')
ON CONFLICT (code) DO NOTHING;

-- Insert Trainers (with plain passwords for legacy compatibility)
INSERT INTO trainers (name, username, password, department_id)
SELECT 'John Trainer', 'john', 'john123', id FROM departments WHERE name = 'Electrical'
ON CONFLICT (username) DO NOTHING;

INSERT INTO trainers (name, username, password, department_id)
SELECT 'Mary Trainer', 'mary', 'mary123', id FROM departments WHERE name = 'Mechanical'
ON CONFLICT (username) DO NOTHING;

-- Assign Units to Classes with Trainers
INSERT INTO class_units (class_id, unit_id, trainer_id)
SELECT c.id, u.id, t.id 
FROM classes c, units u, trainers t 
WHERE c.name = 'ELECT-1' AND u.code = 'EE101' AND t.username = 'john'
ON CONFLICT (class_id, unit_id, trainer_id) DO NOTHING;

INSERT INTO class_units (class_id, unit_id, trainer_id)
SELECT c.id, u.id, t.id 
FROM classes c, units u, trainers t 
WHERE c.name = 'ELECT-1' AND u.code = 'EE102' AND t.username = 'john'
ON CONFLICT (class_id, unit_id, trainer_id) DO NOTHING;

INSERT INTO class_units (class_id, unit_id, trainer_id)
SELECT c.id, u.id, t.id 
FROM classes c, units u, trainers t 
WHERE c.name = 'ELECT-2' AND u.code = 'EE102' AND t.username = 'john'
ON CONFLICT (class_id, unit_id, trainer_id) DO NOTHING;

INSERT INTO class_units (class_id, unit_id, trainer_id)
SELECT c.id, u.id, t.id 
FROM classes c, units u, trainers t 
WHERE c.name = 'MECH-1' AND u.code = 'ME101' AND t.username = 'mary'
ON CONFLICT (class_id, unit_id, trainer_id) DO NOTHING;

-- Insert Admin Account (with plain password for legacy compatibility)
INSERT INTO admins (username, password) VALUES
('admin', 'admin123')
ON CONFLICT (username) DO NOTHING;
