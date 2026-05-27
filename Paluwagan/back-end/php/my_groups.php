<?php
session_start();
// Corrected path to move up one folder to reach db.php in back-end/
include "../db.php";

// 1. Force safety checkpoint redirect if no logged-in user session exists
if (!isset($_SESSION['user_id'])) {
    // Corrected path: move up 3 steps (php/ -> back-end/ -> Paluwagan/ -> Group-ECHO/) to reach index.php
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Get Logged-in User Profile Context
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1));
}

// ─── ACTION A: PROCESSING THE "+ CREATE GROUP" FORM SUBMISSION ───
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_group'])) {
    $group_name = mysqli_real_escape_string($conn, trim($_POST['group_name']));
    $contribution_amount = floatval($_POST['contribution']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $cycle_length = intval($_POST['cycle_length']); // Track max pool slot limit capacity
    
    // Generate unique alphanumeric 6-character invitation code string
    $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    // Maps exactly to your DESC groups terminal layout schema blueprint keys
    $insert_group = "INSERT INTO groups (group_name, description, contribution_amount, frequency, cycle_length, invite_code, is_active, created_by) 
                     VALUES ('$group_name', 'TrustFund Paluwagan Savings Pool Group Circle.', '$contribution_amount', '$frequency', '$cycle_length', '$invite_code', 1, '$current_user_id')";

    if (mysqli_query($conn, $insert_group)) {
        $new_group_id = mysqli_insert_id($conn);

        // Auto-join the creator as an active member inside their group pool tracker layout matrix
        $join_creator = "INSERT INTO group_members (user_id, group_id, status) VALUES ('$current_user_id', '$new_group_id', 'active')";
        mysqli_query($conn, $join_creator);

        // Redirects back to itself in the same directory (no path changes needed here)
        header("Location: my_groups.php?success=" . urlencode("Group created successfully! Invite Code: $invite_code"));
        exit();
    } else {
        $error_message = "Database writing fault failure string: " . mysqli_error($conn);
    }
}

// ─── ACTION B: PROCESSING THE "JOIN WITH CODE" FORM SUBMISSION ───
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_join_group'])) {
    $target_code = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['target_invite_code'])));
    
    // Search database for active matching group tokens
    $lookup = mysqli_query($conn, "SELECT * FROM groups WHERE invite_code = '$target_code' AND is_active = 1 LIMIT 1");
    
    if (mysqli_num_rows($lookup) > 0) {
        $group_row = mysqli_fetch_assoc($lookup);
        $found_group_id = $group_row['group_id'];
        
        // Prevent duplicate group records entry exceptions
        $duplicate_check = mysqli_query($conn, "SELECT * FROM group_members WHERE group_id = '$found_group_id' AND user_id = '$current_user_id'");
        
        if (mysqli_num_rows($duplicate_check) == 0) {
            mysqli_query($conn, "INSERT INTO group_members (group_id, user_id, status) VALUES ('$found_group_id', '$current_user_id', 'active')");
            // Redirects back to itself in the same directory (no path changes needed here)
            header("Location: my_groups.php?success=" . urlencode("Successfully joined the savings pool!"));
            exit();
        } else {
            $error_message = "You are already a registered active member of this circle group!";
        }
    } else {
        $error_message = "Invalid or inactive invitation code. Please verify parameter values.";
    }
}

// ─── STAGE DATA FOR DISPLAY ───
$groups_query = "SELECT g.*, 
                  (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status = 'active') as members_count 
                  FROM groups g 
                  JOIN group_members m ON g.group_id = m.group_id 
                  WHERE m.user_id = '$current_user_id' AND m.status = 'active'";
$groups_res = mysqli_query($conn, $groups_query);

// ─── RENDER PHASE ───
// Corrected path: move up 2 steps (php/ -> back-end/), then enter front-end/views/
include "../../front-end/views/my_groups-view.php";
?>