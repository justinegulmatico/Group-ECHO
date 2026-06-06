<?php
session_start();
// process/ is sibling to php/ under back-end/
include "../db.php";

// Auto-create group_history if missing
$hist_check = mysqli_query($conn, "SHOW TABLES LIKE 'group_history'");
if (!($hist_check && mysqli_num_rows($hist_check) > 0)) {
    $hcreate = "
    CREATE TABLE IF NOT EXISTS `group_history` (
      `history_id` int(11) NOT NULL AUTO_INCREMENT,
      `group_id` int(11) NOT NULL,
      `event_type` varchar(50) NOT NULL,
      `actor_user_id` int(11) DEFAULT NULL,
      `target_user_id` int(11) DEFAULT NULL,
      `cycle_number` int(11) DEFAULT NULL,
      `amount` decimal(10,2) DEFAULT NULL,
      `description` text,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`history_id`),
      KEY `group_id` (`group_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    mysqli_query($conn, $hcreate);
}

// Note: process/ is one level below php/, so redirects to controllers must use ../php/...
// e.g. ../php/group_details.php instead of ../group_details.php (which would 404)

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
        header("Location: ../php/my_groups.php?error=" . urlencode("Invite code is required."));
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
        header("Location: ../php/my_groups.php?error=" . urlencode("Invalid or inactive invite code."));
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
        header("Location: ../php/my_groups.php?error=" . urlencode("This savings group is already full. No open slots remain."));
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
        // Log member joined to history
        $join_desc = "Joined group as position #" . $nextPosition;
        $jhist = mysqli_prepare($conn, "
            INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, description) 
            VALUES (?, 'member_joined', ?, ?, ?)
        ");
        if ($jhist) {
            mysqli_stmt_bind_param($jhist, "iiis", $target_group_id, $user_id, $user_id, $join_desc);
            mysqli_stmt_execute($jhist);
            mysqli_stmt_close($jhist);
        }

        header("Location: ../php/group_details.php?id=" . $target_group_id . "&success=" . urlencode("Successfully joined! You are position #$nextPosition."));
        exit();
    } else {
        header("Location: ../php/my_groups.php?error=" . urlencode("Failed to join the group. Please try again."));
        exit();
    }
}

// Fallback
header("Location: ../php/my_groups.php");
exit();
?>