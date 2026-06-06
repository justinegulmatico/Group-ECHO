<?php
session_start();
include "../db.php";

// 1. Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// 2. Handle Action: Activate a User
if (isset($_GET['action']) && $_GET['action'] == 'activate' && isset($_GET['id'])) {
    $target_id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "UPDATE users SET status = 'active' WHERE user_id = '$target_id'");
    
    // Also mark their document as verified if they uploaded one
    mysqli_query($conn, "UPDATE user_verifications SET status = 'verified', verified_at = NOW() WHERE user_id = '$target_id' AND status = 'pending'");
    
    header("Location: admin.php?success=User activated successfully");
    exit();
}

// 3. Handle Action: Suspend a User
if (isset($_GET['action']) && $_GET['action'] == 'suspend' && isset($_GET['id'])) {
    $target_id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE user_id = '$target_id'");
    header("Location: admin.php?success=User suspended successfully");
    exit();
}

// 4. Handle Action: Process Verification (Approve or Reject via Admin Modal Form)
if (isset($_POST['action_verify'])) {
    $v_id = mysqli_real_escape_string($conn, $_POST['verification_id']);
    $u_id = mysqli_real_escape_string($conn, $_POST['target_user_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']); // 'approved' or 'denied'
    
    if ($status == 'approved') {
        if (!empty($v_id)) {
            mysqli_query($conn, "UPDATE user_verifications SET status = 'verified', verified_at = NOW() WHERE verification_id = '$v_id'");
        } else {
            mysqli_query($conn, "INSERT INTO user_verifications (user_id, status, verified_at) VALUES ('$u_id', 'verified', NOW())");
        }
        $user_status = 'active';
    } else {
        if (!empty($v_id)) {
            mysqli_query($conn, "UPDATE user_verifications SET status = 'rejected' WHERE verification_id = '$v_id'");
        }
        $user_status = 'denied';
    }
    
    mysqli_query($conn, "UPDATE users SET status = '$user_status' WHERE user_id = '$u_id'");
    
    header("Location: admin.php?success=Verification updated successfully");
    exit();
}

// ─── ACTION: ADMIN APPROVE A NEW PENDING GROUP ───
if (isset($_POST['action_approve_group'])) {
    $g_id = mysqli_real_escape_string($conn, $_POST['target_group_id']);
    mysqli_query($conn, "UPDATE groups SET status = 'active', is_active = 1 WHERE group_id = '$g_id'");
    header("Location: admin.php?success=Group approved and activated successfully");
    exit();
}

// ─── ACTION: ADMIN REJECT A NEW PENDING GROUP ───
if (isset($_POST['action_reject_group'])) {
    $g_id = mysqli_real_escape_string($conn, $_POST['target_group_id']);
    mysqli_query($conn, "UPDATE groups SET status = 'denied', is_active = 0 WHERE group_id = '$g_id'");
    header("Location: admin.php?success=Group creation application rejected");
    exit();
}

// ─── ADMIN ACTION: FORCE CLOSE A GROUP ───
if (isset($_POST['action_close_group'])) {
    $g_id = mysqli_real_escape_string($conn, $_POST['target_group_id']);
    mysqli_query($conn, "UPDATE groups SET status = 'closed', is_active = 0 WHERE group_id = '$g_id'");
    header("Location: admin.php?success=Group closed successfully");
    exit();
}

// ─── ADMIN ACTION: RE-OPEN A CLOSED GROUP ───
if (isset($_POST['action_open_group'])) {
    $g_id = mysqli_real_escape_string($conn, $_POST['target_group_id']);
    mysqli_query($conn, "UPDATE groups SET status = 'active', is_active = 1 WHERE group_id = '$g_id'");
    header("Location: admin.php?success=Group re-opened successfully");
    exit();
}

// ─── ADMIN ACTION: DELETE A GROUP ENTIRELY ───
if (isset($_POST['action_delete_group'])) {
    $g_id = mysqli_real_escape_string($conn, $_POST['target_group_id']);
    mysqli_query($conn, "DELETE FROM group_members WHERE group_id = '$g_id'");
    mysqli_query($conn, "DELETE FROM groups WHERE group_id = '$g_id'");
    header("Location: admin.php?success=Group deleted permanently");
    exit();
}

