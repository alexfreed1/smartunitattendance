<?php
require '../config.php';

// Clear admin session
unset($_SESSION['admin']);

// Redirect to login
header('Location: login.php');
exit;
?>
