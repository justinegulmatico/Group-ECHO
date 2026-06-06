<?php
session_start();
include "back-end/db.php";

$login_error = "";
$register_error = "";
$success = "";
$status_trigger = null;

// ================= LOGIN PROCESSING =================
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
    } else {
        // Status Routing
        if ($row['status'] === 'suspended') {
            $status_trigger = [
                'title' => "Account Suspended",
                'msg' => "Your account has been suspended by an administrator. Please contact support.",
                'icon' => "suspended",
                'type' => "standard"
            ];
        } elseif ($row['status'] === 'pending') {
            $status_trigger = [
                'title' => "Account Pending",
                'msg' => "Your account is still under review. Please wait.",
                'icon' => "pending",
                'type' => "standard"
            ];
        } elseif ($row['status'] === 'denied') {
            $status_trigger = [
                'title' => "Application Denied",
                'msg' => "Your application was denied by the administrator. You can delete this application to start over.",
                'icon' => "error",
                'type' => "denied",
                'username' => $row['username']
            ];
        } else {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            // Populate name for immediate sidebar display (same logic)
            $_SESSION['first_name'] = $row['first_name'] ?? '';
            $_SESSION['last_name'] = $row['last_name'] ?? '';

            if ($_SESSION['role'] === 'admin') {
                header("Location: back-end/php/admin/index.php");
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

    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username = ? OR email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    $check = mysqli_stmt_get_result($stmt);
    $exists = mysqli_num_rows($check) > 0;
    mysqli_stmt_close($stmt);

    if ($exists) {
        $register_error = "Username or Email already exists!";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, first_name, last_name, email, phone, occupation, address, password_hash, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'member')");
        mysqli_stmt_bind_param($stmt, "ssssssss", $username, $first_name, $last_name, $email, $phone, $occupation, $address, $password);
        if (mysqli_stmt_execute($stmt)) {
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            if (!empty($_FILES['proof']['name'])) {
                $target_dir = __DIR__ . "/assets/uploads/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                $ext = pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
                $filename = "verify_" . $new_user_id . "_" . time() . "." . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
                if (move_uploaded_file($_FILES['proof']['tmp_name'], $target_dir . $filename)) {
                    $vstmt = mysqli_prepare($conn, "INSERT INTO user_verifications (user_id, document) VALUES (?, ?)");
                    mysqli_stmt_bind_param($vstmt, "is", $new_user_id, $filename);
                    mysqli_stmt_execute($vstmt);
                    mysqli_stmt_close($vstmt);
                }
            }
            $status_trigger = [
                'title' => "Account Created",
                'msg' => "Account created successfully! Please wait for administrators to approve your application.",
                'icon' => "success",
                'type' => "standard"
            ];
        } else {
            mysqli_stmt_close($stmt);
            $register_error = "Database Error.";
        }
    }
}

// Delete denied (also prepared for safety)
if (isset($_POST['delete_account'])) {
    $del_user = mysqli_real_escape_string($conn, $_POST['delete_username']);
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE username = ? AND status = 'denied'");
    mysqli_stmt_bind_param($stmt, "s", $del_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success = "Old account deleted. You may now create a new one.";
}

include "front-end/views/index-view.php";
?>