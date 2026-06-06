<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — My Profile</title>
  <link rel="stylesheet" href="../../assets/css/global.css" />
  <link rel="stylesheet" href="../../assets/css/my-profile.css" />
</head>
<body>
  <div class="app-layout">

    <?php include "components/sidebar-view.php"; ?>

    <div class="main-content">

      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">My Profile</span>
        </div>
        <div class="topbar-right">
          <button class="notif-btn" id="notif-btn" aria-label="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notif-badge" id="notif-badge" style="display:none;"></span>
          </button>
        </div>
      </header>

      <div class="page-content">

        <div class="profile-hero">
          <div class="profile-hero-avatar" id="profile-avatar" onclick="document.getElementById('avatar-file').click()" title="Click to change photo" role="button" tabindex="0">
            <span class="avatar-initials"><?= htmlspecialchars($initials); ?></span>
            <input type="file" id="avatar-file" accept="image/*" class="file-input-hidden" onchange="previewAvatar(event)" />
          </div>
          <div class="profile-hero-info">
            <h1 class="profile-hero-name"><?= htmlspecialchars($full_name); ?></h1>
            <p class="profile-hero-meta">@<?= htmlspecialchars($_SESSION['username'] ?? 'username'); ?> · <?= htmlspecialchars($_SESSION['email'] ?? 'user@email.com'); ?></p>
            <div class="profile-badges">
              <span class="badge badge-verified" style="text-transform: capitalize;"><?= htmlspecialchars($user_role); ?></span>
            </div>
          </div>
        </div>

        <div class="profile-card">
          <h2 class="profile-card-title">Personal Information</h2>
          
          <form id="profile-form" class="profile-form" method="POST" action="">
            <div class="form-row">
              <div class="form-group">
                <label class="input-label" for="profile-firstname">First Name</label>
                <input type="text" id="profile-firstname" name="first_name" class="input-field" value="<?= htmlspecialchars($first_name); ?>" readonly style="background-color: #f9f9f9; cursor: not-allowed;" />
              </div>
              <div class="form-group">
                <label class="input-label" for="profile-lastname">Last Name</label>
                <input type="text" id="profile-lastname" name="last_name" class="input-field" value="<?= htmlspecialchars($last_name ?: '—'); ?>" readonly style="background-color: #f9f9f9; cursor: not-allowed;" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="input-label" for="profile-occupation">Occupation</label>
                <input type="text" id="profile-occupation" name="occupation" class="input-field" value="<?= htmlspecialchars($_SESSION['occupation'] ?? '—'); ?>" />
              </div>
              <div class="form-group">
                <label class="input-label" for="profile-address">Address</label>
                <input type="text" id="profile-address" name="address" class="input-field" value="<?= htmlspecialchars($_SESSION['address'] ?? '—'); ?>" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="input-label" for="profile-phone">Phone</label>
                <input type="tel" id="profile-phone" name="phone" class="input-field" value="<?= htmlspecialchars($_SESSION['phone'] ?? '—'); ?>" />
              </div>
              <div class="form-group">
                <label class="input-label">Account Role Group</label>
                <input type="text" class="input-field" value="<?= htmlspecialchars($user_role); ?>" readonly style="background-color: #f9f9f9; color: #e8481a; font-weight: 600; text-transform: capitalize; cursor: not-allowed;" />
              </div>
            </div>

            <button type="submit" class="btn-primary btn-save">Save Changes</button>
          </form>
        </div>

        <div class="profile-card">
          <h2 class="profile-card-title">Groups Summary</h2>
          <p class="profile-summary-text">Active member dashboard configuration system parameters tracking online.</p>
        </div>

      </div></div></div><script>
    // Preview Uploaded Image Avatar File Script
    function previewAvatar(event) {
      const file = event.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = e => {
        const avatar = document.getElementById('profile-avatar');
        avatar.style.backgroundImage = `url(${e.target.result})`;
        avatar.style.backgroundSize = 'cover';
        avatar.style.backgroundPosition = 'center';
        avatar.querySelector('.avatar-initials').style.display = 'none';
      };
      reader.readAsDataURL(file);
    }

    // Console Logging Submission Validation Sandbox
    document.getElementById('profile-form').addEventListener('submit', function(e) {
      // Un-comment e.preventDefault() if performing AJAX asynchronous processing calls
      // e.preventDefault();
      console.log('Profile submission controller active.');
    });
  </script>

  <div class="notif-overlay" id="notif-overlay"></div>
  <div class="notif-panel" id="notif-panel">
    <div class="notif-panel-header">
      <span class="notif-panel-title">Notifications</span>
      <button class="mark-all-btn" id="mark-all-btn">Mark all read</button>
    </div>
    <div class="notif-list" id="notif-list">
      <div class="notif-empty">
        <p>No notifications</p>
        <span>You're all caught up!</span>
      </div>
    </div>
  </div>
  <div class="toast-container" id="toast-container"></div>
  
  <script src="../../front-end/js/notifications.js"></script>
</body>
</html>