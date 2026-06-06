<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

$toast_message = "";
$toast_type = "";

// Handle quick payment success redirect (PRG pattern for clean re-render of tracker)
// We also support rich "Record Payment" style confirmation banner + toast
if (isset($_GET['payment_success'])) {
    $paid_amt = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
    if ($paid_amt > 0) {
        $toast_message = "Payment successful. ₱" . number_format($paid_amt, 2) . " deducted from your wallet.";
        $toast_type = "success";
    }
}
if (isset($_GET['payment_error'])) {
    $toast_message = htmlspecialchars($_GET['payment_error']);
    $toast_type = "error";
}

// Flags for post-deposit "awaiting admin approval" modal (only for regular users)
$show_deposit_pending_modal = false;
$pending_deposit_amount = 0;

// Post-create invite code sharing (improved join code UX)
$newly_created_invite_code = null;
$newly_created_group_name = null;

// ─── LOGIC: CREATE GROUP (prepared, full fields, seed cycle, consistent) ───
if (isset($_POST['action_create_group'])) {
    $group_name = trim($_POST['group_name'] ?? '');
    $desc = trim($_POST['group_desc'] ?? '');
    $privacy = $_POST['privacy'] ?? 'public';
    $amount = isset($_POST['contribution']) ? (float)$_POST['contribution'] : 0;
    $max = isset($_POST['max_members']) ? max(2, (int)$_POST['max_members']) : 5;
    $freq = $_POST['frequency'] ?? 'monthly';
    if ($freq === 'biweekly') $freq = 'weekly';

    $invite_code = null;
    if ($privacy === 'private') {
        $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    }
    $cycle_length = $max;

    // Prevent duplicate group names
    $check_stmt = mysqli_prepare($conn, "SELECT group_id FROM groups WHERE group_name = ? LIMIT 1");
    mysqli_stmt_bind_param($check_stmt, "s", $group_name);
    mysqli_stmt_execute($check_stmt);
    $duplicate = mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0;
    mysqli_stmt_close($check_stmt);

    if ($duplicate) {
        $toast_message = "A group with this name already exists. Please choose a different name.";
        $toast_type = "error";
    } else {
        $sql = "INSERT INTO groups (group_name, description, privacy, contribution_amount, max_members, frequency, cycle_length, invite_code, created_by, is_active, status, current_cycle)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', 1)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssdisisi", $group_name, $desc, $privacy, $amount, $max, $freq, $cycle_length, $invite_code, $current_user_id);

        if (mysqli_stmt_execute($stmt)) {
            $gid = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Creator gets position 1
            $stmt2 = mysqli_prepare($conn, "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', 1)");
            mysqli_stmt_bind_param($stmt2, "ii", $current_user_id, $gid);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            // Pre-generate full cycles for paluwagan (position N receives in cycle N)
            for ($c = 1; $c <= $max; $c++) {
                $stmtC = mysqli_prepare($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, status, payout_status) VALUES (?, ?, CURDATE(), 'ongoing', 'pending')");
                mysqli_stmt_bind_param($stmtC, "ii", $gid, $c);
                mysqli_stmt_execute($stmtC);
                mysqli_stmt_close($stmtC);
            }

            $toast_message = "Group created successfully! Cycles and positions ready.";
            $toast_type = "success";

            // Expose private invite code for improved "share the join code" UX right on dashboard
            if ($privacy === 'private' && !empty($invite_code)) {
                $newly_created_group_name = $group_name;
                $newly_created_invite_code = $invite_code;
            }
        } else {
            mysqli_stmt_close($stmt);
            $toast_message = "Failed to create group.";
            $toast_type = "error";
        }
    }
}

