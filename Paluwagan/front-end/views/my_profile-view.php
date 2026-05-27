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
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notif-badge" id="notif-badge" style="display:none;"></span>
          </button>
        </div>
      </header>

      <div class="page-content" style="padding: 24px; max-w: 800px; margin: 0 auto;">
        
        <div class="profile-card" style="background: #fff; border: 1px solid #e3e3e3; padding: 32px; border-radius: 16px; display: flex; align-items: center; gap: 24px; margin-bottom: 24px;">
          <div class="profile-avatar" style="width: 80px; height: 80px; rounded-full: 50%; background: #ffe0d6; color: #e8481a; font-size: 1.8rem; font-weight: 700; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
            <?= htmlspecialchars($initials); ?>
          </div>
          <div>
            <h2 style="margin: 0; font-size: 1.5rem; color: #111; font-weight: 600;"><?= htmlspecialchars($full_name); ?></h2>
            <p style="margin: 4px 0 0; color: #666; font-size: 0.9rem; text-transform: capitalize; font-weight: 500;"><?= htmlspecialchars($user_role); ?></p>
          </div>
        </div>

        <div class="profile-details-section" style="background: #fff; border: 1px solid #e3e3e3; padding: 32px; border-radius: 16px;">
          <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.1rem; color: #111; font-weight: 600; border-b: 1px solid #f0f0f0; padding-bottom: 12px;">Account Information</h3>
          
          <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; flex-direction: column; gap: 6px;">
              <span style="font-size: 0.75rem; text-transform: uppercase; tracking: 0.05em; color: #999; font-weight: 600;">First Name</span>
              <div style="padding: 12px 16px; background: #f9f9f9; border: 1px solid #e3e3e3; border-radius: 8px; color: #333; font-size: 0.95rem; font-weight: 500;">
                <?= htmlspecialchars($first_name); ?>
              </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
              <span style="font-size: 0.75rem; text-transform: uppercase; tracking: 0.05em; color: #999; font-weight: 600;">Last Name</span>
              <div style="padding: 12px 16px; background: #f9f9f9; border: 1px solid #e3e3e3; border-radius: 8px; color: #333; font-size: 0.95rem; font-weight: 500;">
                <?= htmlspecialchars($last_name ?: '—'); ?>
              </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
              <span style="font-size: 0.75rem; text-transform: uppercase; tracking: 0.05em; color: #999; font-weight: 600;">System Privilege Group</span>
              <div style="padding: 12px 16px; background: #f9f9f9; border: 1px solid #e3e3e3; border-radius: 8px; color: #e8481a; font-size: 0.95rem; font-weight: 600; text-transform: capitalize;">
                <?= htmlspecialchars($user_role); ?>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

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

  <script src="../js/notifications.js"></script>
</body>
</html>