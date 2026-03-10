<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

// Inline Login Logic (Plain Text Password)
if(empty($_SESSION['trainer'])){
    $error = '';
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_username'])){
        $u = $conn->real_escape_string($_POST['login_username']);
        $p = $_POST['login_password']; 
        // Plain text check as requested
        $res = $conn->query("SELECT * FROM trainers WHERE username='$u' AND password='$p'");
        if($res && $res->num_rows > 0){
            $_SESSION['trainer'] = $res->fetch_assoc();
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><title>Trainer Login</title><script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
      <div class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-xl font-bold mb-4 text-center">Trainer Login</h2>
        <?php if($error) echo '<p class="text-red-500 mb-4 text-sm">'.$error.'</p>'; ?>
        <form method="post">
          <div class="mb-4"><label class="block text-sm font-bold mb-2">Username</label><input type="text" name="login_username" class="w-full border p-2 rounded" required></div>
          <div class="mb-4"><label class="block text-sm font-bold mb-2">Password</label><input type="password" name="login_password" class="w-full border p-2 rounded" required></div>
          <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded font-bold">Login</button>
        </form>
        <div class="mt-4 text-center"><a href="../index.php" class="text-gray-500 text-sm">Back to Home</a></div>
      </div>
    </body>
    </html>
    <?php exit;
}

// Inline Department Selection
if(empty($_SESSION['selected_department'])){
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dept_id'])){
        $_SESSION['selected_department'] = (int)$_POST['dept_id'];
        header("Location: dashboard.php"); exit;
    }
    $depts = $conn->query("SELECT * FROM departments");
    ?>
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><title>Select Department</title><script src="https://cdn.tailwindcss.com"></script></head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
      <div class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-xl font-bold mb-4 text-center">Select Department</h2>
        <form method="post"><div class="mb-4"><label class="block text-sm font-bold mb-2">Department</label><select name="dept_id" class="w-full border p-2 rounded" required><option value="">-- Select --</option><?php while($d = $depts->fetch_assoc()) echo '<option value="'.$d['id'].'">'.htmlspecialchars($d['name']).'</option>'; ?></select></div><button type="submit" class="w-full bg-blue-600 text-white p-2 rounded font-bold">Continue</button></form>
        <div class="mt-4 text-center"><a href="logout.php" class="text-red-500 text-sm">Logout</a></div>
      </div>
    </body></html>
    <?php exit;
}

$trainer = $_SESSION['trainer'];
$dept_id = (int)$_SESSION['selected_department'];

// Get department name
$dept_res = $conn->query("SELECT name FROM departments WHERE id=$dept_id");
$dept_name = $dept_res->fetch_assoc()['name'];

// classes in department
$classes = $conn->query("SELECT DISTINCT c.* FROM classes c JOIN class_units cu ON c.id=cu.class_id WHERE c.department_id=$dept_id AND cu.trainer_id=".(int)$trainer['id']." ORDER BY c.name");
$class_list = [];
while($c = $classes->fetch_assoc()){
  $class_list[] = $c;
}

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : (isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0);
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : (isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0);
$week = isset($_GET['week']) ? (int)$_GET['week'] : (isset($_POST['week']) ? (int)$_POST['week'] : 1);
$lesson = isset($_GET['lesson']) ? $conn->real_escape_string($_GET['lesson']) : (isset($_POST['lesson']) ? $conn->real_escape_string($_POST['lesson']) : 'L1');

$units_list = [];
$students_list = [];
if($class_id){
  $units = $conn->query("SELECT cu.*, u.code, u.name FROM class_units cu JOIN units u ON u.id=cu.unit_id WHERE cu.class_id=$class_id AND cu.trainer_id=".(int)$trainer['id']);
  while($u = $units->fetch_assoc()){
    $units_list[] = $u;
  }
  $students = $conn->query("SELECT * FROM students WHERE class_id=$class_id");
  while($s = $students->fetch_assoc()){
    $students_list[] = $s;
  }
}

