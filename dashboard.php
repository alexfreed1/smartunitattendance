<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

// Get institution name
$institution_name = $_SESSION['institution_name'] ?? 'Institution';
$institution_code = $_SESSION['institution_code'] ?? '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background-color: #F4F7FC; }

    .dashboard-container { display: flex; min-height: 100vh; }

    /* Sidebar Styling - Dark Theme */
    .sidebar { 
      width: 280px; 
      background-color: #1F2A40; 
      color: white; 
      display: flex; 
      flex-direction: column; 
      box-shadow: 2px 0 15px rgba(0,0,0,0.1); 
    }
    .sidebar-header { 
      padding: 25px 20px; 
      text-align: center; 
      border-bottom: 1px solid rgba(255,255,255,0.1); 
    }
    .sidebar-header img { 
      height: 70px; 
      margin-bottom: 12px; 
      filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2)); 
    }
    .sidebar-header h2 { 
      font-size: 15px; 
      font-weight: 700; 
      margin: 0; 
      letter-spacing: 0.5px; 
      color: white;
    }
    .sidebar-header h3 { 
      font-size: 12px; 
      font-weight: 500; 
      margin: 5px 0 0; 
      color: rgba(255,255,255,0.8); 
    }
    .sidebar-header .institution-name { 
      font-size: 11px; 
      color: rgba(255,255,255,0.7); 
      margin-top: 8px; 
      padding-top: 8px; 
      border-top: 1px solid rgba(255,255,255,0.15); 
    }

    .sidebar-menu { flex: 1; padding: 20px 0; overflow-y: auto; }
    .sidebar-menu a { 
      display: block; 
      padding: 14px 25px; 
      color: rgba(255,255,255,0.85); 
      text-decoration: none; 
      transition: all 0.3s; 
      border-left: 4px solid transparent; 
      font-size: 14px; 
    }
    .sidebar-menu a:hover { 
      background-color: #2D6CDF; 
      color: white; 
    }
    .sidebar-menu a.active { 
      background: linear-gradient(90deg, #2F6FED, #3C8CE7); 
      color: white; 
      border-left-color: #2F6FED;
      box-shadow: 2px 0 10px rgba(47, 111, 237, 0.3);
    }

    .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.15); }
    .sidebar-footer a { 
      display: block; 
      padding: 12px; 
      text-align: center; 
      background: rgba(239, 68, 68, 0.2); 
      color: white; 
      text-decoration: none; 
      border-radius: 8px; 
      transition: all 0.3s; 
      font-weight: 500; 
    }
    .sidebar-footer a:hover { background: rgba(239, 68, 68, 0.3); }

    /* Main Content Styling */
    .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    iframe { width: 100%; height: 100%; border: none; }
  </style>
</head>
<body>
  <div class="dashboard-container max-w-7xl mx-auto my-6 bg-white shadow-xl rounded-2xl overflow-hidden">
    <div class="sidebar">
      <div class="sidebar-header">
        <img src="../assets/smartlogo.svg" alt="SUAS Logo" style="background: white; border-radius: 50%; padding: 5px;">
        <h2>SMART UNIT ATTENDANCE SYSTEM</h2>
        <h3>Admin Dashboard</h3>
        <div class="institution-name">
          <?= htmlspecialchars($institution_name); ?> 
          <?php if($institution_code): ?> (<?= htmlspecialchars($institution_code); ?>) <?php endif; ?>
        </div>
      </div>
      <div class="sidebar-menu">
        <a href="welcome.php" target="content_frame" class="active">📊 Dashboard</a>
        <a href="all_students.php" target="content_frame">🎓 All Students</a>
        <a href="trainer_attendance.php" target="content_frame">📈 Trainer Reports</a>
        <a href="departments.php" target="content_frame">🏢 Departments</a>
        <a href="trainers.php" target="content_frame">👨‍🏫 Trainers</a>
        <a href="classes.php" target="content_frame">📚 Classes</a>
        <a href="students.php" target="content_frame">➕ Add Student</a>
        <a href="units.php" target="content_frame">📖 Units</a>
        <a href="assign_units.php" target="content_frame">📋 Assign Units</a>
        <a href="view_attendance.php" target="content_frame">📝 View Attendance</a>
        <a href="change_password.php" target="content_frame">🔑 Change Password</a>
      </div>
      <div class="sidebar-footer">
        <a href="../logout.php">🚪 Logout</a>
      </div>
    </div>
    <div class="main-content">
      <iframe name="content_frame" src="welcome.php"></iframe>
    </div>
  </div>
</body>
</html>
