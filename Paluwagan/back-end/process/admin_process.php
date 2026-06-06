<?php
session_start();
include "../db.php";

// Security
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../admin/index.php");
    exit();
}

// Legacy shim: Admin actions are now in the admin/ folder (index.php, users.php, etc.).
// Redirect to the new admin dashboard.
header("Location: ../admin/index.php");
exit();
?>