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
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;

// Fetch departments
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// Fetch trainers with department info
$trainers_query = "SELECT t.*, d.name as dept_name 
                   FROM trainers t 
                   LEFT JOIN departments d ON t.department_id = d.id ";
if($department_id) {
    $trainers_query .= "WHERE t.department_id = $department_id ";
}
$trainers_query .= "ORDER BY t.name";
$trainers = $conn->query($trainers_query);

// Fetch units
$units = $conn->query("SELECT * FROM units ORDER BY code");

// Fetch AGGREGATED attendance records (grouped by class, unit, week, lesson, date)
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
if($month) {
    $attendance_query .= "AND MONTH(a.attendance_date) = $month ";
}

$attendance_query .= "GROUP BY a.trainer_id, a.unit_id, c.id, DATE(a.attendance_date), a.week, a.lesson
                     ORDER BY a.attendance_date DESC, c.name, u.code";
$attendance = $conn->query($attendance_query);

// Get summary statistics
$total_classes = $attendance->num_rows;
$total_present = $conn->query("SELECT SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as count
                                FROM attendance a
                                LEFT JOIN students s ON a.student_id = s.id
                                LEFT JOIN classes c ON s.class_id = c.id
                                WHERE 1=1 " .
                                ($department_id ? " AND c.department_id = $department_id" : "") .
                                ($year ? " AND YEAR(a.attendance_date) = $year" : ""))
                               ->fetch_assoc()['count'];
$total_absent = $conn->query("SELECT SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as count
                               FROM attendance a
                               LEFT JOIN students s ON a.student_id = s.id
                               LEFT JOIN classes c ON s.class_id = c.id
                               WHERE 1=1 " .
                               ($department_id ? " AND c.department_id = $department_id" : "") .
                               ($year ? " AND YEAR(a.attendance_date) = $year" : ""))
                              ->fetch_assoc()['count'];
$total_students = $total_present + $total_absent;
$attendance_rate = $total_students > 0 ? round(($total_present / $total_students) * 100, 1) : 0;

// Get unique trainers who have taken attendance
$trainer_stats_query = "SELECT t.id, t.name, d.name as dept_name,
                               COUNT(DISTINCT CONCAT(a.unit_id, a.week, a.lesson, DATE(a.attendance_date))) as classes_taught,
                               SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                               SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                        FROM trainers t
                        LEFT JOIN attendance a ON a.trainer_id = t.id
                        LEFT JOIN students s ON a.student_id = s.id
                        LEFT JOIN classes c ON s.class_id = c.id
                        LEFT JOIN departments d ON t.department_id = d.id
                        WHERE 1=1 ";
if($department_id) {
    $trainer_stats_query .= " AND (t.department_id = $department_id OR c.department_id = $department_id) ";
}
if($year) {
    $trainer_stats_query .= " AND YEAR(a.attendance_date) = $year ";
}
$trainer_stats_query .= "GROUP BY t.id, t.name, d.name ORDER BY classes_taught DESC";
$trainer_stats = $conn->query($trainer_stats_query);

