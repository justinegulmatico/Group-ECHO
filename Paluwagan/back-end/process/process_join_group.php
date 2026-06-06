<?php
session_start();
// process/ is sibling to php/ under back-end/
include "../db.php";

// 1. Force safety checkpoint redirect if no logged-in user session exists
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// 2. Form submission intercept - FIXED to match actual schema & invite generation (6 char UPPER from md5)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $invite_code = strtoupper(trim($_POST['invite_code'] ?? ''));

    if (empty($invite_code)) {
        header("Location: ../my_groups.php?error=" . urlencode("Invite code is required."));
        exit();
    }

    // Use prepared statement
    $stmt = mysqli_prepare($conn, "SELECT * FROM groups WHERE invite_code = ? AND (status = 'active' OR status = 'pending') LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $invite_code);
    mysqli_stmt_execute($stmt);
    $verify_res = mysqli_stmt_get_result($stmt);
    $group_data = mysqli_fetch_assoc($verify_res);
    mysqli_stmt_close($stmt);

    if (!$group_data) {
        header("Location: ../my_groups.php?error=" . urlencode("Invalid or inactive invite code."));
        exit();
    }

    $target_group_id = (int)$group_data['group_id'];

    // Multi-slot support: Check total filled slots instead of per-user (one user can own multiple slots/positions)
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as current_slots FROM group_members WHERE group_id = ? AND status = 'active'");
    mysqli_stmt_bind_param($stmt, "i", $target_group_id);
    mysqli_stmt_execute($stmt);
    $slots_res = mysqli_stmt_get_result($stmt);
    $slots_data = mysqli_fetch_assoc($slots_res);
    mysqli_stmt_close($stmt);

    $max_slots = (int)($group_data['cycle_length'] ?: $group_data['max_members'] ?: 5);

    if ((int)($slots_data['current_slots'] ?? 0) >= $max_slots) {
        header("Location: ../my_groups.php?error=" . urlencode("This savings group is already full. No open slots remain."));
        exit();
    }

    // Assign next position (simple paluwagan: position decides who gets paid in which cycle)
    $stmtPos = mysqli_prepare($conn, "SELECT COALESCE(MAX(position), 0) + 1 as next_pos FROM group_members WHERE group_id = ?");
    mysqli_stmt_bind_param($stmtPos, "i", $target_group_id);
    mysqli_stmt_execute($stmtPos);
    $posRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtPos));
    $nextPosition = (int)$posRow['next_pos'];
    mysqli_stmt_close($stmtPos);

    $stmt = mysqli_prepare($conn, "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', ?)");
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $target_group_id, $nextPosition);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        header("Location: ../group_details.php?id=" . $target_group_id . "&success=" . urlencode("Successfully joined! You are position #$nextPosition."));
        exit();
    } else {
        header("Location: ../my_groups.php?error=" . urlencode("Failed to join the group. Please try again."));
        exit();
    }
}

// Fallback
header("Location: ../my_groups.php");
exit();
?>