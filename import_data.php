<?php
require '../config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

if(empty($_SESSION['admin'])){ header('Location: login.php'); exit; }

$message = '';
$error = '';

// Check if POST request was made
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check for max post size error (if file is too big, $_POST is empty)
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "The uploaded file is too large. It exceeds the post_max_size directive in php.ini.";
    } 
    elseif (isset($_POST['import'])) {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
            // Handle specific upload errors
            if(isset($_FILES['file'])) {
                switch($_FILES['file']['error']) {
                    case UPLOAD_ERR_INI_SIZE: $error = "File exceeds upload_max_filesize."; break;
                    case UPLOAD_ERR_FORM_SIZE: $error = "File exceeds MAX_FILE_SIZE."; break;
                    case UPLOAD_ERR_PARTIAL: $error = "File only partially uploaded."; break;
                    case UPLOAD_ERR_NO_FILE: $error = "No file was uploaded."; break;
                    default: $error = "File upload error.";
                }
            } else {
                $error = "Please upload a valid CSV file.";
            }
        } else {
            $type = $_POST['type'];
            $filename = $_FILES['file']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if($ext !== 'csv'){
                $error = "Invalid format. Please save your Excel file as CSV (Comma delimited) and upload.";
            } else {
            $file = $_FILES['file']['tmp_name'];
            
            ini_set('auto_detect_line_endings', TRUE);
            
            $handle = fopen($file, "r");
            
            if ($handle) {
                // Skip header row
                fgetcsv($handle);
                
                $count = 0;
                $errCount = 0;
                
                // Use 0 for unlimited line length (fixes issues with long rows)
                while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                    // Skip empty rows
                    if (empty(array_filter($data))) continue;
                    
                    // Basic validation: check if we have enough columns
                    // This helps detect delimiter issues (e.g. if file uses ; instead of ,)
                    if (count($data) < 2 && $type != 'departments') {
                        $errCount++;
                        continue;
                    }

                    $success = false;
                    
                    try {
                        switch ($type) {
                            case 'departments':
                                if (isset($data[0])) {
                                    $name = $conn->real_escape_string(trim($data[0]));
                                    if ($name) {
                                        $conn->query("INSERT IGNORE INTO departments (name) VALUES ('$name')");
                                        $success = true;
                                    }
                                }
                                break;
                                
                            case 'classes':
                                if (isset($data[0], $data[1])) {
                                    $name = $conn->real_escape_string(trim($data[0]));
                                    $dept = $conn->real_escape_string(trim($data[1]));
                                    
                                    $dr = $conn->query("SELECT id FROM departments WHERE name='$dept'");
                                    if ($dr && $dr->num_rows > 0) {
                                        $did = $dr->fetch_assoc()['id'];
                                        $conn->query("INSERT IGNORE INTO classes (name, department_id) VALUES ('$name', $did)");
                                        $success = true;
                                    }
                                }
                                break;
                                
                            case 'units':
                                if (isset($data[0], $data[1])) {
                                    $code = $conn->real_escape_string(trim($data[0]));
                                    $name = $conn->real_escape_string(trim($data[1]));
                                    $conn->query("INSERT IGNORE INTO units (code, name) VALUES ('$code', '$name')");
                                    $success = true;
                                }
                                break;
                                
                            case 'trainers':
                                if (isset($data[0], $data[1], $data[2], $data[3])) {
                                    $name = $conn->real_escape_string(trim($data[0]));
                                    $user = $conn->real_escape_string(trim($data[1]));
                                    $pass = trim($data[2]);
                                    $dept = $conn->real_escape_string(trim($data[3]));
                                    
                                    $dr = $conn->query("SELECT id FROM departments WHERE name='$dept'");
                                    if ($dr && $dr->num_rows > 0) {
                                        $did = $dr->fetch_assoc()['id'];
                                        $conn->query("INSERT IGNORE INTO trainers (name, username, password, department_id) VALUES ('$name', '$user', '$pass', $did)");
                                        $success = true;
                                    }
                                }
                                break;
                                
                            case 'students':
                                if (isset($data[0], $data[1], $data[2])) {
                                    $adm = $conn->real_escape_string(trim($data[0]));
                                    $fullname = $conn->real_escape_string(trim($data[1]));
                                    $cls = $conn->real_escape_string(trim($data[2]));
                                    
                                    $cr = $conn->query("SELECT id FROM classes WHERE name='$cls'");
                                    if ($cr && $cr->num_rows > 0) {
                                        $cid = $cr->fetch_assoc()['id'];
                                        $conn->query("INSERT IGNORE INTO students (admission_number, full_name, class_id) VALUES ('$adm', '$fullname', $cid)");
                                        $success = true;
                                    }
                                }
                                break;
                        }
                    } catch (Exception $e) {
                        // ignore
                    }
                    
                    if ($success) $count++; else $errCount++;
                }
                fclose($handle);
                $message = "Import complete. Successfully imported: $count. Failed/Skipped: $errCount.";
                if($count == 0 && $errCount > 0) {
                    $error = "No records were imported. Please check your CSV format (delimiters) and ensure referenced data (like Class Names) exists exactly as in the database.";
                }
            } else {
                $error = "Could not open file.";
            }
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Import Data</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
    .top-bar { background-color: #1e5a9f; color: white; padding: 15px 20px; text-align: right; }
    .top-bar a { color: #ffd700; text-decoration: none; margin-left: 20px; }
    .header { background: white; padding: 20px; border-bottom: 3px solid #1e5a9f; }
    .header a { color: #1e5a9f; text-decoration: none; font-weight: bold; }
    .container { max-width: 800px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h2 { color: #1e5a9f; margin-bottom: 20px; border-bottom: 2px solid #1e5a9f; padding-bottom: 10px; }
    .message { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    form { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
    label { display: block; margin-bottom: 10px; font-weight: bold; }
    select, input[type="file"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; }
    button { background-color: #1e5a9f; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
    button:hover { background-color: #154070; }
    .note { font-size: 0.9em; color: #666; margin-top: 20px; line-height: 1.5; }
  </style>
</head>
<body>
  <div class="top-bar">
    <a href="dashboard.php">Dashboard</a> | <a href="logout.php">Logout</a>
  </div>

  <div class="header">
    <h1><i class="fas fa-file-import"></i> Bulk Import Data</h1>
  </div>

  <div class="container">
    <?php if($message) echo '<div class="message success">'.$message.'</div>'; ?>
    <?php if($error) echo '<div class="message error">'.$error.'</div>'; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Select Data Type:</label>
        <select name="type" required>
            <option value="departments">Departments (Format: Name)</option>
            <option value="classes">Classes (Format: Name, Department Name)</option>
            <option value="units">Units (Format: Code, Name)</option>
            <option value="trainers">Trainers (Format: Name, Username, Password, Department Name)</option>
            <option value="students">Students (Format: Adm No, Full Name, Class Name)</option>
        </select>
        
        <label>Upload Excel File (CSV format):</label>
        <input type="file" name="file" accept=".csv" required>
        
        <button type="submit" name="import">Import Data</button>
    </form>

    <div class="note">
        <p><strong>Instructions:</strong></p>
        <ul>
            <li>Please save your Excel file as <strong>CSV (Comma delimited)</strong> before uploading.</li>
            <li>The first row is treated as a header and will be skipped.</li>
            <li>Ensure referenced data (like Department Name for Classes) exists before importing.</li>
        </ul>
    </div>
  </div>
</body>
</html>