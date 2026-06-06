<?php
session_start();
include "../../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// Handle suspend / activate via GET
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'] === 'suspend' ? 'suspended' : 'activated';

    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE user_id = ?");
    if (!$stmt) {
        die("Prepare failed (users update): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "si", $action, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: users.php?success=User " . ($action === 'suspended' ? 'suspended' : 'activated'));
    exit();
}

// Fetch all non-admin users
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
if (!$stmt) {
    die("Prepare failed (fetch users): " . mysqli_error($conn));
}
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

include "../../../front-end/views/admin/users-view.php";
?>