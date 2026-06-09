// analytics-view.js - charts + filters for olap dash

// globals
let barChart, lineChart, pieChart;
let currentData = null; // last api data
let currentFilters = {
    year: (window.analyticsConfig && window.analyticsConfig.year) || 0,
    quarter: 0,
    group_key: (window.analyticsConfig && window.analyticsConfig.group_key) || 0,
    time_level: (window.analyticsConfig && window.analyticsConfig.time_level) || 'month',
    trans_type: 'all'
};

// INITIALIZE EVERYTHING WHEN PAGE LOADS
document.addEventListener('DOMContentLoaded', function() {
    // Set initial active button for time granularity
    const initialLevel = (window.analyticsConfig && window.analyticsConfig.time_level) || 'month';
    setActiveGranularityButton(initialLevel);
    
    // Load initial data from the API (AJAX)
    fetchAnalyticsData();
    
    // tip: could preload from php too,
    // but calling the API makes the page fully dynamic.
});

// SET TIME GRANULARITY (ROLL-UP / DRILL-DOWN)
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

// COLLECT ALL CURRENT FILTER VALUES FROM THE PAGE
function getCurrentFiltersFromUI() {
    return {
        year: parseInt(document.getElementById('filter-year').value) || 0,
        quarter: parseInt(document.getElementById('filter-quarter').value) || 0,
        group_key: parseInt(document.getElementById('filter-group').value) || 0,
        time_level: currentFilters.time_level,
        trans_type: document.getElementById('filter-type').value
    };
}

// MAIN FUNCTION: FETCH DATA FROM THE API (AJAX)
async function fetchAnalyticsData() {
    const filters = getCurrentFiltersFromUI();
    currentFilters = filters;   // Save for exports
    
    // Show loading effect on KPI cards
    const kpiContainer = document.getElementById('kpi-grid');
    if (kpiContainer) kpiContainer.classList.add('loading');
    
    // Build the query string
    const params = new URLSearchParams({
        year: filters.year,
        quarter: filters.quarter,
        group_key: filters.group_key,
        time_level: filters.time_level,
        trans_type: filters.trans_type
    });
    
    try {
        // ajax to our api
        const response = await fetch(`../../api/analytics_data.php?${params.toString()}`);
        const result = await response.json();
        
        if (!result.success) {
            alert('Error loading analytics: ' + (result.error || 'Unknown error'));
            return;
        }
        
        currentData = result;           // Store for CSV/PDF export
        updateKPICards(result.summary);
        updateAllCharts(result);
        
        // log olap ops (for demo) 
        console.log('%c[OLAP]', 'color:#166534', result.olap_operations);
        
    } catch (error) {
        console.error('Analytics fetch failed:', error);
        alert('Failed to load analytics data. Check browser console and make sure the OLAP database has data.');
    } finally {
        if (kpiContainer) kpiContainer.classList.remove('loading');
    }
}

// Called automatically when any dropdown changes
function applyFilters() {
    fetchAnalyticsData();
}

// UPDATE THE FOUR KPI CARDS
function updateKPICards(summary) {
    if (!summary) return;
    
    const contribEl = document.getElementById('kpi-contributions');
    const payoutsEl = document.getElementById('kpi-payouts');
    const txEl = document.getElementById('kpi-transactions');
    const groupsEl = document.getElementById('kpi-groups');
    
    if (contribEl) {
        contribEl.textContent = '₱' + parseFloat(summary.total_contributions || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});
    }
    if (payoutsEl) {
        payoutsEl.textContent = '₱' + parseFloat(summary.total_payouts || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});
    }
    if (txEl) {
        txEl.textContent = parseInt(summary.total_transactions || 0).toLocaleString();
    }
    
    // estimate from by_group len (no groups_involved in all responses)
    if (groupsEl) {
        const groupsCount = (currentData && currentData.by_group) ? currentData.by_group.length : '—';
        groupsEl.textContent = groupsCount;
    }
}

// UPDATE ALL THREE CHARTS
function updateAllCharts(data) {
    updateBarChart(data.by_group || []);
    updateLineChart(data.time_series || []);
    updatePieChart(data.payout_distribution || data.by_group || []);
}

