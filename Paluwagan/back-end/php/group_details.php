<?php
session_start();
// Corrected path to move up one folder to reach db.php in back-end/
include "../db.php";

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    // Corrected path: move up 3 steps (php/ -> back-end/ -> Paluwagan/ -> Group-ECHO/) to reach index.php
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? int_escape($conn, $_GET['id']) : 0;
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Helper to clean variables safely
function int_escape($link, $data) {
    return (int)mysqli_real_escape_string($link, trim($data));
}

// 2. Fetch User Profile Context
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1));
}

// 3. Group Validity & Membership Guard Check
$group_guard_q = "SELECT g.*, m.member_id FROM groups g 
                  JOIN group_members m ON g.group_id = m.group_id 
                  WHERE g.group_id = '$group_id' AND m.user_id = '$current_user_id' AND m.status = 'active'";
$group_guard_res = mysqli_query($conn, $group_guard_q);
$current_group = mysqli_fetch_assoc($group_guard_res);

if (!$current_group) {
    // Redirects to dashboard.php or my_groups.php which are in the same directory (back-end/php/)
    header("Location: my_groups.php");
    exit();
}

$my_member_id = $current_group['member_id'];

// 4. Financial Statistics Aggregation for Header Metrics
// Total Collected across all historic cycles for this specific group
$collected_q = "SELECT SUM(c.amount) as total_coll FROM contributions c 
                JOIN cycles cy ON c.cycle_id = cy.cycle_id 
                WHERE cy.group_id = '$group_id' AND c.status = 'paid'";
$collected_res = mysqli_query($conn, $collected_q);
$collected_data = mysqli_fetch_assoc($collected_res);
$total_collected = $collected_data['total_coll'] ?? 0.00;

// Total Payouts Released out of the ecosystem pool
$payouts_q = "SELECT SUM(p.amount) as total_pay FROM payouts p 
              JOIN cycles cy ON p.cycle_id = cy.cycle_id 
              WHERE cy.group_id = '$group_id' AND p.status = 'released'";
$payouts_res = mysqli_query($conn, $payouts_q);
$payouts_data = mysqli_fetch_assoc($payouts_res);
$total_paid_out = $payouts_data['total_pay'] ?? 0.00;

// Current Capital Available inside the Pool Vault
$in_pool = $total_collected - $total_paid_out;

// Total slots filled metric configuration mapping
$slots_q = "SELECT COUNT(*) as active_members FROM group_members WHERE group_id = '$group_id' AND status = 'active'";
$slots_res = mysqli_query($conn, $slots_q);
$slots_data = mysqli_fetch_assoc($slots_res);
$slots_filled = $slots_data['active_members'] ?? 0;

// Calculate My Specific Due Balance configuration parameters
$my_due_q = "SELECT SUM(c.amount) as due_amt FROM contributions c 
             WHERE c.member_id = '$my_member_id' AND c.status = 'pending'";
$my_due_res = mysqli_query($conn, $my_due_q);
$my_due_data = mysqli_fetch_assoc($my_due_res);
$my_balance_due = $my_due_data['due_amt'] ?? $current_group['contribution_amount'];

// 5. RENDER PHASE
// Corrected path: move up 2 steps (php/ -> back-end/), then enter front-end/views/
include "../../front-end/views/group_details-view.php";
?>