<?php
$pageTitle = 'Super Admin Login - SUAS';
require 'config_master.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $masterConn->real_escape_string($_POST['username'] ?? '');
    $p = $masterConn->real_escape_string($_POST['password'] ?? '');

    $res = $masterConn->query("SELECT * FROM super_admins WHERE username='$u' AND password='$p'");
    if ($res && $res->num_rows) {
        $row = $res->fetch_assoc();
        $_SESSION['super_admin_id'] = $row['id'];
        $_SESSION['super_admin_username'] = $row['username'];
        header('Location: super_admin_dashboard.php');
        exit;
    } else {
        $err = 'Invalid credentials';
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="w-full max-w-md">
    <!-- Logo and Title -->
    <div class="text-center mb-8">
        <img src="assets/smartlogo.svg" alt="SUAS Logo" class="h-20 mx-auto mb-4 drop-shadow-lg">
        <h1 class="text-2xl font-bold text-white mb-1">SUAS Super Admin</h1>
        <p class="text-indigo-200 text-sm">Smart Unit Attendance System</p>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-2xl p-8 border border-white border-opacity-30">
        <?php if(!empty($err)): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <span><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="post" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-user-shield text-indigo-500 mr-2"></i>Username
                </label>
                <input type="text" name="username" required 
                    class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:bg-white transition-all"
                    placeholder="Enter super admin username">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-lock text-indigo-500 mr-2"></i>Password
                </label>
                <input type="password" name="password" required 
                    class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-indigo-500 focus:bg-white transition-all"
                    placeholder="Enter password">
            </div>
            
            <button type="submit" 
                class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Home
            </a>
        </div>
    </div>
    
    <!-- Footer Info -->
    <div class="mt-6 text-center text-indigo-200 text-xs">
        <p>© 2025 Smart Unit Attendance System (SUAS)</p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
