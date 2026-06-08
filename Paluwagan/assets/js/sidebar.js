document.addEventListener('DOMContentLoaded', function () {
  const toggleBtn = document.getElementById('sidebar-toggle');
  const appLayout = document.querySelector('.app-layout');

  if (!toggleBtn || !appLayout) return;

  // Prevent transition animation on initial page load when restoring state
  appLayout.classList.add('no-transition');

  // Restore previous state from localStorage
  const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  if (isCollapsed) {
    appLayout.classList.add('sidebar-collapsed');
    toggleBtn.innerHTML = '&raquo;';
  }

  // Re-enable transitions after the browser has painted the initial state
  requestAnimationFrame(() => {
    appLayout.classList.remove('no-transition');
  });

  toggleBtn.addEventListener('click', function () {
    appLayout.classList.toggle('sidebar-collapsed');
    const collapsed = appLayout.classList.contains('sidebar-collapsed');
    toggleBtn.innerHTML = collapsed ? '&raquo;' : '&laquo;';
    localStorage.setItem('sidebarCollapsed', collapsed);
  });
});
