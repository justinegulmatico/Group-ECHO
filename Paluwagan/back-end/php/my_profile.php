<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// Handle profile update (only editable fields)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['phone'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $stmt = mysqli_prepare($conn, "UPDATE users SET phone=?, occupation=?, address=? WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "sssi", $phone, $occupation, $address, $current_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: my_profile.php?success=Profile updated successfully");
    exit();
}

// Fetch current user (prepared)
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$first_name = $user_data ? $user_data['first_name'] : "User";
$last_name  = $user_data ? ($user_data['last_name'] ?? '') : "";
$full_name  = trim($first_name . " " . $last_name) ?: "User";
$user_role  = $user_data ? ucfirst($user_data['role']) : "Member";
$created_at = $user_data['created_at'] ?? null;

// Keep session in sync
if ($user_data) {
    $_SESSION['username']   = $user_data['username'];
    $_SESSION['email']      = $user_data['email'];
    $_SESSION['phone']      = $user_data['phone'];
    $_SESSION['occupation'] = $user_data['occupation'];
    $_SESSION['address']    = $user_data['address'];
    $_SESSION['role']       = $user_data['role'];
}

// Avatar initials
$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($first_name, 0, 1) . ($last_name ? substr($last_name, 0, 1) : ''));
}

// === STATS (reused pattern from dashboard - simple & student friendly) ===
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM group_members WHERE user_id = ? AND status = 'active'");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$active_groups = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(c.amount), 0) AS total_contributed
    FROM contributions c
    JOIN group_members gm ON c.member_id = gm.member_id
    WHERE gm.user_id = ? AND c.status = 'paid'
");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$total_contributed = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_contributed'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(p.amount), 0) AS total_received
    FROM payouts p
    JOIN group_members gm ON p.member_id = gm.member_id
    WHERE gm.user_id = ? AND p.status = 'released'
");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$total_received = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_received'] ?? 0);
mysqli_stmt_close($stmt);

$net_position = $total_received - $total_contributed;

// Profile completeness (simple student-friendly calculation)
$completeness = 40; // base
if (!empty($user_data['phone'])) $completeness += 15;
if (!empty($user_data['occupation'])) $completeness += 15;
if (!empty($user_data['address'])) $completeness += 15;
if ($active_groups > 0) $completeness += 15;
$completeness = min(100, $completeness);

// Include the view
include "../../front-end/views/my_profile-view.php";
?>