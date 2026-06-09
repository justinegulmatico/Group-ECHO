<?php
// join using invite code (with lock)
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../php/my_groups.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$invite_code = strtoupper(trim($_POST['invite_code'] ?? ''));

if (empty($invite_code)) {
    header("Location: ../php/my_groups.php?error=" . urlencode("Invite code is required."));
    exit();
}

try {
    $db = Database::getInstance();

    $result = $db->transaction(function($pdo) use ($user_id, $invite_code) {
        // lock group + validate invite
        $stmt = $pdo->prepare(
            "SELECT * FROM groups WHERE invite_code = ? AND (status = 'active' OR status = 'pending') FOR UPDATE"
        );
        $stmt->execute([$invite_code]);
        $group = $stmt->fetch();

        if (!$group) {
            throw new Exception("Invalid or inactive invite code.");
        }

        $target_group_id = (int)$group['group_id'];
        $max_slots = (int)($group['cycle_length'] ?: $group['max_members'] ?: 5);

        // lock members too (race safe)
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as current_slots FROM group_members 
             WHERE group_id = ? AND status = 'active' 
             FOR UPDATE"
        );
        $stmt->execute([$target_group_id]);
        $current_slots = (int)$stmt->fetchColumn();

        if ($current_slots >= $max_slots) {
            throw new Exception("This savings group is already full. No open slots remain.");
        }

        // next position under lock
        $stmt = $pdo->prepare(
            "SELECT COALESCE(MAX(position), 0) + 1 as next_pos 
             FROM group_members 
             WHERE group_id = ?"
        );
        $stmt->execute([$target_group_id]);
        $nextPosition = (int)$stmt->fetchColumn();

        // insert member
        $stmt = $pdo->prepare(
            "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', ?)"
        );
        $stmt->execute([$user_id, $target_group_id, $nextPosition]);

        // log join
        $hist = $pdo->prepare("
            INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, description) 
            VALUES (?, 'member_joined', ?, ?, ?)
        ");
        $hist->execute([
            $target_group_id, 
            $user_id, 
            $user_id, 
            "Joined group as position #" . $nextPosition
        ]);

        return [
            'group_id' => $target_group_id,
            'position' => $nextPosition
        ];
    });

    header("Location: ../php/group_details.php?id=" . $result['group_id'] . 
           "&success=" . urlencode("Successfully joined! You are position #" . $result['position'] . "."));
    exit();

} catch (Exception $e) {
    header("Location: ../php/my_groups.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>