// ─── LOGIC: JOIN GROUP (prepared, supports pending public too) ───
if (isset($_POST['action_join_group'])) {
    $code = strtoupper(trim($_POST['join_code'] ?? ''));

    $stmt = mysqli_prepare($conn, "SELECT group_id FROM groups WHERE invite_code = ? AND (status = 'active' OR status = 'pending') LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($row) {
        $gid = (int)$row['group_id'];

        // prevent dup (use effective)
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM group_members WHERE user_id = ? AND group_id = ? AND status='active' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $gid);
        mysqli_stmt_execute($stmt);
        $already = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
        mysqli_stmt_close($stmt);

        if (!$already) {
            $stmt = mysqli_prepare($conn, "INSERT INTO group_members (user_id, group_id, status) VALUES (?, ?, 'active')");
            mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $gid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $toast_message = "Joined group successfully!";
            $toast_type = "success";
        } else {
            $toast_message = "You are already a member.";
            $toast_type = "error";
        }
    } else {
        $toast_message = "Invalid invite code.";
        $toast_type = "error";
    }
}

// ─── FETCH STATS (prepared) ───
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM group_members WHERE user_id = ? AND status = 'active'");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$active_groups_count = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
mysqli_stmt_close($stmt);

$total_contributed = 0.00;
$total_received = 0.00;

$stmt = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(c.amount), 0) AS total_contributed
    FROM contributions c
    JOIN group_members gm ON c.member_id = gm.member_id
    WHERE gm.user_id = ? AND c.status = 'paid'
");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$total_contributed = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_contributed'] ?? 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(p.amount), 0) AS total_received
    FROM payouts p
    JOIN group_members gm ON p.member_id = gm.member_id
    WHERE gm.user_id = ? AND p.status = 'released'
");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$total_received = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_received'] ?? 0);
mysqli_stmt_close($stmt);

$net_position = $total_received - $total_contributed;

// Auto-create wallet_requests table if it doesn't exist yet (prevents errors on first use)
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

// Compute current wallet balance early (for withdraw validation in this request)
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

