#!/bin/bash

# SUAS - Quick Start Script
# This script helps you get started quickly

echo "======================================"
echo "  SUAS - Quick Start Setup"
echo "======================================"
echo ""

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 8.0 or higher."
    exit 1
fi

echo "✅ PHP version: $(php -v | head -n 1)"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created. Please edit it with your database credentials."
    echo ""
fi

# Create storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/logs storage/sessions storage/cache storage/framework/views
chmod -R 777 storage/
echo "✅ Storage directories created"
echo ""

# Check if running in development or production
echo "Select your deployment type:"
echo "1. Local Development (MySQL)"
echo "2. Production (Supabase)"
echo ""
read -p "Enter choice (1 or 2): " choice

if [ "$choice" = "1" ]; then
    echo ""
    echo "📋 Local Development Setup:"
    echo "1. Make sure XAMPP/MySQL is running"
    echo "2. Open phpMyAdmin: http://localhost/phpmyadmin"
    echo "3. Import init_master_db.sql"
    echo "4. Access: http://localhost:8000"
    echo ""
    read -p "Start PHP built-in server? (y/n): " start_server
    
    if [ "$start_server" = "y" ]; then
        echo "🚀 Starting PHP server on http://localhost:8000"
        php -S localhost:8000
    fi
else
    echo ""
    echo "📋 Production Setup (Supabase):"
    echo "1. Create Supabase project at supabase.com"
    echo "2. Run supabase/master_schema.sql in SQL Editor"
    echo "3. Update .env with Supabase credentials"
    echo "4. Deploy to Render using render.yaml"
    echo ""
    echo "📖 See DEPLOYMENT_GUIDE.md for detailed instructions"
fi

echo ""
echo "======================================"
echo "  Setup Complete!"
echo "======================================"
