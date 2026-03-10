<?php
require 'config_master.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));

    if ($code !== '') {
        $safeCode = $masterConn->real_escape_string($code);
        $res = $masterConn->query("SELECT * FROM institutions WHERE code='$safeCode' AND active = 1");

        if ($res && $res->num_rows) {
            $inst = $res->fetch_assoc();
            $_SESSION['institution_db'] = $inst['db_name'];
            $_SESSION['institution_name'] = $inst['name'];
            $_SESSION['institution_code'] = $inst['code'];

            header('Location: index.php');
            exit;
        } else {
            $msg = 'Institution code not found or inactive.';
        }
    } else {
        $msg = 'Please enter an institution code.';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Select Institution - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .institution-box {
      background: white;
      border-radius: 24px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 480px;
      overflow: hidden;
    }
    .institution-header {
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
      padding: 40px 30px;
      text-align: center;
    }
    .institution-header img {
      height: 70px;
      margin-bottom: 15px;
      filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
    }
    .institution-header h1 {
      color: white;
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 5px;
    }
    .institution-header p {
      color: rgba(255,255,255,0.9);
      font-size: 14px;
    }
    .institution-body {
      padding: 40px 30px;
    }
    .msg-error {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #dc2626;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      font-size: 14px;
    }
    .msg-error i { margin-right: 10px; }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
      font-size: 14px;
    }
    .form-group label i {
      color: #6366f1;
      margin-right: 8px;
    }
    .form-group input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      font-size: 15px;
      transition: all 0.3s;
      font-family: 'Poppins', sans-serif;
      text-transform: uppercase;
    }
    .form-group input:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    .submit-btn {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      font-family: 'Poppins', sans-serif;
    }
    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
    }
    .institution-footer {
      text-align: center;
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #e5e7eb;
      display: flex;
      justify-content: center;
      gap: 20px;
    }
    .institution-footer a {
      color: #6366f1;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: color 0.3s;
    }
    .institution-footer a:hover { color: #8b5cf6; }
    .divider { color: #d1d5db; }
  </style>
</head>
<body>
  <div class="institution-box">
    <div class="institution-header">
      <img src="assets/smartlogo.svg" alt="SUAS Logo">
      <h1>Select Your Institution</h1>
      <p>Enter your institution code to continue</p>
    </div>
    
    <div class="institution-body">
      <?php if($msg): ?>
        <div class="msg-error">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>
      
      <form method="post">
        <div class="form-group">
          <label><i class="fas fa-university"></i>Institution Code</label>
          <input type="text" name="code" placeholder="e.g., INST001" required autocomplete="off">
        </div>
        
        <button type="submit" class="submit-btn">
          <i class="fas fa-arrow-right"></i> Continue
        </button>
      </form>
      
      <div class="institution-footer">
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <span class="divider">|</span>
        <a href="super_admin_login.php"><i class="fas fa-user-shield"></i> Super Admin</a>
      </div>
    </div>
  </div>
</body>
</html>
