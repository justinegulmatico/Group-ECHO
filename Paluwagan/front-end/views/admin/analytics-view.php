<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrustFund • OLAP Analytics Dashboard</title>

    <link rel="stylesheet" href="../../../assets/css/global.css">
    <link rel="stylesheet" href="../../../assets/css/admin-panel.css">
    <link rel="stylesheet" href="../../../assets/css/analytics-view.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../components/sidebar-view.php'; ?>
        
        <div class="main-content">
            <div class="analytics-container">
                
                <!--Header-->
                <div class="analytics-header">
                    <div>
                        <h1>📊 OLAP Analytics Dashboard</h1>
                        <p>Interactive Slice, Dice, Roll-up &amp; Drill-down on the TrustFund Data Warehouse</p>
                    </div>
                </div>

                <!--KPI Summary Cards-->
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

                <!--Filters (Slice + Dice + Roll-up)-->
                <div class="filters-card">
                    <div class="filters-title">
                        🔍 Analysis Filters 
                        <span class="filter-note">(Changes update charts automatically via AJAX)</span>
                    </div>
                    
                    <div class="filters-grid">
                        
                        <!--Year Filter-->
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
                        
                        <!--Quarter Filter-->
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
                        
                        <!--Group Filter-->
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
                        
                        <!--Transaction Type-->
                        <div class="filter-group">
                            <label>Transaction Type</label>
                            <select id="filter-type" onchange="applyFilters()">
                                <option value="all">All Transactions</option>
                                <option value="contribution">Contributions Only</option>
                                <option value="payout">Payouts Only</option>
                            </select>
                        </div>
                        
                    </div>
                    
                    <!--Time Granularity = Roll-up/ Drill-Down Control-->
                    <div class="granularity-section">
                        <label class="granularity-label">
                            Time Granularity (Roll-up ↔ Drill-down)
                        </label>
                        <div class="granularity-buttons">
                            <button type="button" class="granularity-btn" data-level="year" onclick="setTimeLevel('year')">Year (Roll-up)</button>
                            <button type="button" class="granularity-btn" data-level="quarter" onclick="setTimeLevel('quarter')">Quarter</button>
                            <button type="button" class="granularity-btn active" data-level="month" onclick="setTimeLevel('month')">Month (Drill-down)</button>
                        </div>
                        <small class="granularity-hint">Higher level = more summarized (Roll-up). Lower level = more detailed (Drill-down).</small>
                    </div>
                    
                </div>

                <!--The Three Charts-->
                <div class="charts-grid">
                    
                    <!--Chart 1: BAR - Contributions per Group-->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">📊 Contributions by Group (Bar)</div>
                            <button onclick="downloadChartImage('barChart', 'contributions-by-group')" 
                                    class="chart-png-btn">
                                PNG
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                        <div class="chart-help">
                            Slice by selecting a single group above to focus the bar chart.
                        </div>
                    </div>
                    
                    <!--Chart 2: Line - Trends over Time-->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">📈 Trends Over Time (Line)</div>
                            <button onclick="downloadChartImage('lineChart', 'time-trends')" 
                                    class="chart-png-btn">
                                PNG
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="lineChart"></canvas>
                        </div>
                        <div class="chart-help">
                            Change <strong>Time Granularity</strong> above to see Roll-up vs Drill-down in action.
                        </div>
                    </div>
                    
                    <!--Chart 3: PIE - Payout Distribution-->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">🥧 Payout Distribution (Pie)</div>
                            <button onclick="downloadChartImage('pieChart', 'payout-distribution')" 
                                    class="chart-png-btn">
                                PNG
                            </button>
                        </div>
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas>
                        </div>
                        <div class="chart-help">
                            Shows how total payouts are distributed across groups.
                        </div>
                    </div>
                    
                </div>

                <!--Export Section-->
                <div class="export-section">
                    <strong class="export-label">Export Current View:</strong>
                    
                    <button onclick="exportToCSV()" class="export-btn csv">
                        ⬇️ Download CSV
                    </button>
                    
                    <button onclick="exportToPDF()" class="export-btn pdf">
                        📄 Generate PDF Report
                    </button>
                    
                    <span class="export-note">
                        CSV includes full Summary + Group + Time Series data (Excel-friendly).
                    </span>
                </div>
                
                <div class="student-note">
                    <strong>Student Note (for defense):</strong> 
                    All filtering uses the separate <code>trustfund_olap</code> data warehouse. 
                    The API demonstrates <strong>Slice</strong> (one group), <strong>Dice</strong> (multiple dimensions), 
                    and <strong>Roll-up/Drill-down</strong> by changing the GROUP BY level dynamically.
                </div>
                
            </div>
        </div>
    </div>

    <script>
        window.analyticsConfig = {
            year: <?= (int)$initial_year ?>,
            group_key: <?= (int)$initial_group_key ?>,
            time_level: '<?= htmlspecialchars($initial_time_level) ?>'
        };
    </script>

    <!--External JS-->
    <script src="../../../assets/js/analytics-view.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/analytics-view.js') ?>"></script>
</body>
</html>
