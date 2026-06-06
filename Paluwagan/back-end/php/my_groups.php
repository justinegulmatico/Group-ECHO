<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$error_message = "";
$success_message = "";

// CREATE GROUP (simple)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_group'])) {
    $group_name = mysqli_real_escape_string($conn, trim($_POST['group_name']));
    $contribution = floatval($_POST['contribution']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $cycle_length = intval($_POST['cycle_length']);
    if ($cycle_length < 2) $cycle_length = 2;

    $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    $sql = "INSERT INTO groups (group_name, description, contribution_amount, frequency, cycle_length, invite_code, is_active, created_by, status)
            VALUES ('$group_name', 'Paluwagan Savings Group', $contribution, '$frequency', $cycle_length, '$invite_code', 0, $current_user_id, 'pending')";

    if (mysqli_query($conn, $sql)) {
        $new_group_id = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO group_members (user_id, group_id, status) VALUES ($current_user_id, $new_group_id, 'active')");
        header("Location: my_groups.php?success=" . urlencode("Group created! Invite: $invite_code (waiting for admin)"));
        exit();
    } else {
        $error_message = "Create failed";
    }
}

// JOIN (fixed direct invite code match)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_join_group'])) {
    $code = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['target_invite_code'])));

    $res = mysqli_query($conn, "SELECT * FROM groups WHERE invite_code='$code' AND is_active=1 LIMIT 1");
    $group = mysqli_fetch_assoc($res);

    if (!$group) {
        $error_message = "Invalid or not approved code.";
    } else {
        $gid = (int)$group['group_id'];
        $check = mysqli_query($conn, "SELECT 1 FROM group_members WHERE user_id=$current_user_id AND group_id=$gid AND status='active' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $error_message = "Already a member.";
        } else {
            if (mysqli_query($conn, "INSERT INTO group_members (user_id, group_id, status) VALUES ($current_user_id, $gid, 'active')")) {
                $new_mid = mysqli_insert_id($conn);
                // backfill contributions for existing cycles (student simple)
                $cyclesQ = mysqli_query($conn, "SELECT cycle_id, end_date FROM cycles WHERE group_id=$gid");
                $amt = floatval($group['contribution_amount']);
                while ($cy = mysqli_fetch_assoc($cyclesQ)) {
                    mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status, payment_method) VALUES ({$cy['cycle_id']}, $new_mid, $amt, '{$cy['end_date']}', 'pending', 'Cash')");
                }
                header("Location: my_groups.php?success=Joined!");
                exit();
            }
        }
    }
}

$groups_res = mysqli_query($conn, "
    SELECT g.*, (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status='active') as members_count
    FROM groups g
    INNER JOIN group_members m ON g.group_id = m.group_id
    WHERE m.user_id = $current_user_id AND m.status='active'
    ORDER BY g.created_at DESC
");

include "../../front-end/views/my_groups-view.php";
?>