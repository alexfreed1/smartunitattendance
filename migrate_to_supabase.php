<?php
/**
 * SUAS - Supabase Database Migration Script
 * 
 * This script helps migrate data from MySQL to Supabase PostgreSQL
 * 
 * Usage:
 * 1. Configure MySQL connection in config.local.php
 * 2. Configure Supabase connection in .env
 * 3. Run: php migrate_to_supabase.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_master.php';

// Migration configuration
$mysqlConfig = [
    'host' => '127.0.0.1',
    'user' => 'root',
    'pass' => '',
    'database' => 'hlsuas_master' // Change to your MySQL database name
];

// Colors for CLI output
function colorize($text, $status) {
    $colors = [
        'success' => "\033[32m", // Green
        'error' => "\033[31m",   // Red
        'warning' => "\033[33m", // Yellow
        'info' => "\033[36m",    // Cyan
        'reset' => "\033[0m"
    ];
    return $colors[$status] . $text . $colors['reset'];
}

function logMessage($message, $status = 'info') {
    echo colorize("[$status] $message\n", $status);
}

// Connect to MySQL
logMessage("Connecting to MySQL...", 'info');
try {
    $mysqlConn = new mysqli(
        $mysqlConfig['host'],
        $mysqlConfig['user'],
        $mysqlConfig['pass'],
        $mysqlConfig['database']
    );
    
    if ($mysqlConn->connect_error) {
        throw new Exception("MySQL connection failed: " . $mysqlConn->connect_error);
    }
    logMessage("Connected to MySQL successfully", 'success');
} catch (Exception $e) {
    logMessage($e->getMessage(), 'error');
    exit(1);
}

// Connect to Supabase
logMessage("Connecting to Supabase...", 'info');
try {
    $supabaseConn = SupabaseDB::getMasterConnection();
    logMessage("Connected to Supabase successfully", 'success');
} catch (Exception $e) {
    logMessage($e->getMessage(), 'error');
    exit(1);
}

// Migration functions
function migrateTable($mysqlConn, $supabaseConn, $table, $transformFn = null) {
    global $argv;
    
    logMessage("Migrating table: $table", 'info');
    
    $result = $mysqlConn->query("SELECT * FROM $table");
    if (!$result) {
        logMessage("Failed to query MySQL table: $table", 'error');
        return false;
    }
    
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        try {
            // Apply transformation if provided
            if ($transformFn && is_callable($transformFn)) {
                $row = $transformFn($row);
            }
            
            // Build INSERT query for PostgreSQL
            $columns = array_keys($row);
            $values = array_values($row);
            $placeholders = array_fill(0, count($values), '?');
            
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            SupabaseDB::execute($supabaseConn, $sql, $values);
            $count++;
        } catch (Exception $e) {
            logMessage("Error inserting row into $table: " . $e->getMessage(), 'error');
        }
    }
    
    logMessage("Migrated $count rows from $table", 'success');
    return true;
}

// Start migration
logMessage("Starting migration...", 'info');

// Migrate super_admins
migrateTable($mysqlConn, $supabaseConn, 'super_admins');

// Migrate institutions
migrateTable($mysqlConn, $supabaseConn, 'institutions', function($row) {
    // Convert active from INT to BOOLEAN
    $row['active'] = (bool)$row['active'];
    return $row;
});

logMessage("Migration completed successfully!", 'success');

// Close connections
$mysqlConn->close();

echo "\n";
logMessage("Next steps:", 'warning');
echo "1. Verify data in Supabase dashboard\n";
echo "2. Update .env file with USE_SUPABASE=true\n";
echo "3. Test the application\n";
echo "4. Deploy to Render\n";