// ─── HANDLE DEPOSIT / WITHDRAW REQUESTS (before balance calc so UI reflects immediately on same render) ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Ensure organized upload folders exist under assets
  $assets_uploads_base = __DIR__ . '/../../assets/uploads';
  $documents_dir = $assets_uploads_base . '/documents';
  $payments_dir = $assets_uploads_base . '/payments';
  if (!is_dir($documents_dir)) @mkdir($documents_dir, 0755, true);
  if (!is_dir($payments_dir)) @mkdir($payments_dir, 0755, true);

  // Table is auto-created earlier in the script if missing; this check is now mostly for legacy/guard
  $wallet_table_exists = false;
  $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
  if ($table_check && mysqli_num_rows($table_check) > 0) {
    $wallet_table_exists = true;
  }

  if (isset($_POST['action_wallet_deposit'])) {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $method = trim($_POST['payment_method'] ?? 'GCash');

    if ($amount > 0) {
      $attachment = null;
      if (!empty($_FILES['receipt']['name']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','pdf','gif'];
        if (in_array($ext, $allowed)) {
          $fname = 'wallet_deposit_' . $current_user_id . '_' . time() . '.' . $ext;
          $dest = $documents_dir . '/' . $fname;   // organized under uploads/documents
          if (move_uploaded_file($_FILES['receipt']['tmp_name'], $dest)) {
            $attachment = 'assets/uploads/documents/' . $fname;
          }
        }
      }

      if ($wallet_table_exists) {
        // Admins depositing (including to their own account) are auto-approved, no validation needed.
        // Regular users always go through pending review.
        $is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
        $dep_status = $is_admin ? 'approved' : 'pending';
        $dep_reviewed_by = $is_admin ? $current_user_id : null;
        $dep_reviewed_at = $is_admin ? 'NOW()' : null;

        if ($is_admin) {
          $stmt = mysqli_prepare($conn, "
            INSERT INTO wallet_requests 
            (user_id, type, amount, payment_method, attachment, status, created_at, reviewed_by, reviewed_at) 
            VALUES (?, 'deposit', ?, ?, ?, 'approved', NOW(), ?, NOW())
          ");
          if ($stmt) {
            mysqli_stmt_bind_param($stmt, "idssi", $current_user_id, $amount, $method, $attachment, $dep_reviewed_by);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $toast_message = "Deposit credited to your wallet immediately (admin direct).";
            $toast_type = "success";
          } else {
            $toast_message = "Failed to submit deposit (database error).";
            $toast_type = "error";
          }
        } else {
          $stmt = mysqli_prepare($conn, "INSERT INTO wallet_requests (user_id, type, amount, payment_method, attachment, status, created_at) VALUES (?, 'deposit', ?, ?, ?, 'pending', NOW())");
          if ($stmt) {
            mysqli_stmt_bind_param($stmt, "idss", $current_user_id, $amount, $method, $attachment);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $toast_message = "Deposit request submitted. Awaiting admin review.";
            $toast_type = "success";

            // Trigger a dedicated modal window telling the user to wait for admin approval
            $show_deposit_pending_modal = true;
            $pending_deposit_amount = $amount;
          } else {
            $toast_message = "Failed to submit deposit (database error).";
            $toast_type = "error";
          }
        }
      } else {
        $toast_message = "Wallet system not initialized yet. Please create the 'wallet_requests' table first (see admin/transactions.php for SQL).";
        $toast_type = "error";
      }
    } else {
      $toast_message = "Please enter a valid deposit amount.";
      $toast_type = "error";
    }
  }

  if (isset($_POST['action_wallet_withdraw'])) {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $method = trim($_POST['payment_method'] ?? 'GCash');
    $details = trim($_POST['account_details'] ?? '');

    if ($amount > 0 && $amount <= $wallet_balance) {
      if ($wallet_table_exists) {
        $stmt = mysqli_prepare($conn, "INSERT INTO wallet_requests (user_id, type, amount, payment_method, account_details, status, created_at) VALUES (?, 'withdraw', ?, ?, ?, 'pending', NOW())");
        if ($stmt) {
          mysqli_stmt_bind_param($stmt, "idss", $current_user_id, $amount, $method, $details);
          mysqli_stmt_execute($stmt);
          mysqli_stmt_close($stmt);
          $toast_message = "Withdrawal request submitted. Awaiting admin review.";
          $toast_type = "success";
        } else {
          $toast_message = "Failed to submit withdrawal (database error).";
          $toast_type = "error";
        }
      } else {
        $toast_message = "Wallet system not initialized yet. Please create the 'wallet_requests' table first.";
        $toast_type = "error";
      }
    } else if ($amount > $wallet_balance) {
      $toast_message = "Insufficient wallet balance for this withdrawal.";
      $toast_type = "error";
    } else {
      $toast_message = "Please enter a valid withdrawal amount.";
      $toast_type = "error";
    }
  }

  // ─── QUICK PAY FROM DASHBOARD: "Pay Now" in Pending Payments tracker ───
  if (isset($_POST['action_pay_dashboard'])) {
    $gid = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $cyc_num = isset($_POST['cycle_number']) ? max(1, (int)$_POST['cycle_number']) : 1;
    $amt = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $mid = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;

    if ($gid <= 0 || $mid <= 0 || $amt <= 0) {
      header("Location: dashboard.php?payment_error=" . urlencode("Invalid payment data."));
      exit();
    }

    // Recompute fresh wallet for safety
    $fresh_wallet = 0.00;
    $tchk = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
    if ($tchk && mysqli_num_rows($tchk) > 0) {
      $wst = mysqli_prepare($conn, "
        SELECT 
          COALESCE(SUM(CASE WHEN type = 'deposit' AND status = 'approved' THEN amount ELSE 0 END), 0) -
          COALESCE(SUM(CASE WHEN type = 'withdraw' AND status = 'approved' THEN amount ELSE 0 END), 0) AS balance
        FROM wallet_requests 
        WHERE user_id = ?
      ");
      if ($wst) {
        mysqli_stmt_bind_param($wst, "i", $current_user_id);
        mysqli_stmt_execute($wst);
        if ($wr = mysqli_fetch_assoc(mysqli_stmt_get_result($wst))) {
          $fresh_wallet = (float)($wr['balance'] ?? 0);
        }
        mysqli_stmt_close($wst);
      }
    }

    if ($amt > $fresh_wallet + 0.001) {
      header("Location: dashboard.php?payment_error=" . urlencode("Insufficient wallet balance. Please deposit funds first."));
      exit();
    }

    // Verify membership
    $vstmt = mysqli_prepare($conn, "SELECT 1 FROM group_members WHERE member_id = ? AND user_id = ? AND group_id = ? AND status='active' LIMIT 1");
    mysqli_stmt_bind_param($vstmt, "iii", $mid, $current_user_id, $gid);
    mysqli_stmt_execute($vstmt);
    $is_valid_member = mysqli_num_rows(mysqli_stmt_get_result($vstmt)) > 0;
    mysqli_stmt_close($vstmt);
    if (!$is_valid_member) {
      header("Location: dashboard.php?payment_error=" . urlencode("Not authorized for this group."));
      exit();
    }

    // Extra guard: receivers should not pay into their own receiving cycle
    $pos_stmt = mysqli_prepare($conn, "SELECT position FROM group_members WHERE member_id = ? LIMIT 1");
    mysqli_stmt_bind_param($pos_stmt, "i", $mid);
    mysqli_stmt_execute($pos_stmt);
    $pos_row = mysqli_fetch_assoc(mysqli_stmt_get_result($pos_stmt));
    mysqli_stmt_close($pos_stmt);
    $payer_pos = (int)($pos_row['position'] ?? 0);

    if ($payer_pos > 0 && $payer_pos === $cyc_num) {
      header("Location: dashboard.php?payment_error=" . urlencode("As the receiver for this cycle you do not need to contribute."));
      exit();
    }

    // Fallback receiver check (if position not set on the member row)
    $recg = mysqli_prepare($conn, "SELECT user_id FROM group_members WHERE group_id = ? AND position = ? AND status='active' LIMIT 1");
    mysqli_stmt_bind_param($recg, "ii", $gid, $cyc_num);
    mysqli_stmt_execute($recg);
    $recu = mysqli_fetch_assoc(mysqli_stmt_get_result($recg));
    mysqli_stmt_close($recg);
    if ($recu && (int)$recu['user_id'] === $current_user_id) {
      header("Location: dashboard.php?payment_error=" . urlencode("As the receiver for this cycle you do not need to contribute."));
      exit();
    }

    // Find cycle
    $cstmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id = ? AND cycle_number = ? LIMIT 1");
    mysqli_stmt_bind_param($cstmt, "ii", $gid, $cyc_num);
    mysqli_stmt_execute($cstmt);
    $cres = mysqli_stmt_get_result($cstmt);
    $cyc = mysqli_fetch_assoc($cres);
    mysqli_stmt_close($cstmt);
    if (!$cyc) {
      header("Location: dashboard.php?payment_error=" . urlencode("Cycle not found."));
      exit();
    }
    $cycle_id = (int)$cyc['cycle_id'];

    // Prevent double payment
    $chk = mysqli_prepare($conn, "SELECT 1 FROM contributions WHERE cycle_id = ? AND member_id = ? AND status = 'paid' LIMIT 1");
    mysqli_stmt_bind_param($chk, "ii", $cycle_id, $mid);
    mysqli_stmt_execute($chk);
    if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
      mysqli_stmt_close($chk);
      header("Location: dashboard.php?payment_error=" . urlencode("You have already paid for this cycle."));
      exit();
    }
    mysqli_stmt_close($chk);

    // Record contribution
    $ins = mysqli_prepare($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, paid_at, status) VALUES (?, ?, ?, CURDATE(), CURDATE(), 'paid')");
    mysqli_stmt_bind_param($ins, "iid", $cycle_id, $mid, $amt);
    $ok = mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);

    if ($ok) {
      // Optional transaction log (best effort)
      $trans_table = mysqli_query($conn, "SHOW TABLES LIKE 'transactions'");
      if ($trans_table && mysqli_num_rows($trans_table) > 0) {
        $tins = mysqli_prepare($conn, "INSERT INTO transactions (group_id, cycle_id, member_id, user_id, transaction_type, amount, transaction_date, status, recorded_by) VALUES (?, ?, ?, ?, 'contribution', ?, CURDATE(), 'completed', ?)");
        if ($tins) {
          mysqli_stmt_bind_param($tins, "iiidii", $gid, $cycle_id, $mid, $current_user_id, $amt, $current_user_id);
          mysqli_stmt_execute($tins);
          mysqli_stmt_close($tins);
        }
      }

      // Wallet internal deduct (approved withdraw)
      $wt_exists = false;
      $wtc = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
      if ($wtc && mysqli_num_rows($wtc) > 0) $wt_exists = true;

      if ($wt_exists) {
        $note = "Group contribution - Group #{$gid} Cycle #{$cyc_num}";
        $wd = mysqli_prepare($conn, "
          INSERT INTO wallet_requests (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by)
          VALUES (?, 'withdraw', ?, 'Internal Wallet', ?, 'approved', NOW(), NOW(), ?)
        ");
        if ($wd) {
          mysqli_stmt_bind_param($wd, "idsi", $current_user_id, $amt, $note, $current_user_id);
          mysqli_stmt_execute($wd);
          mysqli_stmt_close($wd);
        }
      }

      // Log history if table exists
      $hchk = mysqli_query($conn, "SHOW TABLES LIKE 'group_history'");
      if ($hchk && mysqli_num_rows($hchk) > 0) {
        $hnote = "Paid ₱" . number_format($amt, 2) . " for cycle #{$cyc_num} (from Dashboard)";
        $hist = mysqli_prepare($conn, "INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, cycle_number, amount, description) VALUES (?, 'payment', ?, ?, ?, ?, ?)");
        if ($hist) {
          mysqli_stmt_bind_param($hist, "iiiids", $gid, $current_user_id, $current_user_id, $cyc_num, $amt, $hnote);
          mysqli_stmt_execute($hist);
          mysqli_stmt_close($hist);
        }
      }

      // Fetch group name for a rich "Record Payment" style confirmation on the dashboard
      $paid_group_name = 'Group';
      $gstmt = mysqli_prepare($conn, "SELECT group_name FROM groups WHERE group_id = ? LIMIT 1");
      if ($gstmt) {
        mysqli_stmt_bind_param($gstmt, "i", $gid);
        mysqli_stmt_execute($gstmt);
        if ($g = mysqli_fetch_assoc(mysqli_stmt_get_result($gstmt))) {
          $paid_group_name = $g['group_name'];
        }
        mysqli_stmt_close($gstmt);
      }

      header("Location: dashboard.php?payment_success=1&amount=" . urlencode($amt) . "&cycle=" . urlencode($cyc_num) . "&group=" . urlencode($paid_group_name));
      exit();
    } else {
      header("Location: dashboard.php?payment_error=" . urlencode("Failed to record payment."));
      exit();
    }
  }
}

// ─── WALLET BALANCE (computed from approved wallet_requests — safe if table missing) ───
$wallet_balance = 0.00;
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
  $stmt = mysqli_prepare($conn, "
    SELECT 
      COALESCE(SUM(CASE WHEN type = 'deposit' AND status = 'approved' THEN amount ELSE 0 END), 0) -
      COALESCE(SUM(CASE WHEN type = 'withdraw' AND status = 'approved' THEN amount ELSE 0 END), 0) AS balance
    FROM wallet_requests 
    WHERE user_id = ?
  ");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $current_user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
      $wallet_balance = (float)($row['balance'] ?? 0);
    }
    mysqli_stmt_close($stmt);
  }
}

