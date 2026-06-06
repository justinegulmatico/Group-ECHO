<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($group_id <= 0) { header("Location: my_groups.php?error=Invalid group"); exit(); }

function get_active_members($conn, $gid) {
    $res = mysqli_query($conn, "SELECT member_id FROM group_members WHERE group_id=$gid AND status='active' ORDER BY joined_at ASC");
    $list = [];
    while ($r = mysqli_fetch_assoc($res)) $list[] = (int)$r['member_id'];
    return $list;
}
function get_ongoing_cycle($conn, $gid) {
    $res = mysqli_query($conn, "SELECT * FROM cycles WHERE group_id=$gid AND status='ongoing' LIMIT 1");
    return mysqli_fetch_assoc($res);
}

// 1. INIT CYCLE 1 (creator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initialize_schedule'])) {
    $g = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM groups WHERE group_id=$group_id"));
    if ((int)$g['created_by'] != $current_user_id) { header("Location: group_details.php?id=$group_id&error=Only creator"); exit(); }

    $members = get_active_members($conn, $group_id);
    if (count($members) < 1) { header("Location: group_details.php?id=$group_id&error=No members"); exit(); }

    $amt = floatval($g['contribution_amount']);
    $freq = strtolower($g['frequency'] ?? 'monthly');
    $start = date('Y-m-d');
    $end = new DateTime(); $freq=='weekly' ? $end->modify('+7 days') : $end->modify('+1 month');
    $end_str = $end->format('Y-m-d');

    mysqli_query($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status) VALUES ($group_id, 1, '$start', '$end_str', 'ongoing')");
    $cycle_id = mysqli_insert_id($conn);

    $first = $members[0];
    $pot = $amt * count($members);
    mysqli_query($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES ($cycle_id, $first, $pot, '$end_str', 'pending')");

    foreach ($members as $mid) {
        mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status, payment_method) VALUES ($cycle_id, $mid, $amt, '$end_str', 'pending', 'Cash')");
    }
    header("Location: group_details.php?id=$group_id&success=Cycle 1 started");
    exit();
}

// 2. PAY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_make_payment'])) {
    $cid = (int)$_POST['contribution_id'];
    mysqli_query($conn, "UPDATE contributions SET status='paid', paid_at='" . date('Y-m-d') . "' WHERE contribution_id=$cid");
    header("Location: group_details.php?id=$group_id&success=Paid");
    exit();
}

// 3. RELEASE + NEXT (creator + full collection check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_release_payout'])) {
    $g = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM groups WHERE group_id=$group_id"));
    if ((int)$g['created_by'] != $current_user_id) { header("Location: group_details.php?id=$group_id&error=Only creator"); exit(); }

    $cycle = get_ongoing_cycle($conn, $group_id);
    if (!$cycle) { header("Location: group_details.php?id=$group_id&error=No cycle"); exit(); }
    $cid = (int)$cycle['cycle_id'];
    $cnum = (int)$cycle['cycle_number'];

    $collected = floatval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) t FROM contributions WHERE cycle_id=$cid AND status='paid'"))['t']);
    $target = floatval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT amount FROM payouts WHERE cycle_id=$cid LIMIT 1"))['amount'] ?? 0);

    if ($collected < $target) { header("Location: group_details.php?id=$group_id&error=Not all paid yet"); exit(); }

    $today = date('Y-m-d');
    mysqli_query($conn, "UPDATE cycles SET status='completed' WHERE cycle_id=$cid");
    mysqli_query($conn, "UPDATE payouts SET status='released', payout_date='$today' WHERE cycle_id=$cid");

    $members = get_active_members($conn, $group_id);
    $amt = floatval($g['contribution_amount']);
    $freq = strtolower($g['frequency'] ?? 'monthly');
    $next = $cnum + 1;

    if ($next > count($members)) {
        mysqli_query($conn, "UPDATE groups SET status='completed' WHERE group_id=$group_id");
        header("Location: group_details.php?id=$group_id&success=Finished!"); exit();
    }

    $end = new DateTime(); $freq=='weekly' ? $end->modify('+7 days') : $end->modify('+1 month');
    $end_str = $end->format('Y-m-d');

    mysqli_query($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status) VALUES ($group_id, $next, '$today', '$end_str', 'ongoing')");
    $nextc = mysqli_insert_id($conn);

    $recv = $members[$next-1];
    $pot = $amt * count($members);
    mysqli_query($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES ($nextc, $recv, $pot, '$end_str', 'pending')");

    foreach ($members as $mid) {
        mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status, payment_method) VALUES ($nextc, $mid, $amt, '$end_str', 'pending', 'Cash')");
    }
    header("Location: group_details.php?id=$group_id&success=Released! Cycle $next live");
    exit();
}

// DATA FOR VIEW
$group = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM groups WHERE group_id=$group_id"));
$creator = (int)($group['created_by'] ?? 0);
$my_member = mysqli_fetch_assoc(mysqli_query($conn, "SELECT member_id FROM group_members WHERE group_id=$group_id AND user_id=$current_user_id AND status='active' LIMIT 1"));
$my_member_id = (int)($my_member['member_id'] ?? 0);

$current_cycle = get_ongoing_cycle($conn, $group_id);
$current_cycle_id = $current_cycle ? (int)$current_cycle['cycle_id'] : 0;
$cycle_number = $current_cycle ? (int)$current_cycle['cycle_number'] : 0;

$my_pending_contribution_id = 0;
if ($my_member_id && $current_cycle_id) {
    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT contribution_id FROM contributions WHERE member_id=$my_member_id AND cycle_id=$current_cycle_id AND status='pending' LIMIT 1"));
    $my_pending_contribution_id = $p ? (int)$p['contribution_id'] : 0;
}
$schedule_initialized = $current_cycle_id > 0;
$is_creator = $current_user_id === $creator;

ob_start(); ?>
<div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php if (!$schedule_initialized && $is_creator): ?>
        <form method="POST"><button type="submit" name="initialize_schedule" style="background:#d9534f;color:#fff;border:none;padding:8px 14px;border-radius:4px;font-size:13px;">Initialize Schedule</button></form>
    <?php endif; ?>
    <?php if ($my_pending_contribution_id > 0): ?>
        <form method="POST" onsubmit="return confirm('Record payment?');"><input type="hidden" name="contribution_id" value="<?= $my_pending_contribution_id ?>"><button type="submit" name="action_make_payment" style="background:#fff;color:#111;border:1px solid #ccc;padding:8px 14px;border-radius:4px;font-size:13px;">+ My Payment</button></form>
    <?php endif; ?>
    <?php if ($is_creator && $current_cycle_id): ?>
        <form method="POST" onsubmit="return confirm('Release and advance?');"><button type="submit" name="action_release_payout" style="background:#f0ad4e;color:#fff;border:none;padding:8px 14px;border-radius:4px;font-size:13px;">Release Payout + Next</button></form>
    <?php endif; ?>
</div>
<?php $top_action_buttons_html = ob_get_clean();

$slots_filled = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM group_members WHERE group_id=$group_id AND status='active'"));
$total_collected = 0;
if ($current_cycle_id) {
    $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) t FROM contributions WHERE cycle_id=$current_cycle_id AND status='paid'"));
    $total_collected = floatval($t['t']);
}

include "../../front-end/views/group_details-view.php";
?>