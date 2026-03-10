<?php
/**
 * SUAS - Smart Unit Attendance System
 * Supabase Database Connection Handler
 * 
 * This file provides database connection functionality for Supabase PostgreSQL
 */

class SupabaseDB {
    private static $masterConnection = null;
    private static $institutionConnection = null;
    
    /**
     * Get master database connection
     */
    public static function getMasterConnection() {
        if (self::$masterConnection === null) {
            self::$masterConnection = self::createConnection(
                getenv('SUPABASE_DB_HOST') ?: getenv('DB_HOST') ?: 'localhost',
                getenv('SUPABASE_DB_USER') ?: getenv('DB_USER') ?: 'postgres',
                getenv('SUPABASE_DB_PASS') ?: getenv('DB_PASS') ?: '',
                getenv('SUPABASE_MASTER_DB_NAME') ?: getenv('MASTER_DB_NAME') ?: 'hlsuas_master',
                getenv('SUPABASE_DB_PORT') ?: getenv('DB_PORT') ?: '5432'
            );
        }
        return self::$masterConnection;
    }
    
    /**
     * Get institution database connection
     */
    public static function getInstitutionConnection($dbName = null) {
        if (self::$institutionConnection === null && $dbName !== null) {
            self::$institutionConnection = self::createConnection(
                getenv('SUPABASE_DB_HOST') ?: getenv('DB_HOST') ?: 'localhost',
                getenv('SUPABASE_DB_USER') ?: getenv('DB_USER') ?: 'postgres',
                getenv('SUPABASE_DB_PASS') ?: getenv('DB_PASS') ?: '',
                $dbName,
                getenv('SUPABASE_DB_PORT') ?: getenv('DB_PORT') ?: '5432'
            );
        }
        return self::$institutionConnection;
    }
    
    /**
     * Create a new database connection
     */
    private static function createConnection($host, $user, $pass, $dbName, $port = '5432') {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbName;";
        
        try {
            $conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $conn;
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Close all connections
     */
    public static function closeConnections() {
        self::$masterConnection = null;
        self::$institutionConnection = null;
    }
    
    /**
     * Check if connection is PostgreSQL
     */
    public static function isPostgreSQL($conn) {
        return $conn instanceof PDO;
    }
    
    /**
     * Escape string for PostgreSQL
     */
    public static function escape($conn, $string) {
        if ($conn instanceof PDO) {
            // PDO prepared statements handle escaping automatically
            return $string;
        }
        return $string;
    }
    
    /**
     * Execute a query and return results
     */
    public static function query($conn, $sql, $params = []) {
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute a query without returning results
     */
    public static function execute($conn, $sql, $params = []) {
        try {
            $stmt = $conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Execute error: " . $e->getMessage());
            throw new Exception("Execute failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get last inserted ID
     */
    public static function getLastInsertId($conn, $sequenceName = null) {
        if ($conn instanceof PDO) {
            return $conn->lastInsertId($sequenceName);
        }
        return null;
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction($conn) {
        if ($conn instanceof PDO) {
            return $conn->beginTransaction();
        }
        return false;
    }
    
    /**
     * Commit transaction
     */
    public static function commit($conn) {
        if ($conn instanceof PDO) {
            return $conn->commit();
        }
        return false;
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback($conn) {
        if ($conn instanceof PDO) {
            return $conn->rollBack();
        }
        return false;
    }
}
