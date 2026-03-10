<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

$error = '';

if(isset($_POST['username'])) {
    $u = db_escape($_POST['username']);
    $p = db_escape($_POST['password']);

    $result = db_query("SELECT * FROM trainers WHERE username='$u' AND password='$p'");
    if($result && count($result) > 0) {
        $_SESSION['trainer'] = $result[0];
        $_SESSION['trainer_id'] = $result[0]['id'];
        $_SESSION['trainer_username'] = $u;
        header("Location: select_department.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Trainer Login - SUAS</title>
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
    .login-container {
      background: white;
      border-radius: 24px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 450px;
      overflow: hidden;
    }
    .login-header {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 50%, #6d28d9 100%);
      padding: 40px 30px;
      text-align: center;
    }
    .login-header img {
      height: 80px;
      margin-bottom: 15px;
      filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
    }
    .login-header h1 {
      color: white;
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 5px;
    }
    .login-header h2 {
      color: rgba(255,255,255,0.95);
      font-size: 16px;
      font-weight: 500;
    }
    .login-body {
      padding: 40px 30px;
    }
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
    .error i { margin-right: 10px; }
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
      color: #8b5cf6;
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
      border-color: #8b5cf6;
      box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
    }
    .submit-btn {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
      box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
    }
    .login-footer {
      text-align: center;
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #e5e7eb;
    }
    .login-footer a {
      color: #8b5cf6;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: color 0.3s;
    }
    .login-footer a:hover { color: #7c3aed; }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-header">
      <img src="../assets/smartlogo.svg" alt="SUAS Logo">
      <h1>SMART UNIT ATTENDANCE SYSTEM</h1>
      <h2>Trainer Login</h2>
    </div>

    <div class="login-body">
      <?php if(isset($error)) echo '<div class="error"><i class="fas fa-exclamation-circle"></i>'.h($error).'</div>'; ?>

      <form method="post">
        <div class="form-group">
          <label><i class="fas fa-user"></i>Username</label>
          <input type="text" name="username" required placeholder="Enter your username">
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
        <a href="../index.php"><i class="fas fa-home"></i> Back to Home</a>
      </div>
    </div>
  </div>
</body>
</html>
