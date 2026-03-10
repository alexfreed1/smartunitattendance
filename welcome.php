<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

// Fetch statistics
$dept_count = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$trainer_count = $conn->query("SELECT COUNT(*) as count FROM trainers")->fetch_assoc()['count'];
$student_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$unit_count = $conn->query("SELECT COUNT(*) as count FROM units")->fetch_assoc()['count'];
$class_count = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];
$today_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE DATE(attendance_date) = CURDATE()")->fetch_assoc()['count'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: #F4F7FC;
      padding: 15px;
    }
    .dashboard-container {
      max-width: 1200px;
      margin: 0 auto;
    }
    /* Professional Header with Prominent Logo */
    .header {
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      border-radius: 16px;
      padding: 20px 25px;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 20px;
      box-shadow: 0 8px 25px rgba(47, 111, 237, 0.3);
      position: relative;
      overflow: hidden;
    }
    .header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 300px;
      height: 300px;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
    }
    .header-logo-container {
      background: white;
      border-radius: 16px;
      padding: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      flex-shrink: 0;
      z-index: 1;
    }
    .header-logo {
      height: 70px;
      width: 70px;
      object-fit: contain;
    }
    .header-info {
      flex: 1;
      color: white;
      z-index: 1;
    }
    .header-info h1 {
      font-size: 22px;
      font-weight: 800;
      margin-bottom: 5px;
      letter-spacing: 1px;
    }
    .header-info p {
      font-size: 13px;
      opacity: 0.95;
    }
    .header-badge {
      background: rgba(255,255,255,0.2);
      padding: 10px 16px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
      backdrop-filter: blur(10px);
      z-index: 1;
    }
    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 15px;
      margin-bottom: 15px;
    }
    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 18px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      display: flex;
      align-items: center;
      gap: 15px;
      transition: all 0.3s;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    .stat-icon {
      width: 55px;
      height: 55px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: white;
      flex-shrink: 0;
    }
    .stat-icon.blue { background: linear-gradient(90deg, #2F6FED, #3C8CE7); }
    .stat-icon.purple { background: linear-gradient(90deg, #8B5CF6, #7C3AED); }
    .stat-icon.orange { background: linear-gradient(90deg, #F59E0B, #D97706); }
    .stat-icon.cyan { background: linear-gradient(90deg, #06B6D4, #0891B2); }
    .stat-icon.green { background: linear-gradient(90deg, #22C55E, #16A34A); }
    .stat-icon.pink { background: linear-gradient(90deg, #EC4899, #DB2777); }
    .stat-info {
      flex: 1;
      min-width: 0;
    }
    .stat-info h3 {
      font-size: 12px;
      color: #6B7280;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .stat-info p {
      font-size: 28px;
      font-weight: 700;
      color: #2B2B2B;
      line-height: 1;
    }
    /* Quick Actions */
    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
      margin-bottom: 15px;
    }
    .action-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 15px 20px;
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      color: white;
      text-decoration: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s;
      box-shadow: 0 4px 15px rgba(47, 111, 237, 0.25);
    }
    .action-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(47, 111, 237, 0.4);
    }
    .action-btn.green {
      background: linear-gradient(90deg, #22C55E, #16A34A);
    }
    .action-btn.orange {
      background: linear-gradient(90deg, #F59E0B, #D97706);
    }
    /* Security Alert */
    .security-alert {
      background: linear-gradient(135deg, #FEF3C7, #FDE68A);
      border-left: 4px solid #F59E0B;
      padding: 15px 18px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .security-alert i {
      font-size: 22px;
      color: #F59E0B;
      flex-shrink: 0;
    }
    .security-alert-content {
      flex: 1;
    }
    .security-alert h4 {
      font-size: 14px;
      color: #92400E;
      margin-bottom: 4px;
    }
    .security-alert p {
      font-size: 13px;
      color: #78350F;
    }
    .security-alert a {
      color: #2F6FED;
      font-weight: 600;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Professional Header with Prominent Logo -->
    <div class="header">
      <div class="header-logo-container">
        <img src="../assets/SMARTLOGO.svg" alt="SUAS Logo" class="header-logo">
      </div>
      <div class="header-info">
        <h1>SMART UNIT ATTENDANCE SYSTEM</h1>
        <p>Admin Dashboard</p>
      </div>
      <div class="header-badge">
        <i class="fas fa-university"></i> <?= htmlspecialchars($_SESSION['institution_name'] ?? 'N/A'); ?>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <i class="fas fa-building"></i>
        </div>
        <div class="stat-info">
          <h3>Departments</h3>
          <p><?php echo $dept_count; ?></p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="stat-info">
          <h3>Trainers</h3>
          <p><?php echo $trainer_count; ?></p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange">
          <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-info">
          <h3>Students</h3>
          <p><?php echo $student_count; ?></p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon cyan">
          <i class="fas fa-book"></i>
        </div>
        <div class="stat-info">
          <h3>Units</h3>
          <p><?php echo $unit_count; ?></p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <i class="fas fa-school"></i>
        </div>
        <div class="stat-info">
          <h3>Classes</h3>
          <p><?php echo $class_count; ?></p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pink">
          <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="stat-info">
          <h3>Today</h3>
          <p><?php echo $today_attendance; ?></p>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <a href="all_students.php" class="action-btn" target="content_frame">
        <i class="fas fa-users"></i> All Students
      </a>
      <a href="trainer_attendance.php" class="action-btn" target="content_frame">
        <i class="fas fa-chart-bar"></i> Trainer Reports
      </a>
      <a href="students.php" class="action-btn green" target="content_frame">
        <i class="fas fa-user-plus"></i> Add Student
      </a>
      <a href="view_attendance.php" class="action-btn orange" target="content_frame">
        <i class="fas fa-eye"></i> View Attendance
      </a>
    </div>

    <!-- Security Alert -->
    <div class="security-alert">
      <i class="fas fa-exclamation-triangle"></i>
      <div class="security-alert-content">
        <h4>Security Reminder</h4>
        <p>Using default password <strong>admin123</strong>? <a href="change_password.php">Change it now</a></p>
      </div>
    </div>
  </div>
</body>
</html>
