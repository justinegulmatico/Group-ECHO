<?php
session_start();
// Path remains correct: moves up one folder to reach db.php in back-end/
include "../db.php";

// Core Session Security Gate: Kick unauthenticated guests back to index.php
if (!isset($_SESSION['user_id'])) {
    // Corrected path: move up 3 steps (php/ -> back-end/ -> Paluwagan/ -> Group-ECHO/) to reach index.php
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// ─── DATA ENGINE: FETCH CURRENT ACTIVE USER PROFILE ───
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_res = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_res);

$display_name = htmlspecialchars($user_data['first_name'] ?? 'User');
$display_fullname = htmlspecialchars(($user_data['first_name'] ?? 'User') . ' ' . ($user_data['last_name'] ?? ''));
$display_role = htmlspecialchars(ucfirst($user_data['role'] ?? 'Member'));
$avatar_initial = strtoupper(substr($display_name, 0, 1));

// ─── CALCULATION ENGINE: COMPUTE PERSONAL PALUWAGAN STATS ───
// Count total active group memberships for this user
$count_my_groups_query = "SELECT COUNT(*) as total FROM group_members WHERE user_id = '$current_user_id' AND status = 'active'";
$count_my_groups_res = mysqli_query($conn, $count_my_groups_query);
$active_groups_count = mysqli_fetch_assoc($count_my_groups_res)['total'] ?? 0;

// Placeholder values for financial aggregation balances (can be wired to your upcoming payment logs tables)
$total_contributed = 0;
$total_received = 0;
$net_position = $total_received - $total_contributed;

// ─── RENDER PHASE ───
// Corrected path: move up 2 steps (php/ -> back-end/), then enter front-end/views/
include "../../front-end/views/dashboard-view.php";
?>