// Simple extra paluwagan info for dashboard (next upcoming payout indicator)
$next_payout_info = "Join or create groups to see your rotation schedule.";
$stmtNext = mysqli_prepare($conn, "
    SELECT g.group_name, c.cycle_number, gm.position
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.group_id
    JOIN cycles c ON c.group_id = g.group_id AND c.cycle_number = gm.position
    WHERE gm.user_id = ? AND gm.status='active' AND c.payout_status='pending'
    ORDER BY c.cycle_number ASC LIMIT 1
");
mysqli_stmt_bind_param($stmtNext, "i", $current_user_id);
mysqli_stmt_execute($stmtNext);
$nextRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtNext));
mysqli_stmt_close($stmtNext);
if ($nextRow) {
    $next_payout_info = "In \"" . htmlspecialchars($nextRow['group_name']) . "\" you are position #" . $nextRow['position'] . " (Cycle " . $nextRow['cycle_number'] . ")";
}

// ─── BUILD PENDING PAYMENTS & CYCLES TRACKER DATA ───
// Collects only groups where the current user still owes a contribution for the active (collecting) cycle.
$pending_payments = [];
$user_full_name = "You";
$user_first = "";
$user_last = "";

// Current user name (for "(You)" receiver spotlight)
$ustmt = mysqli_prepare($conn, "SELECT first_name, last_name FROM users WHERE user_id = ? LIMIT 1");
if ($ustmt) {
  mysqli_stmt_bind_param($ustmt, "i", $current_user_id);
  mysqli_stmt_execute($ustmt);
  if ($urow = mysqli_fetch_assoc(mysqli_stmt_get_result($ustmt))) {
    $user_first = trim($urow['first_name'] ?? '');
    $user_last  = trim($urow['last_name'] ?? '');
    $user_full_name = trim($user_first . ' ' . $user_last) ?: 'You';
  }
  mysqli_stmt_close($ustmt);
}

