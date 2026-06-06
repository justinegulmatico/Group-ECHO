<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

$error_message = "";

// ==================== CREATE GROUP (prepared + cycle init, same logic) ====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_group'])) {
    $group_name = trim($_POST['group_name'] ?? '');
    $description = trim($_POST['group_desc'] ?? '');
    $contribution_amount = isset($_POST['contribution']) ? (float)$_POST['contribution'] : 0;
    $max_members = isset($_POST['max_members']) ? max(2, (int)$_POST['max_members']) : 5;
    $frequency = $_POST['frequency'] ?? 'monthly';
    $privacy = $_POST['privacy'] ?? 'public';
    if ($frequency === 'biweekly') $frequency = 'weekly';

    $invite_code = null;
    if ($privacy === 'private') {
        $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    }
    $cycle_length = $max_members;

    // Prevent duplicate group names (simple check)
    $check_stmt = mysqli_prepare($conn, "SELECT group_id FROM groups WHERE group_name = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, "s", $group_name);
    mysqli_stmt_execute($check_stmt);
    $duplicate = mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0;
    mysqli_stmt_close($check_stmt);

    if ($duplicate) {
        $error_message = "A group with this name already exists. Please choose a different name.";
    } else {
        $sql = "INSERT INTO groups 
            (group_name, description, contribution_amount, max_members, frequency, privacy, cycle_length, invite_code, created_by, is_active, status, current_cycle) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', 1)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdissisi", $group_name, $description, $contribution_amount, $max_members, $frequency, $privacy, $cycle_length, $invite_code, $current_user_id);

        if (mysqli_stmt_execute($stmt)) {
            $new_group_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Creator gets position 1 (simple rotation: position = payout order)
            $stmt2 = mysqli_prepare($conn, "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', 1)");
            mysqli_stmt_bind_param($stmt2, "ii", $current_user_id, $new_group_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            // Pre-generate ALL cycles for the full paluwagan rotation (student-simple model)
            // Cycle N will be paid to the member who has position = N
            for ($c = 1; $c <= $cycle_length; $c++) {
                $stmtC = mysqli_prepare($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, status, payout_status) VALUES (?, ?, CURDATE(), 'ongoing', 'pending')");
                mysqli_stmt_bind_param($stmtC, "ii", $new_group_id, $c);
                mysqli_stmt_execute($stmtC);
                mysqli_stmt_close($stmtC);
            }

            $msg = "Group created successfully! Positions assigned. Cycles ready.";
            if ($invite_code) $msg .= " Invite Code: $invite_code";
            header("Location: my_groups.php?success=" . urlencode($msg));
            exit();
        } else {
            mysqli_stmt_close($stmt);
            $error_message = "Error creating group: " . mysqli_error($conn);
        }
    }
}

// ==================== JOIN GROUP (prepared) - supports invite code or direct public join ====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['action_join_group']) || isset($_POST['action_join_public']))) {
    $group_id = 0;
    $group = null;

    if (isset($_POST['action_join_group'])) {
        // Join by invite code (works for private + public)
        $invite_code = strtoupper(trim($_POST['invite_code'] ?? ''));
        $stmt = mysqli_prepare($conn, "SELECT * FROM groups WHERE invite_code = ? AND (status = 'active' OR status = 'pending') LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $invite_code);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $group = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
        if ($group) $group_id = (int)$group['group_id'];
    } elseif (isset($_POST['action_join_public']) && isset($_POST['group_id'])) {
        // Direct join for public groups (no code needed)
        $group_id = (int)$_POST['group_id'];
        $stmt = mysqli_prepare($conn, "SELECT * FROM groups WHERE group_id = ? AND privacy = 'public' AND (status = 'active' OR status = 'pending') LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $group_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $group = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    if ($group && $group_id > 0) {
        // Check if already member (using effective for simulation)
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM group_members WHERE user_id = ? AND group_id = ? AND status='active' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $group_id);
        mysqli_stmt_execute($stmt);
        $already = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);

        if ($already) {
            $error_message = "You are already a member of this group.";
        } else {
            // Multi-slot support: check total slots
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as filled FROM group_members WHERE group_id = ? AND status='active'");
            mysqli_stmt_bind_param($stmt, "i", $group_id);
            mysqli_stmt_execute($stmt);
            $filled = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['filled'] ?? 0);
            mysqli_stmt_close($stmt);

            $max_slots = (int)($group['max_members'] ?? 5);

            if ($filled < $max_slots) {
                // Assign next position
                $stmtPos = mysqli_prepare($conn, "SELECT COALESCE(MAX(position), 0) + 1 as next_pos FROM group_members WHERE group_id = ?");
                mysqli_stmt_bind_param($stmtPos, "i", $group_id);
                mysqli_stmt_execute($stmtPos);
                $posRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtPos));
                $nextPosition = (int)$posRow['next_pos'];
                mysqli_stmt_close($stmtPos);

                $stmt = mysqli_prepare($conn, "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', ?)");
                mysqli_stmt_bind_param($stmt, "iii", $current_user_id, $group_id, $nextPosition);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // After joining, go to the group details (better UX)
                header("Location: group_details.php?id=$group_id&success=" . urlencode("Successfully joined the group! You are in position #$nextPosition."));
                exit();
            } else {
                $error_message = "This group is full.";
            }
        }
    } else {
        $error_message = "Invalid group or invite code.";
    }
}

// ==================== PUBLIC GROUPS (visible for pending too so others can discover & join) ====================
$public_query = "SELECT g.*, 
    (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status = 'active') AS member_count
    FROM groups g 
    WHERE g.privacy = 'public' AND g.status IN ('pending', 'active')
    ORDER BY g.created_at DESC";
$public_groups = mysqli_query($conn, $public_query);

// ==================== MY GROUPS ====================
$my_query = "SELECT g.*, 
    (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status = 'active') AS member_count
    FROM groups g
    INNER JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ? AND gm.status = 'active'
    ORDER BY g.created_at DESC";
$stmt = mysqli_prepare($conn, $my_query);
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$my_groups = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt); // result object remains valid for view consumption

include "../../front-end/views/my_groups-view.php";
?>