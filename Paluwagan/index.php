<?php
session_start();
include "back-end/db.php";

$login_error = "";
$register_error = "";
$success = "";
$status_trigger = null;

// LOGIN
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['login_username']);
    $password = $_POST['login_password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        $login_error = "User not found!";
    } elseif (!password_verify($password, $row['password_hash'])) {
        $login_error = "Invalid Password!";
    } elseif ($row['status'] === 'suspended') {
        $login_error = "Your account has been suspended by an administrator.";
    } elseif ($row['status'] === 'pending' || $row['status'] === 'denied') {
        $status_trigger = [
            'title' => ($row['status']==='pending') ? "Account Pending" : "Application Denied",
            'msg'   => ($row['status']==='pending') ? "Your account is under review. Please wait." : "Your application was denied."
        ];
    } else {
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['first_name'] = $row['first_name'] ?? '';
        $_SESSION['last_name'] = $row['last_name'] ?? '';

        header("Location: " . ($row['role']==='admin' ? "back-end/php/admin.php" : "back-end/php/dashboard.php"));
        exit();
    }
}

// REGISTER (always starts as pending)
if (isset($_POST['register'])) {
    $username   = mysqli_real_escape_string($conn, $_POST['username']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $occupation = mysqli_real_escape_string($conn, $_POST['occupation']);
    $address    = mysqli_real_escape_string($conn, $_POST['address']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = mysqli_real_escape_string($conn, trim($_POST['firstname']));
    $last_name  = mysqli_real_escape_string($conn, trim($_POST['lastname']));

    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? OR email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
    mysqli_stmt_close($stmt);

    if ($exists) {
        $register_error = "Username or Email already exists!";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, first_name, last_name, email, phone, occupation, address, password_hash, role, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'member', 'pending')");
        mysqli_stmt_bind_param($stmt, "ssssssss", $username, $first_name, $last_name, $email, $phone, $occupation, $address, $password);
        if (mysqli_stmt_execute($stmt)) {
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            if (!empty($_FILES['proof']['name'])) {
                $target_dir = __DIR__ . "/assets/uploads/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $filename = "verify_" . $new_user_id . "_" . time() . "." . pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['proof']['tmp_name'], $target_dir . $filename)) {
                    $v = mysqli_prepare($conn, "INSERT INTO user_verifications (user_id, document) VALUES (?, ?)");
                    mysqli_stmt_bind_param($v, "is", $new_user_id, $filename);
                    mysqli_stmt_execute($v);
                    mysqli_stmt_close($v);
                }
            }
            $success = "Account Created! Waiting for admin review.";
        } else {
            $register_error = "Database error.";
        }
    }
}

include "front-end/views/index-view.php";
?>