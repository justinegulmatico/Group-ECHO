<?php
session_start();
include "../../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

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

// Handle close group
if (isset($_POST['action_close_group'])) {
    $g_id = (int)($_POST['target_group_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "UPDATE groups SET status = 'closed' WHERE group_id = ?");
    if (!$stmt) {
        die("Prepare failed (close group): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $g_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Log group closed to history
    $admin_id = (int)$_SESSION['user_id'];
    $chist = mysqli_prepare($conn, "
        INSERT INTO group_history (group_id, event_type, actor_user_id, description) 
        VALUES (?, 'group_closed', ?, 'Group closed by admin')
    ");
    if ($chist) {
        mysqli_stmt_bind_param($chist, "ii", $g_id, $admin_id);
        mysqli_stmt_execute($chist);
        mysqli_stmt_close($chist);
    }

    header("Location: groups.php?success=Group closed successfully");
    exit();
}

// Fetch all groups with owner and member info
$groups = mysqli_query($conn, "
    SELECT 
        g.*, 
        u.first_name as owner_first, 
        u.last_name as owner_last,
        u.username as owner_user,
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status='active') as member_count,
        (SELECT GROUP_CONCAT(CONCAT(u2.first_name, ' ', u2.last_name) SEPARATOR ', ') 
         FROM group_members gm 
         JOIN users u2 ON gm.user_id = u2.user_id 
         WHERE gm.group_id = g.group_id AND gm.status='active') as member_list
    FROM groups g
    LEFT JOIN users u ON g.created_by = u.user_id
    ORDER BY g.created_at DESC
") or die(mysqli_error($conn));

include "../../../front-end/views/admin/groups-view.php";
?>