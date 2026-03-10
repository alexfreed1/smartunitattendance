<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Verify current password
        $current_user = $_SESSION['admin'];
        $result = $conn->query("SELECT * FROM admins WHERE username='$current_user'");
        
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Check current password
            if ($current_password !== $admin['password']) {
                $error = "Current password is incorrect.";
            } else {
                // Update password
                $new_password_escaped = $conn->real_escape_string($new_password);
                if ($conn->query("UPDATE admins SET password='$new_password_escaped' WHERE username='$current_user'")) {
                    $message = "Password changed successfully! Please login again with your new password.";
                    // Clear admin session to force re-login
                    unset($_SESSION['admin']);
                    header("Location: login.php?password_changed=1");
                    exit;
                } else {
                    $error = "Failed to change password. Please try again.";
                }
            }
        } else {
            $error = "Admin account not found.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Change Password - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    .password-container {
      background: white;
      border-radius: 24px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 500px;
      overflow: hidden;
    }
    .password-header {
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
      padding: 35px 30px;
      text-align: center;
    }
    .password-header i {
      font-size: 48px;
      color: white;
      margin-bottom: 15px;
    }
    .password-header h2 {
      color: white;
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 5px;
    }
    .password-header p {
      color: rgba(255,255,255,0.9);
      font-size: 14px;
    }
    .password-body {
      padding: 40px 30px;
    }
    .message {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #059669;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      font-size: 14px;
    }
    .message i { margin-right: 10px; font-size: 18px; }
    .error {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #dc2626;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      font-size: 14px;
    }
    .error i { margin-right: 10px; font-size: 18px; }
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
    }
    .form-group input:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    .password-requirements {
      background: #f9fafb;
      padding: 15px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-size: 13px;
      color: #6b7280;
    }
    .password-requirements ul {
      list-style: none;
      padding-left: 0;
    }
    .password-requirements li {
      padding: 5px 0;
    }
    .password-requirements li i {
      color: #6366f1;
      margin-right: 8px;
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
    .password-footer {
      text-align: center;
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #e5e7eb;
    }
    .password-footer a {
      color: #6366f1;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: color 0.3s;
    }
    .password-footer a:hover { color: #8b5cf6; }
    .strength-meter {
      height: 4px;
      background: #e5e7eb;
      border-radius: 2px;
      margin-top: 8px;
      overflow: hidden;
    }
    .strength-bar {
      height: 100%;
      width: 0%;
      transition: all 0.3s;
      border-radius: 2px;
    }
    .strength-weak { background: #ef4444; width: 33%; }
    .strength-medium { background: #f59e0b; width: 66%; }
    .strength-strong { background: #10b981; width: 100%; }
  </style>
</head>
<body>
  <div class="password-container">
    <div class="password-header">
      <i class="fas fa-key"></i>
      <h2>Change Password</h2>
      <p>Update your admin password</p>
    </div>
    
    <div class="password-body">
      <?php if(isset($_GET['password_changed'])): ?>
        <div class="message">
          <i class="fas fa-check-circle"></i>
          Password changed successfully! Please login with your new password.
        </div>
      <?php endif; ?>
      
      <?php if($error): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      
      <form method="post">
        <div class="form-group">
          <label><i class="fas fa-lock"></i>Current Password</label>
          <input type="password" name="current_password" required placeholder="Enter your current password">
        </div>
        
        <div class="form-group">
          <label><i class="fas fa-key"></i>New Password</label>
          <input type="password" name="new_password" id="new_password" required placeholder="Enter new password" onkeyup="checkStrength()">
          <div class="strength-meter">
            <div class="strength-bar" id="strength_bar"></div>
          </div>
          <p id="strength_text" style="font-size: 12px; margin-top: 5px; color: #9ca3af;"></p>
        </div>
        
        <div class="form-group">
          <label><i class="fas fa-lock"></i>Confirm New Password</label>
          <input type="password" name="confirm_password" required placeholder="Confirm new password">
        </div>
        
        <div class="password-requirements">
          <strong><i class="fas fa-info-circle"></i> Password Requirements:</strong>
          <ul>
            <li><i class="fas fa-check"></i> Minimum 6 characters</li>
            <li><i class="fas fa-check"></i> Use a mix of letters and numbers</li>
            <li><i class="fas fa-check"></i> Avoid common passwords</li>
          </ul>
        </div>
        
        <button type="submit" class="submit-btn">
          <i class="fas fa-save"></i> Change Password
        </button>
      </form>
      
      <div class="password-footer">
        <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
      </div>
    </div>
  </div>
  
  <script>
    function checkStrength() {
      var password = document.getElementById('new_password').value;
      var strength_bar = document.getElementById('strength_bar');
      var strength_text = document.getElementById('strength_text');
      
      var strength = 0;
      
      if (password.length >= 6) strength++;
      if (password.length >= 10) strength++;
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
      if (/\d/.test(password)) strength++;
      if (/[^a-zA-Z0-9]/.test(password)) strength++;
      
      strength_bar.className = 'strength-bar';
      
      if (strength <= 2) {
        strength_bar.classList.add('strength-weak');
        strength_text.textContent = 'Weak password';
        strength_text.style.color = '#ef4444';
      } else if (strength <= 4) {
        strength_bar.classList.add('strength-medium');
        strength_text.textContent = 'Medium password';
        strength_text.style.color = '#f59e0b';
      } else {
        strength_bar.classList.add('strength-strong');
        strength_text.textContent = 'Strong password';
        strength_text.style.color = '#10b981';
      }
      
      if (password.length === 0) {
        strength_bar.className = 'strength-bar';
        strength_text.textContent = '';
      }
    }
  </script>
</body>
</html>
