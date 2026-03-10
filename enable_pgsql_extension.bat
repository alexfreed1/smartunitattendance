@echo off
REM SUAS - Enable PostgreSQL Extension in XAMPP
REM Run this script as Administrator

echo ======================================
echo   SUAS - Enable PostgreSQL Driver
echo ======================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [!] Please run this script as Administrator
    echo Right-click on this file and select "Run as administrator"
    pause
    exit /b 1
)

echo [OK] Running as Administrator
echo.

REM Find XAMPP installation
set XAMPP_PATH=C:\xampp
if not exist "%XAMPP_PATH%\php\php.ini" (
    echo [!] XAMPP not found at C:\xampp
    echo Please update XAMPP_PATH variable
    pause
    exit /b 1
)

echo [OK] XAMPP found at %XAMPP_PATH%
echo.

REM Backup php.ini
echo [INFO] Backing up php.ini...
copy "%XAMPP_PATH%\php\php.ini" "%XAMPP_PATH%\php\php.ini.backup" >nul
if %errorLevel% neq 0 (
    echo [!] Failed to backup php.ini
    pause
    exit /b 1
)
echo [OK] Backup created: php.ini.backup
echo.

REM Enable pdo_pgsql
echo [INFO] Enabling pdo_pgsql extension...
findstr /N "^;extension=pdo_pgsql" "%XAMPP_PATH%\php\php.ini" >nul
if %errorLevel% equ 0 (
    powershell -Command "(Get-Content '%XAMPP_PATH%\php\php.ini') -replace ';extension=pdo_pgsql', 'extension=pdo_pgsql' | Set-Content '%XAMPP_PATH%\php\php.ini'"
    echo [OK] pdo_pgsql enabled
) else (
    echo [INFO] pdo_pgsql might already be enabled
)

REM Enable pgsql
echo [INFO] Enabling pgsql extension...
findstr /N "^;extension=pgsql" "%XAMPP_PATH%\php\php.ini" >nul
if %errorLevel% equ 0 (
    powershell -Command "(Get-Content '%XAMPP_PATH%\php\php.ini') -replace ';extension=pgsql', 'extension=pgsql' | Set-Content '%XAMPP_PATH%\php\php.ini'"
    echo [OK] pgsql enabled
) else (
    echo [INFO] pgsql might already be enabled
)

echo.
echo ======================================
echo   Configuration Complete!
echo ======================================
echo.
echo [OK] Extensions enabled in php.ini
echo.
echo NEXT STEPS:
echo 1. Restart Apache in XAMPP Control Panel
echo    - Click Stop on Apache
echo    - Click Start on Apache
echo.
echo 2. Verify extensions are loaded
echo    - Open: http://localhost/HLSUAS/check_extensions.php
echo.
echo 3. Test Supabase connection
echo    - Open: http://localhost/HLSUAS/test_connection.php
echo.
pause
