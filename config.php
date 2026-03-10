<?php
// SUAS - Smart Unit Attendance System
// Institution Database Configuration
// Supports both MySQL (local) and Supabase PostgreSQL (production)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$conn = null;
$DB_NAME = '';

if ($useSupabase) {
    // Supabase PostgreSQL Configuration
    require_once __DIR__ . '/includes/supabase_db.php';
    
    if (!empty($_SESSION['institution_db'])) {
        $DB_NAME = $_SESSION['institution_db'];
        try {
            $conn = SupabaseDB::getInstitutionConnection($DB_NAME);
        } catch (Exception $e) {
            error_log("Supabase connection error: " . $e->getMessage());
            die('Database connection error. Please check your configuration.');
        }
    }
} else {
    // MySQL Configuration (local development)
    $DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
    $DB_USER = getenv('DB_USER') ?: 'root';
    $DB_PASS = getenv('DB_PASS') ?: '';
    
    if (!empty($_SESSION['institution_db'])) {
        $DB_NAME = $_SESSION['institution_db'];
        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
        if ($conn->connect_error) {
            die('Database connection error: ' . $conn->connect_error);
        }
    }
}

/**
 * Escape HTML output
 */
function h($s){
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Get database connection type
 */
function get_db_type() {
    global $useSupabase;
    return $useSupabase ? 'postgresql' : 'mysql';
}

/**
 * Execute a database query (compatible with both MySQL and PostgreSQL)
 */
function db_query($sql, $params = []) {
    global $conn, $useSupabase;
    
    if ($useSupabase && $conn instanceof PDO) {
        return SupabaseDB::query($conn, $sql, $params);
    } else {
        $result = $conn->query($sql);
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
 * Execute a prepared statement (compatible with both MySQL and PostgreSQL)
 */
function db_prepare($sql, $types = '', $params = []) {
    global $conn, $useSupabase;
    
    if ($useSupabase && $conn instanceof PDO) {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }
        return $stmt;
    }
}

/**
 * Get last inserted ID
 */
function db_last_insert_id($sequenceName = null) {
    global $conn, $useSupabase;
    
    if ($useSupabase && $conn instanceof PDO) {
        return $conn->lastInsertId($sequenceName);
    } else {
        return $conn->insert_id;
    }
}

/**
 * Escape string for database
 */
function db_escape($string) {
    global $conn, $useSupabase;
    
    if ($useSupabase && $conn instanceof PDO) {
        return $string; // PDO handles escaping in prepared statements
    } else {
        return $conn->real_escape_string($string);
    }
}
