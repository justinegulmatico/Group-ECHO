<?php
session_start();
include "../db.php";

// 1. Force safety checkpoint redirect if no logged-in user session exists
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_record_payment'])) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => "Session expired. Please log in again."]);
        exit();
    }
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// ========================================================================
// RE-ENGINEERED STEP 2 AJAX PAYMENT LEADER HANDLER
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_record_payment'])) {
    
    // Clear any previous output buffers to avoid broken JSON formatting strings
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    
    if (!isset($_POST['contribution_id']) || empty($_POST['contribution_id'])) {
        echo json_encode(["success" => false, "message" => "Missing identification identifier value context."]);
        exit();
    }

    $contribution_id = mysqli_real_escape_string($conn, $_POST['contribution_id']);
    $paid_date = date('Y-m-d'); 

    // Update query targeting your contributions schema
    $update_payment_q = "UPDATE contributions 
                         SET status = 'paid', paid_at = '$paid_date' 
                         WHERE contribution_id = '$contribution_id'";

    if (mysqli_query($conn, $update_payment_q)) {
        echo json_encode([
            "success" => true,
            "message" => "Record Payment Successfully"
        ]);
        exit();
    } else {
        echo json_encode([
            "success" => false,
            "message" => "SQL Database Error: " . mysqli_error($conn)
        ]);
        exit();
    }
}
// ========================================================================

// 2. Get Logged-In Identity Context Metadata parameters
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1));
}

// 3. RESOURCE QUERIES: Sub-query routing loop pulls recorded transactions tied to active group context profiles
$payments_log_q = "SELECT c.*, g.group_name FROM contributions c
                   JOIN group_members m ON c.member_id = m.member_id
                   JOIN cycles cy ON c.cycle_id = cy.cycle_id
                   JOIN groups g ON cy.group_id = g.group_id
                   WHERE m.user_id = '$current_user_id' ORDER BY c.contribution_id DESC";
$payments_log_res = mysqli_query($conn, $payments_log_q);

// 4. RENDER PHASE
include "../../front-end/views/payments-view.php";
?>