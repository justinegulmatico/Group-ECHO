<?php
session_start();
include "../db.php";

// Note: process/ is one level below php/, so redirects to controllers must use ../php/...
// e.g. ../php/group_details.php instead of ../group_details.php (which would 404)

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = (int)$_SESSION['user_id'];
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $cycle_number = isset($_POST['cycle_number']) ? max(1, (int)$_POST['cycle_number']) : 1;

    if ($group_id <= 0 || $member_id <= 0 || $amount <= 0) {
        header("Location: ../php/group_details.php?id=" . $group_id . "&error=" . urlencode("Invalid payment data."));
        exit();
    }

    // Auto-create wallet_requests table if it doesn't exist yet
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

    // === WALLET BALANCE CHECK (replaces payment method) ===
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
        mysqli_stmt_bind_param($wstmt, "i", $user_id);
        mysqli_stmt_execute($wstmt);
        $wres = mysqli_stmt_get_result($wstmt);
        if ($wrow = mysqli_fetch_assoc($wres)) {
          $wallet_balance = (float)($wrow['balance'] ?? 0);
        }
        mysqli_stmt_close($wstmt);
      }
    }

    if ($amount > $wallet_balance) {
        header("Location: ../php/group_details.php?id=" . $group_id . "&error=" . urlencode("Insufficient wallet balance. Please deposit funds from the Dashboard."));
        exit();
    }

    // Verify membership (security)
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM group_members WHERE member_id = ? AND user_id = ? AND group_id = ? AND status='active' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "iii", $member_id, $user_id, $group_id);
    mysqli_stmt_execute($stmt);
    $okMember = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);
    if (!$okMember) {
        header("Location: ../php/group_details.php?id=" . $group_id . "&error=" . urlencode("Not authorized for this group."));
        exit();
    }

    // Find the pre-created cycle (we now generate all cycles on group creation)
    $stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id = ? AND cycle_number = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $group_id, $cycle_number);
    mysqli_stmt_execute($stmt);
    $cres = mysqli_stmt_get_result($stmt);
    $cycle = mysqli_fetch_assoc($cres);
    mysqli_stmt_close($stmt);

    if (!$cycle) {
        header("Location: ../php/group_details.php?id=" . $group_id . "&error=" . urlencode("Cycle not found for this group."));
        exit();
    }
    $cycle_id = (int)$cycle['cycle_id'];

    // Record contribution (prevent double with simple check in group_details too)
    $stmt = mysqli_prepare($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, paid_at, status) 
                                   VALUES (?, ?, ?, CURDATE(), CURDATE(), 'paid')");
    mysqli_stmt_bind_param($stmt, "iid", $cycle_id, $member_id, $amount);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($success) {
        // Also record in transactions fact table for OLAP
        $trans_stmt = mysqli_prepare($conn, "INSERT INTO transactions (group_id, cycle_id, member_id, user_id, transaction_type, amount, transaction_date, status, recorded_by) 
                                            VALUES (?, ?, ?, ?, 'contribution', ?, CURDATE(), 'completed', ?)");
        mysqli_stmt_bind_param($trans_stmt, "iiidii", $group_id, $cycle_id, $member_id, $user_id, $amount, $user_id);
        mysqli_stmt_execute($trans_stmt);
        mysqli_stmt_close($trans_stmt);

        // === AUTO-DEDUCT FROM WALLET (internal transfer) ===
        // Guard + create payments/documents folders for future proofs
        $assets_uploads_base = __DIR__ . '/../../assets/uploads';
        $documents_dir = $assets_uploads_base . '/documents';
        $payments_dir = $assets_uploads_base . '/payments';
        if (!is_dir($documents_dir)) @mkdir($documents_dir, 0755, true);
        if (!is_dir($payments_dir)) @mkdir($payments_dir, 0755, true);

        $wallet_table_exists = false;
        $tcheck = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
        if ($tcheck && mysqli_num_rows($tcheck) > 0) $wallet_table_exists = true;

        if ($wallet_table_exists) {
          // Insert an approved withdraw so the computed wallet balance decreases immediately.
          $note = "Group contribution - Group #{$group_id} Cycle #{$cycle_number}";
          $deduct_stmt = mysqli_prepare($conn, "
              INSERT INTO wallet_requests 
              (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by) 
              VALUES (?, 'withdraw', ?, 'Internal Wallet', ?, 'approved', NOW(), NOW(), ?)
          ");
          if ($deduct_stmt) {
              mysqli_stmt_bind_param($deduct_stmt, "idsi", $user_id, $amount, $note, $user_id);
              mysqli_stmt_execute($deduct_stmt);
              mysqli_stmt_close($deduct_stmt);
          }
        }

        // Log payment to group history
        $hist_note = "Paid ₱" . number_format($amount, 2) . " for cycle #{$cycle_number}";
        $hist_stmt = mysqli_prepare($conn, "
            INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, cycle_number, amount, description) 
            VALUES (?, 'payment', ?, ?, ?, ?, ?)
        ");
        if ($hist_stmt) {
            mysqli_stmt_bind_param($hist_stmt, "iiiids", $group_id, $user_id, $user_id, $cycle_number, $amount, $hist_note);
            mysqli_stmt_execute($hist_stmt);
            mysqli_stmt_close($hist_stmt);
        }

        // Auto-payout will be attempted on the next page load of group_details (via automation logic)
        header("Location: ../php/group_details.php?id=" . $group_id . "&tab=payments&success=" . urlencode("Payment recorded for cycle #$cycle_number. (Deducted from wallet)"));
        exit();
    } else {
        header("Location: ../php/group_details.php?id=" . $group_id . "&error=" . urlencode("Failed to record payment."));
        exit();
    }
}

header("Location: ../php/my_groups.php");
exit();
?>