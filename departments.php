<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dept'])) {
    $name = $conn->real_escape_string($_POST['name']);
    if($name) {
        $chk = $conn->query("SELECT id FROM departments WHERE name='$name'");
        if($chk->num_rows > 0){
            $error = "Department already exists.";
        } else {
            $conn->query("INSERT INTO departments (name) VALUES ('$name')");
        }
    }
}

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM departments WHERE id=$id");
    header("Location: departments.php"); exit;
}

$depts = $conn->query("SELECT * FROM departments ORDER BY name");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Departments</title>
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
    input[type="text"] { width: 100%; max-width: 400px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; display: block; }
    button { padding: 10px 20px; background-color: #1e5a9f; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    button:hover { background-color: #154070; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th { background-color: #1e5a9f; color: white; padding: 12px; text-align: left; }
    table td { padding: 12px; border-bottom: 1px solid #ddd; }
    table tr:hover { background-color: #f9f9f9; }
    .error { color: #d32f2f; background-color: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
  </style>
</head>
<body>
<div class="header">
  <h2><i class="fas fa-building"></i> Manage Departments</h2>
  <a href="welcome.php">Back to Dashboard</a>
</div>

<div class="container">
    <?php if(isset($error)) echo '<div class="error">'.h($error).'</div>'; ?>

    <div class="form-section">
        <h3>Add New Department</h3>
        <form method="post">
            <label>Department Name:</label> <input type="text" name="name" required>
            <button type="submit" name="add_dept">Add Department</button>
        </form>
    </div>

    <h3>Existing Departments</h3>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php while($d = $depts->fetch_assoc()): ?>
            <tr>
                <td><?php echo h($d['id']); ?></td>
                <td><?php echo h($d['name']); ?></td>
                <td><a href="?delete=<?php echo $d['id']; ?>" onclick="return confirm('Are you sure?');" style="color: #d32f2f;">Delete</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>