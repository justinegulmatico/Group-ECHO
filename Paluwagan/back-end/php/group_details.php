<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../db.php"; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$active_tab = isset($_GET['tab']) ? mysqli_real_escape_string($conn, trim($_GET['tab'])) : 'overview';

if (!$group_id) {
    header("Location: my_groups.php?error=Invalid Group");
    exit();
}

$today = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| SELF-SUBMITTING BACKEND HANDLERS
|--------------------------------------------------------------------------
*/

// 1. INITIALIZE SCHEDULE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initialize_schedule'])) {
    $group_q = mysqli_query($conn, "SELECT * FROM groups WHERE group_id = $group_id");
    $group = mysqli_fetch_assoc($group_q);
    $amount = floatval($group['contribution_amount'] ?? 0);
    $frequency = strtolower($group['frequency'] ?? 'weekly');
    $creator_id = intval($group['created_by'] ?? 0);

    $members_q = mysqli_query($conn, "SELECT member_id FROM group_members WHERE group_id = $group_id AND user_id != $creator_id AND status='active' ORDER BY joined_at ASC");
    $members = [];
    while($row = mysqli_fetch_assoc($members_q)) { $members[] = $row['member_id']; }

    if (count($members) > 0) {
        $start_date = new DateTime();
        $end_date = clone $start_date;
        $frequency == 'weekly' ? $end_date->modify('+7 days') : $end_date->modify('+1 month');

        $start_str = $start_date->format('Y-m-d');
        $end_str = $end_date->format('Y-m-d');

        mysqli_query($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status) VALUES ($group_id, 1, '$start_str', '$end_str', 'ongoing')");
        $cycle_id = mysqli_insert_id($conn);

        $payout_total = $amount * count($members);
        mysqli_query($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES ($cycle_id, {$members[0]}, $payout_total, '$end_str', 'pending')");

        foreach ($members as $m_id) {
            mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status, payment_method) VALUES ($cycle_id, $m_id, $amount, '$end_str', 'pending', 'Cash')");
        }
        header("Location: group_details.php?id=$group_id&success=Cycle 1 Started!");
        exit();
    }
}

// 2. RECORD MEMBER PAYMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_make_payment'])) {
    $contribution_id = intval($_POST['contribution_id']);
    mysqli_query($conn, "UPDATE contributions SET status='paid', paid_at='$today' WHERE contribution_id = $contribution_id");
    header("Location: group_details.php?id=$group_id&success=Payment tracked!");
    exit();
}

// 3. ADMIN REQUESTS PAYOUT APPROVAL (GATEKEEPER LOGIC)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_request_payout'])) {
    $cycle_id = intval($_POST['current_cycle_id']);
    
    // Check total collected
    $funds_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM contributions WHERE cycle_id = $cycle_id AND status = 'paid'");
    $total_collected = floatval(mysqli_fetch_assoc($funds_q)['total'] ?? 0);
    
    // Check target amount
    $target_q = mysqli_query($conn, "SELECT amount FROM payouts WHERE cycle_id = $cycle_id LIMIT 1");
    $target_amount = floatval(mysqli_fetch_assoc($target_q)['amount'] ?? 0);

    // Gatekeeper: only allow request if funds are sufficient
    if ($total_collected >= $target_amount && $target_amount > 0) {
        mysqli_query($conn, "UPDATE payouts SET status='pending_approval' WHERE cycle_id = $cycle_id AND status='pending'");
        header("Location: group_details.php?id=$group_id&success=Payout requested!");
    } else {
        header("Location: group_details.php?id=$group_id&error=Insufficient funds collected. Cannot request payout.");
    }
    exit();
}

/*
|--------------------------------------------------------------------------
| UI RENDERING QUERIES
|--------------------------------------------------------------------------
*/
$group_q = mysqli_query($conn, "SELECT * FROM groups WHERE group_id = $group_id");
$group = mysqli_fetch_assoc($group_q);
$creator = $group['created_by'] ?? 0;
$amount = floatval($group['contribution_amount'] ?? 0);

$member_q = mysqli_query($conn, "SELECT member_id FROM group_members WHERE group_id = $group_id AND user_id = $current_user_id AND status = 'active' LIMIT 1");
$member = mysqli_fetch_assoc($member_q);
$my_member_id = $member['member_id'] ?? 0;

$current_cycle_id = 0;
$cycle_number = 0;
$cycle_q = mysqli_query($conn, "SELECT cycle_id, cycle_number FROM cycles WHERE group_id = $group_id AND status = 'ongoing' LIMIT 1");
if ($cycle_q && mysqli_num_rows($cycle_q) > 0) {
    $cycle_data = mysqli_fetch_assoc($cycle_q);
    $current_cycle_id = $cycle_data['cycle_id'];
    $cycle_number = $cycle_data['cycle_number'];
}

$payout_status = '';
if ($current_cycle_id > 0) {
    $payout_status_q = mysqli_query($conn, "SELECT status FROM payouts WHERE cycle_id = $current_cycle_id LIMIT 1");
    if ($payout_status_q && mysqli_num_rows($payout_status_q) > 0) {
        $payout_status = mysqli_fetch_assoc($payout_status_q)['status'];
    }
}

$my_pending_contribution_id = 0;
if ($my_member_id > 0 && $current_cycle_id > 0) {
    $pending_q = mysqli_query($conn, "SELECT contribution_id FROM contributions WHERE member_id = $my_member_id AND cycle_id = $current_cycle_id AND status = 'pending' LIMIT 1");
    if ($pending_q && mysqli_num_rows($pending_q) > 0) {
        $my_pending_contribution_id = mysqli_fetch_assoc($pending_q)['contribution_id'];
    }
}

$check_init = mysqli_query($conn, "SELECT cycle_id FROM cycles WHERE group_id = $group_id LIMIT 1");
$schedule_initialized = (mysqli_num_rows($check_init) > 0);

ob_start();
?>
<div style="display: flex; gap: 10px;">
    <?php if (!$schedule_initialized && $current_user_id == $creator): ?>
        <form method="POST" action="">
            <button type="submit" name="initialize_schedule" style="background-color: #d9534f; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                Initialize Group Schedule Matrix
            </button>
        </form>
    <?php endif; ?>

    <?php if ($my_pending_contribution_id > 0): ?>
        <form method="POST" action="" onsubmit="return confirm('Confirm payment?');">
            <input type="hidden" name="contribution_id" value="<?= $my_pending_contribution_id ?>">
            <button type="submit" name="action_make_payment" style="background-color: #fff; color: #111; border: 1px solid #ccc; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                + Payment
            </button>
        </form>
    <?php endif; ?>

    <?php if ($current_user_id == $creator && $payout_status === 'pending'): ?>
        <form method="POST" action="" onsubmit="return confirm('Request to initiate payout pool release?');">
            <input type="hidden" name="current_cycle_id" value="<?= $current_cycle_id ?>">
            <button type="submit" name="action_request_payout" style="background-color: #f0ad4e; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                + Request Payout (Cycle #<?= $cycle_number ?>)
            </button>
        </form>
    <?php endif; ?>
</div>
<?php
$top_action_buttons_html = ob_get_clean();
include "../../front-end/views/group_details-view.php";
?>