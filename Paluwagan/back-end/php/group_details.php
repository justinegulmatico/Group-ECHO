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

// Auto-create wallet_requests table if it doesn't exist yet (for auto-payouts, record payments, and wallet features)
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
if (!($table_check && mysqli_num_rows($table_check) > 0)) {
    $create_sql = "
    CREATE TABLE IF NOT EXISTS `wallet_requests` (
      `request_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `type` enum('deposit','withdraw') NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `payment_method` varchar(60) DEFAULT NULL,
      `account_details` text DEFAULT NULL,
      `attachment` varchar(255) DEFAULT NULL,
      `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      `reviewed_by` int(11) DEFAULT NULL,
      `reviewed_at` datetime DEFAULT NULL,
      PRIMARY KEY (`request_id`),
      KEY `user_id` (`user_id`),
      KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    mysqli_query($conn, $create_sql);
}

// Auto-create group_history table for proper activity logging
$history_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'group_history'");
if (!($history_table_check && mysqli_num_rows($history_table_check) > 0)) {
    $history_create = "
    CREATE TABLE IF NOT EXISTS `group_history` (
      `history_id` int(11) NOT NULL AUTO_INCREMENT,
      `group_id` int(11) NOT NULL,
      `event_type` varchar(50) NOT NULL COMMENT 'payment, payout, member_joined, group_activated, group_closed, etc.',
      `actor_user_id` int(11) DEFAULT NULL,
      `target_user_id` int(11) DEFAULT NULL,
      `cycle_number` int(11) DEFAULT NULL,
      `amount` decimal(10,2) DEFAULT NULL,
      `description` text,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`history_id`),
      KEY `group_id` (`group_id`),
      KEY `event_type` (`event_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    mysqli_query($conn, $history_create);
}

// Helper to log history events
function log_group_history($conn, $group_id, $event_type, $actor_user_id = null, $target_user_id = null, $cycle_number = null, $amount = null, $description = '') {
    $stmt = mysqli_prepare($conn, "
        INSERT INTO group_history 
        (group_id, event_type, actor_user_id, target_user_id, cycle_number, amount, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isiiids", $group_id, $event_type, $actor_user_id, $target_user_id, $cycle_number, $amount, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// === WALLET BALANCE (for Record Payment - use wallet instead of external method) ===
$wallet_balance = 0.00;
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
  $wstmt = mysqli_prepare($conn, "
    SELECT 
      COALESCE(SUM(CASE WHEN type = 'deposit' AND status = 'approved' THEN amount ELSE 0 END), 0) -
      COALESCE(SUM(CASE WHEN type = 'withdraw' AND status = 'approved' THEN amount ELSE 0 END), 0) AS balance
    FROM wallet_requests 
    WHERE user_id = ?
  ");
  if ($wstmt) {
    mysqli_stmt_bind_param($wstmt, "i", $current_user_id);
    mysqli_stmt_execute($wstmt);
    $wres = mysqli_stmt_get_result($wstmt);
    if ($wrow = mysqli_fetch_assoc($wres)) {
      $wallet_balance = (float)($wrow['balance'] ?? 0);
    }
    mysqli_stmt_close($wstmt);
  }
}

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

// === Determine Active Cycle for Overview Highlight + Auto Payout ===
$active_cycle = null;
$full_pot = (float)$current_group['contribution_amount'] * count($group_members);

foreach ($cycles as $c) {
  $pstat = $c['payout_status'] ?? 'pending';
  if ($pstat !== 'released') {
    $active_cycle = $c;
    break;
  }
}

// Compute paid count + full_pot for the active cycle (for progress bar)
if ($active_cycle) {
  $ac_id = (int)$active_cycle['cycle_id'];
  $pc_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM contributions WHERE cycle_id = ? AND status = 'paid'");
  mysqli_stmt_bind_param($pc_stmt, "i", $ac_id);
  mysqli_stmt_execute($pc_stmt);
  $pc_row = mysqli_fetch_assoc(mysqli_stmt_get_result($pc_stmt));
  mysqli_stmt_close($pc_stmt);

  $active_cycle['paid_count'] = (int)($pc_row['cnt'] ?? 0);
  $active_cycle['full_pot'] = $full_pot;
}

// Has the current user already paid for the active cycle?
$user_paid_for_active_cycle = false;
if ($active_cycle && $my_member_id > 0) {
  $upc_stmt = mysqli_prepare($conn, "SELECT 1 FROM contributions WHERE cycle_id = ? AND member_id = ? AND status = 'paid' LIMIT 1");
  mysqli_stmt_bind_param($upc_stmt, "ii", $active_cycle['cycle_id'], $my_member_id);
  mysqli_stmt_execute($upc_stmt);
  $user_paid_for_active_cycle = mysqli_num_rows(mysqli_stmt_get_result($upc_stmt)) > 0;
  mysqli_stmt_close($upc_stmt);
}

// === AUTO-PAYOUT AUTOMATION (when cycle is fully funded) ===
// Call this after loading cycles so that when all contributions are in, money moves to receiver wallet automatically.
function try_auto_payout_cycle($conn, $group_id, $cycle, $receiver, $full_pot, $current_user_id) {
  if (!$cycle || empty($receiver) || $full_pot <= 0) return false;

  // Ensure organized upload folders exist (documents for receipts, payments for contribution proofs)
  $assets_uploads_base = __DIR__ . '/../../assets/uploads';
  $documents_dir = $assets_uploads_base . '/documents';
  $payments_dir = $assets_uploads_base . '/payments';
  if (!is_dir($documents_dir)) @mkdir($documents_dir, 0755, true);
  if (!is_dir($payments_dir)) @mkdir($payments_dir, 0755, true);

  $cycle_id = (int)$cycle['cycle_id'];
  $pstat = $cycle['payout_status'] ?? 'pending';
  if ($pstat === 'released') return false;

  $collected = (float)($cycle['collected'] ?? 0);
  if ($collected + 0.01 < $full_pot) return false; // not fully paid yet

  // Idempotency: check if a payout already exists for this cycle
  $chk = mysqli_prepare($conn, "SELECT payout_id FROM payouts WHERE cycle_id = ? LIMIT 1");
  mysqli_stmt_bind_param($chk, "i", $cycle_id);
  mysqli_stmt_execute($chk);
  $already = mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0;
  mysqli_stmt_close($chk);

  if ($already) {
    // Ensure cycle flag is set
    $fix = mysqli_prepare($conn, "UPDATE cycles SET payout_status='released', payout_member_id=? WHERE cycle_id=?");
    mysqli_stmt_bind_param($fix, "ii", $receiver['member_id'], $cycle_id);
    mysqli_stmt_execute($fix);
    mysqli_stmt_close($fix);
    return true;
  }

  // 1. Record the payout
  $pstmt = mysqli_prepare($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES (?, ?, ?, CURDATE(), 'released')");
  mysqli_stmt_bind_param($pstmt, "iid", $cycle_id, $receiver['member_id'], $full_pot);
  mysqli_stmt_execute($pstmt);
  mysqli_stmt_close($pstmt);

  // 2. Mark cycle released
  $upc = mysqli_prepare($conn, "UPDATE cycles SET payout_member_id=?, payout_status='released' WHERE cycle_id=?");
  mysqli_stmt_bind_param($upc, "ii", $receiver['member_id'], $cycle_id);
  mysqli_stmt_execute($upc);
  mysqli_stmt_close($upc);

  // 3. Credit the receiver's wallet balance via an approved internal deposit
  // Guard to prevent fatal error if wallet_requests table doesn't exist yet
  $wallet_table_exists = false;
  $tcheck = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
  if ($tcheck && mysqli_num_rows($tcheck) > 0) $wallet_table_exists = true;

  if ($wallet_table_exists) {
    $note = "Auto-payout • Group #{$group_id} • Cycle #{$cycle['cycle_number']}";
    $wstmt = mysqli_prepare($conn, "
      INSERT INTO wallet_requests 
      (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by) 
      VALUES (?, 'deposit', ?, 'Auto Payout', ?, 'approved', NOW(), NOW(), ?)
    ");
    if ($wstmt) {
      mysqli_stmt_bind_param($wstmt, "idsi", $receiver['user_id'], $full_pot, $note, $current_user_id);
      mysqli_stmt_execute($wstmt);
      mysqli_stmt_close($wstmt);
    }
  }

  // Log payout to history
  log_group_history($conn, $group_id, 'payout', $current_user_id, $receiver['user_id'], $cycle['cycle_number'], $full_pot,
    "Auto-payout of ₱" . number_format($full_pot, 2) . " released");

  return true;
}

// Run auto-payout check for the current active cycle (safe + idempotent)
if ($active_cycle && !empty($active_cycle['receiver'])) {
  $did_auto = try_auto_payout_cycle($conn, $group_id, $active_cycle, $active_cycle['receiver'], $full_pot, $current_user_id);

  // If we just auto-released, refresh the active cycle's payout status/collected in memory for this render
  if ($did_auto) {
    $active_cycle['payout_status'] = 'released';
    // Recompute collected just in case (though it shouldn't have changed)
    $ref_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(amount),0) as paid FROM contributions WHERE cycle_id = ? AND status='paid'");
    mysqli_stmt_bind_param($ref_stmt, "i", $active_cycle['cycle_id']);
    mysqli_stmt_execute($ref_stmt);
    $ref_row = mysqli_fetch_assoc(mysqli_stmt_get_result($ref_stmt));
    mysqli_stmt_close($ref_stmt);
    $active_cycle['collected'] = (float)($ref_row['paid'] ?? $active_cycle['collected']);

    // Refresh global payout stats so the 4 metric cards are accurate after auto-payout
    $payouts_data2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(p.amount), 0) as total_pay 
        FROM payouts p JOIN cycles cy ON p.cycle_id = cy.cycle_id 
        WHERE cy.group_id = {$group_id} AND p.status = 'released'"));
    $total_paid_out = (float)($payouts_data2['total_pay'] ?? $total_paid_out);
    $in_pool = $total_collected - $total_paid_out;
  }
}

// Handle simple actions (record contribution for a cycle + release payout)
$action_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === ACTIVATE PALUWAGAN (allow partial activation once >= 3 members; freezes roster) ===
    if (isset($_POST['activate_paluwagan'])) {
        // Only the real group creator can activate
        if ((int)$current_group['created_by'] === $current_user_id && $current_group['status'] === 'pending') {
            // Count current members
            $cnt_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM group_members WHERE group_id = ? AND status='active'");
            mysqli_stmt_bind_param($cnt_stmt, "i", $group_id);
            mysqli_stmt_execute($cnt_stmt);
            $cnt = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cnt_stmt))['cnt'] ?? 0);
            mysqli_stmt_close($cnt_stmt);

            if ($cnt >= 3) {
                $activated_size = $cnt;

                // Freeze roster size (shrink if not full) so no more members can join
                $upd = mysqli_prepare($conn, "UPDATE groups SET status='active', max_members=?, cycle_length=? WHERE group_id=?");
                mysqli_stmt_bind_param($upd, "iii", $activated_size, $activated_size, $group_id);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);

                // Trim any pre-created extra cycles beyond the activated roster size
                $trim_stmt = mysqli_prepare($conn, "DELETE FROM cycles WHERE group_id = ? AND cycle_number > ?");
                mysqli_stmt_bind_param($trim_stmt, "ii", $group_id, $activated_size);
                mysqli_stmt_execute($trim_stmt);
                mysqli_stmt_close($trim_stmt);

                // Generate contribution invoices (pending) for cycle 1 for the current members only
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

                // Log activation
                log_group_history($conn, $group_id, 'group_activated', $current_user_id, null, 1, null, 
                    "Group activated with {$activated_size} members - rotation started (partial roster frozen)");

                header("Location: group_details.php?id=$group_id&success=" . urlencode("Paluwagan ACTIVATED with {$activated_size} members! Roster is now locked. First cycle invoices generated."));
                exit();
            } else {
                header("Location: group_details.php?id=$group_id&error=" . urlencode("At least 3 members are required to activate the group."));
                exit();
            }
        }
    }

    // Record my contribution for a specific cycle (hulugan) — now uses wallet balance
    if (isset($_POST['record_contribution'])) {
        $cycle_id = (int)$_POST['cycle_id'];
        $amount = (float)$_POST['amount'];

        // Prevent duplicate for same member + cycle
        $chk = mysqli_prepare($conn, "SELECT 1 FROM contributions WHERE cycle_id=? AND member_id=?");
        mysqli_stmt_bind_param($chk, "ii", $cycle_id, $my_member_id);
        mysqli_stmt_execute($chk);
        $exists = mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0;
        mysqli_stmt_close($chk);

        if ($exists) {
            $action_msg = "You already recorded for this cycle.";
        } else {
            // Wallet balance check
            $wbal = 0.00;
            $tcheck = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
            if ($tcheck && mysqli_num_rows($tcheck) > 0) {
              $wst = mysqli_prepare($conn, "
                SELECT 
                  COALESCE(SUM(CASE WHEN type = 'deposit' AND status = 'approved' THEN amount ELSE 0 END), 0) -
                  COALESCE(SUM(CASE WHEN type = 'withdraw' AND status = 'approved' THEN amount ELSE 0 END), 0) AS balance
                FROM wallet_requests WHERE user_id = ?
              ");
              if ($wst) {
                mysqli_stmt_bind_param($wst, "i", $current_user_id);
                mysqli_stmt_execute($wst);
                if ($wr = mysqli_fetch_assoc(mysqli_stmt_get_result($wst))) $wbal = (float)($wr['balance'] ?? 0);
                mysqli_stmt_close($wst);
              }
            }

            if ($amount > $wbal) {
                $action_msg = "Insufficient wallet balance.";
                header("Location: group_details.php?id=$group_id&error=" . urlencode($action_msg));
                exit();
            }

            $ins = mysqli_prepare($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, paid_at, status) VALUES (?, ?, ?, CURDATE(), CURDATE(), 'paid')");
            mysqli_stmt_bind_param($ins, "iid", $cycle_id, $my_member_id, $amount);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            // Auto-deduct from wallet (internal) — guarded
            $wallet_table_exists = false;
            $tcheck = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
            if ($tcheck && mysqli_num_rows($tcheck) > 0) $wallet_table_exists = true;

            if ($wallet_table_exists) {
              $note = "Group contribution (direct) - Group #{$group_id}";
              $ded = mysqli_prepare($conn, "INSERT INTO wallet_requests (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by) VALUES (?, 'withdraw', ?, 'Internal Wallet', ?, 'approved', NOW(), NOW(), ?)");
              if ($ded) {
                mysqli_stmt_bind_param($ded, "idsi", $current_user_id, $amount, $note, $current_user_id);
                mysqli_stmt_execute($ded);
                mysqli_stmt_close($ded);
              }
            }

            $action_msg = "Contribution recorded for this cycle! (Deducted from wallet)";

            // Log to history
            log_group_history($conn, $group_id, 'payment', $current_user_id, $current_user_id, null, $amount, 
                "Paid ₱" . number_format($amount, 2) . " for cycle");

            // === Try to auto-release this cycle if it is now fully funded ===
            // Find the cycle object
            $just_paid_cycle = null;
            foreach ($cycles as $cyc) {
              if ((int)$cyc['cycle_id'] === $cycle_id) { $just_paid_cycle = $cyc; break; }
            }
            if ($just_paid_cycle) {
              $rec_for_cycle = $just_paid_cycle['receiver'] ?? null;
              if ($rec_for_cycle) {
                try_auto_payout_cycle($conn, $group_id, $just_paid_cycle, $rec_for_cycle, $full_pot, $current_user_id);
              }
            }
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

                // Also record in transactions fact table for OLAP
                $trans_stmt = mysqli_prepare($conn, "INSERT INTO transactions (group_id, cycle_id, member_id, user_id, transaction_type, amount, transaction_date, status, recorded_by) 
                                                    VALUES (?, ?, ?, ?, 'payout', ?, CURDATE(), 'completed', ?)");
                mysqli_stmt_bind_param($trans_stmt, "iiidii", $group_id, $cycle_id, $receiver_id, $current_user_id, $payout_amount, $current_user_id);
                mysqli_stmt_execute($trans_stmt);
                mysqli_stmt_close($trans_stmt);

                // Mark cycle as released
                $up = mysqli_prepare($conn, "UPDATE cycles SET payout_member_id=?, payout_status='released' WHERE cycle_id=?");
                mysqli_stmt_bind_param($up, "ii", $receiver_id, $cycle_id);
                mysqli_stmt_execute($up);
                mysqli_stmt_close($up);

                // Also credit receiver wallet (for legacy/manual payout path)
                $note = "Manual payout • Group #{$group_id} • Cycle #{$cyc['cycle_number']}";
                // Find the actual user_id for the receiver member
                $receiver_user_id = null;
                foreach ($group_members as $gm) {
                  if ((int)$gm['member_id'] === (int)$receiver_id) { $receiver_user_id = (int)$gm['user_id']; break; }
                }
                if ($receiver_user_id) {
                  $wallet_table_exists = false;
                  $tcheck = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
                  if ($tcheck && mysqli_num_rows($tcheck) > 0) $wallet_table_exists = true;

                  if ($wallet_table_exists) {
                    $wcred = mysqli_prepare($conn, "INSERT INTO wallet_requests (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by) VALUES (?, 'deposit', ?, 'Payout', ?, 'approved', NOW(), NOW(), ?)");
                    if ($wcred) {
                      mysqli_stmt_bind_param($wcred, "idsi", $receiver_user_id, $payout_amount, $note, $current_user_id);
                      mysqli_stmt_execute($wcred);
                      mysqli_stmt_close($wcred);
                    }
                  }
                }

                $action_msg = "Payout released for cycle #" . $cyc['cycle_number'] . "!";

                // Log to history
                log_group_history($conn, $group_id, 'payout', $current_user_id, $receiver_id, $cyc['cycle_number'], $payout_amount,
                    "Payout of ₱" . number_format($payout_amount, 2) . " released to position #" . $cyc['cycle_number']);
            }
        }
        header("Location: group_details.php?id=$group_id&success=" . urlencode($action_msg));
        exit();
    }
}

// 5. RENDER PHASE

// Fetch group history for the History tab (payments, payouts, joins, activations, closures etc.)
$group_history = [];
$hist_stmt = mysqli_prepare($conn, "
    SELECT gh.*, 
           u1.first_name as actor_first, u1.last_name as actor_last,
           u2.first_name as target_first, u2.last_name as target_last
    FROM group_history gh
    LEFT JOIN users u1 ON gh.actor_user_id = u1.user_id
    LEFT JOIN users u2 ON gh.target_user_id = u2.user_id
    WHERE gh.group_id = ?
    ORDER BY gh.created_at DESC
    LIMIT 50
");
if ($hist_stmt) {
    mysqli_stmt_bind_param($hist_stmt, "i", $group_id);
    mysqli_stmt_execute($hist_stmt);
    $hist_res = mysqli_stmt_get_result($hist_stmt);
    while ($row = mysqli_fetch_assoc($hist_res)) {
        $group_history[] = $row;
    }
    mysqli_stmt_close($hist_stmt);
}

// Also enrich with some real events from other tables if no dedicated log yet (for payments/payouts/joins)
if (empty($group_history)) {
    // Fallback: pull recent contributions as payments
    $pay_stmt = mysqli_prepare($conn, "
        SELECT c.paid_at as created_at, c.amount, gm.user_id as target_user_id,
               'payment' as event_type, c.cycle_id,
               u.first_name as target_first, u.last_name as target_last,
               cy.cycle_number
        FROM contributions c
        JOIN group_members gm ON c.member_id = gm.member_id
        JOIN users u ON gm.user_id = u.user_id
        JOIN cycles cy ON c.cycle_id = cy.cycle_id
        WHERE gm.group_id = ? AND c.status = 'paid'
        ORDER BY c.paid_at DESC LIMIT 20
    ");
    if ($pay_stmt) {
        mysqli_stmt_bind_param($pay_stmt, "i", $group_id);
        mysqli_stmt_execute($pay_stmt);
        $pay_res = mysqli_stmt_get_result($pay_stmt);
        while ($p = mysqli_fetch_assoc($pay_res)) {
            $group_history[] = [
                'created_at' => $p['created_at'],
                'event_type' => 'payment',
                'target_first' => $p['target_first'],
                'target_last' => $p['target_last'],
                'amount' => $p['amount'],
                'cycle_number' => $p['cycle_number'],
                'description' => 'Contribution recorded'
            ];
        }
        mysqli_stmt_close($pay_stmt);
    }
}

include "../../front-end/views/group_details-view.php";
?>