// Check if attendance submitted
$attendance_submitted = false;
if($unit_id && $week && $lesson){
    $chk = $conn->query("SELECT id FROM attendance WHERE unit_id=$unit_id AND trainer_id=".(int)$trainer['id']." AND week=$week AND lesson='".$lesson."' LIMIT 1");
    if($chk && $chk->num_rows > 0) $attendance_submitted = true;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Student Attendance Capture</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; display: none; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .message.success { color: green; margin-top: 10px; }
    .message.error { color: red; margin-top: 10px; }
    .checkbox-present, .checkbox-absent { -webkit-appearance: none; -moz-appearance: none; appearance: none; width: 2rem; height: 2rem; border: 5px solid #000; border-radius: 0.25rem; display: inline-block; cursor: pointer; position: relative; background-color: transparent; }
    .checkbox-present:checked { background-color: #28a745; border-color: #000; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E"); background-size: 60%; background-position: center; background-repeat: no-repeat; }
    .checkbox-absent:checked { background-color: #dc3545; border-color: #000; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cline x1='18' y1='6' x2='6' y2='18'%3E%3C/line%3E%3Cline x1='6' y1='6' x2='18' y2='18'%3E%3C/line%3E%3C/svg%3E"); background-size: 60%; background-position: center; background-repeat: no-repeat; }
    #studentListContainer { background-color: #f9fafb; padding: 1rem; border-radius: 0.5rem; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1.5rem; max-height: none; overflow-y: visible; }
    #studentList table { width: 100%; border-collapse: collapse; font-size: clamp(0.8rem, 2vw, 1rem); }
    #studentList th, #studentList td { border: 1px solid #e5e7eb; padding: 0.5rem; text-align: left; word-wrap: break-word; }
    .checkbox-present, .checkbox-absent { width: clamp(1.5rem, 4vw, 2rem); height: clamp(1.5rem, 4vw, 2rem); }
  </style>
</head>
<body class="bg-gray-100 p-6">
  <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-lg">

    <div class="flex justify-between items-center mb-6 border-b pb-4">
      <a href="select_department.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 text-sm font-bold">← Change Dept</a>
      <span class="text-gray-600 hidden md:inline">Logged in as: <?php echo h($trainer['name']); ?></span>
      <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm font-bold">Logout</a>
    </div>

    <div class="text-center mb-6">
      <div style="background: white; border-radius: 16px; padding: 12px; display: inline-block; box-shadow: 0 4px 15px rgba(0,0,0,0.15); margin-bottom: 15px;">
        <img src="../assets/SMARTLOGO.svg" alt="SUAS Logo" style="height: 70px; width: 70px; object-fit: contain;">
      </div>
      <h1 class="text-xl font-bold uppercase text-indigo-700">SMART UNIT ATTENDANCE SYSTEM (SUAS)</h1>
      <h2 class="text-md font-semibold text-purple-600"><?php echo h($dept_name); ?> DEPARTMENT</h2>
      <h3 class="text-md font-semibold text-gray-700">UNIT ATTENDANCE REGISTER</h3>
      <p class="text-sm text-gray-500 mt-1">
        <span class="font-semibold"><?= htmlspecialchars($_SESSION['institution_name'] ?? ''); ?></span>
        <?php if(!empty($_SESSION['institution_code'])): ?> (<?= htmlspecialchars($_SESSION['institution_code']); ?>) <?php endif; ?>
      </p>
      <p id="classTitle" class="text-gray-600 mt-2">CLASS: <?php echo ($class_id ? h($class_list[array_search($class_id, array_column($class_list,'id'))]['name']) : ''); ?></p>
    </div>

    <div id="message" class="message hidden text-center font-bold mb-4"></div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="form-group">
        <label for="classSelect" class="font-medium">Select Class:</label>
        <select id="classSelect" class="block w-full rounded-lg border px-2 py-1" onchange="onFilterChange()">
          <option value="">-- Choose Class --</option>
          <?php foreach($class_list as $c){ echo '<option value="'.h($c['id']).'" '.($class_id==$c['id']?'selected':'').'>'.h($c['name']).'</option>'; } ?>
        </select>
      </div>
      <div class="form-group">
        <label for="unitSelect" class="font-medium">Select Unit:</label>
        <select id="unitSelect" class="block w-full rounded-lg border px-2 py-1" onchange="onFilterChange()">
          <option value="">-- Choose Unit --</option>
          <?php foreach($units_list as $u){ echo '<option value="'.h($u['unit_id']).'" '.($unit_id==$u['unit_id']?'selected':'').'>'.h($u['code'].' - '.$u['name']).'</option>'; } ?>
        </select>
      </div>
      <div class="form-group">
        <label for="weekSelect" class="font-medium">Select Week:</label>
        <select id="weekSelect" class="block w-full rounded-lg border px-2 py-1" onchange="onFilterChange()">
          <option value="">-- Choose Week --</option>
          <?php for($w=1;$w<=52;$w++){ echo '<option value="'.$w.'" '.($week==$w?'selected':'').'>Week '.$w.'</option>'; } ?>
        </select>
      </div>
      <div class="form-group">
        <label for="lessonSelect" class="font-medium">Select Lesson:</label>
        <select id="lessonSelect" class="block w-full rounded-lg border px-2 py-1" onchange="onFilterChange()">
          <option value="">-- Choose Lesson --</option>
          <option value="L1" <?php echo $lesson=='L1'?'selected':''; ?>>L1</option>
          <option value="L2" <?php echo $lesson=='L2'?'selected':''; ?>>L2</option>
          <option value="L3" <?php echo $lesson=='L3'?'selected':''; ?>>L3</option>
          <option value="L4" <?php echo $lesson=='L4'?'selected':''; ?>>L4</option>
        </select>
      </div>
    </div>

    <div id="studentListContainer" class="bg-gray-50 p-4 rounded-lg shadow-inner mb-6">
      <h2 class="text-xl font-semibold text-gray-700 mb-4">Student List</h2>
      <div id="studentList">
        <?php if(empty($students_list)): ?>
          <p class="text-gray-500 text-center py-8">No students found for this class.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr class="bg-blue-600 text-white">
                <th class="px-4 py-2">Admission No.</th>
                <th class="px-4 py-2">Student Name</th>
                <th class="px-4 py-2">Present</th>
                <th class="px-4 py-2">Absent</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($students_list as $s): ?>
              <tr>
                <td class="px-4 py-2"><?php echo h($s['admission_number']); ?></td>
                <td class="px-4 py-2"><?php echo h($s['full_name']); ?></td>
                <td class="px-4 py-2 text-center">
                  <input type="checkbox" id="present_<?php echo $s['id']; ?>" class="checkbox-present" data-student-id="<?php echo $s['id']; ?>" onclick="toggleCheckbox(<?php echo $s['id']; ?>, 'present')">
                </td>
                <td class="px-4 py-2 text-center">
                  <input type="checkbox" id="absent_<?php echo $s['id']; ?>" class="checkbox-absent" data-student-id="<?php echo $s['id']; ?>" onclick="toggleCheckbox(<?php echo $s['id']; ?>, 'absent')">
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-4">
      <button type="button" onclick="viewAttendance()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">View Attendance</button>
      <button type="button" onclick="downloadAttendance()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">Download Attendance PDF</button>
      <button type="button" onclick="window.print()" class="bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800">Print Register</button>
      <button id="submitBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 <?php echo $attendance_submitted ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo $attendance_submitted ? 'disabled' : ''; ?>>
        <?php echo $attendance_submitted ? 'Submitted' : 'Submit Attendance'; ?>
      </button>
      <div id="loadingSpinner" class="loading-spinner"></div>
    </div>
  </div>

  <script>
    // Basic toggle: only one checkbox per row can be active
    function toggleCheckbox(id, type){
      var p = document.getElementById('present_'+id);
      var a = document.getElementById('absent_'+id);
      if(type === 'present'){
        if(p.checked){ a.checked = false; }
      } else {
        if(a.checked){ p.checked = false; }
      }
    }

    function viewAttendance(){
      var c = document.getElementById('classSelect').value;
      var u = document.getElementById('unitSelect').value;
      var w = document.getElementById('weekSelect').value;
      var l = document.getElementById('lessonSelect').value;
      if(!c || !u || !w || !l){
        alert('Please select Class, Unit, Week and Lesson first.'); return;
      }
      window.location = 'view_attendance.php?class_id='+c+'&unit_id='+u+'&week='+w+'&lesson='+encodeURIComponent(l);
    }

    function downloadAttendance(){
      var c = document.getElementById('classSelect').value;
      var u = document.getElementById('unitSelect').value;
      var w = document.getElementById('weekSelect').value;
      var l = document.getElementById('lessonSelect').value;
      if(!c || !u || !w || !l){
        alert('Please select Class, Unit, Week and Lesson first.'); return;
      }
      window.open('download_attendance_pdf.php?class_id='+c+'&unit_id='+u+'&week='+w+'&lesson='+encodeURIComponent(l), '_blank');
    }

    document.getElementById('submitBtn').addEventListener('click', function(){
      var classId = document.getElementById('classSelect').value;
      var unitId = document.getElementById('unitSelect').value;
      var week = document.getElementById('weekSelect').value;
      var lesson = document.getElementById('lessonSelect').value;
      var msg = document.getElementById('message');
      var spinner = document.getElementById('loadingSpinner');

      if(!classId || !unitId || !week || !lesson){
        msg.textContent = 'Please select Class, Unit, Week and Lesson.'; msg.className = 'message error'; return;
      }

      // build form data similar to old server expectations
      var form = new FormData();
      form.append('class_id', classId);
      form.append('unit_id', unitId);
      form.append('week', week);
      form.append('lesson', lesson);

      var any = false;
      <?php foreach($students_list as $s): ?>
        (function(){
          var sid = '<?php echo $s['id']; ?>';
          var p = document.getElementById('present_'+sid);
          var a = document.getElementById('absent_'+sid);
          if(p && p.checked){ form.append('status['+sid+']', 'present'); any = true; }
          else if(a && a.checked){ form.append('status['+sid+']', 'absent'); any = true; }
        })();
      <?php endforeach; ?>

      if(!any){ msg.textContent = 'No attendance recorded.'; msg.className = 'message error'; return; }

      spinner.style.display = 'block';
      fetch('submit_attendance.php', { method: 'POST', body: form })
        .then(r => r.json().catch(()=>({ success:false, message: 'Invalid response' })))
        .then(resp => {
          spinner.style.display = 'none';
          if(resp.success){ msg.textContent = resp.message || 'Attendance saved'; msg.className = 'message success'; }
          else { msg.textContent = resp.message || 'Error saving attendance'; msg.className = 'message error'; }
        }).catch(err => { spinner.style.display = 'none'; msg.textContent = 'Network error'; msg.className = 'message error'; });
    });

    function onFilterChange(){
      // submit GET form by building URL
      var c = document.getElementById('classSelect').value;
      var u = document.getElementById('unitSelect').value;
      var w = document.getElementById('weekSelect').value;
      var l = document.getElementById('lessonSelect').value;
      var params = new URLSearchParams();
      if(c) params.set('class_id', c);
      if(u) params.set('unit_id', u);
      if(w) params.set('week', w);
      if(l) params.set('lesson', l);
      window.location = 'dashboard.php?'+params.toString();
    }
  </script>
</body>
</html>