// ─── CENTRALIZED INTERFACE HANDLER: RELEASE PAYOUT & ADVANCE ROTATION CYCLE ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_release_payout'])) {
    $group_id = intval($_POST['group_id']);
    $cycle_id = intval($_POST['cycle_id']);
    $today = date('Y-m-d');

    // 1. Complete active rotation data and mark payout as officially released
    mysqli_query($conn, "UPDATE cycles SET status = 'completed' WHERE cycle_id = $cycle_id");
    
    // FIXED: Form submittals will targeting 'released' state transitions cleanly now
    mysqli_query($conn, "UPDATE payouts SET status = 'released', payout_date = '$today' WHERE cycle_id = $cycle_id");

    // 2. Look up group configuration matrix rules to provision the next cycle sequence
    $group_q = mysqli_query($conn, "SELECT * FROM groups WHERE group_id = $group_id");
    $group = mysqli_fetch_assoc($group_q);
    $amount = floatval($group['contribution_amount'] ?? 0);
    $frequency = strtolower($group['frequency'] ?? 'weekly');
    $creator_id = intval($group['created_by'] ?? 0);

    // Fetch active users in pool sequencing
    $members_q = mysqli_query($conn, "SELECT member_id FROM group_members WHERE group_id = $group_id AND user_id != $creator_id AND status='active' ORDER BY joined_at ASC");
    $members = [];
    while($row = mysqli_fetch_assoc($members_q)) { $members[] = $row['member_id']; }

    // Derive cycle progress details
    $cycle_num_q = mysqli_query($conn, "SELECT cycle_number FROM cycles WHERE cycle_id = $cycle_id");
    $current_cycle_num = intval(mysqli_fetch_assoc($cycle_num_q)['cycle_number'] ?? 1);
    $next_cycle_num = $current_cycle_num + 1;

    // 3. Increment rotation track or close the pool completely if everyone is settled
    if ($next_cycle_num <= count($members)) {
        $start_date = new DateTime();
        $end_date = clone $start_date;
        $frequency == 'weekly' ? $end_date->modify('+7 days') : $end_date->modify('+1 month');

        $start_str = $start_date->format('Y-m-d');
        $end_str = $end_date->format('Y-m-d');

        // Insert Next Cycle Track row
        mysqli_query($conn, "INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status) VALUES ($group_id, $next_cycle_num, '$start_str', '$end_str', 'ongoing')");
        $next_cycle_id = mysqli_insert_id($conn);

        // Map next user index recipient
        $next_receiver_index = $next_cycle_num - 1; 
        $payout_total = $amount * count($members);
        mysqli_query($conn, "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) VALUES ($next_cycle_id, {$members[$next_receiver_index]}, $payout_total, '$end_str', 'pending')");

        // Set up next loop phase payment tracker objects
        foreach ($members as $m_id) {
            mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status, payment_method) VALUES ($next_cycle_id, $m_id, $amount, '$end_str', 'pending', 'Cash')");
        }
        $msg = "Payout approved! Cycle #{$next_cycle_num} has been automatically spawned.";
    } else {
        mysqli_query($conn, "UPDATE groups SET status = 'completed' WHERE group_id = $group_id");
        $msg = "All rotation cycles completed successfully for this group!";
    }

    header("Location: admin.php?tab=pending_approvals&success=" . urlencode($msg));
    exit();
}

// 5. Fetch Counts for Stat Cards
$res_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$row_users = mysqli_fetch_assoc($res_users);
$total_users = $row_users['total'] ?? 0;

$res_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE status = 'pending' AND role = 'member'");
$row_pending = mysqli_fetch_assoc($res_pending);
$pending_verifications = $row_pending['total'] ?? 0;

$res_groups = mysqli_query($conn, "SELECT COUNT(*) as total FROM groups");
$row_groups = mysqli_fetch_assoc($res_groups);
$total_groups = $row_groups['total'] ?? 0;

$res_active = mysqli_query($conn, "SELECT COUNT(*) as total FROM groups WHERE is_active = 1 AND status = 'active'");
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
                                            user_verifications.verification_id,
                                            user_verifications.document,
                                            user_verifications.status as verification_status,
                                            users.user_id as target_user_id, 
                                            users.user_id,
                                            users.first_name, 
                                            users.last_name, 
                                            users.username, 
                                            users.email,
                                            users.phone,
                                            users.occupation,
                                            users.address,
                                            users.created_at 
                                        FROM users 
                                        LEFT JOIN user_verifications ON users.user_id = user_verifications.user_id 
                                        WHERE users.status = 'pending' AND users.role = 'member'
                                        ORDER BY users.created_at DESC") or die("SQL Error: " . mysqli_error($conn));

// FIXED QUERY: Uses IN operator to match 'pending_approval' or standard 'pending' status flags.
// Also uses LEFT JOINs to ensure it handles creators or edge cases safely.
$requested_payouts_res = mysqli_query($conn, "
    SELECT 
        p.payout_id, 
        p.amount, 
        p.cycle_id, 
        cy.cycle_number, 
        g.group_name, 
        g.group_id, 
        u.first_name, 
        u.last_name 
    FROM payouts p
    JOIN cycles cy ON p.cycle_id = cy.cycle_id
    JOIN groups g ON cy.group_id = g.group_id
    JOIN group_members m ON p.member_id = m.member_id
    JOIN users u ON m.user_id = u.user_id
    WHERE p.status IN ('pending_approval', 'pending')
") or die("SQL Error: " . mysqli_error($conn));

// 7. Load HTML Template View
include "../../front-end/views/admin-view.php";
?>