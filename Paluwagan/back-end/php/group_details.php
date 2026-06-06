<?php
session_start();
include "../db.php";

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Helper defined before use
function int_escape($link, $data) {
    return (int)mysqli_real_escape_string($link, trim((string)$data));
}

// 2. Fetch User Profile Context (prepared)
$stmt = mysqli_prepare($conn, "SELECT first_name, last_name, role FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'] ?: '', 0, 1));
}

// 3. Group Validity & Membership Guard Check (prepared)
$stmt = mysqli_prepare($conn, "SELECT g.*, m.member_id FROM groups g 
                  JOIN group_members m ON g.group_id = m.group_id 
                  WHERE g.group_id = ? AND m.user_id = ? AND m.status = 'active' LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $group_id, $current_user_id);
mysqli_stmt_execute($stmt);
$group_guard_res = mysqli_stmt_get_result($stmt);
$current_group = mysqli_fetch_assoc($group_guard_res);
mysqli_stmt_close($stmt);

$is_member = true;
if (!$current_group) {
    // Allow non-members to view public groups (for discovery during PENDING phase)
    $stmt = mysqli_prepare($conn, "SELECT * FROM groups WHERE group_id = ? AND privacy = 'public' AND status IN ('pending', 'active') LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $group_id);
    mysqli_stmt_execute($stmt);
    $public_group = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($public_group) {
        $current_group = $public_group;
        $is_member = false;
        $my_member_id = 0;
    } else {
        header("Location: my_groups.php");
        exit();
    }
} else {
    $my_member_id = (int)$current_group['member_id'];
}

// Compat: many older creates left cycle_length NULL; fall back to max_members for UI
if (empty($current_group['cycle_length']) || (int)$current_group['cycle_length'] < 1) {
    $current_group['cycle_length'] = (int)($current_group['max_members'] ?: 5);
}

// 4. Financial Statistics (prepared)
$stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(c.amount), 0) as total_coll 
    FROM contributions c JOIN cycles cy ON c.cycle_id = cy.cycle_id 
    WHERE cy.group_id = ? AND c.status = 'paid'");
mysqli_stmt_bind_param($stmt, "i", $group_id);
mysqli_stmt_execute($stmt);
$collected_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$total_collected = (float)($collected_data['total_coll'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(p.amount), 0) as total_pay 
    FROM payouts p JOIN cycles cy ON p.cycle_id = cy.cycle_id 
    WHERE cy.group_id = ? AND p.status = 'released'");
mysqli_stmt_bind_param($stmt, "i", $group_id);
mysqli_stmt_execute($stmt);
$payouts_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$total_paid_out = (float)($payouts_data['total_pay'] ?? 0);

$in_pool = $total_collected - $total_paid_out;

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as active_members FROM group_members WHERE group_id = ? AND status = 'active'");
mysqli_stmt_bind_param($stmt, "i", $group_id);
mysqli_stmt_execute($stmt);
$slots_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$slots_filled = (int)($slots_data['active_members'] ?? 0);

// My due (pending contributions for me)
$stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(c.amount), 0) as due_amt FROM contributions c 
             WHERE c.member_id = ? AND c.status = 'pending'");
mysqli_stmt_bind_param($stmt, "i", $my_member_id);
mysqli_stmt_execute($stmt);
$my_due_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$my_balance_due = (float)($my_due_data['due_amt'] ?? $current_group['contribution_amount']);

// === NEW PALUWAGAN CORE DATA (simple student style) ===

// Get all members with their positions (for showing who is in what slot)
$stmt = mysqli_prepare($conn, "
    SELECT gm.member_id, gm.position, u.first_name, u.last_name, u.user_id
    FROM group_members gm
    JOIN users u ON gm.user_id = u.user_id
    WHERE gm.group_id = ? AND gm.status = 'active'
    ORDER BY gm.position ASC
");
mysqli_stmt_bind_param($stmt, "i", $group_id);
mysqli_stmt_execute($stmt);
$members_result = mysqli_stmt_get_result($stmt);
$group_members = [];
while ($m = mysqli_fetch_assoc($members_result)) {
    $group_members[] = $m;
}
mysqli_stmt_close($stmt);

// Get all cycles + compute receiver from position (position N receives in cycle N)
$stmt = mysqli_prepare($conn, "
    SELECT c.* 
    FROM cycles c 
    WHERE c.group_id = ? 
    ORDER BY c.cycle_number ASC
");
mysqli_stmt_bind_param($stmt, "i", $group_id);
mysqli_stmt_execute($stmt);
$cycles_result = mysqli_stmt_get_result($stmt);
$cycles = [];
while ($cy = mysqli_fetch_assoc($cycles_result)) {
    // Find who has this position
    $receiver = null;
    foreach ($group_members as $mem) {
        if ((int)$mem['position'] === (int)$cy['cycle_number']) {
            $receiver = $mem;
            break;
        }
    }
    $cy['receiver'] = $receiver;

    // Count how much has been paid into this cycle
    $stmtPaid = mysqli_prepare($conn, "SELECT COALESCE(SUM(amount),0) as paid FROM contributions WHERE cycle_id = ? AND status='paid'");
    mysqli_stmt_bind_param($stmtPaid, "i", $cy['cycle_id']);
    mysqli_stmt_execute($stmtPaid);
    $paidRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtPaid));
    $cy['collected'] = (float)$paidRow['paid'];
    mysqli_stmt_close($stmtPaid);

    $cycles[] = $cy;
}
mysqli_stmt_close($stmt);

