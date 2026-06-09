<?php
// safe payout handler (with lock + auto stuff)

if (!function_exists('perform_safe_payout')) {

    function perform_safe_payout(
        PDO $pdo, 
        int $group_id, 
        int $cycle_id, 
        int $cycle_number, 
        float $full_pot, 
        int $actor_user_id
    ): bool {
        // caller handles tx (so we can nest from contribution)

        // lock cycle row (no double payout)
        $stmt = $pdo->prepare(
            "SELECT * FROM cycles WHERE cycle_id = ? FOR UPDATE"
        );
        $stmt->execute([$cycle_id]);
        $cycle = $stmt->fetch();

        if (!$cycle) {
            throw new Exception("Cycle not found.");
        }

        if (($cycle['payout_status'] ?? 'pending') === 'released') {
            return true; // Already paid — idempotent success
        }

        // make sure cycle is full
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) 
            FROM contributions 
            WHERE cycle_id = ? AND status = 'paid'
        ");
        $stmt->execute([$cycle_id]);
        $collected = (float)$stmt->fetchColumn();

        if ($collected + 0.01 < $full_pot) {
            // Not ready yet — do not payout (this can happen in concurrent scenarios)
            return false;
        }

        // find who gets paid this cycle (by position)
        $stmt = $pdo->prepare("
            SELECT gm.member_id, gm.user_id 
            FROM group_members gm
            WHERE gm.group_id = ? AND gm.position = ? AND gm.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$group_id, $cycle_number]);
        $receiver = $stmt->fetch();

        if (!$receiver) {
            throw new Exception("No active member found for payout position #{$cycle_number}.");
        }

        $receiver_member_id = (int)$receiver['member_id'];
        $receiver_user_id   = (int)$receiver['user_id'];

        // insert payout
        $stmt = $pdo->prepare(
            "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) 
             VALUES (?, ?, ?, CURDATE(), 'released')"
        );
        $stmt->execute([$cycle_id, $receiver_member_id, $full_pot]);

        // mark cycle released
        $stmt = $pdo->prepare(
            "UPDATE cycles SET payout_member_id = ?, payout_status = 'released' WHERE cycle_id = ?"
        );
        $stmt->execute([$receiver_member_id, $cycle_id]);

        // fact table for olap
        $stmt = $pdo->prepare(
            "INSERT INTO transactions 
             (group_id, cycle_id, member_id, user_id, transaction_type, amount, transaction_date, status, recorded_by) 
             VALUES (?, ?, ?, ?, 'payout', ?, CURDATE(), 'completed', ?)"
        );
        $stmt->execute([$group_id, $cycle_id, $receiver_member_id, $receiver_user_id, $full_pot, $actor_user_id]);

        // credit wallet (as approved deposit)
        $note = "Payout • Group #{$group_id} • Cycle #{$cycle_number}";
        $stmt = $pdo->prepare("
            INSERT INTO wallet_requests 
            (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by) 
            VALUES (?, 'deposit', ?, 'Payout', ?, 'approved', NOW(), NOW(), ?)
        ");
        $stmt->execute([$receiver_user_id, $full_pot, $note, $actor_user_id]);

        // group history log
        $stmt = $pdo->prepare("
            INSERT INTO group_history 
            (group_id, event_type, actor_user_id, target_user_id, cycle_number, amount, description) 
            VALUES (?, 'payout', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $group_id, 
            $actor_user_id, 
            $receiver_user_id, 
            $cycle_number, 
            $full_pot,
            "Payout of ₱" . number_format($full_pot, 2) . " released to position #{$cycle_number}"
        ]);

        return true;
    }

    // standalone version (starts its own tx)
    function perform_safe_payout_standalone(
        int $group_id, 
        int $cycle_id, 
        int $cycle_number, 
        float $full_pot, 
        int $actor_user_id
    ): bool {
        $db = Database::getInstance();

        return $db->transaction(function($pdo) use ($group_id, $cycle_id, $cycle_number, $full_pot, $actor_user_id) {
            return perform_safe_payout($pdo, $group_id, $cycle_id, $cycle_number, $full_pot, $actor_user_id);
        });
    }

    // old helper for full pot calc
    function get_cycle_full_pot(PDO $pdo, int $group_id): float
    {
        $stmt = $pdo->prepare("
            SELECT g.contribution_amount * COUNT(gm.member_id) as pot
            FROM groups g
            JOIN group_members gm ON gm.group_id = g.group_id AND gm.status='active'
            WHERE g.group_id = ?
            GROUP BY g.group_id
        ");
        $stmt->execute([$group_id]);
        $row = $stmt->fetch();
        return (float)($row['pot'] ?? 0);
    }
}
?>