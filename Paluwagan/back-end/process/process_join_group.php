<?php
/**
 * process_join_group.php
 * 
 * OLTP Transaction: Join Paluwagan Group (assigns next available position)
 * 
 * Key Transactional Features:
 * - Uses SELECT ... FOR UPDATE to lock the roster for this group (prevents race conditions when multiple users join simultaneously).
 * - Entire operation (check capacity + assign position + insert + history log) is atomic.
 * - If the group fills up between the time the user saw it and when they submit, the transaction will correctly reject.
 */

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
        // 1. Lock and validate the group (using FOR UPDATE on the group row)
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

        // 2. Lock all current active members of this group (prevents two people joining at the exact same time and both getting the last slot)
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

        // 3. Compute next position under lock
        $stmt = $pdo->prepare(
            "SELECT COALESCE(MAX(position), 0) + 1 as next_pos 
             FROM group_members 
             WHERE group_id = ?"
        );
        $stmt->execute([$target_group_id]);
        $nextPosition = (int)$stmt->fetchColumn();

        // 4. Insert the new member
        $stmt = $pdo->prepare(
            "INSERT INTO group_members (user_id, group_id, status, position) VALUES (?, ?, 'active', ?)"
        );
        $stmt->execute([$user_id, $target_group_id, $nextPosition]);

        // 5. Log the join
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