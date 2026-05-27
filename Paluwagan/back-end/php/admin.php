<?php
session_start();
// Corrected path to move up one folder to reach db.php
include "../db.php";

// 1. Core Absolute Security Gate: Only let 'admin' accounts render this script
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Silently redirect standard members away to their dashboard
    header("Location: dashboard.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// 2. CALCULATION ENGINE: FETCH REAL-TIME STATS
// Count total application accounts
$count_users_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users = mysqli_fetch_assoc($count_users_res)['total'] ?? 0;

// Count verifications (Checking user_verifications table for pending rows)
$count_pending_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM user_verifications WHERE status = 'pending'");
$pending_verifications = mysqli_fetch_assoc($count_pending_res)['total'] ?? 0;

// Count total groups registered
$count_groups_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM groups");
$total_groups = mysqli_fetch_assoc($count_groups_res)['total'] ?? 0;

// Count active groups (where is_active is 1)
$count_active_groups_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM groups WHERE is_active = 1");
$active_groups = mysqli_fetch_assoc($count_active_groups_res)['total'] ?? 0;

// 3. RESOURCE QUERIES (Fetch records for data tables)
$users_query = "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC";
$users_res = mysqli_query($conn, $users_query);

$groups_query = "SELECT g.*, u.first_name, u.last_name,
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status='active') as total_members
                FROM groups g 
                LEFT JOIN users u ON g.created_by = u.user_id 
                ORDER BY g.created_at DESC";
$groups_res = mysqli_query($conn, $groups_query);

// 4. RENDER PHASE: Connect to your user interface template
// Corrected path to go out of php/ and back-end/, then enter front-end/views/
include "../../front-end/views/admin-view.php";
?>