// Generate years for dropdown (current year and past 5 years)
$current_year = date('Y');
$years = range($current_year, $current_year - 5);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Trainer Attendance Reports - SUAS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #F4F7FC;
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
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .header h2 {
      margin: 0;
      color: #2F6FED;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .header a {
      color: #2F6FED;
      text-decoration: none;
      font-weight: 600;
      padding: 10px 20px;
      background: linear-gradient(90deg, #E0E7FF 0%, #C7D2FE 100%);
      border-radius: 10px;
      transition: all 0.3s;
    }
    .header a:hover {
      background: linear-gradient(90deg, #C7D2FE 0%, #A5B4FC 100%);
    }
    .container {
      max-width: 1400px;
      margin: 0 auto;
    }
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-box {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      text-align: center;
    }
    .stat-box .number {
      font-size: 36px;
      font-weight: 700;
      color: #2F6FED;
    }
    .stat-box .number.green { color: #22C55E; }
    .stat-box .number.red { color: #EF4444; }
    .stat-box .number.orange { color: #F59E0B; }
    .stat-box .label {
      font-size: 13px;
      color: #6B7280;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-top: 5px;
    }
    .card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin-bottom: 20px;
    }
    .card h3 {
      color: #2B2B2B;
      margin-bottom: 20px;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .card h3 i {
      color: #2F6FED;
    }
    .filter-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    .filter-item label {
      display: block;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
      font-size: 13px;
    }
    .filter-item label i {
      color: #2F6FED;
      margin-right: 6px;
    }
    .filter-item select,
    .filter-item input {
      width: 100%;
      padding: 10px 12px;
      border: 2px solid #E5E7EB;
      border-radius: 8px;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      transition: all 0.3s;
    }
    .filter-item select:focus,
    .filter-item input:focus {
      outline: none;
      border-color: #2F6FED;
      box-shadow: 0 0 0 4px rgba(47, 111, 237, 0.1);
    }
    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      transition: all 0.3s;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }
    .btn-primary {
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      color: white;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(47, 111, 237, 0.4);
    }
    .btn-success {
      background: linear-gradient(90deg, #22C55E, #16A34A);
      color: white;
    }
    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(34, 197, 94, 0.4);
    }
    .trainer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    .trainer-card {
      background: linear-gradient(135deg, #F4F7FC 0%, #E8ECF5 100%);
      padding: 20px;
      border-radius: 12px;
      border-left: 4px solid #2F6FED;
      transition: all 0.3s;
    }
    .trainer-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .trainer-card h4 {
      color: #2B2B2B;
      margin-bottom: 12px;
      font-size: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .trainer-card .dept {
      font-size: 12px;
      color: #6B7280;
      font-weight: normal;
    }
    .trainer-stats {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 10px;
      margin-top: 12px;
    }
    .trainer-stat {
      background: white;
      padding: 10px;
      border-radius: 8px;
      text-align: center;
    }
    .trainer-stat .value {
      font-size: 20px;
      font-weight: 700;
      color: #2F6FED;
    }
    .trainer-stat .label {
      font-size: 10px;
      color: #9CA3AF;
      text-transform: uppercase;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      border-radius: 12px;
      overflow: hidden;
      font-size: 13px;
    }
    table th {
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      color: white;
      padding: 12px 10px;
      text-align: left;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
      white-space: nowrap;
    }
    table td {
      padding: 12px 10px;
      border-bottom: 1px solid #E5E7EB;
      color: #2B2B2B;
    }
    table tr:last-child td { border-bottom: none; }
    table tr:hover td { background: #EFF6FF; }
    .no-records {
      text-align: center;
      padding: 40px;
      color: #9CA3AF;
    }
    .no-records i {
      font-size: 48px;
      margin-bottom: 15px;
      color: #E5E7EB;
    }
    .actions-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .badge {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
    }
    .badge-blue {
      background: #E0E7FF;
      color: #2F6FED;
    }
    .badge-green {
      background: #D1FAE5;
      color: #22C55E;
    }
    .badge-red {
      background: #FEE2E2;
      color: #EF4444;
    }
    .attendance-bar {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .bar-container {
      flex: 1;
      height: 8px;
      background: #E5E7EB;
      border-radius: 4px;
      overflow: hidden;
    }
    .bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #22C55E, #16A34A);
      border-radius: 4px;
    }
    .bar-fill.low {
      background: linear-gradient(90deg, #EF4444, #DC2626);
    }
    .bar-fill.med {
      background: linear-gradient(90deg, #F59E0B, #D97706);
    }
  </style>
</head>
<body>
<div class="header">
  <h2><i class="fas fa-chalkboard-teacher"></i> Trainer Attendance Reports</h2>
  <a href="welcome.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="container">
    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="number"><?php echo $total_classes; ?></div>
            <div class="label">Class Sessions</div>
        </div>
        <div class="stat-box">
            <div class="number green"><?php echo $total_present; ?></div>
            <div class="label">Total Present</div>
        </div>
        <div class="stat-box">
            <div class="number red"><?php echo $total_absent; ?></div>
            <div class="label">Total Absent</div>
        </div>
        <div class="stat-box">
            <div class="number orange"><?php echo $attendance_rate; ?>%</div>
            <div class="label">Attendance Rate</div>
        </div>
    </div>

    <!-- Trainer Statistics -->
    <div class="card">
        <h3><i class="fas fa-chart-bar"></i> Trainer Performance Summary</h3>
        <div class="trainer-grid">
            <?php 
            if($trainer_stats && $trainer_stats->num_rows > 0):
                while($ts = $trainer_stats->fetch_assoc()): 
                    $total = $ts['present_count'] + $ts['absent_count'];
                    $attendance_rate = $total > 0 ? round(($ts['present_count'] / $total) * 100, 1) : 0;
            ?>
            <div class="trainer-card">
                <h4>
                    <?php echo htmlspecialchars($ts['name']); ?>
                    <span class="dept"><?php echo htmlspecialchars($ts['dept_name'] ?? 'N/A'); ?></span>
                </h4>
                <div class="trainer-stats">
                    <div class="trainer-stat">
                        <div class="value"><?php echo $ts['classes_taught']; ?></div>
                        <div class="label">Classes</div>
                    </div>
                    <div class="trainer-stat">
                        <div class="value" style="color: #22C55E;"><?php echo $ts['present_count']; ?></div>
                        <div class="label">Present</div>
                    </div>
                    <div class="trainer-stat">
                        <div class="value" style="color: #EF4444;"><?php echo $ts['absent_count']; ?></div>
                        <div class="label">Absent</div>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            else:
            ?>
            <div class="no-records">
                <i class="fas fa-inbox"></i>
                <p>No trainer records found for the selected filters.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="card">
        <h3><i class="fas fa-list"></i> Class Attendance Summary by Trainer</h3>
        
        <!-- Filters -->
        <form method="get">
            <div class="filter-grid">
                <div class="filter-item">
                    <label><i class="fas fa-building"></i>Department</label>
                    <select name="department_id" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php 
                        $departments->data_seek(0);
                        while($d = $departments->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($department_id == $d['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label><i class="fas fa-chalkboard-teacher"></i>Trainer</label>
                    <select name="trainer_id" onchange="this.form.submit()">
                        <option value="">All Trainers</option>
                        <?php 
                        $trainers->data_seek(0);
                        while($t = $trainers->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo ($trainer_id == $t['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label><i class="fas fa-book"></i>Unit</label>
                    <select name="unit_id" onchange="this.form.submit()">
                        <option value="">All Units</option>
                        <?php 
                        $units->data_seek(0);
                        while($u = $units->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($unit_id == $u['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['code'].' - '.$u['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label><i class="fas fa-calendar"></i>Year</label>
                    <select name="year" onchange="this.form.submit()">
                        <?php foreach($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($year == $y) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label><i class="fas fa-calendar-week"></i>Week</label>
                    <select name="week" onchange="this.form.submit()">
                        <option value="">All Weeks</option>
                        <?php for($w=1; $w<=52; $w++): ?>
                        <option value="<?php echo $w; ?>" <?php echo ($week == $w) ? 'selected' : ''; ?>>
                            Week <?php echo $w; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label><i class="fas fa-calendar-alt"></i>Month</label>
                    <select name="month" onchange="this.form.submit()">
                        <option value="">All Months</option>
                        <?php 
                        $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        for($m=1; $m<=12; $m++): 
                        ?>
                        <option value="<?php echo $m; ?>" <?php echo ($month == $m) ? 'selected' : ''; ?>>
                            <?php echo $months[$m]; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </form>
        
        <!-- Actions Bar -->
        <div class="actions-bar">
            <div>
                <span style="color: #6B7280; font-size: 13px;">
                    <i class="fas fa-info-circle"></i> Showing <?php echo $attendance->num_rows; ?> class sessions
                    <?php if($year): ?> for <?php echo $year; ?> <?php endif; ?>
                </span>
            </div>
            <div>
                <a href="download_trainer_attendance.php?department_id=<?php echo $department_id; ?>&trainer_id=<?php echo $trainer_id; ?>&unit_id=<?php echo $unit_id; ?>&year=<?php echo $year; ?>&week=<?php echo $week; ?>" 
                   class="btn btn-success" target="_blank">
                    <i class="fas fa-download"></i> Download Report
                </a>
            </div>
        </div>
        
        <!-- Attendance Table -->
        <?php if($attendance->num_rows == 0): ?>
            <div class="no-records">
                <i class="fas fa-inbox"></i>
                <p>No attendance records found matching your filters.</p>
            </div>
        <?php else: ?>
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
                        <th>Total Students</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while($a = $attendance->fetch_assoc()): 
                        $rate = $a['total_students'] > 0 ? round(($a['present_count'] / $a['total_students']) * 100, 1) : 0;
                        $rate_class = $rate >= 90 ? '' : ($rate >= 75 ? 'med' : 'low');
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600;"><?php echo date('d M Y', strtotime($a['att_date'])); ?></div>
                        </td>
                        <td>
                            <div style="font-size: 11px; color: #9CA3AF;">
                                <?php echo date('H:i', strtotime($a['min_time'])); ?> - <?php echo date('H:i', strtotime($a['max_time'])); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($a['trainer_name']); ?></td>
                        <td>
                            <div style="font-weight: 600; color: #2F6FED;"><?php echo htmlspecialchars($a['unit_code']); ?></div>
                            <div style="font-size: 11px; color: #9CA3AF;"><?php echo htmlspecialchars($a['unit_name']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($a['class_name']); ?></td>
                        <td><span class="badge badge-blue">Week <?php echo $a['week']; ?></span></td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars($a['lesson']); ?></span></td>
                        <td style="font-weight: 600;"><?php echo $a['total_students']; ?></td>
                        <td>
                            <span class="badge badge-green" style="font-size: 12px;">
                                <i class="fas fa-check"></i> <?php echo $a['present_count']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-red" style="font-size: 12px;">
                                <i class="fas fa-times"></i> <?php echo $a['absent_count']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="attendance-bar">
                                <div class="bar-container">
                                    <div class="bar-fill <?php echo $rate_class; ?>" style="width: <?php echo $rate; ?>%"></div>
                                </div>
                                <span style="font-weight: 700; min-width: 45px;"><?php echo $rate; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
