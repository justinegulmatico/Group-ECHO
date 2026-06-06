<?php
session_start();
include "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$update_success = false;

// Dynamic Form updates handling engine
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $occupation = mysqli_real_escape_string($conn, $_POST['occupation'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    
    $stmt = mysqli_prepare($conn, "UPDATE users SET phone=?, occupation=?, address=? WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "sssi", $phone, $occupation, $address, $current_user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if ($ok) {
        header("Location: my_profile.php?success=Profile updated successfully");
        exit();
    }
}

// Fetch fresh, verified parameters post action routing
$user_query = "SELECT * FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$first_name = $user_data ? $user_data['first_name'] : "User";
$last_name  = $user_data ? $user_data['last_name'] : "";
$full_name  = ($user_data) ? $first_name . " " . $last_name : "User";
$user_role  = ($user_data) ? ucfirst($user_data['role']) : "Member";

// FIXED: Populating the session array values so the input fields don't show empty or crash on load
if ($user_data) {
    $_SESSION['username']   = $user_data['username'];
    $_SESSION['email']      = $user_data['email'];
    $_SESSION['phone']      = $user_data['phone'];
    $_SESSION['occupation'] = $user_data['occupation'];
    $_SESSION['address']    = $user_data['address'];
    $_SESSION['role']       = $user_data['role'];
}

$initials = "U";
if ($user_data && !empty($last_name)) {
    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
} elseif ($user_data) {
    $initials = strtoupper(substr($first_name, 0, 2));
}

// Step back two directories out of back-end/php/ and step into your front-end layout folder
include "../../front-end/views/my_profile-view.php";
?>