// Handle simple actions (record contribution for a cycle + release payout)
$action_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === ACTIVATE PALUWAGAN (only when all slots full and still PENDING) ===
    if (isset($_POST['activate_paluwagan'])) {
        // Only the real group creator can activate (even in simulation)
        if ((int)$current_group['created_by'] === $current_user_id && $current_group['status'] === 'pending') {
            // Count current members
            $cnt_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM group_members WHERE group_id = ? AND status='active'");
            mysqli_stmt_bind_param($cnt_stmt, "i", $group_id);
            mysqli_stmt_execute($cnt_stmt);
            $cnt = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cnt_stmt))['cnt'] ?? 0);
            mysqli_stmt_close($cnt_stmt);

            if ($cnt >= (int)$current_group['max_members']) {
                // Freeze: set active + generate first cycle contributions
                $act = mysqli_prepare($conn, "UPDATE groups SET status='active' WHERE group_id=?");
                mysqli_stmt_bind_param($act, "i", $group_id);
                mysqli_stmt_execute($act);
                mysqli_stmt_close($act);

                // Generate contribution invoices for cycle 1 for every slot
                $c1_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id=? AND cycle_number=1 LIMIT 1");
                mysqli_stmt_bind_param($c1_stmt, "i", $group_id);
                mysqli_stmt_execute($c1_stmt);
                $c1 = mysqli_fetch_assoc(mysqli_stmt_get_result($c1_stmt));
                mysqli_stmt_close($c1_stmt);

                if ($c1) {
                    $c1_id = (int)$c1['cycle_id'];
                    $slots_stmt = mysqli_prepare($conn, "SELECT member_id FROM group_members WHERE group_id=? AND status='active'");
                    mysqli_stmt_bind_param($slots_stmt, "i", $group_id);
                    mysqli_stmt_execute($slots_stmt);
                    $slots_res = mysqli_stmt_get_result($slots_stmt);
                    while ($slot = mysqli_fetch_assoc($slots_res)) {
                        $ins = mysqli_prepare($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status) 
                                                    VALUES (?, ?, ?, CURDATE(), 'pending')");
                        mysqli_stmt_bind_param($ins, "iid", $c1_id, $slot['member_id'], $current_group['contribution_amount']);
                        mysqli_stmt_execute($ins);
                        mysqli_stmt_close($ins);
                    }
                    mysqli_stmt_close($slots_stmt);
                }
                header("Location: group_details.php?id=$group_id&success=" . urlencode("Paluwagan ACTIVATED! First cycle invoices generated."));
                exit();
            }
        }
    }

    // Record my contribution for a specific cycle (hulugan)
    if (isset($_POST['record_contribution'])) {
        $cycle_id = (int)$_POST['cycle_id'];
        $amount = (float)$_POST['amount'];

        // Prevent duplicate for same member + cycle
        $chk = mysqli_prepare($conn, "SELECT 1 FROM contributions WHERE cycle_id=? AND member_id=?");
        mysqli_stmt_bind_param($chk, "ii", $cycle_id, $my_member_id);
        mysqli_stmt_execute($chk);
        $exists = mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0;
        mysqli_stmt_close($chk);

        if (!$exists) {
            $ins = mysqli_prepare($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, paid_at, status) VALUES (?, ?, ?, CURDATE(), CURDATE(), 'paid')");
            mysqli_stmt_bind_param($ins, "iid", $cycle_id, $my_member_id, $amount);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
            $action_msg = "Contribution recorded for this cycle!";
        } else {
            $action_msg = "You already recorded for this cycle.";
        }
        header("Location: group_details.php?id=$group_id&success=" . urlencode($action_msg));
        exit();
    }

    // Release payout for a cycle (owner or admin can do this - simulation)
    if (isset($_POST['release_payout'])) {
        $cycle_id = (int)$_POST['cycle_id'];
        $payout_amount = (float)$_POST['payout_amount'];

        // Find the cycle and its receiver
        $cycStmt = mysqli_prepare($conn, "SELECT * FROM cycles WHERE cycle_id = ?");
        mysqli_stmt_bind_param($cycStmt, "i", $cycle_id);
        mysqli_stmt_execute($cycStmt);
        $cyc = mysqli_fetch_assoc(mysqli_stmt_get_result($cycStmt));
        mysqli_stmt_close($cycStmt);

        if ($cyc && $cyc['payout_status'] === 'pending' && $cyc['payout_member_id'] == null) {
            // Find receiver by position
            $receiver_id = null;
            foreach ($group_members as $mem) {
                if ((int)$mem['position'] === (int)$cyc['cycle_number']) {
                    $receiver_id = (int)$mem['member_id'];
                    break;
                }
            }

            if ($receiver_id) {
                // Record the payout
                $pstmt = mysqli_prepare($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES (?, ?, ?, CURDATE(), 'released')");
                mysqli_stmt_bind_param($pstmt, "iid", $cycle_id, $receiver_id, $payout_amount);
                mysqli_stmt_execute($pstmt);
                mysqli_stmt_close($pstmt);

                // Mark cycle as released
                $up = mysqli_prepare($conn, "UPDATE cycles SET payout_member_id=?, payout_status='released' WHERE cycle_id=?");
                mysqli_stmt_bind_param($up, "ii", $receiver_id, $cycle_id);
                mysqli_stmt_execute($up);
                mysqli_stmt_close($up);

                $action_msg = "Payout released for cycle #" . $cyc['cycle_number'] . "!";
            }
        }
        header("Location: group_details.php?id=$group_id&success=" . urlencode($action_msg));
        exit();
    }
}

// 5. RENDER PHASE
include "../../front-end/views/group_details-view.php";
?>