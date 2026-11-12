<?php
// views/reports/partials/filter_bar.php
// Reusable High-Performance Filter Bar — Auto-Submit without Filter Button

$curFilterType = $filter['filter_type'] ?? 'monthly';
$curYear       = (int)($filter['year'] ?? date('Y'));
$curMonth      = (int)($filter['month'] ?? date('m'));
$curFromDate   = $filter['from_date'] ?? date('Y-m-01');
$curToDate     = $filter['to_date'] ?? date('Y-m-d');
$currentReportType = $reportType ?? 'overview';
$reportPermissionModules = [
    'overview' => 'reports_overview',
    'profitability' => 'reports_profitability',
    'sales' => 'reports_sales',
    'sales_pos' => 'reports_sales',
    'sales_warehouse' => 'reports_sales',
    'sales_production' => 'reports_sales',
    'sales_website' => 'reports_website',
    'sales_whatsapp' => 'reports_whatsapp',
    'website' => 'reports_website',
    'whatsapp' => 'reports_whatsapp',
    'online' => 'reports_online',
    'production' => 'reports_production',
    'warehouse' => 'reports_warehouse',
    'purchase' => 'reports_purchase',
    'shop' => 'reports_shop',
    'inventory' => 'reports_inventory',
];
$reportPermissionModule = $reportPermissionModules[$currentReportType] ?? 'reports_overview';
$canExportReport = hasPermission($reportPermissionModule, 'export')
    || hasPermission($reportPermissionModule, 'view')
    || hasPermission('reports', 'view');
$canPrintReport = hasPermission($reportPermissionModule, 'print')
    || hasPermission($reportPermissionModule, 'view')
    || hasPermission('reports', 'view');
$reportFilters = $_GET;
unset($reportFilters['url']);
$reportQuery = http_build_query($reportFilters);
$reportPageUrl = url("reports/{$currentReportType}");
$exportUrl = url("reports/export/{$currentReportType}") . ($reportQuery !== '' ? '?' . $reportQuery : '');
$printUrl = url("reports/print/{$currentReportType}") . ($reportQuery !== '' ? '?' . $reportQuery : '');
$pdfUrl = url("reports/pdf/{$currentReportType}") . ($reportQuery !== '' ? '?' . $reportQuery : '');

$yearsList = [date('Y') + 1, date('Y'), date('Y') - 1, date('Y') - 2];
$monthsList = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="<?= e($reportPageUrl) ?>" id="reportFilterForm" class="row g-2 align-items-end">
            <!-- Filter Type Selector -->
            <div class="col-xl-2 col-md-3 col-sm-6">
                <label class="form-label text-muted small fw-semibold mb-1" for="filterTypeSelect">
                    <i class="bi bi-funnel text-primary me-1"></i>Filter Mode
                </label>
                <select name="filter_type" id="filterTypeSelect" class="form-select form-select-sm fw-semibold" onchange="handleFilterTypeChange()">
                    <option value="monthly" <?= $curFilterType === 'monthly' ? 'selected' : '' ?>>Monthly Wise</option>
                    <option value="yearly" <?= $curFilterType === 'yearly' ? 'selected' : '' ?>>Yearly Wise</option>
                    <option value="custom" <?= $curFilterType === 'custom' ? 'selected' : '' ?>>Custom Date Range</option>
                    <option value="all" <?= $curFilterType === 'all' ? 'selected' : '' ?>>All Time</option>
                </select>
            </div>

            <!-- Month Selection (visible for Monthly) -->
            <div class="col-xl-2 col-md-3 col-sm-6 filter-control-group <?= $curFilterType === 'monthly' ? '' : 'd-none' ?>" id="monthInputGroup">
                <label class="form-label text-muted small fw-semibold mb-1">Select Month</label>
                <select name="month" class="form-select form-select-sm" onchange="handleMonthYearChange()">
                    <?php foreach ($monthsList as $mNum => $mName): ?>
                        <option value="<?= $mNum ?>" <?= $curMonth === $mNum ? 'selected' : '' ?>>
                            <?= $mName ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Year Selection (visible for Monthly & Yearly) -->
            <div class="col-xl-2 col-md-2 col-sm-6 filter-control-group <?= in_array($curFilterType, ['monthly', 'yearly']) ? '' : 'd-none' ?>" id="yearInputGroup">
                <label class="form-label text-muted small fw-semibold mb-1">Select Year</label>
                <select name="year" class="form-select form-select-sm" onchange="handleMonthYearChange()">
                    <?php foreach ($yearsList as $yr): ?>
                        <option value="<?= $yr ?>" <?= $curYear === (int)$yr ? 'selected' : '' ?>>
                            <?= $yr ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Custom From Date (Visible for Custom Date Range) -->
            <div class="col-xl-2 col-md-2 col-sm-6 filter-control-group <?= $curFilterType === 'custom' ? '' : 'd-none' ?>" id="fromDateInputGroup">
                <label class="form-label text-muted small fw-semibold mb-1">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $curFilterType === 'custom' ? e($curFromDate) : '' ?>" onchange="autoSubmitCustomDate()">
            </div>

            <!-- Custom To Date (Visible for Custom Date Range) -->
            <div class="col-xl-2 col-md-2 col-sm-6 filter-control-group <?= $curFilterType === 'custom' ? '' : 'd-none' ?>" id="toDateInputGroup">
                <label class="form-label text-muted small fw-semibold mb-1">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $curFilterType === 'custom' ? e($curToDate) : '' ?>" onchange="autoSubmitCustomDate()">
            </div>

            <!-- Action Buttons aligned with inputs -->
            <div class="col-xl ms-auto d-flex align-items-center justify-content-sm-end gap-2 mt-2 mt-xl-0 flex-wrap">
                <?php if ($canExportReport): ?>
                <a href="<?= e($pdfUrl) ?>"
                   class="btn btn-sm btn-outline-danger fw-semibold shadow-xs" title="Download official PDF report">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i>Download PDF
                </a>
                <?php endif; ?>

                <!-- Export formatted Excel workbook -->
                <?php if ($canExportReport): ?>
                <a href="<?= e($exportUrl) ?>"
                   class="btn btn-sm btn-outline-success fw-semibold shadow-xs" title="Download a formatted Excel workbook">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel (.xlsx)
                </a>
                <?php endif; ?>

                <!-- Print Document Button -->
                <?php if ($canPrintReport): ?>
                <a href="<?= e($printUrl) ?>"
                   target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold shadow-xs" title="Open printable document view">
                    <i class="bi bi-printer me-1"></i>Print
                </a>
                <?php endif; ?>

            </div>
        </form>

        <!-- Quick Filter Presets Bar -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-2.5 border-top">
            <div class="d-flex align-items-center flex-wrap gap-1.5">
                <span class="text-muted small me-1 fw-semibold" style="font-size: 0.76rem;">
                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Quick Presets:
                </span>
                
                <a href="<?= e($reportPageUrl) ?>?period=this_month" class="badge rounded-pill text-decoration-none px-2.5 py-1 <?= $curFilterType === 'monthly' && $curMonth === (int)date('m') && $curYear === (int)date('Y') ? 'bg-primary text-white' : 'bg-light text-secondary border' ?>">
                    This Month
                </a>
                <a href="<?= e($reportPageUrl) ?>?period=last_month" class="badge rounded-pill text-decoration-none px-2.5 py-1 <?= $curFilterType === 'last_month' ? 'bg-primary text-white' : 'bg-light text-secondary border' ?>">
                    Last Month
                </a>
                <a href="<?= e($reportPageUrl) ?>?period=this_quarter" class="badge rounded-pill text-decoration-none px-2.5 py-1 <?= $curFilterType === 'this_quarter' ? 'bg-primary text-white' : 'bg-light text-secondary border' ?>">
                    This Quarter
                </a>
                <a href="<?= e($reportPageUrl) ?>?period=yearly&amp;year=<?= date('Y') ?>" class="badge rounded-pill text-decoration-none px-2.5 py-1 <?= $curFilterType === 'yearly' && $curYear === (int)date('Y') ? 'bg-primary text-white' : 'bg-light text-secondary border' ?>">
                    Year <?= date('Y') ?>
                </a>
                <a href="<?= e($reportPageUrl) ?>?period=all" class="badge rounded-pill text-decoration-none px-2.5 py-1 <?= $curFilterType === 'all' ? 'bg-primary text-white' : 'bg-light text-secondary border' ?>">
                    All Time
                </a>
            </div>

            <!-- Active Date Tag Badge -->
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 font-monospace" style="font-size: 0.78rem;">
                    <i class="bi bi-calendar-check me-1"></i><?= e($filter['label']) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
