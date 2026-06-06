<?php
session_start();
include "../db.php";

// 1. Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// 2. Handle Action: Activate a User (GET kept for current UI links)
if (isset($_GET['action']) && $_GET['action'] == 'activate' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'activated' WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $target_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: admin.php?success=" . urlencode("User activated successfully"));
    exit();
}

// 3. Handle Action: Suspend a User
if (isset($_GET['action']) && $_GET['action'] == 'suspend' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'suspended' WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $target_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: admin.php?success=" . urlencode("User suspended successfully"));
    exit();
}

// 4. Handle Action: Process Verification (Approve or Reject)
if (isset($_POST['action_verify'])) {
    $v_id = (int)($_POST['verification_id'] ?? 0);
    $u_id = (int)($_POST['target_user_id'] ?? 0);
    $status = $_POST['status'] ?? 'denied'; // 'approved' or 'denied'
    
    if ($status == 'approved') {
        $stmt = mysqli_prepare($conn, "UPDATE user_verifications SET status = ?, verified_at = NOW() WHERE verification_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $v_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE user_verifications SET status = ? WHERE verification_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $v_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    $user_status = ($status == 'approved') ? 'activated' : 'denied';
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $user_status, $u_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    header("Location: admin.php?success=" . urlencode("Verification updated successfully"));
    exit();
}

// Handle Action: Close a Group
if (isset($_POST['action_close_group'])) {
    $g_id = (int)($_POST['target_group_id'] ?? 0);
    $stmt = mysqli_prepare($conn, "UPDATE groups SET status = 'closed' WHERE group_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $g_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: admin.php?success=" . urlencode("Group closed successfully"));
    exit();
}

// === NEW ADMIN CONTROLS: Cycle, Date, and Group Management ===
if (isset($_POST['admin_advance_cycle'])) {
    $g_id = (int)($_POST['group_id'] ?? 0);
    // Get current info
    $stmt = mysqli_prepare($conn, "SELECT current_cycle, max_members FROM groups WHERE group_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $g_id);
    mysqli_stmt_execute($stmt);
    $ginfo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($ginfo) {
        $cur = (int)$ginfo['current_cycle'];
        $new_cur = $cur + 1;

        // Update group current cycle
        $up = mysqli_prepare($conn, "UPDATE groups SET current_cycle = ? WHERE group_id = ?");
        mysqli_stmt_bind_param($up, "ii", $new_cur, $g_id);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);

        // Ensure next cycle row exists
        $cyc_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id = ? AND cycle_number = ?");
        mysqli_stmt_bind_param($cyc_stmt, "ii", $g_id, $new_cur);
        mysqli_stmt_execute($cyc_stmt);
        $cyc = mysqli_fetch_assoc(mysqli_stmt_get_result($cyc_stmt));
        mysqli_stmt_close($cyc_stmt);

        if (!$cyc) {
            $ins_c = mysqli_prepare($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, status, payout_status) VALUES (?, ?, CURDATE(), 'ongoing', 'pending')");
            mysqli_stmt_bind_param($ins_c, "ii", $g_id, $new_cur);
            mysqli_stmt_execute($ins_c);
            mysqli_stmt_close($ins_c);
            $cyc_id = mysqli_insert_id($conn);
        } else {
            $cyc_id = (int)$cyc['cycle_id'];
        }

        // Generate pending contributions for all active members for the new cycle
        $mems = mysqli_prepare($conn, "SELECT member_id FROM group_members WHERE group_id = ? AND status = 'active'");
        mysqli_stmt_bind_param($mems, "i", $g_id);
        mysqli_stmt_execute($mems);
        $mres = mysqli_stmt_get_result($mems);
        while ($m = mysqli_fetch_assoc($mres)) {
            $ins_cont = mysqli_prepare($conn, "INSERT IGNORE INTO contributions (cycle_id, member_id, amount, due_date, status) VALUES (?, ?, (SELECT contribution_amount FROM groups WHERE group_id=?), CURDATE(), 'pending')");
            mysqli_stmt_bind_param($ins_cont, "iii", $cyc_id, $m['member_id'], $g_id);
            mysqli_stmt_execute($ins_cont);
            mysqli_stmt_close($ins_cont);
        }
        mysqli_stmt_close($mems);

        header("Location: admin.php?success=" . urlencode("Advanced group to cycle #$new_cur and generated contributions."));
        exit();
    }
}

if (isset($_POST['admin_set_cycle_date'])) {
    $c_id = (int)($_POST['cycle_id'] ?? 0);
    $new_date = $_POST['start_date'] ?? date('Y-m-d');
    $stmt = mysqli_prepare($conn, "UPDATE cycles SET start_date = ? WHERE cycle_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $new_date, $c_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: admin.php?success=" . urlencode("Cycle date updated."));
    exit();
}

if (isset($_POST['admin_force_paid'])) {
    $g_id = (int)($_POST['group_id'] ?? 0);
    $cyc_num = (int)($_POST['cycle_number'] ?? 1);
    $cyc_stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id=? AND cycle_number=?");
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
    header("Location: admin.php?success=" . urlencode("Forced all contributions paid for cycle #$cyc_num."));
    exit();
}

if (isset($_POST['admin_release_payout'])) {
    $c_id = (int)($_POST['cycle_id'] ?? 0);
    // Simple: mark cycle released and create payout for the position holder if exists
    $cyc = mysqli_prepare($conn, "SELECT * FROM cycles WHERE cycle_id=?");
    mysqli_stmt_bind_param($cyc, "i", $c_id);
    mysqli_stmt_execute($cyc);
    $cy = mysqli_fetch_assoc(mysqli_stmt_get_result($cyc));
    mysqli_stmt_close($cyc);

    if ($cy && $cy['payout_status'] != 'released') {
        // Find member by position = cycle_number
        $pos_stmt = mysqli_prepare($conn, "SELECT gm.member_id, g.contribution_amount * (SELECT COUNT(*) FROM group_members WHERE group_id=g.group_id AND status='active') as pot FROM groups g JOIN group_members gm ON gm.group_id = g.group_id WHERE g.group_id=? AND gm.position = ? LIMIT 1");
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
    header("Location: admin.php?success=" . urlencode("Payout released for cycle."));
    exit();
}

// 5. Fetch Counts for Stat Cards
$res_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$row_users = mysqli_fetch_assoc($res_users);
$total_users = $row_users['total'] ?? 0;

$res_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM user_verifications WHERE status = 'pending'");
$row_pending = mysqli_fetch_assoc($res_pending);
$pending_verifications = $row_pending['total'] ?? 0;

$res_groups = mysqli_query($conn, "SELECT COUNT(*) as total FROM groups");
$row_groups = mysqli_fetch_assoc($res_groups);
$total_groups = $row_groups['total'] ?? 0;

$res_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM groups WHERE is_active = 1");
$row_active = mysqli_fetch_assoc($res_active);
$active_groups = $row_active['total'] ?? 0;

// 6. Fetch Table Resources
$users_res = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");

$groups_res = mysqli_query($conn, "SELECT 
                                    groups.*, 
                                    users.first_name as owner_first, 
                                    users.last_name as owner_last,
                                    users.username as owner_user,
                                    (SELECT COUNT(*) FROM group_members WHERE group_members.group_id = groups.group_id) as member_count,
                                    (SELECT GROUP_CONCAT(CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') 
                                    FROM group_members gm 
                                    JOIN users u ON gm.user_id = u.user_id 
                                    WHERE gm.group_id = groups.group_id) as member_list
                                FROM groups 
                                LEFT JOIN users ON groups.created_by = users.user_id 
                                ORDER BY groups.created_at DESC") or die("SQL Error: " . mysqli_error($conn));

$verifications_res = mysqli_query($conn, "SELECT 
                                            user_verifications.*, 
                                            users.first_name, 
                                            users.last_name, 
                                            users.username, 
                                            users.email,
                                            users.phone,
                                            users.occupation,
                                            users.address,
                                            users.created_at 
                                        FROM user_verifications 
                                        JOIN users ON user_verifications.user_id = users.user_id 
                                        WHERE user_verifications.status = 'pending'") or die("SQL Error: " . mysqli_error($conn));

// Fetch groups for Admin Controls (with current cycle info)
$controls_groups_res = mysqli_query($conn, "
    SELECT g.group_id, g.group_name, g.current_cycle, g.status, 
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status='active') as members,
           c.cycle_id as current_cycle_id, c.start_date as current_start
    FROM groups g
    LEFT JOIN cycles c ON c.group_id = g.group_id AND c.cycle_number = g.current_cycle
    WHERE g.status = 'active'
    ORDER BY g.group_name
") or die("SQL Error: " . mysqli_error($conn));

// 7. Load HTML Template View
include "../../front-end/views/admin-view.php";
?>