<?php
session_start();
include "../../db.php";

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// quick stats
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
if (!$stmt) die("Prepare failed (users count): " . mysqli_error($conn));
mysqli_stmt_execute($stmt);
$total_users = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM user_verifications WHERE status = 'pending'");
if (!$stmt) die("Prepare failed (verifications count): " . mysqli_error($conn));
mysqli_stmt_execute($stmt);
$pending_verifications = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM groups");
if (!$stmt) die("Prepare failed (groups count): " . mysqli_error($conn));
mysqli_stmt_execute($stmt);
$total_groups = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM groups WHERE status = 'active'");
if (!$stmt) die("Prepare failed (active groups count): " . mysqli_error($conn));
mysqli_stmt_execute($stmt);
$active_groups = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

include "../../../front-end/views/admin/index-view.php";
?>