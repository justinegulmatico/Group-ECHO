<?php
session_start();
include "../../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// === Admin Controls Actions ===

// Helper to redirect with message
function redirect_with_msg($msg, $type = 'success') {
    $param = $type === 'error' ? 'error' : 'success';
    header("Location: controls.php?$param=" . urlencode($msg));
    exit();
}

// Advance cycle
if (isset($_POST['admin_advance_cycle'])) {
    $g_id = (int)($_POST['group_id'] ?? 0);

    $stmt = mysqli_prepare($conn, "SELECT current_cycle FROM groups WHERE group_id = ?");
    if (!$stmt) {
        die("Prepare failed (get current cycle): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $g_id);
    mysqli_stmt_execute($stmt);
    $ginfo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($ginfo) {
        $new_cur = (int)$ginfo['current_cycle'] + 1;

        $up = mysqli_prepare($conn, "UPDATE groups SET current_cycle = ? WHERE group_id = ?");
        mysqli_stmt_bind_param($up, "ii", $new_cur, $g_id);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);

        // Create cycle if needed
        $cyc_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id = ? AND cycle_number = ?");
        mysqli_stmt_bind_param($cyc_stmt, "ii", $g_id, $new_cur);
        mysqli_stmt_execute($cyc_stmt);
        $cyc = mysqli_fetch_assoc(mysqli_stmt_get_result($cyc_stmt));
        mysqli_stmt_close($cyc_stmt);

        if (!$cyc) {
            // Compute proper rotated payout/start date for this cycle number using group's freq
            $gstmt = mysqli_prepare($conn, "SELECT frequency FROM groups WHERE group_id = ?");
            mysqli_stmt_bind_param($gstmt, "i", $g_id);
            mysqli_stmt_execute($gstmt);
            $g = mysqli_fetch_assoc(mysqli_stmt_get_result($gstmt));
            mysqli_stmt_close($gstmt);
            $g_freq = $g['frequency'] ?? 'monthly';

            $base_stmt = mysqli_prepare($conn, "SELECT start_date FROM cycles WHERE group_id = ? AND cycle_number = 1 LIMIT 1");
            mysqli_stmt_bind_param($base_stmt, "i", $g_id);
            mysqli_stmt_execute($base_stmt);
            $base = mysqli_fetch_assoc(mysqli_stmt_get_result($base_stmt));
            mysqli_stmt_close($base_stmt);
            $base_date = $base['start_date'] ?? date('Y-m-d');

            $cycle_date = $base_date;
            if ($new_cur > 1) {
                $dt = new DateTime($base_date);
                if ($g_freq === 'weekly') {
                    $dt->modify('+' . (7 * ($new_cur - 1)) . ' days');
                } elseif ($g_freq === 'biweekly') {
                    $dt->modify('+' . (14 * ($new_cur - 1)) . ' days');
                } else {
                    $dt->modify('+' . ($new_cur - 1) . ' months');
                }
                $cycle_date = $dt->format('Y-m-d');
            }

            $ins = mysqli_prepare($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, status, payout_status) VALUES (?, ?, ?, 'ongoing', 'pending')");
            mysqli_stmt_bind_param($ins, "iis", $g_id, $new_cur, $cycle_date);
            mysqli_stmt_execute($ins);
            $cyc_id = mysqli_insert_id($conn);
            mysqli_stmt_close($ins);
        } else {
            $cyc_id = (int)$cyc['cycle_id'];
        }

        // Generate contributions for active members
        $mems = mysqli_prepare($conn, "SELECT member_id FROM group_members WHERE group_id = ? AND status='active'");
        mysqli_stmt_bind_param($mems, "i", $g_id);
        mysqli_stmt_execute($mems);
        $mres = mysqli_stmt_get_result($mems);
        while ($m = mysqli_fetch_assoc($mres)) {
            $ins_cont = mysqli_prepare($conn, "INSERT IGNORE INTO contributions (cycle_id, member_id, amount, due_date, status) 
                VALUES (?, ?, (SELECT contribution_amount FROM groups WHERE group_id=?), CURDATE(), 'pending')");
            mysqli_stmt_bind_param($ins_cont, "iii", $cyc_id, $m['member_id'], $g_id);
            mysqli_stmt_execute($ins_cont);
            mysqli_stmt_close($ins_cont);
        }
        mysqli_stmt_close($mems);

        redirect_with_msg("Advanced to cycle #" . $new_cur);
    } else {
        redirect_with_msg("Group not found.", 'error');
    }
}

// Force paid
if (isset($_POST['admin_force_paid'])) {
    $g_id = (int)($_POST['group_id'] ?? 0);
    $cyc_num = (int)($_POST['cycle_number'] ?? 1);

    $cyc_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id=? AND cycle_number=?");
    if (!$cyc_stmt) {
        die("Prepare failed (get cycle for force paid): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($cyc_stmt, "ii", $g_id, $cyc_num);
    mysqli_stmt_execute($cyc_stmt);
    $c = mysqli_fetch_assoc(mysqli_stmt_get_result($cyc_stmt));
    mysqli_stmt_close($cyc_stmt);

    if ($c) {
        $upd = mysqli_prepare($conn, "UPDATE contributions SET status='paid', paid_at=CURDATE() WHERE cycle_id = ? AND status != 'paid'");
        mysqli_stmt_bind_param($upd, "i", $c['cycle_id']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        redirect_with_msg("Forced contributions paid for cycle #" . $cyc_num);
    } else {
        redirect_with_msg("Cycle not found.", 'error');
    }
}

// Set cycle date (improved: accepts group_id + cycle_number OR direct cycle_id)
if (isset($_POST['admin_set_cycle_date'])) {
    $c_id = (int)($_POST['cycle_id'] ?? 0);
    $g_id = (int)($_POST['group_id'] ?? 0);
    $cyc_num = (int)($_POST['cycle_number'] ?? 0);
    $new_date = $_POST['start_date'] ?? date('Y-m-d');

    if ($c_id <= 0 && $g_id > 0 && $cyc_num > 0) {
        // Resolve cycle_id from group + cycle num
        $find = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id = ? AND cycle_number = ?");
        mysqli_stmt_bind_param($find, "ii", $g_id, $cyc_num);
        mysqli_stmt_execute($find);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($find));
        mysqli_stmt_close($find);
        if ($row) $c_id = (int)$row['cycle_id'];
    }

    if ($c_id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE cycles SET start_date = ? WHERE cycle_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_date, $c_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        redirect_with_msg("Cycle date updated successfully.");
    } else {
        redirect_with_msg("No valid cycle specified for date update.", 'error');
    }
}

// Release payout (improved: can accept group_id + cycle_number)
if (isset($_POST['admin_release_payout'])) {
    $c_id = (int)($_POST['cycle_id'] ?? 0);
    $g_id = (int)($_POST['group_id'] ?? 0);
    $cyc_num = (int)($_POST['cycle_number'] ?? 0);

    if ($c_id <= 0 && $g_id > 0 && $cyc_num > 0) {
        $find = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id = ? AND cycle_number = ?");
        mysqli_stmt_bind_param($find, "ii", $g_id, $cyc_num);
        mysqli_stmt_execute($find);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($find));
        mysqli_stmt_close($find);
        if ($row) $c_id = (int)$row['cycle_id'];
    }

    $cyc = mysqli_prepare($conn, "SELECT * FROM cycles WHERE cycle_id=?");
    if (!$cyc) {
        die("Prepare failed (get cycle for release): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($cyc, "i", $c_id);
    mysqli_stmt_execute($cyc);
    $cy = mysqli_fetch_assoc(mysqli_stmt_get_result($cyc));
    mysqli_stmt_close($cyc);

    if (!$cy) {
        redirect_with_msg("Cycle not found.", 'error');
    }

    if ($cy['payout_status'] === 'released') {
        redirect_with_msg("Payout already released for this cycle.", 'error');
    }

    // Safety check: ensure all contributions for this cycle are paid
    $paid_check = mysqli_prepare($conn, "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid
        FROM contributions 
        WHERE cycle_id = ?
    ");
    mysqli_stmt_bind_param($paid_check, "i", $c_id);
    mysqli_stmt_execute($paid_check);
    $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($paid_check));
    mysqli_stmt_close($paid_check);

    if (!$stats || (int)$stats['paid'] < (int)$stats['total']) {
        redirect_with_msg("Cannot release payout: not all contributions are marked paid for this cycle (" . ($stats['paid'] ?? 0) . "/" . ($stats['total'] ?? 0) . "). Use Force Paid first.", 'error');
    }

    if ($cy && $cy['payout_status'] != 'released') {
        $pos_stmt = mysqli_prepare($conn, "SELECT gm.member_id, g.contribution_amount * (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status='active') as pot 
            FROM groups g 
            JOIN group_members gm ON gm.group_id = g.group_id 
            WHERE g.group_id=? AND gm.position = ? LIMIT 1");
        mysqli_stmt_bind_param($pos_stmt, "ii", $cy['group_id'], $cy['cycle_number']);
        mysqli_stmt_execute($pos_stmt);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($pos_stmt));
        mysqli_stmt_close($pos_stmt);

        if ($p) {
            $pstmt = mysqli_prepare($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES (?, ?, ?, CURDATE(), 'released')");
            mysqli_stmt_bind_param($pstmt, "iid", $c_id, $p['member_id'], $p['pot']);
            mysqli_stmt_execute($pstmt);
            mysqli_stmt_close($pstmt);
        }

        $up = mysqli_prepare($conn, "UPDATE cycles SET payout_status='released' WHERE cycle_id=?");
        mysqli_stmt_bind_param($up, "i", $c_id);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);

        redirect_with_msg("Payout released successfully for cycle #" . $cy['cycle_number']);
    }
}

// Fetch active groups for controls table (with paid stats for current cycle)
$controls = mysqli_query($conn, "
    SELECT 
        g.group_id, 
        g.group_name, 
        g.current_cycle, 
        g.status,
        g.contribution_amount,
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status='active') as members,
        c.cycle_id as current_cycle_id, 
        c.start_date as current_start,
        c.payout_status,
        COALESCE((
            SELECT COUNT(*) FROM contributions co 
            WHERE co.cycle_id = c.cycle_id AND co.status = 'paid'
        ), 0) as paid_count,
        COALESCE((
            SELECT COUNT(*) FROM contributions co 
            WHERE co.cycle_id = c.cycle_id
        ), 0) as total_contribs
    FROM groups g
    LEFT JOIN cycles c ON c.group_id = g.group_id AND c.cycle_number = g.current_cycle
    WHERE g.status IN ('active', 'pending')
    ORDER BY g.group_name
") or die("Query failed (controls fetch): " . mysqli_error($conn));

// Groups list for dropdown selectors in manual tools (used by the view)
$groups_select = mysqli_query($conn, "
    SELECT group_id, group_name, current_cycle 
    FROM groups 
    WHERE status IN ('active', 'pending') 
    ORDER BY group_name ASC
");

// Bulk force paid for ALL active groups (demo convenience)
if (isset($_POST['admin_bulk_force_paid'])) {
    $updated = 0;
    $groups = mysqli_query($conn, "SELECT group_id, current_cycle FROM groups WHERE status IN ('active','pending')");
    while ($gr = mysqli_fetch_assoc($groups)) {
        $cyc_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id=? AND cycle_number=?");
        mysqli_stmt_bind_param($cyc_stmt, "ii", $gr['group_id'], $gr['current_cycle']);
        mysqli_stmt_execute($cyc_stmt);
        $c = mysqli_fetch_assoc(mysqli_stmt_get_result($cyc_stmt));
        mysqli_stmt_close($cyc_stmt);

        if ($c) {
            $upd = mysqli_prepare($conn, "UPDATE contributions SET status='paid', paid_at=CURDATE() WHERE cycle_id = ? AND status != 'paid'");
            mysqli_stmt_bind_param($upd, "i", $c['cycle_id']);
            mysqli_stmt_execute($upd);
            $updated += mysqli_affected_rows($conn);
            mysqli_stmt_close($upd);
        }
    }
    redirect_with_msg("Bulk force paid applied across active groups. Updated $updated contribution records.");
}

include "../../../front-end/views/admin/controls-view.php";
?>