<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-qr-code-scan me-2 text-primary"></i>New Label Batch &amp; QR Generator</h2>
        <span class="text-muted">Select stitching batch by name, dynamically load catalog &amp; variant quantities, and generate scannable 2D QR price tags</span>
    </div>
    <a href="<?= url('production/labels') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Label Batches
    </a>
</div>

<form method="POST" action="<?= url('production/labels/store') ?>" id="labelForm" onsubmit="return validateLabelBatchForm(event)">
    <?= CSRF::field() ?>

    <input type="hidden" name="program_id" id="inputProgramId" value="<?= e($selectedProgram['id'] ?? ($selectedStitching['program_id'] ?? '')) ?>">

    <div class="row g-4">
        <!-- Left Column: Label Batch Details & Stitching Selection -->
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-info-circle me-2 text-primary"></i>1. Label Batch Info
                    </h5>
                </div>
                <div class="card-body p-4">
                    <!-- Batch Number & Status -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Batch Number</label>
                            <input type="text" name="batch_number" id="batchNumber" class="form-control bg-light fw-bold" value="<?= e($suggestedBatchNumber) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="IN_PROGRESS" selected>In Progress</option>
                                <option value="PENDING">Pending</option>
                                <option value="COMPLETED">Completed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Stitching Name Dropdown (Primary Selection) -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label required fw-semibold mb-0">
                                <i class="bi bi-threads text-primary me-1"></i>Select Stitching Name <span class="text-danger">*</span>
                            </label>
                            <span class="badge bg-primary-subtle text-primary border">Source Batch</span>
                        </div>
                        <select name="stitching_batch_id" id="stitchingBatchSelect" class="form-select form-select-lg border-primary fw-semibold" required onchange="this.classList.remove('is-invalid', 'border-danger'); handleStitchingBatchChange(this.value)">
                            <option value="">-- Choose Stitching Name / Batch --</option>
                            <?php foreach ($stitchingBatches as $sb): ?>
                                <?php 
                                    $stitchingDisplayName = !empty($sb['name']) ? $sb['name'] : $sb['batch_number'];
                                    $progStr = !empty($sb['program_number']) ? $sb['program_number'] : ('Program #' . $sb['program_id']);
                                    $qtyStr = !empty($sb['stitched_qty']) ? (number_format($sb['stitched_qty']) . ' pcs Stitched') : '0 pcs';
                                ?>
                                <option value="<?= $sb['id'] ?>" 
                                    <?= ($selectedStitching && $selectedStitching['id'] == $sb['id']) ? 'selected' : '' ?>
                                    data-name="<?= e($stitchingDisplayName) ?>"
                                    data-batch="<?= e($sb['batch_number']) ?>"
                                    data-program-id="<?= e($sb['program_id']) ?>"
                                    data-program-number="<?= e($sb['program_number'] ?? '') ?>"
                                    data-program-name="<?= e($sb['program_name'] ?? ($sb['product_name'] ?? '')) ?>"
                                    data-qty="<?= e($sb['stitched_qty'] ?? 0) ?>">
                                    <?= e($stitchingDisplayName) ?> (<?= e($sb['batch_number']) ?> &bull; <?= e($progStr) ?> &bull; <?= e($qtyStr) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select a stitching batch to automatically fetch catalog attributes, variant items, and stitched quantities.</small>
                        <?php if (empty($stitchingBatches) && !$selectedStitching): ?>
                            <div class="alert alert-warning py-2 px-3 small mt-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i> All stitching batches have already been labeled. <a href="<?= url('production/stitching/create') ?>" class="alert-link">Create a stitching batch</a>.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Stitching & Catalog Summary Box -->
                    <div id="stitchingDetailsBox" class="p-3 bg-light rounded border mb-3" style="<?= $selectedStitching ? '' : 'display: none;' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary px-2 py-1" id="boxBatchNumber"><?= e($selectedStitching['batch_number'] ?? '') ?></span>
                            <span class="badge bg-success-subtle text-success border" id="boxStitchedQty">
                                <i class="bi bi-check2-circle me-1"></i><?= number_format($selectedStitching['stitched_qty'] ?? 0) ?> pcs Stitched
                            </span>
                        </div>
                        <div class="fw-bold text-dark fs-6 mb-1" id="boxStitchingName"><?= e($selectedStitching['name'] ?? 'Stitching Batch') ?></div>
                        <div class="text-muted small mb-2" id="boxProgramInfo">
                            <i class="bi bi-file-earmark-text me-1"></i><?= e($selectedStitching['program_number'] ?? '') ?> — <?= e($selectedStitching['product_name'] ?? '') ?>
                        </div>
                        
                        <!-- Catalog Hierarchy & Multi-Catalog Badges -->
                        <div class="p-3 bg-white rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted fw-bold"><i class="bi bi-diagram-3 me-1 text-info"></i>Catalog Hierarchy:</span>
                                <span class="badge bg-light text-dark border" id="catalogCountBadge"><?= count($distinctCatalogs ?? []) ?> Catalog(s)</span>
                            </div>
                            <div id="catalogBadgesList" class="d-flex flex-column gap-1 mb-1">
                                <?php if (!empty($distinctCatalogs)): ?>
                                    <?php foreach ($distinctCatalogs as $c): ?>
                                        <?php 
                                            $lastSeq = (int)($c['last_sequence'] ?? 0);
                                            $nextStart = $lastSeq + 1;
                                            $totalQty = (int)($c['total_qty'] ?? 0);
                                            $nextEnd = $nextStart + max($totalQty, 1) - 1;
                                        ?>
                                        <div class="p-2 rounded bg-light border mb-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold text-dark fs-7"><?= e($c['name']) ?></span>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-primary-subtle text-primary border"><?= $totalQty ?> pcs</span>
                                                    <?php if ($totalQty > 0): ?>
                                                        <span class="badge bg-success-subtle text-success border ms-1" title="Continuous Sequence Range">#<?= $nextStart ?>–#<?= $nextEnd ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($lastSeq > 0): ?>
                                                <div class="text-muted mt-1" style="font-size:0.72rem;">
                                                    <i class="bi bi-arrow-repeat text-success me-1"></i>Prev ended at <strong>#<?= $lastSeq ?></strong> &bull; Starts at <strong class="text-success">#<?= $nextStart ?></strong>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted mt-1" style="font-size:0.72rem;">
                                                    <i class="bi bi-stars text-primary me-1"></i>Starts from <strong>#1</strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border fs-7 text-wrap text-start" id="boxCatalogHierarchy">
                                        <?= e($catalogHierarchy ?? ($selectedStitching['category_name'] ?? 'Apparel Catalog')) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Variant Quantities & QR Code Actions -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100 d-flex flex-column">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom flex-wrap gap-2">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-qr-code me-2 text-primary"></i>2. Variant QR Code Labels &amp; Quantities
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border px-3 py-2 fs-6" id="totalRequiredBadge">
                            <i class="bi bi-tag me-1"></i>0 Required Tags
                        </span>
                        <button type="button" class="btn btn-outline-dark btn-sm px-3 py-2 fw-semibold shadow-sm" onclick="openPrinterSettingsModal()" title="Calibrate TSC TE244 printer alignment and font sizes">
                            <i class="bi bi-gear-fill me-1 text-primary"></i>Printer & Alignment Settings
                        </button>
                        <button type="button" class="btn btn-primary btn-sm px-3 py-2 fw-semibold shadow-sm" onclick="openQRPrintModal()">
                            <i class="bi bi-printer me-1"></i>Generate &amp; Print QR Labels
                        </button>
                    </div>
                </div>

                <!-- Catalog Filter Tabs -->
                <div id="catalogTabsContainer" class="p-3 bg-light border-bottom" style="<?= (!empty($distinctCatalogs) && count($distinctCatalogs) > 1) ? '' : 'display:none;' ?>">
                    <ul class="nav nav-pills gap-1 flex-wrap" id="catalogPillsNav">
                        <li class="nav-item">
                            <button type="button" class="nav-link active px-3 py-1 fs-7 fw-semibold" onclick="filterVariantsByCatalog('ALL', this)">
                                All Catalogs <span class="badge bg-secondary-subtle text-dark ms-1" id="tabAllCount"><?= count($items) ?></span>
                            </button>
                        </li>
                        <?php if (!empty($distinctCatalogs)): ?>
                            <?php foreach ($distinctCatalogs as $c): ?>
                                <li class="nav-item">
                                    <button type="button" class="nav-link px-3 py-1 fs-7 fw-semibold" onclick="filterVariantsByCatalog('<?= e(addslashes($c['name'])) ?>', this)">
                                        <?= e($c['name']) ?> <span class="badge bg-primary-subtle text-primary ms-1"><?= $c['total_qty'] ?> pcs</span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card-body p-0 flex-grow-1" id="labelItemsContainer">
                    <!-- Empty State -->
                    <div id="noStitchingSelectedState" class="p-5 text-center text-muted" style="<?= $selectedStitching ? 'display: none;' : '' ?>">
                        <div class="mb-3">
                            <div class="d-inline-flex p-3 rounded-circle bg-light">
                                <i class="bi bi-qr-code-scan fs-1 text-primary"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold text-dark">Select a Stitching Name</h5>
                        <p class="text-muted mb-0">Choose a stitching batch from the dropdown on the left to dynamically fetch variant quantities and generate 2D QR price tags.</p>
                    </div>

                    <!-- Table Container -->
                    <div id="variantsTableContainer" class="table-responsive" style="<?= $selectedStitching ? '' : 'display: none;' ?>">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 170px;">Attribute (Size / Color)</th>
                                    <th style="width: 110px;" class="text-center text-success"><i class="bi bi-threads me-1"></i>Stitched</th>
                                    <th style="width: 120px;" class="text-center">Required Tags <span class="text-danger">*</span></th>
                                    <th style="width: 100px;" class="text-center">QR Action</th>
                                </tr>
                            </thead>
                            <tbody id="variantsTableBody">
                                <!-- Populated dynamically by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-top">
                    <a href="<?= url('production/labels') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Save Label Batch
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- QR Code Single Preview Modal -->
<div class="modal fade" id="singleQRModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"><i class="bi bi-qr-code me-2 text-primary"></i>2D QR Code Tag Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="p-2 border rounded bg-white d-inline-block shadow-sm mb-3 position-relative text-start" style="width: 240px;">
                    <div class="d-flex align-items-start gap-2">
                        <div class="text-center flex-shrink-0">
                            <div id="singleQRCanvas" class="bg-white p-1 rounded border"></div>
                            <div class="lbl-damage-code code" id="previewDamageCode">WMF01</div>
                        </div>
                        <div class="overflow-hidden ms-1" style="margin-top: 4px;">
                            <div class="lbl-title text-uppercase">GRM WAREHOUSE</div>
                            <div class="lbl-sku text-truncate" id="previewSKU">SKU-001</div>
                            <div class="lbl-mrp" id="previewBasePrice">MRP: ₹1,499.00</div>
                            <div class="lbl-disc" id="previewDiscPrice">Disc: ₹1,199.00</div>
                        </div>
                    </div>
                </div>
                <div class="text-muted small">Scannable 2D QR Code containing SKU, Style, Size, Color &amp; Price payload.</div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary fw-semibold" onclick="bootstrap.Modal.getInstance(document.getElementById('singleQRModal')).hide(); openExtraLabelModal(currentPreviewIndex);">
                    <i class="bi bi-printer me-1"></i>Print Extra Continue Label
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Extra Label Preview & Print Modal (Next Ascending Continue Labels) -->
<div class="modal fade" id="extraLabelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-printer-fill fs-5 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Print Extra Label — Next Ascending Continue Tag</h5>
                        <small class="text-white-50">Preview and print next sequential barcode label continuing after batch end</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <!-- Variant & Sequence Information Banner -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fs-7" id="extraVariantSize">Size: S</span>
                                <span class="badge bg-primary-subtle text-primary border px-2 py-1 fs-7" id="extraVariantColor">Color: RED</span>
                                <span class="badge bg-info-subtle text-info border px-2 py-1 fs-7" id="extraVariantCat">BSH</span>
                                <div class="fw-bold text-dark mt-1" id="extraVariantCatalogHierarchy">Apparel Catalog</div>
                                <small class="text-muted" id="extraVariantSku">SKU: S-RED</small>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted mb-1">Sequence Status:</div>
                                <span class="badge bg-dark-subtle text-dark border px-2 py-1" id="extraBatchRangeBadge">Batch: Active</span>
                                <span class="badge bg-success px-2 py-1 fs-7 fw-bold" id="extraNextSeqBadge">Next Continue: #1</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controls: Quantity & Next Continuous Codes -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    <i class="bi bi-123 me-1 text-primary"></i>Number of Extra Labels:
                                </label>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" onclick="adjustExtraQty(-1)"><i class="bi bi-dash"></i></button>
                                    <input type="number" id="extraLabelQty" class="form-control text-center fw-bold fs-6" value="1" min="1" max="50" oninput="updateExtraLabelPreview()">
                                    <button type="button" class="btn btn-outline-secondary" onclick="adjustExtraQty(1)"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    <i class="bi bi-tag-fill me-1 text-primary"></i>Next Ascending Code(s):
                                </label>
                                <div class="form-control form-control-sm bg-white font-monospace text-primary fw-bold" id="extraCodesPreviewList" readonly style="min-height: 31px;">
                                    —
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Sticker Preview Container -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center border-bottom">
                        <span class="fw-bold small text-dark"><i class="bi bi-eye me-1 text-primary"></i>Live Thermal Sticker Preview (TSC TE244 / 40mm &times; 22mm)</span>
                        <span class="badge bg-primary-subtle text-primary border small" id="extraStickersCountBadge">1 Sticker</span>
                    </div>
                    <div class="card-body p-3 bg-secondary-subtle text-center">
                        <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap" id="extraStickersPreviewGrid">
                            <!-- Populated dynamically with live sticker cards -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary fw-semibold" onclick="printExtraLabelBrowser()">
                        <i class="bi bi-printer me-1"></i>Browser Print
                    </button>
                    <button type="button" class="btn btn-success fw-semibold shadow-sm px-4" id="btnConfirmPrintExtra" onclick="executePrintExtraLabels()">
                        <i class="bi bi-printer-fill me-1"></i>Print to TSC TE244
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Batch Print Modal & Printable Area -->
<div class="modal fade" id="qrPrintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-printer me-2 text-primary"></i>Batch QR Label Sticker Tags</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-secondary-subtle" id="printableLabelsContainer">
                <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded border flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-tag me-1 text-primary"></i>Ready to Print QR Stickers</h6>
                        <small class="text-muted">Formatted for thermal sticker roll (40mm × 22mm) or A4 sticker sheet printing</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm no-print" role="group" aria-label="Select QR labels to print">
                            <button type="button" class="btn btn-outline-primary" onclick="setAllQRStickersSelected(true)">Select All</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setAllQRStickersSelected(false)">Clear</button>
                        </div>
                        <div class="input-group input-group-sm" style="width: 280px;">
                            <input type="text" id="deleteLabelInput" class="form-control border-danger-subtle" placeholder="Type Label Code (e.g. WMF92605)..." onkeyup="if(event.key==='Enter') removeLabelByCode()">
                            <button type="button" class="btn btn-outline-danger fw-semibold" onclick="removeLabelByCode()">
                                <i class="bi bi-trash me-1"></i>Delete Label
                            </button>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border fs-7 px-3 py-2" id="printableCountBadge">0 / 0 Selected</span>
                        <button type="button" class="btn btn-success fw-semibold shadow-sm" onclick="printQRStickersDirectly()">
                            <i class="bi bi-printer-fill me-1"></i> Print QR Stickers Now
                        </button>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3" id="qrStickersGrid">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TSC TE244 Printer Settings & Live Layout Calibration Modal -->
<div class="modal fade" id="printerSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1280px; width: 95%;">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-sliders me-2 text-warning"></i>TSC TE244 Printer &amp; Dynamic Label Layout Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-4">
                    <!-- Left Side: Interactive Controls -->
                    <div class="col-lg-5 col-md-6">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold py-2"><i class="bi bi-bounding-box me-1 text-primary"></i>1. Sticker Dimensions</div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Sticker Width (mm)</label>
                                        <input type="number" id="cfgWidth" class="form-control form-control-sm" value="40" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Sticker Height (mm)</label>
                                        <input type="number" id="cfgHeight" class="form-control form-control-sm" value="25" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Roll Columns</label>
                                        <select id="cfgColumns" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="2">2 Columns (Dual Roll)</option>
                                            <option value="1">1 Column (Single Roll)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Row Gap (mm)</label>
                                        <input type="number" id="cfgGap" class="form-control form-control-sm" value="2" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <label class="form-label small fw-semibold mb-1 text-primary"><i class="bi bi-arrow-left-right me-1"></i>2nd Sticker (Right Column) X Shift (dots)</label>
                                        <input type="number" id="cfgCol2ShiftDots" class="form-control form-control-sm border-primary" value="0" placeholder="e.g. -10 to move left, +10 to move right" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white fw-bold py-2"><i class="bi bi-qr-code me-1 text-success"></i>2. QR Code Alignment (Left Column)</div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR X (dots)</label>
                                        <input type="number" id="cfgQrX" class="form-control form-control-sm" value="12" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR Y (dots)</label>
                                        <input type="number" id="cfgQrY" class="form-control form-control-sm" value="35" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold mb-1">QR Size</label>
                                        <select id="cfgQrCellSize" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="3">Size 3 (Medium)</option>
                                            <option value="2">Size 2 (Small)</option>
                                            <option value="4">Size 4 (Large)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Code Subtitle X</label>
                                        <input type="number" id="cfgCodeX" class="form-control form-control-sm" value="12" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Code Subtitle Y</label>
                                        <input type="number" id="cfgCodeY" class="form-control form-control-sm" value="125" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white fw-bold py-2"><i class="bi bi-fonts me-1 text-info"></i>3. Text Positions (Right Column)</div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Right Text X Offset</label>
                                        <input type="number" id="cfgTextX" class="form-control form-control-sm" value="115" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">TSPL Font Size</label>
                                        <select id="cfgTextFont" class="form-select form-select-sm" onchange="updateLivePreview()">
                                            <option value="2">Font 2 (Standard)</option>
                                            <option value="1">Font 1 (Small)</option>
                                            <option value="3">Font 3 (Large)</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">Title Y</label>
                                        <input type="number" id="cfgTitleY" class="form-control form-control-sm" value="35" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">SKU Y</label>
                                        <input type="number" id="cfgSkuY" class="form-control form-control-sm" value="62" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">MRP Y</label>
                                        <input type="number" id="cfgMrpY" class="form-control form-control-sm" value="90" oninput="updateLivePreview()">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label small fw-semibold mb-1">Disc Y</label>
                                        <input type="number" id="cfgDiscY" class="form-control form-control-sm" value="118" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Live Visual Dual-Sticker Preview Box -->
                    <div class="col-lg-7 col-md-6 d-flex flex-column">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white fw-bold py-2 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-eye me-1 text-primary"></i>Live Dual-Sticker Preview</span>
                                <span class="badge bg-primary-subtle text-primary border" id="previewDimensionBadge">Dual Roll 40mm × 20mm</span>
                            </div>
                            <div class="card-body p-3 d-flex flex-column align-items-center justify-content-center bg-secondary-subtle rounded m-2 overflow-auto">
                                <div id="liveStickerContainer" class="d-flex align-items-center gap-3 p-3 bg-white rounded border shadow-sm flex-wrap justify-content-center">
                                    <!-- Left Sticker #1 -->
                                    <div id="liveStickerCard1" class="bg-white border border-secondary-subtle rounded shadow-sm position-relative flex-shrink-0" style="width: 240px; height: 130px; font-family: 'Segoe UI', monospace; overflow: hidden; background: #fafafa;">
                                        <div id="badgeSticker1" class="badge bg-light text-dark position-absolute top-0 start-0 m-1 border shadow-xs" style="font-size: 9px; opacity: 0.85;">Left Sticker #1</div>
                                        <div id="prevQRBox1" class="position-absolute border border-dark rounded d-flex align-items-center justify-content-center bg-light text-muted fw-bold" style="left: 15px; top: 20px; width: 42px; height: 42px; font-size: 10px;">QR</div>
                                        <div id="prevCodeTxt1" class="position-absolute fw-bold text-dark" style="left: 15px; top: 85px; font-size: 9px;">WMF92601</div>
                                        <div id="prevTitleTxt1" class="position-absolute fw-bold text-primary" style="left: 80px; top: 20px; font-size: 9px;">GRM WAREHOUSE</div>
                                        <div id="prevSkuTxt1" class="position-absolute fw-bold text-dark" style="left: 80px; top: 38px; font-size: 9px;">S-BLUE</div>
                                        <div id="prevMrpTxt1" class="position-absolute text-secondary" style="left: 80px; top: 56px; font-size: 8.5px; text-decoration: line-through;">MRP: Rs.1000</div>
                                        <div id="prevDiscTxt1" class="position-absolute fw-bold text-success" style="left: 80px; top: 74px; font-size: 8.5px;">Disc: Rs.500</div>
                                    </div>

                                    <!-- Right Sticker #2 -->
                                    <div id="liveStickerCard2" class="bg-white border border-secondary-subtle rounded shadow-sm position-relative flex-shrink-0" style="width: 240px; height: 130px; font-family: 'Segoe UI', monospace; overflow: hidden; background: #fafafa;">
                                        <div id="badgeSticker2" class="badge bg-light text-dark position-absolute top-0 start-0 m-1 border shadow-xs" style="font-size: 9px; opacity: 0.85;">Right Sticker #2</div>
                                        <div id="prevQRBox2" class="position-absolute border border-dark rounded d-flex align-items-center justify-content-center bg-light text-muted fw-bold" style="left: 15px; top: 20px; width: 42px; height: 42px; font-size: 10px;">QR</div>
                                        <div id="prevCodeTxt2" class="position-absolute fw-bold text-dark" style="left: 15px; top: 85px; font-size: 9px;">WMF92602</div>
                                        <div id="prevTitleTxt2" class="position-absolute fw-bold text-primary" style="left: 80px; top: 20px; font-size: 9px;">GRM WAREHOUSE</div>
                                        <div id="prevSkuTxt2" class="position-absolute fw-bold text-dark" style="left: 80px; top: 38px; font-size: 9px;">S-BLUE</div>
                                        <div id="prevMrpTxt2" class="position-absolute text-secondary" style="left: 80px; top: 56px; font-size: 8.5px; text-decoration: line-through;">MRP: Rs.1000</div>
                                        <div id="prevDiscTxt2" class="position-absolute fw-bold text-success" style="left: 80px; top: 74px; font-size: 8.5px;">Disc: Rs.500</div>
                                    </div>
                                </div>
                                <div class="text-muted small mt-2 text-center" style="font-size: 0.8rem;">
                                    <i class="bi bi-info-circle me-1"></i>Simulating physical 2-column TSC thermal sticker roll layout.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-primary fw-semibold" onclick="testPrintCurrentSettings()">
                    <i class="bi bi-printer me-1"></i>Test Print 1 Sticker
                </button>
                <button type="button" class="btn btn-primary fw-semibold shadow-sm px-4" onclick="savePrinterSettings()">
                    <i class="bi bi-check-lg me-1"></i>Save Printer Configuration
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Library CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>
@media print {
    @page {
        size: 40mm 22mm;
        margin: 0;
    }
    html, body {
        width: 40mm !important;
        height: 22mm !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        overflow: visible !important;
    }
    body > *:not(#qrPrintModal) {
        display: none !important;
    }
    .modal-backdrop, .modal-header, .btn, .d-flex.justify-content-between, .page-header, form, nav, footer {
        display: none !important;
    }
    .modal, .modal-dialog, .modal-content, .modal-body, #printableLabelsContainer {
        position: static !important;
        width: 40mm !important;
        height: auto !important;
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        background: #fff !important;
        box-shadow: none !important;
    }
    #qrStickersGrid {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #qrStickersGrid > .col {
        display: block !important;
        width: 40mm !important;
        height: 22mm !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .qr-sticker-card {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        width: 39mm !important;
        height: 21mm !important;
        padding: 2px !important;
        border: 1px solid #000 !important;
        border-radius: 4px !important;
        box-shadow: none !important;
        background: #fff !important;
    }
}
.lbl-title {
    font-size: 0.65rem;
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 2px;
    color: #4f46e5;
}
.lbl-sku {
    font-size: 0.78rem;
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 2px;
    color: #111827;
}
.lbl-mrp {
    font-size: 0.64rem;
    text-decoration: line-through;
    color: #6b7280;
    line-height: 1.1;
    margin-bottom: 2px;
}
.lbl-disc {
    font-size: 0.72rem;
    font-weight: 700;
    color: #047857;
    line-height: 1.1;
}
.lbl-damage-code {
    font-size: 0.60rem;
    font-weight: 700;
    color: #4f46e5;
    margin-top: 2px;
    line-height: 1;
}
.fs-7 { font-size: 0.825rem; }
.fs-8 { font-size: 0.725rem; }
</style>

