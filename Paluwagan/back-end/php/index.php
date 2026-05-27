<?php
session_start();
// Verified path pointing directly to your backend setup configuration
include "back-end/db.php";

$login_error = "";
$register_error = "";
$success = "";

// ================= LOGIN PROCESSING =================
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['login_username']);
    $password = $_POST['login_password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Routed directly into your clean /back-end/php/ endpoint architecture paths
            header("Location: " . ($_SESSION['role'] === 'admin' ? "back-end/php/admin.php" : "back-end/php/dashboard.php"));
            exit();
        } else {
            $login_error = "Invalid Password!";
        }
    } else {
        $login_error = "User not found!";
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

    $nameParts  = explode(" ", mysqli_real_escape_string($conn, $_POST['fullname']), 2);
    $first_name = $nameParts[0];
    $last_name  = $nameParts[1] ?? "";

    // 1. Username & Email Duplicate Safety Check
    $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username' OR email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $register_error = "Username or Email already exists!";
    } else {
        // 2. Main creation in users table
        $insertUser = "INSERT INTO users (username, first_name, last_name, email, phone, occupation, address, password_hash, role)
                       VALUES ('$username', '$first_name', '$last_name', '$email', '$phone', '$occupation', '$address', '$password', 'member')";

        if (mysqli_query($conn, $insertUser)) {
            $new_user_id = mysqli_insert_id($conn);

            // 3. Document attachment processing updated to your clean uploads asset layout
            if (!empty($_FILES['proof']['name'])) {
                $target_dir = "assets/uploads/";
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

// Load the view presentation script template layer
include "front-end/views/auth-view.php";
?>