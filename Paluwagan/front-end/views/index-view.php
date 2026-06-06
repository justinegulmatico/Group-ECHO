<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome to TrustFund</title>
  <link rel="stylesheet" href="assets/css/global.css" />
  <link rel="stylesheet" href="assets/css/auth.css?v=<?= filemtime('assets/css/auth.css'); ?>" />
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

          <form method="POST" action="index.php">
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
            <?php if(isset($login_error) && $login_error): ?> <div class="error-msg"><?= $login_error ?></div> <?php endif; ?>
          </form>
        </div>

        <div id="registerSection" class="tab-content">
          <h2 class="auth-heading">Create Account</h2>
          <p class="auth-subheading">Join the community savings platform</p>

          <form method="POST" action="index.php" enctype="multipart/form-data">
            <div class="form-row">
              <div class="form-group">
                <label class="input-label">First Name</label>
                <input type="text" name="firstname" class="input-field" placeholder="Juan" required />
              </div>
              <div class="form-group">
                <label class="input-label">Last Name</label>
                <input type="text" name="lastname" class="input-field" placeholder="Dela Cruz" required />
              </div>
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
            <?php if(isset($register_error) && $register_error): ?> <div class="error-msg"><?= $register_error ?></div> <?php endif; ?>
            <?php if(isset($success) && $success): ?> <div class="success-msg"><?= $success ?></div> <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
</div>

<div id="status-modal" class="tf-modal-overlay">
  <div class="tf-modal-box">
    <div id="modal-icon" style="margin-bottom: 15px;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f47321" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 8v4l3 3"></path>
        </svg>
    </div>
    <h3 id="modal-title" style="margin-bottom: 10px; font-size: 1.5rem;">Account Status</h3>
    <p id="modal-message" style="color: #666; margin-bottom: 25px; line-height: 1.5;"></p>
    <button class="btn-primary" onclick="closeStatusModal()" style="width: 100%; padding: 12px;">Got it</button>
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

    // Modal Logic
function openStatusModal(title, message) {
        const modal = document.getElementById('status-modal');
        document.getElementById('modal-title').textContent = title;
        document.getElementById('modal-message').textContent = message;
        
        // Add a small "fade-in" animation effect
        modal.style.display = 'flex';
        modal.style.opacity = '0';
        setTimeout(() => { modal.style.opacity = '1'; modal.style.transition = 'opacity 0.3s ease'; }, 10);
    }

    function closeStatusModal() {
        document.getElementById('status-modal').style.setProperty('display', 'none', 'important');
    }

    // PHP Trigger Injection
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (isset($status_trigger) && $status_trigger): ?>
            openStatusModal("<?= $status_trigger['title'] ?>", "<?= $status_trigger['msg'] ?>");
        <?php endif; ?>
    });

    // PHP Trigger Injection
    <?php if (isset($status_trigger) && $status_trigger): ?>
        window.onload = function() {
            openStatusModal("<?= $status_trigger['title'] ?>", "<?= $status_trigger['msg'] ?>");
        };
    <?php endif; ?>

    <?php if (isset($_POST['register']) || !empty($success)): ?>
      switchTab('register');
    <?php endif; ?>
</script>
<script src="front-end/js/notifications.js"></script>
</body>
</html>