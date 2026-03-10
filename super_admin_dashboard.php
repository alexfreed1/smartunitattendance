<?php
require 'config_master.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['super_admin_id'])) {
    header('Location: super_admin_login.php');
    exit;
}

$message = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle toggle active status
if (isset($_GET['toggle_id']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $toggle_id = (int)$_GET['toggle_id'];
    master_query("UPDATE institutions SET active = NOT active WHERE id = ?", [$toggle_id]);
    header('Location: super_admin_dashboard.php');
    exit;
}

// Handle delete institution
if (isset($_GET['delete_id']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $delete_id = (int)$_GET['delete_id'];
    $inst = master_query("SELECT db_name FROM institutions WHERE id = ?", [$delete_id]);
    if ($inst && count($inst) > 0) {
        $dbName = $inst[0]['db_name'];
        
        if ($useSupabase) {
            // For Supabase, drop the schema instead of database
            try {
                SupabaseDB::execute($masterConn, "DROP SCHEMA IF EXISTS $dbName CASCADE");
            } catch (Exception $e) {
                error_log("Error dropping schema: " . $e->getMessage());
            }
        } else {
            // For MySQL, drop the database
            $rootConn = new mysqli('127.0.0.1', 'root', '');
            if ($rootConn->connect_error === null) {
                $rootConn->query("DROP DATABASE `$dbName`");
            }
        }
        master_query("DELETE FROM institutions WHERE id = ?", [$delete_id]);
    }
    header('Location: super_admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));

    if ($name !== '' && $code !== '') {
        $schemaName = 'suas_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $code));

        if ($useSupabase) {
            // Supabase: Create schema and tables within the same database
            try {
                // Create schema
                SupabaseDB::execute($masterConn, "CREATE SCHEMA IF NOT EXISTS $schemaName");
                
                // Read and execute institution schema
                $sql = file_get_contents(__DIR__ . '/supabase/institution_schema.sql');
                
                if ($sql === false) {
                    $message = 'Could not read institution_schema.sql';
                } else {
                    // Split SQL into individual statements
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    
                    foreach ($statements as $statement) {
                        if (empty($statement) || strpos($statement, '--') === 0) continue;
                        
                        // Prefix table names with schema
                        $statement = str_replace('CREATE TABLE IF NOT EXISTS ', "CREATE TABLE IF NOT EXISTS $schemaName.", $statement);
                        $statement = str_replace('INSERT INTO ', "INSERT INTO $schemaName.", $statement);
                        $statement = str_replace('FROM departments', "FROM $schemaName.departments", $statement);
                        $statement = str_replace('FROM classes', "FROM $schemaName.classes", $statement);
                        $statement = str_replace('FROM units', "FROM $schemaName.units", $statement);
                        $statement = str_replace('FROM trainers', "FROM $schemaName.trainers", $statement);
                        
                        try {
                            SupabaseDB::execute($masterConn, $statement);
                        } catch (Exception $e) {
                            // Ignore duplicate errors
                            if (strpos($e->getMessage(), 'duplicate') === false) {
                                error_log("SQL Error: " . $e->getMessage());
                            }
                        }
                    }

                    // Insert institution record
                    SupabaseDB::execute(
                        $masterConn, 
                        "INSERT INTO institutions (name, code, db_name) VALUES ($1, $2, $3)",
                        [$name, $code, $schemaName]
                    );
                    $message = "Institution registered successfully! Code: <strong>$code</strong>";
                }
            } catch (Exception $e) {
                $message = 'Error creating institution: ' . $e->getMessage();
            }
        } else {
            // MySQL: Create separate database
            $rootConn = new mysqli('127.0.0.1', 'root', '');
            if ($rootConn->connect_error) {
                $message = 'MySQL connection error: ' . $rootConn->connect_error;
            } else {
                if ($rootConn->query("CREATE DATABASE `$schemaName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                    $rootConn->select_db($schemaName);
                    $sql = file_get_contents(__DIR__ . '/init_db.sql');

                    if ($sql === false) {
                        $message = 'Could not read init_db.sql';
                    } else {
                        if ($rootConn->multi_query($sql)) {
                            while ($rootConn->more_results() && $rootConn->next_result()) { /* flush */ }

                            $stmt = $masterConn->prepare("INSERT INTO institutions (name, code, db_name) VALUES (?,?,?)");
                            if ($stmt) {
                                $stmt->bind_param('sss', $name, $code, $schemaName);
                                $stmt->execute();
                                $stmt->close();
                                $message = "Institution registered successfully! Code: <strong>$code</strong>";
                            } else {
                                $message = 'Error saving institution: ' . $masterConn->error;
                            }
                        } else {
                            $message = 'Error importing schema: ' . $rootConn->error;
                        }
                    }
                } else {
                    $message = 'Error creating DB: ' . $rootConn->error;
                }
            }
        }
    } else {
        $message = 'Please fill in all fields.';
    }
}

$institutionsRes = master_query("SELECT * FROM institutions ORDER BY created_at DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin Dashboard - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
      min-height: 100vh;
      padding: 30px 20px;
    }
    .dashboard-container {
      max-width: 1400px;
      margin: 0 auto;
    }
    .top-bar {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 20px 30px;
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border: 1px solid rgba(255,255,255,0.2);
    }
    .top-bar h1 {
      color: white;
      font-size: 24px;
      font-weight: 700;
    }
    .top-bar .subtitle {
      color: rgba(255,255,255,0.7);
      font-size: 14px;
      margin-top: 5px;
    }
    .top-bar-actions {
      text-align: right;
    }
    .top-bar-actions span {
      color: rgba(255,255,255,0.9);
      font-size: 14px;
      display: block;
      margin-bottom: 5px;
    }
    .top-bar-actions a {
      color: #a5b4fc;
      text-decoration: none;
      font-size: 13px;
      transition: color 0.3s;
    }
    .top-bar-actions a:hover { color: white; }
    .layout {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 30px;
    }
    .card {
      background: white;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    }
    .card h2 {
      font-size: 20px;
      font-weight: 700;
      color: #1e1b4b;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }
    .card h2 i {
      margin-right: 12px;
      color: #6366f1;
    }
    .message {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #059669;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-size: 14px;
    }
    label {
      display: block;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
      font-size: 14px;
    }
    input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      font-size: 15px;
      margin-bottom: 15px;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s;
    }
    input:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    button {
      padding: 14px 24px;
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      font-family: 'Poppins', sans-serif;
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th {
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      color: white;
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
    }
    th:first-child { border-radius: 10px 0 0 0; }
    th:last-child { border-radius: 0 10px 0 0; }
    td {
      padding: 15px;
      border-bottom: 1px solid #e5e7eb;
      font-size: 14px;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f9fafb; }
    .badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    .badge-active {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #059669;
    }
    .badge-inactive {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #dc2626;
    }
    .action-link {
      color: #6366f1;
      text-decoration: none;
      margin-right: 15px;
      font-weight: 500;
      transition: color 0.3s;
    }
    .action-link:hover { color: #8b5cf6; }
    .action-link.delete { color: #ef4444; }
    .action-link.delete:hover { color: #dc2626; }
    .db-type-badge {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
      margin-left: 8px;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="top-bar">
      <div style="display: flex; align-items: center; gap: 15px;">
        <div style="background: white; border-radius: 12px; padding: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
          <img src="assets/SMARTLOGO.svg" alt="SUAS Logo" style="height: 60px; width: 60px; object-fit: contain;">
        </div>
        <div>
          <h1><i class="fas fa-crown"></i> SUAS Super Admin</h1>
          <div class="subtitle">Smart Unit Attendance System - Institution Management</div>
          <?php if($useSupabase): ?>
            <span class="db-type-badge"><i class="fas fa-database"></i> Supabase Mode</span>
          <?php else: ?>
            <span class="db-type-badge" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-database"></i> MySQL Mode</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="top-bar-actions">
        <span>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['super_admin_username'] ?? 'superadmin'); ?></strong></span>
        <a href="index.php"><i class="fas fa-home"></i> Go to Home</a>
      </div>
    </div>

    <div class="layout">
      <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Register New Institution</h2>
        <?php if($message) echo '<div class="message"><i class="fas fa-check-circle"></i> '.$message.'</div>'; ?>
        <form method="post">
          <label>Institution Name</label>
          <input type="text" name="name" placeholder="e.g. Hansen Technical Institute" required>

          <label>Institution Code</label>
          <input type="text" name="code" placeholder="e.g. HTI001" required>

          <button type="submit"><i class="fas fa-database"></i> Create <?php echo $useSupabase ? 'Schema' : 'Database'; ?> & Register</button>
        </form>
        
        <?php if($useSupabase): ?>
        <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-radius: 12px; border-left: 4px solid #0284c7;">
          <strong style="color: #0369a1;"><i class="fas fa-info-circle"></i> Supabase Mode:</strong>
          <p style="color: #0c4a6e; font-size: 13px; margin-top: 5px;">
            Institutions are created as PostgreSQL schemas within the same database. This is the recommended approach for Supabase.
          </p>
        </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <h2><i class="fas fa-university"></i> Registered Institutions</h2>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Code</th>
              <th><?php echo $useSupabase ? 'Schema' : 'Database'; ?></th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if($institutionsRes && count($institutionsRes) > 0): ?>
            <?php foreach($institutionsRes as $inst): ?>
              <tr>
                <td><?php echo htmlspecialchars($inst['name']); ?></td>
                <td><?php echo htmlspecialchars($inst['code']); ?></td>
                <td><code style="background:#f3f4f6;padding:3px 8px;border-radius:4px;"><?php echo htmlspecialchars($inst['db_name']); ?></code></td>
                <td>
                  <?php if(!empty($inst['active'])): ?>
                    <span class="badge badge-active"><i class="fas fa-check-circle"></i> Active</span>
                  <?php else: ?>
                    <span class="badge badge-inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="?toggle_id=<?php echo $inst['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-link">
                    <?php echo !empty($inst['active']) ? '<i class="fas fa-toggle-on"></i> Deactivate' : '<i class="fas fa-toggle-off"></i> Activate'; ?>
                  </a>
                  <a href="?delete_id=<?php echo $inst['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-link delete" onclick="return confirm('Delete this institution and its <?php echo $useSupabase ? 'schema' : 'database'; ?>? This cannot be undone!');">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:30px;">No institutions registered yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
