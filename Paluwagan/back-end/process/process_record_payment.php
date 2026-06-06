<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = (int)$_SESSION['user_id'];
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $cycle_number = isset($_POST['cycle_number']) ? max(1, (int)$_POST['cycle_number']) : 1;
    // payment_method received but not stored in current schema

    if ($group_id <= 0 || $member_id <= 0 || $amount <= 0) {
        header("Location: ../group_details.php?id=" . $group_id . "&error=" . urlencode("Invalid payment data."));
        exit();
    }

    // Verify membership (security)
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM group_members WHERE member_id = ? AND user_id = ? AND group_id = ? AND status='active' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "iii", $member_id, $user_id, $group_id);
    mysqli_stmt_execute($stmt);
    $okMember = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);
    if (!$okMember) {
        header("Location: ../group_details.php?id=" . $group_id . "&error=" . urlencode("Not authorized for this group."));
        exit();
    }

    // Find the pre-created cycle (we now generate all cycles on group creation)
    $stmt = mysqli_prepare($conn, "SELECT cycle_id FROM cycles WHERE group_id = ? AND cycle_number = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $group_id, $cycle_number);
    mysqli_stmt_execute($stmt);
    $cres = mysqli_stmt_get_result($stmt);
    $cycle = mysqli_fetch_assoc($cres);
    mysqli_stmt_close($stmt);

    if (!$cycle) {
        header("Location: ../group_details.php?id=" . $group_id . "&error=" . urlencode("Cycle not found for this group."));
        exit();
    }
    $cycle_id = (int)$cycle['cycle_id'];

    // Record contribution (prevent double with simple check in group_details too)
    $stmt = mysqli_prepare($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, paid_at, status) 
                                   VALUES (?, ?, ?, CURDATE(), CURDATE(), 'paid')");
    mysqli_stmt_bind_param($stmt, "iid", $cycle_id, $member_id, $amount);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($success) {
        // Also record in transactions fact table for OLAP
        $trans_stmt = mysqli_prepare($conn, "INSERT INTO transactions (group_id, cycle_id, member_id, user_id, transaction_type, amount, transaction_date, status, recorded_by) 
                                            VALUES (?, ?, ?, ?, 'contribution', ?, CURDATE(), 'completed', ?)");
        mysqli_stmt_bind_param($trans_stmt, "iiidii", $group_id, $cycle_id, $member_id, $user_id, $amount, $user_id);
        mysqli_stmt_execute($trans_stmt);
        mysqli_stmt_close($trans_stmt);

        header("Location: ../group_details.php?id=" . $group_id . "&tab=payments&success=" . urlencode("Payment recorded for cycle #$cycle_number."));
        exit();
    } else {
        header("Location: ../group_details.php?id=" . $group_id . "&error=" . urlencode("Failed to record payment."));
        exit();
    }
}

header("Location: ../my_groups.php");
exit();
?>