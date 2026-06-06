<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

// User context (prepared)
$stmt = mysqli_prepare($conn, "SELECT first_name, last_name, role FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'] ?: '', 0, 1));
}

// Payments history: use only columns that exist in schema (contributions + cycles + groups).
// contributions has no payment_method / created_at / cycle_number directly; we pull what we can + cycle_number via join.
// The view gracefully handles missing fields.
$payments_log_q = "
    SELECT 
        c.contribution_id,
        c.amount,
        c.status,
        c.paid_at,
        c.due_date,
        g.group_name,
        cy.cycle_number
    FROM contributions c
    JOIN group_members m ON c.member_id = m.member_id
    JOIN cycles cy ON c.cycle_id = cy.cycle_id
    JOIN groups g ON cy.group_id = g.group_id
    WHERE m.user_id = ?
    ORDER BY c.contribution_id DESC
";
$stmt = mysqli_prepare($conn, $payments_log_q);
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$payments_log_res = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt); // result lives for the view include

// 4. RENDER PHASE
include "../../front-end/views/payments-view.php";
?>