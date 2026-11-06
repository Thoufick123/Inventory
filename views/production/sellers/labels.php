<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Fabric Roll Labels') ?> — GRM Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="<?= asset('js/qrcode.min.js') ?>"></script>
    <script>if (typeof QRCode === 'undefined') document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');</script>
    <style>
        body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .labels-container {
            max-width: 1140px;
            margin: 0 auto;
        }

        .stickers-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: flex-start;
        }

        /* 50x30mm / 2x1 inch standard thermal sticker card */
        .fabric-sticker-card {
            width: 260px;
            min-height: 148px;
            background: #fff;
            border: 1.5px solid #0f172a;
            border-radius: 6px;
            padding: 6px 10px 8px 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: all 0.2s ease;
        }

        .fabric-sticker-card.is-selected {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.35) !important;
            background: #f8faff;
        }

        .fabric-sticker-card.newly-added {
            animation: pulseSuccess 2s ease;
            border-color: #198754 !important;
        }

        @keyframes pulseSuccess {
            0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
            100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
        }

        .sticker-card-toolbar {
            background: #f8fafc;
            border-bottom: 1px dashed #cbd5e1;
            padding: 2px 4px 4px 4px;
            margin: -6px -10px 4px -10px;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
        }

        .sticker-card-toolbar .btn-single-print {
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 4px;
            line-height: 1.3;
        }

        .sticker-card-toolbar .btn-single-delete {
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 4px;
            line-height: 1.3;
        }

        .sticker-header {
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }

        .sticker-title {
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .sticker-supplier {
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            line-height: 1;
        }

        .sticker-body {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qr-wrapper {
            width: 72px;
            flex-shrink: 0;
            text-align: center;
        }

        .qr-canvas-box {
            width: 68px;
            height: 68px;
            background: #fff;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .qr-canvas-box img,
        .qr-canvas-box canvas {
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
        }

        .code-text {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-weight: 800;
            font-size: 10px;
            color: #0f172a;
            letter-spacing: 0.8px;
            margin-top: 2px;
            line-height: 1;
            text-align: center;
        }

        .details-col {
            flex-grow: 1;
            overflow: hidden;
            line-height: 1.15;
        }

        .roll-badge {
            font-size: 10.5px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .material-text {
            font-size: 7.5px;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }

        .spec-row {
            margin-top: 3px;
            font-size: 8px;
            color: #334155;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .spec-item {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .piece-count-item {
            font-weight: 700;
            color: #0f172a;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print, .sticker-card-toolbar {
                display: none !important;
            }

            .labels-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .stickers-grid {
                gap: 8px !important;
                justify-content: flex-start !important;
            }

            .fabric-sticker-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                padding: 6px 8px !important;
                width: 250px !important;
                min-height: 130px !important;
            }

            .print-hidden {
                display: none !important;
            }

            body.single-print-mode {
                width: auto !important;
                height: auto !important;
            }
            body.single-print-mode .stickers-grid {
                display: block !important;
            }
            body.single-print-mode .fabric-sticker-card:not(.print-target) {
                display: none !important;
            }
            body.single-print-mode .fabric-sticker-card.print-target {
                display: flex !important;
                margin: 0 !important;
                border: 1px solid #000 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

<div class="labels-container">
    <!-- Top Action Bar (hidden on print) -->
    <div class="no-print bg-white p-3 rounded-3 shadow-sm border mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-2">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="bi bi-qr-code text-primary me-2"></i><?= e($pageTitle ?? 'Fabric Roll Labels') ?>
                </h5>
                <div class="text-muted small">
                    Supplier: <strong class="text-dark"><?= e($seller['company'] ?: $seller['name']) ?></strong> • 
                    Fabric: <span class="badge bg-light text-dark border"><?= e($seller['material_type'] ?: 'Standard Fabric') ?></span> • 
                    Total: <span class="badge bg-primary text-white" id="totalStickersBadge"><?= count($stickers) ?> Sticker<?= count($stickers) === 1 ? '' : 's' ?></span>
                </div>
            </div>

            <!-- Main Action Buttons -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Generate Extra Continuation Label -->
                <button type="button" class="btn btn-success shadow-sm" onclick="openAddExtraLabelModal()" title="Generate next label in sequence">
                    <i class="bi bi-plus-circle me-1"></i> + Generate Extra Label
                </button>

                <!-- Delete Selected Button (Dynamically enabled) -->
                <button type="button" class="btn btn-outline-danger fw-bold shadow-sm" id="btnDeleteSelected" onclick="deleteSelectedStickers()" style="display: none;" title="Delete selected labels">
                    <i class="bi bi-trash me-1"></i> Delete Selected (<span id="deleteSelectedCountSpan">0</span>)
                </button>

                <!-- Print Selected Button (Dynamically enabled) -->
                <button type="button" class="btn btn-warning fw-bold shadow-sm" id="btnPrintSelected" onclick="printSelectedStickers()" style="display: none;">
                    <i class="bi bi-printer me-1"></i> Print Selected (<span id="selectedCountSpan">0</span>)
                </button>

                <!-- Print All Button -->
                <button type="button" class="btn btn-primary shadow-sm" id="btnPrintAll" onclick="printAllStickers()">
                    <i class="bi bi-printer me-1"></i> Print All Stickers (<span id="allCountSpan"><?= count($stickers) ?></span>)
                </button>

                <!-- Close Button -->
                <button type="button" class="btn btn-outline-secondary" onclick="window.close()">
                    <i class="bi bi-x-lg me-1"></i> Close
                </button>
            </div>
        </div>

        <!-- Filter & Selection Bar -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnToggleSelectAll" onclick="toggleSelectAll()">
                    <i class="bi bi-check2-square me-1"></i> Select All
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearSelection" onclick="clearSelection()" style="display: none;">
                    <i class="bi bi-x-square me-1"></i> Clear Selection
                </button>
                <span class="text-muted small ms-1" id="selectionHint">
                    <i class="bi bi-info-circle me-1"></i>Tip: Use <strong>Print This Label</strong> to reprint, or <i class="bi bi-trash text-danger"></i> / <strong>Delete Selected</strong> to remove labels.
                </span>
            </div>

            <!-- Live Filter Box -->
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="stickerSearchInput" class="form-control border-start-0" placeholder="Filter by code or piece #..." oninput="filterStickers(this.value)">
                <button class="btn btn-outline-secondary" type="button" onclick="clearFilter()" title="Clear filter">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Feedback Toast Notification Container -->
    <div id="toastNotification" class="alert alert-success alert-dismissible fade no-print d-none" role="alert">
        <span id="toastMessage"></span>
        <button type="button" class="btn-close" onclick="hideToast()"></button>
    </div>

    <!-- Stickers Grid -->
    <div class="stickers-grid" id="stickersGrid">
        <?php foreach ($stickers as $idx => $stk): ?>
            <div class="fabric-sticker-card" id="card_<?= $idx ?>" data-idx="<?= $idx ?>" data-code="<?= e($stk['code']) ?>" data-seq="<?= (int)$stk['seq'] ?>" data-piece="<?= $stk['piece_index'] ?>" data-roll="<?= e($stk['roll_number']) ?>" data-roll-id="<?= (int)($stk['roll_id'] ?? 0) ?>">
                <!-- Interactive Toolbar (hidden on physical print) -->
                <div class="sticker-card-toolbar no-print">
                    <label class="form-check-label d-flex align-items-center gap-1 cursor-pointer mb-0">
                        <input type="checkbox" class="form-check-input sticker-check" data-idx="<?= $idx ?>" onchange="updateSelectedCount()">
                        <span class="fw-semibold text-secondary">#<?= $stk['piece_index'] ?></span>
                    </label>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-dark btn-single-print" onclick="printSingleSticker(<?= $idx ?>)" title="Print ONLY this label (for reprint / damaged sticker)">
                            <i class="bi bi-printer-fill me-1"></i> Print This Label
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-single-delete" onclick="deleteSingleSticker(<?= $idx ?>)" title="Delete this label">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Sticker Header -->
                <div class="sticker-header d-flex justify-content-between align-items-center">
                    <div class="sticker-title text-truncate" title="GRM RAW MATERIAL">GRM RAW MATERIAL</div>
                    <div class="sticker-supplier text-truncate" style="max-width: 120px;" title="<?= e($stk['supplier_name']) ?>">
                        <?= e($stk['supplier_name']) ?>
                    </div>
                </div>

                <!-- Sticker Body -->
                <div class="sticker-body">
                    <div class="qr-wrapper">
                        <div class="qr-canvas-box" id="qr_canvas_<?= $idx ?>"></div>
                        <div class="code-text"><?= e($stk['code']) ?></div>
                    </div>
                    <div class="details-col">
                        <div class="roll-badge" title="<?= e($stk['roll_number']) ?>"><?= e($stk['roll_number']) ?></div>
                        <div class="material-text" title="<?= e($stk['material_type']) ?>"><?= e($stk['material_type']) ?></div>
                        <div class="spec-row">
                            <?php if (!empty($stk['color'])): ?>
                                <div class="spec-item"><strong>Color:</strong> <?= e($stk['color']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($stk['design_name'])): ?>
                                <div class="spec-item"><strong>Design:</strong> <?= e($stk['design_name']) ?></div>
                            <?php endif; ?>
                            <?php if ($stk['weight'] > 0): ?>
                                <div class="spec-item weight-item"><strong>Piece Weight:</strong> <?= number_format($stk['weight'], 2) ?> kg</div>
                            <?php endif; ?>
                            <div class="spec-item piece-count-item">Piece <?= (int)$stk['piece_index'] ?> of <?= (int)$stk['total_qty'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Generate Extra Continuation Label -->
<div class="modal fade no-print" id="addExtraLabelModal" tabindex="-1" aria-labelledby="addExtraLabelModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="addExtraLabelModalTitle">
                    <i class="bi bi-plus-circle text-success me-2"></i>Generate Extra Continuation Label
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    This will generate the next sequential roll sticker(s) continuing from the previous sequence code and update the roll piece count in the system.
                </p>

                <!-- Select Roll (if multiple rolls) -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Fabric Roll <span class="text-danger">*</span></label>
                    <select class="form-select" id="extraLabelRollSelect">
                        <?php foreach ($rolls as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" data-roll-number="<?= e($r['roll_number']) ?>" data-end-code="<?= e($r['end_code']) ?>" data-qty="<?= (int)$r['quantity'] ?>">
                                <?= e($r['roll_number']) ?> (Current: <?= (int)$r['quantity'] ?> pcs, Last Code: <?= e($r['end_code'] ?: 'N/A') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Number of Labels to Generate -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Extra Labels to Generate</label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" onclick="adjustExtraCount(-1)">-</button>
                        <input type="number" class="form-control text-center fw-bold" id="extraLabelCountInput" value="1" min="1" max="50">
                        <button class="btn btn-outline-secondary" type="button" onclick="adjustExtraCount(1)">+</button>
                    </div>
                    <small class="text-muted">Typically 1 extra label to replace or extend.</small>
                </div>

                <div class="alert alert-info py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i>The next label code will automatically continue after the last piece in sequence.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success fw-bold" id="btnConfirmAddExtra" onclick="submitAddExtraLabel()">
                    <span id="btnConfirmAddExtraSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                    <i class="bi bi-check-circle me-1" id="btnConfirmAddExtraIcon"></i> Generate & Add Label
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let stickersData = <?= json_encode($stickers) ?>;
const csrfToken = '<?= CSRF::token() ?>';
const addExtraUrlBase = '<?= url("production/sellers/add-extra-label/") ?>';
const deleteExtraUrlBase = '<?= url("production/sellers/delete-extra-label/") ?>';

document.addEventListener('DOMContentLoaded', function() {
    renderAllQRCodes();
    updateSelectedCount();

    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    const printIdxParam = urlParams.get('print_idx');

    if (searchParam) {
        const input = document.getElementById('stickerSearchInput');
        if (input) input.value = searchParam;
        filterStickers(searchParam);
    }

    if (printIdxParam !== null) {
        const idx = parseInt(printIdxParam, 10);
        if (!isNaN(idx)) {
            setTimeout(() => {
                printSingleSticker(idx);
            }, 600);
        }
    }
});

// Render QR codes on cards
function renderAllQRCodes() {
    stickersData.forEach((stk, idx) => {
        renderQRCodeForIndex(idx, stk.code);
    });
}

function renderQRCodeForIndex(idx, text) {
    const container = document.getElementById('qr_canvas_' + idx);
    if (container && window.QRCode) {
        container.innerHTML = '';
        new QRCode(container, {
            text: text,
            width: 64,
            height: 64,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    }
}

// -------------------------------------------------------------
// 1. PRINT SINGLE / DAMAGED STICKER
// -------------------------------------------------------------
function printSingleSticker(idx) {
    const card = document.getElementById('card_' + idx);
    if (!card) return;

    document.body.classList.add('single-print-mode');
    document.querySelectorAll('.fabric-sticker-card').forEach(c => c.classList.remove('print-target'));
    card.classList.add('print-target');

    window.print();

    setTimeout(() => {
        document.body.classList.remove('single-print-mode');
        card.classList.remove('print-target');
    }, 600);
}

// -------------------------------------------------------------
// 2. PRINT SELECTED STICKERS (Multi-select damaged / reprint)
// -------------------------------------------------------------
function updateSelectedCount() {
    const checked = document.querySelectorAll('.sticker-check:checked');
    const count = checked.length;
    const btnPrintSelected = document.getElementById('btnPrintSelected');
    const btnDeleteSelected = document.getElementById('btnDeleteSelected');
    const countSpan = document.getElementById('selectedCountSpan');
    const deleteCountSpan = document.getElementById('deleteSelectedCountSpan');
    const clearBtn = document.getElementById('btnClearSelection');

    if (countSpan) countSpan.textContent = count;
    if (deleteCountSpan) deleteCountSpan.textContent = count;

    if (count > 0) {
        if (btnPrintSelected) btnPrintSelected.style.display = 'inline-block';
        if (btnDeleteSelected) btnDeleteSelected.style.display = 'inline-block';
        if (clearBtn) clearBtn.style.display = 'inline-block';
    } else {
        if (btnPrintSelected) btnPrintSelected.style.display = 'none';
        if (btnDeleteSelected) btnDeleteSelected.style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'none';
    }

    // Highlight selected cards
    document.querySelectorAll('.fabric-sticker-card').forEach(card => {
        const chk = card.querySelector('.sticker-check');
        if (chk && chk.checked) {
            card.classList.add('is-selected');
        } else {
            card.classList.remove('is-selected');
        }
    });
}

function toggleSelectAll() {
    const checkboxes = Array.from(document.querySelectorAll('.sticker-check'));
    const anyUnchecked = checkboxes.some(c => !c.checked);
    checkboxes.forEach(c => c.checked = anyUnchecked);
    updateSelectedCount();
}

function clearSelection() {
    document.querySelectorAll('.sticker-check').forEach(c => c.checked = false);
    updateSelectedCount();
}

function printSelectedStickers() {
    const checked = Array.from(document.querySelectorAll('.sticker-check:checked'));
    if (checked.length === 0) {
        alert('Please check at least one label sticker to print.');
        return;
    }

    document.querySelectorAll('.fabric-sticker-card').forEach(c => {
        const chk = c.querySelector('.sticker-check');
        if (chk && chk.checked) {
            c.classList.remove('print-hidden');
        } else {
            c.classList.add('print-hidden');
        }
    });

    window.print();

    setTimeout(() => {
        document.querySelectorAll('.fabric-sticker-card').forEach(c => c.classList.remove('print-hidden'));
    }, 600);
}

function printAllStickers() {
    document.body.classList.remove('single-print-mode');
    document.querySelectorAll('.fabric-sticker-card').forEach(c => c.classList.remove('print-hidden', 'print-target'));
    window.print();
}

// -------------------------------------------------------------
// 3. DELETE SINGLE / SELECTED LABELS
// -------------------------------------------------------------
function deleteSingleSticker(idx) {
    const card = document.getElementById('card_' + idx);
    if (!card) return;

    const piece = card.getAttribute('data-piece') || (idx + 1);
    const code = card.getAttribute('data-code') || '';
    const rollId = card.getAttribute('data-roll-id') || (stickersData[idx] ? stickersData[idx].roll_id : 0);
    const sequence = card.getAttribute('data-seq') || (stickersData[idx] ? stickersData[idx].seq : 0);

    if (!confirm(`Are you sure you want to delete label Piece #${piece} (${code})?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('sequences[]', sequence);
    formData.append('ajax', '1');

    fetch(deleteExtraUrlBase + rollId, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + (data.message || 'Failed to delete label.'));
            return;
        }

        // Animate out card
        card.style.transition = 'all 0.3s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.8)';

        setTimeout(() => {
            card.remove();
            stickersData.splice(idx, 1);
            refreshStickersFromDOM();
            showToast(data.message || 'Label deleted successfully.');
        }, 300);
    })
    .catch(err => {
        alert('Request failed: ' + err.message);
    });
}

function deleteSelectedStickers() {
    const checked = Array.from(document.querySelectorAll('.sticker-check:checked'));
    if (checked.length === 0) {
        alert('Please check at least one label to delete.');
        return;
    }

    const count = checked.length;
    if (!confirm(`Are you sure you want to delete the ${count} selected label(s)?`)) {
        return;
    }

    const groups = new Map();
    for (const checkbox of checked) {
        const card = checkbox.closest('.fabric-sticker-card');
        if (!card) continue;

        const rollId = card.getAttribute('data-roll-id');
        const sequence = card.getAttribute('data-seq');
        if (!rollId || !sequence) {
            alert('Unable to identify a selected label. Refresh the page and try again.');
            return;
        }

        if (!groups.has(rollId)) groups.set(rollId, { sequences: [], cards: [] });
        groups.get(rollId).sequences.push(sequence);
        groups.get(rollId).cards.push(card);
    }

    Promise.all(Array.from(groups.entries()).map(async ([rollId, group]) => {
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        formData.append('ajax', '1');
        group.sequences.forEach(sequence => formData.append('sequences[]', sequence));

        const response = await fetch(deleteExtraUrlBase + rollId, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        const data = await response.json();
        return { group, data };
    }))
    .then(results => {
        let removedCount = 0;
        const errors = [];
        results.forEach(({ group, data }) => {
            if (!data.success) {
                errors.push(data.message || 'Failed to delete one or more labels.');
                return;
            }
            group.cards.forEach(card => card.remove());
            removedCount += group.cards.length;
        });

        if (removedCount > 0) {
            refreshStickersFromDOM();
            showToast(`Successfully removed ${removedCount} label(s).`);
        }
        if (errors.length > 0) alert('Some labels could not be deleted: ' + errors.join(' '));
    })
    .catch(err => {
        alert('Request failed: ' + err.message);
    });
}

function refreshStickersFromDOM() {
    const cards = Array.from(document.querySelectorAll('.fabric-sticker-card'));
    const total = cards.length;
    const stickersByCode = new Map(stickersData.map(sticker => [sticker.code, sticker]));
    stickersData = cards
        .map(card => stickersByCode.get(card.getAttribute('data-code')))
        .filter(Boolean);
    updatePieceCountsByRoll();

    document.getElementById('totalStickersBadge').textContent = total + ' Sticker' + (total === 1 ? '' : 's');
    document.getElementById('allCountSpan').textContent = total;

    cards.forEach((card, newIdx) => {
        card.id = 'card_' + newIdx;
        card.setAttribute('data-idx', newIdx);

        const chk = card.querySelector('.sticker-check');
        if (chk) {
            chk.setAttribute('data-idx', newIdx);
        }

        const printBtn = card.querySelector('.btn-single-print');
        if (printBtn) printBtn.setAttribute('onclick', `printSingleSticker(${newIdx})`);

        const delBtn = card.querySelector('.btn-single-delete');
        if (delBtn) delBtn.setAttribute('onclick', `deleteSingleSticker(${newIdx})`);

    });

    updateSelectedCount();
}

function updatePieceCountsByRoll() {
    const cardsByRoll = new Map();
    const stickersByCode = new Map(stickersData.map(sticker => [sticker.code, sticker]));
    document.querySelectorAll('.fabric-sticker-card').forEach(card => {
        const rollId = card.getAttribute('data-roll-id');
        if (!cardsByRoll.has(rollId)) cardsByRoll.set(rollId, []);
        cardsByRoll.get(rollId).push(card);
    });

    cardsByRoll.forEach(cards => {
        cards.forEach((card, index) => {
            const sticker = stickersByCode.get(card.getAttribute('data-code'));
            if (!sticker) return;
            sticker.piece_index = index + 1;
            sticker.total_qty = cards.length;
            card.setAttribute('data-piece', String(index + 1));
            const toolbarIndex = card.querySelector('.sticker-card-toolbar .fw-semibold');
            if (toolbarIndex) toolbarIndex.textContent = '#' + (index + 1);
            const pieceCount = card.querySelector('.piece-count-item');
            if (pieceCount) pieceCount.textContent = `Piece ${index + 1} of ${cards.length}`;
        });
    });
}

// -------------------------------------------------------------
// 4. FILTER / SEARCH STICKERS
// -------------------------------------------------------------
function filterStickers(term) {
    const cleanTerm = term.trim().toUpperCase();
    document.querySelectorAll('.fabric-sticker-card').forEach(card => {
        if (!cleanTerm) {
            card.style.display = 'flex';
            return;
        }
        const code = (card.getAttribute('data-code') || '').toUpperCase();
        const piece = (card.getAttribute('data-piece') || '').toUpperCase();
        const roll = (card.getAttribute('data-roll') || '').toUpperCase();

        if (code.includes(cleanTerm) || piece === cleanTerm || roll.includes(cleanTerm) || ('PIECE ' + piece).includes(cleanTerm)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function clearFilter() {
    const input = document.getElementById('stickerSearchInput');
    if (input) input.value = '';
    filterStickers('');
}

// -------------------------------------------------------------
// 5. GENERATE EXTRA CONTINUATION LABEL VIA AJAX
// -------------------------------------------------------------
function openAddExtraLabelModal() {
    const modalEl = document.getElementById('addExtraLabelModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function adjustExtraCount(delta) {
    const input = document.getElementById('extraLabelCountInput');
    let val = parseInt(input.value, 10) || 1;
    val = Math.max(1, Math.min(50, val + delta));
    input.value = val;
}

function submitAddExtraLabel() {
    const rollSelect = document.getElementById('extraLabelRollSelect');
    const rollId = rollSelect.value;
    const countInput = document.getElementById('extraLabelCountInput');
    const count = parseInt(countInput.value, 10) || 1;

    const btn = document.getElementById('btnConfirmAddExtra');
    const spinner = document.getElementById('btnConfirmAddExtraSpinner');
    const icon = document.getElementById('btnConfirmAddExtraIcon');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');

    const formData = new FormData();
    formData.append('_csrf_token', csrfToken);
    formData.append('count', count);
    formData.append('ajax', '1');

    fetch(addExtraUrlBase + rollId, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');

        if (!data.success) {
            alert('Error: ' + (data.message || 'Failed to generate extra label.'));
            return;
        }

        const modalEl = document.getElementById('addExtraLabelModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        if (data.new_stickers && data.new_stickers.length > 0) {
            const grid = document.getElementById('stickersGrid');
            const firstNewSticker = data.new_stickers[0];
            const unitWeight = Number(firstNewSticker.weight) || 0;
            stickersData.forEach((sticker, index) => {
                if (String(sticker.roll_id) !== String(rollId)) return;
                sticker.weight = unitWeight;
                sticker.roll_weight = Number(firstNewSticker.roll_weight) || 0;
                sticker.qr_payload = sticker.code;
                sticker.total_qty = Number(firstNewSticker.total_qty) || sticker.total_qty;
                renderQRCodeForIndex(index, sticker.code);
                const card = document.getElementById('card_' + index);
                const pieceCount = card ? card.querySelector('.piece-count-item') : null;
                if (pieceCount) pieceCount.textContent = `Piece ${sticker.piece_index} of ${sticker.total_qty}`;
                const weightItem = card ? card.querySelector('.weight-item') : null;
                if (weightItem) {
                    weightItem.innerHTML = `<strong>Piece Weight:</strong> ${unitWeight.toFixed(2)} kg`;
                } else if (card && unitWeight > 0) {
                    const row = card.querySelector('.spec-row');
                    if (row) {
                        const item = document.createElement('div');
                        item.className = 'spec-item weight-item';
                        item.innerHTML = `<strong>Piece Weight:</strong> ${unitWeight.toFixed(2)} kg`;
                        row.appendChild(item);
                    }
                }
            });

            data.new_stickers.forEach(stk => {
                const newIdx = stickersData.length;
                stickersData.push(stk);

                const card = document.createElement('div');
                card.className = 'fabric-sticker-card newly-added';
                card.id = 'card_' + newIdx;
                card.setAttribute('data-idx', newIdx);
                card.setAttribute('data-code', stk.code);
                card.setAttribute('data-seq', stk.seq);
                card.setAttribute('data-piece', stk.piece_index);
                card.setAttribute('data-roll', stk.roll_number);
                card.setAttribute('data-roll-id', stk.roll_id);

                const safeColor = stk.color ? `<div class="spec-item"><strong>Color:</strong> ${stk.color}</div>` : '';
                const safeDesign = stk.design_name ? `<div class="spec-item"><strong>Design:</strong> ${stk.design_name}</div>` : '';
                const safeWeight = stk.weight > 0 ? `<div class="spec-item weight-item"><strong>Piece Weight:</strong> ${Number(stk.weight).toFixed(2)} kg</div>` : '';

                card.innerHTML = `
                    <div class="sticker-card-toolbar no-print">
                        <label class="form-check-label d-flex align-items-center gap-1 cursor-pointer mb-0">
                            <input type="checkbox" class="form-check-input sticker-check" data-idx="${newIdx}" onchange="updateSelectedCount()">
                            <span class="fw-semibold text-secondary">#${stk.piece_index}</span>
                        </label>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-dark btn-single-print" onclick="printSingleSticker(${newIdx})" title="Print ONLY this label">
                                <i class="bi bi-printer-fill me-1"></i> Print This Label
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-single-delete" onclick="deleteSingleSticker(${newIdx})" title="Delete this label">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="sticker-header d-flex justify-content-between align-items-center">
                        <div class="sticker-title text-truncate" title="GRM RAW MATERIAL">GRM RAW MATERIAL</div>
                        <div class="sticker-supplier text-truncate" style="max-width: 120px;" title="${stk.supplier_name}">${stk.supplier_name}</div>
                    </div>
                    <div class="sticker-body">
                        <div class="qr-wrapper">
                            <div class="qr-canvas-box" id="qr_canvas_${newIdx}"></div>
                            <div class="code-text">${stk.code}</div>
                        </div>
                        <div class="details-col">
                            <div class="roll-badge" title="${stk.roll_number}">${stk.roll_number}</div>
                            <div class="material-text" title="${stk.material_type}">${stk.material_type}</div>
                            <div class="spec-row">
                                ${safeColor}
                                ${safeDesign}
                                ${safeWeight}
                                <div class="spec-item piece-count-item">Piece ${stk.piece_index} of ${stk.total_qty}</div>
                            </div>
                        </div>
                    </div>
                `;

                grid.appendChild(card);
                renderQRCodeForIndex(newIdx, stk.code);
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            refreshStickersFromDOM();
            showToast(data.message || `Successfully generated ${data.new_stickers.length} continuation label(s)! Click "Print This Label" on the new card.`);
        }
    })
    .catch(err => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
        alert('Request failed: ' + err.message);
    });
}

function showToast(msg) {
    const toast = document.getElementById('toastNotification');
    const msgSpan = document.getElementById('toastMessage');
    if (toast && msgSpan) {
        msgSpan.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + msg;
        toast.classList.remove('d-none');
        toast.classList.add('show');
        setTimeout(() => hideToast(), 8000);
    }
}

function hideToast() {
    const toast = document.getElementById('toastNotification');
    if (toast) {
        toast.classList.remove('show');
        toast.classList.add('d-none');
    }
}
</script>

</body>
</html>