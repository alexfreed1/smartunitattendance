<?php
// SUAS - Smart Unit Attendance System
// Master Database Configuration
// Supports both MySQL (local) and Supabase PostgreSQL (production)

// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Detect if we should use Supabase or MySQL
$useSupabase = getenv('USE_SUPABASE') === 'true' || 
               getenv('SUPABASE_DB_HOST') !== false ||
               (getenv('DATABASE_URL') && strpos(getenv('DATABASE_URL'), 'postgres') !== false);

$masterConn = null;

if ($useSupabase) {
    // Supabase PostgreSQL Configuration
    require_once __DIR__ . '/includes/supabase_db.php';
    
    try {
        $masterConn = SupabaseDB::getMasterConnection();
    } catch (Exception $e) {
        error_log("Supabase master connection error: " . $e->getMessage());
        // Show setup instructions for Supabase
        ?>
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>SUAS - Supabase Setup Required</title>
            <style>
                body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
                .setup-box { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); max-width: 700px; }
                h1 { color: #6366f1; margin-bottom: 20px; }
                .step { background: #f9fafb; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
                .step h3 { color: #1f2937; margin-bottom: 10px; }
                code { background: #e5e7eb; padding: 2px 8px; border-radius: 4px; font-family: monospace; }
                .btn { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; margin-top: 20px; }
                .warning { background: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px; margin: 15px 0; }
            </style>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
        </head>
        <body>
            <div class="setup-box">
                <h1>🚀 SUAS Supabase Setup Required</h1>
                <p style="color: #6b7280; margin-bottom: 25px;">The Supabase database is not set up yet. Follow these steps:</p>

                <div class="step">
                    <h3>Step 1: Create Supabase Project</h3>
                    <p>Go to <a href="https://supabase.com" target="_blank">supabase.com</a> and create a new project</p>
                </div>

                <div class="step">
                    <h3>Step 2: Get Database Credentials</h3>
                    <p>In your Supabase project dashboard:</p>
                    <ul>
                        <li>Go to Settings → Database</li>
                        <li>Copy the connection string</li>
                        <li>Note the host, user, password, and port</li>
                    </ul>
                </div>

                <div class="step">
                    <h3>Step 3: Configure Environment Variables</h3>
                    <p>Create a <code>.env</code> file in the project root with:</p>
                    <code style="display:block; padding:15px; margin-top:10px; white-space:pre-wrap;">
USE_SUPABASE=true
SUPABASE_DB_HOST=your-project.supabase.co
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=your-password
SUPABASE_MASTER_DB_NAME=postgres
SUPABASE_DB_PORT=5432
                    </code>
                </div>

                <div class="step">
                    <h3>Step 4: Run Master Database Migration</h3>
                    <p>In Supabase SQL Editor, run the SQL from:</p>
                    <code>supabase/master_schema.sql</code>
                </div>

                <div class="warning">
                    <strong>⚠️ Important:</strong> For production deployment on Render, set these environment variables in your Render dashboard instead of using a .env file.
                </div>

                <a href="index.php" class="btn">← Back to Home</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
} else {
    // MySQL Configuration (local development)
    $MASTER_DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
    $MASTER_DB_USER = getenv('DB_USER') ?: 'root';
    $MASTER_DB_PASS = getenv('DB_PASS') ?: '';
    $MASTER_DB_NAME = getenv('MASTER_DB_NAME') ?: 'hlsuas_master';

    $masterConn = new mysqli($MASTER_DB_HOST, $MASTER_DB_USER, $MASTER_DB_PASS, $MASTER_DB_NAME);
    
    if ($masterConn->connect_error) {
        // If database doesn't exist, show setup instructions
        if (strpos($masterConn->connect_error, 'Unknown database') !== false) {
            ?>
            <!doctype html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>SUAS - Setup Required</title>
                <style>
                    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
                    .setup-box { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); max-width: 600px; }
                    h1 { color: #6366f1; margin-bottom: 20px; }
                    .step { background: #f9fafb; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
                    .step h3 { color: #1f2937; margin-bottom: 10px; }
                    code { background: #e5e7eb; padding: 2px 8px; border-radius: 4px; font-family: monospace; }
                    .btn { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; margin-top: 20px; }
                </style>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
            </head>
            <body>
                <div class="setup-box">
                    <h1>🚀 SUAS Setup Required</h1>
                    <p style="color: #6b7280; margin-bottom: 25px;">The master database is not set up yet. Follow these steps:</p>

                    <div class="step">
                        <h3>Step 1: Open phpMyAdmin</h3>
                        <p>Navigate to <code>http://localhost/phpmyadmin</code></p>
                    </div>

                    <div class="step">
                        <h3>Step 2: Import Master Database</h3>
                        <p>Go to SQL tab and import the file:</p>
                        <code>init_master_db.sql</code>
                    </div>

                    <div class="step">
                        <h3>Step 3: Refresh This Page</h3>
                        <p>After importing, refresh this page to continue.</p>
                    </div>

                    <a href="index.php" class="btn">← Back to Home</a>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
        die('Master DB connection error: ' . $masterConn->connect_error);
    }
}

/**
 * Execute a master database query
 */
function master_query($sql, $params = []) {
    global $masterConn, $useSupabase;
    
    if ($useSupabase && $masterConn instanceof PDO) {
        return SupabaseDB::query($masterConn, $sql, $params);
    } else {
        $result = $masterConn->query($sql);
        if ($result === false) {
            return false;
        }
        if ($result === true) {
            return true;
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}

/**
 * Get last inserted ID from master database
 */
function master_last_insert_id($sequenceName = null) {
    global $masterConn, $useSupabase;
    
    if ($useSupabase && $masterConn instanceof PDO) {
        return $masterConn->lastInsertId($sequenceName);
    } else {
        return $masterConn->insert_id;
    }
}

/**
 * Escape string for master database
 */
function master_escape($string) {
    global $masterConn, $useSupabase;
    
    if ($useSupabase && $masterConn instanceof PDO) {
        return $string;
    } else {
        return $masterConn->real_escape_string($string);
    }
}
