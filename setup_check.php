<?php
/**
 * SUAS - Setup Verification Script
 * 
 * Run this script to verify your setup is complete and working
 * Access: http://localhost/HLSUAS/setup_check.php
 */

$results = [];
$errors = 0;
$warnings = 0;

function check($name, $condition, $error = null) {
    global $errors, $warnings;
    $status = $condition ? 'pass' : 'fail';
    if (!$condition) {
        $errors++;
    }
    return ['name' => $name, 'status' => $status, 'error' => $error];
}

function warn($name, $condition, $message = null) {
    global $warnings;
    $status = $condition ? 'pass' : 'warn';
    if (!$condition) {
        $warnings++;
    }
    return ['name' => $name, 'status' => $status, 'warning' => $message];
}

// PHP Version Check
$results[] = check(
    "PHP Version >= 8.0",
    version_compare(PHP_VERSION, '8.0.0', '>='),
    "Current version: " . PHP_VERSION
);

// Required Extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $results[] = check("PHP Extension: $ext", $loaded);
}

// PostgreSQL Extension (for Supabase)
$results[] = warn(
    "PHP Extension: pdo_pgsql (Supabase)",
    extension_loaded('pdo_pgsql'),
    "Required for Supabase/PostgreSQL support"
);

// File Checks
$requiredFiles = [
    'config.php',
    'config_master.php',
    'init_db.sql',
    'init_master_db.sql',
    'supabase/master_schema.sql',
    'supabase/institution_schema.sql'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $results[] = check("File exists: $file", $exists);
}

// Directory Checks
$requiredDirs = ['storage', 'storage/logs', 'storage/sessions', 'storage/cache', 'includes'];
foreach ($requiredDirs as $dir) {
    $exists = is_dir(__DIR__ . '/' . $dir);
    $results[] = check("Directory exists: $dir", $exists);
}

// Writability Checks
$writableDirs = ['storage', 'storage/logs', 'storage/sessions', 'storage/cache'];
foreach ($writableDirs as $dir) {
    $writable = is_writable(__DIR__ . '/' . $dir);
    $results[] = check("Directory writable: $dir", $writable);
}

// Environment File Check
$envExists = file_exists(__DIR__ . '/.env');
$results[] = warn(
    "Environment file (.env)",
    $envExists,
    "Copy .env.example to .env and configure"
);

// Config Files Readable
$configReadable = is_readable(__DIR__ . '/config.php');
$results[] = check("config.php readable", $configReadable);

$configMasterReadable = is_readable(__DIR__ . '/config_master.php');
$results[] = check("config_master.php readable", $configMasterReadable);

// Database Connection Tests
if ($envExists) {
    $envLines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $envVars = [];
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
    
    $useSupabase = isset($envVars['USE_SUPABASE']) && $envVars['USE_SUPABASE'] === 'true';
    $results[] = check(
        "Database mode",
        true,
        $useSupabase ? "Supabase (PostgreSQL)" : "MySQL"
    );
    
    if (!$useSupabase) {
        // Test MySQL connection
        $mysqlHost = $envVars['DB_HOST'] ?? '127.0.0.1';
        $mysqlUser = $envVars['DB_USER'] ?? 'root';
        $mysqlPass = $envVars['DB_PASS'] ?? '';
        $mysqlDB = $envVars['MASTER_DB_NAME'] ?? 'hlsuas_master';
        
        $mysqlConn = @new mysqli($mysqlHost, $mysqlUser, $mysqlPass, $mysqlDB);
        $mysqlConnected = !$mysqlConn->connect_error;
        $results[] = check(
            "MySQL Connection",
            $mysqlConnected,
            $mysqlConn->connect_error ?? "Connected successfully"
        );
    } else {
        // Test Supabase connection
        $supaHost = $envVars['SUPABASE_DB_HOST'] ?? '';
        $supaUser = $envVars['SUPABASE_DB_USER'] ?? 'postgres';
        $supaPass = $envVars['SUPABASE_DB_PASS'] ?? '';
        $supaDB = $envVars['SUPABASE_MASTER_DB_NAME'] ?? 'postgres';
        
        if (!empty($supaHost)) {
            try {
                $dsn = "pgsql:host=$supaHost;port=5432;dbname=$supaDB;";
                $pdo = @new PDO($dsn, $supaUser, $supaPass);
                $supaConnected = $pdo !== false;
                $results[] = check(
                    "Supabase Connection",
                    $supaConnected,
                    $supaConnected ? "Connected successfully" : "Connection failed"
                );
            } catch (Exception $e) {
                $results[] = check("Supabase Connection", false, $e->getMessage());
            }
        } else {
            $results[] = warn("Supabase Host", false, "SUPABASE_DB_HOST not configured");
        }
    }
}

// Check if functions exist
$requiredFunctions = ['session_start', 'htmlspecialchars', 'file_get_contents', 'json_decode'];
foreach ($requiredFunctions as $func) {
    $exists = function_exists($func);
    $results[] = check("Function exists: $func()", $exists);
}