// Active group memberships (with basics needed for cards)
$gm_stmt = mysqli_prepare($conn, "
  SELECT gm.member_id, gm.position, g.group_id, g.group_name, g.contribution_amount,
         g.max_members, g.cycle_length
  FROM group_members gm
  JOIN groups g ON gm.group_id = g.group_id
  WHERE gm.user_id = ? AND gm.status = 'active' AND g.is_active = 1
  ORDER BY g.group_name ASC
");
$active_memberships = [];
if ($gm_stmt) {
  mysqli_stmt_bind_param($gm_stmt, "i", $current_user_id);
  mysqli_stmt_execute($gm_stmt);
  $gres = mysqli_stmt_get_result($gm_stmt);
  while ($row = mysqli_fetch_assoc($gres)) {
    $active_memberships[] = $row;
  }
  mysqli_stmt_close($gm_stmt);
}

foreach ($active_memberships as $mem) {
  $gid = (int)$mem['group_id'];
  $mid = (int)$mem['member_id'];
  $contrib_amt = (float)$mem['contribution_amount'];
  $cycle_len = (int)($mem['cycle_length'] ?: $mem['max_members'] ?: 5);
  $my_pos = (int)($mem['position'] ?: 0);

  // Find first non-released cycle as the "active" collecting cycle
  $cstmt = mysqli_prepare($conn, "
    SELECT cycle_id, cycle_number, payout_status
    FROM cycles
    WHERE group_id = ?
    ORDER BY cycle_number ASC
  ");
  mysqli_stmt_bind_param($cstmt, "i", $gid);
  mysqli_stmt_execute($cstmt);
  $cres = mysqli_stmt_get_result($cstmt);
  $active_cyc = null;
  while ($c = mysqli_fetch_assoc($cres)) {
    if (($c['payout_status'] ?? 'pending') !== 'released') {
      $active_cyc = $c;
      break;
    }
  }
  mysqli_stmt_close($cstmt);

  if (!$active_cyc) continue;

  $cyc_id  = (int)$active_cyc['cycle_id'];
  $cyc_num = (int)$active_cyc['cycle_number'];

  // === FIX: Receivers never "pay" into their own receiving cycle ===
  // Check via position (fast path for creator) + fallback query (for members whose position was assigned later)
  $my_position = (int)($mem['position'] ?: 0);
  $is_receiver_for_cycle = false;

  if ($my_position > 0 && $my_position === $cyc_num) {
    $is_receiver_for_cycle = true;
  } else {
    // Fallback query (handles cases where position column was not populated on join)
    $rec_chk = mysqli_prepare($conn, "
      SELECT user_id FROM group_members 
      WHERE group_id = ? AND status = 'active' AND position = ? 
      LIMIT 1
    ");
    mysqli_stmt_bind_param($rec_chk, "ii", $gid, $cyc_num);
    mysqli_stmt_execute($rec_chk);
    $rec_row = mysqli_fetch_assoc(mysqli_stmt_get_result($rec_chk));
    mysqli_stmt_close($rec_chk);
    if ($rec_row && (int)$rec_row['user_id'] === $current_user_id) {
      $is_receiver_for_cycle = true;
    }
  }

  if ($is_receiver_for_cycle) {
    continue; // You are receiving this cycle — no contribution required from you this round
  }

  // Did THIS user already pay their share for the active cycle?
  $pchk = mysqli_prepare($conn, "SELECT 1 FROM contributions WHERE cycle_id = ? AND member_id = ? AND status = 'paid' LIMIT 1");
  mysqli_stmt_bind_param($pchk, "ii", $cyc_id, $mid);
  mysqli_stmt_execute($pchk);
  $user_has_paid = mysqli_num_rows(mysqli_stmt_get_result($pchk)) > 0;
  mysqli_stmt_close($pchk);

  if ($user_has_paid) continue; // caught up for this rotation cycle

  // Paid count for progress (how many members have contributed to this cycle)
  $pc_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM contributions WHERE cycle_id = ? AND status = 'paid'");
  mysqli_stmt_bind_param($pc_stmt, "i", $cyc_id);
  mysqli_stmt_execute($pc_stmt);
  $pc_row = mysqli_fetch_assoc(mysqli_stmt_get_result($pc_stmt));
  $paid_cnt = (int)($pc_row['cnt'] ?? 0);
  mysqli_stmt_close($pc_stmt);

  // Receiver for the cycle = member holding the matching position
  $rec_name = "Position #{$cyc_num}";
  $rec_is_you = false;
  $rec_stmt = mysqli_prepare($conn, "
    SELECT gm.user_id, u.first_name, u.last_name
    FROM group_members gm
    JOIN users u ON gm.user_id = u.user_id
    WHERE gm.group_id = ? AND gm.status = 'active' AND gm.position = ?
    LIMIT 1
  ");
  mysqli_stmt_bind_param($rec_stmt, "ii", $gid, $cyc_num);
  mysqli_stmt_execute($rec_stmt);
  if ($r = mysqli_fetch_assoc(mysqli_stmt_get_result($rec_stmt))) {
    $rfull = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
    if ($rfull) $rec_name = $rfull;
    if ((int)$r['user_id'] === $current_user_id) {
      $rec_is_you = true;
      $rec_name = $rfull ? ($rfull . " (You)") : "You";
    }
  }
  mysqli_stmt_close($rec_stmt);

  $pending_payments[] = [
    'group_id'       => $gid,
    'group_name'     => $mem['group_name'],
    'cycle_number'   => $cyc_num,
    'cycle_length'   => $cycle_len,
    'contribution'   => $contrib_amt,
    'member_id'      => $mid,
    'paid_count'     => $paid_cnt,
    'total_members'  => (int)($mem['max_members'] ?: $cycle_len),
    'receiver_name'  => $rec_name,
    'receiver_is_you'=> $rec_is_you,
  ];
}

include "../../front-end/views/dashboard-view.php";
?>