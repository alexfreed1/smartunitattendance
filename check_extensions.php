<?php
/**
 * PHP Extension Checker
 * Shows which extensions are loaded and which are missing
 */
echo "<!doctype html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>PHP Extension Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        h1 { color: #6366f1; margin-bottom: 10px; }
        h2 { color: #1f2937; margin: 20px 0 10px; font-size: 18px; }
        .ext-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .ext-item {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        .ext-loaded {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
            border-left: 3px solid #10b981;
        }
        .ext-missing {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border-left: 3px solid #ef4444;
        }
        .info-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box h3 {
            color: #92400e;
            margin-bottom: 10px;
        }
        .info-box code {
            background: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        .step {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .step strong {
            color: #6366f1;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin: 10px 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='box'>
            <h1>🔍 PHP Extension Checker</h1>
            <p style='color: #6b7280; margin-bottom: 20px;'>Checking loaded PHP extensions...</p>
            
            <h2>Required Extensions for SUAS</h2>
            <div class='ext-grid'>";

$required = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'pgsql', 'mbstring', 'json', 'session'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    $class = $loaded ? 'ext-loaded' : 'ext-missing';
    $icon = $loaded ? '✓' : '✗';
    echo "<div class='ext-item $class'>$icon $ext</div>";
}

echo "      </div>
        </div>
        
        <div class='box'>
            <h2>PHP Configuration</h2>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td>" . PHP_VERSION . "</td>
                </tr>
                <tr>
                    <td>PHP INI File</td>
                    <td><code>" . php_ini_loaded_file() . "</code></td>
                </tr>
                <tr>
                    <td>Additional INI Files</td>
                    <td><code>" . php_ini_scanned_files() . "</code></td>
                </tr>
                <tr>
                    <td>Extension Directory</td>
                    <td><code>" . ini_get('extension_dir') . "</code></td>
                </tr>
            </table>
        </div>
        
        <div class='box'>
            <h2>All Loaded Extensions</h2>
            <div class='ext-grid'>";

$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "<div class='ext-item ext-loaded'>✓ " . htmlspecialchars($ext) . "</div>";
}

echo "      </div>
        </div>";

// Check if pdo_pgsql is missing
if (!extension_loaded('pdo_pgsql')) {
    echo "<div class='box'>
            <div class='info-box'>
                <h3>⚠️ PostgreSQL Driver Missing</h3>
                <p>The <code>pdo_pgsql</code> extension is not enabled. This is required for Supabase connection.</p>
                
                <h3 style='margin-top: 20px;'>📝 How to Fix in XAMPP:</h3>
                
                <div class='step'>
                    <strong>Step 1: Open php.ini</strong>
                    <p>Go to: <code>C:\\xampp\\php\\php.ini</code></p>
                    <p>Or click: <a href='http://localhost/xampp/phpinfo.php' target='_blank'>Check phpinfo</a></p>
                </div>
                
                <div class='step'>
                    <strong>Step 2: Find and Uncomment</strong>
                    <p>Press <code>Ctrl+F</code> and search for: <code>pdo_pgsql</code></p>
                    <p>Find this line:</p>
                    <p><code>;extension=pdo_pgsql</code></p>
                    <p>Remove the semicolon to make it:</p>
                    <p><code>extension=pdo_pgsql</code></p>
                </div>
                
                <div class='step'>
                    <strong>Step 3: Also Enable pgsql</strong>
                    <p>Search for: <code>pgsql</code></p>
                    <p>Change: <code>;extension=pgsql</code></p>
                    <p>To: <code>extension=pgsql</code></p>
                </div>
                
                <div class='step'>
                    <strong>Step 4: Restart Apache</strong>
                    <p>1. Open XAMPP Control Panel</p>
                    <p>2. Click <strong>Stop</strong> on Apache</p>
                    <p>3. Click <strong>Start</strong> on Apache</p>
                </div>
                
                <div class='step'>
                    <strong>Step 5: Verify</strong>
                    <p>Refresh this page to check if extensions are now loaded.</p>
                    <a href='check_extensions.php' class='btn'>🔄 Refresh Check</a>
                </div>
            </div>
          </div>";
} else {
    echo "<div class='box'>
            <div class='info-box' style='background: #d1fae5; border-color: #10b981;'>
                <h3 style='color: #059669;'>✅ All Extensions Loaded!</h3>
                <p>Your PHP is properly configured for SUAS with Supabase.</p>
                <a href='test_connection.php' class='btn'>🔌 Test Supabase Connection</a>
            </div>
          </div>";
}

echo "    </div>
</body>
</html>";
