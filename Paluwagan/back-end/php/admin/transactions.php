<?php
session_start();
include "../../db.php";

/*
  wallet_requests table is now AUTO-CREATED on first access if it does not exist.
  (The old manual SQL is kept here for reference.)

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
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  (Optional) To persist approved balance directly on users:
  ALTER TABLE `users` ADD COLUMN `wallet_balance` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `status`;
*/


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];
$toast_message = "";
$toast_type = "";

// Auto-create wallet_requests table if it doesn't exist yet (prevents "table does not exist" errors)
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

// ─── HANDLE APPROVE / DECLINE ACTIONS + ADMIN DIRECT DEPOSIT ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Admin Direct Deposit / Credit (auto-approved, no user validation needed) ---
    if (isset($_POST['action_admin_direct_deposit'])) {
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $source = trim($_POST['payment_method'] ?? 'Admin Direct Credit');
        $note = trim($_POST['note'] ?? '');

        if ($target_user_id <= 0 || $amount <= 0) {
            $toast_message = "Please select a user and enter a valid positive amount.";
            $toast_type = "error";
        } else {
            // Verify target user exists (admins are now allowed, e.g. for self-credit)
            $ucheck = mysqli_prepare($conn, "SELECT user_id, first_name, last_name, role FROM users WHERE user_id = ? LIMIT 1");
            mysqli_stmt_bind_param($ucheck, "i", $target_user_id);
            mysqli_stmt_execute($ucheck);
            $target = mysqli_fetch_assoc(mysqli_stmt_get_result($ucheck));
            mysqli_stmt_close($ucheck);

            if (!$target) {
                $toast_message = "Target user not found.";
                $toast_type = "error";
            } else {
                // Insert as immediately approved (table is auto-created at top of script if missing)
                $details = $note ? $note : 'Direct credit by admin';
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO wallet_requests 
                    (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_by, reviewed_at) 
                    VALUES (?, 'deposit', ?, ?, ?, 'approved', NOW(), ?, NOW())
                ");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "idssi", $target_user_id, $amount, $source, $details, $admin_id);
                    $ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    if ($ok) {
                        $full_name = trim($target['first_name'] . ' ' . $target['last_name']);
                        $toast_message = "Successfully credited ₱" . number_format($amount, 2) . " to {$full_name}'s wallet (immediate, no review required).";
                        $toast_type = "success";
                    } else {
                        $toast_message = "Database error while crediting wallet.";
                        $toast_type = "error";
                    }
                } else {
                    $toast_message = "Failed to prepare database statement for credit.";
                    $toast_type = "error";
                }
            }
        }

        header("Location: transactions.php?success=" . urlencode($toast_message));
        exit();
    }

    // --- Existing user request approval/decline (requires admin validation) ---
    if (isset($_POST['action_approve']) || isset($_POST['action_decline'])) {
        $req_id = (int)($_POST['request_id'] ?? 0);
        $new_status = isset($_POST['action_approve']) ? 'approved' : 'declined';

        // Fetch the request to know type/amount/user for potential immediate effects (balance computed on fly)
        $stmt = mysqli_prepare($conn, "SELECT * FROM wallet_requests WHERE request_id = ? AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $req_id);
        mysqli_stmt_execute($stmt);
        $req = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($req) {
            $stmt = mysqli_prepare($conn, "UPDATE wallet_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ?");
            mysqli_stmt_bind_param($stmt, "sii", $new_status, $admin_id, $req_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $type_label = ucfirst($req['type']);
            $toast_message = "Request #{$req_id} ({$type_label} ₱" . number_format($req['amount'], 2) . ") marked as {$new_status}.";
            $toast_type = ($new_status === 'approved') ? "success" : "error";
        } else {
            $toast_message = "Request not found or already processed.";
            $toast_type = "error";
        }

        // Redirect to avoid resubmission
        header("Location: transactions.php?success=" . urlencode($toast_message));
        exit();
    }
}

// Fetch all pending wallet requests (newest first) — safe if table not created yet
$pending_requests = false;
$recent_processed = false;

$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'wallet_requests'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
  $stmt = mysqli_prepare($conn, "
      SELECT wr.*, 
             u.first_name, u.last_name, u.username
      FROM wallet_requests wr
      JOIN users u ON wr.user_id = u.user_id
      WHERE wr.status = 'pending'
      ORDER BY wr.created_at DESC
  ");
  if ($stmt) {
    mysqli_stmt_execute($stmt);
    $pending_requests = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
  }

  $stmt2 = mysqli_prepare($conn, "
      SELECT wr.*, 
             u.first_name, u.last_name, u.username
      FROM wallet_requests wr
      JOIN users u ON wr.user_id = u.user_id
      WHERE wr.status IN ('approved', 'declined')
      ORDER BY wr.reviewed_at DESC
      LIMIT 6
  ");
  if ($stmt2) {
    mysqli_stmt_execute($stmt2);
    $recent_processed = mysqli_stmt_get_result($stmt2);
    mysqli_stmt_close($stmt2);
  }
}

// Fetch users (including admins) for the Direct Credit form.
// This allows admins to credit their own account or other admin accounts directly.
$all_users = [];
$user_stmt = mysqli_prepare($conn, "
    SELECT user_id, first_name, last_name, username, role 
    FROM users 
    ORDER BY 
      CASE WHEN role = 'admin' THEN 0 ELSE 1 END,
      first_name ASC, last_name ASC 
    LIMIT 300
");
if ($user_stmt) {
  mysqli_stmt_execute($user_stmt);
  $ures = mysqli_stmt_get_result($user_stmt);
  while ($u = mysqli_fetch_assoc($ures)) {
    $all_users[] = $u;
  }
  mysqli_stmt_close($user_stmt);
}

include "../../../front-end/views/admin/transactions-view.php";
?>