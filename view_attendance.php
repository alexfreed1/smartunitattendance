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
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
$week = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$lesson = isset($_GET['lesson']) ? $conn->real_escape_string($_GET['lesson']) : '';

// Fetch departments
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// Fetch classes filtered by department
$classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c LEFT JOIN departments d ON c.department_id = d.id ".($department_id ? "WHERE c.department_id = $department_id" : "")." ORDER BY c.name");

// Fetch units
$units = $conn->query("SELECT * FROM units ORDER BY code");

$attendance = [];
if($class_id && $unit_id && $week && $lesson){
    $sql = "SELECT a.*, s.admission_number, s.full_name
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            WHERE a.unit_id = $unit_id
            AND a.week = $week
            AND a.lesson = '$lesson'
            AND s.class_id = $class_id
            ORDER BY s.admission_number";
    $res = $conn->query($sql);
    if($res){
        while($row = $res->fetch_assoc()){
            $attendance[] = $row;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View Attendance - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      min-height: 100vh;
      padding: 20px;
    }
    .header {
      background: white;
      padding: 20px 30px;
      border-radius: 16px;
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .header h2 {
      margin: 0;
      color: #6366f1;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .header a {
      color: #6366f1;
      text-decoration: none;
      font-weight: 600;
      padding: 10px 20px;
      background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
      border-radius: 10px;
      transition: all 0.3s;
    }
    .header a:hover {
      background: linear-gradient(135deg, #c7d2fe 0%, #a5b4fc 100%);
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    h3 {
      color: #1f2937;
      margin-top: 0;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e5e7eb;
      font-size: 20px;
      font-weight: 700;
    }
    .form-section {
      background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
      padding: 25px;
      border-radius: 12px;
      margin-bottom: 30px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }
    label {
      display: block;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
      font-size: 14px;
    }
    label i {
      color: #6366f1;
      margin-right: 8px;
    }
    select {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s;
      background: white;
    }
    select:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    button {
      padding: 12px 25px;
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s;
      margin-top: 10px;
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      border-radius: 12px;
      overflow: hidden;
    }
    table th {
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      color: white;
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
    }
    table td {
      padding: 15px;
      border-bottom: 1px solid #e5e7eb;
      font-size: 14px;
    }
    table tr:last-child td { border-bottom: none; }
    table tr:hover td { background: #f9fafb; }
    .present { color: #10b981; font-weight: 700; }
    .absent { color: #ef4444; font-weight: 700; }
    .btn-download {
      display: inline-block;
      padding: 12px 25px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      text-decoration: none;
      border-radius: 10px;
      font-weight: 600;
      margin-bottom: 15px;
      margin-right: 10px;
      transition: all 0.3s;
    }
    .btn-download:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
    }
    .btn-print {
      display: inline-block;
      padding: 12px 25px;
      background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
      color: white;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s;
    }
    .btn-print:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(107, 114, 128, 0.4);
    }
    .no-records {
      text-align: center;
      padding: 40px;
      color: #9ca3af;
    }
    .no-records i {
      font-size: 48px;
      margin-bottom: 15px;
      color: #e5e7eb;
    }
  </style>
</head>
<body>
<div class="header">
  <h2><i class="fas fa-clipboard-list"></i> View Attendance</h2>
  <a href="welcome.php"><i class="fas fa-home"></i> Back to Dashboard</a>
</div>

<div class="container">
    <div class="form-section">
        <h3><i class="fas fa-filter"></i> Filter Attendance</h3>
        <form method="get">
            <div class="form-grid">
                <div>
                    <label><i class="fas fa-building"></i>Department</label>
                    <select name="department_id" required onchange="this.form.submit()">
                        <option value="">-- Select Department --</option>
                        <?php 
                        $departments->data_seek(0);
                        while($d = $departments->fetch_assoc()) 
                            echo "<option value='{$d['id']}' ".($department_id==$d['id']?'selected':'').">".h($d['name'])."</option>"; 
                        ?>
                    </select>
                </div>
                
                <?php if($department_id): ?>
                <div>
                    <label><i class="fas fa-school"></i>Class</label>
                    <select name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php 
                        $classes->data_seek(0);
                        while($c = $classes->fetch_assoc()) 
                            echo "<option value='{$c['id']}' ".($class_id==$c['id']?'selected':'').">".h($c['name'])." (".h($c['dept_name']).")</option>"; 
                        ?>
                    </select>
                </div>
                
                <div>
                    <label><i class="fas fa-book"></i>Unit</label>
                    <select name="unit_id" required>
                        <option value="">-- Select Unit --</option>
                        <?php 
                        $units->data_seek(0);
                        while($u = $units->fetch_assoc()) 
                            echo "<option value='{$u['id']}' ".($unit_id==$u['id']?'selected':'').">".h($u['code'].' - '.$u['name'])."</option>"; 
                        ?>
                    </select>
                </div>
                
                <div>
                    <label><i class="fas fa-calendar-week"></i>Week</label>
                    <select name="week" required>
                        <option value="">-- Select Week --</option>
                        <?php for($i=1; $i<=52; $i++) echo "<option value='$i' ".($week==$i?'selected':'').">Week $i</option>"; ?>
                    </select>
                </div>
                
                <div>
                    <label><i class="fas fa-clock"></i>Lesson</label>
                    <select name="lesson" required>
                        <option value="">-- Select Lesson --</option>
                        <option value="L1" <?php echo $lesson=='L1'?'selected':''; ?>>L1</option>
                        <option value="L2" <?php echo $lesson=='L2'?'selected':''; ?>>L2</option>
                        <option value="L3" <?php echo $lesson=='L3'?'selected':''; ?>>L3</option>
                        <option value="L4" <?php echo $lesson=='L4'?'selected':''; ?>>L4</option>
                    </select>
                </div>
                
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" style="width: 100%; margin-top: 0;">
                        <i class="fas fa-search"></i> View Records
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if(!$department_id): ?>
            <div style="text-align: center; padding: 30px; color: #9ca3af;">
                <i class="fas fa-arrow-up" style="font-size: 24px; margin-bottom: 10px;"></i>
                <p>Please select a department first to view classes</p>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if($class_id && $unit_id && $week && $lesson): ?>
    <h3><i class="fas fa-table"></i> Attendance Records</h3>
    
    <div style="margin-bottom: 20px;">
        <a href="download_attendance_pdf.php?class_id=<?php echo $class_id; ?>&unit_id=<?php echo $unit_id; ?>&week=<?php echo $week; ?>&lesson=<?php echo urlencode($lesson); ?>" class="btn-download" target="_blank">
            <i class="fas fa-file-pdf"></i> Download PDF
        </a>
        <button type="button" class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print This Page
        </button>
    </div>
    
    <?php if(empty($attendance)): ?>
        <div class="no-records">
            <i class="fas fa-inbox"></i>
            <p>No attendance records found for this selection.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Status</th>
                    <th>Date Recorded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($attendance as $r): ?>
                <tr>
                    <td><?php echo h($r['admission_number']); ?></td>
                    <td><?php echo h($r['full_name']); ?></td>
                    <td>
                        <?php if($r['status']=='present'): ?>
                            <span class="present"><i class="fas fa-check-circle"></i> Present</span>
                        <?php else: ?>
                            <span class="absent"><i class="fas fa-times-circle"></i> Absent</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo h($r['attendance_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
