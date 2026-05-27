<?php
session_start();

// Back out one folder to 'back-end/', then connect to 'db.php'
include "back-end/db.php";

$login_error = "";
$register_error = "";
$success = "";

// ================= LOGIN PROCESSING =================
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['login_username']);
    $password = $_POST['login_password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    $row = mysqli_fetch_assoc($result);

    // 1. Check if the user even exists
    if (!$row) {
        $login_error = "User not found!";
    } 
    // 2. User exists, now check if the password is wrong
    elseif (!password_verify($password, $row['password_hash'])) {
        $login_error = "Invalid Password!";
    } 
    // 3. Username and Password are correct! Proceed to routing
    else {
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        // Admin Routing
        if ($_SESSION['role'] === 'admin') {
            header("Location: back-end/php/admin.php");
            exit();
        }
        
        // Pending or Denied Routing (Sends them to the review page we made)
        if (isset($row['status']) && ($row['status'] === 'pending' || $row['status'] === 'denied')) {
            header("Location: back-end/php/pending-review.php");
            exit();
        }

        // Standard Verified Member Routing
        header("Location: back-end/php/dashboard.php");
        exit();
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

    // FIXED: Split the raw string input by space first
    $raw_name_parts = explode(" ", trim($_POST['fullname']));
    
    if (count($raw_name_parts) > 1) {
        // Pop the very last element off as the last name
        $raw_last  = array_pop($raw_name_parts);
        // Keep all preceding elements together as the first name (handles multiple names like Mark Angelo)
        $raw_first = implode(" ", $raw_name_parts);
    } else {
        $raw_first = $raw_name_parts[0];
        $raw_last  = "";
    }

    // Now cleanly escape the isolated string pieces for the SQL statement
    $first_name = mysqli_real_escape_string($conn, $raw_first);
    $last_name  = mysqli_real_escape_string($conn, $raw_last);

    $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username' OR email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $register_error = "Username or Email already exists!";
    } else {
        $insertUser = "INSERT INTO users (username, first_name, last_name, email, phone, occupation, address, password_hash, role)
                    VALUES ('$username', '$first_name', '$last_name', '$email', '$phone', '$occupation', '$address', '$password', 'member')";

        if (mysqli_query($conn, $insertUser)) {
            $new_user_id = mysqli_insert_id($conn);

            if (!empty($_FILES['proof']['name'])) {
                // Back out two folders to root, then save inside assets/uploads/
                $target_dir = "../../assets/uploads/";
                if (!is_dir($target_dir)) { 
                    mkdir($target_dir, 0777, true); 
                }

                $file_ext = pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
                $filename = "verify_" . $new_user_id . "_" . time() . "." . $file_ext;
                $target_path = $target_dir . $filename;

                if (move_uploaded_file($_FILES['proof']['tmp_name'], $target_path)) {
                    $insertVerify = "INSERT INTO user_verifications (user_id, document) 
                                    VALUES ('$new_user_id', '$filename')";
                    mysqli_query($conn, $insertVerify);
                }
            }
            $success = "Account Created Successfully! Your application is now pending review.";
        } else {
            $register_error = "Database Error: Could not process submission.";
        }
    }
}

// Back out two folders to the root level to include your view file
include "front-end/views/index-view.php";
?>