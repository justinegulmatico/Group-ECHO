<?php
session_start();
// Corrected path to move up one folder to reach db.php in back-end/
include "../db.php";

// 1. Force safety checkpoint redirect if no logged-in user session exists
if (!isset($_SESSION['user_id'])) {
    // Corrected path: move up 3 steps (php/ -> back-end/ -> Paluwagan/ -> Group-ECHO/) to reach index.php
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Get Logged-in User Profile Context
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1));
}

// ─── DYNAMIC SCHEMA DETECTOR ───
$group_pk = 'group_id';
$test_groups_table = mysqli_query($conn, "SHOW COLUMNS FROM groups LIKE 'group_id'");
if (!$test_groups_table || mysqli_num_rows($test_groups_table) == 0) {
    $group_pk = 'id'; // Fallback to 'id' if 'group_id' does not exist in groups table
}

// ─── ACTION A: PROCESSING THE "+ CREATE GROUP" FORM SUBMISSION ───
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_group'])) {
    $group_name = mysqli_real_escape_string($conn, trim($_POST['group_name']));
    $contribution_amount = floatval($_POST['contribution']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $cycle_length = intval($_POST['cycle_length']); // Max pool slot capacity
    
    // Generate unique alphanumeric 6-character invitation code string
    $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    // FIXED: Removed the non-existent 'status' column from groups query to match trustfund_db.sql
    // Set is_active to 0 upon creation for Admin Review gate flow
    $insert_group = "INSERT INTO groups (group_name, description, contribution_amount, frequency, cycle_length, invite_code, is_active, created_by) 
                     VALUES ('$group_name', 'TrustFund Paluwagan Savings Pool Group Circle.', '$contribution_amount', '$frequency', '$cycle_length', '$invite_code', 0, '$current_user_id')";

    if (mysqli_query($conn, $insert_group)) {
        $new_group_id = mysqli_insert_id($conn);

        // Auto-join the creator as an active member inside their group mapping table
        $join_creator = "INSERT INTO group_members (user_id, group_id, status) VALUES ('$current_user_id', '$new_group_id', 'active')";
        mysqli_query($conn, $join_creator);
        $creator_member_id = mysqli_insert_id($conn);

        // ====================================================================
        // OPTIONAL: AUTOMATED MATRIX SCHEDULE GENERATION (Runs immediately if required)
        // If you want the matrix generated only AFTER Admin approval, move this block 
        // to your admin approval controller script instead!
        // ====================================================================
        $total_payout = $contribution_amount * $cycle_length;
        $start_date = new DateTime();
        
        for ($i = 0; $i < $cycle_length; $i++) {
            $cycle_num = $i + 1;
            $end_date = clone $start_date;
            
            if (strtolower($frequency) === 'weekly') {
                $end_date->modify('+7 days');
            } else {
                $end_date->modify('+1 month');
            }
            
            $start_str = $start_date->format('Y-m-d');
            $end_str = $end_date->format('Y-m-d');
            
            // 1. Insert into cycles table
            $ins_cycle = "INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status) 
                          VALUES ($new_group_id, $cycle_num, '$start_str', '$end_str', 'ongoing')";
            mysqli_query($conn, $ins_cycle);
            $new_cycle_id = mysqli_insert_id($conn);
            
            // 2. Pre-allocate payout target slot assignments
            $assigned_member = ($cycle_num === 1) ? $creator_member_id : "NULL";
            $ins_payout = "INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status) 
                           VALUES ($new_cycle_id, $assigned_member, $total_payout, '$end_str', 'pending')";
            mysqli_query($conn, $ins_payout);
            
            // 3. Pre-allocate contribution requirement record entry lines
            $ins_contrib = "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status) 
                            VALUES ($new_cycle_id, $creator_member_id, $contribution_amount, '$end_str', 'pending')";
            mysqli_query($conn, $ins_contrib);
            
            $start_date = clone $end_date;
        }
        // ====================================================================

        // Redirects back to itself with an admin approval instruction notification parameter
        header("Location: my_groups.php?success=" . urlencode("Group created successfully! It is currently pending Admin approval. Invite Code: $invite_code"));
        exit();
    } else {
        $error_message = "Database writing fault failure string: " . mysqli_error($conn);
    }
}

// ─── ACTION B: PROCESSING THE "JOIN WITH CODE" FORM SUBMISSION ───
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_join_group'])) {
    $target_code = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['target_invite_code'])));
    
    // FIXED: Removed non-existent 'status' column verification from query, relies strictly on is_active = 1
    $lookup = mysqli_query($conn, "SELECT * FROM groups WHERE invite_code = '$target_code' AND is_active = 1 LIMIT 1");
    
    if (mysqli_num_rows($lookup) > 0) {
        $group_row = mysqli_fetch_assoc($lookup);
        $found_group_id = isset($group_row['group_id']) ? $group_row['group_id'] : $group_row['id'];
        $group_contribution = floatval($group_row['contribution_amount']);
        
        // Prevent duplicate group records entry exceptions
        $duplicate_check = mysqli_query($conn, "SELECT * FROM group_members WHERE group_id = '$found_group_id' AND user_id = '$current_user_id'");
        
        if (mysqli_num_rows($duplicate_check) == 0) {
            // Join group member record write
            mysqli_query($conn, "INSERT INTO group_members (group_id, user_id, status) VALUES ('$found_group_id', '$current_user_id', 'active')");
            $new_member_id = mysqli_insert_id($conn);
            
            // ====================================================================
            // SYNC NEW MEMBER TO EXPECTED CONTRIBUTIONS LOGS
            // Since schedule cycles are already pre-allocated, automatically insert 
            // a contribution row for this member across all existing group cycles
            // ====================================================================
            $cycles_lookup = mysqli_query($conn, "SELECT cycle_id, end_date FROM cycles WHERE group_id = '$found_group_id'");
            while ($cycle = mysqli_fetch_assoc($cycles_lookup)) {
                $c_id = $cycle['cycle_id'];
                $d_date = $cycle['end_date'];
                mysqli_query($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status) 
                                     VALUES ($c_id, $new_member_id, $group_contribution, '$d_date', 'pending')");
            }
            // ====================================================================
            
            header("Location: my_groups.php?success=" . urlencode("Successfully joined the savings pool circle!"));
            exit();
        } else {
            $error_message = "You are already a registered active member of this circle group!";
        }
    } else {
        $error_message = "Invalid, pending, closed, or inactive invitation code. Please verify parameter values.";
    }
}

// ─── STAGE DATA FOR DISPLAY (FIXED MAPPING PATHS) ───
// Group table query adjusted to remove non-existent status column constraints
$groups_query = "SELECT g.*, 
                  (SELECT COUNT(*) FROM group_members WHERE group_id = g.$group_pk AND status = 'active') as members_count 
                  FROM groups g 
                  JOIN group_members m ON g.$group_pk = m.group_id 
                  WHERE m.user_id = '$current_user_id' 
                    AND m.status = 'active'";
$groups_res = mysqli_query($conn, $groups_query);

// ─── RENDER PHASE ───
// Corrected path: move up 2 steps (php/ -> back-end/), then enter front-end/views/
include "../../front-end/views/my_groups-view.php";
?>