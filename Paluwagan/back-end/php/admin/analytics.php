<?php
/**
 * admin/analytics.php
 * OLAP Analytics Dashboard - Admin
 * 
 * This file follows the exact same structure and styling as other admin pages
 * (index.php and transactions.php) for consistency.
 * 
 * - Uses the same topbar, sidebar, admin-hero, stat-cards, and page-content layout.
 * - Fetches initial dropdown data and a default summary from the OLAP warehouse.
 * - The interactive filtering, charts, and updates are handled by vanilla JS + AJAX.
 */

session_start();

// Include OLAP connection (separate from main OLTP db.php)
require_once "../../olap_db.php";

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

$olap = OlapDatabase::getInstance()->getPdo();

// ============================================
// INITIAL DATA FOR DROPDOWNS (populated once on page load)
// ============================================

// Get all groups for the Group filter (from dim_group in OLAP)
$groups_stmt = $olap->query("SELECT group_key, group_name FROM dim_group ORDER BY group_name ASC");
$groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available years directly from the OLAP fact data (most reliable for "what years do I actually have?").
// We prefer YEAR(created_at) on the facts themselves so the filter reflects real data in the warehouse
// (e.g. 2026 transactions will appear even if dim_time join has issues or time_key fallback was used during ETL).
// Falls back to the dim_time join if created_at is not populated.
try {
    $years_stmt = $olap->query("
        SELECT DISTINCT YEAR(created_at) as y
        FROM fact_transactions
        WHERE created_at IS NOT NULL
        ORDER BY y DESC
    ");
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $available_years = [];
}

if (empty($available_years)) {
    // Fallback: derive from the time dimension rows that are actually referenced by facts
    try {
        $years_stmt = $olap->query("
            SELECT DISTINCT dt.year 
            FROM fact_transactions ft
            JOIN dim_time dt ON ft.time_key = dt.time_key
            ORDER BY dt.year DESC
        ");
        $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        $available_years = [];
    }
}

if (empty($available_years)) {
    // Still nothing → just show the "All Years" option (no year-specific slices available)
    $available_years = [];
}

// Last ETL sync timestamp (shown in header)
$lastSyncTimestamp = null;
try {
    $lsStmt = $olap->prepare("SELECT last_sync_timestamp FROM etl_control ORDER BY last_sync_timestamp DESC LIMIT 1");
    $lsStmt->execute();
    $row = $lsStmt->fetch(PDO::FETCH_ASSOC);
    $lastSyncTimestamp = $row['last_sync_timestamp'] ?? null;
} catch (Throwable $e) {
    // etl_control table may not exist yet (no sync run)
    $lastSyncTimestamp = null;
}

// Default summary for initial render (no filters)
$initial_summary_stmt = $olap->query("
    SELECT 
        COALESCE(SUM(amount_contribution), 0) AS total_contributions,
        COALESCE(SUM(amount_payout), 0) AS total_payouts,
        COUNT(*) AS total_transactions,
        COUNT(DISTINCT group_key) AS active_groups
    FROM fact_transactions
");
$initial_summary = $initial_summary_stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_contributions' => 0,
    'total_payouts' => 0,
    'total_transactions' => 0,
    'active_groups' => 0
];

// Include the full view (HTML + JS). We keep everything in one file for student simplicity
// while matching the controller pattern of other admin pages.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — OLAP Analytics | Admin</title>

  <!-- Project CSS (exact same as other admin pages) -->
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-analytics.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-analytics.css') ?>" />

  <!-- Chart.js via CDN (student-friendly, no build tools) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- jsPDF + autoTable for PDF export (student-friendly, CDN only) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

  <!-- Tailwind via CDN for additional clean components (keeps modern feel while matching project) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>
  <div class="app-layout">
    <!-- Sidebar - exactly the same include used in other admin pages -->
    <?php include __DIR__ . '/../../../front-end/views/components/sidebar-view.php'; ?>

    <div class="main-content">
      <!-- Topbar - matches index.php and transactions.php exactly -->
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin → OLAP Analytics</span>
        </div>
        <div class="topbar-right" style="display:flex; align-items:center; gap:10px;">
          <?php if ($lastSyncTimestamp): ?>
            <span id="last-sync-text" class="analytics-last-sync">
              Last ETL: <strong><?= htmlspecialchars(date('M j, H:i', strtotime($lastSyncTimestamp))) ?></strong>
            </span>
          <?php else: ?>
            <span id="last-sync-text" class="analytics-last-sync">No ETL sync yet</span>
          <?php endif; ?>

          <button type="button" id="btn-sync" onclick="triggerETLSync(false)"
                  class="btn-outline analytics-sync-btn">
            <i class="fas fa-sync-alt"></i>
            <span>ETL Sync</span>
          </button>

          <button type="button" id="btn-full-sync" onclick="triggerETLSync(true)"
                  class="btn-outline analytics-sync-btn full">
            Full Sync
          </button>
        </div>
      </header>

      <div class="page-content">

        <!-- Sync result banner (populated by JS when ETL Sync / Full Sync is used) -->
        <div id="sync-result"></div>

        <!-- Admin Hero Header - matches style in transactions.php and index.php -->
        <div class="admin-hero" style="margin-bottom: 20px; padding: 20px 24px;">
          <div>
            <div class="admin-hero-title analytics-hero-title">OLAP Analytics Dashboard</div>
            <div class="admin-hero-sub">
              Slice, Dice, Roll-up and Drill-down analysis on the TrustFund data warehouse.
            </div>
          </div>
        </div>

        <!-- 1. TOP SUMMARY CARDS - Using the exact stat-cards system from other admin pages -->
        <div class="stat-cards analytics-stat-cards">
          <div class="stat-card">
            <div class="stat-card-label">Total Contributions</div>
            <div class="stat-card-value green" id="stat-contributions">₱<?= number_format($initial_summary['total_contributions'], 2) ?></div>
            <div class="stat-card-sub">All time</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Total Payouts</div>
            <div class="stat-card-value" style="color: #E15225;" id="stat-payouts">₱<?= number_format($initial_summary['total_payouts'], 2) ?></div>
            <div class="stat-card-sub">All time</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Transactions Analyzed</div>
            <div class="stat-card-value" id="stat-transactions"><?= number_format($initial_summary['total_transactions']) ?></div>
            <div class="stat-card-sub">in current view</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Active Groups</div>
            <div class="stat-card-value" id="stat-groups"><?= number_format($initial_summary['active_groups']) ?></div>
            <div class="stat-card-sub">in current view</div>
          </div>
        </div>

        <!-- 2. ANALYSIS FILTERS CARD -->
        <div class="analysis-card">
          <div class="section-title">
            <i class="fas fa-filter mr-2"></i> Analysis Filters
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Year -->
            <div>
              <label class="input-label analytics-label">Year</label>
              <select id="filter-year" class="input-field" onchange="applyFilters()">
                <option value="0">All Years</option>
                <?php foreach ($available_years as $y): ?>
                  <option value="<?= (int)$y ?>"><?= (int)$y ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Quarter -->
            <div>
              <label class="input-label analytics-label">Quarter</label>
              <select id="filter-quarter" class="input-field" onchange="applyFilters()">
                <option value="0">All Quarters</option>
                <option value="1">Q1 (Jan - Mar)</option>
                <option value="2">Q2 (Apr - Jun)</option>
                <option value="3">Q3 (Jul - Sep)</option>
                <option value="4">Q4 (Oct - Dec)</option>
              </select>
            </div>

            <!-- Group (Slice) -->
            <div>
              <label class="input-label analytics-label">Group (Slice)</label>
              <select id="filter-group" class="input-field" onchange="applyFilters()">
                <option value="0">All Groups</option>
                <?php foreach ($groups as $g): ?>
                  <option value="<?= (int)$g['group_key'] ?>"><?= htmlspecialchars($g['group_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Transaction Type (Dice) -->
            <div>
              <label class="input-label analytics-label">Transaction Type</label>
              <select id="filter-type" class="input-field" onchange="applyFilters()">
                <option value="all">All Transactions</option>
                <option value="contribution">Contributions Only</option>
                <option value="payout">Payouts Only</option>
              </select>
            </div>
          </div>

          <!-- Time Granularity (Roll-up / Drill-down) -->
          <div style="margin-top: 16px;">
            <label class="input-label analytics-granularity-label">Time Granularity (Roll-up ↔ Drill-down)</label>
            <div class="flex flex-wrap gap-2">
              <button type="button" class="granularity-btn" data-level="year" onclick="setGranularity('year')">Year (Roll-up)</button>
              <button type="button" class="granularity-btn" data-level="quarter" onclick="setGranularity('quarter')">Quarter</button>
              <button type="button" class="granularity-btn active" data-level="month" onclick="setGranularity('month')">Month (Drill-down)</button>
            </div>
            <div class="analytics-granularity-hint">
              Higher level = more summarized data. Lower level = more detailed data.
            </div>
          </div>
        </div>

        <!-- 3. THREE INTERACTIVE CHARTS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
          
          <!-- Bar Chart: Contributions by Group -->
          <div class="chart-card">
            <div class="chart-title">
              <div class="chart-title-icon">
                <i class="fas fa-chart-bar text-[#166534]"></i>
                <span>Contributions by Group (Bar)</span>
              </div>
              <button onclick="downloadChartPNG('barChart', 'contributions-by-group')" 
                      class="chart-png-btn" title="Download this chart as PNG">
                <i class="fas fa-download"></i>
                <span>PNG</span>
              </button>
            </div>
            <div class="chart-canvas-container">
              <canvas id="barChart"></canvas>
            </div>
          </div>

          <!-- Line Chart: Trends Over Time -->
          <div class="chart-card">
            <div class="chart-title">
              <div class="chart-title-icon">
                <i class="fas fa-chart-line text-[#166534]"></i>
                <span>Trends Over Time (Line)</span>
              </div>
              <button onclick="downloadChartPNG('lineChart', 'trends-over-time')" 
                      class="chart-png-btn" title="Download this chart as PNG">
                <i class="fas fa-download"></i>
                <span>PNG</span>
              </button>
            </div>
            <div class="chart-canvas-container">
              <canvas id="lineChart"></canvas>
            </div>
          </div>

          <!-- Pie Chart: Payout Distribution -->
          <div class="chart-card lg:col-span-2">
            <div class="chart-title">
              <div class="chart-title-icon">
                <i class="fas fa-chart-pie text-[#E15225]"></i>
                <span>Payout Distribution (Pie)</span>
              </div>
              <button onclick="downloadChartPNG('pieChart', 'payout-distribution')" 
                      class="chart-png-btn" title="Download this chart as PNG">
                <i class="fas fa-download"></i>
                <span>PNG</span>
              </button>
            </div>
            <div class="chart-canvas-container pie">
              <canvas id="pieChart"></canvas>
            </div>
          </div>

        </div>

        <!-- 4. EXPORT SECTION -->
        <div class="analysis-card">
          <div class="section-title" style="margin-bottom: 10px;">
            <i class="fas fa-download mr-2"></i> Export Current View
          </div>
          <div class="flex flex-wrap gap-3">
            <button onclick="exportCSV()" class="analytics-export-btn csv">
              <i class="fas fa-file-csv"></i> Download CSV
            </button>
            <button onclick="exportPDF()" class="analytics-export-btn pdf">
              <i class="fas fa-file-pdf"></i> Generate PDF Report
            </button>
          </div>
          <div class="analytics-export-note">
            Exports use the currently filtered data shown in the charts and summary cards.
          </div>
        </div>

        <!-- Student-friendly note -->
        <div class="analytics-tip">
          <strong>Tip for your defense:</strong> The API uses dynamic <strong>WHERE</strong> clauses for Slice (single group) and Dice (multiple dimensions), 
          while the <strong>time_level</strong> parameter controls the <strong>GROUP BY</strong> for Roll-up / Drill-down.
        </div>

      </div>
    </div>
  </div>

  <script>
    // ============================================
    // STUDENT-FRIENDLY JAVASCRIPT (Vanilla only)
    // ============================================

    let barChartInstance = null;
    let lineChartInstance = null;
    let pieChartInstance = null;

    // Store the last successful data response so we can export it
    let lastData = null;

    let currentFilters = {
      year: 0,
      quarter: 0,
      group_key: 0,
      trans_type: 'all',
      time_level: 'month'
    };

    // Tailwind script config (optional polish)
    function initTailwind() {
      if (typeof tailwind !== 'undefined') {
        tailwind.config = {
          theme: {
            extend: {
              fontFamily: {
                'body': ['var(--font-body)']
              }
            }
          }
        };
      }
    }

    // Set active state on granularity buttons
    function setGranularity(level) {
      currentFilters.time_level = level;

      // Update button active states
      document.querySelectorAll('.granularity-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.level === level) {
          btn.classList.add('active');
        }
      });

      applyFilters();
    }

    // Collect current filter values from the UI
    function getFiltersFromUI() {
      return {
        year: parseInt(document.getElementById('filter-year').value) || 0,
        quarter: parseInt(document.getElementById('filter-quarter').value) || 0,
        group_key: parseInt(document.getElementById('filter-group').value) || 0,
        trans_type: document.getElementById('filter-type').value,
        time_level: currentFilters.time_level
      };
    }

    // Main function: Fetch data from API and update UI
    async function loadAnalyticsData(filters) {
      const params = new URLSearchParams(filters);
      
      try {
        // Call our dedicated analytics API (relative path from admin/analytics.php)
        const res = await fetch(`../../api/analytics_data.php?${params.toString()}`);
        const data = await res.json();

        if (!data.success) {
          console.error('API error:', data.error);
          return;
        }

        // Store the full response for PDF/CSV export
        lastData = data;

        // Update everything
        updateSummaryCards(data.summary);
        updateBarChart(data.by_group || []);
        updateLineChart(data.time_series || []);
        updatePieChart(data.by_group || []);

        // Store current filters for export
        currentFilters = filters;

      } catch (err) {
        console.error('Failed to load analytics data:', err);
      }
    }

    // Update the 4 summary cards
    function updateSummaryCards(summary) {
      if (!summary) return;

      document.getElementById('stat-contributions').textContent = 
        '₱' + parseFloat(summary.total_contributions || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });

      document.getElementById('stat-payouts').textContent = 
        '₱' + parseFloat(summary.total_payouts || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });

      document.getElementById('stat-transactions').textContent = 
        parseInt(summary.total_transactions || 0).toLocaleString();

      document.getElementById('stat-groups').textContent = 
        parseInt(summary.active_groups || 0).toLocaleString();
    }

    // Chart 1: Bar - Contributions by Group
    function updateBarChart(groupData) {
      const ctx = document.getElementById('barChart');
      if (barChartInstance) barChartInstance.destroy();

      const labels = groupData.map(item => item.group_name);
      const values = groupData.map(item => parseFloat(item.total_contributions || 0));

      barChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Contributions (₱)',
            data: values,
            backgroundColor: '#166534',
            borderColor: '#14532d',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { callback: (val) => '₱' + val.toLocaleString() } }
          }
        }
      });
    }

    // Chart 2: Line - Trends Over Time
    function updateLineChart(timeData) {
      const ctx = document.getElementById('lineChart');
      if (lineChartInstance) lineChartInstance.destroy();

      const labels = timeData.map(item => item.period_label);
      const contributions = timeData.map(item => parseFloat(item.contributions || 0));
      const payouts = timeData.map(item => parseFloat(item.payouts || 0));

      lineChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Contributions',
              data: contributions,
              borderColor: '#166534',
              backgroundColor: 'rgba(22, 101, 52, 0.1)',
              tension: 0.3,
              fill: true
            },
            {
              label: 'Payouts',
              data: payouts,
              borderColor: '#E15225',
              backgroundColor: 'rgba(225, 82, 37, 0.1)',
              tension: 0.3,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'top' } },
          scales: {
            y: { beginAtZero: true, ticks: { callback: (val) => '₱' + val.toLocaleString() } }
          }
        }
      });
    }

    // Chart 3: Pie - Payout Distribution
    function updatePieChart(groupData) {
      const ctx = document.getElementById('pieChart');
      if (pieChartInstance) pieChartInstance.destroy();

      const labels = groupData.map(item => item.group_name);
      const values = groupData.map(item => parseFloat(item.total_payouts || 0));

      const colors = ['#166534', '#E15225', '#3B82F6', '#F59E0B', '#8B5CF6', '#14B8A6'];

      pieChartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [{
            data: values,
            backgroundColor: colors.slice(0, labels.length),
            borderWidth: 2,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'right' } }
        }
      });
    }

    // Called whenever any filter or granularity changes
    function applyFilters() {
      const newFilters = getFiltersFromUI();
      loadAnalyticsData(newFilters);
    }

    // Download individual chart as high-quality PNG (uses Chart.js toBase64Image when available)
    function downloadChartPNG(canvasId, filename) {
      const canvas = document.getElementById(canvasId);
      if (!canvas) {
        console.warn('Chart canvas not found:', canvasId);
        return;
      }

      // Prefer the official Chart.js instance method (cleanest output, respects current state)
      let dataUrl = null;
      try {
        if (typeof Chart !== 'undefined' && typeof Chart.getChart === 'function') {
          const chart = Chart.getChart(canvas);
          if (chart && typeof chart.toBase64Image === 'function') {
            dataUrl = chart.toBase64Image('image/png', 1); // 1 = no compression
          }
        }
      } catch (e) {
        console.warn('Chart.getChart failed, using canvas fallback', e);
      }

      // Fallback to raw canvas (still works even if Chart.js instance is lost)
      if (!dataUrl) {
        dataUrl = canvas.toDataURL('image/png');
      }

      const link = document.createElement('a');
      link.download = filename + '.png';
      link.href = dataUrl;
      link.click();
    }

    // Reusable: get PNG data URL for a chart canvas (used by PDF export + download buttons)
    function getChartImageData(canvasId) {
      const canvas = document.getElementById(canvasId);
      if (!canvas) return null;

      try {
        if (typeof Chart !== 'undefined' && typeof Chart.getChart === 'function') {
          const chart = Chart.getChart(canvas);
          if (chart && typeof chart.toBase64Image === 'function') {
            return chart.toBase64Image('image/png', 1); // high quality
          }
        }
      } catch (e) {
        console.warn('Chart.getChart failed for', canvasId, e);
      }
      // Fallback
      try {
        return canvas.toDataURL('image/png');
      } catch (e) {
        return null;
      }
    }

    // Export current data as CSV (simple client-side generation)
    function exportCSV() {
      if (!lastData) {
        alert('No data available to export yet. Please wait for the charts to load.');
        return;
      }

      let csv = [];

      // Header row with metadata
      csv.push('TrustFund OLAP Analytics Export');
      csv.push('Generated,' + new Date().toLocaleString());
      csv.push('Time Granularity,' + currentFilters.time_level);
      csv.push('Year,' + (currentFilters.year || 'All'));
      csv.push('Quarter,' + (currentFilters.quarter || 'All'));
      csv.push('Group Key,' + (currentFilters.group_key || 'All'));
      csv.push('Transaction Type,' + currentFilters.trans_type);
      csv.push('');

      // Summary section
      csv.push('SUMMARY');
      csv.push('Metric,Value');
      csv.push('Total Contributions,' + (lastData.summary.total_contributions || 0));
      csv.push('Total Payouts,' + (lastData.summary.total_payouts || 0));
      csv.push('Transactions Analyzed,' + (lastData.summary.total_transactions || 0));
      csv.push('Active Groups,' + (lastData.summary.active_groups || 0));
      csv.push('');

      // Group performance table
      csv.push('GROUP PERFORMANCE');
      csv.push('Group,Total Contributions,Total Payouts');
      if (lastData.by_group && lastData.by_group.length > 0) {
        lastData.by_group.forEach(row => {
          csv.push(
            '"' + (row.group_name || '') + '",' +
            (row.total_contributions || 0) + ',' +
            (row.total_payouts || 0)
          );
        });
      } else {
        csv.push('No group data');
      }

      // Create and download the file
      const csvContent = csv.join('\n');
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.href = url;
      link.download = `TrustFund_OLAP_Analytics_${new Date().toISOString().slice(0,10)}.csv`;
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    // Helper to format numbers safely for PDF (avoid ₱ symbol which causes font issues in jsPDF)
    function formatCurrencyForPDF(amount) {
      const num = parseFloat(amount || 0);
      // Use clean "PHP " prefix + localized number (no special symbols)
      return 'PHP ' + num.toLocaleString('en-PH', { 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0 
      });
    }

    // Export as professional PDF using jsPDF + autoTable
    function exportPDF() {
      if (!lastData) {
        alert('No data available to export yet. Please wait for the charts to load.');
        return;
      }

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      // Title
      doc.setFontSize(18);
      doc.text('TrustFund - OLAP Analytics Report', 20, 20);

      // Metadata
      doc.setFontSize(11);
      doc.text('Generated: ' + new Date().toLocaleString(), 20, 28);
      doc.text('Time Granularity: ' + currentFilters.time_level.toUpperCase(), 20, 34);

      let filterText = 'Filters: ';
      if (currentFilters.year) filterText += 'Year=' + currentFilters.year + ' ';
      if (currentFilters.quarter) filterText += 'Q' + currentFilters.quarter + ' ';
      if (currentFilters.group_key) filterText += 'Group ID=' + currentFilters.group_key + ' ';
      if (currentFilters.trans_type !== 'all') filterText += 'Type=' + currentFilters.trans_type;
      if (filterText === 'Filters: ') filterText += 'All data';
      doc.text(filterText.trim(), 20, 40);

      // Summary Section
      doc.setFontSize(14);
      doc.text('Key Metrics', 20, 52);

      const summaryData = [
        ['Total Contributions', formatCurrencyForPDF(lastData.summary.total_contributions)],
        ['Total Payouts', formatCurrencyForPDF(lastData.summary.total_payouts)],
        ['Transactions Analyzed', (lastData.summary.total_transactions || 0).toLocaleString()],
        ['Active Groups in View', (lastData.summary.active_groups || 0).toLocaleString()]
      ];

      doc.autoTable({
        startY: 56,
        head: [['Metric', 'Value']],
        body: summaryData,
        theme: 'grid',
        headStyles: { fillColor: [22, 101, 52] }, // Green to match project
        styles: { fontSize: 11 }
      });

      // Group Performance Table
      let finalY = doc.lastAutoTable.finalY + 12;
      doc.setFontSize(14);
      doc.text('Group Performance (Current Filter)', 20, finalY);

      const groupTable = [];
      if (lastData.by_group && lastData.by_group.length > 0) {
        lastData.by_group.forEach(row => {
          groupTable.push([
            row.group_name || 'Unknown Group',
            formatCurrencyForPDF(row.total_contributions),
            formatCurrencyForPDF(row.total_payouts)
          ]);
        });
      } else {
        groupTable.push(['No data available for current filters', '', '']);
      }

      doc.autoTable({
        startY: finalY + 4,
        head: [['Group Name', 'Contributions (PHP)', 'Payouts (PHP)']],
        body: groupTable,
        theme: 'striped',
        headStyles: { fillColor: [225, 82, 37] }, // Orange to match project
        styles: { fontSize: 10 },
        columnStyles: {
          0: { halign: 'left' },
          1: { halign: 'right' },
          2: { halign: 'right' }
        },
        // Force right-align on the header cells for the two numeric columns too
        didParseCell: function (data) {
          if (data.section === 'head' && (data.column.index === 1 || data.column.index === 2)) {
            data.cell.styles.halign = 'right';
          }
        },
        // Prevent text overflow / character spacing issues
        margin: { right: 15 }
      });

      // ============================================
      // ANALYTICS CHARTS (embedded images)
      // ============================================
      let y = doc.lastAutoTable.finalY + 14;
      doc.setFontSize(14);
      doc.setTextColor(0);
      doc.text('Analytics Charts', 20, y);
      y += 7;

      const imgWidth = 170; // mm (fits well in A4 with margins)

      function addChartImage(canvasId, label, defaultHeight) {
        const dataUrl = getChartImageData(canvasId);
        if (!dataUrl) return y;

        // Page break if needed before this chart
        if (y > 230) {
          doc.addPage();
          y = 20;
        }

        doc.setFontSize(11);
        doc.text(label, 20, y);
        y += 3;

        // Try to preserve aspect ratio from the canvas backing store
        let imgHeight = defaultHeight;
        const canvasEl = document.getElementById(canvasId);
        if (canvasEl && canvasEl.width > 0 && canvasEl.height > 0) {
          const ratio = canvasEl.height / canvasEl.width;
          imgHeight = Math.max(35, Math.min(imgWidth * ratio, 95));
        }

        try {
          doc.addImage(dataUrl, 'PNG', 20, y, imgWidth, imgHeight);
        } catch (e) {
          console.warn('Failed to add chart image to PDF:', canvasId, e);
        }
        y += imgHeight + 8;
        return y;
      }

      // Add the three analytics charts
      y = addChartImage('barChart', 'Contributions by Group', 58);
      y = addChartImage('lineChart', 'Trends Over Time', 52);
      y = addChartImage('pieChart', 'Payout Distribution', 68);

      // Footer note (after charts)
      if (y > 240) {
        doc.addPage();
        y = 20;
      }
      doc.setFontSize(9);
      doc.setTextColor(100);
      doc.text('Report generated from TrustFund OLAP data warehouse.', 20, y);
      doc.text('Data reflects current filter selections (Slice / Dice / Roll-up). Charts captured from current view.', 20, y + 5);

      // Save the PDF
      const filename = `TrustFund_OLAP_Analytics_${new Date().toISOString().slice(0,10)}.pdf`;
      doc.save(filename);
    }

    // ============================================
    // ETL SYNC / FULL SYNC (header buttons)
    // ============================================
    async function triggerETLSync(isFull) {
      const btnSync = document.getElementById('btn-sync');
      const btnFull = document.getElementById('btn-full-sync');
      const resultEl = document.getElementById('sync-result');
      const lastSyncText = document.getElementById('last-sync-text');

      if (!btnSync || !btnFull) return;

      // Confirmation for full sync (can be heavy)
      if (isFull && !confirm('Full Sync will clear and reload ALL data from the beginning.\nThis can take significantly longer. Continue?')) {
        return;
      }

      // Disable buttons + show loading state
      const originalSyncHTML = btnSync.innerHTML;
      const originalFullHTML = btnFull.innerHTML;

      btnSync.disabled = true;
      btnFull.disabled = true;
      btnSync.style.opacity = '0.6';
      btnFull.style.opacity = '0.6';

      btnSync.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Syncing...</span>';
      if (isFull) {
        btnFull.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Full Sync...</span>';
      }

      // Show processing banner
      resultEl.style.display = 'flex';
      resultEl.style.background = '#fef3c7';
      resultEl.style.border = '1px solid #fde68a';
      resultEl.style.color = '#92400e';
      resultEl.innerHTML = isFull 
        ? '<i class="fas fa-spinner fa-spin"></i> <span>Running <strong>Full ETL Sync</strong> (this may take a while)...</span>'
        : '<i class="fas fa-spinner fa-spin"></i> <span>Running incremental ETL Sync...</span>';

      try {
        const url = `../../api/olap_sync.php${isFull ? '?full=1' : ''}`;
        const res = await fetch(url, { method: 'GET' });
        const data = await res.json();

        if (data.success) {
          // Success UI
          resultEl.style.background = '#dcfce7';
          resultEl.style.border = '1px solid #bbf7d0';
          resultEl.style.color = '#166534';
          resultEl.innerHTML = `<i class="fas fa-check-circle"></i> <span><strong>Sync complete!</strong> ${data.message || 'Data warehouse updated.'}</span>`;

          // Update last sync text in header (immediate feedback)
          if (lastSyncText) {
            lastSyncText.innerHTML = `Last ETL: <strong>just now</strong>`;
          }

          // Refresh the analytics charts with current filters so new data shows immediately
          try {
            const currentFilters = getFiltersFromUI();
            await loadAnalyticsData(currentFilters);
          } catch (e) {
            console.warn('Could not auto-refresh analytics after sync:', e);
          }

          // Hide the success banner after a few seconds
          setTimeout(() => {
            resultEl.style.display = 'none';
          }, 5500);

          // Optional: log full ETL output to console (great for demos/defense)
          if (data.output) {
            console.log('%c[ETL Sync Output]', 'color:#166534; font-weight:600', '\n' + data.output);
          }
        } else {
          resultEl.style.background = '#fee2e2';
          resultEl.style.border = '1px solid #fecaca';
          resultEl.style.color = '#991b1b';
          resultEl.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <span><strong>Sync failed:</strong> ${data.error || data.message || 'Unknown error'}</span>`;
          setTimeout(() => { resultEl.style.display = 'none'; }, 8000);
        }
      } catch (err) {
        console.error('ETL sync request failed:', err);
        resultEl.style.background = '#fee2e2';
        resultEl.style.border = '1px solid #fecaca';
        resultEl.style.color = '#991b1b';
        resultEl.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <span><strong>Network error:</strong> Could not reach the ETL sync endpoint.</span>`;
        setTimeout(() => { resultEl.style.display = 'none'; }, 8000);
      } finally {
        // Restore buttons
        btnSync.disabled = false;
        btnFull.disabled = false;
        btnSync.style.opacity = '1';
        btnFull.style.opacity = '1';
        btnSync.innerHTML = originalSyncHTML;
        btnFull.innerHTML = originalFullHTML;
      }
    }

    // Initial setup
    function initializeAnalytics() {
      initTailwind();

      // Load data on first page load (uses default "all" filters)
      const initialFilters = {
        year: 0,
        quarter: 0,
        group_key: 0,
        trans_type: 'all',
        time_level: 'month'
      };
      
      // Trigger initial data load (this populates charts + stores data for export)
      loadAnalyticsData(initialFilters);

      // Set initial active button
      document.querySelectorAll('.granularity-btn').forEach(btn => {
        if (btn.dataset.level === 'month') btn.classList.add('active');
      });

      // Optional: allow pressing Enter in selects to trigger (already handled by onchange)
    }

    // Boot the page
    document.addEventListener('DOMContentLoaded', initializeAnalytics);
  </script>
</body>
</html>
