<?php
session_start();
include "../../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// Handle verification approval/rejection
if (isset($_POST['action_verify'])) {
    $v_id = (int)($_POST['verification_id'] ?? 0);
    $u_id = (int)($_POST['target_user_id'] ?? 0);
    $status = $_POST['status'] ?? 'denied';

    $stmt = mysqli_prepare($conn, "UPDATE user_verifications SET status = ?, verified_at = NOW() WHERE verification_id = ?");
    if (!$stmt) {
        die("Prepare failed (user_verifications update): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "si", $status, $v_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $user_status = ($status === 'approved') ? 'activated' : 'denied';
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE user_id = ?");
    if (!$stmt) {
        die("Prepare failed (users update): " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "si", $user_status, $u_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: verifications.php?success=" . urlencode("Verification " . $status));
    exit();
}

// Fetch pending verifications
$stmt = mysqli_prepare($conn, "
    SELECT uv.*, u.first_name, u.last_name, u.username, u.email, u.phone, u.occupation, u.address, u.created_at 
    FROM user_verifications uv
    JOIN users u ON uv.user_id = u.user_id 
    WHERE uv.status = 'pending'
    ORDER BY u.created_at DESC
");
if (!$stmt) {
    die("Prepare failed (fetch verifications): " . mysqli_error($conn));
}
mysqli_stmt_execute($stmt);
$verifications = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

include "../../../front-end/views/admin/verifications-view.php";
?>