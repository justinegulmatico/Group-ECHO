<?php
session_start();
// Verified path to your database connection
include "trustfund/back-end/db.php"; 

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

            header("Location: " . ($_SESSION['role'] === 'admin' ? "trustfund/admin.php" : "trustfund/dashboard.php"));
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
            // Grab the foreign key ID for the table link
            $new_user_id = mysqli_insert_id($conn);

            // 3. Document attachment processing
            if (!empty($_FILES['proof']['name'])) {
                $target_dir = "uploads/";
                if (!is_dir($target_dir)) { 
                    mkdir($target_dir, 0777, true); 
                }

                $file_ext = pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
                $filename = "verify_" . $new_user_id . "_" . time() . "." . $file_ext;
                $target_path = $target_dir . $filename;

                if (move_uploaded_file($_FILES['proof']['tmp_name'], $target_path)) {
                    // Match with your exact DB schema columns: verification_id, user_id, document, status, verified_at
                    // status automatically falls back to 'pending' via your database default setting.
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Authentication</title>
  <link rel="stylesheet" href="css/global.css" />
  <link rel="stylesheet" href="css/auth.css" />
</head>
<body>

<div class="auth-page">
    <div class="auth-left">
      <div class="auth-brand">
        <div class="auth-brand-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <path d="M12 5v14M5 12h14"/>
          </svg>
        </div>
        <span class="auth-brand-name">TrustFund</span>
      </div>
      <h1 class="auth-tagline">Community savings,<br>built on trust</h1>
      <p class="auth-tagline-desc">
        Join or create a group savings circle with your friends, family, or colleagues.
        Transparent, trackable, and trustworthy.
      </p>
    </div>

    <div class="auth-right">
      <div class="auth-right-inner">
        
        <div class="auth-tabs" role="tablist">
          <button id="loginTab" class="auth-tab active" onclick="switchTab('login')">Sign In</button>
          <button id="registerTab" class="auth-tab" onclick="switchTab('register')">Create Account</button>
        </div>

        <div id="loginSection" class="tab-content active">
          <h2 class="auth-heading">Welcome Back</h2>
          <p class="auth-subheading">Sign in to manage your savings groups</p>

          <form method="POST">
            <div class="form-group">
              <label class="input-label">Username</label>
              <input type="text" name="login_username" class="input-field" required />
            </div>

            <div class="form-group">
              <label class="input-label">Password</label>
              <div class="password-wrap">
                <input id="pass_log" type="password" name="login_password" class="input-field" placeholder="••••••••" required />
                <button class="password-toggle" type="button" onclick="togglePass('pass_log', this)">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </div>
            </div>

            <button class="btn-primary" type="submit" name="login">Sign In</button>
            <?php if($login_error): ?> <div class="error-msg"><?= $login_error ?></div> <?php endif; ?>
          </form>
        </div>

        <div id="registerSection" class="tab-content">
          <h2 class="auth-heading">Create Account</h2>
          <p class="auth-subheading">Join the community savings platform</p>

          <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
              <label class="input-label">Full Name</label>
              <input type="text" name="fullname" class="input-field" placeholder="Juan Dela Cruz" required />
            </div>

            <div class="form-group">
              <label class="input-label">Username</label>
              <input type="text" name="username" class="input-field" required />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="input-label">Email</label>
                <input type="email" name="email" class="input-field" required />
              </div>
              <div class="form-group">
                <label class="input-label">Phone</label>
                <input type="text" name="phone" class="input-field" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="input-label">Occupation</label>
                <input type="text" name="occupation" class="input-field" />
              </div>
              <div class="form-group">
                <label class="input-label">Address</label>
                <input type="text" name="address" class="input-field" />
              </div>
            </div>

            <div class="form-group">
              <label class="input-label">Password</label>
              <input type="password" name="password" class="input-field" required />
            </div>

            <div class="form-group file-group-wrapper">
              <label class="input-label">Proof of Income (Optional)</label>
              <input type="file" name="proof" class="input-field file-input-field" />
            </div>

            <button class="btn-primary" type="submit" name="register">Create Account</button>
            <?php if($register_error): ?> <div class="error-msg"><?= $register_error ?></div> <?php endif; ?>
            <?php if($success): ?> <div class="success-msg"><?= $success ?></div> <?php endif; ?>
          </form>
        </div>

        <div class="demo-divider">
          <span class="demo-divider-line"></span>
          <span class="demo-divider-text">Demo Access</span>
          <span class="demo-divider-line"></span>
        </div>

        <button class="demo-btn" type="button" onclick="fillAdmin()">
          Admin: admin / admin123
        </button>

      </div>
    </div>
</div>

<script>
    function switchTab(type) {
      const isLogin = type === 'login';
      document.getElementById("loginSection").classList.toggle("active", isLogin);
      document.getElementById("registerSection").classList.toggle("active", !isLogin);
      document.getElementById("loginTab").classList.toggle("active", isLogin);
      document.getElementById("registerTab").classList.toggle("active", !isLogin);
    }

    function togglePass(id, btn) {
      const input = document.getElementById(id);
      const isPass = input.type === 'password';
      input.type = isPass ? 'text' : 'password';
      btn.innerHTML = isPass 
        ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M1 1l22 22"/></svg>`
        : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>`;
    }

    function fillAdmin() {
      switchTab('login');
      document.getElementsByName('login_username')[0].value = 'admin';
      document.getElementsByName('login_password')[0].value = 'admin123';
    }

    <?php if (isset($_POST['register']) || !empty($success)): ?>
      switchTab('register');
    <?php endif; ?>
</script>
</body>
</html>