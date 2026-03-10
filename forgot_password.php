<?php
require 'config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: select_institution.php');
    exit;
}

$msg = "";
if (isset($_POST['email'])) {
    $email = $_POST['email'];
    
    // Logic to check if email exists in students or trainers table
    // and send a reset link would go here.
    // For security, we typically show the same message whether the email exists or not.
    
    $msg = "If an account exists for this email address, a password reset link has been sent to it.";
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Forgot Password</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #1e5a9f 0%, #2e75b6 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
    .login-box h2 { color: #1e5a9f; margin-bottom: 20px; text-align: center; }
    .login-box p { color: #666; margin-bottom: 20px; text-align: center; font-size: 14px; }
    .success { color: #2e7d32; background-color: #e8f5e9; padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-size: 14px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
    .form-group input:focus { outline: none; border-color: #1e5a9f; box-shadow: 0 0 4px rgba(30, 90, 159, 0.2); }
    .form-group button { width: 100%; padding: 12px; background-color: #1e5a9f; color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.3s; }
    .form-group button:hover { background-color: #154070; }
    .footer { text-align: center; margin-top: 20px; font-size: 14px; }
    .footer a { color: #1e5a9f; text-decoration: none; margin: 0 5px; }
    .footer a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="login-box">
    <div style="text-align:center; margin-bottom:20px;"><img src="assets/smartlogo.png" alt="Logo" style="height:80px;"></div>
    <h2>Reset Password</h2>
    <?php if($msg) echo '<div class="success">'.htmlspecialchars($msg).'</div>'; ?>

    <p>Please enter your email address to request a password reset.</p>
    <form method="post">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <button type="submit">Send Reset Link</button>
        </div>
    </form>

    <div class="footer">
        <p><a href="student_login.php">Back to Student Login</a> | <a href="index.php">Back to Home</a></p>
    </div>
  </div>
</body>
</html>