<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['trainer'])){ header('Location: login.php'); exit; }
if(empty($_SESSION['selected_department'])){ header('Location: select_department.php'); exit; }

$trainer = $_SESSION['trainer'];
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
$week = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$lesson = isset($_GET['lesson']) ? $conn->real_escape_string($_GET['lesson']) : '';

if(!$class_id || !$unit_id || !$week || !$lesson){
  echo 'Missing parameters.'; exit;
}

// fetch class and unit info
$classR = $conn->query("SELECT * FROM classes WHERE id=$class_id");
$class = $classR->fetch_assoc();
$deptR = $conn->query("SELECT name FROM departments WHERE id=".$class['department_id']);
$dept = $deptR->fetch_assoc();

$unitR = $conn->query("SELECT * FROM units WHERE id=$unit_id");
$unit = $unitR->fetch_assoc(); 

// fetch attendance records for this trainer/unit/week/lesson
$att = $conn->query("SELECT a.*, s.admission_number, s.full_name FROM attendance a JOIN students s ON s.id=a.student_id WHERE a.unit_id=$unit_id AND a.week=$week AND a.lesson='".$lesson."' AND a.trainer_id=".(int)$trainer['id']." ORDER BY s.admission_number");

?>
<!DOCTYPE html>
<html>
<head> 
  <meta charset="utf-8">
  <title>View Attendance</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-lg">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
      <a href="dashboard.php?class_id=<?php echo $class_id; ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-sm font-bold">← Back</a> 
      <span class="text-gray-600 hidden md:inline">Logged in as: <?php echo h($trainer['name']); ?></span> 
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm font-bold">Logout</a>
    </div>

    <div class="text-center mb-6">
      <h1 class="text-xl font-bold uppercase">HANSEN &amp; LAWRENCE SMART UNIT ATTENDANCE SYSTEM (HL-SUAS)</h1> 
      <h2 class="text-md font-semibold"><?php echo h($dept['name']); ?> DEPARTMENT</h2>
      <h3 class="text-md font-semibold">UNIT ATTENDANCE REGISTER</h3>
      <p class="text-gray-600 mt-2">CLASS: <?php echo h($class['name']); ?></p>
    </div>

    <div class="mb-6 pb-6 border-b border-gray-300">
      <h2 class="text-lg font-bold text-gray-800">Attendance Details</h2>
      <p class="text-gray-600 mt-2">Unit: <strong><?php echo h($unit['code'].' - '.$unit['name']); ?></strong> | Week: <strong><?php echo $week; ?></strong> | Lesson: <strong><?php echo h($lesson); ?></strong></p> 
    </div>

    <div class="overflow-x-auto mb-6">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-blue-600 text-white"> 
            <th class="px-4 py-2 text-left border">Admission No.</th>
            <th class="px-4 py-2 text-left border">Student Name</th>
            <th class="px-4 py-2 text-left border">Status</th>
            <th class="px-4 py-2 text-left border">Date</th>
          </tr>
        </thead>
        <tbody>
        <?php if($att && $att->num_rows){ while($r=$att->fetch_assoc()){ echo '<tr class="hover:bg-gray-50"><td class="px-4 py-2 border">'.h($r['admission_number']).'</td><td class="px-4 py-2 border">'.h($r['full_name']).'</td><td class="px-4 py-2 border">'.($r['status']=='present'?'<span class="present">✓ Present</span>':'<span class="absent">✗ Absent</span>').'</td><td class="px-4 py-2 border">'.h($r['attendance_date']).'</td></tr>'; } } else { echo '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No attendance records found for this selection.</td></tr>'; } ?>
        </tbody>
      </table>
    </div>

    <div class="flex gap-4 justify-center">
      <a href="dashboard.php?class_id=<?php echo $class_id; ?>" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">← Back to Dashboard</a>
      <a href="download_attendance_pdf.php?class_id=<?php echo $class_id; ?>&unit_id=<?php echo $unit_id; ?>&week=<?php echo $week; ?>&lesson=<?php echo urlencode($lesson); ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Download PDF</a>
    </div>
  </div> 
</body>
</html>
