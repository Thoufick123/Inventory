<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($reportData['title'] ?? 'Official System Report') ?> &mdash; GRM Enterprise</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Local System Fonts to prevent html2canvas CORS Taint -->
    <!-- html2pdf.js for Client-side direct PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js';"></script>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            background-color: #f1f5f9;
            padding: 0;
            margin: 0;
            font-size: 12px;
        }
        .font-mono {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace;
        }
        .report-sheet {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px 32px;
            box-shadow: none;
            border-radius: 0;
        }
        .report-header {
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .company-logo-badge {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #ffffff;
            font-weight: 800;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            letter-spacing: -0.5px;
        }
        .company-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .report-title {
            font-size: 16px;
            font-weight: 700;
            color: #2563eb;
            margin-top: 4px;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
        }
        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            background: #ffffff;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
            height: 100%;
        }
        .table-custom {
            font-size: 11.5px;
        }
        .table-custom th {
            background-color: #0f172a !important;
            color: #ffffff !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10.5px;
            letter-spacing: 0.4px;
            padding: 9px 10px;
            border: none;
        }
        .table-custom td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .table-custom tfoot td {
            background-color: #f8fafc;
            font-weight: 800;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-size: 11.5px;
        }
        .signature-block {
            margin-top: 52px;
            padding-top: 24px;
            border-top: 1.5px dashed #cbd5e1;
            page-break-inside: avoid;
        }
        .sig-line {
            width: 170px;
            border-bottom: 1.5px solid #475569;
            margin-bottom: 8px;
        }

        /* Floating top action bar */
        .action-bar {
            max-width: 1120px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .report-sheet {
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .table-custom th {
                background-color: #0f172a !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .meta-box, .kpi-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .report-sheet {
                padding: 10px !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Hidden in Print / PDF export) -->
    <div class="no-print action-bar">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary px-2.5 py-1.5 fs-7 font-mono">
                <i class="bi bi-file-earmark-text me-1"></i><?= strtoupper(e($type)) ?> REPORT
            </span>
            <span class="text-muted small">Period: <strong><?= e($filter['label']) ?></strong></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div id="pdfDownloadError" class="alert alert-danger py-1 px-2 mb-0 small d-none" role="alert"></div>
            <button id="btnDownloadPdf" onclick="downloadAsPDF()" class="btn btn-danger btn-sm px-3 shadow-sm fw-semibold">
                <i class="bi bi-file-earmark-pdf-fill me-1.5"></i>Download PDF Format
            </button>
            <button onclick="window.print()" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold">
                <i class="bi bi-printer me-1.5"></i>Print Document
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-x-lg me-1"></i>Close
            </button>
        </div>
    </div>

    <!-- Official Report Sheet -->
    <div class="report-sheet" id="printableReportSheet">
        <!-- Header -->
        <div class="report-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="company-logo-badge">GRM</div>
                <div>
                    <div class="company-title">GRM ENTERPRISE INVENTORY</div>
                    <div class="report-title"><?= e($reportData['title'] ?? 'Official System Report') ?></div>
                    <div class="text-muted small" style="font-size: 11px;">Garments Manufacturing, Multi-Channel Warehousing &amp; Retail POS Operations</div>
                </div>
            </div>
            <div class="text-end small">
                <span class="badge bg-dark px-2.5 py-1 text-uppercase font-mono mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Audited &amp; Confidential</span>
                <div class="text-muted font-mono" style="font-size: 11px;">Generated: <?= date('d-M-Y, H:i:s') ?></div>
                <div class="text-secondary" style="font-size: 11px;">Issued By: <strong><?= e(Session::get('user_name') ?? 'System Administrator') ?></strong></div>
            </div>
        </div>

        <!-- Filter Meta Block -->
        <div class="meta-box">
            <div class="row g-2 align-items-center">
                <div class="col-sm-4">
                    <span class="text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.4px;">Report Period Filter:</span>
                    <div class="fw-bold text-dark fs-6 mt-0.5"><?= e($filter['label']) ?></div>
                </div>
                <div class="col-sm-4 text-sm-center">
                    <span class="text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.4px;">Effective Date Range:</span>
                    <div class="fw-bold text-dark font-mono mt-0.5"><?= date('d M Y', strtotime($filter['start_date'])) ?> &rarr; <?= date('d M Y', strtotime($filter['end_date'])) ?></div>
                </div>
                <div class="col-sm-4 text-sm-end">
                    <span class="text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.4px;">Audit Integrity:</span>
                    <div class="fw-bold text-success mt-0.5"><i class="bi bi-shield-check me-1"></i>Verified Database Record</div>
                </div>
            </div>
        </div>

        <!-- KPI Summary Scoreboard (if present) -->
        <?php if (!empty($reportData['summary_kpis'])): ?>
            <div class="mb-4">
                <div class="fw-bold text-uppercase text-muted mb-2" style="font-size: 10.5px; letter-spacing: 0.5px;">
                    <i class="bi bi-bar-chart-fill text-primary me-1"></i>Executive Key Metrics &amp; Aggregates
                </div>
                <div class="row g-2">
                    <?php foreach ($reportData['summary_kpis'] as $kpiName => $kpiVal): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="kpi-card">
                                <div class="text-muted text-uppercase text-truncate" style="font-size: 10px; font-weight: 600;"><?= e($kpiName) ?></div>
                                <div class="fw-bold fs-6 text-dark font-mono mt-1"><?= e((string)$kpiVal) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Data Table -->
        <div class="table-responsive mb-4">
            <table class="table table-custom table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 45px;">#</th>
                        <?php foreach ($reportData['columns'] as $colTitle): ?>
                            <th><?= e($colTitle) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData['rows'])): ?>
                        <tr>
                            <td colspan="<?= count($reportData['columns']) + 1 ?>" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                No records found for the selected reporting period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reportData['rows'] as $idx => $row): ?>
                            <tr>
                                <td class="ps-3 text-muted font-mono" style="font-size: 10.5px;"><?= $idx + 1 ?></td>
                                <?php foreach ($row as $cell): ?>
                                    <td><?= e((string)$cell) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($reportData['totals'])): ?>
                    <tfoot>
                        <tr>
                            <td class="ps-3"></td>
                            <?php foreach ($reportData['totals'] as $totCell): ?>
                                <td><?= e((string)$totCell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- Document Signatures Footer -->
        <div class="signature-block">
            <div class="row text-center">
                <div class="col-4">
                    <div class="d-flex flex-column align-items-center">
                        <div class="sig-line"></div>
                        <div class="small fw-bold text-dark">Prepared By</div>
                        <div class="text-muted" style="font-size: 10px;">Reports Officer / System Admin</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="d-flex flex-column align-items-center">
                        <div class="sig-line"></div>
                        <div class="small fw-bold text-dark">Audited &amp; Checked</div>
                        <div class="text-muted" style="font-size: 10px;">Accounts &amp; Inventory Manager</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="d-flex flex-column align-items-center">
                        <div class="sig-line"></div>
                        <div class="small fw-bold text-dark">Authorized Signatory</div>
                        <div class="text-muted" style="font-size: 10px;">Managing Director / Director</div>
                    </div>
                </div>
            </div>
            <div class="text-center text-muted mt-4" style="font-size: 10px;">
                This document is generated by GRM Enterprise Multi-Channel Inventory Management System. Timestamp: <?= date('Y-m-d H:i:s') ?>.
            </div>
        </div>
    </div>

    <!-- Direct PDF Generation Script -->
    <script>
    function downloadAsPDF() {
        const btn = document.getElementById('btnDownloadPdf');
        const origContent = btn ? btn.innerHTML : '';
        if (typeof html2pdf !== 'function') {
            const error = document.getElementById('pdfDownloadError');
            if (error) {
                error.textContent = 'PDF generator could not load. Check your internet connection, then reload this report.';
                error.classList.remove('d-none');
            }
            return;
        }
        if (btn) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5" role="status"></span> Generating PDF...';
            btn.disabled = true;
        }

        const element = document.getElementById('printableReportSheet');
        const opt = {
            margin:       [8, 8, 8, 8],
            filename:     'GRM_<?= strtoupper(e($type)) ?>_REPORT_<?= date('Ymd_His') ?>.pdf',
            image:        { type: 'jpeg', quality: 0.95 },
            html2canvas:  { scale: 1, useCORS: true, allowTaint: true, scrollY: 0, logging: false },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape', compress: true }
        };

        html2pdf().set(opt).from(element).save().then(function() {
            if (btn) {
                btn.innerHTML = origContent;
                btn.disabled = false;
            }
            if (window.parent && window.parent !== window) {
                window.parent.postMessage('PDF_GENERATED', '*');
            } else {
                setTimeout(() => window.close(), 1000);
            }
        }).catch(function(err) {
            console.error('PDF error:', err);
            if (btn) {
                btn.innerHTML = origContent;
                btn.disabled = false;
            }
            if (window.parent && window.parent !== window) {
                window.parent.postMessage('PDF_FAILED', '*');
            } else {
                const error = document.getElementById('pdfDownloadError');
                if (error) {
                    error.textContent = 'PDF generation failed. Use Print Document and choose “Save as PDF”.';
                    error.classList.remove('d-none');
                }
            }
        });
    }

    // Auto-trigger PDF download if requested via query param
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('pdf') === '1' || urlParams.get('download_pdf') === '1' || urlParams.get('download') === 'pdf') {
            setTimeout(downloadAsPDF, 50);
        }
    });

    // Listen for manual trigger from parent window (hidden iframe preload)
    window.addEventListener('message', function(e) {
        if (e.data === 'START_PDF') {
            setTimeout(downloadAsPDF, 50);
        }
    });
    </script>
</body>
</html>
