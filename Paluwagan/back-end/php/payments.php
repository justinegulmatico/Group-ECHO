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
// Corrected path: move up 2 steps (php/ -> back-end/), then enter front-end/views/
include "../../front-end/views/payments-view.php";
?>