<script>
let currentCatalogs = <?= json_encode($distinctCatalogs ?? []) ?>;
let currentCatalogSequences = <?= json_encode($catalogSequences ?? []) ?>;
let currentVariantItems = <?= json_encode($items ?? []) ?>;
let currentCatalogHierarchy = <?= json_encode($catalogHierarchy ?? 'General Apparel') ?>;
let currentProgramInfo = <?= json_encode([
    'product_name'   => $selectedProgram['name'] ?? ($selectedStitching['product_name'] ?? 'GRM Style Apparel'),
    'program_number' => $selectedProgram['program_number'] ?? ($selectedStitching['program_number'] ?? ''),
]) ?>;
let activeCatalogFilter = 'ALL';

function filterVariantsByCatalog(catalogName, btnEl) {
    activeCatalogFilter = catalogName;
    if (btnEl) {
        document.querySelectorAll('#catalogPillsNav .nav-link').forEach(btn => {
            btn.classList.remove('active', 'bg-primary', 'text-white');
        });
        btnEl.classList.add('active');
    }
    const rows = document.querySelectorAll('#variantsTableBody tr');
    rows.forEach(row => {
        const rowCat = row.getAttribute('data-catalog');
        if (catalogName === 'ALL' || !catalogName || rowCat === catalogName) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function handleStitchingBatchChange(stitchingBatchId) {
    if (!stitchingBatchId) {
        document.getElementById('stitchingDetailsBox').style.display = 'none';
        document.getElementById('noStitchingSelectedState').style.display = 'block';
        document.getElementById('variantsTableContainer').style.display = 'none';
        document.getElementById('variantsTableBody').innerHTML = '';
        currentVariantItems = [];
        currentCatalogs = [];
        currentCatalogSequences = {};
        updateCatalogTabsUI();
        calcLabelTotals();
        return;
    }

    const select = document.getElementById('stitchingBatchSelect');
    const selectedOption = select.options[select.selectedIndex];
    const stitchingName = selectedOption.getAttribute('data-name') || '';
    const batchNumber = selectedOption.getAttribute('data-batch') || '';
    const programId = selectedOption.getAttribute('data-program-id') || '';
    const programNumber = selectedOption.getAttribute('data-program-number') || '';
    const programName = selectedOption.getAttribute('data-program-name') || '';
    const stitchedQty = selectedOption.getAttribute('data-qty') || '0';

    if (programId) {
        document.getElementById('inputProgramId').value = programId;
    }

    document.getElementById('stitchingDetailsBox').style.display = 'block';
    document.getElementById('boxBatchNumber').textContent = batchNumber;
    document.getElementById('boxStitchingName').textContent = stitchingName;
    document.getElementById('boxProgramInfo').innerHTML = `<i class="bi bi-file-earmark-text me-1"></i>${programNumber} — ${programName}`;
    document.getElementById('boxStitchedQty').innerHTML = `<i class="bi bi-check2-circle me-1"></i>${parseInt(stitchedQty).toLocaleString()} pcs Stitched`;

    fetch('<?= url("api/stitching/") ?>' + stitchingBatchId + '/details')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const sb = data.stitchingBatch;
                const prg = data.program;

                currentCatalogHierarchy = data.catalogHierarchy || 'General Apparel';
                currentCatalogs = data.catalogs || [];
                currentCatalogSequences = data.catalogSequences || {};
                currentProgramInfo = prg || {};
                
                // Update catalog summary in left box
                const catBadgesList = document.getElementById('catalogBadgesList');
                const catCountBadge = document.getElementById('catalogCountBadge');
                if (catBadgesList && currentCatalogs.length > 0) {
                    if (catCountBadge) catCountBadge.textContent = currentCatalogs.length + ' Catalog(s)';
                    catBadgesList.innerHTML = currentCatalogs.map(c => {
                        const cKey = (c.category_id || 0) + '_' + (c.subcategory_id || 0) + '_' + (c.sub_subcategory_id || 0);
                        const seqInfo = (currentCatalogSequences && currentCatalogSequences[cKey]) 
                            ? currentCatalogSequences[cKey] 
                            : (currentCatalogSequences && currentCatalogSequences[c.name] ? currentCatalogSequences[c.name] : {});
                        const lastSeq = (c.last_sequence !== undefined) ? parseInt(c.last_sequence) : parseInt(seqInfo.last_sequence || 0);
                        const nextStart = lastSeq + 1;
                        const totalQty = parseInt(c.total_qty || 0);
                        const nextEnd = nextStart + Math.max(totalQty, 1) - 1;
                        const seqRangeBadge = (totalQty > 0)
                            ? `<span class="badge bg-success-subtle text-success border ms-1" title="Continuous Sequence Range">#${nextStart}–#${nextEnd}</span>`
                            : '';
                        const seqNote = (lastSeq > 0) 
                            ? `<div class="text-muted mt-1" style="font-size:0.72rem;"><i class="bi bi-arrow-repeat text-success me-1"></i>Prev ended at <strong>#${lastSeq}</strong> &bull; Starts at <strong class="text-success">#${nextStart}</strong></div>`
                            : `<div class="text-muted mt-1" style="font-size:0.72rem;"><i class="bi bi-stars text-primary me-1"></i>Starts from <strong>#1</strong></div>`;
                        return `
                            <div class="p-2 rounded bg-light border mb-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-dark fs-7">${c.name}</span>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-primary-subtle text-primary border">${c.total_qty} pcs</span>
                                        ${seqRangeBadge}
                                    </div>
                                </div>
                                ${seqNote}
                            </div>
                        `;
                    }).join('');
                } else if (catBadgesList) {
                    if (catCountBadge) catCountBadge.textContent = '1 Catalog';
                    catBadgesList.innerHTML = `<span class="badge bg-light text-dark border fs-7 text-wrap text-start">${currentCatalogHierarchy}</span>`;
                }
                
                if (sb.program_id) document.getElementById('inputProgramId').value = sb.program_id;

                currentVariantItems = data.items || [];
                renderLabelVariants(currentVariantItems);
            } else {
                alert(data.message || 'Error fetching stitching details.');
            }
        })
        .catch(err => {
            console.error('Error fetching stitching details:', err);
            alert('Could not load stitching details.');
        });
}

