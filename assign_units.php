<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$filter_class = isset($_GET['filter_class']) ? (int)$_GET['filter_class'] : 0;
$filter_trainer = isset($_GET['filter_trainer']) ? (int)$_GET['filter_trainer'] : 0;

$error = '';
$success = '';

// Handle Add Assignment
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $class_id = (int)$_POST['class_id'];
    $unit_id = (int)$_POST['unit_id'];
    $trainer_id = (int)$_POST['trainer_id'];

    if($class_id && $unit_id && $trainer_id) {
        $chk = $conn->query("SELECT id FROM class_units WHERE class_id=$class_id AND unit_id=$unit_id AND trainer_id=$trainer_id");
        if($chk->num_rows > 0){
            $error = "This assignment already exists.";
        } else {
            $conn->query("INSERT INTO class_units (class_id, unit_id, trainer_id) VALUES ($class_id, $unit_id, $trainer_id)");
            $success = "Assignment created successfully.";
        }
    } else {
        $error = "Please select Class, Unit, and Trainer.";
    }
}

// Handle CSV Import
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    if($_FILES['csv_file']['error'] == 0){
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if(count($data) >= 3) {
                $className = $conn->real_escape_string(trim($data[0]));
                $unitCode = $conn->real_escape_string(trim($data[1]));
                $trainerUser = $conn->real_escape_string(trim($data[2]));

                $cRes = $conn->query("SELECT id FROM classes WHERE name='$className'");
                $uRes = $conn->query("SELECT id FROM units WHERE code='$unitCode'");
                $tRes = $conn->query("SELECT id FROM trainers WHERE username='$trainerUser'");

                if($cRes && $cRes->num_rows > 0 && $uRes && $uRes->num_rows > 0 && $tRes && $tRes->num_rows > 0){
                    $cid = $cRes->fetch_assoc()['id'];
                    $uid = $uRes->fetch_assoc()['id'];
                    $tid = $tRes->fetch_assoc()['id'];

                    $chk = $conn->query("SELECT id FROM class_units WHERE class_id=$cid AND unit_id=$uid AND trainer_id=$tid");
                    if($chk->num_rows == 0){
                        $conn->query("INSERT INTO class_units (class_id, unit_id, trainer_id) VALUES ($cid, $uid, $tid)");
                        $count++;
                    }
                }
            }
        }
        fclose($handle);
        $success = "Imported $count assignments successfully.";
    }
}

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM class_units WHERE id=$id");
    header("Location: assign_units.php?department_id=$department_id"); exit;
}

// Fetch departments
$departments = $conn->query("SELECT * FROM departments ORDER BY name");

// Fetch Data for Dropdowns (filtered by department if selected)
$classes_query = "SELECT * FROM classes ".($department_id ? "WHERE department_id = $department_id" : "")." ORDER BY name";
$classes_res = $conn->query($classes_query);
$classes = []; while($row = $classes_res->fetch_assoc()) $classes[] = $row;

$units_res = $conn->query("SELECT * FROM units ORDER BY code");
$units = []; while($row = $units_res->fetch_assoc()) $units[] = $row;

$trainers_res = $conn->query("SELECT * FROM trainers ORDER BY name");
$trainers = []; while($row = $trainers_res->fetch_assoc()) $trainers[] = $row;

// Filter Assignments
$where = [];
if($department_id) $where[] = "c.department_id = $department_id";
if($filter_class) $where[] = "cu.class_id = $filter_class";
if($filter_trainer) $where[] = "cu.trainer_id = $filter_trainer";
$where_sql = count($where) ? "WHERE ".implode(' AND ', $where) : "";

