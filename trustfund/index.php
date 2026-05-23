<?php
session_start();
include "db.php";

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

            // Step 3 Implementation: Conditional Admin Redirect
            header("Location: " . ($_SESSION['role'] === 'admin' ? "admin.php" : "dashboard.php"));
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

    $insert = "INSERT INTO users (username, first_name, last_name, email, phone, occupation, address, password_hash, role) 
               VALUES ('$username', '$first_name', '$last_name', '$email', '$phone', '$occupation', '$address', '$password', 'member')";

    if (mysqli_query($conn, $insert)) {
        $user_id = mysqli_insert_id($conn);

        if (!empty($_FILES['proof']['name'])) {
            $filename = time() . "_" . basename($_FILES['proof']['name']);
            if (move_uploaded_file($_FILES['proof']['tmp_name'], "uploads/" . $filename)) {
                mysqli_query($conn, "INSERT INTO user_verifications (user_id, document, status) VALUES ('$user_id', '$filename', 'pending')");
            }
        }
        $success = "Account Created Successfully!";
    } else {
        $register_error = "Username or Email already exists!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>TrustFund</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { height: 100vh; display: flex; background: #f4f0e8; }
        .left { width: 35%; background: linear-gradient(to bottom, #6b3f16, #000); color: white; padding: 30px; display: flex; align-items: flex-end; }
        .left h1 { font-size: 55px; line-height: 1.1; margin: 20px 0; }
        .left p { font-size: 20px; line-height: 1.5; }
        .logo { color: #ff5722; font-size: 40px; margin-bottom: 20px; }
        .right { width: 65%; display: flex; justify-content: center; align-items: center; padding: 20px; overflow: auto; }
        .container { width: 650px; }
        .tabs { display: flex; background: #ddd; border-radius: 15px; overflow: hidden; margin-bottom: 30px; }
        .tab-btn { width: 50%; padding: 18px; border: none; background: none; font-size: 22px; cursor: pointer; }
        .tab-btn.active { background: white; border-radius: 15px; }
        h2 { font-size: 50px; margin-bottom: 10px; }
        .sub { color: #666; margin-bottom: 30px; font-size: 22px; }
        form { display: none; }
        form.active-form { display: block; }
        label { display: block; margin-top: 15px; margin-bottom: 8px; font-size: 20px; }
        input { width: 100%; padding: 15px; border-radius: 12px; border: 1px solid #ccc; font-size: 18px; }
        .row { display: flex; gap: 20px; }
        .row div { flex: 1; }
        .submit-btn { width: 100%; padding: 18px; background: #ff5722; color: white; border: none; border-radius: 12px; font-size: 24px; margin-top: 30px; cursor: pointer; }
        .submit-btn:hover { background: #e64a19; }
        .message, .error { margin-top: 20px; font-size: 18px; }
        .message { color: green; }
        .error { color: red; }
        .demo { margin-top: 30px; background: #ddd; padding: 15px; border-radius: 12px; text-align: center; font-size: 20px; }
    </style>
</head>
<body>

<div class="left">
    <div>
        <div class="logo">⊕ TrustFund</div>
        <h1>Community savings,<br>built on trust</h1>
        <p>Join or create a group savings circle with your friends, family, or colleagues. Transparent, trackable, and trustworthy.</p>
    </div>
</div>

<div class="right">
    <div class="container">
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('login')">Sign In</button>
            <button class="tab-btn" onclick="switchTab('register')">Create Account</button>
        </div>

        <form method="POST" id="loginForm" class="active-form">
            <h2>Welcome Back</h2>
            <p class="sub">Sign in to manage your savings groups</p>

            <label>Username</label>
            <input type="text" name="login_username" required>

            <label>Password</label>
            <input type="password" name="login_password" required>

            <button type="submit" name="login" class="submit-btn">Sign In</button>
            <?= isset($login_error) ? "<div class='error'>$login_error</div>" : "" ?>

            <div class="demo">Admin: admin / admin123</div>
        </form>

        <form method="POST" enctype="multipart/form-data" id="registerForm">
            <h2>Create Account</h2>
            <p class="sub">Join the community savings platform</p>

            <label>Full Name</label>
            <input type="text" name="fullname" required>

            <label>Username</label>
            <input type="text" name="username" required>

            <div class="row">
                <div>
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div>
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
            </div>

            <div class="row">
                <div>
                    <label>Occupation</label>
                    <input type="text" name="occupation">
                </div>
                <div>
                    <label>Address</label>
                    <input type="text" name="address">
                </div>
            </div>

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Proof of Employment/Income (optional)</label>
            <input type="file" name="proof">

            <button type="submit" name="register" class="submit-btn">Create Account</button>
            <?= isset($success) ? "<div class='message'>$success</div>" : "" ?>
            <?= isset($register_error) ? "<div class='error'>$register_error</div>" : "" ?>
        </form>
    </div>
</div>

<script>
function switchTab(type) {
    const isLogin = type === 'login';
    document.getElementById("loginForm").classList.toggle("active-form", isLogin);
    document.getElementById("registerForm").classList.toggle("active-form", !isLogin);
    
    const tabs = document.querySelectorAll(".tab-btn");
    tabs[0].classList.toggle("active", isLogin);
    tabs[1].classList.toggle("active", !isLogin);
}

// Keep the correct tab visible if a form returns a validation error
<?php if (isset($_POST['register']) && isset($register_error)): ?>
    switchTab('register');
<?php endif; ?>
</script>

</body>
</html>