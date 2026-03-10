<?php
require '../config.php';

// Clear trainer session data
unset($_SESSION['trainer']);
unset($_SESSION['selected_department']);

// Redirect to login
header('Location: login.php');
exit;
?>