function getCatalogAbbreviation(str, item) {
    if (item && item.qr_code_prefix && item.qr_code_prefix.trim() !== '') {
        const rawPrefix = item.qr_code_prefix.trim();
        if (rawPrefix.includes('-')) {
            const parts = rawPrefix.split(/[^A-Za-z0-9]+/);
            let extracted = '';
            parts.forEach(p => {
                if (p.length > 0) extracted += p.charAt(0);
            });
            return extracted.slice(0, 3).toUpperCase();
        } else if (rawPrefix.length <= 4) {
            return rawPrefix.toUpperCase();
        } else {
            return rawPrefix.slice(0, 3).toUpperCase();
        }
    }
    const c1 = (item && item.category_code ? item.category_code.trim() : '');
    const c2 = (item && item.subcategory_code ? item.subcategory_code.trim() : '');
    const c3 = (item && item.sub_subcategory_code ? item.sub_subcategory_code.trim() : '');
    if (c1 || c2 || c3) {
        let extracted = (c1.charAt(0) + c2.charAt(0) + c3.charAt(0)).toUpperCase();
        if (extracted.length === 3) return extracted;
        return (c1 + c2 + c3).slice(0, 3).toUpperCase();
    }
    
    let hierarchy = (item && item.catalog_hierarchy) ? item.catalog_hierarchy : (str || '');
    if (!hierarchy || hierarchy === 'General Apparel' || hierarchy === 'Apparel Catalog') {
        if (currentProgramInfo && (currentProgramInfo.qr_code_prefix || currentProgramInfo.short_code)) {
            return (currentProgramInfo.qr_code_prefix || currentProgramInfo.short_code).toUpperCase().slice(0, 3);
        }
        return 'RMT';
    }

    const parts = hierarchy.split(/›|>|\||\-/).map(p => p.trim()).filter(p => p.length > 0);
    if (parts.length === 0) return 'RMT';

    let abbr = '';
    parts.forEach(part => {
        const words = part.split(/\s+/).filter(w => w.length > 0);
        if (words.length > 0) {
            abbr += words[0].charAt(0).toUpperCase();
        }
    });

    return (abbr.slice(0, 3) || 'RMT').toUpperCase();
}

