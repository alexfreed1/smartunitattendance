<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get institution info
$institution_name = $_SESSION['institution_name'] ?? 'Institution';
$institution_code = $_SESSION['institution_code'] ?? '';

// Fetch students with filters
$students_query = "SELECT s.*, c.name as class_name, d.name as dept_name 
                   FROM students s 
                   LEFT JOIN classes c ON s.class_id = c.id 
                   LEFT JOIN departments d ON c.department_id = d.id 
                   WHERE 1=1 ";
if($department_id) {
    $students_query .= "AND c.department_id = $department_id ";
}
if($class_id) {
    $students_query .= "AND s.class_id = $class_id ";
}
$students_query .= "ORDER BY c.name, s.admission_number";
$students = $conn->query($students_query);

// Get class info if selected
$class_info = null;
if($class_id) {
    $class_info = $conn->query("SELECT c.name as class_name, d.name as dept_name 
                                 FROM classes c 
                                 LEFT JOIN departments d ON d.id = c.department_id 
                                 WHERE c.id = $class_id")->fetch_assoc();
}

// Get department info if selected
$dept_info = null;
if($department_id && !$class_id) {
    $dept_info = $conn->query("SELECT name FROM departments WHERE id = $department_id")->fetch_assoc();
}

$generated_date = date('d M Y, H:i');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Class List - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: 'Poppins', sans-serif; 
      background-color: #F4F7FC;
      line-height: 1.6; 
    }
    @media print {
      body { background: white; }
      .no-print { display: none; }
    }
    @page { size: A4; margin: 0.5in; }
    .print-container { 
      max-width: 8.5in; 
      margin: 0 auto; 
      padding: 30px; 
      background: white;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .header { 
      text-align: center; 
      margin-bottom: 30px; 
      padding-bottom: 20px;
      border-bottom: 4px solid #2F6FED;
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      margin: -30px -30px 30px -30px;
      padding: 30px;
      color: white;
    }
    .logo-container {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 20px;
      margin-bottom: 15px;
    }
    .logo-img { 
      height: 70px; 
      background: white;
      border-radius: 50%;
      padding: 8px;
    }
    .header h1 { 
      font-size: 24px; 
      font-weight: 700;
      margin-bottom: 8px; 
      letter-spacing: 1px;
    }
    .header h2 { 
      font-size: 16px; 
      font-weight: 600;
      margin-bottom: 5px; 
      opacity: 0.95;
    }
    .header h3 { 
      font-size: 14px; 
      font-weight: 500;
      margin-top: 10px; 
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    .info-bar { 
      display: grid; 
      grid-template-columns: 1fr 1fr; 
      gap: 20px; 
      margin-bottom: 25px; 
      padding: 20px; 
      background: linear-gradient(135deg, #F4F7FC 0%, #E8ECF5 100%);
      border-radius: 12px;
      border-left: 4px solid #2F6FED;
      font-size: 13px; 
    }
    .info-item {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .info-item strong { 
      color: #2F6FED;
      font-weight: 600;
    }
    .info-item span {
      color: #2B2B2B;
    }
    .summary-box {
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      color: white;
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      text-align: center;
    }
    .summary-box .count {
      font-size: 48px;
      font-weight: 700;
    }
    .summary-box .label {
      font-size: 14px;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    table { 
      width: 100%; 
      border-collapse: collapse; 
      margin-top: 20px; 
      border-radius: 12px;
      overflow: hidden;
    }
    thead { 
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
    }
    th { 
      padding: 15px; 
      text-align: left; 
      font-weight: 600; 
      font-size: 12px; 
      color: white;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    td { 
      padding: 14px; 
      font-size: 13px; 
      border-bottom: 1px solid #E5E7EB; 
      color: #2B2B2B;
    }
    tbody tr:nth-child(even) { 
      background-color: #F9FAFB;
    }
    tbody tr:hover {
      background-color: #EFF6FF;
    }
    .footer {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 2px solid #E5E7EB;
      text-align: center;
      font-size: 12px;
      color: #9CA3AF;
    }
    .footer strong {
      color: #2F6FED;
    }
    .buttons { 
      margin-top: 25px; 
      text-align: right; 
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }
    .btn { 
      padding: 12px 24px; 
      border: none; 
      border-radius: 8px; 
      cursor: pointer; 
      font-size: 14px; 
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s;
    }
    .btn-print { 
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      color: white; 
    }
    .btn-print:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(47, 111, 237, 0.4);
    }
    .btn-back { 
      background-color: #6B7280; 
      color: white; 
    }
    .btn-back:hover {
      background-color: #4B5563;
    }
  </style>
</head>
<body>
  <div class="print-container">
    <div class="header">
      <div class="logo-container">
        <img src="../assets/smartlogo.svg" alt="SUAS Logo" class="logo-img">
      </div>
      <h1>SMART UNIT ATTENDANCE SYSTEM</h1>
      <h2><?= htmlspecialchars($institution_name); ?> <?php if($institution_code): ?> (<?= htmlspecialchars($institution_code); ?>) <?php endif; ?></h2>
      <h3>Student Class List</h3>
    </div>

    <?php if($class_info): ?>
    <div class="info-bar">
      <div class="info-item">
        <div><strong>CLASS:</strong> <span><?= htmlspecialchars($class_info['class_name']); ?></span></div>
        <div><strong>DEPARTMENT:</strong> <span><?= htmlspecialchars($class_info['dept_name']); ?></span></div>
      </div>
      <div class="info-item">
        <div><strong>TOTAL STUDENTS:</strong> <span><?php echo $students->num_rows; ?></span></div>
        <div><strong>GENERATED:</strong> <span><?= $generated_date; ?></span></div>
      </div>
    </div>
    
    <div class="summary-box">
      <div class="count"><?php echo $students->num_rows; ?></div>
      <div class="label">Total Students in <?php echo htmlspecialchars($class_info['class_name']); ?></div>
    </div>
    <?php elseif($dept_info): ?>
    <div class="info-bar">
      <div class="info-item">
        <div><strong>DEPARTMENT:</strong> <span><?= htmlspecialchars($dept_info['name']); ?></span></div>
        <div><strong>TOTAL STUDENTS:</strong> <span><?php echo $students->num_rows; ?></span></div>
      </div>
      <div class="info-item">
        <div><strong>GENERATED:</strong> <span><?= $generated_date; ?></span></div>
      </div>
    </div>
    
    <div class="summary-box">
      <div class="count"><?php echo $students->num_rows; ?></div>
      <div class="label">Total Students in <?php echo htmlspecialchars($dept_info['name']); ?> Department</div>
    </div>
    <?php else: ?>
    <div class="info-bar">
      <div class="info-item">
        <div><strong>TOTAL STUDENTS:</strong> <span><?php echo $students->num_rows; ?></span></div>
        <div><strong>GENERATED:</strong> <span><?= $generated_date; ?></span></div>
      </div>
    </div>
    
    <div class="summary-box">
      <div class="count"><?php echo $students->num_rows; ?></div>
      <div class="label">Total Students in Institution</div>
    </div>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th style="width: 60px;">No.</th>
          <th>Admission No</th>
          <th>Student Name</th>
          <th>Class</th>
          <th>Department</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if($students->num_rows > 0){
          $num = 1;
          while($s = $students->fetch_assoc()){
            echo '<tr>';
            echo '<td>'.$num++.'</td>';
            echo '<td>'.htmlspecialchars($s['admission_number']).'</td>';
            echo '<td>'.htmlspecialchars($s['full_name']).'</td>';
            echo '<td>'.htmlspecialchars($s['class_name']).'</td>';
            echo '<td>'.htmlspecialchars($s['dept_name']).'</td>';
            echo '</tr>';
          }
        } else {
          echo '<tr><td colspan="5" style="text-align: center; padding: 40px; color: #9CA3AF;">No students found</td></tr>';
        }
        ?>
      </tbody>
    </table>

    <div class="footer">
      <p>Generated on <strong><?= date('d M Y'); ?></strong> at <strong><?= date('H:i'); ?></strong> | SUAS - Smart Unit Attendance System</p>
    </div>

    <div class="buttons no-print">
      <button class="btn btn-back" onclick="history.back()">
        <i class="fas fa-arrow-left"></i> Back
      </button>
      <button class="btn btn-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print / Save as PDF
      </button>
    </div>
  </div>
</body>
</html>
