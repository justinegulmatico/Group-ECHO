<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

$toast_message = "";
$toast_type = "";

// ─── LOGIC: CREATE GROUP (prepared, full fields, seed cycle, consistent) ───
if (isset($_POST['action_create_group'])) {
    $group_name = trim($_POST['group_name'] ?? '');
    $desc = trim($_POST['group_desc'] ?? '');
    $privacy = $_POST['privacy'] ?? 'public';
    $amount = isset($_POST['contribution']) ? (float)$_POST['contribution'] : 0;
    $max = isset($_POST['max_members']) ? max(2, (int)$_POST['max_members']) : 5;
    $freq = $_POST['frequency'] ?? 'monthly';
    if ($freq === 'biweekly') $freq = 'weekly';

    $invite_code = null;
    if ($privacy === 'private') {
        $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    }
    $cycle_length = $max;

    // Prevent duplicate group names
    $check_stmt = mysqli_prepare($conn, "SELECT group_id FROM groups WHERE group_name = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, "s", $group_name);
    mysqli_stmt_execute($check_stmt);
    $duplicate = mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0;
    mysqli_stmt_close($check_stmt);

    if ($duplicate) {
        $toast_message = "A group with this name already exists. Please choose a different name.";
        $toast_type = "error";
    } else {
        $sql = "INSERT INTO groups (group_name, description, privacy, contribution_amount, max_members, frequency, cycle_length, invite_code, created_by, is_active, status, current_cycle)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', 1)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssdisisi", $group_name, $desc, $privacy, $amount, $max, $freq, $cycle_length, $invite_code, $current_user_id);

        if (mysqli_stmt_execute($stmt)) {
            $gid = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Creator gets position 1
            $stmt2 = mysqli_prepare($conn, "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', 1)");
            mysqli_stmt_bind_param($stmt2, "ii", $current_user_id, $gid);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            // Pre-generate full cycles for paluwagan (position N receives in cycle N)
            for ($c = 1; $c <= $max; $c++) {
                $stmtC = mysqli_prepare($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, status, payout_status) VALUES (?, ?, CURDATE(), 'ongoing', 'pending')");
                mysqli_stmt_bind_param($stmtC, "ii", $gid, $c);
                mysqli_stmt_execute($stmtC);
                mysqli_stmt_close($stmtC);
            }

            $toast_message = "Group created successfully! Cycles and positions ready.";
            $toast_type = "success";
        } else {
            mysqli_stmt_close($stmt);
            $toast_message = "Failed to create group.";
            $toast_type = "error";
        }
    }
}

// ─── LOGIC: JOIN GROUP (prepared, supports pending public too) ───
if (isset($_POST['action_join_group'])) {
    $code = strtoupper(trim($_POST['join_code'] ?? ''));

    $stmt = mysqli_prepare($conn, "SELECT group_id FROM groups WHERE invite_code = ? AND (status = 'active' OR status = 'pending') LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($row) {
        $gid = (int)$row['group_id'];

        // prevent dup (use effective)
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM group_members WHERE user_id = ? AND group_id = ? AND status='active' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $gid);
        mysqli_stmt_execute($stmt);
        $already = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);

        if (!$already) {
            $stmt = mysqli_prepare($conn, "INSERT INTO group_members (user_id, group_id, status) VALUES (?, ?, 'active')");
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $gid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $toast_message = "Joined group successfully!";
            $toast_type = "success";
        } else {
            $toast_message = "You are already a member.";
            $toast_type = "error";
        }
    } else {
        $toast_message = "Invalid invite code.";
        $toast_type = "error";
    }
}

// ─── FETCH STATS (prepared) ───
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM group_members WHERE user_id = ? AND status = 'active'");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$active_groups_count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

$total_contributed = 0.00;
$total_received = 0.00;

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

// Simple extra paluwagan info for dashboard (next upcoming payout indicator)
$next_payout_info = "Join or create groups to see your rotation schedule.";
$stmtNext = mysqli_prepare($conn, "
    SELECT g.group_name, c.cycle_number, gm.position
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.group_id
    JOIN cycles c ON c.group_id = g.group_id AND c.cycle_number = gm.position
    WHERE gm.user_id = ? AND gm.status='active' AND c.payout_status='pending'
    ORDER BY c.cycle_number ASC LIMIT 1
");
mysqli_stmt_bind_param($stmtNext, "i", $current_user_id);
mysqli_stmt_execute($stmtNext);
$nextRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtNext));
mysqli_stmt_close($stmtNext);
if ($nextRow) {
    $next_payout_info = "In \"" . htmlspecialchars($nextRow['group_name']) . "\" you are position #" . $nextRow['position'] . " (Cycle " . $nextRow['cycle_number'] . ")";
}

include "../../front-end/views/dashboard-view.php";
?>