function getLabelCode(seqIndex, str, item) {
    const abbr = getCatalogAbbreviation(str, item);
    const mfgDate = (item && (item.variant_mfg_date || item.mfg_date)) ? new Date(item.variant_mfg_date || item.mfg_date) : new Date();
    const now = isNaN(mfgDate.getTime()) ? new Date() : mfgDate;
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = String(now.getFullYear()).slice(-2);
    const seq = String(seqIndex).padStart(2, '0');
    return `${abbr}${month}${year}${seq}`.toUpperCase();
}

function updateCatalogTabsUI() {
    const tabsContainer = document.getElementById('catalogTabsContainer');
    const pillsNav = document.getElementById('catalogPillsNav');
    if (!tabsContainer || !pillsNav) return;

    if (!currentCatalogs || currentCatalogs.length <= 1) {
        tabsContainer.style.display = 'none';
        return;
    }

    tabsContainer.style.display = 'block';
    let totalItems = currentVariantItems.length;
    let html = `
        <li class="nav-item">
            <button type="button" class="nav-link ${activeCatalogFilter === 'ALL' ? 'active' : ''} px-3 py-1 fs-7 fw-semibold" onclick="filterVariantsByCatalog('ALL', this)">
                All Catalogs <span class="badge bg-secondary-subtle text-dark ms-1">${totalItems}</span>
            </button>
        </li>
    `;

    currentCatalogs.forEach(c => {
        const isActive = (activeCatalogFilter === c.name);
        const safeName = c.name.replace(/'/g, "\\'");
        html += `
            <li class="nav-item">
                <button type="button" class="nav-link ${isActive ? 'active' : ''} px-3 py-1 fs-7 fw-semibold" onclick="filterVariantsByCatalog('${safeName}', this)">
                    ${c.name} <span class="badge bg-primary-subtle text-primary ms-1">${c.total_qty} pcs</span>
                </button>
            </li>
        `;
    });

    pillsNav.innerHTML = html;
}

function renderLabelVariants(items) {
    const tbody = document.getElementById('variantsTableBody');
    tbody.innerHTML = '';

    if (!items || items.length === 0) {
        document.getElementById('noStitchingSelectedState').style.display = 'block';
        document.getElementById('variantsTableContainer').style.display = 'none';
        return;
    }

    document.getElementById('noStitchingSelectedState').style.display = 'none';
    document.getElementById('variantsTableContainer').style.display = 'block';

    // Maintain catalog sequence tracker
    const catalogRunningSeq = {};
    if (currentCatalogs && currentCatalogs.length > 0) {
        currentCatalogs.forEach(c => {
            const cKey = (c.category_id || 0) + '_' + (c.subcategory_id || 0) + '_' + (c.sub_subcategory_id || 0);
            const seqInfo = (currentCatalogSequences && currentCatalogSequences[cKey]) 
                ? currentCatalogSequences[cKey] 
                : (currentCatalogSequences && currentCatalogSequences[c.name] ? currentCatalogSequences[c.name] : {});
            const start = (c.next_sequence !== undefined && c.next_sequence !== null) 
                ? parseInt(c.next_sequence) 
                : ((seqInfo.next_sequence !== undefined && seqInfo.next_sequence !== null) 
                    ? parseInt(seqInfo.next_sequence) 
                    : ((c.last_sequence !== undefined && c.last_sequence !== null) ? parseInt(c.last_sequence) + 1 : 1));
            catalogRunningSeq[cKey] = start;
            if (c.name) catalogRunningSeq[c.name] = start;
        });
    }

    items.forEach((it, idx) => {
        const sizeName = it.size_name || it.display_size || 'Standard';
        const colorName = it.color_name || it.display_color || 'Assorted';
        const variantId = it.variant_id || '';
        const stitchedQty = parseInt(it.stitched_qty || it.received_qty) || 0;
        const requiredQty = stitchedQty;
        const printedQty = stitchedQty;
        const damagedQty = 0;
        const sku = it.sku || (sizeName + '-' + colorName);
        const basePrice = parseFloat(it.base_price || it.variant_mrp || 0);
        const discountPrice = parseFloat(it.discount_price || it.variant_selling_price || 0);

        // Derive item's specific catalog hierarchy and abbreviation
        let itemCatalogHierarchy = it.catalog_hierarchy || '';
        if (!itemCatalogHierarchy) {
            const hParts = [it.category_name, it.subcategory_name, it.sub_subcategory_name].filter(Boolean);
            itemCatalogHierarchy = hParts.length > 0 ? hParts.join(' › ') : currentCatalogHierarchy;
        }

        const catKey = (it.category_id || 0) + '_' + (it.subcategory_id || 0) + '_' + (it.sub_subcategory_id || 0);
        if (catalogRunningSeq[catKey] === undefined) {
            if (currentCatalogSequences && currentCatalogSequences[catKey] && currentCatalogSequences[catKey].next_sequence !== undefined) {
                catalogRunningSeq[catKey] = parseInt(currentCatalogSequences[catKey].next_sequence);
            } else if (currentCatalogSequences && currentCatalogSequences[itemCatalogHierarchy] && currentCatalogSequences[itemCatalogHierarchy].next_sequence !== undefined) {
                catalogRunningSeq[catKey] = parseInt(currentCatalogSequences[itemCatalogHierarchy].next_sequence);
            } else if (it.next_sequence !== undefined && it.next_sequence !== null) {
                catalogRunningSeq[catKey] = parseInt(it.next_sequence);
            } else {
                catalogRunningSeq[catKey] = 1;
            }
        }

        const itemQty = Math.max(requiredQty, 1);
        const itemStartSeq = catalogRunningSeq[catKey];
        const itemEndSeq = itemStartSeq + itemQty - 1;
        catalogRunningSeq[catKey] = itemEndSeq + 1;

        const itemCatalogAbbr = getCatalogAbbreviation(itemCatalogHierarchy, it);
        const startCode = getLabelCode(itemStartSeq, itemCatalogHierarchy, it);
        const endCode = getLabelCode(itemEndSeq, itemCatalogHierarchy, it);

        it._start_seq = itemStartSeq;
        it._end_seq = itemEndSeq;
        it._start_code = startCode;
        it._end_code = endCode;

        const formatPriceStr = (val) => {
            const num = parseFloat(val || 0);
            if (isNaN(num)) return "0";
            return num % 1 === 0 ? String(Math.round(num)) : num.toFixed(2);
        };
        const fmtBase = formatPriceStr(basePrice);
        const fmtDisc = formatPriceStr(discountPrice);

        const tr = document.createElement('tr');
        tr.setAttribute('data-catalog', itemCatalogHierarchy);
        tr.innerHTML = `
            <td>
                <input type="hidden" name="items[${idx}][variant_id]" value="${variantId}">
                <input type="hidden" name="items[${idx}][category_id]" value="${it.category_id || ''}">
                <input type="hidden" name="items[${idx}][subcategory_id]" value="${it.subcategory_id || ''}">
                <input type="hidden" name="items[${idx}][sub_subcategory_id]" value="${it.sub_subcategory_id || ''}">
                <input type="hidden" name="items[${idx}][catalog_hierarchy]" value="${itemCatalogHierarchy}">
                <input type="hidden" name="items[${idx}][size_name]" value="${sizeName}">
                <input type="hidden" name="items[${idx}][color_name]" value="${colorName}">
                <input type="hidden" name="items[${idx}][base_price]" value="${basePrice}">
                <input type="hidden" name="items[${idx}][discount_price]" value="${discountPrice}">
                <input type="hidden" name="items[${idx}][printed_qty]" value="${printedQty}">
                <input type="hidden" name="items[${idx}][damaged_qty]" value="${damagedQty}">
                <input type="hidden" name="items[${idx}][start_seq]" value="${itemStartSeq}">
                <input type="hidden" name="items[${idx}][end_seq]" value="${itemEndSeq}">
                <input type="hidden" name="items[${idx}][start_code]" value="${startCode}">
                <input type="hidden" name="items[${idx}][end_code]" value="${endCode}">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">${sizeName}</span>
                    <span class="badge bg-primary-subtle text-primary border px-2 py-1">${colorName}</span>
                    <span class="badge bg-info-subtle text-info border px-2 py-1" title="${itemCatalogHierarchy}">${itemCatalogAbbr}</span>
                    <span class="badge bg-dark text-white px-2 py-1 code" title="Label Code Range">${startCode}${itemEndSeq > itemStartSeq ? ' &ndash; ' + endCode : ''}</span>
                    <span class="badge bg-light text-dark border px-2 py-1 fs-8" title="Continuous Label Numbers">#${itemStartSeq}–#${itemEndSeq}</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap fs-8">
                    <code class="text-muted">${sku}</code>
                    <span class="badge bg-light text-muted border text-decoration-line-through"><i class="bi bi-tag text-secondary me-1"></i>MRP: ₹${fmtBase}</span>
                    <span class="badge bg-success-subtle text-success border"><i class="bi bi-percent text-success me-1"></i>Disc: ₹${fmtDisc}</span>
                </div>
            </td>
            <td class="text-center">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold">
                    ${stitchedQty} pcs
                </span>
            </td>
            <td>
                <input type="number" name="items[${idx}][required_qty]" 
                    class="form-control form-control-sm text-center fw-bold bg-light text-dark label-req-input" 
                    style="cursor: not-allowed;" 
                    min="0" value="${requiredQty}" readonly>
            </td>
            <td class="text-center">
                <div class="d-flex align-items-center justify-content-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="previewSingleQR(${idx})" title="Preview QR Tag">
                        <i class="bi bi-qr-code"></i> Preview
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="printSingleVariantLabel(${idx})" title="Print 1 Extra Label for this variant">
                        <i class="bi bi-printer me-1"></i>Print 1 Extra
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    calcLabelTotals();
    updateCatalogTabsUI();
}

function calcLabelTotals() {
    let totalReq = 0;
    document.querySelectorAll('.label-req-input').forEach(inp => {
        totalReq += parseInt(inp.value) || 0;
    });
    const reqBadge = document.getElementById('totalRequiredBadge');
    if (reqBadge) {
        reqBadge.innerHTML = '<i class="bi bi-tag me-1"></i>' + totalReq.toLocaleString() + ' Required Tags';
    }
}

function generateQRCodeContainer(containerId, textPayload) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';

    if (window.QRCode) {
        new QRCode(container, {
            text: textPayload,
            width: 75,
            height: 75,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
        setTimeout(() => {
            const canvas = container.querySelector('canvas');
            if (canvas) {
                try {
                    const imgUrl = canvas.toDataURL("image/png");
                    container.innerHTML = `<img src="${imgUrl}" style="width: 75px; height: 75px; display: block; border-radius: 2px;" alt="QR">`;
                } catch(e) {}
            }
        }, 40);
    } else {
        container.innerHTML = `<div class="p-1 border text-center fs-8 text-muted">QR Code<br><code>${textPayload}</code></div>`;
    }
}

let currentPreviewIndex = 0;
let currentExtraVariantIndex = null;
let catalogExtraNextSeq = {};

function previewSingleQR(idx) {
    if (!currentVariantItems || !currentVariantItems[idx]) return;
    currentPreviewIndex = idx;
    const item = currentVariantItems[idx];

    let itemCatalogHierarchy = item.catalog_hierarchy || '';
    if (!itemCatalogHierarchy) {
        const hParts = [item.category_name, item.subcategory_name, item.sub_subcategory_name].filter(Boolean);
        itemCatalogHierarchy = hParts.length > 0 ? hParts.join(' › ') : currentCatalogHierarchy;
    }

    const startSeq = item._start_seq || item.start_seq || 1;
    const sizeName = item.size_name || item.display_size || 'Standard';
    const colorName = item.color_name || item.display_color || 'Assorted';
    const sku = item.sku || (sizeName + '-' + colorName);
    const basePrice = parseFloat(item.base_price || item.variant_mrp || 0);
    const discountPrice = parseFloat(item.discount_price || item.variant_selling_price || 0);
    const damageCode = item._start_code || item.start_code || getLabelCode(startSeq, itemCatalogHierarchy, item);

    const fmtBase = (basePrice % 1 === 0) ? String(Math.round(basePrice)) : basePrice.toFixed(2);
    const fmtDisc = (discountPrice % 1 === 0) ? String(Math.round(discountPrice)) : discountPrice.toFixed(2);

    document.getElementById('previewSKU').textContent = sku;
    document.getElementById('previewDamageCode').textContent = damageCode;
    document.getElementById('previewBasePrice').textContent = 'MRP: ₹' + fmtBase;
    document.getElementById('previewDiscPrice').textContent = 'Disc: ₹' + fmtDisc;

    const qrPayload = `${damageCode}|${sku}|MRP:${fmtBase}|Disc:${fmtDisc}`;

    generateQRCodeContainer('singleQRCanvas', qrPayload);
    const modal = new bootstrap.Modal(document.getElementById('singleQRModal'));
    modal.show();
}

/**
 * Reusable Direct Printing API for TSC TE244 via Windows Local Print Helper (127.0.0.1:9191)
 */
async function printLabel(labelData) {
    const helperUrl = 'http://127.0.0.1:9191/print';

    let labels = [];
    if (Array.isArray(labelData)) {
        labels = labelData;
    } else if (labelData && (labelData.sku || labelData.productName)) {
        labels = [labelData];
    } else {
        showPrintToast('Invalid label data. Empty required fields.', 'danger');
        return { success: false, message: 'Invalid label data.' };
    }

    for (let item of labels) {
        if (!item.sku && !item.productName) {
            showPrintToast('Empty required fields. SKU or product name missing.', 'warning');
            return { success: false, message: 'Missing SKU or product name.' };
        }
    }

    try {
        const response = await fetch(helperUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                printerName: "TSC TE244",
                labels: labels
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showPrintToast(result.message || 'Label printed successfully on TSC TE244.', 'success');
            return { success: true, message: result.message };
        } else {
            const errMsg = result.message || 'Printer is not available. Please check the TSC TE244 connection.';
            showPrintToast(errMsg, 'danger');
            return { success: false, message: errMsg };
        }
    } catch (err) {
        console.error('Local print helper error:', err);
        const errMsg = 'Printer is not available. Please check the TSC TE244 connection or local print helper.';
        showPrintToast(errMsg, 'danger');
        return { success: false, message: errMsg };
    }
}

function showPrintToast(message, type = 'info') {
    let container = document.getElementById('printToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'printToastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'danger' ? 'bg-danger text-white' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-dark text-white'));
    const icon = type === 'success' ? 'bi-check-circle-fill' : (type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill');

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2 fs-6">
                    <i class="bi ${icon} fs-5"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    if (window.bootstrap && bootstrap.Toast) {
        const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
        toast.show();
    } else {
        setTimeout(() => toastEl.remove(), 4000);
    }
}

function getNextAscendingSequence(item) {
    let itemCatalogHierarchy = item.catalog_hierarchy || '';
    if (!itemCatalogHierarchy) {
        const hParts = [item.category_name, item.subcategory_name, item.sub_subcategory_name].filter(Boolean);
        itemCatalogHierarchy = hParts.length > 0 ? hParts.join(' › ') : currentCatalogHierarchy;
    }
    const catKey = (item.category_id || 0) + '_' + (item.subcategory_id || 0) + '_' + (item.sub_subcategory_id || 0);

    if (!catalogExtraNextSeq[catKey]) {
        let maxSeq = 0;
        if (currentVariantItems && currentVariantItems.length > 0) {
            currentVariantItems.forEach(it => {
                const itCatKey = (it.category_id || 0) + '_' + (it.subcategory_id || 0) + '_' + (it.sub_subcategory_id || 0);
                let itHierarchy = it.catalog_hierarchy || '';
                if (!itHierarchy) {
                    const parts = [it.category_name, it.subcategory_name, it.sub_subcategory_name].filter(Boolean);
                    itHierarchy = parts.length > 0 ? parts.join(' › ') : currentCatalogHierarchy;
                }
                if (itCatKey === catKey || itHierarchy === itemCatalogHierarchy) {
                    const end = parseInt(it._end_seq || it.end_seq || 0);
                    const start = parseInt(it._start_seq || it.start_seq || 0);
                    const qty = parseInt(it.required_qty || 0);
                    const calcEnd = end > 0 ? end : (start > 0 ? start + Math.max(qty, 1) - 1 : 0);
                    if (calcEnd > maxSeq) maxSeq = calcEnd;
                }
            });
        }
        if (typeof currentCatalogSequences !== 'undefined' && currentCatalogSequences) {
            const seqInfo = currentCatalogSequences[catKey] || currentCatalogSequences[itemCatalogHierarchy];
            if (seqInfo) {
                const dbLast = parseInt(seqInfo.last_sequence || 0);
                if (dbLast > maxSeq) maxSeq = dbLast;
            }
        }
        catalogExtraNextSeq[catKey] = Math.max(maxSeq, 0) + 1;
    }

    return catalogExtraNextSeq[catKey];
}

function openExtraLabelModal(idx) {
    if (!currentVariantItems || !currentVariantItems[idx]) return;
    currentExtraVariantIndex = idx;
    const item = currentVariantItems[idx];

    const sizeName = item.size_name || item.display_size || 'Standard';
    const colorName = item.color_name || item.display_color || 'Assorted';
    const sku = item.sku || (sizeName + '-' + colorName);

    let itemCatalogHierarchy = item.catalog_hierarchy || '';
    if (!itemCatalogHierarchy) {
        const hParts = [item.category_name, item.subcategory_name, item.sub_subcategory_name].filter(Boolean);
        itemCatalogHierarchy = hParts.length > 0 ? hParts.join(' › ') : currentCatalogHierarchy;
    }
    const catAbbr = getCatalogAbbreviation(itemCatalogHierarchy, item);

    document.getElementById('extraVariantSize').textContent = 'Size: ' + sizeName;
    document.getElementById('extraVariantColor').textContent = 'Color: ' + colorName;
    document.getElementById('extraVariantCat').textContent = catAbbr;
    document.getElementById('extraVariantCatalogHierarchy').textContent = itemCatalogHierarchy;
    document.getElementById('extraVariantSku').textContent = 'SKU: ' + sku;

    // Determine batch range for this catalog
    let minBatchSeq = 99999999;
    let maxBatchSeq = 0;
    const catKey = (item.category_id || 0) + '_' + (item.subcategory_id || 0) + '_' + (item.sub_subcategory_id || 0);
    currentVariantItems.forEach(it => {
        const itCatKey = (it.category_id || 0) + '_' + (it.subcategory_id || 0) + '_' + (it.sub_subcategory_id || 0);
        let itHierarchy = it.catalog_hierarchy || '';
        if (!itHierarchy) {
            const parts = [it.category_name, it.subcategory_name, it.sub_subcategory_name].filter(Boolean);
            itHierarchy = parts.length > 0 ? parts.join(' › ') : currentCatalogHierarchy;
        }
        if (itCatKey === catKey || itHierarchy === itemCatalogHierarchy) {
            const start = parseInt(it._start_seq || it.start_seq || 0);
            const end = parseInt(it._end_seq || it.end_seq || 0);
            if (start > 0 && start < minBatchSeq) minBatchSeq = start;
            if (end > maxBatchSeq) maxBatchSeq = end;
        }
    });

    if (minBatchSeq <= maxBatchSeq && minBatchSeq !== 99999999) {
        document.getElementById('extraBatchRangeBadge').textContent = `Batch: #${minBatchSeq} – #${maxBatchSeq}`;
    } else {
        document.getElementById('extraBatchRangeBadge').textContent = 'Batch Sequence Active';
    }

    const nextSeq = getNextAscendingSequence(item);
    document.getElementById('extraNextSeqBadge').textContent = `Next Continue: #${nextSeq}`;

    document.getElementById('extraLabelQty').value = 1;
    updateExtraLabelPreview();

    const modal = new bootstrap.Modal(document.getElementById('extraLabelModal'));
    modal.show();
}

function adjustExtraQty(delta) {
    const qtyInp = document.getElementById('extraLabelQty');
    if (!qtyInp) return;
    let val = parseInt(qtyInp.value) || 1;
    val = Math.max(1, Math.min(50, val + delta));
    qtyInp.value = val;
    updateExtraLabelPreview();
}

function updateExtraLabelPreview() {
    if (currentExtraVariantIndex === null || !currentVariantItems[currentExtraVariantIndex]) return;
    const item = currentVariantItems[currentExtraVariantIndex];

    const qtyInp = document.getElementById('extraLabelQty');
    const qty = Math.max(1, Math.min(50, parseInt(qtyInp ? qtyInp.value : 1) || 1));

    let itemCatalogHierarchy = item.catalog_hierarchy || '';
    if (!itemCatalogHierarchy) {
        const hParts = [item.category_name, item.subcategory_name, item.sub_subcategory_name].filter(Boolean);
        itemCatalogHierarchy = hParts.length > 0 ? hParts.join(' › ') : currentCatalogHierarchy;
    }

    const sizeName = item.size_name || item.display_size || 'Standard';
    const colorName = item.color_name || item.display_color || 'Assorted';
    const sku = item.sku || (sizeName + '-' + colorName);
    const basePrice = parseFloat(item.base_price || item.variant_mrp || 0);
    const discountPrice = parseFloat(item.discount_price || item.variant_selling_price || 0);

    const fmtBase = (basePrice % 1 === 0) ? String(Math.round(basePrice)) : basePrice.toFixed(2);
    const fmtDisc = (discountPrice % 1 === 0) ? String(Math.round(discountPrice)) : discountPrice.toFixed(2);

    const startSeq = getNextAscendingSequence(item);
    const codes = [];
    for (let i = 0; i < qty; i++) {
        const seq = startSeq + i;
        const code = getLabelCode(seq, itemCatalogHierarchy, item);
        codes.push(`${code} (#${seq})`);
    }

    document.getElementById('extraCodesPreviewList').textContent = codes.join(', ');
    document.getElementById('extraStickersCountBadge').textContent = qty + (qty === 1 ? ' Sticker' : ' Stickers');
    document.getElementById('extraNextSeqBadge').textContent = `Next Continue: #${startSeq}${qty > 1 ? '–#' + (startSeq + qty - 1) : ''}`;

    const previewGrid = document.getElementById('extraStickersPreviewGrid');
    previewGrid.innerHTML = '';

    for (let i = 0; i < qty; i++) {
        const seq = startSeq + i;
        const damageCode = getLabelCode(seq, itemCatalogHierarchy, item);
        const cardId = `extraCard_${i}`;
        const canvasId = `extraQR_${i}`;

        const card = document.createElement('div');
        card.className = 'card shadow-sm border p-2 bg-white text-start position-relative qr-sticker-card';
        card.style.width = '240px';
        card.style.minHeight = '125px';
        card.id = cardId;

        card.innerHTML = `
            <div class="d-flex align-items-start gap-2">
                <div class="text-center flex-shrink-0" style="width: 75px;">
                    <div id="${canvasId}" class="bg-white p-1 rounded border shadow-xs d-flex justify-content-center align-items-center" style="width: 75px; height: 75px;"></div>
                    <div class="lbl-damage-code mt-1 code">${damageCode}</div>
                </div>
                <div class="flex-grow-1 overflow-hidden" style="margin-top: 2px;">
                    <div class="lbl-title text-uppercase text-truncate" title="GRM WAREHOUSE">GRM WAREHOUSE</div>
                    <div class="lbl-sku text-truncate fw-bold mt-1" title="${sku}">${sku}</div>
                    <div class="lbl-mrp mt-1">MRP: ₹${fmtBase}</div>
                    <div class="lbl-disc fw-bold mt-1">Disc: ₹${fmtDisc}</div>
                    <div class="badge bg-success-subtle text-success border border-success-subtle mt-2 fs-8 fw-semibold">Next #${seq}</div>
                </div>
            </div>
        `;
        previewGrid.appendChild(card);

        const qrPayload = `${damageCode}|${sku}|MRP:${fmtBase}|Disc:${fmtDisc}`;
        generateQRCodeContainer(canvasId, qrPayload);
    }
}

async function executePrintExtraLabels() {
    if (currentExtraVariantIndex === null || !currentVariantItems[currentExtraVariantIndex]) return;
    const item = currentVariantItems[currentExtraVariantIndex];

    const qtyInp = document.getElementById('extraLabelQty');
    const qty = Math.max(1, Math.min(50, parseInt(qtyInp ? qtyInp.value : 1) || 1));

    let itemCatalogHierarchy = item.catalog_hierarchy || '';
    if (!itemCatalogHierarchy) {
        const hParts = [item.category_name, item.subcategory_name, item.sub_subcategory_name].filter(Boolean);
        itemCatalogHierarchy = hParts.length > 0 ? hParts.join(' › ') : currentCatalogHierarchy;
    }

    const sizeName = item.size_name || item.display_size || 'Standard';
    const colorName = item.color_name || item.display_color || 'Assorted';
    const sku = item.sku || (sizeName + '-' + colorName);
    const basePrice = parseFloat(item.base_price || item.variant_mrp || 0);
    const discountPrice = parseFloat(item.discount_price || item.variant_selling_price || 0);

    const startSeq = getNextAscendingSequence(item);
    const labelPayloads = [];

    for (let i = 0; i < qty; i++) {
        const seq = startSeq + i;
        const code = getLabelCode(seq, itemCatalogHierarchy, item);
        labelPayloads.push({
            sku: sku,
            basePrice: basePrice.toFixed(2),
            discountPrice: discountPrice.toFixed(2),
            damageCode: code,
            title: "GRM WAREHOUSE",
            quantity: 1
        });
    }

    const catKey = (item.category_id || 0) + '_' + (item.subcategory_id || 0) + '_' + (item.sub_subcategory_id || 0);
    // Advance the continuing sequence for this catalog
    catalogExtraNextSeq[catKey] = startSeq + qty;

    showPrintToast(`Sending ${qty} extra label(s) (${labelPayloads.map(p => p.damageCode).join(', ')}) to TSC TE244...`, 'info');

    const result = await printLabel(labelPayloads);
    if (result && result.success) {
        const modalEl = document.getElementById('extraLabelModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
}

function printExtraLabelBrowser() {
    const previewGrid = document.getElementById('extraStickersPreviewGrid');
    if (!previewGrid) return;

    const printWin = window.open('', '_blank', 'width=600,height=600');
    if (!printWin) {
        alert('Please allow popups to use browser print.');
        return;
    }

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Extra Thermal QR Stickers</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; background: #fff; }
                .lbl-title { font-size: 9px; font-weight: 800; color: #1e293b; line-height: 1.1; }
                .lbl-sku { font-size: 9px; font-weight: 700; color: #0f172a; line-height: 1.1; }
                .lbl-mrp { font-size: 8px; color: #64748b; text-decoration: line-through; line-height: 1.1; }
                .lbl-disc { font-size: 8.5px; font-weight: 800; color: #16a34a; line-height: 1.1; }
                .lbl-damage-code { font-size: 8.5px; font-weight: 800; background: #000; color: #fff; padding: 1px 4px; border-radius: 2px; display: inline-block; margin-top: 2px; }
                @media print {
                    body { padding: 0; margin: 0; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body onload="setTimeout(() => { window.print(); window.close(); }, 300)">
            <div class="d-flex flex-wrap gap-2">
                ${previewGrid.innerHTML}
            </div>
        </body>
        </html>
    `);
    printWin.document.close();
}

function printSingleVariantLabel(idx) {
    openExtraLabelModal(idx);
}


function removeLabelByCode() {
    const input = document.getElementById('deleteLabelInput');
    if (!input || !input.value.trim()) {
        alert('Please enter a Label Code (e.g. WMF92605) to remove.');
        return;
    }
    const targetCode = input.value.trim().toUpperCase();
    const grid = document.getElementById('qrStickersGrid');
    const cols = grid.querySelectorAll('.col');
    let removedCount = 0;

    cols.forEach(col => {
        const codeEl = col.querySelector('.lbl-damage-code');
        if (codeEl && codeEl.textContent.trim().toUpperCase() === targetCode) {
            col.remove();
            removedCount++;
        }
    });

    if (removedCount > 0) {
        input.value = '';
        updatePrintModalSummary();
    } else {
        alert(`Label code "${targetCode}" not found in current printable stickers.`);
    }
}

function removeSingleStickerCard(btn) {
    const col = btn.closest('.col');
    if (col) {
        col.remove();
        updatePrintModalSummary();
    }
}

function updatePrintModalSummary() {
    const grid = document.getElementById('qrStickersGrid');
    const total = grid ? grid.querySelectorAll('.qr-sticker-select').length : 0;
    const selected = grid ? grid.querySelectorAll('.qr-sticker-select:checked').length : 0;
    const summaryEl = document.getElementById('printableCountBadge');
    if (summaryEl) {
        summaryEl.textContent = `${selected} / ${total} Selected`;
    }
}

function setAllQRStickersSelected(selected) {
    document.querySelectorAll('#qrStickersGrid .qr-sticker-select').forEach(input => {
        input.checked = selected;
    });
    updatePrintModalSummary();
}

function openQRPrintModal() {
    if (!currentVariantItems || currentVariantItems.length === 0) {
        alert('Please select a Stitching Name first to load variant items.');
        return;
    }

    const grid = document.getElementById('qrStickersGrid');
    grid.innerHTML = '';

    currentVariantItems.forEach((item, idx) => {
        const sizeName = item.size_name || item.display_size || 'Standard';
        const colorName = item.color_name || item.display_color || 'Assorted';
        const sku = item.sku || (sizeName + '-' + colorName);
        const basePrice = parseFloat(item.base_price || item.variant_mrp || 0);
        const discountPrice = parseFloat(item.discount_price || item.variant_selling_price || 0);

        let itemCatalogHierarchy = item.catalog_hierarchy || '';
        if (!itemCatalogHierarchy) {
            const hParts = [item.category_name, item.subcategory_name, item.sub_subcategory_name].filter(Boolean);
            itemCatalogHierarchy = hParts.length > 0 ? hParts.join(' › ') : currentCatalogHierarchy;
        }

        const reqInp = document.querySelectorAll('.label-req-input')[idx];
        const count = reqInp ? (parseInt(reqInp.value) || 1) : 1;
        const startSeq = item._start_seq || item.start_seq || 1;

        for (let i = 0; i < count; i++) {
            const currentSeq = startSeq + i;
            const damageCode = getLabelCode(currentSeq, itemCatalogHierarchy, item);

            const qrCanvasId = `batchQR_${idx}_${i}`;
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `
                <div class="p-2 border rounded bg-white shadow-sm qr-sticker-card position-relative">
                    <input type="checkbox" class="form-check-input position-absolute top-0 start-0 m-2 no-print qr-sticker-select" checked onchange="updatePrintModalSummary()" aria-label="Select this QR sticker to print" title="Select this QR sticker to print" style="z-index: 10;">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-1 no-print" style="font-size: 0.65rem; z-index: 10; opacity: 0.8;" onclick="removeSingleStickerCard(this)" title="Remove this label"></button>
                    <div class="d-flex align-items-start gap-2">
                        <div class="text-center flex-shrink-0">
                            <div id="${qrCanvasId}" class="bg-white p-1 rounded border"></div>
                            <div class="lbl-damage-code code">${damageCode}</div>
                        </div>
                        <div class="overflow-hidden ms-1" style="margin-top: 4px;">
                            <div class="lbl-title text-uppercase">GRM WAREHOUSE</div>
                            <div class="lbl-sku text-truncate">${sku}</div>
                            <div class="lbl-mrp">MRP: ₹${basePrice.toFixed(2)}</div>
                            <div class="lbl-disc">Disc: ₹${discountPrice.toFixed(2)}</div>
                        </div>
                    </div>
                </div>
            `;
            grid.appendChild(col);

            const qrPayload = `${damageCode}|${sku}|MRP:${basePrice.toFixed(2)}|Disc:${discountPrice.toFixed(2)}`;

            setTimeout(() => {
                generateQRCodeContainer(qrCanvasId, qrPayload);
            }, 50);
        }
    });

    updatePrintModalSummary();
    const modal = new bootstrap.Modal(document.getElementById('qrPrintModal'));
    modal.show();
}

function printQRStickersDirectly() {
    const grid = document.getElementById('qrStickersGrid');
    if (!grid || !grid.querySelectorAll('.qr-sticker-card').length) {
        showPrintToast('No QR stickers generated to print.', 'warning');
        return;
    }

    const cols = grid.querySelectorAll('.col');
    const batchLabels = [];

    cols.forEach(col => {
        const selected = col.querySelector('.qr-sticker-select');
        if (selected && !selected.checked) return;

        const skuEl = col.querySelector('.lbl-sku');
        const mrpEl = col.querySelector('.lbl-mrp');
        const discEl = col.querySelector('.lbl-disc');
        const codeEl = col.querySelector('.lbl-damage-code');

        const sku = skuEl ? skuEl.textContent.trim() : 'SKU';
        const basePrice = mrpEl ? mrpEl.textContent.replace('MRP: ₹', '').trim() : '0.00';
        const discountPrice = discEl ? discEl.textContent.replace('Disc: ₹', '').trim() : '0.00';
        const damageCode = codeEl ? codeEl.textContent.trim() : sku;

        batchLabels.push({
            sku: sku,
            basePrice: basePrice,
            discountPrice: discountPrice,
            damageCode: damageCode,
            title: "GRM WAREHOUSE",
            quantity: 1
        });
    });

    if (batchLabels.length === 0) {
        showPrintToast('Select at least one QR sticker to print.', 'warning');
        return;
    }

    // Directly print batch TSPL to TSC TE244 without browser print dialog
    printLabel(batchLabels);
}

/**
 * Open Printer & Alignment Settings Modal & Load Current Config
 */
async function openPrinterSettingsModal() {
    try {
        const response = await fetch('http://127.0.0.1:9191/config');
        if (response.ok) {
            const data = await response.json();
            if (data && data.config) {
                const cfg = data.config;
                if (document.getElementById('cfgWidth')) document.getElementById('cfgWidth').value = cfg.labelWidthMm || 40;
                if (document.getElementById('cfgHeight')) document.getElementById('cfgHeight').value = cfg.labelHeightMm || 25;
                if (document.getElementById('cfgColumns')) document.getElementById('cfgColumns').value = cfg.columns || 2;
                if (document.getElementById('cfgGap')) document.getElementById('cfgGap').value = cfg.gapMm || 2;
                if (document.getElementById('cfgCol2ShiftDots')) document.getElementById('cfgCol2ShiftDots').value = cfg.col2ShiftDots !== undefined ? cfg.col2ShiftDots : 0;
                
                if (document.getElementById('cfgQrX')) document.getElementById('cfgQrX').value = cfg.qrX !== undefined ? cfg.qrX : 12;
                if (document.getElementById('cfgQrY')) document.getElementById('cfgQrY').value = cfg.qrY !== undefined ? cfg.qrY : 35;
                if (document.getElementById('cfgQrCellSize')) document.getElementById('cfgQrCellSize').value = cfg.qrCellSize || 3;
                if (document.getElementById('cfgCodeX')) document.getElementById('cfgCodeX').value = cfg.codeX !== undefined ? cfg.codeX : 12;
                if (document.getElementById('cfgCodeY')) document.getElementById('cfgCodeY').value = cfg.codeY !== undefined ? cfg.codeY : 125;
                
                if (document.getElementById('cfgTextX')) document.getElementById('cfgTextX').value = cfg.textX !== undefined ? cfg.textX : 115;
                if (document.getElementById('cfgTextFont')) document.getElementById('cfgTextFont').value = cfg.textFont || "2";
                if (document.getElementById('cfgTitleY')) document.getElementById('cfgTitleY').value = cfg.titleY !== undefined ? cfg.titleY : 35;
                if (document.getElementById('cfgSkuY')) document.getElementById('cfgSkuY').value = cfg.skuY !== undefined ? cfg.skuY : 62;
                if (document.getElementById('cfgMrpY')) document.getElementById('cfgMrpY').value = cfg.mrpY !== undefined ? cfg.mrpY : 90;
                if (document.getElementById('cfgDiscY')) document.getElementById('cfgDiscY').value = cfg.discY !== undefined ? cfg.discY : 118;
            }
        }
    } catch (e) {
        console.warn('Could not fetch current printer config:', e);
    }

    updateLivePreview();
    const modal = new bootstrap.Modal(document.getElementById('printerSettingsModal'));
    modal.show();
}

/**
 * Update Live Visual Sticker Layout Preview Box (Dual Sticker Roll)
 */
function updateLivePreview() {
    const widthMm = parseFloat(document.getElementById('cfgWidth')?.value || 40);
    const heightMm = parseFloat(document.getElementById('cfgHeight')?.value || 20);
    const columns = parseInt(document.getElementById('cfgColumns')?.value || 2);
    const col2ShiftDots = parseFloat(document.getElementById('cfgCol2ShiftDots')?.value || 0);

    const badge = document.getElementById('previewDimensionBadge');
    if (badge) badge.textContent = `${columns === 2 ? 'Dual Roll ' : ''}${widthMm}mm × ${heightMm}mm`;

    const card1 = document.getElementById('liveStickerCard1');
    const card2 = document.getElementById('liveStickerCard2');
    if (!card1) return;

    // Scale card preview dimensions: 40mm -> 240px card width (6 px/mm scale factor)
    const scale = 240 / widthMm;
    const cardPxWidth = 240;
    const cardPxHeight = Math.max(100, Math.round(heightMm * scale));

    card1.style.width = `${cardPxWidth}px`;
    card1.style.height = `${cardPxHeight}px`;

    if (card2) {
        if (columns === 1) {
            card2.style.display = 'none';
        } else {
            card2.style.display = 'block';
            card2.style.width = `${cardPxWidth}px`;
            card2.style.height = `${cardPxHeight}px`;
        }
    }

    // Update Right Sticker #2 Badge label to show shift amount
    const badge2 = document.getElementById('badgeSticker2');
    if (badge2) {
        if (col2ShiftDots !== 0) {
            badge2.textContent = `Right Sticker #2 (${col2ShiftDots > 0 ? '+' : ''}${col2ShiftDots} dots)`;
            badge2.className = 'badge bg-primary text-white position-absolute top-0 start-0 m-1 border';
        } else {
            badge2.textContent = 'Right Sticker #2';
            badge2.className = 'badge bg-light text-dark position-absolute top-0 start-0 m-1 border';
        }
    }

    // 8.12 dots/mm at 203 DPI -> dots to preview px scale factor = scale / 8.12
    const dotScale = scale / 8.12;

    const qrX = parseFloat(document.getElementById('cfgQrX')?.value || 12) * dotScale;
    const qrY = parseFloat(document.getElementById('cfgQrY')?.value || 35) * dotScale;
    const qrSize = parseInt(document.getElementById('cfgQrCellSize')?.value || 3);
    const qrPxDim = Math.max(20, Math.round(qrSize * 8.5 * (scale / 4)));

    const codeX = parseFloat(document.getElementById('cfgCodeX')?.value || 12) * dotScale;
    const codeY = parseFloat(document.getElementById('cfgCodeY')?.value || 125) * dotScale;

    const textX = parseFloat(document.getElementById('cfgTextX')?.value || 115) * dotScale;
    const titleY = parseFloat(document.getElementById('cfgTitleY')?.value || 35) * dotScale;
    const skuY = parseFloat(document.getElementById('cfgSkuY')?.value || 62) * dotScale;
    const mrpY = parseFloat(document.getElementById('cfgMrpY')?.value || 90) * dotScale;
    const discY = parseFloat(document.getElementById('cfgDiscY')?.value || 118) * dotScale;

    // Shift in pixels for Right Sticker #2
    const col2ShiftPx = col2ShiftDots * dotScale;

    const setPos = (id, x, y, w = null, h = null) => {
        const el = document.getElementById(id);
        if (el) {
            el.style.left = `${Math.round(x)}px`;
            el.style.top = `${Math.round(y)}px`;
            if (w !== null) el.style.width = `${Math.round(w)}px`;
            if (h !== null) el.style.height = `${Math.round(h)}px`;
        }
    };

    // Position Left Sticker #1 elements
    setPos('prevQRBox1', qrX, qrY, qrPxDim, qrPxDim);
    setPos('prevCodeTxt1', codeX, codeY);
    setPos('prevTitleTxt1', textX, titleY);
    setPos('prevSkuTxt1', textX, skuY);
    setPos('prevMrpTxt1', textX, mrpY);
    setPos('prevDiscTxt1', textX, discY);

    // Position Right Sticker #2 elements (with col2ShiftPx offset)
    setPos('prevQRBox2', qrX + col2ShiftPx, qrY, qrPxDim, qrPxDim);
    setPos('prevCodeTxt2', codeX + col2ShiftPx, codeY);
    setPos('prevTitleTxt2', textX + col2ShiftPx, titleY);
    setPos('prevSkuTxt2', textX + col2ShiftPx, skuY);
    setPos('prevMrpTxt2', textX + col2ShiftPx, mrpY);
    setPos('prevDiscTxt2', textX + col2ShiftPx, discY);
}

/**
 * Get current form values as configuration payload object
 */
function readCurrentSettingsForm() {
    return {
        labelWidthMm: parseFloat(document.getElementById('cfgWidth')?.value || 40),
        labelHeightMm: parseFloat(document.getElementById('cfgHeight')?.value || 25),
        columns: parseInt(document.getElementById('cfgColumns')?.value || 2),
        gapMm: parseFloat(document.getElementById('cfgGap')?.value || 2),
        qrX: parseInt(document.getElementById('cfgQrX')?.value || 12),
        qrY: parseInt(document.getElementById('cfgQrY')?.value || 35),
        qrCellSize: parseInt(document.getElementById('cfgQrCellSize')?.value || 3),
        codeX: parseInt(document.getElementById('cfgCodeX')?.value || 12),
        codeY: parseInt(document.getElementById('cfgCodeY')?.value || 125),
        textX: parseInt(document.getElementById('cfgTextX')?.value || 115),
        textFont: document.getElementById('cfgTextFont')?.value || "2",
        titleY: parseInt(document.getElementById('cfgTitleY')?.value || 35),
        skuY: parseInt(document.getElementById('cfgSkuY')?.value || 62),
        mrpY: parseInt(document.getElementById('cfgMrpY')?.value || 90),
        discY: parseInt(document.getElementById('cfgDiscY')?.value || 118),
        col2ShiftDots: parseInt(document.getElementById('cfgCol2ShiftDots')?.value || 0)
    };
}

/**
 * Test Print 1 Sticker using live unsaved settings
 */
async function testPrintCurrentSettings() {
    const opts = readCurrentSettingsForm();
    showPrintToast('Sending test sticker to TSC TE244...', 'info');

    try {
        const response = await fetch('http://127.0.0.1:9191/print', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                printerName: "TSC TE244",
                labels: [{
                    sku: "TEST-ITEM",
                    basePrice: "1000.00",
                    discountPrice: "500.00",
                    damageCode: "WMF92601",
                    title: "GRM WAREHOUSE",
                    quantity: 1
                }],
                ...opts
            })
        });

        const res = await response.json();
        if (response.ok && res.success) {
            showPrintToast('Test sticker printed successfully!', 'success');
        } else {
            showPrintToast(res.message || 'Test print failed.', 'danger');
        }
    } catch (e) {
        showPrintToast('Print helper not responding: ' + e.message, 'danger');
    }
}

/**
 * Save Printer Settings permanently to printer_service/config.json
 */
async function savePrinterSettings() {
    const opts = readCurrentSettingsForm();

    try {
        const response = await fetch('http://127.0.0.1:9191/config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(opts)
        });

        const res = await response.json();
        if (response.ok && res.success) {
            showPrintToast('Printer alignment settings saved permanently!', 'success');
            const modalEl = document.getElementById('printerSettingsModal');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        } else {
            showPrintToast(res.message || 'Failed to save settings.', 'danger');
        }
    } catch (e) {
        showPrintToast('Could not reach print helper service to save settings.', 'danger');
    }
}

function validateLabelBatchForm(e) {
    const stitchingSelect = document.getElementById('stitchingBatchSelect');
    if (!stitchingSelect || !stitchingSelect.value) {
        stitchingSelect?.classList.add('is-invalid', 'border-danger');
        alert('Select Stitching Name is required. Please choose a Stitching Batch.');
        stitchingSelect?.focus();
        if (e && e.preventDefault) e.preventDefault();
        return false;
    }
    stitchingSelect?.classList.remove('is-invalid', 'border-danger');

    const itemInputs = document.querySelectorAll('#variantsTableBody tr');
    if (itemInputs.length === 0) {
        alert('Please select a Stitching Name to generate variant label items.');
        stitchingSelect?.focus();
        if (e && e.preventDefault) e.preventDefault();
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    const stitchingSelect = document.getElementById('stitchingBatchSelect');
    if (stitchingSelect && stitchingSelect.value) {
        handleStitchingBatchChange(stitchingSelect.value);
    }
});
</script>
