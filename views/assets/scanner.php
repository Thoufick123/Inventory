<?php
/**
 * Dedicated Asset QR & Multi-Tag Fast Finder Station
 */
?>

<!-- HTML5-QRCode Library for Web & Mobile Camera Scanning -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<style>
.scanner-station-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3), 0 8px 10px -6px rgba(15, 23, 42, 0.2);
}
.scanner-glow-icon {
    width: 48px;
    height: 48px;
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #60a5fa;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
}
.scanner-search-input-lg {
    background: #0f172a !important;
    border: 2px solid #334155 !important;
    color: #f8fafc !important;
    font-size: 1.15rem;
    letter-spacing: 0.5px;
    padding: 14px 20px;
    border-radius: 12px 0 0 12px;
    transition: all 0.2s ease;
}
.scanner-search-input-lg:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25) !important;
}
.scanner-search-input-lg::placeholder {
    color: #64748b;
    font-size: 0.95rem;
}
.camera-reader-box {
    border-radius: 14px;
    overflow: hidden;
    background: #000;
    position: relative;
    border: 2px solid #3b82f6;
    box-shadow: 0 0 25px rgba(59, 130, 246, 0.3);
}
.asset-preview-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    animation: slideDownFade 0.3s ease-out forwards;
}
@keyframes slideDownFade {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.tag-pill {
    font-size: 0.75rem;
    font-family: monospace;
    padding: 3px 8px;
    border-radius: 6px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #334155;
}
.candidate-row:hover {
    background-color: #f8fafc;
    cursor: pointer;
}
</style>

<!-- Page Header with Tab Navigation -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-qr-code-scan text-primary"></i> Asset QR &amp; Multi-Tag Scanner Station
        </h2>
        <span class="text-secondary small">Real-time hardware inspection station across 2D QR Codes, Asset Tags, Serials, Models, and Invoices.</span>
    </div>
    <div class="d-flex align-items-center flex-wrap gap-2">
        <a href="<?= url('asset/list') ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-list-columns-reverse me-1"></i> View Asset List
        </a>
        <a href="<?= url('asset/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-1">
            <i class="bi bi-plus-circle me-1"></i> Register New Asset
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<!-- DEDICATED FULL SCANNER HERO WORKSTATION                                   -->
<!-- ========================================================================= -->
<div class="card scanner-station-hero rounded-4 p-4 p-md-5 mb-4 text-white">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="scanner-glow-icon">
                <i class="bi bi-upc-scan"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                    Live Hardware Scanner &amp; Tag Intelligence
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 fs-xs fw-normal px-2 py-1">
                        <i class="bi bi-broadcast me-1"></i> Scanner Gun Active
                    </span>
                </h4>
                <div class="text-secondary small mt-1">
                    Point your camera or pull the barcode gun trigger to inspect full maintenance history, book value, and location.
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            <button type="button" id="btn_toggle_camera" class="btn btn-outline-info rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2" onclick="toggleCameraScanner()">
                <i class="bi bi-camera-video-fill"></i> <span>Start Camera Scanner</span>
            </button>
            <button type="button" class="btn btn-outline-light rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1" onclick="clearStationFinder()">
                <i class="bi bi-arrow-counterclockwise"></i> Clear / Reset
            </button>
        </div>
    </div>

    <!-- Collapsible Camera Scanner Viewport -->
    <div id="camera_scanner_container" class="mb-4 d-none">
        <div class="card bg-black border border-primary border-opacity-50 rounded-4 p-3 shadow-lg">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="small text-info fw-semibold">
                    <i class="bi bi-record-circle text-danger me-1"></i> Live Optical Viewfinder: Aim at Asset QR Sticker or 1D Barcode
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary text-white rounded-pill px-3" onclick="switchCamera()">
                        <i class="bi bi-arrow-repeat me-1"></i> Flip Camera
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="toggleCameraScanner()">
                        <i class="bi bi-x-lg me-1"></i> Close Viewfinder
                    </button>
                </div>
            </div>
            <div id="asset_camera_reader" style="width: 100%; max-width: 520px; margin: 0 auto;" class="camera-reader-box"></div>
        </div>
    </div>

    <!-- Main Scanner Input -->
    <div class="row g-2 mb-3">
        <div class="col-12">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary border-end-0 text-primary px-3">
                    <i class="bi bi-search fs-4"></i>
                </span>
                <input type="text" 
                       id="station_qr_input" 
                       class="form-control scanner-search-input-lg font-monospace" 
                       placeholder="Scan QR sticker or type Tag Code (AST-PRD-0001), Serial # (BR-2026-9981), Model (DDL-8700)..." 
                       autocomplete="off" 
                       autofocus>
                <button type="button" class="btn btn-primary px-4 fw-bold fs-6 d-inline-flex align-items-center gap-2" onclick="performStationLookup()">
                    <i class="bi bi-box-arrow-in-right"></i> <span>Look Up</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Supported Identifier Formats & Quick Chips -->
    <div class="d-flex flex-wrap align-items-center gap-2 small text-secondary">
        <span class="fw-semibold text-light"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Test Tags:</span>
        <?php 
            $sampleCodes = array_slice(array_filter(array_column($sampleAssets ?? [], 'asset_code')), 0, 5);
            if (empty($sampleCodes)) $sampleCodes = ['AST-PRD-0001', 'AST-WHS-0001', 'AST-SHP-0001', 'AST-OFF-0001'];
            foreach ($sampleCodes as $sc): 
        ?>
            <a href="javascript:void(0)" class="badge bg-dark border border-secondary text-light text-decoration-none px-2 py-1" onclick="quickFillTag('<?= e($sc) ?>')">
                <i class="bi bi-qr-code me-1 text-info"></i><?= e($sc) ?>
            </a>
        <?php endforeach; ?>
        <span class="ms-auto text-muted small"><i class="bi bi-keyboard me-1"></i> Auto-detects barcode gun trigger &amp; Enter key</span>
    </div>

    <!-- Result Division: Live Asset Card Display (Dynamically Populated) -->
    <div id="station_result_wrapper" class="mt-4 d-none text-dark">
        <!-- Spinner -->
        <div id="station_loading_spinner" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
            <div class="text-light small mt-3 fw-semibold">Searching enterprise database across QR codes, tags, serial numbers, and models...</div>
        </div>

        <!-- Single Match Card -->
        <div id="station_asset_card" class="asset-preview-card p-4 d-none">
            <!-- Injected by JavaScript -->
        </div>

        <!-- Multi-Matches Candidates Table -->
        <div id="station_candidates_box" class="card border-0 shadow-sm rounded-3 mt-3 d-none">
            <div class="card-header bg-light py-2 px-3 border-0 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark small" id="station_candidates_title"><i class="bi bi-collection text-primary me-1"></i> Multiple Matching Assets Found</span>
                <span class="badge bg-secondary" id="station_candidates_count">0</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Asset Code / Tag</th>
                            <th>Equipment Name</th>
                            <th>Category</th>
                            <th>Current Location</th>
                            <th>Serial #</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="station_candidates_tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Error / Not Found Alert -->
        <div id="station_not_found" class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 d-none mb-0">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-1">No Matching Asset Located</h6>
                <div class="small" id="station_not_found_msg">No equipment found matching the scanned QR code or tag number.</div>
            </div>
            <a href="<?= url('asset/create') ?>" class="btn btn-sm btn-dark ms-auto"><i class="bi bi-plus-circle me-1"></i> Register as New Asset</a>
        </div>
    </div>

    <!-- Recent Session Scans History Strip -->
    <div id="station_recent_scans" class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-none">
        <div class="d-flex align-items-center gap-2 small">
            <span class="text-secondary fw-semibold"><i class="bi bi-clock-history me-1 text-primary"></i> Recent Scans in this Session:</span>
            <div id="station_history_badges" class="d-flex flex-wrap gap-2"></div>
        </div>
    </div>
</div>

<!-- Modal: Inter-Unit Transfer -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('asset/transfers/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="asset_id" id="trf_asset_id">
                <input type="hidden" name="redirect_back" value="<?= e($_SERVER['REQUEST_URI'] ?? url('asset/scanner')) ?>">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-left-right me-2"></i> Inter-Unit Asset Transfer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-muted small">Asset Selected:</div>
                        <div class="fw-bold text-dark" id="trf_asset_name">-</div>
                        <div class="text-muted small mt-1">Current Location: <span class="text-primary fw-semibold" id="trf_current_loc">-</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Destination Facility / Unit <span class="text-danger">*</span></label>
                        <select name="target_unit" class="form-select" required>
                            <option value="">-- Choose Destination --</option>
                            <optgroup label="🏭 Production Units">
                                <?php foreach (($unitsMap['production'] ?? []) as $pu): ?>
                                    <option value="PU_<?= $pu['id'] ?>"><?= e($pu['name']) ?> (<?= e($pu['code'] ?: 'PU'.$pu['id']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🏢 Central Warehouses">
                                <?php foreach (($unitsMap['warehouses'] ?? []) as $wh): ?>
                                    <option value="WH_<?= $wh['id'] ?>"><?= e($wh['name']) ?> (<?= e($wh['code'] ?: 'WH'.$wh['id']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🛍️ Retail Flagship Shops">
                                <?php foreach (($unitsMap['shops'] ?? []) as $sh): ?>
                                    <option value="SH_<?= $sh['id'] ?>"><?= e($sh['name']) ?> (<?= e($sh['code'] ?: 'SH'.$sh['id']) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="🏛️ Corporate Office">
                                <option value="OFFICE_1">GRM Head Office &amp; Admin (HQ)</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Target Room / Floor Area</label>
                        <input type="text" name="to_floor_area" class="form-control" placeholder="e.g. Line 3, Counter 2, Server Room">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Reason for Relocation <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Capacity expansion, seasonal reallocation, repair handover" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Confirm Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Maintenance Logging -->
<div class="modal fade" id="maintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('asset/maintenance/store') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="asset_id" id="mnt_asset_id">
                <input type="hidden" name="redirect_back" value="<?= e($_SERVER['REQUEST_URI'] ?? url('asset/scanner')) ?>">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i> Log Service / Repair Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="text-muted small">Asset:</div>
                        <div class="fw-bold text-dark" id="mnt_asset_name">-</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Service Type <span class="text-danger">*</span></label>
                            <select name="service_type" class="form-select form-select-sm" required>
                                <option value="ROUTINE_PREVENTIVE">Routine Preventive Maintenance</option>
                                <option value="BREAKDOWN_REPAIR">Breakdown Repair</option>
                                <option value="CALIBRATION">Calibration &amp; Safety Testing</option>
                                <option value="PART_REPLACEMENT">Spare Part Replacement</option>
                                <option value="ANNUAL_AMC">Annual AMC Servicing</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Service Date <span class="text-danger">*</span></label>
                            <input type="date" name="service_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Issue / Maintenance Scope <span class="text-danger">*</span></label>
                        <textarea name="issue_description" class="form-control" rows="2" placeholder="Describe issue or service required..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Action Taken</label>
                        <textarea name="action_taken" class="form-control" rows="2" placeholder="Work done, parts replaced..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Vendor / Agency</label>
                            <input type="text" name="serviced_by_vendor" class="form-control form-control-sm" placeholder="e.g. Machinery Corp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Technician</label>
                            <input type="text" name="technician_name" class="form-control form-control-sm" placeholder="e.g. R. Kumar">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Cost (₹)</label>
                            <input type="number" step="0.01" name="service_cost" class="form-control form-control-sm" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Downtime (Hrs)</label>
                            <input type="number" step="0.5" name="downtime_hours" class="form-control form-control-sm" value="0.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Next Due Date</label>
                            <input type="date" name="next_due_date" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Condition Post-Service</label>
                            <select name="condition_state" class="form-select form-select-sm">
                                <option value="EXCELLENT">Excellent</option>
                                <option value="GOOD" selected>Good</option>
                                <option value="FAIR">Fair</option>
                                <option value="POOR">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Operational Status</label>
                            <select name="asset_status" class="form-select form-select-sm">
                                <option value="IN_USE" selected>In Use (Ready)</option>
                                <option value="IN_MAINTENANCE">In Maintenance</option>
                                <option value="IDLE">Idle</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4 fw-semibold"><i class="bi bi-check-circle me-1"></i> Save Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// State
let html5QrScanner = null;
let isCameraActive = false;
let currentCameraId = null;
let availableCameras = [];
let scanHistory = [];

function playScannerBeep() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime);
        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.12);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.12);
    } catch(e) {}
}

function openTransferModal(asset) {
    document.getElementById('trf_asset_id').value = asset.id;
    document.getElementById('trf_asset_name').textContent = (asset.asset_code || '') + ' - ' + (asset.name || '');
    document.getElementById('trf_current_loc').textContent = (asset.location_display || '') + (asset.floor_area ? ' (' + asset.floor_area + ')' : '');
    new bootstrap.Modal(document.getElementById('transferModal')).show();
}

function openMaintenanceModal(asset) {
    document.getElementById('mnt_asset_id').value = asset.id;
    document.getElementById('mnt_asset_name').textContent = (asset.asset_code || '') + ' - ' + (asset.name || '');
    new bootstrap.Modal(document.getElementById('maintenanceModal')).show();
}

function quickFillTag(code) {
    const input = document.getElementById('station_qr_input');
    input.value = code;
    performStationLookup();
}

function clearStationFinder() {
    document.getElementById('station_qr_input').value = '';
    document.getElementById('station_result_wrapper').classList.add('d-none');
    document.getElementById('station_asset_card').classList.add('d-none');
    document.getElementById('station_candidates_box').classList.add('d-none');
    document.getElementById('station_not_found').classList.add('d-none');
    document.getElementById('station_qr_input').focus();
}

function performStationLookup(forcedCode = null) {
    const input = document.getElementById('station_qr_input');
    const code = (forcedCode !== null ? forcedCode : input.value).trim();
    if (!code) {
        input.focus();
        return;
    }

    const wrapper = document.getElementById('station_result_wrapper');
    const spinner = document.getElementById('station_loading_spinner');
    const card = document.getElementById('station_asset_card');
    const candidatesBox = document.getElementById('station_candidates_box');
    const notFound = document.getElementById('station_not_found');

    wrapper.classList.remove('d-none');
    spinner.classList.remove('d-none');
    card.classList.add('d-none');
    candidatesBox.classList.add('d-none');
    notFound.classList.add('d-none');

    fetch('<?= url('api/asset/lookup') ?>?code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');

            if (data.success && data.asset) {
                playScannerBeep();
                renderAssetIntelligenceCard(data.asset);
                card.classList.remove('d-none');

                if (data.matches && data.matches.length > 1) {
                    renderCandidatesTable(data.matches, data.asset.id);
                    candidatesBox.classList.remove('d-none');
                }

                addToScanHistory(data.asset);
            } else {
                notFound.classList.remove('d-none');
                document.getElementById('station_not_found_msg').innerHTML = `No asset matched code/tag: <strong>${escapeHtml(code)}</strong>. Check spelling, serial number, or QR label.`;
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            notFound.classList.remove('d-none');
            document.getElementById('station_not_found_msg').textContent = 'Server communication error during asset lookup.';
        });
}

function renderAssetIntelligenceCard(asset) {
    const card = document.getElementById('station_asset_card');
    
    let statusBadge = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> In Use (Active)</span>';
    if (asset.status === 'IN_MAINTENANCE') {
        statusBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-tools me-1"></i> Under Maintenance</span>';
    } else if (asset.status === 'DAMAGED') {
        statusBadge = '<span class="badge bg-danger"><i class="bi bi-exclamation-octagon me-1"></i> Damaged / Defective</span>';
    } else if (asset.status === 'IDLE') {
        statusBadge = '<span class="badge bg-secondary"><i class="bi bi-pause-circle me-1"></i> Idle Reserve</span>';
    } else if (asset.status === 'DISPOSED') {
        statusBadge = '<span class="badge bg-dark"><i class="bi bi-trash me-1"></i> Disposed</span>';
    }

    let condBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">Good</span>';
    if (asset.condition_state === 'EXCELLENT') {
        condBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Excellent</span>';
    } else if (asset.condition_state === 'FAIR') {
        condBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Fair</span>';
    } else if (asset.condition_state === 'POOR' || asset.condition_state === 'CRITICAL') {
        condBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Critical/Poor</span>';
    }

    const bookValueFormatted = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(asset.current_book_value || 0);
    const purchaseCostFormatted = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(asset.purchase_cost || 0);

    card.innerHTML = `
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 pb-3 border-bottom mb-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-light rounded-3 border text-primary fs-3">
                    <i class="bi ${asset.category_icon || 'bi-cpu'}"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <span class="badge bg-dark font-monospace fs-6 px-2 py-1">${escapeHtml(asset.asset_code)}</span>
                        ${statusBadge}
                        ${condBadge}
                        <span class="badge bg-light text-secondary border">${escapeHtml(asset.category_name || 'General Asset')}</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-0">${escapeHtml(asset.name)}</h4>
                    <div class="text-muted small mt-1">
                        ${asset.brand ? 'Brand: <strong>' + escapeHtml(asset.brand) + '</strong> &bull; ' : ''}
                        ${asset.model_number ? 'Model: <code>' + escapeHtml(asset.model_number) + '</code> &bull; ' : ''}
                        ${asset.serial_number ? 'Serial #: <code class="text-primary fw-bold">' + escapeHtml(asset.serial_number) + '</code>' : ''}
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2">
                <a href="<?= url('asset/view/') ?>${asset.id}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-eye"></i> 360° Profile
                </a>
                <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" onclick='openTransferModal(${JSON.stringify(asset)})'>
                    <i class="bi bi-arrow-left-right"></i> Transfer
                </button>
                <button type="button" class="btn btn-outline-warning text-dark btn-sm d-inline-flex align-items-center gap-1" onclick='openMaintenanceModal(${JSON.stringify(asset)})'>
                    <i class="bi bi-wrench"></i> Log Service
                </button>
                <a href="<?= url('asset/label/') ?>${asset.id}" target="_blank" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-printer"></i> QR Label
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <div class="p-3 bg-light rounded-3 h-100 border">
                    <div class="text-uppercase text-muted small fw-bold mb-2"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Location &amp; Custody</div>
                    <div class="fw-bold text-dark fs-6">${escapeHtml(asset.location_display || 'Unassigned')}</div>
                    <div class="text-secondary small mt-1">
                        Floor / Area: <strong>${escapeHtml(asset.floor_area || 'Main Area')}</strong>
                    </div>
                    <div class="text-secondary small mt-1">
                        Custodian In-Charge: <strong>${escapeHtml(asset.custodian_name || 'Unit Manager')}</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="p-3 bg-light rounded-3 h-100 border">
                    <div class="text-uppercase text-muted small fw-bold mb-2"><i class="bi bi-cash-stack text-success me-1"></i> Valuation &amp; Book Value</div>
                    <div class="fw-bold text-success fs-5 font-monospace">${bookValueFormatted}</div>
                    <div class="text-secondary small mt-1">
                        Purchase Cost: <strong>${purchaseCostFormatted}</strong> &bull; Dep Rate: <strong>${asset.depreciation_rate_annual || 15}% / yr</strong>
                    </div>
                    <div class="text-secondary small mt-1">
                        Invoice / Bill: <code>${escapeHtml(asset.invoice_number || 'N/A')}</code> &bull; Purchased: ${asset.purchase_date || 'N/A'}
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-12">
                <div class="p-3 bg-light rounded-3 h-100 border">
                    <div class="text-uppercase text-muted small fw-bold mb-2"><i class="bi bi-shield-check text-primary me-1"></i> Warranty &amp; Health</div>
                    <div class="text-secondary small">
                        Warranty: <strong>${asset.warranty_expiry ? asset.warranty_expiry : 'Not Registered'}</strong>
                    </div>
                    <div class="text-secondary small mt-1">
                        Useful Life: <strong>${asset.useful_life_years || 5} Years</strong> &bull; Total Downtime: <strong>${asset.total_downtime_hours || 0} hrs</strong>
                    </div>
                    <div class="text-secondary small mt-1">
                        Barcode / QR Tag: <code class="tag-pill text-dark">${escapeHtml(asset.barcode_qr || asset.asset_code)}</code>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderCandidatesTable(matches, currentSelectedId) {
    const tbody = document.getElementById('station_candidates_tbody');
    document.getElementById('station_candidates_count').textContent = matches.length + ' Candidates';
    
    let html = '';
    matches.forEach(m => {
        const isSel = m.id === currentSelectedId;
        html += `
            <tr class="candidate-row ${isSel ? 'table-primary' : ''}" onclick="selectCandidateAsset(${m.id})">
                <td class="ps-3 fw-bold font-monospace text-primary">${escapeHtml(m.asset_code)}</td>
                <td class="fw-semibold text-dark">${escapeHtml(m.name)}</td>
                <td><span class="badge bg-light text-secondary border">${escapeHtml(m.category_name || '')}</span></td>
                <td>${escapeHtml(m.location_display || '')}</td>
                <td><code>${escapeHtml(m.serial_number || '-')}</code></td>
                <td><span class="badge ${m.status === 'IN_USE' ? 'bg-success' : 'bg-warning text-dark'}">${escapeHtml(m.status)}</span></td>
                <td class="text-end pe-3">
                    <button type="button" class="btn btn-xs ${isSel ? 'btn-primary' : 'btn-outline-primary'} py-1 px-2" onclick="event.stopPropagation(); selectCandidateAsset(${m.id})">
                        ${isSel ? '<i class="bi bi-check-lg"></i> Active' : '<i class="bi bi-arrow-right"></i> Inspect'}
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function selectCandidateAsset(assetId) {
    fetch('<?= url('api/asset/lookup') ?>?code=' + encodeURIComponent(assetId))
        .then(res => res.json())
        .then(data => {
            if (data.success && data.asset) {
                renderAssetIntelligenceCard(data.asset);
                document.getElementById('station_asset_card').classList.remove('d-none');
                addToScanHistory(data.asset);
                renderCandidatesTable(data.matches || [data.asset], data.asset.id);
            }
        });
}

function addToScanHistory(asset) {
    if (!scanHistory.find(item => item.id === asset.id)) {
        scanHistory.unshift(asset);
        if (scanHistory.length > 8) scanHistory.pop();
    }
    renderScanHistory();
}

function renderScanHistory() {
    const container = document.getElementById('station_recent_scans');
    const badgesDiv = document.getElementById('station_history_badges');
    if (scanHistory.length === 0) {
        container.classList.add('d-none');
        return;
    }
    container.classList.remove('d-none');
    let html = '';
    scanHistory.forEach(a => {
        html += `
            <a href="javascript:void(0)" class="badge bg-dark border border-secondary text-light text-decoration-none px-2 py-1 d-inline-flex align-items-center gap-1" onclick="performStationLookup('${escapeHtml(a.asset_code)}')">
                <i class="bi bi-qr-code text-primary"></i> ${escapeHtml(a.asset_code)} (${escapeHtml(a.name).substring(0, 14)}...)
            </a>
        `;
    });
    badgesDiv.innerHTML = html;
}

function toggleCameraScanner() {
    const container = document.getElementById('camera_scanner_container');
    const btn = document.getElementById('btn_toggle_camera');

    if (isCameraActive) {
        stopCameraScanner();
    } else {
        container.classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-camera-video-off-fill text-danger"></i> <span>Stop Camera</span>';
        btn.classList.replace('btn-outline-info', 'btn-outline-danger');
        startCameraScanner();
    }
}

function startCameraScanner() {
    if (typeof Html5Qrcode === 'undefined') {
        alert("Camera scanner library is loading. Please try again in 2 seconds.");
        return;
    }

    html5QrScanner = new Html5Qrcode("asset_camera_reader");
    const config = { fps: 15, qrbox: { width: 250, height: 250 } };

    Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            availableCameras = devices;
            const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('rear')) || devices[0];
            currentCameraId = backCam.id;

            html5QrScanner.start(
                currentCameraId,
                config,
                (decodedText) => {
                    document.getElementById('station_qr_input').value = decodedText;
                    stopCameraScanner();
                    performStationLookup(decodedText);
                },
                () => {}
            ).then(() => {
                isCameraActive = true;
            }).catch(err => {
                console.error("Camera start error:", err);
                alert("Could not start camera. Please verify camera permissions in your browser.");
                stopCameraScanner();
            });
        } else {
            alert("No camera device found on this system.");
            stopCameraScanner();
        }
    }).catch(err => {
        console.error("Camera error:", err);
        alert("Camera permission denied or camera unavailable.");
        stopCameraScanner();
    });
}

function switchCamera() {
    if (!availableCameras || availableCameras.length <= 1) {
        alert("No secondary camera available to switch.");
        return;
    }
    const nextCam = availableCameras.find(d => d.id !== currentCameraId) || availableCameras[0];
    if (nextCam && html5QrScanner && isCameraActive) {
        html5QrScanner.stop().then(() => {
            currentCameraId = nextCam.id;
            startCameraScanner();
        });
    }
}

function stopCameraScanner() {
    if (html5QrScanner && isCameraActive) {
        html5QrScanner.stop().then(() => {
            html5QrScanner.clear();
            isCameraActive = false;
        }).catch(() => {
            isCameraActive = false;
        });
    }
    document.getElementById('camera_scanner_container').classList.add('d-none');
    const btn = document.getElementById('btn_toggle_camera');
    btn.innerHTML = '<i class="bi bi-camera-video-fill"></i> <span>Start Camera Scanner</span>';
    btn.classList.replace('btn-outline-danger', 'btn-outline-info');
    isCameraActive = false;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('station_qr_input');
    input?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performStationLookup();
        }
    });
});
</script>
