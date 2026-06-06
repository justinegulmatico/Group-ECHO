<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$original_user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$target_group_id = isset($_POST['current_group_id']) ? (int)$_POST['current_group_id'] : 0;

// === SWITCH USER (for presentation - sandbox only) ===
if ($action === 'switch_user' && isset($_POST['switch_to_user'])) {
    $new_id = (int)$_POST['switch_to_user'];
    if ($new_id === $original_user_id) {
        unset($_SESSION['simulation_user_id']);
    } else {
        $_SESSION['simulation_user_id'] = $new_id;
    }
    header("Location: ../php/dashboard.php?success=" . urlencode("Switched simulation identity."));
    exit();
}

if ($action === 'reset_user') {
    unset($_SESSION['simulation_user_id']);
    header("Location: ../php/dashboard.php?success=" . urlencode("Reset to your real account."));
    exit();
}

// Get the effective user for simulation actions
$effective_user_id = $_SESSION['simulation_user_id'] ?? $original_user_id;

// === SIMULATE NEXT CYCLE ===
// Advances the group if all current cycle contributions are PAID
if ($action === 'simulate_next_cycle' && $target_group_id > 0) {
    // Check if all slots for current cycle are paid
    $stmt = mysqli_prepare($conn, "
        SELECT g.current_cycle, COUNT(*) as total_slots,
               SUM(CASE WHEN c.status = 'paid' THEN 1 ELSE 0 END) as paid_slots
        FROM groups g
        JOIN group_members gm ON g.group_id = gm.group_id
        LEFT JOIN contributions c ON c.cycle_id = (
            SELECT cycle_id FROM cycles WHERE group_id = g.group_id AND cycle_number = g.current_cycle LIMIT 1
        ) AND c.member_id = gm.member_id
        WHERE g.group_id = ? AND gm.status = 'active'
        GROUP BY g.group_id
    ");
    mysqli_stmt_bind_param($stmt, "i", $target_group_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row && (int)$row['paid_slots'] >= (int)$row['total_slots']) {
        // All paid - advance cycle
        $new_cycle = (int)$row['current_cycle'] + 1;

        // Update group current cycle
        $up = mysqli_prepare($conn, "UPDATE groups SET current_cycle = ? WHERE group_id = ?");
        mysqli_stmt_bind_param($up, "ii", $new_cycle, $target_group_id);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);

        // Generate invoices (contributions) for the new cycle for all active slots
        $slots_stmt = mysqli_prepare($conn, "SELECT member_id FROM group_members WHERE group_id = ? AND status = 'active'");
        mysqli_stmt_bind_param($slots_stmt, "i", $target_group_id);
        mysqli_stmt_execute($slots_stmt);
        $slots_res = mysqli_stmt_get_result($slots_stmt);

        $cycle_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id = ? AND cycle_number = ? LIMIT 1");
        mysqli_stmt_bind_param($cycle_stmt, "ii", $target_group_id, $new_cycle);
        mysqli_stmt_execute($cycle_stmt);
        $cycle_row = mysqli_fetch_assoc(mysqli_stmt_get_result($cycle_stmt));
        mysqli_stmt_close($cycle_stmt);

        if ($cycle_row) {
            $new_cycle_id = (int)$cycle_row['cycle_id'];
            while ($slot = mysqli_fetch_assoc($slots_res)) {
                $ins = mysqli_prepare($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status) 
                                            VALUES (?, ?, (SELECT contribution_amount FROM groups WHERE group_id = ?), CURDATE(), 'pending')");
                $amt_group = $target_group_id;
                mysqli_stmt_bind_param($ins, "iii", $new_cycle_id, $slot['member_id'], $amt_group);
                mysqli_stmt_execute($ins);
                mysqli_stmt_close($ins);
            }
        }
        mysqli_stmt_close($slots_stmt);

        header("Location: ../php/group_details.php?id=$target_group_id&success=" . urlencode("Cycle advanced to #$new_cycle! New invoices generated."));
        exit();
    } else {
        header("Location: ../php/group_details.php?id=$target_group_id&error=" . urlencode("Cannot advance - not all payments collected yet."));
        exit();
    }
}

