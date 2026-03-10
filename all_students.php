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
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Fetch departments with student count
$departments = $conn->query("SELECT d.id, d.name, COUNT(s.id) as student_count 
                              FROM departments d 
                              LEFT JOIN classes c ON c.department_id = d.id 
                              LEFT JOIN students s ON s.class_id = c.id 
                              GROUP BY d.id, d.name 
                              ORDER BY d.name");

// Fetch classes with student count (filtered by department if selected)
$classes_query = "SELECT c.id, c.name, c.department_id, d.name as dept_name, 
                         COUNT(s.id) as student_count 
                  FROM classes c 
                  LEFT JOIN departments d ON d.id = c.department_id 
                  LEFT JOIN students s ON s.class_id = c.id ";
if($department_id) {
    $classes_query .= "WHERE c.department_id = $department_id ";
}
$classes_query .= "GROUP BY c.id, c.name, c.department_id, d.name ORDER BY c.name";
$classes = $conn->query($classes_query);

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
if($search) {
    $students_query .= "AND (s.full_name LIKE '%$search%' OR s.admission_number LIKE '%$search%') ";
}
$students_query .= "ORDER BY s.admission_number";
$students = $conn->query($students_query);

// Get total counts
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$total_classes = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>All Students - SUAS</title>
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
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }
    .filter-item label {
      display: block;
      font-weight: 600;
      color: #374151;
      margin-bottom: 8px;
      font-size: 14px;
    }
    .filter-item label i {
      color: #2F6FED;
      margin-right: 8px;
    }
    .filter-item select,
    .filter-item input {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #E5E7EB;
      border-radius: 10px;
      font-size: 14px;
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
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      transition: all 0.3s;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
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
    .dept-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    .dept-card {
      background: linear-gradient(135deg, #F4F7FC 0%, #E8ECF5 100%);
      padding: 20px;
      border-radius: 12px;
      border-left: 4px solid #2F6FED;
    }
    .dept-card h4 {
      color: #2B2B2B;
      margin-bottom: 10px;
      font-size: 16px;
    }
    .dept-card .count {
      font-size: 28px;
      font-weight: 700;
      color: #2F6FED;
    }
    .dept-card .label {
      font-size: 12px;
      color: #6B7280;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      border-radius: 12px;
      overflow: hidden;
    }
    table th {
      background: linear-gradient(90deg, #2F6FED, #3C8CE7);
      color: white;
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
    }
    table td {
      padding: 15px;
      border-bottom: 1px solid #E5E7EB;
      font-size: 14px;
    }
    table tr:last-child td { border-bottom: none; }
    table tr:hover td { background: #F9FAFB; }
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
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    .badge-blue {
      background: #E0E7FF;
      color: #2F6FED;
    }
  </style>
</head>
<body>
<div class="header">
  <h2><i class="fas fa-user-graduate"></i> All Students</h2>
  <a href="welcome.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="container">
    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="number"><?php echo $total_students; ?></div>
            <div class="label">Total Students</div>
        </div>
        <div class="stat-box">
            <div class="number"><?php echo $total_departments; ?></div>
            <div class="label">Departments</div>
        </div>
        <div class="stat-box">
            <div class="number"><?php echo $total_classes; ?></div>
            <div class="label">Classes</div>
        </div>
    </div>

    <!-- Department Statistics -->
    <div class="card">
        <h3><i class="fas fa-chart-pie"></i> Students by Department</h3>
        <div class="dept-grid">
            <?php 
            $departments->data_seek(0);
            while($dept = $departments->fetch_assoc()): 
            ?>
            <div class="dept-card">
                <h4><?php echo htmlspecialchars($dept['name']); ?></h4>
                <div class="count"><?php echo $dept['student_count']; ?></div>
                <div class="label">students</div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Class Statistics -->
    <div class="card">
        <h3><i class="fas fa-school"></i> Students by Class</h3>
        <div class="dept-grid">
            <?php 
            $classes->data_seek(0);
            while($class = $classes->fetch_assoc()): 
            ?>
            <div class="dept-card" style="border-left-color: #22C55E;">
                <h4>
                    <?php echo htmlspecialchars($class['name']); ?>
                    <span class="badge badge-blue"><?php echo htmlspecialchars($class['dept_name']); ?></span>
                </h4>
                <div class="count"><?php echo $class['student_count']; ?></div>
                <div class="label">students</div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Students List -->
    <div class="card">
        <h3><i class="fas fa-list"></i> Students List</h3>
        
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
                            <?php echo htmlspecialchars($d['name']); ?> (<?php echo $d['student_count']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label><i class="fas fa-school"></i>Class</label>
                    <select name="class_id" onchange="this.form.submit()">
                        <option value="">All Classes</option>
                        <?php 
                        $classes->data_seek(0);
                        while($c = $classes->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($class_id == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo $c['student_count']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-item">
                    <label><i class="fas fa-search"></i>Search</label>
                    <input type="text" name="search" placeholder="Name or Admission No..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="filter-item" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Actions Bar -->
        <div class="actions-bar">
            <div>
                <span style="color: #6B7280; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> Showing <?php echo $students->num_rows; ?> of <?php echo $total_students; ?> students
                </span>
            </div>
            <div>
                <a href="download_class_list.php?department_id=<?php echo $department_id; ?>&class_id=<?php echo $class_id; ?>" 
                   class="btn btn-success" target="_blank">
                    <i class="fas fa-download"></i> Download Class List
                </a>
            </div>
        </div>
        
        <!-- Students Table -->
        <?php if($students->num_rows == 0): ?>
            <div class="no-records">
                <i class="fas fa-inbox"></i>
                <p>No students found matching your filters.</p>
            </div>
        <?php else: ?>
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
                    $num = 1;
                    while($s = $students->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $num++; ?></td>
                        <td><?php echo htmlspecialchars($s['admission_number']); ?></td>
                        <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($s['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($s['dept_name']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
