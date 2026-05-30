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
    $target_id = $_GET['id'];
    // Changed 'active' to 'activated' here
    mysqli_query($conn, "UPDATE users SET status = 'activated' WHERE user_id = '$target_id'");
    header("Location: admin.php?success=User activated successfully");
    exit();
}


// 3. Handle Action: Suspend a User
if (isset($_GET['action']) && $_GET['action'] == 'suspend' && isset($_GET['id'])) {
    $target_id = $_GET['id'];
    mysqli_query($conn, "UPDATE users SET status = 'suspended' WHERE user_id = '$target_id'");
    header("Location: admin.php?success=User suspended successfully");
    exit();
}

// 4. Handle Action: Process Verification (Approve or Reject)
if (isset($_POST['action_verify'])) {
    $v_id = $_POST['verification_id'];
    $u_id = $_POST['target_user_id'];
    $status = $_POST['status']; // 'approved' or 'denied'
    
    // UPDATE: If approved, set the verified_at date to today!
// UPDATE: Use NOW() instead of CURRENT_DATE() to capture the exact time
    if ($status == 'approved') {
        mysqli_query($conn, "UPDATE user_verifications SET status = '$status', verified_at = NOW() WHERE verification_id = '$v_id'");
    } else {
        mysqli_query($conn, "UPDATE user_verifications SET status = '$status' WHERE verification_id = '$v_id'");
    }
    
    // Update the main users table
    $user_status = ($status == 'approved') ? 'activated' : 'denied';
    mysqli_query($conn, "UPDATE users SET status = '$user_status' WHERE user_id = '$u_id'");
    
    header("Location: admin.php?success=Verification updated successfully");
    exit();
}

// Handle Action: Close a Group
if (isset($_POST['action_close_group'])) {
    $g_id = $_POST['target_group_id'];
    mysqli_query($conn, "UPDATE groups SET status = 'closed' WHERE group_id = '$g_id'");
    header("Location: admin.php?success=Group closed successfully");
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

// 7. Load HTML Template View
include "../../front-end/views/admin-view.php";
?>