// bar chart - contribs per group
function updateBarChart(groupData) {
    const ctx = document.getElementById('barChart');
    if (!ctx) return;
    
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

// line chart - trends over time
function updateLineChart(timeData) {
    const ctx = document.getElementById('lineChart');
    if (!ctx) return;
    
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

// pie - payout dist
function updatePieChart(payoutData) {
    const ctx = document.getElementById('pieChart');
    if (!ctx) return;
    
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

// ---
// EXPORT FUNCTIONS (CSV + PDF)
// ---

// Robust CSV export — includes all visible analytics data + proper Excel compatibility
function exportToCSV() {
    if (!currentData) {
        alert('Please wait for data to load first.');
        return;
    }

    const rows = [];

    // Header + filters (designed to look good in Excel/Sheets — matches main admin analytics)
    rows.push(['TrustFund OLAP Analytics Export']);
    rows.push(['━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━']);
    rows.push(['Professional OLAP Report  •  Slice • Dice • Roll-up • Drill-down']);
    rows.push(['━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━']);
    rows.push([]);
    rows.push(['Generated', new Date().toLocaleString()]);
    // filters may be in currentFilters after fetch
    const f = (typeof currentFilters !== 'undefined' && currentFilters) || {};
    rows.push(['Time Granularity', f.time_level || 'month']);
    rows.push(['Year', f.year || 'All']);
    rows.push(['Quarter', f.quarter || 'All']);
    rows.push(['Group', f.group_key || 'All']);
    rows.push(['Transaction Type', f.trans_type || 'all']);
    rows.push(['Note', 'All amounts in PHP. Designed for Excel / Google Sheets.']);
    rows.push([]);

    // SUMMARY
    rows.push(['▶ SUMMARY']);
    rows.push(['────────────────────────────']);
    rows.push(['Metric', 'Value']);
    const sum = currentData.summary || {};
    rows.push(['Total Contributions', sum.total_contributions || 0]);
    rows.push(['Total Payouts', sum.total_payouts || 0]);
    rows.push(['Transactions', sum.total_transactions || 0]);
    rows.push([]);

    // BY GROUP (includes tx count when present)
    rows.push(['▶ BY GROUP PERFORMANCE']);
    rows.push(['────────────────────────────']);
    rows.push(['Group', 'Contributions (PHP)', 'Payouts (PHP)', 'Transactions']);
    let gTotalC = 0, gTotalP = 0, gTotalTx = 0;
    if (currentData.by_group && currentData.by_group.length) {
        currentData.by_group.forEach(r => {
            const c = parseFloat(r.total_contributions || 0);
            const p = parseFloat(r.total_payouts || 0);
            const tx = parseInt(r.transaction_count || 0);
            gTotalC += c; gTotalP += p; gTotalTx += tx;
            rows.push([r.group_name || '', c, p, tx]);
        });
        rows.push(['']);
        rows.push(['TOTAL (current view)', gTotalC, gTotalP, gTotalTx]);
    } else {
        rows.push(['No group data', '', '', '']);
    }
    rows.push([]);

    // TIME SERIES (full trends data from line chart)
    rows.push(['▶ TIME SERIES TRENDS']);
    rows.push(['────────────────────────────']);
    rows.push(['Period', 'Contributions (PHP)', 'Payouts (PHP)']);
    if (currentData.time_series && currentData.time_series.length) {
        currentData.time_series.forEach(r => {
            rows.push([
                r.period_label || '',
                parseFloat(r.contributions || 0),
                parseFloat(r.payouts || 0)
            ]);
        });
    } else {
        rows.push(['No time series data', '', '']);
    }
    rows.push([]);

    // Footer / provenance (styled to match main export)
    rows.push(['──────────────────────────────────────────────────────────────────────────────────────────────']);
    rows.push(['exported from olap']);
    rows.push(['──────────────────────────────────────────────────────────────────────────────────────────────']);

    // Proper CSV escaping
    function esc(v) {
        if (v === null || v === undefined) return '';
        const s = String(v);
        if (/[",\n\r]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
        return s;
    }

    const csvContent = rows.map(r => r.map(esc).join(',')).join('\n');

    // data: URI + BOM for best cross-app compatibility (especially Excel on Windows)
    const encoded = encodeURIComponent('\uFEFF' + csvContent);
    const link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encoded;
    link.download = `TrustFund_Analytics_${(f.time_level||'month')}_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// pdf export with jspdf (easy)
async function exportToPDF() {
    if (!currentData) {
        alert('Please wait for data to load first.');
        return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Title
    doc.setFontSize(18);
    doc.text("TrustFund - OLAP Report", 20, 20);
    
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
    
    // charts in the pdf (as images)
    let y = doc.lastAutoTable.finalY + 12;
    doc.setFontSize(13);
    doc.text("Analytics Charts", 20, y);
    y += 6;

    const imgWidth = 170;

    function addChartImage(chartInstance, canvasId, label, defaultH) {
        if (!chartInstance || typeof chartInstance.toBase64Image !== 'function') return y;

        if (y > 225) { doc.addPage(); y = 20; }

        doc.setFontSize(10);
        doc.text(label, 20, y);
        y += 2;

        let h = defaultH;
        const c = document.getElementById(canvasId);
        if (c && c.width && c.height) {
            h = Math.max(35, Math.min(imgWidth * (c.height / c.width), 90));
        }

        try {
            const dataUrl = chartInstance.toBase64Image('image/png', 1);
            doc.addImage(dataUrl, 'PNG', 20, y, imgWidth, h);
        } catch (e) {}
        y += h + 7;
        return y;
    }

    y = addChartImage(barChart, 'barChart', 'Contributions by Group', 55);
    y = addChartImage(lineChart, 'lineChart', 'Trends Over Time', 50);
    y = addChartImage(pieChart, 'pieChart', 'Payout Distribution', 65);

    // Footer note
    if (y > 235) { doc.addPage(); y = 20; }
    doc.setFontSize(9);
    doc.text("From TrustFund OLAP. Filters applied.", 20, y);
    
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

// ---
// KEYBOARD SHORTCUT (nice touch for demo)
// ---
document.addEventListener('keydown', function(e) {
    if (e.key === '?' && document.activeElement.tagName === 'BODY') {
        e.preventDefault();
        const groupFilter = document.getElementById('filter-group');
        if (groupFilter) groupFilter.focus();
    }
});

console.log('%c[analytics] olap dash loaded', 'color:#6B6560');