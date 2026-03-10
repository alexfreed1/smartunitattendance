<?php
// SUAS Database Update Script
// Run this AFTER selecting an institution

session_start();

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    die('<h2>SUAS Database Update</h2><p style="color:red;">Error: No institution selected. Please <a href="select_institution.php">select your institution</a> first.</p>');
}

$DB_NAME = $_SESSION['institution_db'];

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('<h2>SUAS Database Update</h2><p style="color:red;">Database connection error: ' . $conn->connect_error . '</p>');
}

echo "<h2>SUAS Database Update Script</h2>";
echo "<p>Updating database schema for institution: <strong>" . htmlspecialchars($_SESSION['institution_name'] ?? $DB_NAME) . "</strong></p><hr>";

// 1. Ensure departments table exists
$sql = "CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "<p>✓ Checked 'departments' table.</p>";
} else {
    die("<p>✗ Error: " . $conn->error . "</p>");
}

// 2. Ensure classes table exists
$sql = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "<p>✓ Checked 'classes' table.</p>";
} else {
    die("<p>✗ Error: " . $conn->error . "</p>");
}

// 3. Ensure students table exists with correct schema
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) NOT NULL,
    admission_number VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    class_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "<p>✓ Checked 'students' table.</p>";
} else {
    echo "<p>✗ Error checking students table: " . $conn->error . "</p>";
}

// Check and update students table columns
$columns = ['full_name', 'admission_number', 'email', 'password', 'class_id'];
foreach ($columns as $col) {
    $result = $conn->query("SHOW COLUMNS FROM students LIKE '$col'");
    if ($result->num_rows == 0) {
        if ($col == 'full_name') {
            $check = $conn->query("SHOW COLUMNS FROM students LIKE 'name'");
            if ($check->num_rows > 0) {
                $conn->query("ALTER TABLE students CHANGE COLUMN name full_name VARCHAR(200) NOT NULL");
                echo "<p>✓ Renamed 'name' to 'full_name' in students table.</p>";
            } else {
                $conn->query("ALTER TABLE students ADD COLUMN full_name VARCHAR(200) NOT NULL AFTER id");
                echo "<p>✓ Added 'full_name' column to students table.</p>";
            }
        } elseif ($col == 'admission_number') {
            $check = $conn->query("SHOW COLUMNS FROM students LIKE 'admission_no'");
            if ($check->num_rows > 0) {
                $conn->query("ALTER TABLE students CHANGE COLUMN admission_no admission_number VARCHAR(50) NOT NULL UNIQUE");
                echo "<p>✓ Renamed 'admission_no' to 'admission_number'.</p>";
            } else {
                $check2 = $conn->query("SHOW COLUMNS FROM students LIKE 'username'");
                if ($check2->num_rows > 0) {
                    $conn->query("ALTER TABLE students CHANGE COLUMN username admission_number VARCHAR(50) NOT NULL UNIQUE");
                    echo "<p>✓ Renamed 'username' to 'admission_number'.</p>";
                } else {
                    $conn->query("ALTER TABLE students ADD COLUMN admission_number VARCHAR(50) AFTER full_name");
                    echo "<p>✓ Added 'admission_number' column.</p>";
                }
            }
        } elseif ($col == 'email') {
            $conn->query("ALTER TABLE students ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER admission_number");
            echo "<p>✓ Added 'email' column.</p>";
        } elseif ($col == 'password') {
            $conn->query("ALTER TABLE students ADD COLUMN password VARCHAR(255) NOT NULL AFTER email");
            echo "<p>✓ Added 'password' column.</p>";
        } elseif ($col == 'class_id') {
            $conn->query("ALTER TABLE students ADD COLUMN class_id INT NOT NULL AFTER password");
            $conn->query("ALTER TABLE students ADD CONSTRAINT fk_student_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE");
            echo "<p>✓ Added 'class_id' column and foreign key.</p>";
        }
    } else {
        echo "<p>✓ Column '$col' exists in students table.</p>";
    }
}

// 4. Ensure units table exists
$sql = "CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "<p>✓ Checked 'units' table.</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// Check for 'name' column in units
$result = $conn->query("SHOW COLUMNS FROM units LIKE 'name'");
if ($result->num_rows == 0) {
    $check = $conn->query("SHOW COLUMNS FROM units LIKE 'title'");
    if ($check->num_rows > 0) {
        $conn->query("ALTER TABLE units CHANGE COLUMN title name VARCHAR(200) NOT NULL");
        echo "<p>✓ Renamed 'title' to 'name' in units table.</p>";
    } else {
        $conn->query("ALTER TABLE units ADD COLUMN name VARCHAR(200) NOT NULL AFTER code");
        echo "<p>✓ Added 'name' column to units table.</p>";
    }
}

// 5. Ensure trainers table exists
$sql = "CREATE TABLE IF NOT EXISTS trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "<p>✓ Checked 'trainers' table.</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// Ensure password column is long enough
$conn->query("ALTER TABLE trainers MODIFY COLUMN password VARCHAR(255) NOT NULL");
echo "<p>✓ Updated trainers.password to VARCHAR(255).</p>";

// 6. Ensure admins table exists
$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "<p>✓ Checked 'admins' table.</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 7. Ensure class_units table exists
$sql = "CREATE TABLE IF NOT EXISTS class_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    unit_id INT NOT NULL,
    trainer_id INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (class_id, unit_id, trainer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "<p>✓ Checked 'class_units' table.</p>";
} else {
    echo "<p>✗ Error: " . $conn->error . "</p>";
}

// 8. Ensure attendance table exists with correct schema
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    unit_id INT NOT NULL,
    trainer_id INT NOT NULL,
    lesson ENUM('L1','L2','L3','L4') NOT NULL,
    week INT NOT NULL,
    status ENUM('present','absent') NOT NULL,
    attendance_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($conn->query($sql)) {
    echo "<p>✓ Checked 'attendance' table.</p>";
} else {
    echo "<p>✗ Error creating attendance table: " . $conn->error . "</p>";
    $conn->query("ALTER TABLE attendance MODIFY COLUMN status ENUM('present','absent') NOT NULL");
    echo "<p>✓ Updated attendance.status to ENUM('present','absent').</p>";
}

// Migrate old status values if any
$conn->query("UPDATE attendance SET status = 'present' WHERE status IN ('P', 'Present', '✓', '1')");
$conn->query("UPDATE attendance SET status = 'absent' WHERE status IN ('A', 'Absent', '✗', '0')");
echo "<p>✓ Migrated attendance status values.</p>";

echo "<hr><p><strong>✓ Database is up to date!</strong></p>";
echo "<p><a href='index.php' style='display:inline-block;padding:10px 20px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;text-decoration:none;border-radius:8px;font-weight:600;'>Go to Home</a></p>";
?>
