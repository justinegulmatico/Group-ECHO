(function () {

  /* ── Internal state ── */
  var notifications = [];

  /* ── DOM refs ── */
  var btn       = document.getElementById('notif-btn');
  var panel     = document.getElementById('notif-panel');
  var overlay   = document.getElementById('notif-overlay');
  var list      = document.getElementById('notif-list');
  var badge     = document.getElementById('notif-badge');
  var markAllBtn = document.getElementById('mark-all-btn');

  /* ── Wire up events ── */
  if (btn)       btn.addEventListener('click', togglePanel);
  if (overlay)   overlay.addEventListener('click', closePanel);
  if (markAllBtn) markAllBtn.addEventListener('click', markAllRead);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closePanel();
  });

  /* ── Toggle / open / close ── */
  function togglePanel() {
    if (panel.classList.contains('open')) {
      closePanel();
    } else {
      openPanel();
    }
  }

  function openPanel() {
    panel.classList.add('open');
    overlay.classList.add('open');
  }

  function closePanel() {
    panel.classList.remove('open');
    overlay.classList.remove('open');
  }

  /* ── Add a notification (called by page scripts) ── */
  function addNotification(title, body) {
    var notif = {
      id:    Date.now(),
      title: title || '',
      body:  body  || '',
      time:  nowTime(),
      read:  false
    };
    notifications.unshift(notif);
    renderList();
    updateBadge();
  }

  /* ── Mark one as read ── */
  function readOne(id) {
    var n = findById(id);
    if (n) n.read = true;
    renderList();
    updateBadge();
  }

  /* ── Mark all read ── */
  function markAllRead() {
    notifications.forEach(function (n) { n.read = true; });
    renderList();
    updateBadge();
  }

  /* ── Render the list ── */
  function renderList() {
    if (!list) return;

    if (notifications.length === 0) {
      list.innerHTML =
        '<div class="notif-empty">' +
          '<p>No notifications</p>' +
          '<span>You\'re all caught up!</span>' +
        '</div>';
      return;
    }

    var html = '';
    notifications.forEach(function (n) {
      html +=
        '<div class="notif-item ' + (n.read ? '' : 'unread') + '" data-id="' + n.id + '">' +
          '<div class="notif-dot"' + (n.read ? ' style="visibility:hidden"' : '') + '></div>' +
          '<div>' +
            '<div class="notif-item-text">'  + esc(n.title) + '</div>' +
            '<div class="notif-item-body">'  + esc(n.body)  + '</div>' +
            '<div class="notif-item-time">'  + esc(n.time)  + '</div>' +
          '</div>' +
        '</div>';
    });
    list.innerHTML = html;

    /* Attach click to mark-read */
    list.querySelectorAll('.notif-item').forEach(function (el) {
      el.addEventListener('click', function () {
        readOne(parseInt(el.getAttribute('data-id')));
      });
    });
  }

  /* ── Update badge counter ── */
  function updateBadge() {
    if (!badge) return;
    var unread = notifications.filter(function (n) { return !n.read; }).length;
    if (unread > 0) {
      badge.textContent = unread;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  /* ── Helpers ── */
  function findById(id) {
    for (var i = 0; i < notifications.length; i++) {
      if (notifications[i].id === id) return notifications[i];
    }
    return null;
  }

  function nowTime() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  /* HTML Sanitizer */
  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ── Public API ── */
  window.Notifications = {
    add:         addNotification,
    markAllRead: markAllRead,
    open:        openPanel,
    close:       closePanel
  };

})();

/* ================================================
   Toast — global helper, usable by any page script
   showToast('message', 'success' | 'error' | 'info')
   ================================================ */
function showToast(msg, type) {
  var container = document.getElementById('toast-container');
  if (!container) return;
  var toast = document.createElement('div');
  toast.className = 'toast toast-' + (type || 'info');
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(function () { toast.classList.add('show'); }, 10);
  setTimeout(function () {
    toast.classList.remove('show');
    setTimeout(function () { toast.remove(); }, 300);
  }, 3000);
}

/* ── AUTOMATED SYSTEM INTERCEPTOR FOR URL FLAGGED MESSAGES ── */
document.addEventListener("DOMContentLoaded", function() {
  var urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('success')) {
    showToast(urlParams.get('success'), 'success');
  } else if (urlParams.has('error')) {
    showToast(urlParams.get('error'), 'error');
  }
});