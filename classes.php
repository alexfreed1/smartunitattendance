<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

// Handle Add Class
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $dept_id = (int)$_POST['department_id'];
    
    if($name && $dept_id) {
        $chk = $conn->query("SELECT id FROM classes WHERE name='$name'");
        if($chk->num_rows > 0){
            $error = "Class already exists.";
        } else {
            $conn->query("INSERT INTO classes (name, department_id) VALUES ('$name', $dept_id)");
        }
    }
}

// Handle CSV Import
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    if($_FILES['csv_file']['error'] == 0){
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Format: Class Name, Department Name
            if(count($data) >= 2) {
                $name = $conn->real_escape_string(trim($data[0]));
                $dept_name = $conn->real_escape_string(trim($data[1]));
                
                // Find department id
                $dr = $conn->query("SELECT id FROM departments WHERE name='$dept_name'");
                if ($dr && $dr->num_rows > 0) {
                    $dept_id = $dr->fetch_assoc()['id'];
                    // Insert if not exists
                    $chk = $conn->query("SELECT id FROM classes WHERE name='$name'");
                    if($chk->num_rows == 0){
                        $conn->query("INSERT INTO classes (name, department_id) VALUES ('$name', $dept_id)");
                        $count++;
                    }
                }
            }
        }
        fclose($handle);
        $success = "Imported $count classes successfully.";
    }
}

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM classes WHERE id=$id");
    header("Location: classes.php"); exit;
}

// Fetch departments for dropdowns
$depts_res = $conn->query("SELECT * FROM departments ORDER BY name");
$depts_list = [];
while($d = $depts_res->fetch_assoc()){
    $depts_list[] = $d;
}

// Filter Logic
$filter_dept = isset($_GET['filter_dept']) ? (int)$_GET['filter_dept'] : 0;
$where_clause = $filter_dept ? "WHERE c.department_id = $filter_dept" : "";

$classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c LEFT JOIN departments d ON c.department_id = d.id $where_clause ORDER BY c.name");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Classes</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
    .header { background: white; padding: 20px; border-bottom: 3px solid #1e5a9f; display: flex; justify-content: space-between; align-items: center; }
    .header h2 { margin: 0; color: #1e5a9f; }
    .header a { color: #1e5a9f; text-decoration: none; font-weight: bold; }
    .container { max-width: 1200px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    h3 { color: #1e5a9f; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .form-section { background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
    label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
    input[type="text"], select { width: 100%; max-width: 400px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; display: block; }
    button { padding: 10px 20px; background-color: #1e5a9f; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    button:hover { background-color: #154070; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th { background-color: #1e5a9f; color: white; padding: 12px; text-align: left; }
    table td { padding: 12px; border-bottom: 1px solid #ddd; }
    table tr:hover { background-color: #f9f9f9; }
    .error { color: #d32f2f; background-color: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
  </style>
</head>
<body>
<div class="header">
  <h2><i class="fas fa-school"></i> Manage Classes</h2>
  <a href="welcome.php">Back to Dashboard</a>
</div>

<div class="container">
    <?php if(isset($error)) echo '<div class="error">'.h($error).'</div>'; ?>
    <?php if(isset($success)) echo '<div class="success">'.h($success).'</div>'; ?>

    <div class="form-section">
        <h3>Add New Class</h3>
        <form method="post">
            <label>Class Name:</label> <input type="text" name="name" required>
            <label>Department:</label> 
            <select name="department_id" required>
                <option value="">Select Department</option>
                <?php foreach($depts_list as $d): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo h($d['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add_class">Add Class</button>
        </form>
    </div>

    <div class="form-section">
        <h3>Import Classes (CSV)</h3>
        <form method="post" enctype="multipart/form-data">
            <p style="margin-bottom: 10px; color: #666;"><strong>Format:</strong> Class Name, Department Name</p>
            <input type="file" name="csv_file" required accept=".csv">
            <button type="submit" name="import_csv">Import CSV</button>
        </form>
    </div>

    <div class="form-section" style="background-color: #e3f2fd;">
        <h3>Filter Classes</h3>
        <form method="get" style="display: flex; align-items: flex-start; gap: 15px; flex-wrap: wrap;">
            <div style="flex-grow: 1; max-width: 300px;">
                <label style="display: block; margin-bottom: 5px;">By Department:</label>
                <select name="filter_dept" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                    <option value="">All Departments</option>
                    <?php foreach($depts_list as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($filter_dept == $d['id']) ? 'selected' : ''; ?>><?php echo h($d['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" style="padding: 8px 15px; margin-top: 20px;">Filter</button>
        </form>
    </div>

    <h3>Existing Classes</h3>
    <table>
        <thead>
            <tr><th>Class Name</th><th>Department</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php while($c = $classes->fetch_assoc()): ?>
            <tr>
                <td><?php echo h($c['name']); ?></td>
                <td><?php echo h($c['dept_name']); ?></td>
                <td><a href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Are you sure?');" style="color: #d32f2f;">Delete</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>