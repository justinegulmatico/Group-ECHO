<?php
// create group + members + prebuild cycles
session_start();
require_once "../db.php";   // New PDO-based db.php

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../php/my_groups.php");
    exit();
}

$owner_id = (int)$_SESSION['user_id'];

$group_name = trim($_POST['group_name'] ?? '');
$desc       = trim($_POST['group_desc'] ?? '');
$privacy    = $_POST['privacy'] ?? 'public';
$amount     = isset($_POST['contribution']) ? (float)$_POST['contribution'] : 0.0;
$max_members = isset($_POST['max_members']) ? max(2, (int)$_POST['max_members']) : 5;
$freq       = $_POST['frequency'] ?? 'monthly';
$start_date = $_POST['start_date'] ?? date('Y-m-d');

$invite_code = null;
if ($privacy === 'private') {
    $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

$cycle_length = $max_members;

try {
    $db = Database::getInstance();

    // whole thing in 1 tx
    $new_group_id = $db->transaction(function($pdo) use (
        $owner_id, $group_name, $desc, $privacy, $amount, $max_members, 
        $freq, $invite_code, $cycle_length, $start_date
    ) {
        // no dup names (inside tx)
        $stmt = $pdo->prepare("SELECT group_id FROM groups WHERE group_name = ? LIMIT 1");
        $stmt->execute([$group_name]);
        if ($stmt->fetch()) {
            throw new Exception("A group with this name already exists.");
        }

        // insert group
        $sql = "INSERT INTO groups 
            (group_name, description, privacy, contribution_amount, max_members, frequency, 
             cycle_length, invite_code, created_by, is_active, status, current_cycle) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'pending', 1)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $group_name, $desc, $privacy, $amount, $max_members, $freq,
            $cycle_length, $invite_code, $owner_id
        ]);
        
        $new_group_id = (int)$pdo->lastInsertId();

        // creator = pos 1
        $stmt = $pdo->prepare(
            "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', 1)"
        );
        $stmt->execute([$owner_id, $new_group_id]);

        // log creation + join
        $hist = $pdo->prepare("
            INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $hist->execute([$new_group_id, 'group_created', $owner_id, $owner_id, 'Group created']);
        $hist->execute([$new_group_id, 'member_joined', $owner_id, $owner_id, 'Joined as position #1 (creator)']);

        // pre create all cycles (paluwagan rule)
        // cycle 1 uses start_date, rest +freq
        $cycleStmt = $pdo->prepare(
            "INSERT INTO cycles (group_id, cycle_number, start_date, status, payout_status) 
             VALUES (?, ?, ?, 'ongoing', 'pending')"
        );
        for ($c = 1; $c <= $max_members; $c++) {
            $cycle_date = $start_date;
            if ($c > 1) {
                $dt = new DateTime($start_date);
                if ($freq === 'weekly') {
                    $dt->modify('+' . (7 * ($c - 1)) . ' days');
                } elseif ($freq === 'biweekly') {
                    $dt->modify('+' . (14 * ($c - 1)) . ' days');
                } else {
                    // monthly (handle diff month lengths)
                    $dt->modify('+' . ($c - 1) . ' months');
                }
                $cycle_date = $dt->format('Y-m-d');
            }
            $cycleStmt->execute([$new_group_id, $c, $cycle_date]);
        }

        return $new_group_id;
    });

    $successMsg = "Group created successfully! Full cycles + positions ready.";
    if ($invite_code) {
        $successMsg .= " Invite Code: " . $invite_code;
    }
    header("Location: ../php/my_groups.php?success=" . urlencode($successMsg));
    exit();

} catch (Exception $e) {
    // The Database class already did rollback + logged to failed_transaction_logs
    $msg = $e->getMessage();
    if (str_contains($msg, 'name already exists')) {
        header("Location: ../php/my_groups.php?error=" . urlencode($msg));
    } else {
        header("Location: ../php/my_groups.php?error=" . urlencode("Failed to create group due to a system error. Please try again."));
    }
    exit();
}
?>