function handleFilterTypeChange() {
    const mode = document.getElementById('filterTypeSelect').value;
    const mGroup = document.getElementById('monthInputGroup');
    const yGroup = document.getElementById('yearInputGroup');
    const fGroup = document.getElementById('fromDateInputGroup');
    const tGroup = document.getElementById('toDateInputGroup');

    // Dynamically toggle inputs based on mode
    if (mode === 'custom') {
        mGroup.classList.add('d-none');
        yGroup.classList.add('d-none');
        fGroup.classList.remove('d-none');
        tGroup.classList.remove('d-none');
        // Do not auto submit right away for 'custom', wait for them to pick dates
        return;
    } else {
        // Clear custom dates if switching away from custom
        document.querySelector('[name="from_date"]').value = '';
        document.querySelector('[name="to_date"]').value = '';
        
        if (mode === 'monthly') {
            mGroup.classList.remove('d-none');
            yGroup.classList.remove('d-none');
            fGroup.classList.add('d-none');
            tGroup.classList.add('d-none');
        } else if (mode === 'yearly') {
            mGroup.classList.add('d-none');
            yGroup.classList.remove('d-none');
            fGroup.classList.add('d-none');
            tGroup.classList.add('d-none');
        } else if (mode === 'all') {
            mGroup.classList.add('d-none');
            yGroup.classList.add('d-none');
            fGroup.classList.add('d-none');
            tGroup.classList.add('d-none');
        }
        document.getElementById('reportFilterForm').submit();
    }
}

function handleMonthYearChange() {
    document.querySelector('[name="from_date"]').value = '';
    document.querySelector('[name="to_date"]').value = '';
    document.getElementById('reportFilterForm').submit();
}

function autoSubmitCustomDate() {
    const fromDate = document.querySelector('[name="from_date"]').value;
    const toDate = document.querySelector('[name="to_date"]').value;
    
    // Only auto-submit when both dates are filled
    if (fromDate && toDate) {
        document.getElementById('filterTypeSelect').value = 'custom';
        document.getElementById('reportFilterForm').submit();
    }
}

function triggerPdfDownload(url = null, btnElement = null) {
    window.location.assign(url || <?= json_encode($pdfUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
    const button = btnElement || document.getElementById('btnDownloadPdfFilter');
    if (button) {
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Preparing PDF...';
        button.disabled = true;
        setTimeout(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
        }, 2500);
    }
}
</script>
