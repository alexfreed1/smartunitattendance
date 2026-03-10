<?php
require 'config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: select_institution.php');
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['student'])) {
    header("Location: student_login.php");
    exit;
}

$student_id = $_SESSION['student']['id'];
$student_name = isset($_SESSION['student']['full_name']) ? $_SESSION['student']['full_name'] : ($_SESSION['student']['name'] ?? '');
$admission_number = isset($_SESSION['student']['admission_number']) ? $_SESSION['student']['admission_number'] : ($_SESSION['student']['admission_no'] ?? '');
$class_id = isset($_SESSION['student']['class_id']) ? $_SESSION['student']['class_id'] : 0;

$stmt_class = $conn->prepare("SELECT class_id FROM students WHERE id = ?");
$stmt_class->bind_param("i", $student_id);
$stmt_class->execute();
$res_class = $stmt_class->get_result();
if($res_class->num_rows > 0) {
    $class_id = $res_class->fetch_assoc()['class_id'];
}

$query = "SELECT u.code as unit_code,
                 u.name as unit_name,
                 COUNT(a.id) as total_records,
                 SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as attended,
                 MAX(a.attendance_date) as last_update
          FROM class_units cu
          JOIN units u ON cu.unit_id = u.id
          LEFT JOIN attendance a ON a.unit_id = u.id AND a.student_id = ?
          WHERE cu.class_id = ?
          GROUP BY u.id, u.code, u.name";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $student_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

$attendance_data = [];
$total_attended_sum = 0;
$total_records_sum = 0;

