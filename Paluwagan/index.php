<?php
session_start();
include "back-end/db.php";

$login_error = "";
$register_error = "";
$success = "";
$status_trigger = null; // New: This will hold our modal data

// ================= LOGIN PROCESSING =================
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['login_username']);
    $password = $_POST['login_password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    $row = mysqli_fetch_assoc($result);

    if (!$row) {
        $login_error = "User not found!";
    } elseif (!password_verify($password, $row['password_hash'])) {
        $login_error = "Invalid Password!";
    } elseif ($row['status'] === 'suspended') {
        $login_error = "Your account has been suspended by an administrator.";
    } else {
        // NEW: Check status and set trigger instead of redirecting
        if ($row['status'] === 'pending' || $row['status'] === 'denied') {
            $status_trigger = [
                'title' => ($row['status'] === 'pending') ? "Account Pending" : "Application Denied",
                'msg'   => ($row['status'] === 'pending') ? "Your account is under review. Please wait." : "Your application was denied."
            ];
        } else {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            if ($_SESSION['role'] === 'admin') {
                header("Location: back-end/php/admin.php");
            } else {
                header("Location: back-end/php/dashboard.php");
            }
            exit();
        }
    }
}

// ================= REGISTRATION PROCESSING =================
if (isset($_POST['register'])) {
    $username   = mysqli_real_escape_string($conn, $_POST['username']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $occupation = mysqli_real_escape_string($conn, $_POST['occupation']);
    $address    = mysqli_real_escape_string($conn, $_POST['address']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = mysqli_real_escape_string($conn, trim($_POST['firstname']));
    $last_name  = mysqli_real_escape_string($conn, trim($_POST['lastname']));

    $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username' OR email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $register_error = "Username or Email already exists!";
    } else {
        $insertUser = "INSERT INTO users (username, first_name, last_name, email, phone, occupation, address, password_hash, role)
                    VALUES ('$username', '$first_name', '$last_name', '$email', '$phone', '$occupation', '$address', '$password', 'member')";

        if (mysqli_query($conn, $insertUser)) {
            $new_user_id = mysqli_insert_id($conn);
            if (!empty($_FILES['proof']['name'])) {
                $target_dir = __DIR__ . "/assets/uploads/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                $filename = "verify_" . $new_user_id . "_" . time() . "." . pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
                if (move_uploaded_file($_FILES['proof']['tmp_name'], $target_dir . $filename)) {
                    mysqli_query($conn, "INSERT INTO user_verifications (user_id, document) VALUES ('$new_user_id', '$filename')");
                }
            }
            $success = "Account Created Successfully! Pending review.";
        } else {
            $register_error = "Database Error.";
        }
    }
}

include "front-end/views/index-view.php";
?>