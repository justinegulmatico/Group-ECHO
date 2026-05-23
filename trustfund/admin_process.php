<?php
session_start();
include "db.php";

// Tight Security Check: Kick out anyone who isn't a logged-in Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// ─── ACTION 1: ACTIVATE SUSPENDED USER ───
if (isset($_GET['activate_user'])) {
    $target_user_id = intval($_GET['activate_user']);
    
    // Updates role or status depending on how your system defines active state
    // If you have a dedicated 'status' column, use: UPDATE users SET status='active' WHERE...
    // Given the 'suspended' label, we will simulate activation by ensuring they remain a member or updating status if it exists.
    $update_user = "UPDATE users SET role = 'member' WHERE user_id = '$target_user_id'";
    mysqli_query($conn, $update_user);
    
    header("Location: admin.php?success=" . urlencode("User activated successfully."));
    exit();
}

// ─── ACTION 2: CHANGE GROUP POOL STATUS DROPDOWN ───
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_update_group_status'])) {
    $target_group_id = intval($_POST['group_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['group_status']);
    
    // Map 'active' to 1 and 'closed' to 0 matching your tinyint(1) groups table layout schema
    $is_active_val = ($new_status === 'active') ? 1 : 0;
    
    $update_group = "UPDATE groups SET is_active = '$is_active_val' WHERE group_id = '$target_group_id'";
    mysqli_query($conn, $update_group);
    
    header("Location: admin.php?success=" . urlencode("Group status updated successfully."));
    exit();
}

// If no valid actions are hit, route back to safety
header("Location: admin.php");
exit();
?>