while($row = $result->fetch_assoc()){
    $attendance_data[] = $row;
    $total_attended_sum += $row['attended'];
    $total_records_sum += $row['total_records'];
}
$overall_percentage = ($total_records_sum > 0) ? round(($total_attended_sum / $total_records_sum) * 100, 1) : 0;
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard - SUAS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .navbar {
            background: linear-gradient(90deg, #2F6FED, #3C8CE7);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(47, 111, 237, 0.3);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            height: 60px;
            margin-right: 15px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        }
        .navbar-brand h1 {
            font-size: 18px;
            font-weight: 700;
        }
        .navbar-brand p {
            font-size: 12px;
            opacity: 0.9;
        }
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .navbar-user span {
            font-size: 14px;
        }
        .navbar-user a {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        .navbar-user a:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-right: 20px;
            color: white;
        }
        .bg-blue { background: linear-gradient(90deg, #2F6FED, #3C8CE7); }
        .bg-green { background: linear-gradient(90deg, #22C55E, #16A34A); }
        .bg-purple { background: linear-gradient(90deg, #8B5CF6, #7C3AED); }
        .stat-info h3 {
            margin: 0;
            font-size: 13px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-info p {
            margin: 5px 0 0;
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
        }
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
        }
        .card-header h2 {
            color: #1f2937;
            font-size: 20px;
            font-weight: 700;
        }
        .card-header .badge {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #4f46e5;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            padding: 15px 30px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .toolbar input {
            padding: 10px 15px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            font-size: 14px;
            min-width: 250px;
            font-family: 'Poppins', sans-serif;
        }
        .toolbar input:focus {
            outline: none;
            border-color: #6366f1;
        }
        .toolbar button {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(90deg, #2F6FED, #3C8CE7);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(47, 111, 237, 0.4);
        }
        .btn-secondary {
            background-color: #6B7280;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #4B5563;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: linear-gradient(90deg, #2F6FED, #3C8CE7);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 18px 25px;
            text-align: left;
        }
        th:first-child { border-radius: 0 0 0 10px; }
        th:last-child { border-radius: 0 0 10px 0; }
        td {
            padding: 20px 25px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 14px;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f9fafb; }
        .unit-code {
            font-weight: 700;
            color: #6366f1;
            background: #e0e7ff;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 13px;
        }
        .unit-name {
            font-weight: 500;
            color: #1f2937;
        }
        .progress-container {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            max-width: 200px;
        }
        .progress-bg {
            flex: 1;
            height: 10px;
            background: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 1s ease;
        }
        .status-high { background: linear-gradient(90deg, #10b981, #059669); }
        .status-med { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .status-low { background: linear-gradient(90deg, #ef4444, #dc2626); }
        .text-high { color: #059669; font-weight: 700; }
        .text-med { color: #d97706; font-weight: 700; }
        .text-low { color: #dc2626; font-weight: 700; }
        .last-update {
            font-size: 12px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        .empty-state i {
            font-size: 64px;
            color: #e5e7eb;
            margin-bottom: 20px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #c7c7c7;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">
            <div style="background: white; border-radius: 16px; padding: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); margin-right: 15px;">
                <img src="assets/SMARTLOGO.svg" alt="SUAS Logo" style="height: 60px; width: 60px; object-fit: contain;">
            </div>
            <div>
                <h1>SMART UNIT ATTENDANCE SYSTEM (SUAS)</h1>
                <p>Student Portal - <?= htmlspecialchars($_SESSION['institution_name'] ?? ''); ?> <?php if(!empty($_SESSION['institution_code'])): ?> (<?= htmlspecialchars($_SESSION['institution_code']); ?>) <?php endif; ?></p>
            </div>
        </div>
        <div class="navbar-user">
            <span><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($student_name); ?> (<?= htmlspecialchars($admission_number); ?>)</span>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="summary-grid">
        <div class="stat-card">
            <div class="stat-icon bg-blue"><i class="fas fa-book"></i></div>
            <div class="stat-info">
                <h3>Total Units</h3>
                <p><?php echo count($attendance_data); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3>Classes Attended</h3>
                <p><?php echo $total_attended_sum; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple"><i class="fas fa-chart-pie"></i></div>
            <div class="stat-info">
                <h3>Avg. Attendance</h3>
                <p><?php echo $overall_percentage; ?>%</p>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-clipboard-list"></i> Attendance Dashboard</h2>
            <span class="badge"><i class="fas fa-calendar"></i> <?php echo date('F Y'); ?></span>
        </div>
        <div class="toolbar">
            <input type="text" id="unitFilter" placeholder="🔍 Filter by unit code or name...">
            <button class="btn-primary" type="button" onclick="applyUnitFilter()"><i class="fas fa-filter"></i> Apply Filter</button>
            <button class="btn-secondary" type="button" onclick="clearUnitFilter()"><i class="fas fa-times"></i> Clear</button>
            <div style="flex:1;"></div>
            <button class="btn-secondary" type="button" onclick="window.print()"><i class="fas fa-print"></i> Print / PDF</button>
        </div>

        <?php if (count($attendance_data) > 0): ?>
            <div class="table-responsive">
                <table id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Unit Code</th>
                            <th>Unit Name</th>
                            <th style="text-align: center;">Attended</th>
                            <th style="text-align: center;">Total</th>
                            <th>Attendance %</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($attendance_data as $row):
                            $percentage = ($row['total_records'] > 0) ? round(($row['attended'] / $row['total_records']) * 100, 1) : 0;
                            $statusClass = ($percentage < 75) ? 'status-low' : (($percentage < 90) ? 'status-med' : 'status-high');
                            $textClass = ($percentage < 75) ? 'text-low' : (($percentage < 90) ? 'text-med' : 'text-high');
                        ?>
                        <tr>
                            <td><span class="unit-code"><?php echo htmlspecialchars($row['unit_code']); ?></span></td>
                            <td class="unit-name"><?php echo htmlspecialchars($row['unit_name']); ?></td>
                            <td style="text-align: center; font-weight: bold; color: #059669;"><?php echo $row['attended']; ?></td>
                            <td style="text-align: center; color: #9ca3af;"><?php echo $row['total_records']; ?></td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-bg"><div class="progress-fill <?php echo $statusClass; ?>" style="width: <?php echo $percentage; ?>%"></div></div>
                                    <span class="progress-text <?php echo $textClass; ?>"><?php echo $percentage; ?>%</span>
                                </div>
                            </td>
                            <td><div class="last-update"><i class="far fa-clock"></i> <?php echo $row['last_update'] ? date('d M, H:i', strtotime($row['last_update'])) : '-'; ?></div></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>No attendance records found for your account yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="index.php" style="color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 10px 25px; border-radius: 10px; display: inline-block; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</div>

<script>
    function applyUnitFilter() {
        var filter = document.getElementById('unitFilter').value.toLowerCase();
        var table = document.getElementById('attendanceTable');
        if (!table) return;
        var rows = table.getElementsByTagName('tr');
        for (var i = 1; i < rows.length; i++) {
            var cells = rows[i].getElementsByTagName('td');
            if (cells.length < 2) continue;
            var text = (cells[0].textContent + ' ' + cells[1].textContent).toLowerCase();
            rows[i].style.display = text.indexOf(filter) !== -1 ? '' : 'none';
        }
    }

    function clearUnitFilter() {
        var input = document.getElementById('unitFilter');
        input.value = '';
        applyUnitFilter();
    }
</script>

</body>
</html>
