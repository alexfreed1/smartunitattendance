<?php
session_start();
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

// Ensure admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$messageType = "";

if (isset($_POST['import'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $filename = $_FILES['csv_file']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (strtolower($ext) === 'csv') {
            $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
            
            // Skip header row
            fgetcsv($handle);
            
            $imported = 0;
            
            // Prepare statement for classes import
            $stmt = $conn->prepare("INSERT INTO classes (name, department_id) VALUES (?, ?)");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Assuming CSV format: Class Name, Department Name
                $class_name = $data[0] ?? '';
                $dept_name = $data[1] ?? '';

                if (!empty($class_name) && !empty($dept_name)) {
                    // Find department ID
                    $dept_res = $conn->query("SELECT id FROM departments WHERE name = '" . $conn->real_escape_string($dept_name) . "'");
                    if ($dept_res && $dept_res->num_rows > 0) {
                        $dept_id = $dept_res->fetch_assoc()['id'];
                        $stmt->bind_param("si", $class_name, $dept_id);
                        if ($stmt->execute()) {
                            $imported++;
                        }
                    }
                }
            }
            fclose($handle);
            $message = "Successfully imported $imported classes.";
            $messageType = "success";
        } else {
            $message = "Invalid file format. Please upload a CSV file.";
            $messageType = "error";
        }
    } else {
        $message = "Please select a file to upload.";
        $messageType = "error";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Import Classes - Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #1e5a9f 0%, #2e75b6 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .container { background: white; border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); width: 100%; max-width: 500px; overflow: hidden; }
    .header { background: linear-gradient(135deg, #1e5a9f 0%, #2e75b6 100%); color: white; padding: 30px 20px; text-align: center; }
    .header h2 { font-size: 22px; margin: 0; }
    .content { padding: 30px 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
    .btn { display: block; width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; text-align: center; text-decoration: none; margin-top: 10px; }
    .btn-primary { background-color: #2196F3; color: white; }
    .btn-secondary { background-color: #f5f5f5; color: #333; }
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; font-size: 14px; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2><i class="fas fa-file-csv"></i> Import Classes (CSV)</h2>
    </div>
    <div class="content">
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label>Select CSV File</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
          <small style="color: #666; display: block; margin-top: 5px;">Format: Class Name, Department Name</small>
        </div>
        <button type="submit" name="import" class="btn btn-primary">Upload & Import</button>
        <a href="classes.php" class="btn btn-secondary">Back to Manage Classes</a>
      </form>
    </div>
  </div>
</body>
</html>