// HTTPS Check (for production)
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$results[] = warn(
    "HTTPS Enabled",
    $isHttps || $_SERVER['SERVER_NAME'] === 'localhost',
    "Recommended for production deployments"
);

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SUAS Setup Check</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      min-height: 100vh;
      padding: 40px 20px;
    }
    .container {
      max-width: 900px;
      margin: 0 auto;
    }
    .header {
      background: white;
      border-radius: 20px;
      padding: 40px;
      margin-bottom: 30px;
      text-align: center;
      box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    }
    .header h1 {
      color: #6366f1;
      font-size: 28px;
      margin-bottom: 10px;
    }
    .header p {
      color: #6b7280;
      font-size: 16px;
    }
    .summary {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }
    .summary-card {
      background: white;
      border-radius: 16px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .summary-card.pass { border-top: 4px solid #10b981; }
    .summary-card.warn { border-top: 4px solid #f59e0b; }
    .summary-card.fail { border-top: 4px solid #ef4444; }
    .summary-card .number {
      font-size: 48px;
      font-weight: 700;
      margin-bottom: 5px;
    }
    .summary-card.pass .number { color: #10b981; }
    .summary-card.warn .number { color: #f59e0b; }
    .summary-card.fail .number { color: #ef4444; }
    .summary-card .label {
      color: #6b7280;
      font-size: 14px;
      font-weight: 500;
    }
    .results {
      background: white;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    }
    .result-item {
      display: flex;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #e5e7eb;
    }
    .result-item:last-child { border-bottom: none; }
    .result-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 18px;
    }
    .result-icon.pass { background: #d1fae5; color: #10b981; }
    .result-icon.fail { background: #fee2e2; color: #ef4444; }
    .result-icon.warn { background: #fef3c7; color: #f59e0b; }
    .result-info {
      flex: 1;
    }
    .result-name {
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 3px;
    }
    .result-detail {
      font-size: 13px;
      color: #6b7280;
    }
    .btn {
      display: inline-block;
      padding: 12px 24px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: white;
      text-decoration: none;
      border-radius: 10px;
      font-weight: 600;
      margin-top: 20px;
      transition: transform 0.3s;
    }
    .btn:hover { transform: translateY(-2px); }
    .actions {
      text-align: center;
      margin-top: 30px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="assets/smartlogo.svg" alt="SUAS Logo" style="height: 80px; margin-bottom: 20px;">
      <h1>Setup Verification</h1>
      <p>Checking your SUAS installation for completeness and potential issues</p>
    </div>

    <div class="summary">
      <div class="summary-card pass">
        <div class="number"><?= count(array_filter($results, fn($r) => $r['status'] === 'pass')) ?></div>
        <div class="label">Passed</div>
      </div>
      <div class="summary-card warn">
        <div class="number"><?= $warnings ?></div>
        <div class="label">Warnings</div>
      </div>
      <div class="summary-card fail">
        <div class="number"><?= $errors ?></div>
        <div class="label">Errors</div>
      </div>
    </div>

    <div class="results">
      <h2 style="color: #1f2937; margin-bottom: 20px; font-size: 20px;">
        <i class="fas fa-clipboard-check"></i> Check Results
      </h2>
      
      <?php foreach ($results as $result): ?>
        <div class="result-item">
          <div class="result-icon <?= $result['status'] ?>">
            <?php if ($result['status'] === 'pass'): ?>
              <i class="fas fa-check"></i>
            <?php elseif ($result['status'] === 'fail'): ?>
              <i class="fas fa-times"></i>
            <?php else: ?>
              <i class="fas fa-exclamation"></i>
            <?php endif; ?>
          </div>
          <div class="result-info">
            <div class="result-name"><?= htmlspecialchars($result['name']) ?></div>
            <?php if (isset($result['error'])): ?>
              <div class="result-detail" style="color: #ef4444;"><?= htmlspecialchars($result['error']) ?></div>
            <?php elseif (isset($result['warning'])): ?>
              <div class="result-detail" style="color: #f59e0b;"><?= htmlspecialchars($result['warning']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="actions">
      <?php if ($errors > 0): ?>
        <p style="color: #ef4444; margin-bottom: 15px;">
          <i class="fas fa-exclamation-triangle"></i>
          Please fix the errors above before using SUAS.
        </p>
        <a href="DEPLOYMENT_GUIDE.md" class="btn">View Setup Guide</a>
      <?php elseif ($warnings > 0): ?>
        <p style="color: #f59e0b; margin-bottom: 15px;">
          <i class="fas fa-info-circle"></i>
          Warnings detected. Review and fix if needed.
        </p>
        <a href="index.php" class="btn">Continue to SUAS</a>
      <?php else: ?>
        <p style="color: #10b981; margin-bottom: 15px;">
          <i class="fas fa-check-circle"></i>
          All checks passed! Your setup looks good.
        </p>
        <a href="index.php" class="btn">Continue to SUAS</a>
      <?php endif; ?>
      
      <div style="margin-top: 20px;">
        <a href="setup_check.php" style="color: #6366f1; text-decoration: none; margin: 0 10px;">
          <i class="fas fa-redo"></i> Re-run Check
        </a>
      </div>
    </div>
  </div>
</body>
</html>