// Fetch Existing Assignments
$assignments = $conn->query("SELECT cu.id, c.name as class_name, c.department_id, u.code, u.name as unit_name, t.name as trainer_name
                             FROM class_units cu
                             JOIN classes c ON cu.class_id = c.id
                             JOIN units u ON cu.unit_id = u.id
                             JOIN trainers t ON cu.trainer_id = t.id
                             $where_sql
                             ORDER BY c.name, u.code");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Assign Units - SUAS</title>
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
      max-width: 1400px;
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
      margin-bottom: 25px;
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
    input[type="file"] {
      width: 100%;
      padding: 12px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Poppins', sans-serif;
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
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
    }
    .error {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #dc2626;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }
    .error i { margin-right: 10px; font-size: 18px; }
    .success {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #059669;
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }
    .success i { margin-right: 10px; font-size: 18px; }
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
    .delete-link {
      color: #ef4444;
      text-decoration: none;
      font-weight: 600;
      padding: 6px 12px;
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      border-radius: 8px;
      transition: all 0.3s;
    }
    .delete-link:hover {
      background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
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
  <h2><i class="fas fa-book-open"></i> Assign Units</h2>
  <a href="welcome.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="container">
    <?php if($error) echo '<div class="error"><i class="fas fa-exclamation-circle"></i>'.h($error).'</div>'; ?>
    <?php if($success) echo '<div class="success"><i class="fas fa-check-circle"></i>'.h($success).'</div>'; ?>

    <!-- Department Selection -->
    <div class="form-section">
        <h3><i class="fas fa-building"></i> Step 1: Select Department</h3>
        <form method="get" style="max-width: 400px;">
            <label><i class="fas fa-building"></i>Department</label>
            <select name="department_id" required onchange="this.form.submit()">
                <option value="">-- Select Department --</option>
                <?php 
                $departments->data_seek(0);
                while($d = $departments->fetch_assoc()) 
                    echo "<option value='{$d['id']}' ".($department_id==$d['id']?'selected':'').">".h($d['name'])."</option>"; 
                ?>
            </select>
        </form>
    </div>

    <?php if($department_id): ?>
    <!-- New Assignment Form -->
    <div class="form-section">
        <h3><i class="fas fa-plus-circle"></i> Step 2: Create New Assignment</h3>
        <form method="post">
            <div class="form-grid">
                <div>
                    <label><i class="fas fa-school"></i>Class</label>
                    <select name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo h($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><i class="fas fa-book"></i>Unit</label>
                    <select name="unit_id" required>
                        <option value="">-- Select Unit --</option>
                        <?php foreach($units as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo h($u['code'].' - '.$u['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><i class="fas fa-chalkboard-teacher"></i>Trainer</label>
                    <select name="trainer_id" required>
                        <option value="">-- Select Trainer --</option>
                        <?php foreach($trainers as $t): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo h($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" name="assign" style="width: 100%;">
                        <i class="fas fa-check-circle"></i> Assign Unit
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Import CSV Form -->
    <div class="form-section">
        <h3><i class="fas fa-file-csv"></i> Import Assignments (CSV)</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <div>
                    <label><i class="fas fa-file-upload"></i>CSV File</label>
                    <input type="file" name="csv_file" required accept=".csv">
                    <p style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> Format: Class Name, Unit Code, Trainer Username
                    </p>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" name="import_csv" style="width: 100%;">
                        <i class="fas fa-upload"></i> Import CSV
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Filter Section -->
    <div class="form-section" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);">
        <h3><i class="fas fa-filter"></i> Filter Assignments</h3>
        <form method="get">
            <input type="hidden" name="department_id" value="<?php echo $department_id; ?>">
            <div class="form-grid">
                <div>
                    <label><i class="fas fa-school"></i>By Class</label>
                    <select name="filter_class">
                        <option value="">All Classes</option>
                        <?php foreach($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($filter_class == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo h($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label><i class="fas fa-chalkboard-teacher"></i>By Trainer</label>
                    <select name="filter_trainer">
                        <option value="">All Trainers</option>
                        <?php foreach($trainers as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($filter_trainer == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo h($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" style="width: 100%;">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Current Assignments Table -->
    <h3><i class="fas fa-list"></i> Current Assignments</h3>
    <?php if($assignments->num_rows == 0): ?>
        <div class="no-records">
            <i class="fas fa-inbox"></i>
            <p>No assignments found for this department.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Unit</th>
                    <th>Trainer</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $assignments->fetch_assoc()): ?>
                <tr>
                    <td><?php echo h($row['class_name']); ?></td>
                    <td><?php echo h($row['code'].' - '.$row['unit_name']); ?></td>
                    <td><?php echo h($row['trainer_name']); ?></td>
                    <td>
                        <a href="?delete=<?php echo $row['id']; ?>&department_id=<?php echo $department_id; ?>" 
                           class="delete-link" 
                           onclick="return confirm('Remove this assignment?');">
                            <i class="fas fa-trash"></i> Remove
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php else: ?>
    <div class="no-records">
        <i class="fas fa-arrow-up"></i>
        <p>Please select a department first to assign units</p>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
