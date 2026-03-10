<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

// Get institution info
$institution_name = $_SESSION['institution_name'] ?? 'Institution';
$institution_code = $_SESSION['institution_code'] ?? '';

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
$week = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$lesson = isset($_GET['lesson']) ? $conn->real_escape_string($_GET['lesson']) : '';

if(!$class_id || !$unit_id || !$week || !$lesson){ echo 'Missing parameters.'; exit; }

$classR = $conn->query("SELECT * FROM classes WHERE id=$class_id");
$class = $classR->fetch_assoc();
$deptR = $conn->query("SELECT name FROM departments WHERE id=".$class['department_id']);
$dept = $deptR->fetch_assoc();
$unitR = $conn->query("SELECT * FROM units WHERE id=$unit_id");
$unit = $unitR->fetch_assoc();

// Fetch attendance
$sql = "SELECT a.*, s.admission_number, s.full_name
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE a.unit_id = $unit_id
        AND a.week = $week
        AND a.lesson = '$lesson'
        AND s.class_id = $class_id
        ORDER BY s.admission_number";
$att = $conn->query($sql);

$attendance_records = [];
$attendanceDate = '-';
$trainer_name = '_______________';

if($att && $att->num_rows){
    while($r = $att->fetch_assoc()) $attendance_records[] = $r;
    if(!empty($attendance_records)) {
        $attendanceDate = $attendance_records[0]['attendance_date'];
        if(isset($attendance_records[0]['trainer_id'])){
            $tr = $conn->query("SELECT name FROM trainers WHERE id=".$attendance_records[0]['trainer_id']);
            if($tr && $tr->num_rows) $trainer_name = $tr->fetch_assoc()['name'];
        }
    }
}

$dateGen = date('d M Y, H:i');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Attendance Report - SUAS</title>
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
    .status-present { 
      color: #22C55E; 
      font-weight: 700; 
    }
    .status-absent { 
      color: #EF4444; 
      font-weight: 700; 
    }
    .no-records { 
      text-align: center; 
      padding: 40px; 
      color: #9CA3AF; 
      font-size: 14px;
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
      <h3>Unit Attendance Register</h3>
    </div>

    <div class="info-bar">
      <div class="info-item">
        <div><strong>CLASS:</strong> <span><?= htmlspecialchars($class['name']); ?></span></div>
        <div><strong>UNIT:</strong> <span><?= htmlspecialchars($unit['code'].' - '.$unit['name']); ?></span></div>
        <div><strong>DEPARTMENT:</strong> <span><?= htmlspecialchars($dept['name']); ?></span></div>
      </div>
      <div class="info-item">
        <div><strong>WEEK:</strong> <span>Week <?= htmlspecialchars($week); ?></span></div>
        <div><strong>LESSON:</strong> <span><?= htmlspecialchars($lesson); ?></span></div>
        <div><strong>DATE:</strong> <span><?= htmlspecialchars($attendanceDate); ?></span></div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width: 60px;">No.</th>
          <th>Admission No</th>
          <th>Student Name</th>
          <th style="width: 120px;">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if(!empty($attendance_records)){
          $num = 1;
          foreach($attendance_records as $r){
            $statusClass = ($r['status'] == 'present') ? 'status-present' : 'status-absent';
            $statusText = ($r['status'] == 'present') ? '✓ Present' : '✗ Absent';
            echo '<tr>';
            echo '<td>'.$num++.'</td>';
            echo '<td>'.htmlspecialchars($r['admission_number']).'</td>';
            echo '<td>'.htmlspecialchars($r['full_name']).'</td>';
            echo '<td class="'.$statusClass.'">'.$statusText.'</td>';
            echo '</tr>';
          }
        } else {
          echo '<tr><td colspan="4" class="no-records">No attendance records found</td></tr>';
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
