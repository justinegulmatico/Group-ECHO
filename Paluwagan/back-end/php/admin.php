<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $uid = (int)$_GET['id'];
    if ($_GET['action']==='activate') {
        mysqli_query($conn, "UPDATE users SET status='active' WHERE user_id=$uid");
        mysqli_query($conn, "UPDATE user_verifications SET status='verified', verified_at=CURDATE() WHERE user_id=$uid");
    }
    if ($_GET['action']==='suspend') mysqli_query($conn, "UPDATE users SET status='suspended' WHERE user_id=$uid");
    header("Location: admin.php?success=User updated"); exit();
}

if (isset($_POST['action_verify'])) {
    $uid = (int)$_POST['target_user_id'];
    if ($_POST['status']==='approved') {
        mysqli_query($conn, "UPDATE users SET status='active' WHERE user_id=$uid");
        mysqli_query($conn, "UPDATE user_verifications SET status='verified', verified_at=CURDATE() WHERE user_id=$uid");
    } else {
        mysqli_query($conn, "UPDATE users SET status='denied' WHERE user_id=$uid");
    }
    header("Location: admin.php?success=Verification done"); exit();
}

if (isset($_POST['action_approve_group'])) {
    $gid = (int)$_POST['target_group_id'];
    mysqli_query($conn, "UPDATE groups SET status='active', is_active=1 WHERE group_id=$gid");
    header("Location: admin.php?success=Group active"); exit();
}
if (isset($_POST['action_reject_group'])) {
    $gid = (int)$_POST['target_group_id'];
    mysqli_query($conn, "UPDATE groups SET status='denied', is_active=0 WHERE group_id=$gid");
    header("Location: admin.php?success=Rejected"); exit();
}

if (isset($_POST['admin_release_payout'])) {
    $cycle_id = (int)$_POST['cycle_id'];
    $group_id = (int)$_POST['group_id'];
    $today = date('Y-m-d');
    mysqli_query($conn, "UPDATE cycles SET status='completed' WHERE cycle_id=$cycle_id");
    mysqli_query($conn, "UPDATE payouts SET status='released', payout_date='$today' WHERE cycle_id=$cycle_id");

    $g = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM groups WHERE group_id=$group_id"));
    $amt = floatval($g['contribution_amount']);
    $freq = strtolower($g['frequency'] ?? 'monthly');

    $members = [];
    $mr = mysqli_query($conn, "SELECT member_id FROM group_members WHERE group_id=$group_id AND status='active' ORDER BY joined_at ASC");
    while ($r = mysqli_fetch_assoc($mr)) $members[] = (int)$r['member_id'];

    $cur = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT cycle_number FROM cycles WHERE cycle_id=$cycle_id"))['cycle_number'];
    $next = $cur + 1;

    if ($next > count($members)) {
        mysqli_query($conn, "UPDATE groups SET status='completed' WHERE group_id=$group_id");
    } else {
        $end = new DateTime(); $freq=='weekly' ? $end->modify('+7 days') : $end->modify('+1 month');
        $es = $end->format('Y-m-d');
        mysqli_query($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status) VALUES ($group_id, $next, '$today', '$es', 'ongoing')");
        $nc = mysqli_insert_id($conn);
        $recv = $members[$next-1];
        $pot = $amt * count($members);
        mysqli_query($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES ($nc, $recv, $pot, '$es', 'pending')");
        foreach ($members as $mid) {
            mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status, payment_method) VALUES ($nc, $mid, $amt, '$es', 'pending', 'Cash')");
        }
    }
    header("Location: admin.php?success=Released + next cycle"); exit();
}

$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM users WHERE role!='admin'"))['t'] ?? 0;
$pending_verifications = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM users WHERE status='pending' AND role='member'"))['t'] ?? 0;
$total_groups = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM groups"))['t'] ?? 0;
$active_groups = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) t FROM groups WHERE is_active=1"))['t'] ?? 0;

$users_res = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
$groups_res = mysqli_query($conn, "SELECT g.*, u.first_name owner_first, u.last_name owner_last, u.username owner_user, (SELECT COUNT(*) FROM group_members WHERE group_id=g.group_id) member_count FROM groups g LEFT JOIN users u ON g.created_by = u.user_id ORDER BY g.created_at DESC");
$verifications_res = mysqli_query($conn, "SELECT uv.verification_id, uv.document, uv.status verification_status, u.* FROM users u LEFT JOIN user_verifications uv ON u.user_id=uv.user_id WHERE u.status='pending' AND u.role='member' ORDER BY u.created_at DESC");
$requested_payouts_res = mysqli_query($conn, "SELECT p.payout_id, p.cycle_id, p.amount, cy.cycle_number, g.group_name, g.group_id, u.first_name, u.last_name FROM payouts p JOIN cycles cy ON p.cycle_id=cy.cycle_id JOIN groups g ON cy.group_id=g.group_id JOIN group_members m ON p.member_id=m.member_id JOIN users u ON m.user_id=u.user_id WHERE p.status IN ('pending','pending_approval')");

include "../../front-end/views/admin-view.php";
?>