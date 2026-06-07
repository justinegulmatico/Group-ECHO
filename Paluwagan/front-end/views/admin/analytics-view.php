<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrustFund • OLAP Analytics Dashboard</title>
    
    <!-- Project CSS (keeps consistent look with the rest of the admin panel) -->
    <link rel="stylesheet" href="../../../assets/css/global.css">
    <link rel="stylesheet" href="../../../assets/css/admin-panel.css">
    
    <!-- Chart.js via CDN - the only charting library we use (very student-friendly) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jsPDF for PDF export (no server library needed - pure client side) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <style>
        /* ================================================
           CLEAN, STUDENT-FRIENDLY STYLES FOR ANALYTICS
           ================================================ */
        :root {
            --primary: #166534;
            --accent: #E15225;
        }
        
        body {
            background: #F8F5F1;
        }
        
        .analytics-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px 36px;
        }
        
        /* Header */
        .analytics-header {
            background: linear-gradient(135deg, #1f2937, #111827);
            color: white;
            border-radius: 16px;
            padding: 28px 36px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .analytics-header h1 {
            font-size: 29px;
            font-weight: 700;
            margin: 0;
        }
        
        .analytics-header p {
            margin: 6px 0 0;
            opacity: 0.75;
            font-size: 14px;
        }
        
        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .kpi-card {
            background: white;
            border: 1px solid #E5E0D8;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .kpi-label {
            font-size: 13px;
            color: #6B6560;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .kpi-value {
            font-size: 30px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.1;
        }
        
        .kpi-value.green { color: #166534; }
        .kpi-value.orange { color: #E15225; }
        
        /* Filter Section */
        .filters-card {
            background: white;
            border: 1px solid #E5E0D8;
            border-radius: 12px;
            padding: 22px 24px;
            margin-bottom: 24px;
        }
        
        .filters-title {
            font-weight: 600;
            margin-bottom: 14px;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }
        
        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #6B6560;
            margin-bottom: 6px;
        }
        
        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #D1C9BE;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .filter-group select:focus {
            outline: none;
            border-color: #166534;
        }
        
        /* Time Granularity (Roll-up / Drill-down) */
        .granularity-buttons {
            display: flex;
            gap: 6px;
            margin-top: 4px;
        }
        
        .granularity-btn {
            padding: 9px 18px;
            border: 1.5px solid #D1C9BE;
            background: white;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .granularity-btn.active {
            background: #166534;
            color: white;
            border-color: #166534;
        }
        
        .granularity-btn:hover:not(.active) {
            background: #F5F0E8;
        }
        
        /* Chart Cards */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .chart-card {
            background: white;
            border: 1px solid #E5E0D8;
            border-radius: 12px;
            padding: 22px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        
        .chart-title {
            font-weight: 600;
            color: #374151;
            font-size: 15.5px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        /* Export Section */
        .export-section {
            background: white;
            border: 1px solid #E5E0D8;
            border-radius: 12px;
            padding: 22px 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }
        
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .export-btn.csv {
            background: #166534;
            color: white;
        }
        
        .export-btn.pdf {
            background: #E15225;
            color: white;
        }
        
        .export-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        

        
        /* Loading state */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .analytics-container {
                padding: 16px 20px;
            }
        }

        /* Use even more width on large screens */
        @media (min-width: 1400px) {
            .analytics-container {
                max-width: 1700px;
                padding: 20px 48px;
            }
        }

        @media (min-width: 1800px) {
            .analytics-container {
                max-width: 1850px;
                padding: 24px 64px;
            }
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../components/sidebar-view.php'; ?>
        
        <div class="main-content">
            <div class="analytics-container">
                
                <!-- ==================== HEADER ==================== -->
                <div class="analytics-header">
                    <div>
                        <h1>📊 OLAP Analytics Dashboard</h1>
                        <p>Interactive Slice, Dice, Roll-up &amp; Drill-down on the TrustFund Data Warehouse</p>
                    </div>
                </div>

                <!-- ==================== KPI SUMMARY CARDS ==================== -->
                <div class="kpi-grid" id="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-label">Total Contributions</div>
                        <div class="kpi-value green" id="kpi-contributions">₱<?= number_format($initial_summary['total_contributions'] ?? 0, 2) ?></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">Total Payouts</div>
                        <div class="kpi-value orange" id="kpi-payouts">₱<?= number_format($initial_summary['total_payouts'] ?? 0, 2) ?></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">Transactions Analyzed</div>
                        <div class="kpi-value" id="kpi-transactions"><?= number_format($initial_summary['total_transactions'] ?? 0) ?></div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">Active Groups in View</div>
                        <div class="kpi-value" id="kpi-groups">—</div>
                    </div>
                </div>

                <!-- ==================== FILTERS (SLICE + DICE + ROLL-UP) ==================== -->
                <div class="filters-card">
                    <div class="filters-title">
                        🔍 Analysis Filters 
                        <span style="font-size:11px; font-weight:400; color:#9CA3AF;">(Changes update charts automatically via AJAX)</span>
                    </div>
                    
                    <div class="filters-grid">
                        
                        <!-- Year Filter -->
                        <div class="filter-group">
                            <label>Year (Time Slice)</label>
                            <select id="filter-year" onchange="applyFilters()">
                                <option value="0">All Years</option>
                                <?php foreach ($available_years as $y): ?>
                                    <option value="<?= (int)$y ?>" <?= $initial_year == $y ? 'selected' : '' ?>>
                                        <?= (int)$y ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Quarter Filter -->
                        <div class="filter-group">
                            <label>Quarter (Dice Dimension)</label>
                            <select id="filter-quarter" onchange="applyFilters()">
                                <option value="0">All Quarters</option>
                                <option value="1">Q1 (Jan–Mar)</option>
                                <option value="2">Q2 (Apr–Jun)</option>
                                <option value="3">Q3 (Jul–Sep)</option>
                                <option value="4">Q4 (Oct–Dec)</option>
                            </select>
                        </div>
                        
                        <!-- Group Filter (Classic Slice) -->
                        <div class="filter-group">
                            <label>Group (Slice by Group)</label>
                            <select id="filter-group" onchange="applyFilters()">
                                <option value="0">All Groups</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= (int)$g['group_key'] ?>" 
                                        <?= $initial_group_key == $g['group_key'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['group_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Transaction Type -->
                        <div class="filter-group">
                            <label>Transaction Type</label>
                            <select id="filter-type" onchange="applyFilters()">
                                <option value="all">All Transactions</option>
                                <option value="contribution">Contributions Only</option>
                                <option value="payout">Payouts Only</option>
                            </select>
                        </div>
                        
                    </div>
                    
                    <!-- TIME GRANULARITY = ROLL-UP / DRILL-DOWN CONTROL -->
                    <div style="margin-top: 18px;">
                        <label style="font-size:12px; font-weight:600; color:#6B6560; display:block; margin-bottom:6px;">
                            Time Granularity (Roll-up ↔ Drill-down)
                        </label>
                        <div class="granularity-buttons">
                            <button type="button" class="granularity-btn" data-level="year" onclick="setTimeLevel('year')">Year (Roll-up)</button>
                            <button type="button" class="granularity-btn" data-level="quarter" onclick="setTimeLevel('quarter')">Quarter</button>
                            <button type="button" class="granularity-btn active" data-level="month" onclick="setTimeLevel('month')">Month (Drill-down)</button>
                        </div>
                        <small style="color:#9CA3AF; font-size:11px;">Higher level = more summarized (Roll-up). Lower level = more detailed (Drill-down).</small>
                    </div>
                    
                </div>

                <!-- ==================== THE THREE CHARTS ==================== -->
                <div class="charts-grid">
                    
                    <!-- CHART 1: BAR - Contributions per Group -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">📊 Contributions by Group (Bar)</div>
                            <button onclick="downloadChartImage('barChart', 'contributions-by-group')" 
                                    style="font-size:11px; padding:4px 8px; border-radius:6px; border:1px solid #D1C9BE; background:white; cursor:pointer;">
                                PNG
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                        <div style="font-size:11px; color:#6B6560; margin-top:8px;">
                            Slice by selecting a single group above to focus the bar chart.
                        </div>
                    </div>
                    
                    <!-- CHART 2: LINE - Trends over Time -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">📈 Trends Over Time (Line)</div>
                            <button onclick="downloadChartImage('lineChart', 'time-trends')" 
                                    style="font-size:11px; padding:4px 8px; border-radius:6px; border:1px solid #D1C9BE; background:white; cursor:pointer;">
                                PNG
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="lineChart"></canvas>
                        </div>
                        <div style="font-size:11px; color:#6B6560; margin-top:8px;">
                            Change <strong>Time Granularity</strong> above to see Roll-up vs Drill-down in action.
                        </div>
                    </div>
                    
                    <!-- CHART 3: PIE - Payout Distribution -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">🥧 Payout Distribution (Pie)</div>
                            <button onclick="downloadChartImage('pieChart', 'payout-distribution')" 
                                    style="font-size:11px; padding:4px 8px; border-radius:6px; border:1px solid #D1C9BE; background:white; cursor:pointer;">
                                PNG
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas>
                        </div>
                        <div style="font-size:11px; color:#6B6560; margin-top:8px;">
                            Shows how total payouts are distributed across groups.
                        </div>
                    </div>
                    
                </div>

                <!-- ==================== EXPORT SECTION ==================== -->
                <div class="export-section">
                    <strong style="margin-right: 12px;">Export Current View:</strong>
                    
                    <button onclick="exportToCSV()" class="export-btn csv">
                        ⬇️ Download CSV
                    </button>
                    
                    <button onclick="exportToPDF()" class="export-btn pdf">
                        📄 Generate PDF Report
                    </button>
                    
                    <span style="margin-left: auto; font-size: 12px; color: #6B6560;">
                        Data updates live when you change any filter.
                    </span>
                </div>
                
                <!-- Educational note for the professor / panel -->
                <div style="margin-top: 20px; font-size: 12px; color: #6B6560; background: #F5F0E8; padding: 12px 16px; border-radius: 8px;">
                    <strong>Student Note (for defense):</strong> 
                    All filtering uses the separate <code>trustfund_olap</code> data warehouse. 
                    The API demonstrates <strong>Slice</strong> (one group), <strong>Dice</strong> (multiple dimensions), 
                    and <strong>Roll-up/Drill-down</strong> by changing the GROUP BY level dynamically.
                </div>
                
            </div>
        </div>
    </div>

    <!-- ==================== MAIN JAVASCRIPT (Vanilla JS - No jQuery) ==================== -->
    <script>
        // =====================================================
        // GLOBAL VARIABLES
        // =====================================================
        let barChart, lineChart, pieChart;
        let currentData = null;           // Stores the last data received from the API
        let currentFilters = {
            year: <?= (int)$initial_year ?>,
            quarter: 0,
            group_key: <?= (int)$initial_group_key ?>,
            time_level: '<?= htmlspecialchars($initial_time_level) ?>',
            trans_type: 'all'
        };

        // =====================================================
        // INITIALIZE EVERYTHING WHEN PAGE LOADS
        // =====================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial active button for time granularity
            setActiveGranularityButton('<?= htmlspecialchars($initial_time_level) ?>');
            
            // Load initial data from the API (AJAX)
            // We call the API even on first load so the page is always consistent
            fetchAnalyticsData();
            
            // STUDENT TIP: You can also pre-load data from PHP if you want,
            // but calling the API makes the page fully dynamic.
        });

        // =====================================================
        // SET TIME GRANULARITY (ROLL-UP / DRILL-DOWN)
        // =====================================================
        function setTimeLevel(level) {
            currentFilters.time_level = level;
            setActiveGranularityButton(level);
            fetchAnalyticsData();   // Immediately refresh charts
        }
        
        function setActiveGranularityButton(level) {
            document.querySelectorAll('.granularity-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.level === level) {
                    btn.classList.add('active');
                }
            });
        }

        // =====================================================
        // COLLECT ALL CURRENT FILTER VALUES FROM THE PAGE
        // =====================================================
        function getCurrentFiltersFromUI() {
            return {
                year: parseInt(document.getElementById('filter-year').value) || 0,
                quarter: parseInt(document.getElementById('filter-quarter').value) || 0,
                group_key: parseInt(document.getElementById('filter-group').value) || 0,
                time_level: currentFilters.time_level,
                trans_type: document.getElementById('filter-type').value
            };
        }

        // =====================================================
        // MAIN FUNCTION: FETCH DATA FROM THE API (AJAX)
        // =====================================================
        async function fetchAnalyticsData() {
            const filters = getCurrentFiltersFromUI();
            currentFilters = filters;   // Save for exports
            
            // Show loading effect on KPI cards
            const kpiContainer = document.getElementById('kpi-grid');
            kpiContainer.classList.add('loading');
            
            // Build the query string
            const params = new URLSearchParams({
                year: filters.year,
                quarter: filters.quarter,
                group_key: filters.group_key,
                time_level: filters.time_level,
                trans_type: filters.trans_type
            });
            
            try {
                // This is the AJAX call to our clean API
                const response = await fetch(`../../api/analytics_data.php?${params.toString()}`);
                const result = await response.json();
                
                if (!result.success) {
                    alert('Error loading analytics: ' + (result.error || 'Unknown error'));
                    return;
                }
                
                currentData = result;           // Store for CSV/PDF export
                updateKPICards(result.summary);
                updateAllCharts(result);
                
                // Optional: log the OLAP operations (great for showing during demo)
                console.log('%c[OLAP Operations]', 'color:#166534', result.olap_operations);
                
            } catch (error) {
                console.error('Analytics fetch failed:', error);
                alert('Failed to load analytics data. Check browser console and make sure the OLAP database has data.');
            } finally {
                kpiContainer.classList.remove('loading');
            }
        }

        // Called automatically when any dropdown changes
        function applyFilters() {
            fetchAnalyticsData();
        }

        // =====================================================
        // UPDATE THE FOUR KPI CARDS
        // =====================================================
        function updateKPICards(summary) {
            if (!summary) return;
            
            document.getElementById('kpi-contributions').textContent = 
                '₱' + parseFloat(summary.total_contributions || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});
            
            document.getElementById('kpi-payouts').textContent = 
                '₱' + parseFloat(summary.total_payouts || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});
            
            document.getElementById('kpi-transactions').textContent = 
                parseInt(summary.total_transactions || 0).toLocaleString();
            
            // We don't have "groups_involved" in every response, so we estimate from by_group length
            const groupsCount = (currentData && currentData.by_group) ? currentData.by_group.length : '—';
            document.getElementById('kpi-groups').textContent = groupsCount;
        }

        // =====================================================
        // UPDATE ALL THREE CHARTS
        // =====================================================
        function updateAllCharts(data) {
            updateBarChart(data.by_group || []);
            updateLineChart(data.time_series || []);
            updatePieChart(data.payout_distribution || data.by_group || []);
        }

        // ---------- CHART 1: BAR CHART (Contributions per Group) ----------
        function updateBarChart(groupData) {
            const ctx = document.getElementById('barChart');
            
            if (barChart) barChart.destroy();
            
            const labels = groupData.map(g => g.group_name);
            const contributions = groupData.map(g => parseFloat(g.total_contributions || 0));
            
            barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Contributions (₱)',
                        data: contributions,
                        backgroundColor: '#166534',
                        borderColor: '#14532d',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } }
                    }
                }
            });
        }

        // ---------- CHART 2: LINE CHART (Trends over Time) ----------
        function updateLineChart(timeData) {
            const ctx = document.getElementById('lineChart');
            
            if (lineChart) lineChart.destroy();
            
            const labels = timeData.map(t => t.period_label);
            const contributions = timeData.map(t => parseFloat(t.contributions || 0));
            const payouts = timeData.map(t => parseFloat(t.payouts || 0));
            
            lineChart = new Chart(ctx, {
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
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => '₱' + v.toLocaleString() } }
                    }
                }
            });
        }

        // ---------- CHART 3: PIE CHART (Payout Distribution) ----------
        function updatePieChart(payoutData) {
            const ctx = document.getElementById('pieChart');
            
            if (pieChart) pieChart.destroy();
            
            // Use payout numbers if available, otherwise fall back to contribution as size
            const labels = payoutData.map(g => g.group_name);
            const values = payoutData.map(g => parseFloat(g.total_payouts || g.total_contributions || 0));
            
            const colors = ['#166534', '#E15225', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899', '#14B8A6', '#F43F5E'];
            
            pieChart = new Chart(ctx, {
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
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        }

        // =====================================================
        // EXPORT FUNCTIONS (CSV + PDF)
        // =====================================================
        
        // Simple CSV export using current chart data
        function exportToCSV() {
            if (!currentData) {
                alert('Please wait for data to load first.');
                return;
            }
            
            let csvContent = "data:text/csv;charset=utf-8,";
            
            // Summary row
            csvContent += "Metric,Value\n";
            csvContent += `Total Contributions,${currentData.summary.total_contributions}\n`;
            csvContent += `Total Payouts,${currentData.summary.total_payouts}\n`;
            csvContent += `Transactions,${currentData.summary.total_transactions}\n\n`;
            
            // Group data
            csvContent += "Group,Contributions,Payouts,Transactions\n";
            currentData.by_group.forEach(row => {
                csvContent += `"${row.group_name}",${row.total_contributions},${row.total_payouts},${row.transaction_count}\n`;
            });
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `trustfund_analytics_${new Date().toISOString().slice(0,10)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // PDF Report using jsPDF (very easy for students)
        async function exportToPDF() {
            if (!currentData) {
                alert('Please wait for data to load first.');
                return;
            }
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Title
            doc.setFontSize(18);
            doc.text("TrustFund Paluwagan - OLAP Analytics Report", 20, 20);
            
            doc.setFontSize(11);
            doc.text(`Generated: ${new Date().toLocaleString()}`, 20, 28);
            doc.text(`Filters: Year=${currentFilters.year || 'All'} | Quarter=${currentFilters.quarter || 'All'} | Time Level=${currentFilters.time_level}`, 20, 34);
            
            // Summary section
            doc.setFontSize(13);
            doc.text("Key Performance Indicators", 20, 46);
            
            doc.autoTable({
                startY: 52,
                head: [['Metric', 'Value']],
                body: [
                    ['Total Contributions', '₱' + parseFloat(currentData.summary.total_contributions).toLocaleString()],
                    ['Total Payouts', '₱' + parseFloat(currentData.summary.total_payouts).toLocaleString()],
                    ['Transactions', currentData.summary.total_transactions],
                ],
                theme: 'grid',
                headStyles: { fillColor: [22, 101, 52] }
            });
            
            // Group breakdown table
            let finalY = doc.lastAutoTable.finalY + 10;
            doc.text("Contributions & Payouts by Group", 20, finalY);
            
            const tableData = currentData.by_group.map(g => [
                g.group_name,
                '₱' + parseFloat(g.total_contributions).toLocaleString(),
                '₱' + parseFloat(g.total_payouts).toLocaleString(),
                g.transaction_count
            ]);
            
            doc.autoTable({
                startY: finalY + 6,
                head: [['Group', 'Contributions', 'Payouts', 'Tx Count']],
                body: tableData,
                theme: 'striped',
                headStyles: { fillColor: [225, 82, 37] }
            });
            
            // Footer note
            const footerY = doc.lastAutoTable.finalY + 15;
            doc.setFontSize(9);
            doc.text("This report was generated from the TrustFund OLAP data warehouse using dynamic Slice/Dice/Roll-up queries.", 20, footerY);
            
            doc.save(`TrustFund_Analytics_Report_${new Date().toISOString().slice(0,10)}.pdf`);
        }
        
        // Bonus: Download individual chart as PNG
        function downloadChartImage(chartVarName, filename) {
            let chartInstance;
            if (chartVarName === 'barChart') chartInstance = barChart;
            if (chartVarName === 'lineChart') chartInstance = lineChart;
            if (chartVarName === 'pieChart') chartInstance = pieChart;
            
            if (!chartInstance) return;
            
            const link = document.createElement('a');
            link.download = `${filename}.png`;
            link.href = chartInstance.toBase64Image();
            link.click();
        }
        
        // =====================================================
        // KEYBOARD SHORTCUT (nice touch for demo)
        // =====================================================
        document.addEventListener('keydown', function(e) {
            if (e.key === '?' && document.activeElement.tagName === 'BODY') {
                e.preventDefault();
                document.getElementById('filter-group').focus();
            }
        });
        
        console.log('%c[Analytics] Student-friendly OLAP dashboard loaded. All interactive logic is in this file + analytics_data.php', 'color:#6B6560');
    </script>
</body>
</html>