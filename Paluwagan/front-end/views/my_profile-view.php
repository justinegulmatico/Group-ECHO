<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — My Profile</title>
  <link rel="stylesheet" href="../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>" />
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
        <div class="topbar-right"></div>
      </header>

      <div class="page-content">

        <!-- Success message -->
        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:14px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="profile-hero">
          <div class="profile-hero-avatar" id="profile-avatar" onclick="document.getElementById('avatar-file').click()" title="Click to change photo">
            <span class="avatar-initials"><?= htmlspecialchars($initials); ?></span>
            <input type="file" id="avatar-file" accept="image/*" class="file-input-hidden" onchange="previewAvatar(event)" />
          </div>
          <div class="profile-hero-info">
            <div class="profile-hero-name"><?= htmlspecialchars($full_name); ?></div>
            <div class="profile-hero-meta">
              @<?= htmlspecialchars($_SESSION['username'] ?? 'username'); ?> · <?= htmlspecialchars($_SESSION['email'] ?? ''); ?>
            </div>
            <div class="profile-badges">
              <span class="badge badge-role"><?= htmlspecialchars($user_role); ?></span>
              <?php if ($created_at): ?>
                <span class="badge badge-muted">Member since <?= date('M Y', strtotime($created_at)); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Stats Row -->
        <div class="stat-cards profile-stats">
          <div class="stat-card">
            <div class="stat-card-label">Active Groups</div>
            <div class="stat-card-value"><?= $active_groups; ?></div>
            <div class="stat-card-sub">groups you're part of</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Total Contributed</div>
            <div class="stat-card-value">₱<?= number_format($total_contributed, 0); ?></div>
            <div class="stat-card-sub">across all cycles</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Total Received</div>
            <div class="stat-card-value green">₱<?= number_format($total_received, 0); ?></div>
            <div class="stat-card-sub">payouts received</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Net Position</div>
            <div class="stat-card-value <?= $net_position >= 0 ? 'green' : 'red'; ?>">₱<?= number_format($net_position, 0); ?></div>
            <div class="stat-card-sub">received minus contributed</div>
          </div>
        </div>

        <!-- Personal Information -->
        <div class="profile-card">
          <div class="profile-card-header">
            <div>
              <h2 class="profile-card-title">Personal Information</h2>
              <p class="profile-card-subtitle">Update your contact &amp; work details</p>
            </div>
          </div>

          <form method="POST" action="my_profile.php" class="profile-form">
            <div class="form-row">
              <div class="form-group">
                <label class="input-label">First Name</label>
                <input type="text" class="input-field" value="<?= htmlspecialchars($first_name); ?>" readonly />
              </div>
              <div class="form-group">
                <label class="input-label">Last Name</label>
                <input type="text" class="input-field" value="<?= htmlspecialchars($last_name ?: '—'); ?>" readonly />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="input-label" for="profile-phone">Phone Number</label>
                <input type="tel" id="profile-phone" name="phone" class="input-field" value="<?= htmlspecialchars($_SESSION['phone'] ?? ''); ?>" placeholder="0917 123 4567" />
              </div>
              <div class="form-group">
                <label class="input-label" for="profile-occupation">Occupation</label>
                <input type="text" id="profile-occupation" name="occupation" class="input-field" value="<?= htmlspecialchars($_SESSION['occupation'] ?? ''); ?>" placeholder="Student / Teacher / etc." />
              </div>
            </div>

            <div class="form-group">
              <label class="input-label" for="profile-address">Address</label>
              <input type="text" id="profile-address" name="address" class="input-field" value="<?= htmlspecialchars($_SESSION['address'] ?? ''); ?>" placeholder="City, Province" />
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary">Save Changes</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>

  <script>
    // Avatar preview
    function previewAvatar(event) {
      const file = event.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = e => {
        const avatar = document.getElementById('profile-avatar');
        avatar.style.backgroundImage = `url(${e.target.result})`;
        avatar.style.backgroundSize = 'cover';
        avatar.style.backgroundPosition = 'center';
        const initialsEl = avatar.querySelector('.avatar-initials');
        if (initialsEl) initialsEl.style.display = 'none';
      };
      reader.readAsDataURL(file);
    }
  </script>

</body>
</html>
