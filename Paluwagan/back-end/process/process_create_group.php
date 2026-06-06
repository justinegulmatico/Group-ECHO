<?php
session_start();
// process/ sibling to php/ under back-end/
include "../db.php";

// Note: process/ is one level below php/, so redirects to controllers must use ../php/...
// e.g. ../php/my_groups.php instead of ../my_groups.php (which would 404)

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

// 1. Auth guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// 2. Handle create - FIXED schema, privacy, cycle_length, prepared stmts, consistent with inline logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = (int)$_SESSION['user_id'];

    $group_name = trim($_POST['group_name'] ?? '');
    $desc = trim($_POST['group_desc'] ?? '');
    $privacy = $_POST['privacy'] ?? 'public';
    $amount = isset($_POST['contribution']) ? (float)$_POST['contribution'] : 0.0;
    $max_members = isset($_POST['max_members']) ? max(2, (int)$_POST['max_members']) : 5;
    $freq = $_POST['frequency'] ?? 'monthly';

    // Normalize frequency to match DB enum('weekly','monthly')
    if ($freq === 'biweekly') $freq = 'weekly';

    $invite_code = null;
    if ($privacy === 'private') {
        $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    }

    // cycle_length for paluwagan rotation/UI slots = max_members
    $cycle_length = $max_members;

    // Prevent duplicate group names
    $check_stmt = mysqli_prepare($conn, "SELECT group_id FROM groups WHERE group_name = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, "s", $group_name);
    mysqli_stmt_execute($check_stmt);
    $duplicate = mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0;
    mysqli_stmt_close($check_stmt);

    if ($duplicate) {
        header("Location: ../php/my_groups.php?error=" . urlencode("A group with this name already exists. Please choose a different name."));
        exit();
    }

    // Use prepared
    $sql = "INSERT INTO groups 
        (group_name, description, privacy, contribution_amount, max_members, frequency, cycle_length, invite_code, created_by, is_active, status, current_cycle) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', 1)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssdisisi", 
        $group_name, $desc, $privacy, $amount, $max_members, $freq, $cycle_length, $invite_code, $owner_id
    );

    if (mysqli_stmt_execute($stmt)) {
        $new_group_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Creator gets position 1 (simple student paluwagan: lower position = earlier payout)
        $stmt2 = mysqli_prepare($conn, "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', 1)");
        mysqli_stmt_bind_param($stmt2, "ii", $owner_id, $new_group_id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        // Log group created + creator joined
        $create_desc = "Group created";
        $chist = mysqli_prepare($conn, "
            INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, description) 
            VALUES (?, 'group_created', ?, ?, ?)
        ");
        if ($chist) {
            mysqli_stmt_bind_param($chist, "iiis", $new_group_id, $owner_id, $owner_id, $create_desc);
            mysqli_stmt_execute($chist);
            mysqli_stmt_close($chist);
        }

        $join_desc = "Joined as position #1 (creator)";
        $jhist = mysqli_prepare($conn, "
            INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, description) 
            VALUES (?, 'member_joined', ?, ?, ?)
        ");
        if ($jhist) {
            mysqli_stmt_bind_param($jhist, "iiis", $new_group_id, $owner_id, $owner_id, $join_desc);
            mysqli_stmt_execute($jhist);
            mysqli_stmt_close($jhist);
        }

        // Pre-create ALL cycles for the full rotation (Cycle #N pays the member with position #N)
        for ($c = 1; $c <= $max_members; $c++) {
            $stmtC = mysqli_prepare($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, status, payout_status) VALUES (?, ?, CURDATE(), 'ongoing', 'pending')");
            mysqli_stmt_bind_param($stmtC, "ii", $new_group_id, $c);
            mysqli_stmt_execute($stmtC);
            mysqli_stmt_close($stmtC);
        }

        $successMsg = "Group created successfully! Full cycles + positions ready.";
        if ($invite_code) {
            $successMsg .= " Invite Code: " . $invite_code;
        }
        header("Location: ../php/my_groups.php?success=" . urlencode($successMsg));
        exit();
    } else {
        $err = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        header("Location: ../php/my_groups.php?error=" . urlencode("Failed to create group: " . $err));
        exit();
    }
}

header("Location: ../php/my_groups.php");
exit();
?>