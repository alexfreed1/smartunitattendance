<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$trainer_id = isset($_GET['trainer_id']) ? (int)$_GET['trainer_id'] : 0;
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$week = isset($_GET['week']) ? (int)$_GET['week'] : 0;

// Get institution info
$institution_name = $_SESSION['institution_name'] ?? 'Institution';
$institution_code = $_SESSION['institution_code'] ?? '';

// Get trainer info if selected
$trainer_info = null;
if($trainer_id) {
    $trainer_info = $conn->query("SELECT t.name, d.name as dept_name 
                                   FROM trainers t 
                                   LEFT JOIN departments d ON t.department_id = d.id 
                                   WHERE t.id = $trainer_id")->fetch_assoc();
}

// Get unit info if selected
$unit_info = null;
if($unit_id) {
    $unit_info = $conn->query("SELECT code, name FROM units WHERE id = $unit_id")->fetch_assoc();
}

// Get department info if selected
$dept_info = null;
if($department_id) {
    $dept_info = $conn->query("SELECT name FROM departments WHERE id = $department_id")->fetch_assoc();
}

// Fetch AGGREGATED attendance records
$attendance_query = "SELECT 
                            a.trainer_id,
                            a.unit_id,
                            c.id as class_id,
                            c.name as class_name,
                            u.code as unit_code, 
                            u.name as unit_name,
                            t.name as trainer_name,
                            d.name as dept_name,
                            DATE(a.attendance_date) as att_date,
                            a.week,
                            a.lesson,
                            COUNT(*) as total_students,
                            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                            MIN(a.attendance_date) as min_time,
                            MAX(a.attendance_date) as max_time
                     FROM attendance a
                     LEFT JOIN students s ON a.student_id = s.id
                     LEFT JOIN classes c ON s.class_id = c.id
                     LEFT JOIN departments d ON c.department_id = d.id
                     LEFT JOIN units u ON a.unit_id = u.id
                     LEFT JOIN trainers t ON a.trainer_id = t.id
                     WHERE 1=1 ";

if($department_id) {
    $attendance_query .= "AND c.department_id = $department_id ";
}
if($trainer_id) {
    $attendance_query .= "AND a.trainer_id = $trainer_id ";
}
if($unit_id) {
    $attendance_query .= "AND a.unit_id = $unit_id ";
}
if($year) {
    $attendance_query .= "AND YEAR(a.attendance_date) = $year ";
}
if($week) {
    $attendance_query .= "AND a.week = $week ";
}

$attendance_query .= "GROUP BY a.trainer_id, a.unit_id, c.id, DATE(a.attendance_date), a.week, a.lesson
                     ORDER BY a.attendance_date DESC, c.name, u.code";
$attendance = $conn->query($attendance_query);

// Get summary
$total_classes = $attendance->num_rows;
$total_present = $conn->query("SELECT SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as count 
                                FROM attendance a
                                LEFT JOIN students s ON a.student_id = s.id 
                                LEFT JOIN classes c ON s.class_id = c.id 
                                WHERE 1=1 " . 
                                ($department_id ? " AND c.department_id = $department_id" : "") . 
                                ($trainer_id ? " AND a.trainer_id = $trainer_id" : "") . 
                                ($year ? " AND YEAR(a.attendance_date) = $year" : ""))
                               ->fetch_assoc()['count'];
$total_absent = $conn->query("SELECT SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as count 
                               FROM attendance a
                               LEFT JOIN students s ON a.student_id = s.id 
                               LEFT JOIN classes c ON s.class_id = c.id 
                               WHERE 1=1 " . 
                               ($department_id ? " AND c.department_id = $department_id" : "") . 
                               ($trainer_id ? " AND a.trainer_id = $trainer_id" : "") . 
                               ($year ? " AND YEAR(a.attendance_date) = $year" : ""))
                              ->fetch_assoc()['count'];
$total_students = $total_present + $total_absent;
$attendance_rate = $total_students > 0 ? round(($total_present / $total_students) * 100, 1) : 0;

$generated_date = date('d M Y, H:i');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Trainer Attendance Report - SUAS</title>
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
    @page { size: A4 landscape; margin: 0.5in; }
    .print-container { 
      max-width: 11in; 
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
      grid-template-columns: repeat(3, 1fr); 
      gap: 15px; 
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
      gap: 5px;
    }
    .info-item strong { 
      color: #2F6FED;
      font-weight: 600;
      font-size: 11px;
      text-transform: uppercase;
    }
    .info-item span {
      color: #2B2B2B;
      font-size: 14px;
    }
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 15px;
      margin-bottom: 25px;
    }
    .summary-box {
      background: linear-gradient(135deg, #2F6FED, #3C8CE7);
      color: white;
      padding: 20px;
      border-radius: 12px;
      text-align: center;
    }
    .summary-box .count {
      font-size: 32px;
      font-weight: 700;
    }
    .summary-box .label {
      font-size: 11px;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-top: 5px;
    }
    .summary-box.green { background: linear-gradient(135deg, #22C55E, #16A34A); }
    .summary-box.red { background: linear-gradient(135deg, #EF4444, #DC2626); }
    .summary-box.orange { background: linear-gradient(135deg, #F59E0B, #D97706); }
    table { 
      width: 100%; 
      border-collapse: collapse; 
      margin-top: 20px; 
      border-radius: 12px;
      overflow: hidden;
      font-size: 11px;
    }
    thead { 
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
    }
    th { 
      padding: 12px 8px; 
      text-align: left; 
      font-weight: 600; 
      font-size: 10px; 
      color: white;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      white-space: nowrap;
    }
    td { 
      padding: 10px 8px; 
      font-size: 11px; 
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
      font-size: 11px;
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
      text-decoration: none;
      display: inline-block;
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
    .badge {
      padding: 3px 8px;
      border-radius: 8px;
      font-size: 10px;
      font-weight: 600;
      background: #E0E7FF;
      color: #2F6FED;
      display: inline-block;
    }
    .badge-green {
      background: #D1FAE5;
      color: #22C55E;
    }
    .badge-red {
      background: #FEE2E2;
      color: #EF4444;
    }
    .text-green { color: #22C55E; font-weight: 700; }
    .text-red { color: #EF4444; font-weight: 700; }
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
      <h3>Trainer Class Attendance Summary Report</h3>
    </div>

    <div class="info-bar">
      <?php if($trainer_info): ?>
      <div class="info-item">
        <strong>Trainer</strong>
        <span><?= htmlspecialchars($trainer_info['name']); ?></span>
      </div>
      <div class="info-item">
        <strong>Department</strong>
        <span><?= htmlspecialchars($trainer_info['dept_name'] ?? 'N/A'); ?></span>
      </div>
      <?php endif; ?>
      
      <?php if($unit_info): ?>
      <div class="info-item">
        <strong>Unit</strong>
        <span><?= htmlspecialchars($unit_info['code'].' - '.$unit_info['name']); ?></span>
      </div>
      <?php endif; ?>
      
      <?php if($dept_info): ?>
      <div class="info-item">
        <strong>Department</strong>
        <span><?= htmlspecialchars($dept_info['name']); ?></span>
      </div>
      <?php endif; ?>
      
      <div class="info-item">
        <strong>Year</strong>
        <span><?php echo $year; ?></span>
      </div>
      <div class="info-item">
        <strong>Generated</strong>
        <span><?= $generated_date; ?></span>
      </div>
    </div>

    <div class="summary-grid">
      <div class="summary-box">
        <div class="count"><?php echo $total_classes; ?></div>
        <div class="label">Class Sessions</div>
      </div>
      <div class="summary-box green">
        <div class="count"><?php echo $total_present; ?></div>
        <div class="label">Total Present</div>
      </div>
      <div class="summary-box red">
        <div class="count"><?php echo $total_absent; ?></div>
        <div class="label">Total Absent</div>
      </div>
      <div class="summary-box orange">
        <div class="count"><?php echo $attendance_rate; ?>%</div>
        <div class="label">Attendance Rate</div>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Time</th>
          <th>Trainer</th>
          <th>Unit</th>
          <th>Class</th>
          <th>Week</th>
          <th>Lesson</th>
          <th>Total</th>
          <th>Present</th>
          <th>Absent</th>
          <th>Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if($attendance->num_rows > 0){
          while($a = $attendance->fetch_assoc()){
            $rate = $a['total_students'] > 0 ? round(($a['present_count'] / $a['total_students']) * 100, 1) : 0;
            echo '<tr>';
            echo '<td>'.date('d M Y', strtotime($a['att_date'])).'</td>';
            echo '<td>'.date('H:i', strtotime($a['min_time'])).' - '.date('H:i', strtotime($a['max_time'])).'</td>';
            echo '<td>'.htmlspecialchars($a['trainer_name']).'</td>';
            echo '<td><strong>'.htmlspecialchars($a['unit_code']).'</strong></td>';
            echo '<td>'.htmlspecialchars($a['class_name']).'</td>';
            echo '<td><span class="badge">W'.$a['week'].'</span></td>';
            echo '<td><span class="badge">'.$a['lesson'].'</span></td>';
            echo '<td style="font-weight: 700;">'.$a['total_students'].'</td>';
            echo '<td><span class="badge badge-green"><i class="fas fa-check"></i> '.$a['present_count'].'</span></td>';
            echo '<td><span class="badge badge-red"><i class="fas fa-times"></i> '.$a['absent_count'].'</span></td>';
            echo '<td style="font-weight: 700;">'.$rate.'%</td>';
            echo '</tr>';
          }
        } else {
          echo '<tr><td colspan="11" style="text-align: center; padding: 40px; color: #9CA3AF;">No attendance records found</td></tr>';
        }
        ?>
      </tbody>
    </table>

    <div class="footer">
      <p>Generated on <strong><?= date('d M Y'); ?></strong> at <strong><?= date('H:i'); ?></strong> | SUAS - Smart Unit Attendance System</p>
    </div>

    <div class="buttons no-print">
      <a href="trainer_attendance.php?department_id=<?php echo $department_id; ?>&trainer_id=<?php echo $trainer_id; ?>&unit_id=<?php echo $unit_id; ?>&year=<?php echo $year; ?>&week=<?php echo $week; ?>" class="btn btn-back">
        <i class="fas fa-arrow-left"></i> Back
      </a>
      <button class="btn btn-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print / Save as PDF
      </button>
    </div>
  </div>
</body>
</html>
