-- SUAS - Smart Unit Attendance System
-- Supabase Master Database Schema
-- Stores super admin and registered institutions

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Super Admins (system-level administrators)
CREATE TABLE IF NOT EXISTS super_admins (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Registered Institutions
CREATE TABLE IF NOT EXISTS institutions (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  name VARCHAR(255) NOT NULL,
  code VARCHAR(50) NOT NULL UNIQUE,
  db_name VARCHAR(100) NOT NULL UNIQUE,
  active BOOLEAN DEFAULT true,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Default Super Admin (username: superadmin, password: super123)
-- Note: In production, use password_hash() for secure password storage
INSERT INTO super_admins (username, password) VALUES 
('superadmin', 'super123')
ON CONFLICT (username) DO NOTHING;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_institutions_code ON institutions(code);
CREATE INDEX IF NOT EXISTS idx_institutions_active ON institutions(active);
CREATE INDEX IF NOT EXISTS idx_super_admins_username ON super_admins(username);

-- Row Level Security (RLS) - Optional, enable if needed
-- ALTER TABLE super_admins ENABLE ROW LEVEL SECURITY;
-- ALTER TABLE institutions ENABLE ROW LEVEL SECURITY;
