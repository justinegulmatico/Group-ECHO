<?php
session_start();
include "../db.php";

// note: redirects from process/ need ../php/admin/...
// e.g. ../php/admin/index.php instead of ../admin/index.php (which would 404)

// Security
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../php/admin/index.php");
    exit();
}

// Legacy shim: Admin actions are now in the admin/ folder (index.php, users.php, etc.).
// Redirect to the new admin dashboard.
header("Location: ../php/admin/index.php");
exit();
?>