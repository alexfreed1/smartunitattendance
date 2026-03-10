@echo off
REM SUAS - Quick Start Script (Windows)
REM This script helps you get started quickly

echo ======================================
echo   SUAS - Quick Start Setup
echo ======================================
echo.

REM Check if PHP is installed
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo X PHP is not installed. Please install PHP 8.0 or higher.
    pause
    exit /b 1
)

echo [OK] PHP is installed
for /f "tokens=*" %%i in ('php -v ^| findstr /C:"PHP"') do set PHPVERSION=%%i
echo     %PHPVERSION%
echo.

REM Check if .env exists
if not exist .env (
    echo [INFO] Creating .env file from .env.example...
    copy .env.example .env
    echo [OK] .env file created. Please edit it with your database credentials.
    echo.
)

REM Create storage directories
echo [INFO] Creating storage directories...
if not exist storage mkdir storage
if not exist storage\logs mkdir storage\logs
if not exist storage\sessions mkdir storage\sessions
if not exist storage\cache mkdir storage\cache
if not exist storage\framework mkdir storage\framework
if not exist storage\framework\views mkdir storage\framework\views
echo [OK] Storage directories created
echo.

echo Select your deployment type:
echo 1. Local Development (MySQL)
echo 2. Production (Supabase)
echo.
set /p choice="Enter choice (1 or 2): "

if "%choice%"=="1" goto LOCAL_SETUP
if "%choice%"=="2" goto PRODUCTION_SETUP

echo Invalid choice
pause
exit /b 1

:LOCAL_SETUP
echo.
echo ======================================
echo   Local Development Setup
echo ======================================
echo.
echo 1. Make sure XAMPP/MySQL is running
echo 2. Open phpMyAdmin: http://localhost/phpmyadmin
echo 3. Import init_master_db.sql
echo 4. Access: http://localhost:8000
echo.
set /p start_server="Start PHP built-in server? (y/n): "

if "%start_server%"=="y" (
    echo.
    echo [OK] Starting PHP server on http://localhost:8000
    echo Press Ctrl+C to stop the server
    echo.
    php -S localhost:8000
) else (
    echo.
    echo You can start the server manually with: php -S localhost:8000
)
goto END

:PRODUCTION_SETUP
echo.
echo ======================================
echo   Production Setup (Supabase)
echo ======================================
echo.
echo 1. Create Supabase project at supabase.com
echo 2. Run supabase/master_schema.sql in SQL Editor
echo 3. Update .env with Supabase credentials
echo 4. Deploy to Render using render.yaml
echo.
echo See DEPLOYMENT_GUIDE.md for detailed instructions
goto END

:END
echo.
echo ======================================
echo   Setup Complete!
echo ======================================
echo.
pause
