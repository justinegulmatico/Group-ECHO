<?php
session_start();
include "../../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// === Admin Controls Actions ===

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
            $ins = mysqli_prepare($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, status, payout_status) VALUES (?, ?, CURDATE(), 'ongoing', 'pending')");
            mysqli_stmt_bind_param($ins, "ii", $g_id, $new_cur);
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

        header("Location: controls.php?success=Advanced to cycle #" . $new_cur);
        exit();
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
    }
    header("Location: controls.php?success=Forced contributions paid for cycle #" . $cyc_num);
    exit();
}

// Set cycle date
if (isset($_POST['admin_set_cycle_date'])) {
    $c_id = (int)($_POST['cycle_id'] ?? 0);
    $new_date = $_POST['start_date'] ?? date('Y-m-d');

    $stmt = mysqli_prepare($conn, "UPDATE cycles SET start_date = ? WHERE cycle_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_date, $c_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: controls.php?success=Cycle date updated");
    exit();
}

// Release payout
if (isset($_POST['admin_release_payout'])) {
    $c_id = (int)($_POST['cycle_id'] ?? 0);

    $cyc = mysqli_prepare($conn, "SELECT * FROM cycles WHERE cycle_id=?");
    if (!$cyc) {
        die("Prepare failed (get cycle for release): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($cyc, "i", $c_id);
    mysqli_stmt_execute($cyc);
    $cy = mysqli_fetch_assoc(mysqli_stmt_get_result($cyc));
    mysqli_stmt_close($cyc);

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
    }
    header("Location: controls.php?success=Payout released");
    exit();
}

// Fetch active groups for controls table
$controls = mysqli_query($conn, "
    SELECT g.group_id, g.group_name, g.current_cycle, g.status,
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status='active') as members,
           c.cycle_id as current_cycle_id, c.start_date as current_start
    FROM groups g
    LEFT JOIN cycles c ON c.group_id = g.group_id AND c.cycle_number = g.current_cycle
    WHERE g.status IN ('active', 'pending')
    ORDER BY g.group_name
") or die("Query failed (controls fetch): " . mysqli_error($conn));

include "../../../front-end/views/admin/controls-view.php";
?>