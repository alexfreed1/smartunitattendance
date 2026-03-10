<?php
require 'config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: select_institution.php');
    exit;
}

$error = '';

if (isset($_POST['admission_number'])) {
    $raw_adm = $_POST['admission_number'];
    $p = $_POST['password'];

    $normalized_adm = strtolower(str_replace([' ', '-'], '', trim($raw_adm)));
    
    // Use database-agnostic query
    $result = db_query("SELECT * FROM students WHERE LOWER(REPLACE(REPLACE(admission_number, ' ', ''), '-', '')) = ?", [$normalized_adm]);

    if($result && count($result) > 0) {
        $student = $result[0];
        if ($p === $student['password']) {
            $_SESSION['student'] = $student;
            $_SESSION['student_id'] = $student['id'];
            header("Location: student_dashboard.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Invalid admission number.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Login - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #F4F7FC 0%, #E8ECF5 50%, #D1D9F0 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-container {
      background: white;
      border-radius: 24px;
      box-shadow: 0 25px 50px rgba(47, 111, 237, 0.2);
      width: 100%;
      max-width: 420px;
      overflow: hidden;
    }
    .login-header {
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      padding: 40px 30px;
      text-align: center;
    }
    .login-header img {
      height: 80px;
      margin-bottom: 15px;
      background: white;
      border-radius: 50%;
      padding: 10px;
    }
    .login-header h2 {
      color: white;
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 5px;
    }
    .login-header p {
      color: rgba(255,255,255,0.95);
      font-size: 14px;
    }
    .login-body {
      padding: 40px 30px;
    }
    .error {
      background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
      color: #DC2626;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      font-size: 14px;
    }
    .error i {
      margin-right: 10px;
      font-size: 18px;
    }
    .success {
      background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
      color: #059669;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 14px;
    }
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
      color: #2F6FED;
      margin-right: 8px;
    }
    .form-group input {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #E5E7EB;
      border-radius: 12px;
      font-size: 15px;
      transition: all 0.3s;
      font-family: 'Poppins', sans-serif;
    }
    .form-group input:focus {
      outline: none;
      border-color: #2F6FED;
      box-shadow: 0 0 0 4px rgba(47, 111, 237, 0.1);
    }
    .submit-btn {
      width: 100%;
      padding: 15px;
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
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
      box-shadow: 0 10px 25px rgba(47, 111, 237, 0.4);
    }
    .login-footer {
      text-align: center;
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #E5E7EB;
    }
    .login-footer a {
      color: #2F6FED;
      text-decoration: none;
      font-size: 14px;
      margin: 0 10px;
      font-weight: 500;
      transition: color 0.3s;
    }
    .login-footer a:hover {
      color: #3C8CE7;
    }
    .divider {
      color: #D1D5DB;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-header">
      <img src="assets/smartlogo.svg" alt="SUAS Logo">
      <h2>Student Login</h2>
      <p>Smart Unit Attendance System</p>
    </div>

    <div class="login-body">
      <?php if(isset($_GET['registered'])): ?>
        <div class="success">
          <i class="fas fa-check-circle"></i>
          Registration successful! Please login.
        </div>
      <?php endif; ?>

      <?php if(isset($error)): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label><i class="fas fa-id-card"></i>Admission Number</label>
          <input type="text" name="admission_number" required placeholder="Enter your admission number">
        </div>

        <div class="form-group">
          <label><i class="fas fa-lock"></i>Password</label>
          <input type="password" name="password" required placeholder="Enter your password">
        </div>

        <button type="submit" class="submit-btn">
          <i class="fas fa-sign-in-alt"></i> Login
        </button>
      </form>

      <div class="login-footer">
        <a href="student_register.php"><i class="fas fa-user-plus"></i> Register</a>
        <span class="divider">|</span>
        <a href="forgot_password.php"><i class="fas fa-key"></i> Forgot Password?</a>
        <div style="margin-top: 15px;">
          <a href="index.php"><i class="fas fa-home"></i> Back to Home</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
