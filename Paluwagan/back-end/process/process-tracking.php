<?php
include_once "../db.php";
session_start();

/*
|--------------------------------------------------------------------------
| ACTION 1: START CYCLE #1 ONLY
|--------------------------------------------------------------------------
*/
if (isset($_POST['initialize_schedule'])) {
    $group_id = intval($_POST['group_id']);

    // Get group info
    $group_q = mysqli_query($conn, "SELECT * FROM groups WHERE group_id = $group_id");
    $group = mysqli_fetch_assoc($group_q);
    $amount = floatval($group['contribution_amount']);
    $frequency = strtolower($group['frequency']);

    // Get active members
    $members_q = mysqli_query($conn, "SELECT member_id FROM group_members WHERE group_id = $group_id AND status='active' ORDER BY joined_at ASC");
    $members = [];
    while($row = mysqli_fetch_assoc($members_q)) { $members[] = $row['member_id']; }

    if (count($members) == 0) {
        header("Location: ../php/group_details.php?id=$group_id&error=No active members.");
        exit();
    }

    // Calculate structural dates
    $start_date = new DateTime();
    $end_date = clone $start_date;
    $frequency == 'weekly' ? $end_date->modify('+7 days') : $end_date->modify('+1 month');

    $start_str = $start_date->format('Y-m-d');
    $end_str = $end_date->format('Y-m-d');

    // 1. Insert Cycle 1
    mysqli_query($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status) VALUES ($group_id, 1, '$start_str', '$end_str', 'ongoing')");
    $cycle_id = mysqli_insert_id($conn);

    // 2. Insert Payout for the first member in rotation array
    $payout_total = $amount * count($members);
    mysqli_query($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES ($cycle_id, {$members[0]}, $payout_total, '$end_str', 'pending')");

    // 3. Insert Contributions for everyone for Cycle 1
    foreach ($members as $m_id) {
        mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status, payment_method) VALUES ($cycle_id, $m_id, $amount, '$end_str', 'pending', 'Cash')");
    }

    header("Location: ../php/group_details.php?id=$group_id&success=Cycle 1 Started Successfully!");
    exit();
}

/*
|--------------------------------------------------------------------------
| ACTION 2: RECORD PAYMENT
|--------------------------------------------------------------------------
*/
if (isset($_POST['action_make_payment'])) {
    $group_id = intval($_POST['group_id']);
    $contribution_id = intval($_POST['contribution_id']);
    $today = date('Y-m-d');

    mysqli_query($conn, "UPDATE contributions SET status='paid', paid_at='$today' WHERE contribution_id = $contribution_id");
    header("Location: ../php/group_details.php?id=$group_id&success=Payment tracked!");
    exit();
}

/*
|--------------------------------------------------------------------------
| ACTION 3: RELEASE PAYOUT + CREATE CYCLE #2 AUTOMATICALLY
|--------------------------------------------------------------------------
*/
if (isset($_POST['action_release_payout'])) {
    $group_id = intval($_POST['group_id']);
    $today = date('Y-m-d');

    // 1. Locate current ongoing cycle details
    $cycle_q = mysqli_query($conn, "SELECT * FROM cycles WHERE group_id = $group_id AND status = 'ongoing' LIMIT 1");
    $current_cycle = mysqli_fetch_assoc($cycle_q);
    $current_cycle_id = $current_cycle['cycle_id'];
    $current_cycle_num = intval($current_cycle['cycle_number']);

    // 2. Set current cycle to completed and release payout vault entry
    mysqli_query($conn, "UPDATE cycles SET status = 'completed' WHERE cycle_id = $current_cycle_id");
    mysqli_query($conn, "UPDATE payouts SET status = 'released', payout_date = '$today' WHERE cycle_id = $current_cycle_id");

    // 3. Get group configuration information to build next step loop
    $group_q = mysqli_query($conn, "SELECT * FROM groups WHERE group_id = $group_id");
    $group = mysqli_fetch_assoc($group_q);
    $amount = floatval($group['contribution_amount']);
    $frequency = strtolower($group['frequency']);

    // Fetch members array again
    $members_q = mysqli_query($conn, "SELECT member_id FROM group_members WHERE group_id = $group_id AND status='active' ORDER BY joined_at ASC");
    $members = [];
    while($row = mysqli_fetch_assoc($members_q)) { $members[] = $row['member_id']; }

    $next_cycle_num = $current_cycle_num + 1;

    // Check if the paluwagan circle rotation limit is reached
    if ($next_cycle_num > count($members)) {
        mysqli_query($conn, "UPDATE groups SET status = 'completed' WHERE group_id = $group_id");
        header("Location: ../php/group_details.php?id=$group_id&success=All rotation cycles completed!");
        exit();
    }

    // 4. AUTOMATICALLY CREATE THE NEXT CHRONOLOGICAL CYCLE ROW
    $start_date = new DateTime();
    $end_date = clone $start_date;
    $frequency == 'weekly' ? $end_date->modify('+7 days') : $end_date->modify('+1 month');

    $start_str = $start_date->format('Y-m-d');
    $end_str = $end_date->format('Y-m-d');

    mysqli_query($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status) VALUES ($group_id, $next_cycle_num, '$start_str', '$end_str', 'ongoing')");
    $next_cycle_id = mysqli_insert_id($conn);

    // 5. AUTOMATICALLY CREATE NEXT PAYOUT FOR THE NEXT MEMBER IN ROTATION
    $next_receiver_index = $next_cycle_num - 1; 
    $payout_total = $amount * count($members);
    mysqli_query($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES ($next_cycle_id, {$members[$next_receiver_index]}, $payout_total, '$end_str', 'pending')");

    // 6. AUTOMATICALLY CREATE FRESH UNPAID CONTRIBUTIONS FOR THIS NEW RUN
    foreach ($members as $m_id) {
        mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status, payment_method) VALUES ($next_cycle_id, $m_id, $amount, '$end_str', 'pending', 'Cash')");
    }

    header("Location: ../php/group_details.php?id=$group_id&success=Payout released! Cycle #" . $next_cycle_num . " is now automatically live.");
    exit();
}