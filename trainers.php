<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

// Handle Add
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trainer'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $dept_id = (int)$_POST['department_id'];
    
    if($name && $username && $password && $dept_id) {
        $conn->query("INSERT INTO trainers (name, username, password, department_id) VALUES ('$name', '$username', '$password', $dept_id)");
        $success = "Trainer added successfully.";
    }
}

// Handle CSV Import
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    if($_FILES['csv_file']['error'] == 0){
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Format: Name, Username, Password, Department Name
            if(count($data) >= 4) {
                $name = $conn->real_escape_string(trim($data[0]));
                $username = $conn->real_escape_string(trim($data[1]));
                $password = trim($data[2]);
                $dept_name = $conn->real_escape_string(trim($data[3]));

                if(empty($username)) continue;

                $dr = $conn->query("SELECT id FROM departments WHERE name='$dept_name'");
                if ($dr && $dr->num_rows > 0) {
                    $dept_id = $dr->fetch_assoc()['id'];
                    $chk = $conn->query("SELECT id FROM trainers WHERE username='$username'");
                    if($chk->num_rows == 0){
                        $conn->query("INSERT INTO trainers (name, username, password, department_id) VALUES ('$name', '$username', '$password', $dept_id)");
                        $count++;
                    }
                }
            }
        }
        fclose($handle);
        $success = "Imported $count trainers successfully.";
    } else {
        $error = "Error uploading file.";
    }
}

// Handle Delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM trainers WHERE id=$id");
    header("Location: trainers.php"); exit;
}

$departments = $conn->query("SELECT * FROM departments");

$search = '';
$where = '';
if(isset($_GET['search']) && !empty($_GET['search'])){
    $search = $conn->real_escape_string($_GET['search']);
    $where = " WHERE t.name LIKE '%$search%' OR t.username LIKE '%$search%' OR d.name LIKE '%$search%' ";
}
$trainers = $conn->query("SELECT t.*, d.name as dept_name FROM trainers t LEFT JOIN departments d ON t.department_id = d.id $where ORDER BY t.name");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Trainers</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
    .top-bar { background-color: #1e5a9f; color: white; padding: 15px 20px; text-align: right; }
    .top-bar a { color: #ffd700; text-decoration: none; margin-left: 20px; }
    .header { background: white; padding: 20px; border-bottom: 3px solid #1e5a9f; }
    .header a { color: #1e5a9f; text-decoration: none; font-weight: bold; }
    .container { max-width: 1200px; margin: 20px auto; background: white; padding: 30px; }
    h2 { color: #1e5a9f; margin-bottom: 20px; border-bottom: 2px solid #1e5a9f; padding-bottom: 10px; }
    .form-section { margin-bottom: 40px; background-color: #f9f9f9; padding: 20px; border-radius: 8px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
    .form-group input, .form-group select { width: 100%; max-width: 400px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    .form-group button { padding: 10px 20px; background-color: #1e5a9f; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .form-group button:hover { background-color: #154070; }
    .success { color: #155724; background-color: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th { background-color: #1e5a9f; color: white; padding: 12px; text-align: left; }
    table td { padding: 12px; border-bottom: 1px solid #ddd; }
    table tr:hover { background-color: #f9f9f9; }
  </style>
</head>
<body>
  <div class="top-bar">
    <a href="dashboard.php">Dashboard</a> | <a href="logout.php">Logout</a>
  </div>

  <div class="header">
    <h1><i class="fas fa-chalkboard-teacher"></i> Manage Trainers</h1>
  </div>

  <div class="container">
    <?php if(!empty($success)) echo '<div class="success">'.$success.'</div>'; ?>
    <?php if(!empty($error)) echo '<div class="error">'.$error.'</div>'; ?>

    <h2>Add New Trainer</h2>
    <div class="form-section">
      <form method="post">
        <div class="form-group">
          <label>Name:</label>
          <input type="text" name="name" required>
        </div>
        <div class="form-group">
          <label>Username:</label>
          <input type="text" name="username" required>
        </div>
        <div class="form-group">
          <label>Password:</label>
          <input type="text" name="password" required>
        </div>
        <div class="form-group">
          <label>Department:</label>
          <select name="department_id" required>
              <option value="">-- Choose Department --</option>
              <?php while($d = $departments->fetch_assoc()): ?>
                  <option value="<?php echo $d['id']; ?>"><?php echo h($d['name']); ?></option>
              <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <button type="submit" name="add_trainer">Add Trainer</button>
        </div>
      </form>
    </div>

    <div class="form-section">
        <h3>Import Trainers (CSV)</h3>
        <form method="post" enctype="multipart/form-data">
            <p style="margin-bottom: 10px; color: #666;"><strong>Format:</strong> Name, Username, Password, Department Name</p>
            <input type="file" name="csv_file" required accept=".csv">
            <button type="submit" name="import_csv">Import CSV</button>
        </form>
    </div>

    <h2>Existing Trainers</h2>
    <form method="get" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="search" placeholder="Search by Name, Username or Dept..." value="<?php echo h($search); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; flex-grow: 1; max-width: 300px;">
        <button type="submit" style="padding: 10px 20px; background-color: #1e5a9f; color: white; border: none; border-radius: 4px; cursor: pointer;">Search</button>
        <?php if($search): ?>
            <a href="trainers.php" style="padding: 10px 20px; background-color: #666; color: white; text-decoration: none; border-radius: 4px; display: inline-block; line-height: normal;">Clear</a>
        <?php endif; ?>
    </form>

    <table>
      <thead>
        <tr><th>Name</th><th>Username</th><th>Department</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php while($t = $trainers->fetch_assoc()): ?>
        <tr>
            <td><?php echo h($t['name']); ?></td>
            <td><?php echo h($t['username']); ?></td>
            <td><?php echo h($t['dept_name']); ?></td>
            <td><a href="?delete=<?php echo $t['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
