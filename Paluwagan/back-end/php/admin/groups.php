<?php
session_start();
include "../../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
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