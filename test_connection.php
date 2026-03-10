<?php
/**
 * SUAS - Supabase Connection Test
 * Quick verification that your Supabase connection is working
 */

echo "<!doctype html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>SUAS - Connection Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .test-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        h1 {
            color: #6366f1;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .test-item.pass {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid #10b981;
        }
        .test-item.fail {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #ef4444;
        }
        .test-item.warn {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
        }
        .icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .pass .icon { background: #10b981; color: white; }
        .fail .icon { background: #ef4444; color: white; }
        .warn .icon { background: #f59e0b; color: white; }
        .test-content {
            flex: 1;
        }
        .test-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 3px;
        }
        .test-detail {
            font-size: 13px;
            color: #6b7280;
        }
        .pass .test-detail { color: #059669; }
        .fail .test-detail { color: #dc2626; }
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
        .config-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 13px;
        }
        .config-box strong {
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class='test-box'>
        <h1>🔌 Supabase Connection Test</h1>
        <p class='subtitle'>Verifying your database configuration...</p>\n";

// Test 1: Check .env file exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "<div class='test-item pass'>
            <div class='icon'>✓</div>
            <div class='test-content'>
                <div class='test-title'>.env File Found</div>
                <div class='test-detail'>Configuration file exists at: " . htmlspecialchars($envFile) . "</div>
            </div>
          </div>";
    
    // Load environment variables
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $envVars = [];
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
    
    // Test 2: Check Supabase settings
    $useSupabase = isset($envVars['USE_SUPABASE']) && $envVars['USE_SUPABASE'] === 'true';
    if ($useSupabase) {
        echo "<div class='test-item pass'>
                <div class='icon'>✓</div>
                <div class='test-content'>
                    <div class='test-title'>Supabase Mode Enabled</div>
                    <div class='test-detail'>USE_SUPABASE = true</div>
                </div>
              </div>";
    } else {
        echo "<div class='test-item fail'>
                <div class='icon'>✗</div>
                <div class='test-content'>
                    <div class='test-title'>Supabase Mode NOT Enabled</div>
                    <div class='test-detail'>Set USE_SUPABASE=true in .env file</div>
                </div>
              </div>";
    }
    
    // Test 3: Check Supabase host
    $host = $envVars['SUPABASE_DB_HOST'] ?? '';
    if (!empty($host)) {
        echo "<div class='test-item pass'>
                <div class='icon'>✓</div>
                <div class='test-content'>
                    <div class='test-title'>Supabase Host Configured</div>
                    <div class='test-detail'>" . htmlspecialchars($host) . "</div>
                </div>
              </div>";
    } else {
        echo "<div class='test-item fail'>
                <div class='icon'>✗</div>
                <div class='test-content'>
                    <div class='test-title'>Supabase Host Missing</div>
                    <div class='test-detail'>Set SUPABASE_DB_HOST in .env file</div>
                </div>
              </div>";
    }
    
    // Test 4: Check database password
    $pass = $envVars['SUPABASE_DB_PASS'] ?? '';
    if (!empty($pass)) {
        echo "<div class='test-item pass'>
                <div class='icon'>✓</div>
                <div class='test-content'>
                    <div class='test-title'>Database Password Set</div>
                    <div class='test-detail'>Password is configured (hidden for security)</div>
                </div>
              </div>";
    } else {
        echo "<div class='test-item fail'>
                <div class='icon'>✗</div>
                <div class='test-content'>
                    <div class='test-title'>Database Password Missing</div>
                    <div class='test-detail'>Set SUPABASE_DB_PASS in .env file</div>
                </div>
              </div>";
    }
    
    // Test 5: Try to connect to Supabase
    echo "<div class='test-item ";
    try {
        $dsn = "pgsql:host=$host;port=5432;dbname=postgres;";
        $pdo = new PDO($dsn, $envVars['SUPABASE_DB_USER'] ?? 'postgres', $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM super_admins");
        $result = $stmt->fetch();
        $adminCount = $result['count'];
        
        echo "pass'>
                <div class='icon'>✓</div>
                <div class='test-content'>
                    <div class='test-title'>Supabase Connection Successful!</div>
                    <div class='test-detail'>Connected to $host | Found $adminCount super admin(s)</div>
                </div>
              </div>";
        
        // Test 6: Check if tables exist
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM institutions");
            $result = $stmt->fetch();
            $instCount = $result['count'];
            
            echo "<div class='test-item pass'>
                    <div class='icon'>✓</div>
                    <div class='test-content'>
                        <div class='test-title'>Database Tables Found</div>
                        <div class='test-detail'>super_admins: $adminCount records | institutions: $instCount records</div>
                    </div>
                  </div>";
            
            // Success! Show next steps
            echo "<div class='config-box'>
                    <strong>✅ Setup Complete!</strong><br><br>
                    Your Supabase connection is working perfectly.<br>
                    All required tables are present and accessible.<br><br>
                    <strong>Next Steps:</strong><br>
                    1. Click 'Continue to Application' below<br>
                    2. Login as Super Admin (superadmin / super123)<br>
                    3. Register your first institution<br>
                    4. Start using SUAS!
                  </div>";
            
        } catch (PDOException $e) {
            echo "<div class='test-item warn'>
                    <div class='icon'>!</div>
                    <div class='test-content'>
                        <div class='test-title'>Tables May Not Exist</div>
                        <div class='test-detail'>Connection works but tables not found. Run supabase/master_schema.sql in Supabase SQL Editor.</div>
                    </div>
                  </div>";
        }
        
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        echo "fail'>
                <div class='icon'>✗</div>
                <div class='test-content'>
                    <div class='test-title'>Connection Failed</div>
                    <div class='test-detail'>" . htmlspecialchars($errorMsg) . "</div>
                </div>
              </div>";
        
        echo "<div class='config-box'>
                <strong>⚠️ Connection Issue</strong><br><br>
                <strong>Check these:</strong><br>
                1. Verify Supabase project is active<br>
                2. Check database password is correct<br>
                3. Ensure Supabase allows connections (Settings → Database → Connections)<br>
                4. Run supabase/master_schema.sql in Supabase SQL Editor
              </div>";
    }
    
} else {
    echo "<div class='test-item fail'>
            <div class='icon'>✗</div>
            <div class='test-content'>
                <div class='test-title'>.env File NOT Found</div>
                <div class='test-detail'>Create .env file in: " . htmlspecialchars($envFile) . "</div>
            </div>
          </div>";
    
    echo "<div class='config-box'>
            <strong>⚠️ Missing Configuration</strong><br><br>
            The .env file was not found. Copy .env.example to .env and configure your Supabase credentials.
          </div>";
}

echo "
        <div class='actions'>
            <a href='index.php' class='btn'>🏠 Continue to Application</a>
            <a href='setup_check.php' class='btn' style='margin-left: 10px; background: linear-gradient(135deg, #10b981, #059669);'>🔍 Full Setup Check</a>
        </div>
    </div>
</body>
</html>";
