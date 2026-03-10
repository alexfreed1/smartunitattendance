<?php
// SUAS - Authentication Helper
// Include this file in pages that require institution selection

// Check if institution is selected
if (empty($_SESSION['institution_db'])) {
    header('Location: ../select_institution.php');
    exit;
}

// Check if database connection exists
if (!isset($conn) || $conn === null) {
    die('Database connection not available. Please select an institution.');
}
?>
