<?php
$pageTitle = 'Student Registration';
require 'config.php';

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: select_institution.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_number = trim($_POST['admission_number']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;

    if ($admission_number && $password && $fullname && $email && $class_id) {
        // Check if admission number exists
        $stmt = $conn->prepare("SELECT id, email FROM students WHERE admission_number = ?");
        $stmt->bind_param("s", $admission_number);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows == 0) {
            $error = "Admission Number not found in the system. Only students added by the Admin can register.";
        } else {
            $row = $res->fetch_assoc();
            if (!empty($row['email'])) {
                $error = "Account already registered. Please login.";
            } else {
                // Update existing record with registration details
                $stmt = $conn->prepare("UPDATE students SET full_name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssi", $fullname, $email, $password, $row['id']);

                if ($stmt->execute()) {
                    header("Location: student_login.php?registered=1");
                    exit;
                } else {
                    $error = "Registration failed: " . $conn->error;
                }
            }
        }
    } else {
        $error = "All fields are required.";
    }
}

// Fetch classes for dropdown
$classes = $conn->query("SELECT id, name FROM classes ORDER BY name");

require __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-8">
    <div class="text-center mb-6">
        <img src="assets/smartlogo.png" alt="Logo" class="h-20 mx-auto">
    </div>
    <h2 class="text-center text-2xl font-semibold text-sky-600 mb-6">Student Registration</h2>
    <?php if(isset($error)): ?>
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4 text-center"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
        <div>
            <label class="block text-sm font-medium">Full Name</label>
            <input type="text" name="fullname" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-sky-600">
        </div>
        <div>
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-sky-600">
        </div>
        <div>
            <label class="block text-sm font-medium">Admission Number</label>
            <input type="text" name="admission_number" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-sky-600">
        </div>
        <div>
            <label class="block text-sm font-medium">Password</label>
            <input type="password" name="password" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-sky-600">
        </div>
        <div>
            <label class="block text-sm font-medium">Class</label>
            <select name="class_id" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-sky-600">
                <option value="">Select Class</option>
                <?php if($classes) while($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="w-full py-2 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded transition">Register</button>
        </div>
    </form>
    <div class="mt-6 text-center text-sm text-gray-600">
        Already have an account? <a href="student_login.php" class="underline hover:text-gray-800">Login here</a>
    </div>
    <div class="mt-4 text-center text-sm">
        <a href="index.php" class="underline hover:text-gray-800">Back to Home</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