// === SIMULATE BULK PAYMENTS (for all dummy slots in current cycle) ===
if ($action === 'simulate_bulk_pay' && $target_group_id > 0) {
    $current_cycle_stmt = mysqli_prepare($conn, "SELECT current_cycle FROM groups WHERE group_id = ?");
    mysqli_stmt_bind_param($current_cycle_stmt, "i", $target_group_id);
    mysqli_stmt_execute($current_cycle_stmt);
    $g = mysqli_fetch_assoc(mysqli_stmt_get_result($current_cycle_stmt));
    mysqli_stmt_close($current_cycle_stmt);

    $cur_cycle = (int)($g['current_cycle'] ?? 1);

    $cycle_id_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id=? AND cycle_number=? LIMIT 1");
    mysqli_stmt_bind_param($cycle_id_stmt, "ii", $target_group_id, $cur_cycle);
    mysqli_stmt_execute($cycle_id_stmt);
    $cyc = mysqli_fetch_assoc(mysqli_stmt_get_result($cycle_id_stmt));
    mysqli_stmt_close($cycle_id_stmt);

    if ($cyc) {
        $cycle_id = (int)$cyc['cycle_id'];
        // Mark all pending contributions for this cycle as paid (simulation bulk)
        $bulk = mysqli_prepare($conn, "UPDATE contributions SET status='paid', paid_at=CURDATE() 
                                      WHERE cycle_id = ? AND status != 'paid'");
        mysqli_stmt_bind_param($bulk, "i", $cycle_id);
        mysqli_stmt_execute($bulk);
        mysqli_stmt_close($bulk);
    }

    header("Location: ../php/group_details.php?id=$target_group_id&success=" . urlencode("Bulk simulated payments applied for current cycle."));
    exit();
}

// === FORCE DEFAULT (from group details - for a specific slot/member) ===
if ($action === 'force_default' && isset($_POST['member_id']) && $target_group_id > 0) {
    $member_id = (int)$_POST['member_id'];
    $late_fee = 50.00; // Fixed simple penalty for demo

    $cur_stmt = mysqli_prepare($conn, "SELECT current_cycle FROM groups WHERE group_id=?");
    mysqli_stmt_bind_param($cur_stmt, "i", $target_group_id);
    mysqli_stmt_execute($cur_stmt);
    $cur = mysqli_fetch_assoc(mysqli_stmt_get_result($cur_stmt));
    mysqli_stmt_close($cur_stmt);

    $cycle_num = (int)($cur['current_cycle'] ?? 1);

    $cyc_id_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id=? AND cycle_number=?");
    mysqli_stmt_bind_param($cyc_id_stmt, "ii", $target_group_id, $cycle_num);
    mysqli_stmt_execute($cyc_id_stmt);
    $cyc_row = mysqli_fetch_assoc(mysqli_stmt_get_result($cyc_id_stmt));
    mysqli_stmt_close($cyc_id_stmt);

    if ($cyc_row) {
        $cycle_id = (int)$cyc_row['cycle_id'];
        // Mark as late/default and add fee (we store fee by increasing the amount or separate note)
        $upd = mysqli_prepare($conn, "UPDATE contributions SET status='late', amount = amount + ? 
                                     WHERE cycle_id=? AND member_id=?");
        mysqli_stmt_bind_param($upd, "dii", $late_fee, $cycle_id, $member_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }

    header("Location: ../php/group_details.php?id=$target_group_id&success=" . urlencode("Forced default + late fee applied."));
    exit();
}

// === ABSORB SLOT (transfer ownership of a dummy slot to the main tester) ===
if ($action === 'absorb_slot' && isset($_POST['member_id']) && $target_group_id > 0) {
    $member_id = (int)$_POST['member_id'];

    // Transfer the slot to the original logged-in tester (or current effective if wanted)
    $transfer_to = $original_user_id;

    $upd = mysqli_prepare($conn, "UPDATE group_members SET user_id = ? WHERE member_id = ? AND group_id = ?");
    mysqli_stmt_bind_param($upd, "iii", $transfer_to, $member_id, $target_group_id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    header("Location: ../php/group_details.php?id=$target_group_id&success=" . urlencode("Slot absorbed by main tester account."));
    exit();
}

// Fallback
header("Location: ../php/dashboard.php");
exit();
?>