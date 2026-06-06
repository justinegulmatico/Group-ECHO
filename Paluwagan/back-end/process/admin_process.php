<?php
session_start();
include "../db.php";

// Security
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../admin.php");
    exit();
}

// Legacy shim: real actions now live in admin.php POST/GET handlers. This just safely redirects.
header("Location: ../admin.php");
exit();
?>