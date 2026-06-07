<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — OLAP Analytics | Admin</title>
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .page-content {
      background: #F4EFEA;
    }

    /* 1. Premium Dark Hero Header */
    .olap-hero {
      background: #1E1E1E;
      border-radius: 16px;
      padding: 24px;
      margin-bottom: 20px;
      color: #fff;
      font-family: var(--font-body);
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
    }
    .olap-hero-content {
      flex: 1;
    }
    .olap-hero-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
    }
    .olap-hero-title {
      font-size: 28px;
      font-weight: 700;
      font-family: var(--font-display);
      letter-spacing: -0.5px;
      margin: 0 0 8px 0;
      color: #fff;
    }
    .olap-hero-sub {
      font-size: 13px;
      color: rgba(255, 255, 255, 0.55);
      font-family: var(--font-body);
      margin: 0;
      line-height: 1.4;
    }



    /* 4. Utility actions — subtle interactive text buttons */
    .olap-utils {
      display: flex;
      gap: 12px;
      font-size: 12px;
      color: #6B6560;
      margin-bottom: 18px;
    }
    .olap-utils a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #6B6560;
      text-decoration: none;
      padding: 5px 10px;
      border-radius: 6px;
      font-weight: 500;
      transition: all 0.15s ease;
    }
    .olap-utils a:hover {
      color: #E15225;
      background-color: #F5F0E8;
    }
    .olap-utils svg {
      width: 12px;
      height: 12px;
      flex-shrink: 0;
    }

    .olap-sync-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 10px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 500;
      background: #E15225;
      color: #fff;
      border: none;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .olap-sync-btn:hover {
      background: #C93D12;
    }
    .olap-sync-btn.full {
      background: #fff;
      color: #E15225;
      border: 1px solid #E15225;
    }
    .olap-sync-btn.full:hover {
      background: #F5F0E8;
    }

    /* Sync buttons in topbar header */
    .topbar-sync-btn {
      font-size: 12px;
      font-weight: 600;
      padding: 6px 10px;
      border-radius: 6px;
      background: #E15225;
      color: #fff;
      border: none;
      cursor: pointer;
      transition: background 0.15s;
      white-space: nowrap;
      line-height: 1;
    }
    .topbar-sync-btn:hover {
      background: #C93D12;
    }
    .topbar-sync-btn.full {
      background: transparent;
      color: #E15225;
      border: 1px solid #E15225;
    }
    .topbar-sync-btn.full:hover {
      background: #F5F0E8;
    }

    /* Make topbar-right accommodate multiple controls */
    .topbar-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* 3. KPI Cards */
    .olap-kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 20px;
    }
    .olap-kpi-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .olap-kpi-label {
      font-size: 11px;
      font-weight: 600;
      color: #6b7280;
      letter-spacing: 0.3px;
      margin-bottom: 4px;
    }
    .olap-kpi-value {
      font-size: 26px;
      font-weight: 700;
      color: #111827;
      line-height: 1.1;
      letter-spacing: -0.6px;
      margin: 2px 0;
    }
    .olap-kpi-sub {
      font-size: 10px;
      color: #6C7D8C;
      font-family: var(--font-body);
      margin-top: 6px;
      line-height: 1.3;
    }

    /* Other existing styles for compatibility */
    .olap-card {
      background: #fff;
      border: 1px solid #E4DDD4;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
      margin-bottom: 18px;
    }
    .olap-card-header {
      font-size: 13px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .olap-filters {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }
    .olap-filter {
      flex: 1;
      min-width: 160px;
    }
    .olap-filter-label {
      display: block;
      font-size: 11px;
      font-weight: 600;
      color: #6B6560;
      margin-bottom: 5px;
      letter-spacing: 0.3px;
    }
    .olap-select {
      width: 100%;
      padding: 9px 12px;
      font-size: 13px;
      border: 1px solid #E4DDD4;
      border-radius: 8px;
      background: #fff;
      color: #1A1A1A;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%236B6560' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 11px center;
      background-size: 10px;
    }
    .olap-select:focus {
      outline: none;
      border-color: #E15225;
      box-shadow: 0 0 0 3px rgba(225,82,37,0.1);
    }
    .olap-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 4px;
    }
    .olap-pill-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      font-size: 12px;
      font-weight: 500;
      border: 1px solid #E4DDD4;
      border-radius: 9999px;
      background: #fff;
      color: #374151;
      cursor: pointer;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .olap-pill-btn:hover {
      background: #F9F6F1;
      border-color: #D4C9B8;
    }
    .olap-pill-btn.active {
      background: #E15225;
      color: #fff;
      border-color: #E15225;
    }
    .olap-btn {
      font-size: 13px;
      padding: 8px 16px;
      border-radius: 8px;
      font-weight: 600;
      border: 1px solid #E4DDD4;
      background: #fff;
      color: #1A1A1A;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .olap-btn:hover {
      background: #F9F6F1;
    }
    .olap-btn.primary {
      background: #E15225;
      color: #fff;
      border-color: #E15225;
    }
    .olap-btn.primary:hover {
      background: #C93D12;
    }
    .olap-chart-grid {
      display: grid;
      grid-template-columns: 7fr 5fr;
      gap: 16px;
    }
    .olap-chart-card {
      background: #fff;
      border: 1px solid #E4DDD4;
      border-radius: 12px;
      padding: 18px 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .olap-chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      gap: 8px;
    }
    .olap-chart-title {
      font-size: 14px;
      font-weight: 600;
      color: #374151;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .olap-chart-badge {
      width: 9px;
      height: 9px;
      border-radius: 2px;
    }
    .olap-chart-badge.contributions { background: #166534; }
    .olap-chart-badge.payouts { background: #E15225; }
    .olap-chart-container {
      position: relative;
      height: 260px;
    }
    .olap-table-card {
      background: #fff;
      border: 1px solid #E4DDD4;
      border-radius: 12px;
      padding: 18px 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .olap-table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }
    .olap-table-title {
      font-size: 14px;
      font-weight: 600;
      color: #374151;
    }
    .olap-table-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Sleek modern search input with icon */
    .olap-search {
      position: relative;
      display: flex;
      align-items: center;
    }
    .olap-search-input {
      height: 34px;
      padding: 0 12px 0 32px;
      font-size: 12.5px;
      border: 1px solid #E4DDD4;
      border-radius: 8px;
      background: #fff;
      color: #1A1A1A;
      width: 180px;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .olap-search-input:focus {
      outline: none;
      border-color: #E15225;
      box-shadow: 0 0 0 3px rgba(225, 82, 37, 0.1);
    }
    .olap-search-input::placeholder {
      color: #9E9790;
    }
    .olap-search-icon {
      position: absolute;
      left: 10px;
      width: 14px;
      height: 14px;
      color: #9E9790;
      pointer-events: none;
    }

    /* Unified export button group (CSV / PDF / Excel) */
    .olap-btn-group {
      display: inline-flex;
      border: 1px solid #E4DDD4;
      border-radius: 8px;
      overflow: hidden;
      background: #fff;
    }
    .olap-btn-export {
      font-size: 11px;
      font-weight: 600;
      padding: 6px 13px;
      background: #fff;
      color: #374151;
      border: none;
      border-right: 1px solid #E4DDD4;
      cursor: pointer;
      transition: background-color 0.1s ease, color 0.1s ease;
      white-space: nowrap;
    }
    .olap-btn-export:last-child {
      border-right: none;
    }
    .olap-btn-export:hover {
      background: #F9F6F1;
      color: #E15225;
    }

    /* Legacy override safety for old .olap-btn in table header */
    .olap-table-actions .olap-btn {
      font-size: 11px;
      padding: 5px 10px;
    }

    /* Chart export buttons (PNG downloads) — modern subtle style */
    .olap-small-btn {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 10px;
      font-weight: 600;
      padding: 5px 9px;
      border: 1px solid #E4DDD4;
      border-radius: 6px;
      background: #fff;
      color: #6B6560;
      cursor: pointer;
      transition: all 0.15s ease;
      line-height: 1;
      white-space: nowrap;
    }
    .olap-small-btn:hover {
      background: #F9F6F1;
      border-color: #E15225;
      color: #E15225;
    }
    .olap-small-btn svg {
      width: 11px;
      height: 11px;
      flex-shrink: 0;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }
    .data-table th {
      font-size: 10px;
      font-weight: 600;
      color: #6B6560;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 10px 12px;
      border-bottom: 1px solid #E4DDD4;
    }
    .data-table th[style*="text-align:right"],
    .data-table td.text-right {
      text-align: right;
    }
    .data-table td {
      padding: 10px 12px;
      color: #374151;
      border-bottom: 1px solid #F5F0E8;
    }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: #F9F6F1; }

    .olap-info {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 12px 16px;
      margin-top: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12px;
      color: #6B6560;
    }
    .olap-info-icon {
      color: #E15225;
      flex-shrink: 0;
    }

    .stat-cards { display: none; }
  </style>

  <style>
    /* Custom Modal for messages (replaces browser alerts like "localhost says") */
    .custom-modal {
      display: none;
      position: fixed;
      z-index: 99999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      align-items: center;
      justify-content: center;
    }
    .custom-modal-content {
      background-color: #fff;
      margin: 0;
      padding: 0;
      border: 1px solid #E4DDD4;
      border-radius: 16px;
      width: 90%;
      max-width: 768px; /* ~3xl for breathing room */
      box-shadow: 0 25px 80px rgba(0, 0, 0, 0.35);
      overflow: hidden;
    }
    .custom-modal-header {
      padding: 20px 28px;
      background: #1E1E1E;
      color: #FAF9F6;
      font-weight: 600;
      font-size: 17px;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top-left-radius: 16px;
      border-top-right-radius: 16px;
    }
    .custom-modal-body {
      padding: 24px 28px;
      font-size: 14px;
      line-height: 1.6;
      color: #374151;
      /* No max-height to eliminate inner scrollbar; content fits naturally */
    }
    .custom-modal-body .section-label {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 1px;
      color: #6B6560;
      margin-bottom: 6px;
      text-transform: uppercase;
    }
    .custom-modal-body table {
      font-size: 12px;
    }
    .custom-modal-body table th {
      font-size: 10px;
      padding-top: 8px;
      padding-bottom: 8px;
    }
    .custom-modal-body table td {
      padding-top: 8px;
      padding-bottom: 8px;
    }
    .custom-modal-body tr:hover {
      background-color: #F9F6F1;
    }
    .custom-modal-body .metric-label {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #6B6560;
    }
    .custom-modal-body .metric-value {
      font-size: 20px;
      font-weight: 700;
      color: #1A1A1A;
      line-height: 1;
    }
    .custom-modal-footer {
      padding: 16px 28px;
      background: #F4EFEA;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      border-top: 1px solid rgba(228, 221, 212, 0.1);
      border-bottom-left-radius: 16px;
      border-bottom-right-radius: 16px;
    }
    .custom-modal-close {
      background: none;
      border: none;
      color: #FAF9F6;
      font-size: 24px;
      cursor: pointer;
      width: 34px;
      height: 34px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 9999px;
      transition: background 0.15s ease;
    }
    .custom-modal-close:hover {
      background: rgba(250, 249, 246, 0.15);
    }
    .custom-modal-btn {
      padding: 10px 22px;
      background: #E15225;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .custom-modal-btn:hover {
      background: #C93D12;
      transform: scale(1.03);
    }

    /* === Drill-Down Modal Polish === */
    .drill-hero {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 1px solid #E4DDD4;
    }
    .drill-avatar {
      width: 52px;
      height: 52px;
      border-radius: 9999px;
      background: #FDE8E2;
      color: #E8481A;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 700;
      flex-shrink: 0;
      border: 2px solid #fff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .drill-hero-name {
      font-size: 18px;
      font-weight: 700;
      color: #1A1A1A;
      line-height: 1.1;
    }
    .drill-hero-sub {
      font-size: 12px;
      color: #6B6560;
      margin-top: 2px;
    }

    .drill-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 22px;
    }
    .drill-chip {
      display: inline-flex;
      align-items: center;
      padding: 6px 14px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 600;
      border: 1px solid #E4DDD4;
      background: #F9F6F1;
      color: #6B6560;
    }
    .drill-chip.primary {
      background: #E8F0FE;
      color: #1E40AF;
      border-color: #BFDBFE;
    }

    .drill-section-title {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      color: #6B6560;
      margin-bottom: 10px;
    }

    .drill-metrics {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-bottom: 24px;
    }
    .drill-metric {
      background: #FFFFFF;
      border: 1.5px solid #E4DDD4;
      border-radius: 12px;
      padding: 16px 14px;
      text-align: center;
      transition: transform 0.1s ease, box-shadow 0.1s ease;
    }
    .drill-metric:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .drill-metric-label {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #6B6560;
      margin-bottom: 6px;
      font-weight: 600;
    }
    .drill-metric-value {
      font-size: 22px;
      font-weight: 700;
      color: #1A1A1A;
      line-height: 1.1;
    }
    .drill-metric-value.accent {
      color: #E8481A;
    }
    .drill-metric-icon {
      width: 18px;
      height: 18px;
      margin: 0 auto 6px;
      color: #E8481A;
      opacity: 0.9;
    }

    .drill-table-wrap {
      border: 1.5px solid #E4DDD4;
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
    }
    .drill-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }
    .drill-table thead {
      background: #F4EFEA;
    }
    .drill-table th {
      text-align: left;
      font-weight: 600;
      color: #6B6560;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 12px 14px;
      border-bottom: 1px solid #E4DDD4;
    }
    .drill-table th.right { text-align: right; }
    .drill-table td {
      padding: 12px 14px;
      border-bottom: 1px solid #F0EBE3;
      color: #374151;
      vertical-align: middle;
    }
    .drill-table tr:last-child td { border-bottom: none; }
    .drill-table tr:hover td {
      background: #F9F6F1;
    }
    .drill-amount {
      font-weight: 600;
      color: #1A1A1A;
    }
    .drill-amount.positive { color: #2D7A45; }
    .drill-status {
      display: inline-block;
      padding: 3px 10px;
      font-size: 10px;
      font-weight: 600;
      border-radius: 9999px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .drill-status.verified {
      background: #E8F5EE;
      color: #2D7A45;
    }
    .drill-status.pending {
      background: #FEF3C7;
      color: #B45309;
    }
  </style>
</head>
<body>
  <div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar-view.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin → OLAP Analytics</span>
        </div>
        <div class="topbar-right">
          <button class="notif-btn" id="notif-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
          </button>
        </div>
      </header>

      <div class="page-content">

        <!-- 1. Premium Dark Hero Header -->
        <div class="olap-hero">
          <div class="olap-hero-content">
            <h1 class="olap-hero-title">OLAP Analytics</h1>
            <p class="olap-hero-sub">Multidimensional insights from the TrustFund data warehouse • Data from trustfund_olap</p>
          </div>
          <div class="olap-hero-actions">
            <button onclick="triggerOlapSync(false)" class="topbar-sync-btn" title="Sync OLAP data (incremental)">
              ↻ Sync
            </button>
            <button onclick="triggerOlapSync(true)" class="topbar-sync-btn full" title="Full reload (slower)">
              Full
            </button>
          </div>
        </div>

        <div class="olap-utils">
          <a href="#" onclick="window.location.reload(); return false;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
            Refresh Data
          </a>
          <a href="#" onclick="exportFullReport(); return false;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export Full Report
          </a>
        </div>

        <!-- Combined: KPIs (Overview) -->
        <div id="view-overview">
          <!-- 3. Polished KPI Cards -->
          <div class="olap-kpi-grid">
            <div class="olap-kpi-card">
              <div class="olap-kpi-label">Total Contributions</div>
              <div class="olap-kpi-value" id="kpi-contributions">₱<?= number_format($initial_data['summary']['total_contributions'] ?? 0, 2) ?></div>
              <div class="olap-kpi-sub">from OLAP data</div>
            </div>
            <div class="olap-kpi-card">
              <div class="olap-kpi-label">Total Payouts</div>
              <div class="olap-kpi-value" id="kpi-payouts">₱<?= number_format($initial_data['summary']['total_payouts'] ?? 0, 2) ?></div>
              <div class="olap-kpi-sub">distributed to members</div>
            </div>
            <div class="olap-kpi-card">
              <div class="olap-kpi-label">Transactions</div>
              <div class="olap-kpi-value" id="kpi-tx"><?= number_format($initial_data['summary']['total_transactions'] ?? 0) ?></div>
              <div class="olap-kpi-sub">recorded in warehouse</div>
            </div>
            <div class="olap-kpi-card">
              <div class="olap-kpi-label">Net Flow</div>
              <div class="olap-kpi-value" id="kpi-net">₱<?= number_format(($initial_data['summary']['total_contributions'] ?? 0) - ($initial_data['summary']['total_payouts'] ?? 0), 2) ?></div>
              <div class="olap-kpi-sub">current balance direction</div>
            </div>
          </div>
        </div>

        <!-- Slice & Dice Filters -->
        <div id="view-filters">
          <div class="olap-card">
            <div class="olap-card-header">
              <span>Analysis Controls — Slice, Dice &amp; Roll-up</span>
            </div>

            <div class="olap-filters" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
              <div class="olap-filter">
                <div class="olap-filter-label">Group (Slice)</div>
                <select id="filter-group" class="olap-select" onchange="applyFilters()">
                  <option value="0">All Groups</option>
                  <?php foreach ($groups as $g): ?>
                    <option value="<?= (int)$g['group_key'] ?>" <?= $group_id == $g['group_key'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($g['group_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="olap-filter">
                <div class="olap-filter-label">Year (Roll-up Level)</div>
                <select id="filter-year" class="olap-select" onchange="applyFilters()">
                  <option value="0">All Years (Overall)</option>
                  <?php foreach ($available_years as $y): ?>
                    <option value="<?= (int)$y ?>" <?= $year == $y ? 'selected' : '' ?>><?= (int)$y ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="olap-filter">
                <div class="olap-filter-label">Transaction Type (Dice)</div>
                <select id="filter-type" class="olap-select" onchange="applyFilters()">
                  <option value="all" <?= $trans_type === 'all' ? 'selected' : '' ?>>All Transactions</option>
                  <option value="contribution" <?= $trans_type === 'contribution' ? 'selected' : '' ?>>Contributions Only</option>
                  <option value="payout" <?= $trans_type === 'payout' ? 'selected' : '' ?>>Payouts Only</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Visual Charts -->
        <div id="view-charts">
          <div class="olap-chart-grid">
            <div class="olap-chart-card">
              <div class="olap-chart-header">
                <div class="olap-chart-title">
                  <span class="olap-chart-badge contributions"></span>
                  Contributions &amp; Payouts Over Time
                </div>
                <button onclick="exportChart('time-chart', 'time-series')" class="olap-small-btn" title="Download PNG">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                  <span>PNG</span>
                </button>
              </div>
              <div class="olap-chart-container">
                <canvas id="time-chart"></canvas>
              </div>
            </div>

            <div class="olap-chart-card">
              <div class="olap-chart-header">
                <div class="olap-chart-title">
                  <span class="olap-chart-badge payouts"></span>
                  Distribution by Group
                </div>
                <button onclick="exportChart('group-chart', 'group-distribution')" class="olap-small-btn" title="Download PNG">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                  <span>PNG</span>
                </button>
              </div>
              <div class="olap-chart-container" style="height: 230px;">
                <canvas id="group-chart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Member Drill-Down -->
        <div id="view-members">
          <div class="olap-table-card">
            <div class="olap-table-header">
              <div class="olap-table-title">Top Members — Click any row for details</div>
              <div class="olap-table-actions">
                <div class="olap-search">
                  <svg class="olap-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                  <input id="member-search" type="text" placeholder="Search..." 
                         class="olap-search-input" 
                         onkeyup="filterMembersTable()">
                </div>
                <div class="olap-btn-group">
                  <button onclick="exportTableCSV()" class="olap-btn-export">CSV</button>
                  <button onclick="exportTablePDF()" class="olap-btn-export">PDF</button>
                  <button onclick="exportTableExcel()" class="olap-btn-export">Excel</button>
                </div>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full data-table" id="members-table">
                <thead>
                  <tr>
                    <th>MEMBER</th>
                    <th>PRIMARY GROUP</th>
                    <th style="text-align:right">CONTRIBUTED</th>
                    <th style="text-align:right">PAYOUTS RECEIVED</th>
                    <th style="text-align:right">TXS</th>
                  </tr>
                </thead>
                <tbody id="members-tbody">
                  <!-- JS populated -->
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 5. Refined Bottom Alert Notice Bar -->
        <div class="olap-info">
          <span class="olap-info-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="#E15225" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2C8.13 2 5 5.13 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.87-3.13-7-7-7zm1 14h-2v-1h2v1zm-1-3.14c-1.86-.7-3-2.5-3-4.36 0-2.21 1.79-4 4-4s4 1.79 4 4c0 1.86-1.14 3.66-3 4.36z"/>
            </svg>
          </span>
          <span>Use the category tabs above to switch between Overview, Filters, Charts, and Member Drill-Down views. All OLAP operations query the data warehouse in real time.</span>
        </div>

        <!-- Sync results at the very bottom -->
        <div id="sync-results" style="display:none; margin: 20px 0 0; background:#fff; border:1px solid #E4DDD4; border-radius:8px; padding:12px; font-size:12px; font-family: ui-monospace, monospace; white-space:pre-wrap; max-height:300px; overflow:auto; color:#374151; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"></div>

      </div>
    </div>
  </div>

  <script>
    let currentData = <?= json_encode($initial_data, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
    let currentFilters = {
      group_id: <?= (int)$group_id ?>,
      year: <?= (int)$year ?>,
      trans_type: '<?= htmlspecialchars($trans_type) ?>'
    };

    let timeChart, groupChart;
    let isLoading = false;

    function showLoading(show) {
      const main = document.querySelector('.page-content');
      let overlay = document.getElementById('loading-overlay');

      if (show) {
        if (!overlay) {
          overlay = document.createElement('div');
          overlay.id = 'loading-overlay';
          overlay.className = 'loading-overlay';
          overlay.innerHTML = `<div class="text-sm text-gray-600 flex items-center gap-2">
            <span class="animate-spin">⟳</span> Updating analytics...
          </div>`;
          main.style.position = 'relative';
          main.appendChild(overlay);
        }
      } else if (overlay) {
        overlay.remove();
      }
    }

    function updateActiveFilters() {
      const el = document.getElementById('active-filters');
      if (!el) return; // element is optional in current layout
      const parts = [];

      if (currentFilters.group_id > 0) {
        const groupSelect = document.getElementById('filter-group');
        const groupName = groupSelect && groupSelect.options[groupSelect.selectedIndex]
          ? groupSelect.options[groupSelect.selectedIndex].text : 'Selected Group';
        parts.push(`Group: ${groupName}`);
      }
      const yearLabel = (currentFilters.year > 0) ? currentFilters.year : 'All Years';
      parts.push(`Year: ${yearLabel}`);
      if (currentFilters.trans_type !== 'all') {
        parts.push(currentFilters.trans_type);
      }

      el.textContent = parts.join(' • ');
    }

    function initCharts(data) {
      if (timeChart) timeChart.destroy();
      if (groupChart) groupChart.destroy();

      const timeLabels = (data.time_series || []).map(r => `${r.month_name || ''} ${r.year || ''}`.trim());
      const contribs = (data.time_series || []).map(r => parseFloat(r.contributions || 0));
      const payouts = (data.time_series || []).map(r => parseFloat(r.payouts || 0));

      const timeCtx = document.getElementById('time-chart');
      if (timeCtx) {
        timeChart = new Chart(timeCtx, {
          type: 'line',
          data: {
            labels: timeLabels,
            datasets: [
              { label: 'Contributions', data: contribs, borderColor: '#166534', backgroundColor: 'rgba(16, 185, 129, 0.1)', tension: 0.35, fill: true },
              { label: 'Payouts', data: payouts, borderColor: '#d97706', backgroundColor: 'rgba(245, 158, 11, 0.1)', tension: 0.35, fill: true }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
            scales: { y: { beginAtZero: true } }
          }
        });
      }

      const groupLabels = (data.by_group || []).map(r => r.group_name);
      const groupValues = (data.by_group || []).map(r => parseFloat(r.contributions || 0) + parseFloat(r.payouts || 0));

      const groupCtx = document.getElementById('group-chart');
      if (groupCtx) {
        groupChart = new Chart(groupCtx, {
          type: 'doughnut',
          data: {
            labels: groupLabels,
            datasets: [{
              data: groupValues,
              backgroundColor: ['#166534', '#d97706', '#1e40af', '#854d0e', '#4c1d95', '#9f1239', '#0f766e', '#334155']
            }]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 10 } } } }
        });
      }
    }

    function applyFilters() {
      if (isLoading) return;
      isLoading = true;
      showLoading(true);

      const group = document.getElementById('filter-group').value;
      const yr = document.getElementById('filter-year').value;
      const typ = document.getElementById('filter-type').value;

      currentFilters.group_id = parseInt(group) || 0;
      currentFilters.year = parseInt(yr) || 0;
      currentFilters.trans_type = typ || 'all';

      const params = new URLSearchParams();
      if (currentFilters.group_id > 0) params.set('group_id', currentFilters.group_id);
      if (currentFilters.year > 0) params.set('year', currentFilters.year);
      if (currentFilters.trans_type && currentFilters.trans_type !== 'all') params.set('trans_type', currentFilters.trans_type);

      fetch(`../../api/olap_data.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
          currentData = data;
          updateKPIs(data.summary);
          updateMembersTable(data.by_member);
          initCharts(data);
          updateActiveFilters();
        })
        .catch(err => {
          console.error(err);
          showCustomModal('Error', 'Failed to load analytics data. Please try again.');
        })
        .finally(() => {
          isLoading = false;
          showLoading(false);
        });
    }

    function resetFilters() {
      const fg = document.getElementById('filter-group');
      const fy = document.getElementById('filter-year');
      const ft = document.getElementById('filter-type');
      if (fg) fg.value = '0';
      if (fy) fy.value = '0';
      if (ft) ft.value = 'all';

      currentFilters = { group_id: 0, year: 0, trans_type: 'all' };
      applyFilters();
    }

    function updateKPIs(summary) {
      const s = summary || {};
      const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
      };
      set('kpi-contributions', '₱' + (parseFloat(s.total_contributions) || 0).toLocaleString(undefined, {minimumFractionDigits:2}));
      set('kpi-payouts', '₱' + (parseFloat(s.total_payouts) || 0).toLocaleString(undefined, {minimumFractionDigits:2}));
      set('kpi-tx', (s.total_transactions || 0).toLocaleString());
      const net = (parseFloat(s.total_contributions) || 0) - (parseFloat(s.total_payouts) || 0);
      set('kpi-net', '₱' + net.toLocaleString(undefined, {minimumFractionDigits:2}));
    }

    function updateMembersTable(members) {
      const tbody = document.getElementById('members-tbody');
      if (!tbody) return;
      tbody.innerHTML = '';

      if (!members || members.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="padding: 32px 16px; text-align:center; color:#6B6560; font-size:13px;">No member data for current filters.</td></tr>`;
        return;
      }

      members.forEach(m => {
        const tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.style.transition = 'background .1s ease';
        tr.onmouseover = () => tr.style.background = '#F9F6F1';
        tr.onmouseout = () => tr.style.background = '';
        tr.innerHTML = `
          <td style="padding:12px 16px; font-weight:600;">${m.full_name || ''}</td>
          <td style="padding:12px 16px; font-size:12px; color:#6B6560;">${m.group_name || ''}</td>
          <td style="padding:12px 16px; text-align:right; font-weight:600; color:#2D7A45;">₱${parseFloat(m.contributed || 0).toFixed(2)}</td>
          <td style="padding:12px 16px; text-align:right; color:#1A1A1A;">₱${parseFloat(m.received || 0).toFixed(2)}</td>
          <td style="padding:12px 16px; text-align:right; font-size:12px; color:#6B6560;">${m.tx_count || 0}</td>
        `;
        tr.onclick = () => drillIntoMember(m);
        tbody.appendChild(tr);
      });
    }

    function filterMembersTable() {
      const termEl = document.getElementById('member-search');
      const term = termEl ? termEl.value.toLowerCase() : '';
      const rows = document.querySelectorAll('#members-tbody tr');

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
      });
    }

    function doRollUp(level) {
      const yearSelect = document.getElementById('filter-year');
      if (yearSelect) {
        if (level === 'year') {
          // Roll-up to overall: select All Years
          yearSelect.value = '0';
        } else {
          // Quarter: for now just re-apply (or could set a specific if supported)
          // Keep current or set to latest if wanted
        }
      }
      applyFilters();
    }

    function doDrillDown() {
      // Drill-down to monthly/member level: re-apply current filters to ensure fresh data,
      // then scroll to the detailed Member Drill-Down table (more granular than aggregate charts).
      applyFilters();
      const members = document.getElementById('view-members');
      if (members) {
        members.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    // Designed drill-down content (restored if a simple showCustomModal overwrote the body)
    const DRILL_DOWN_HTML = `
      <!-- Member Identity -->
      <div class="drill-hero">
        <div class="drill-avatar" id="drill-avatar">M</div>
        <div>
          <div class="drill-hero-name" id="drill-member-name">Member</div>
          <div class="drill-hero-sub">Individual contribution &amp; payout profile</div>
        </div>
      </div>

      <!-- Active Filters -->
      <div>
        <div class="drill-section-title">Active Filters</div>
        <div class="drill-chips">
          <span class="drill-chip primary" id="drill-target-chip">Target: Member</span>
          <span class="drill-chip">Group: All</span>
          <span class="drill-chip">Period: All Years</span>
        </div>
      </div>

      <!-- Quick Summary Metrics -->
      <div>
        <div class="drill-section-title">Quick Summary</div>
        <div class="drill-metrics">
          <div class="drill-metric">
            <svg class="drill-metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            <div class="drill-metric-label">Total Contributed</div>
            <div id="drill-value-contributed" class="drill-metric-value accent">₱0.00</div>
          </div>
          <div class="drill-metric">
            <svg class="drill-metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>
            <div class="drill-metric-label">Payouts Received</div>
            <div id="drill-value-received" class="drill-metric-value">₱0.00</div>
          </div>
          <div class="drill-metric">
            <svg class="drill-metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <div class="drill-metric-label">Transactions</div>
            <div id="drill-value-txs" class="drill-metric-value">0</div>
          </div>
        </div>
      </div>

      <!-- Activity -->
      <div>
        <div class="drill-section-title">Sample Activity</div>
        <div class="drill-table-wrap">
          <table class="drill-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Group</th>
                <th>Type</th>
                <th class="right">Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="drill-history-body">
              <!-- populated dynamically per member -->
            </tbody>
          </table>
        </div>
      </div>
    `;

    function generateSampleActivityRows(member) {
      const contrib = parseFloat(member.contributed || 0);
      const recv = parseFloat(member.received || 0);
      const group = member.group_name || 'Group';
      const rows = [];

      const fmt = (n) => '₱' + n.toFixed(2);

      if (contrib > 0) {
        // Split into 2-3 plausible contribution rows
        const part1 = Math.max(50, Math.round(contrib * 0.35 * 100) / 100);
        const part2 = Math.max(50, Math.round((contrib - part1) * 0.6 * 100) / 100);
        const part3 = Math.max(0, Math.round((contrib - part1 - part2) * 100) / 100);

        rows.push(`<tr>
          <td style="font-weight:600; color:#1A1A1A;">2026-01-12</td>
          <td>${group}</td>
          <td><span style="color:#2D7A45; font-weight:600;">Contribution</span></td>
          <td class="right"><span class="drill-amount positive">${fmt(part1)}</span></td>
          <td><span class="drill-status verified">Verified</span></td>
        </tr>`);

        if (part2 > 0) {
          rows.push(`<tr>
            <td style="font-weight:600; color:#1A1A1A;">2026-02-05</td>
            <td>${group}</td>
            <td><span style="color:#2D7A45; font-weight:600;">Contribution</span></td>
            <td class="right"><span class="drill-amount positive">${fmt(part2)}</span></td>
            <td><span class="drill-status verified">Verified</span></td>
          </tr>`);
        }

        if (part3 > 10) {
          rows.push(`<tr>
            <td style="font-weight:600; color:#1A1A1A;">2026-03-18</td>
            <td>${group}</td>
            <td><span style="color:#2D7A45; font-weight:600;">Contribution</span></td>
            <td class="right"><span class="drill-amount positive">${fmt(part3)}</span></td>
            <td><span class="drill-status verified">Verified</span></td>
          </tr>`);
        }
      }

      if (recv > 0) {
        rows.push(`<tr>
          <td style="font-weight:600; color:#1A1A1A;">2026-04-02</td>
          <td>${group}</td>
          <td><span style="color:#B45309; font-weight:600;">Payout</span></td>
          <td class="right"><span class="drill-amount">${fmt(recv)}</span></td>
          <td><span class="drill-status verified">Verified</span></td>
        </tr>`);
      }

      if (rows.length === 0) {
        rows.push(`<tr><td colspan="5" style="padding:14px 12px; color:#6B6560; font-size:12px;">No activity recorded for the selected filters.</td></tr>`);
      }

      return rows.join('');
    }

    function populateDrillDown(member) {
      const m = member || {};
      const displayName = m.full_name || 'Member';
      const contrib = parseFloat(m.contributed || 0);
      const recv = parseFloat(m.received || 0);
      const txs = parseInt(m.tx_count || 0, 10);

      // Name / header / chip / avatar (reuse existing logic)
      const headerSpan = document.getElementById('custom-modal-title');
      if (headerSpan) headerSpan.textContent = `Drill-Down — ${displayName}`;

      const targetChip = document.getElementById('drill-target-chip');
      if (targetChip) targetChip.textContent = `Target: ${displayName}`;

      const memberNameLabel = document.getElementById('drill-member-name');
      if (memberNameLabel) memberNameLabel.textContent = displayName;

      const avatar = document.getElementById('drill-avatar');
      if (avatar) {
        const parts = displayName.trim().split(/\s+/);
        let initial = displayName.charAt(0).toUpperCase();
        if (parts.length >= 2) initial = (parts[0].charAt(0) + parts[parts.length-1].charAt(0)).toUpperCase();
        avatar.textContent = initial;
      }

      // Real stats
      const vContrib = document.getElementById('drill-value-contributed');
      if (vContrib) vContrib.textContent = '₱' + contrib.toFixed(2);

      const vRecv = document.getElementById('drill-value-received');
      if (vRecv) vRecv.textContent = '₱' + recv.toFixed(2);

      const vTxs = document.getElementById('drill-value-txs');
      if (vTxs) vTxs.textContent = txs;

      // Sample (but varying) activity rows based on this member's actual totals
      const histBody = document.getElementById('drill-history-body');
      if (histBody) {
        histBody.innerHTML = generateSampleActivityRows(m);
      }
    }

    function drillIntoMember(memberOrName) {
      const modal = document.getElementById('custom-modal');
      const bodyEl = document.getElementById('custom-modal-body');
      if (!modal || !bodyEl) return;

      // If a previous simple modal call replaced the content, restore the designed drill-down UI
      if (!bodyEl.querySelector('.drill-hero')) {
        bodyEl.innerHTML = DRILL_DOWN_HTML;
      }

      let member = memberOrName;
      if (!member || typeof member === 'string') {
        // Fallback for any legacy string calls
        member = { full_name: memberOrName || 'Member', contributed: 0, received: 0, tx_count: 0, group_name: 'All' };
      }

      populateDrillDown(member);
      modal.style.display = 'flex';
    }

    function exportChart(chartId, filename) {
      const canvas = document.getElementById(chartId);
      if (!canvas) return;
      const link = document.createElement('a');
      link.download = `olap-${filename}-${new Date().toISOString().slice(0,10)}.png`;
      link.href = canvas.toDataURL('image/png');
      link.click();
    }

    function exportTableCSV() {
      const table = document.getElementById('members-table');
      if (!table) return;
      let csv = [];
      for (let row of table.rows) {
        let cols = [];
        for (let cell of row.cells) {
          cols.push('"' + cell.innerText.trim().replace(/"/g, '""') + '"');
        }
        csv.push(cols.join(','));
      }
      const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `olap-members-${new Date().toISOString().slice(0,10)}.csv`;
      a.click();
    }

    function exportTablePDF() {
      const w = window.open('', '_blank');
      const table = document.getElementById('members-table');
      if (!w || !table) return;

      const filterText = getFilterDescription();

      w.document.write(`
        <html>
          <head>
            <title>OLAP Member Report - ${new Date().toLocaleDateString()}</title>
            <style>
              body { font-family: system-ui, sans-serif; padding: 30px; }
              h1 { font-size: 20px; margin-bottom: 4px; }
              table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 13px; }
              th, td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
              th { background: #f3f4f6; }
              .meta { color: #6b7280; font-size: 12px; margin-bottom: 20px; }
            </style>
          </head>
          <body>
            <h1>OLAP Analytics — Top Members Report</h1>
            <div class="meta">
              Generated: ${new Date().toLocaleString()}<br>
              ${filterText}
            </div>
            ${table.outerHTML}
            <p style="margin-top: 40px; font-size: 11px; color: #6C7D8C; font-family: system-ui, sans-serif;">TrustFund OLAP Analytics</p>
          </body>
        </html>
      `);
      w.document.close();
      setTimeout(() => w.print(), 400);
    }

    function exportTableExcel() {
      const table = document.getElementById('members-table');
      if (!table) return;
      let rows = [];
      for (let row of table.rows) {
        let cols = [];
        for (let cell of row.cells) cols.push(cell.innerText.trim());
        rows.push(cols.join('\t'));
      }
      const blob = new Blob([rows.join('\n')], { type: 'application/vnd.ms-excel' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `olap-report-${new Date().toISOString().slice(0,10)}.xls`;
      a.click();
    }

    function getFilterDescription() {
      let groupLabel = 'All Groups';
      const groupSelect = document.getElementById('filter-group');
      if (groupSelect && currentFilters.group_id > 0) {
        const opt = groupSelect.options[groupSelect.selectedIndex];
        if (opt) groupLabel = opt.text;
      }
      const yearLabel = (currentFilters.year > 0) ? currentFilters.year : 'All Years';
      let typeLabel = currentFilters.trans_type || 'all';
      if (typeLabel === 'contribution') typeLabel = 'Contributions only';
      else if (typeLabel === 'payout') typeLabel = 'Payouts only';
      else typeLabel = 'All Transactions';

      return `Group: ${groupLabel} • Year: ${yearLabel} • Type: ${typeLabel}`;
    }

    function exportFullReport() {
      const w = window.open('', '_blank');
      if (!w) return;

      const now = new Date();
      const generated = now.toLocaleString();
      const filterText = getFilterDescription();

      const summary = currentData.summary || {};
      const timeSeries = currentData.time_series || [];
      const byGroup = currentData.by_group || [];
      const membersTable = document.getElementById('members-table');

      const totalContrib = parseFloat(summary.total_contributions || 0);
      const totalPayout = parseFloat(summary.total_payouts || 0);
      const totalTx = summary.total_transactions || 0;
      const net = totalContrib - totalPayout;

      // Build Time Series table rows
      let timeRows = '';
      if (timeSeries.length > 0) {
        timeSeries.forEach(row => {
          const period = `${row.month_name || ''} ${row.year || ''}`.trim();
          const c = parseFloat(row.contributions || 0);
          const p = parseFloat(row.payouts || 0);
          const n = c - p;
          timeRows += `<tr>
            <td>${period}</td>
            <td style="text-align:right">₱${c.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td style="text-align:right">₱${p.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td style="text-align:right">₱${n.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
          </tr>`;
        });
      } else {
        timeRows = `<tr><td colspan="4" style="text-align:center; color:#666;">No time series data for current filters.</td></tr>`;
      }

      // Build Group table rows
      let groupRows = '';
      if (byGroup.length > 0) {
        byGroup.forEach(g => {
          const c = parseFloat(g.contributions || 0);
          const p = parseFloat(g.payouts || 0);
          const t = c + p;
          groupRows += `<tr>
            <td>${g.group_name || '—'}</td>
            <td style="text-align:right">₱${c.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td style="text-align:right">₱${p.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
            <td style="text-align:right">₱${t.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
          </tr>`;
        });
      } else {
        groupRows = `<tr><td colspan="4" style="text-align:center; color:#666;">No group breakdown data for current filters.</td></tr>`;
      }

      const membersHtml = (membersTable && membersTable.outerHTML) ? membersTable.outerHTML : '<p>No member data available.</p>';

      w.document.write(`
        <html>
          <head>
            <title>OLAP Analytics Full Report - ${now.toLocaleDateString()}</title>
            <meta charset="utf-8">
            <style>
              @media print {
                body { padding: 20px; }
                .section { page-break-inside: avoid; }
                .no-print { display: none; }
              }
              body {
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                padding: 30px 40px;
                color: #1A1A1A;
                line-height: 1.45;
              }
              .header {
                border-bottom: 3px solid #E8481A;
                padding-bottom: 12px;
                margin-bottom: 20px;
              }
              h1 { font-size: 24px; margin: 0 0 4px 0; color: #1A1A1A; }
              .subtitle {
                color: #6B6560;
                font-size: 13px;
              }
              .meta {
                background: #F9F6F1;
                border: 1px solid #E4DDD4;
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 12px;
                color: #6B6560;
                margin-bottom: 24px;
              }
              h2 {
                font-size: 15px;
                margin: 24px 0 10px;
                color: #1A1A1A;
                border-bottom: 1px solid #E4DDD4;
                padding-bottom: 6px;
              }
              .kpi-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
                margin-bottom: 24px;
              }
              .kpi-card {
                background: #fff;
                border: 1.5px solid #E4DDD4;
                border-radius: 10px;
                padding: 12px 14px;
              }
              .kpi-label {
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #6B6560;
                margin-bottom: 4px;
              }
              .kpi-value {
                font-size: 20px;
                font-weight: 700;
                color: #1A1A1A;
              }
              .kpi-value.net { color: #E8481A; }
              table {
                border-collapse: collapse;
                width: 100%;
                font-size: 12.5px;
                margin-bottom: 22px;
              }
              th, td {
                border: 1px solid #D1D5DB;
                padding: 8px 10px;
                text-align: left;
              }
              th {
                background: #F4EFEA;
                font-weight: 600;
                color: #6B6560;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.3px;
              }
              .section { margin-bottom: 12px; }
              .footer-note {
                margin-top: 30px;
                font-size: 11px;
                color: #6B6560;
                border-top: 1px solid #E4DDD4;
                padding-top: 12px;
              }
              .print-hint {
                font-size: 11px;
                color: #9E9790;
                margin-bottom: 12px;
              }
            </style>
          </head>
          <body>
            <div class="header">
              <h1>OLAP Analytics — Full Report</h1>
              <div class="subtitle">TrustFund Data Warehouse • Generated ${generated}</div>
            </div>

            <div class="meta">
              <strong>Applied Filters:</strong> ${filterText}<br>
              <strong>Data Source:</strong> trustfund_olap (real-time view)
            </div>

            <div class="section">
              <h2>Summary (KPIs)</h2>
              <div class="kpi-grid">
                <div class="kpi-card">
                  <div class="kpi-label">Total Contributions</div>
                  <div class="kpi-value">₱${totalContrib.toLocaleString(undefined, {minimumFractionDigits:2})}</div>
                </div>
                <div class="kpi-card">
                  <div class="kpi-label">Total Payouts</div>
                  <div class="kpi-value">₱${totalPayout.toLocaleString(undefined, {minimumFractionDigits:2})}</div>
                </div>
                <div class="kpi-card">
                  <div class="kpi-label">Transactions</div>
                  <div class="kpi-value">${totalTx.toLocaleString()}</div>
                </div>
                <div class="kpi-card">
                  <div class="kpi-label">Net (Contrib - Payout)</div>
                  <div class="kpi-value net">₱${net.toLocaleString(undefined, {minimumFractionDigits:2})}</div>
                </div>
              </div>
            </div>

            <div class="section">
              <h2>Contributions &amp; Payouts Over Time</h2>
              <table>
                <thead>
                  <tr>
                    <th>Period</th>
                    <th style="text-align:right">Contributions</th>
                    <th style="text-align:right">Payouts</th>
                    <th style="text-align:right">Net</th>
                  </tr>
                </thead>
                <tbody>
                  ${timeRows}
                </tbody>
              </table>
            </div>

            <div class="section">
              <h2>Distribution by Group</h2>
              <table>
                <thead>
                  <tr>
                    <th>Group</th>
                    <th style="text-align:right">Contributions</th>
                    <th style="text-align:right">Payouts</th>
                    <th style="text-align:right">Total Activity</th>
                  </tr>
                </thead>
                <tbody>
                  ${groupRows}
                </tbody>
              </table>
            </div>

            <div class="section">
              <h2>Top Members (by contribution)</h2>
              ${membersHtml}
            </div>

            <div class="footer-note">
              Report generated from current OLAP Analytics view.<br>
              Use your browser's Print → Save as PDF to create a permanent copy.
            </div>
          </body>
        </html>
      `);

      w.document.close();
      setTimeout(() => w.print(), 500);
    }

    let isSyncing = false;

    function triggerOlapSync(full = false) {
      if (isSyncing) return;
      isSyncing = true;

      const btns = document.querySelectorAll('.olap-sync-btn');
      btns.forEach(b => b.disabled = true);

      const resultsEl = document.getElementById('sync-results');
      resultsEl.style.display = 'block';
      resultsEl.textContent = 'Starting OLAP sync' + (full ? ' (FULL RELOAD)...\n' : ' (incremental)...\n');

      const url = '../../api/olap_sync.php' + (full ? '?full=1' : '');

      fetch(url)
        .then(r => r.json())
        .then(data => {
          if (data.output) {
            resultsEl.textContent += data.output + '\n';
          }
          if (data.success) {
            resultsEl.textContent += '\n✅ Sync completed successfully.\n';
            resultsEl.textContent += (data.message || '') + '\n\n';
            resultsEl.textContent += 'Tip: Click "Refresh Data" or change a filter to reload the analytics view with new data.';
            showCustomModal('OLAP Sync', 'Sync completed successfully.\n\nTip: Click "Refresh Data" or change a filter to reload the analytics view with new data.');
          } else {
            resultsEl.textContent += '\n❌ Sync failed.\n' + (data.error || '');
          }
        })
        .catch(err => {
          resultsEl.textContent += '\n❌ Request error: ' + err.message;
        })
        .finally(() => {
          isSyncing = false;
          btns.forEach(b => b.disabled = false);
        });
    }

    function init() {
      initCharts(currentData);
      updateKPIs(currentData.summary);
      updateMembersTable(currentData.by_member || []);
      updateActiveFilters();

      // Live filter on change
      ['filter-group', 'filter-year', 'filter-type'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', applyFilters);
      });
    }

    function setActiveTimeBtn(btn) {
      // Highlight the clicked quick OLAP operation button (works across all sections now)
      document.querySelectorAll('.olap-pill-btn').forEach(function(b) {
        b.classList.remove('active');
      });
      if (btn) btn.classList.add('active');
    }

    // Custom modal functions (replaces browser alert/confirm to avoid "localhost says" prefix)
    function showCustomModal(title, message) {
      const modal = document.getElementById('custom-modal');
      const titleEl = document.getElementById('custom-modal-title');
      const bodyEl = document.getElementById('custom-modal-body');
      if (!modal || !titleEl || !bodyEl) {
        alert(title + '\n\n' + message);
        return;
      }
      titleEl.textContent = title;
      // Simple designed message body (no Tailwind)
      bodyEl.innerHTML = `
        <div style="padding:8px 4px 4px; font-size:14.5px; line-height:1.65; color:#374151;">
          ${message.replace(/\n/g, '<br>')}
        </div>
      `;
      modal.style.display = 'flex';
    }

    function hideCustomModal() {
      const modal = document.getElementById('custom-modal');
      if (modal) modal.style.display = 'none';
    }

    // Close modal when clicking outside content
    window.addEventListener('click', function(event) {
      const modal = document.getElementById('custom-modal');
      if (modal && event.target === modal) {
        modal.style.display = 'none';
      }
    });

    window.onload = function() {
      init();
    };
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

  <!-- Custom Modal Window (replaces native "localhost says" alerts) -->
  <div id="custom-modal" class="custom-modal">
    <div class="custom-modal-content">
      <div class="custom-modal-header">
        <span id="custom-modal-title">Member Drill-Down</span>
        <button class="custom-modal-close" onclick="hideCustomModal()">&times;</button>
      </div>
      <div id="custom-modal-body" class="custom-modal-body" style="padding: 24px 28px;">
        <!-- Member Identity -->
        <div class="drill-hero">
          <div class="drill-avatar" id="drill-avatar">M</div>
          <div>
            <div class="drill-hero-name" id="drill-member-name">Member</div>
            <div class="drill-hero-sub">Individual contribution &amp; payout profile</div>
          </div>
        </div>

        <!-- Active Filters -->
        <div>
          <div class="drill-section-title">Active Filters</div>
          <div class="drill-chips">
            <span class="drill-chip primary" id="drill-target-chip">Target: Member</span>
            <span class="drill-chip">Group: All</span>
            <span class="drill-chip">Period: All Years</span>
          </div>
        </div>

        <!-- Quick Summary Metrics -->
        <div>
          <div class="drill-section-title">Quick Summary</div>
          <div class="drill-metrics">
            <div class="drill-metric">
              <svg class="drill-metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
              <div class="drill-metric-label">Total Contributed</div>
              <div id="drill-value-contributed" class="drill-metric-value accent">₱0.00</div>
            </div>
            <div class="drill-metric">
              <svg class="drill-metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>
              <div class="drill-metric-label">Payouts Received</div>
              <div id="drill-value-received" class="drill-metric-value">₱0.00</div>
            </div>
            <div class="drill-metric">
              <svg class="drill-metric-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              <div class="drill-metric-label">Transactions</div>
              <div id="drill-value-txs" class="drill-metric-value">0</div>
            </div>
          </div>
        </div>

        <!-- Sample Activity -->
        <div>
          <div class="drill-section-title">Sample Activity</div>
          <div class="drill-table-wrap">
            <table class="drill-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Group</th>
                  <th>Type</th>
                  <th class="right">Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="drill-history-body">
                <!-- populated dynamically per member -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="custom-modal-footer" style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
        <button class="custom-modal-btn" onclick="hideCustomModal()">Close</button>
      </div>
    </div>
  </div>

  <script src="../../../front-end/js/notifications.js"></script>
</body>
</html>
