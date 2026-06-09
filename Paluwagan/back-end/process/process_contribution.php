<?php
// contribution + wallet deduct + fact table
session_start();
require_once "../db.php";
require_once "process_payout.php";   // for the safe payout call

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../php/my_groups.php");
    exit();
}

$user_id      = (int)$_SESSION['user_id'];
$group_id     = (int)($_POST['group_id'] ?? 0);
$member_id    = (int)($_POST['member_id'] ?? 0);
$amount       = (float)($_POST['amount'] ?? 0);
$cycle_number = max(1, (int)($_POST['cycle_number'] ?? 1));

if ($group_id <= 0 || $member_id <= 0 || $amount <= 0) {
    header("Location: ../php/group_details.php?id=" . $group_id . "&error=" . urlencode("Invalid payment data."));
    exit();
}

try {
    $db = Database::getInstance();

    $db->transaction(function($pdo) use ($db, $user_id, $group_id, $member_id, $amount, $cycle_number) {

        // check membership
        $stmt = $pdo->prepare(
            "SELECT gm.position, g.status, g.group_name 
             FROM group_members gm
             JOIN groups g ON g.group_id = gm.group_id
             WHERE gm.member_id = ? AND gm.user_id = ? AND gm.group_id = ? AND gm.status='active' 
             LIMIT 1"
        );
        $stmt->execute([$member_id, $user_id, $group_id]);
        $membership = $stmt->fetch();

        if (!$membership) {
            throw new Exception("Not authorized for this group.");
        }

        // group must be active first
        if (($membership['status'] ?? 'pending') !== 'active') {
            throw new Exception("This group has not been activated yet. Contributions are only allowed after the creator activates the Paluwagan.");
        }

        $user_position = (int)$membership['position'];

        // receiver cant pay their own cycle
        if ($user_position === $cycle_number) {
            throw new Exception("You are the receiver for Cycle #{$cycle_number}. Receivers do not make contributions for their own payout cycle.");
        }

        // get cycle + make sure not paid yet
        $stmt = $pdo->prepare(
            "SELECT c.cycle_id, c.payout_status 
             FROM cycles c 
             WHERE c.group_id = ? AND c.cycle_number = ? LIMIT 1"
        );
        $stmt->execute([$group_id, $cycle_number]);
        $cycle = $stmt->fetch();
        if (!$cycle) {
            throw new Exception("Cycle not found for this group.");
        }
        if (($cycle['payout_status'] ?? 'pending') === 'released') {
            throw new Exception("Cycle #{$cycle_number} has already been paid out. No more contributions accepted.");
        }
        $cycle_id = (int)$cycle['cycle_id'];

        // wallet check (inside tx)
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'deposit' AND status = 'approved' THEN amount ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN type = 'withdraw' AND status = 'approved' THEN amount ELSE 0 END), 0) AS balance
            FROM wallet_requests 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $wallet_balance = (float)($stmt->fetchColumn() ?? 0);

        if ($amount > $wallet_balance) {
            throw new Exception("Insufficient wallet balance. Please deposit funds from the Dashboard.");
        }

        // save the payment
        $stmt = $pdo->prepare(
            "INSERT INTO contributions (cycle_id, member_id, amount, due_date, paid_at, status) 
             VALUES (?, ?, ?, CURDATE(), CURDATE(), 'paid')"
        );
        $stmt->execute([$cycle_id, $member_id, $amount]);

        // also write to fact table
        $transStmt = $pdo->prepare(
            "INSERT INTO transactions 
             (group_id, cycle_id, member_id, user_id, transaction_type, amount, transaction_date, status, recorded_by) 
             VALUES (?, ?, ?, ?, 'contribution', ?, CURDATE(), 'completed', ?)"
        );
        $transStmt->execute([$group_id, $cycle_id, $member_id, $user_id, $amount, $user_id]);

        // deduct from wallet
        $note = "Group contribution - Group #{$group_id} Cycle #{$cycle_number}";
        $deduct = $pdo->prepare("
            INSERT INTO wallet_requests 
            (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by) 
            VALUES (?, 'withdraw', ?, 'Internal Wallet', ?, 'approved', NOW(), NOW(), ?)
        ");
        $deduct->execute([$user_id, $amount, $note, $user_id]);

        // history log
        $hist = $pdo->prepare("
            INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, cycle_number, amount, description) 
            VALUES (?, 'payment', ?, ?, ?, ?, ?)
        ");
        $hist->execute([
            $group_id, $user_id, $user_id, $cycle_number, $amount,
            "Paid ₱" . number_format($amount, 2) . " for cycle #{$cycle_number}"
        ]);

        // auto payout if cycle full
        $full_pot = get_group_contribution_amount($pdo, $group_id) * get_active_member_count($pdo, $group_id);

        // Only attempt if this cycle might now be complete
        $collected = get_cycle_collected($pdo, $cycle_id);
        if ($collected + 0.01 >= $full_pot) {
            // Call the safe payout function (it will do its own FOR UPDATE inside this tx)
            perform_safe_payout($pdo, $group_id, $cycle_id, $cycle_number, $full_pot, $user_id);
        }
    });

    header("Location: ../php/group_details.php?id=" . $group_id . 
           "&tab=payments&success=" . urlencode("Payment recorded for cycle #{$cycle_number}."));
    exit();

} catch (Exception $e) {
    $safeMsg = $e->getMessage();
    // Hide internal details from regular users in production, but for demo we can show it
    header("Location: ../php/group_details.php?id=" . $group_id . "&error=" . urlencode($safeMsg));
    exit();
}

// helpers used in tx

function get_group_contribution_amount(PDO $pdo, int $group_id): float
{
    $stmt = $pdo->prepare("SELECT contribution_amount FROM groups WHERE group_id = ?");
    $stmt->execute([$group_id]);
    return (float)($stmt->fetchColumn() ?? 0);
}

function get_active_member_count(PDO $pdo, int $group_id): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND status='active'");
    $stmt->execute([$group_id]);
    return (int)$stmt->fetchColumn();
}

function get_cycle_collected(PDO $pdo, int $cycle_id): float
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM contributions WHERE cycle_id = ? AND status='paid'");
    $stmt->execute([$cycle_id]);
    return (float)$stmt->fetchColumn();
}
?>