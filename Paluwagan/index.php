<?php
session_start();
include "back-end/db.php";

$login_error = "";
$register_error = "";
$success = "";
$status_trigger = null;

// login processing
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
        // status routing
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
            // populate name for sidebar right away
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

// registration processing
if (isset($_POST['register'])) {
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $raw_first = trim($_POST['firstname'] ?? '');
    $raw_last  = trim($_POST['lastname'] ?? '');
    $raw_password = $_POST['password'] ?? '';

    // Normalize (collapse multiple spaces) for the final stored value
    $first_name = trim(preg_replace('/\s+/', ' ', $raw_first));
    $last_name  = trim(preg_replace('/\s+/', ' ', $raw_last));

    // Strict client + server validation (HTML5 patterns are mirrored here)
    // Names: letters + single spaces only. Must start and end with a letter. No special chars or numbers.
    $name_pattern = "/^[A-Za-z]+(?:\s[A-Za-z]+)*$/";
    $username_pattern = "/^[A-Za-z0-9_]{3,20}$/";
    $phone_pattern = "/^09[0-9]{9}$/";
    $email_pattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";

    // Reject if the raw input contained any special characters (dashes, etc.) or fails the cleaned pattern
    if (preg_match('/[^A-Za-z\s]/', $raw_first) || !preg_match($name_pattern, $first_name) || strlen($first_name) < 2) {
        $register_error = "First name can only contain letters and single spaces (e.g. Juan or Juan Dela). No numbers, dashes (----), or other special characters allowed. Minimum 2 characters.";
    } elseif (preg_match('/[^A-Za-z\s]/', $raw_last) || !preg_match($name_pattern, $last_name) || strlen($last_name) < 2) {
        $register_error = "Last name can only contain letters and single spaces (e.g. Dela Cruz). No numbers, dashes (----), or other special characters allowed. Minimum 2 characters.";
    } elseif (!preg_match($username_pattern, $username)) {
        $register_error = "Username must be 3–20 characters using only letters, numbers or underscore (_).";
    } elseif (!preg_match($email_pattern, $email)) {
        $register_error = "Please provide a valid email address.";
    } elseif ($phone !== '' && !preg_match($phone_pattern, $phone)) {
        $register_error = "Phone number must be exactly 11 digits starting with 09 (e.g. 09123456789), or leave blank.";
    } elseif (strlen($raw_password) < 6) {
        $register_error = "Password must be at least 6 characters long.";
    } else {
        // Escape for DB only after passing validation
        $username   = mysqli_real_escape_string($conn, $username);
        $email      = mysqli_real_escape_string($conn, $email);
        $phone      = mysqli_real_escape_string($conn, $phone);
        $occupation = mysqli_real_escape_string($conn, $occupation);
        $address    = mysqli_real_escape_string($conn, $address);
        $first_name = mysqli_real_escape_string($conn, $first_name);
        $last_name  = mysqli_real_escape_string($conn, $last_name);
        $password   = password_hash($raw_password, PASSWORD_DEFAULT);

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

                // Handle optional proof of income upload
                $document_path = null;
                if (!empty($_FILES['proof']['name'])) {
                    $target_dir = __DIR__ . "/assets/uploads/documents/";
                    $created_new_dir = !is_dir($target_dir);
                    if ($created_new_dir) { mkdir($target_dir, 0777, true); }

                    // Protect the uploads/documents folder from directory listing
                    $htaccess = $target_dir . '.htaccess';
                    if (!file_exists($htaccess)) {
                        file_put_contents($htaccess, "Options -Indexes\n\n<Files .htaccess>\n    Order allow,deny\n    Deny from all\n</Files>\n\n<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi|exe|dll|bat|cmd)$\">\n    Order allow,deny\n    Deny from all\n</FilesMatch>\n");
                    }
                    $index_html = $target_dir . 'index.html';
                    if (!file_exists($index_html)) {
                        file_put_contents($index_html, "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1><p>Directory listing is not allowed.</p></body></html>");
                    }

                    $ext = pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
                    $filename = "verify_" . $new_user_id . "_" . time() . "." . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
                    $document_path = 'documents/' . $filename;
                    // Attempt upload; we still create the verification record below even if the move fails
                    @move_uploaded_file($_FILES['proof']['tmp_name'], $target_dir . $filename);
                }

                // Always create a pending user_verifications record.
                // This ensures every new registration (status='pending') appears in
                // Admin → Verifications, even if the user did not upload a proof document.
                // This resolves the issue where users who were deleted (after rejection)
                // and then re-registered would not show up in the Verifications list.
                $vstmt = mysqli_prepare($conn, "INSERT INTO user_verifications (user_id, document) VALUES (?, ?)");
                mysqli_stmt_bind_param($vstmt, "is", $new_user_id, $document_path);
                mysqli_stmt_execute($vstmt);
                mysqli_stmt_close($vstmt);

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
}

// delete denied (safety) - archive to user_creation_history before removal
if (isset($_POST['delete_account'])) {
    $del_user = mysqli_real_escape_string($conn, $_POST['delete_username']);

    // Fetch full denied user record for archiving
    $fetch = mysqli_prepare($conn, "SELECT user_id, username, first_name, last_name, email, phone, occupation, address, role FROM users WHERE username = ? AND status = 'denied' LIMIT 1");
    mysqli_stmt_bind_param($fetch, "s", $del_user);
    mysqli_stmt_execute($fetch);
    $res = mysqli_stmt_get_result($fetch);
    $to_archive = mysqli_fetch_assoc($res);
    mysqli_stmt_close($fetch);

    if ($to_archive) {
        // Archive registration details to history (same as pending-review flow)
        $archive_stmt = mysqli_prepare($conn, "INSERT INTO user_creation_history 
            (original_user_id, username, first_name, last_name, email, phone, occupation, address, role, verification_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'denied')");

        mysqli_stmt_bind_param($archive_stmt, "issssssss", 
            $to_archive['user_id'], 
            $to_archive['username'], 
            $to_archive['first_name'], 
            $to_archive['last_name'], 
            $to_archive['email'], 
            $to_archive['phone'], 
            $to_archive['occupation'], 
            $to_archive['address'], 
            $to_archive['role']
        );
        mysqli_stmt_execute($archive_stmt);
        mysqli_stmt_close($archive_stmt);

        // Delete the user (user_verifications will cascade via FK)
        $del_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $to_archive['user_id']);
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }

    $success = "Old account deleted. You may now create a new one.";
}

include "front-end/views/index-view.php";
?>