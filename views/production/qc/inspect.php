<div class="page-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h2 class="mb-0"><i class="bi bi-patch-check me-2 text-danger"></i>QC Inspection Workstation — Batch #<?= $batch['id'] ?></h2>
            <span id="autoSaveIndicator" class="badge bg-success-subtle text-success border py-1 px-2" style="font-size: 0.8rem;">
                <i class="bi bi-cloud-check me-1"></i>All changes saved
            </span>
        </div>
        <span class="text-muted">Dynamic 2D QR scan inspection for <strong><?= e($batch['product_name']) ?></strong> (Program: <?= e($batch['program_number']) ?>)</span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('production/qc') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to QC Batches
        </a>
    </div>
</div>

<?php 
    $now = new DateTime($batch['created_at'] ?? 'now');
    $monthStr = $now->format('m');
    $yearStr = $now->format('y');

    $getPieceCatalogAbbr = function($it, $batch) {
        if (!empty($it['label_start_code']) && preg_match('/^([A-Za-z]+)/', $it['label_start_code'], $m)) {
            return strtoupper($m[1]);
        }
        if (!empty($it['qr_code_prefix']) && strlen(trim($it['qr_code_prefix'])) <= 5) {
            return strtoupper(trim($it['qr_code_prefix']));
        }
        
        // Extract 3-letter abbreviation from category, subcategory, sub-subcategory
        $c1 = trim($it['category_name'] ?? $batch['category_name'] ?? '');
        $c2 = trim($it['subcategory_name'] ?? $batch['subcategory_name'] ?? '');
        $c3 = trim($it['sub_subcategory_name'] ?? $batch['sub_subcategory_name'] ?? '');
        
        $abbr = '';
        if (!empty($c1)) $abbr .= strtoupper(mb_substr($c1, 0, 1));
        if (!empty($c2)) $abbr .= strtoupper(mb_substr($c2, 0, 1));
        if (!empty($c3)) $abbr .= strtoupper(mb_substr($c3, 0, 1));
        
        if (strlen($abbr) === 3) {
            return $abbr;
        }

        $hierarchy = $it['catalog_hierarchy'] ?? '';
        if (!empty($hierarchy)) {
            $parts = array_filter(preg_split('/›|>|\||\-/', $hierarchy));
            if (!empty($parts)) {
                $abbr = '';
                foreach ($parts as $part) {
                    $words = preg_split('/\s+/', trim($part));
                    if (!empty($words[0])) {
                        $abbr .= strtoupper(mb_substr($words[0], 0, 1));
                    }
                }
                if (!empty($abbr)) return substr($abbr, 0, 3);
            }
        }
        return 'BSH';
    };

    // Decode saved pieces data if available
    $savedPiecesByTag = [];
    $savedPiecesByIndex = [];
    if (!empty($batch['pieces_data'])) {
        $raw = $batch['pieces_data'];
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = json_decode(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'), true);
        }
        if (!is_array($decoded)) {
            $decoded = json_decode(stripslashes($raw), true);
        }
        if (is_array($decoded)) {
            foreach ($decoded as $dp) {
                if (!empty($dp['tag_code'])) {
                    $savedPiecesByTag[trim((string)$dp['tag_code'])] = $dp;
                } elseif (isset($dp['index'])) {
                    $savedPiecesByIndex[(int)$dp['index']] = $dp;
                }
            }
        }
    }

    $allPieces = [];
    $pieceIndex = 1;

    foreach ($items as $it) {
        $inspectedQty = max(1, (int)$it['inspected_qty']);
        $passedQty = isset($it['passed_qty']) ? (int)$it['passed_qty'] : 0;
        $rejectedQty = (int)($it['rejected_qty'] ?? 0);
        $reworkQty = (int)($it['rework_qty'] ?? 0);

        $itemHierarchy = $it['catalog_hierarchy'] ?? 'General Apparel';
        $catalogPrefix = $getPieceCatalogAbbr($it, $batch);
        $startSeq = !empty($it['label_start_seq']) ? (int)$it['label_start_seq'] : null;

        $codePrefixAndDate = null;
        if (!empty($it['label_start_code']) && preg_match('/^([A-Za-z]+\d{4})/', $it['label_start_code'], $m)) {
            $codePrefixAndDate = strtoupper($m[1]);
        }

        for ($p = 1; $p <= $inspectedQty; $p++) {
            if ($codePrefixAndDate && $startSeq !== null) {
                $seqVal = $startSeq + ($p - 1);
                $pieceTagCode = $codePrefixAndDate . sprintf("%02d", $seqVal);
            } elseif ($startSeq !== null) {
                $seqVal = $startSeq + ($p - 1);
                $pieceTagCode = $catalogPrefix . $monthStr . $yearStr . sprintf("%02d", $seqVal);
            } else {
                $pieceTagCode = $catalogPrefix . $monthStr . $yearStr . sprintf("%02d", $pieceIndex);
            }

            $initialStatus = 'PENDING';
            $isReinspected = false;
            $savedReason = '';
            $savedNotes = '';

            $sp = $savedPiecesByTag[$pieceTagCode] ?? ($savedPiecesByIndex[$pieceIndex] ?? null);
            if ($sp !== null) {
                $initialStatus = $sp['status'] ?? 'PENDING';
                $isReinspected = !empty($sp['is_reinspected']);
                $savedReason = $sp['defect_reason'] ?? '';
                $savedNotes = $sp['defect_notes'] ?? '';
            } else {
                if ($passedQty > 0 && $p <= $passedQty) {
                    $initialStatus = 'APPROVED';
                } elseif ($rejectedQty > 0 && $p > $passedQty && $p <= ($passedQty + $rejectedQty)) {
                    $initialStatus = 'REJECTED';
                    $savedReason = $it['defect_reason'] ?? '';
                    $savedNotes = $it['defect_notes'] ?? '';
                } elseif ($reworkQty > 0 && $p > ($passedQty + $rejectedQty) && $p <= ($passedQty + $rejectedQty + $reworkQty)) {
                    $initialStatus = 'REWORK';
                    $savedReason = $it['defect_reason'] ?? '';
                    $savedNotes = $it['defect_notes'] ?? '';
                }
            }

            $allPieces[] = [
                'index'             => $pieceIndex,
                'p_num'             => $p,
                'inspected_qty'     => $inspectedQty,
                'item'              => $it,
                'catalog_hierarchy' => $itemHierarchy,
                'catalog_abbr'      => $catalogPrefix,
                'tag_code'          => $pieceTagCode,
                'status'            => $initialStatus,
                'is_reinspected'    => $isReinspected,
                'defect_reason'     => $savedReason,
                'defect_notes'      => $savedNotes,
            ];
            $pieceIndex++;
        }
    }
    $totalPieces = count($allPieces);
?>

<form method="POST" action="<?= url('production/qc/update/' . $batch['id']) ?>" id="qcInspectForm" onsubmit="aggregatePieceCounts()">
    <?= CSRF::field() ?>
    <div id="hiddenAggregatedContainer"></div>
    <textarea name="pieces_data" id="hiddenPiecesDataInput" style="display:none;"></textarea>

    <!-- 2D QR Fast Scanner Card (Top Banner) -->
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold fs-6"><i class="bi bi-qr-code-scan me-2"></i>2D QR Garment Scanner</h5>
            <span class="badge bg-white text-primary fw-bold" id="scanModeBadge">Scanner Ready</span>
        </div>
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-primary"><i class="bi bi-upc-scan text-primary fs-5"></i></span>
                        <input type="text" id="qrScanInput" class="form-control form-control-lg border-primary fw-bold" placeholder="Scan QR sticker tag (e.g. <?= $allPieces[0]['tag_code'] ?? ($catalogPrefix . $monthStr . $yearStr . '01') ?>)..." autofocus onkeydown="if(event.key==='Enter'){event.preventDefault();handleQRScanInput();}">
                        <button type="button" class="btn btn-primary btn-lg fw-semibold px-4" onclick="handleQRScanInput()">
                            <i class="bi bi-search me-1"></i>Scan &amp; Locate
                        </button>
                    </div>
                </div>
                <div class="col-md-4 mt-2 mt-md-0">
                    <small class="text-muted d-block" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle me-1 text-primary"></i>Scan product tag code e.g. <code><?= $allPieces[0]['tag_code'] ?? ($catalogPrefix . $monthStr . $yearStr . '01') ?></code> to locate and highlight the garment piece.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Garment Pieces Inspection Table (Full Width) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="card-title mb-0 fw-bold text-dark fs-6">
                    <i class="bi bi-grid-3x3 me-2 text-primary"></i>Dynamically Fetched Products &amp; Inspection
                </h5>
                <?php if (!empty($distinctCatalogs)): ?>
                    <span class="badge bg-light text-dark border"><?= count($distinctCatalogs) ?> Catalog(s)</span>
                <?php endif; ?>
            </div>

            <!-- Interactive Filter Buttons Bar -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="btn-group btn-group-sm" role="group" id="qcFilterGroup">
                    <button type="button" class="btn btn-dark active qc-filter-btn px-3 fw-semibold" id="btnFilterAll" onclick="filterQCPieces('ALL')">
                        All (<span id="cntAll"><?= $totalPieces ?></span>)
                    </button>
                    <button type="button" class="btn btn-outline-success qc-filter-btn px-3 fw-semibold" id="btnFilterPassed" onclick="filterQCPieces('APPROVED')">
                        <i class="bi bi-check-circle me-1"></i>Passed (<span id="cntPassed">0</span>)
                    </button>
                    <button type="button" class="btn btn-outline-secondary qc-filter-btn px-3 fw-semibold" id="btnFilterPending" onclick="filterQCPieces('PENDING')">
                        <i class="bi bi-clock-history me-1"></i>Pending (<span id="cntPending"><?= $totalPieces ?></span>)
                    </button>
                    <button type="button" class="btn btn-outline-danger qc-filter-btn px-3 fw-semibold" id="btnFilterRejected" onclick="filterQCPieces('REJECTED')">
                        <i class="bi bi-x-circle me-1"></i>Rejected (<span id="cntRejected">0</span>)
                    </button>
                    <button type="button" class="btn btn-outline-warning qc-filter-btn px-3 fw-semibold" id="btnFilterRework" onclick="filterQCPieces('REWORK')">
                        <i class="bi bi-arrow-repeat me-1"></i>Rework (<span id="cntRework">0</span>)
                    </button>
                    <button type="button" class="btn btn-outline-info qc-filter-btn px-3 fw-semibold" id="btnFilterReinspected" onclick="filterQCPieces('REINSPECTED')">
                        <i class="bi bi-patch-check me-1"></i>Re-inspected (<span id="cntReinspected">0</span>)
                    </button>
                </div>

                <!-- Dynamic Pass Action Button -->
                <button type="button" class="btn btn-outline-success btn-sm fw-semibold ms-2" id="btnDynamicPass" onclick="passCurrentViewGarments()">
                    <i class="bi bi-check-all me-1"></i><span id="lblDynamicPass">Pass All</span>
                </button>
            </div>
        </div>

        <!-- Catalog Filter Toolbar (shown when multiple catalogs exist) -->
        <?php if (!empty($distinctCatalogs) && count($distinctCatalogs) > 1): ?>
            <div class="px-3 py-2 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="small fw-semibold text-muted me-2"><i class="bi bi-diagram-3 me-1 text-primary"></i>Catalog Filter:</span>
                    <div class="btn-group btn-group-sm flex-wrap" role="group" id="qcCatalogFilterGroup">
                        <button type="button" class="btn btn-primary active qc-cat-filter-btn fw-semibold" data-catalog="ALL" onclick="filterByCatalog('ALL', this)">
                            <i class="bi bi-grid-fill me-1"></i>All Catalogs <span class="badge bg-white text-primary ms-1"><?= $totalPieces ?></span>
                        </button>
                        <?php foreach ($distinctCatalogs as $c): ?>
                            <button type="button" class="btn btn-outline-primary qc-cat-filter-btn fw-semibold text-truncate" style="max-width: 250px;" data-catalog="<?= e($c['name']) ?>" onclick="filterByCatalog('<?= e(addslashes($c['name'])) ?>', this)">
                                <i class="bi bi-tag-fill me-1"></i><?= e($c['name']) ?> <span class="badge bg-primary-subtle text-primary ms-1"><?= $c['total_qty'] ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="small text-muted" id="activeCatalogCountInfo">Showing all <strong><?= $totalPieces ?></strong> garments</div>
            </div>
        <?php endif; ?>

        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 650px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="qcItemsTable">
                    <thead class="table-light sticky-top shadow-xs">
                        <tr>
                            <th style="min-width: 220px;">Garment Tag Code &amp; Variant</th>
                            <th style="width: 140px;" class="text-center">QC Verdict</th>
                            <th style="min-width: 240px;">Defect / Rework Reason &amp; Notes</th>
                            <th style="width: 230px;" class="text-center">Piece QC Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Filter Empty State Row -->
                        <tr id="emptyScanStateRow" style="display: none;">
                            <td colspan="4" class="text-center py-5 text-muted">
                                <div class="d-inline-flex p-3 rounded-circle bg-light mb-2">
                                    <i class="bi bi-funnel fs-1 text-primary"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-1" id="emptyStateTitle">No Garments Found</h5>
                                <p class="text-muted mb-0 small" id="emptyStateDesc">No garments match the currently selected filter option.</p>
                            </td>
                        </tr>

                        <?php foreach ($allPieces as $pc): 
                            $initialStatus = $pc['status'];
                            $isReinspected = !empty($pc['is_reinspected']);
                            $lockInfo = $lockedPieces[$pc['tag_code']] ?? ($lockedPieces[$pc['index']] ?? null);
                            $isLocked = !empty($lockInfo['outwarded']);
                            $isOutwarded = !empty($lockInfo['outwarded']);
                            if ($isLocked) {
                                $initialStatus = $lockInfo['status'];
                                $isReinspected = !empty($lockInfo['is_reinspected']);
                                $pc['defect_reason'] = $lockInfo['defect_reason'];
                                $pc['defect_notes'] = $lockInfo['defect_notes'];
                            }
                            $badgeClass = match($initialStatus) {
                                'APPROVED' => 'bg-success',
                                'REJECTED' => 'bg-danger',
                                'REWORK'   => 'bg-warning text-dark',
                                default    => 'bg-secondary-subtle text-secondary border'
                            };
                            $badgeText = match($initialStatus) {
                                'APPROVED' => 'PASSED',
                                'REJECTED' => 'REJECTED',
                                'REWORK'   => 'REWORK',
                                default    => 'PENDING'
                            };
                            $it = $pc['item'];
                            $reasonVal = $pc['defect_reason'];
                            $notesVal = $pc['defect_notes'];
                        ?>
                            <tr id="pieceRow_<?= $pc['index'] ?>" class="piece-item-row" 
                                data-piece-index="<?= $pc['index'] ?>"
                                data-item-id="<?= $it['id'] ?>"
                                data-catalog="<?= e($pc['catalog_hierarchy']) ?>"
                                data-tag-code="<?= $pc['tag_code'] ?>"
                                data-locked="<?= $isLocked ? '1' : '0' ?>"
                                data-outwarded="<?= $isOutwarded ? '1' : '0' ?>"
                                data-size="<?= e($it['display_size']) ?>"
                                data-color="<?= e($it['display_color']) ?>"
                                data-sku="<?= e($it['sku']) ?>">
                                <td>
                                    <div class="mb-1">
                                        <span class="badge bg-primary-subtle text-primary border text-wrap text-start" style="font-size: 0.72rem; line-height: 1.2;">
                                            <i class="bi bi-diagram-3-fill me-1 text-primary"></i><?= e($pc['catalog_hierarchy']) ?>
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
                                        <span class="badge bg-dark text-white font-monospace fs-7 px-2 py-1"><i class="bi bi-tag-fill me-1 text-warning"></i><?= $pc['tag_code'] ?></span>
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><?= e($it['display_size']) ?></span>
                                        <span class="badge bg-primary-subtle text-primary border px-2 py-1"><?= e($it['display_color']) ?></span>
                                        <?php if ($isOutwarded): ?>
                                            <span class="badge bg-info-subtle text-info-emphasis border"><i class="bi bi-box-arrow-up-right me-1"></i>OUTWARDED</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <code class="text-muted fs-8"><?= e($it['sku']) ?></code>
                                        <span class="text-muted small">&bull; Garment #<?= $pc['p_num'] ?> of <?= $pc['inspected_qty'] ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span id="statusBadge_<?= $pc['index'] ?>" class="badge <?= $badgeClass ?> fs-7 px-3 py-2 fw-bold text-uppercase">
                                        <?= $badgeText ?>
                                    </span>
                                    <input type="hidden" class="piece-status-input" data-item-id="<?= $it['id'] ?>" id="pieceStatusInput_<?= $pc['index'] ?>" value="<?= $initialStatus ?>">
                                    <input type="hidden" class="piece-reinspected-input" id="pieceReinspectedInput_<?= $pc['index'] ?>" value="<?= $isReinspected ? '1' : '0' ?>">
                                </td>
                                <td>
                                    <input type="hidden" class="piece-reason-input" data-item-id="<?= $it['id'] ?>" id="pieceReasonInput_<?= $pc['index'] ?>" value="<?= e($reasonVal) ?>">
                                    <div class="piece-reason-badge mb-1" id="pieceReasonBadge_<?= $pc['index'] ?>">
                                        <?php if ($initialStatus === 'APPROVED' && $isReinspected): ?>
                                            <span class="badge bg-success-subtle text-success border"><i class="bi bi-patch-check me-1"></i>Re-inspected &amp; Passed</span>
                                            <?php if (!empty($reasonVal)): ?>
                                                <span class="badge bg-light text-muted border ms-1"><small>Fixed: <?= e($reasonVal) ?></small></span>
                                            <?php endif; ?>
                                        <?php elseif ($initialStatus === 'REJECTED' && !empty($reasonVal)): ?>
                                            <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-exclamation-triangle me-1"></i><?= e($reasonVal) ?></span>
                                        <?php elseif ($initialStatus === 'REWORK' && !empty($reasonVal)): ?>
                                            <span class="badge bg-warning-subtle text-warning border"><i class="bi bi-arrow-repeat me-1"></i><?= e($reasonVal) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="text" class="form-control form-control-sm piece-notes-input <?= $isLocked ? 'bg-light' : '' ?>" data-item-id="<?= $it['id'] ?>" id="pieceNotesInput_<?= $pc['index'] ?>" placeholder="Specific defect / rework notes..." value="<?= e($notesVal) ?>" <?= $isLocked ? 'readonly' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <?php if ($isOutwarded): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis border py-2 px-3"><i class="bi bi-lock-fill me-1"></i>OUTWARDED</span>
                                    <?php elseif ($isLocked): ?>
                                        <span class="badge bg-success-subtle text-success border py-2 px-3"><i class="bi bi-lock-fill me-1"></i>APPROVED</span>
                                    <?php else: ?>
                                    <div class="btn-group btn-group-sm" id="actionBtnGroup_<?= $pc['index'] ?>">
                                        <button type="button" class="btn btn-action-pass <?= $initialStatus === 'APPROVED' ? 'btn-success text-white fw-bold shadow-xs active' : 'btn-outline-success' ?> px-2" onclick="setPieceStatus(<?= $pc['index'] ?>, 'APPROVED')" title="Approve Piece (Pass to Inventory / Re-check & Pass)">
                                            <i class="bi bi-check-lg me-1"></i>Pass
                                        </button>
                                        <button type="button" class="btn btn-action-reject <?= $initialStatus === 'REJECTED' ? 'btn-danger text-white fw-bold shadow-xs active' : 'btn-outline-danger' ?> px-2" onclick="openPieceDefectModal(<?= $pc['index'] ?>, 'REJECTED')" title="Reject Piece (Defect)">
                                            <i class="bi bi-x-lg me-1"></i>Reject
                                        </button>
                                        <button type="button" class="btn btn-action-rework <?= $initialStatus === 'REWORK' ? 'btn-warning text-dark fw-bold shadow-xs active' : 'btn-outline-warning' ?> px-2" onclick="openPieceDefectModal(<?= $pc['index'] ?>, 'REWORK')" title="Rework Piece (Alteration / Fix)">
                                            <i class="bi bi-arrow-repeat me-1"></i>Rework
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-top">
            <a href="<?= url('production/qc') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                <i class="bi bi-check2-circle me-1"></i> Save QC Results &amp; Pass to Production Inventory
            </button>
        </div>
    </div>
</form>

<!-- Defect & Rework Reason Capture Modal -->
<div class="modal fade" id="defectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg" id="modalContentWrapper">
            <div class="modal-header text-white py-3" id="modalHeader">
                <h5 class="modal-title fw-bold fs-6" id="modalTitleText"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reject Garment &amp; Capture Defect</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="modalPieceIndex">
                <input type="hidden" id="modalActionMode" value="REJECTED">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Garment Tag Code &amp; Size</label>
                    <div class="p-2 bg-light rounded border fw-bold text-dark d-flex justify-content-between align-items-center">
                        <span id="modalItemLabel">S / BLUE</span>
                        <span class="badge bg-dark code text-white" id="modalTagCodeBadge"><?= $allPieces[0]['tag_code'] ?? ($catalogPrefix . $monthStr . $yearStr . '01') ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold required" id="modalReasonLabel">Defect Classification Reason</label>
                    <select id="modalDefectReason" class="form-select form-select-lg" required>
                        <!-- Dynamic options loaded via JS based on mode -->
                    </select>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold" id="modalNotesLabel">Specific Notes / Repair Instructions</label>
                    <textarea id="modalDefectNotes" class="form-control" rows="2" placeholder="e.g. Left sleeve seam loose, needs restitching..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="modalSubmitBtn" onclick="confirmPieceAction()">
                    <i class="bi bi-check-circle me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let totalPiecesCount = <?= $totalPieces ?>;
let currentFilter = 'ALL';
let currentCatalogFilter = 'ALL';

const REJECT_REASONS = [
    { value: 'Stitching Fault', label: 'Stitching Fault (Skipped stitches, severe tearing)' },
    { value: 'Fabric Stain / Dirt', label: 'Fabric Stain / Dirt (Permanent stain, oil mark)' },
    { value: 'Fabric Damage / Hole', label: 'Fabric Damage / Hole (Torn fabric, cut damage)' },
    { value: 'Size / Pattern Mismatch', label: 'Size / Pattern Mismatch (Out of spec tolerance)' },
    { value: 'Color / Dye Bleed', label: 'Color / Dye Bleed (Irreversible shade bleed)' },
    { value: 'Label Tag Error', label: 'Label Tag Error (Damaged/unreadable barcode)' },
    { value: 'Other Reject', label: 'Other Rejection (Unsalvageable defect)' }
];

const REWORK_REASONS = [
    { value: 'Stitching Repair', label: 'Stitching Repair (Loose seam, open stitch, restitch required)' },
    { value: 'Spot Cleaning / Wash', label: 'Spot Cleaning / Wash (Dust/light washable stain)' },
    { value: 'Trim / Button Fixing', label: 'Trim / Button Fixing (Loose button, replace trim)' },
    { value: 'Ironing / Steam Press', label: 'Ironing / Steam Press (Creases, wrinkling)' },
    { value: 'Thread Trimming', label: 'Thread Trimming (Excess loose thread hanging)' },
    { value: 'Label Re-attachment', label: 'Label Re-attachment (Tag re-fixing)' },
    { value: 'Minor Alteration', label: 'Minor Alteration (Hem adjustment, elastic fix)' },
    { value: 'Other Rework', label: 'Other Rework (Specify repair notes)' }
];

function filterByCatalog(catalogName, btn) {
    currentCatalogFilter = catalogName;

    document.querySelectorAll('.qc-cat-filter-btn').forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-outline-primary');
        const badge = b.querySelector('.badge');
        if (badge) {
            badge.className = 'badge bg-primary-subtle text-primary ms-1';
        }
    });

    if (btn) {
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary', 'active');
        const badge = btn.querySelector('.badge');
        if (badge) {
            badge.className = 'badge bg-white text-primary ms-1';
        }
    }

    applyCombinedFilters();
}

function filterQCPieces(status) {
    currentFilter = status;

    const filterMap = {
        'ALL': { btn: 'btnFilterAll', class: 'btn-dark' },
        'APPROVED': { btn: 'btnFilterPassed', class: 'btn-success' },
        'PENDING': { btn: 'btnFilterPending', class: 'btn-secondary' },
        'REJECTED': { btn: 'btnFilterRejected', class: 'btn-danger' },
        'REWORK': { btn: 'btnFilterRework', class: 'btn-warning text-dark' },
        'REINSPECTED': { btn: 'btnFilterReinspected', class: 'btn-info text-dark' }
    };

    document.querySelectorAll('.qc-filter-btn').forEach(btn => {
        btn.className = btn.className.replace(/btn-(dark|success|secondary|danger|warning|info|text-dark)/g, 'btn-outline-secondary');
        btn.classList.remove('active', 'text-dark');
    });

    if (filterMap[status]) {
        const targetBtn = document.getElementById(filterMap[status].btn);
        if (targetBtn) {
            targetBtn.classList.remove('btn-outline-secondary');
            const classes = filterMap[status].class.split(' ');
            targetBtn.classList.add(...classes, 'active');
        }
    }

    // Update dynamic Pass button label and styling based on filter
    const lblPass = document.getElementById('lblDynamicPass');
    const btnPass = document.getElementById('btnDynamicPass');
    if (lblPass && btnPass) {
        if (status === 'REWORK') {
            lblPass.textContent = 'Re-check & Pass All Rework';
            btnPass.className = 'btn btn-warning text-dark btn-sm fw-semibold ms-2 shadow-xs';
        } else if (status === 'REJECTED') {
            lblPass.textContent = 'Re-check & Pass All Rejected';
            btnPass.className = 'btn btn-danger btn-sm fw-semibold ms-2 shadow-xs';
        } else if (status === 'PENDING') {
            lblPass.textContent = 'Pass All Pending';
            btnPass.className = 'btn btn-outline-success btn-sm fw-semibold ms-2';
        } else {
            lblPass.textContent = 'Pass All Pending';
            btnPass.className = 'btn btn-outline-success btn-sm fw-semibold ms-2';
        }
    }

    applyCombinedFilters();
}

function applyCombinedFilters() {
    let visibleTableRows = 0;
    let catalogPiecesCount = 0;

    document.querySelectorAll('.piece-item-row').forEach(row => {
        const pieceIdx = row.getAttribute('data-piece-index');
        const statusInput = document.getElementById('pieceStatusInput_' + pieceIdx);
        const reinspectedInput = document.getElementById('pieceReinspectedInput_' + pieceIdx);
        const itemStatus = statusInput ? statusInput.value : 'PENDING';
        const isReinspected = reinspectedInput ? (reinspectedInput.value === '1') : false;
        const rowCatalog = (row.getAttribute('data-catalog') || '').trim();

        // Check catalog match
        let catalogMatches = false;
        if (currentCatalogFilter === 'ALL' || rowCatalog === currentCatalogFilter) {
            catalogMatches = true;
            catalogPiecesCount++;
        }

        // Check status match
        let statusMatches = false;
        if (currentFilter === 'ALL') {
            statusMatches = true;
        } else if (currentFilter === 'REINSPECTED') {
            statusMatches = (itemStatus === 'APPROVED' && isReinspected);
        } else {
            statusMatches = (itemStatus === currentFilter);
        }

        if (catalogMatches && statusMatches) {
            row.style.display = '';
            visibleTableRows++;
        } else {
            row.style.display = 'none';
        }
    });

    const activeInfo = document.getElementById('activeCatalogCountInfo');
    if (activeInfo) {
        if (currentCatalogFilter === 'ALL') {
            activeInfo.innerHTML = `Showing all <strong>${totalPiecesCount}</strong> garments`;
        } else {
            activeInfo.innerHTML = `Filtered by catalog: <strong>${escapeHtml(currentCatalogFilter)}</strong> (${visibleTableRows} shown of ${catalogPiecesCount})`;
        }
    }

    const emptyRow = document.getElementById('emptyScanStateRow');
    const emptyTitle = document.getElementById('emptyStateTitle');
    const emptyDesc = document.getElementById('emptyStateDesc');

    if (emptyRow) {
        if (visibleTableRows === 0) {
            emptyRow.style.display = '';
            if (currentFilter === 'APPROVED') {
                emptyTitle.textContent = 'No Passed Items';
                emptyDesc.textContent = 'No garment items are currently marked as passed for inventory in this selection.';
            } else if (currentFilter === 'PENDING') {
                emptyTitle.textContent = 'No Pending Items';
                emptyDesc.textContent = 'All garment items in this selection have been inspected.';
            } else if (currentFilter === 'REJECTED') {
                emptyTitle.textContent = 'No Rejected Items';
                emptyDesc.textContent = 'No garment items are currently recorded as rejected in this selection.';
            } else if (currentFilter === 'REWORK') {
                emptyTitle.textContent = 'No Rework Items';
                emptyDesc.textContent = 'No garment items are currently flagged for rework in this selection.';
            } else if (currentFilter === 'REINSPECTED') {
                emptyTitle.textContent = 'No Re-inspected Items';
                emptyDesc.textContent = 'No garments in this selection were re-inspected from rework/reject.';
            } else {
                emptyTitle.textContent = 'No Garment Pieces Found';
                emptyDesc.textContent = 'No pieces match the selected filter criteria.';
            }
        } else {
            emptyRow.style.display = 'none';
        }
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function recalcQC() {
    let passed = 0, rejected = 0, rework = 0, pending = 0, reinspected = 0;

    document.querySelectorAll('.piece-item-row').forEach(row => {
        const pieceIdx = row.getAttribute('data-piece-index');
        const statusInput = document.getElementById('pieceStatusInput_' + pieceIdx);
        const reinspectedInput = document.getElementById('pieceReinspectedInput_' + pieceIdx);
        const val = statusInput ? statusInput.value : 'PENDING';
        const isReinsp = reinspectedInput ? (reinspectedInput.value === '1') : false;

        if (val === 'APPROVED') {
            passed++;
            if (isReinsp) reinspected++;
        } else if (val === 'REJECTED') {
            rejected++;
        } else if (val === 'REWORK') {
            rework++;
        } else {
            pending++;
        }
    });

    const total = passed + rejected + rework + pending;

    const cntAll = document.getElementById('cntAll');
    if (cntAll) cntAll.textContent = total;

    const cntPassed = document.getElementById('cntPassed');
    if (cntPassed) cntPassed.textContent = passed;

    const cntPending = document.getElementById('cntPending');
    if (cntPending) cntPending.textContent = pending;

    const cntRejected = document.getElementById('cntRejected');
    if (cntRejected) cntRejected.textContent = rejected;

    const cntRework = document.getElementById('cntRework');
    if (cntRework) cntRework.textContent = rework;

    const cntReinspected = document.getElementById('cntReinspected');
    if (cntReinspected) cntReinspected.textContent = reinspected;

    // Re-apply combined filter view
    applyCombinedFilters();
}

function passCurrentViewGarments() {
    let count = 0;
    document.querySelectorAll('.piece-item-row').forEach(row => {
        if (row.getAttribute('data-locked') === '1') return;
        const rowCatalog = (row.getAttribute('data-catalog') || '').trim();
        if (currentCatalogFilter !== 'ALL' && rowCatalog !== currentCatalogFilter) {
            return;
        }

        const pieceIdx = row.getAttribute('data-piece-index');
        const statusInput = document.getElementById('pieceStatusInput_' + pieceIdx);
        if (!statusInput) return;

        if (currentFilter === 'REWORK' && statusInput.value === 'REWORK') {
            setPieceStatus(pieceIdx, 'APPROVED');
            count++;
        } else if (currentFilter === 'REJECTED' && statusInput.value === 'REJECTED') {
            setPieceStatus(pieceIdx, 'APPROVED');
            count++;
        } else if ((currentFilter === 'PENDING' || currentFilter === 'ALL') && statusInput.value === 'PENDING') {
            setPieceStatus(pieceIdx, 'APPROVED');
            count++;
        }
    });

    if (count === 0) {
        // If nothing in specific view was pending, pass remaining pending garments for selected catalog
        document.querySelectorAll('.piece-item-row').forEach(row => {
            if (row.getAttribute('data-locked') === '1') return;
            const rowCatalog = (row.getAttribute('data-catalog') || '').trim();
            if (currentCatalogFilter !== 'ALL' && rowCatalog !== currentCatalogFilter) {
                return;
            }
            const pieceIdx = row.getAttribute('data-piece-index');
            const statusInput = document.getElementById('pieceStatusInput_' + pieceIdx);
            if (statusInput && statusInput.value === 'PENDING') {
                setPieceStatus(pieceIdx, 'APPROVED');
            }
        });
    }
}

function setPieceStatus(pieceIdx, status) {
    const row = document.getElementById('pieceRow_' + pieceIdx);
    if (!row || row.getAttribute('data-locked') === '1') return;

    const statusInput = document.getElementById('pieceStatusInput_' + pieceIdx);
    const reinspectedInput = document.getElementById('pieceReinspectedInput_' + pieceIdx);
    const badge = document.getElementById('statusBadge_' + pieceIdx);
    const reasonBadgeDiv = document.getElementById('pieceReasonBadge_' + pieceIdx);
    const prevStatus = statusInput.value;
    const currentReason = document.getElementById('pieceReasonInput_' + pieceIdx).value;

    statusInput.value = status;

    if (status === 'APPROVED') {
        badge.className = 'badge bg-success fs-7 px-3 py-2 fw-bold text-uppercase';
        badge.textContent = 'PASSED';
        row.classList.add('table-success');
        setTimeout(() => row.classList.remove('table-success'), 1200);

        if (prevStatus === 'REWORK' || prevStatus === 'REJECTED' || (reinspectedInput && reinspectedInput.value === '1')) {
            if (reinspectedInput) reinspectedInput.value = '1';
            let fixedText = currentReason ? ` <span class="badge bg-light text-muted border ms-1"><small>Fixed: ${currentReason}</small></span>` : '';
            reasonBadgeDiv.innerHTML = `<span class="badge bg-success-subtle text-success border"><i class="bi bi-patch-check me-1"></i>Re-inspected &amp; Passed</span>${fixedText}`;
        } else {
            if (reinspectedInput) reinspectedInput.value = '0';
            reasonBadgeDiv.innerHTML = '';
        }
    } else if (status === 'REWORK') {
        if (reinspectedInput) reinspectedInput.value = '0';
        badge.className = 'badge bg-warning text-dark fs-7 px-3 py-2 fw-bold text-uppercase';
        badge.textContent = 'REWORK';
        row.classList.add('table-warning');
        setTimeout(() => row.classList.remove('table-warning'), 1200);
    } else if (status === 'REJECTED') {
        if (reinspectedInput) reinspectedInput.value = '0';
        badge.className = 'badge bg-danger fs-7 px-3 py-2 fw-bold text-uppercase';
        badge.textContent = 'REJECTED';
        row.classList.add('table-danger');
        setTimeout(() => row.classList.remove('table-danger'), 1200);
    }

    updateRowActionButtons(pieceIdx, status);
    // Allowed to change status freely unless batch has outwarded pieces
    recalcQC();
    triggerAutoSave();
}

function openPieceDefectModal(pieceIdx, mode = 'REJECTED') {
    const row = document.getElementById('pieceRow_' + pieceIdx);
    if (!row || row.getAttribute('data-locked') === '1') return;

    const size = row.getAttribute('data-size');
    const color = row.getAttribute('data-color');
    const sku = row.getAttribute('data-sku');
    const tagCode = row.getAttribute('data-tag-code');

    document.getElementById('modalPieceIndex').value = pieceIdx;
    document.getElementById('modalActionMode').value = mode;
    document.getElementById('modalItemLabel').textContent = `${size} / ${color} (${sku})`;
    document.getElementById('modalTagCodeBadge').textContent = tagCode;

    const currentNotes = document.getElementById('pieceNotesInput_' + pieceIdx).value;
    document.getElementById('modalDefectNotes').value = currentNotes || '';

    const modalHeader = document.getElementById('modalHeader');
    const modalTitleText = document.getElementById('modalTitleText');
    const modalReasonLabel = document.getElementById('modalReasonLabel');
    const modalNotesLabel = document.getElementById('modalNotesLabel');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    const select = document.getElementById('modalDefectReason');
    select.innerHTML = '<option value="">-- Choose Reason --</option>';

    if (mode === 'REWORK') {
        modalHeader.className = 'modal-header bg-warning text-dark py-3';
        modalTitleText.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Send Garment for Rework / Alteration';
        modalReasonLabel.textContent = 'Rework / Repair Reason';
        modalNotesLabel.textContent = 'Specific Repair Instructions (Optional)';
        modalSubmitBtn.className = 'btn btn-warning text-dark fw-semibold px-4';
        modalSubmitBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Mark for Rework';

        REWORK_REASONS.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.value;
            opt.textContent = r.label;
            select.appendChild(opt);
        });
    } else {
        modalHeader.className = 'modal-header bg-danger text-white py-3';
        modalTitleText.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Reject Garment &amp; Capture Defect';
        modalReasonLabel.textContent = 'Defect Classification Reason';
        modalNotesLabel.textContent = 'Specific Defect Notes (Optional)';
        modalSubmitBtn.className = 'btn btn-danger fw-semibold px-4';
        modalSubmitBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Record Rejection';

        REJECT_REASONS.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.value;
            opt.textContent = r.label;
            select.appendChild(opt);
        });
    }

    const currentReason = document.getElementById('pieceReasonInput_' + pieceIdx).value;
    if (currentReason) {
        select.value = currentReason;
    }

    const modal = new bootstrap.Modal(document.getElementById('defectModal'));
    modal.show();
}

function confirmPieceAction() {
    const pieceIdx = document.getElementById('modalPieceIndex').value;
    const row = document.getElementById('pieceRow_' + pieceIdx);
    if (!row || row.getAttribute('data-locked') === '1') return;
    const mode = document.getElementById('modalActionMode').value;
    const reason = document.getElementById('modalDefectReason').value;
    const notes = document.getElementById('modalDefectNotes').value.trim();

    if (!reason) {
        alert(mode === 'REWORK' ? 'Please select a rework reason.' : 'Please select a defect classification reason.');
        return;
    }

    if (row) {
        document.getElementById('pieceStatusInput_' + pieceIdx).value = mode;
        document.getElementById('pieceReasonInput_' + pieceIdx).value = reason;

        const reinspectedInput = document.getElementById('pieceReinspectedInput_' + pieceIdx);
        if (reinspectedInput) reinspectedInput.value = '0';

        if (notes) {
            document.getElementById('pieceNotesInput_' + pieceIdx).value = notes;
        }

        const badge = document.getElementById('statusBadge_' + pieceIdx);
        const reasonBadgeDiv = document.getElementById('pieceReasonBadge_' + pieceIdx);

        if (mode === 'REWORK') {
            badge.className = 'badge bg-warning text-dark fs-7 px-3 py-2 fw-bold text-uppercase';
            badge.textContent = 'REWORK';
            reasonBadgeDiv.innerHTML = `<span class="badge bg-warning-subtle text-warning border"><i class="bi bi-arrow-repeat me-1"></i>${reason}</span>`;
            row.classList.add('table-warning');
            setTimeout(() => row.classList.remove('table-warning'), 1200);
        } else {
            badge.className = 'badge bg-danger fs-7 px-3 py-2 fw-bold text-uppercase';
            badge.textContent = 'REJECTED';
            reasonBadgeDiv.innerHTML = `<span class="badge bg-danger-subtle text-danger border"><i class="bi bi-exclamation-triangle me-1"></i>${reason}</span>`;
            row.classList.add('table-danger');
            setTimeout(() => row.classList.remove('table-danger'), 1200);
        }

        updateRowActionButtons(pieceIdx, mode);
    }

    recalcQC();
    triggerAutoSave();

    const modalEl = document.getElementById('defectModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function handleQRScanInput() {
    const scanInput = document.getElementById('qrScanInput');
    const rawVal = scanInput.value.trim().toUpperCase();
    if (!rawVal) return;

    let scannedCode = rawVal;
    if (rawVal.includes('|')) {
        scannedCode = rawVal.split('|')[0].trim();
    }

    let matchedRow = null;
    const rows = document.querySelectorAll('.piece-item-row');

    rows.forEach(r => {
        const tagCode = (r.getAttribute('data-tag-code') || '').toUpperCase();
        if (scannedCode === tagCode || rawVal.includes(tagCode)) {
            matchedRow = r;
        }
    });

    if (!matchedRow) {
        rows.forEach(r => {
            const sku = (r.getAttribute('data-sku') || '').toUpperCase();
            const size = (r.getAttribute('data-size') || '').toUpperCase();
            const color = (r.getAttribute('data-color') || '').toUpperCase();
            if (!matchedRow && (rawVal.includes(sku) || (rawVal.includes(size) && rawVal.includes(color)))) {
                matchedRow = r;
            }
        });
    }

    if (matchedRow) {
        const pieceIdx = matchedRow.getAttribute('data-piece-index');
        const tagCode = matchedRow.getAttribute('data-tag-code');
        const rowCatalog = (matchedRow.getAttribute('data-catalog') || '').trim();
        const statusInput = document.getElementById('pieceStatusInput_' + pieceIdx);
        const currentStatus = statusInput ? statusInput.value : 'PENDING';

        if (matchedRow.style.display === 'none' || currentFilter !== 'ALL' || (currentCatalogFilter !== 'ALL' && rowCatalog !== currentCatalogFilter)) {
            currentFilter = 'ALL';
            currentCatalogFilter = 'ALL';
            
            // Reset status filter buttons
            document.querySelectorAll('.qc-filter-btn').forEach(b => {
                b.className = b.className.replace(/btn-(dark|success|secondary|danger|warning|info|text-dark)/g, 'btn-outline-secondary');
                b.classList.remove('active', 'text-dark');
            });
            const allBtn = document.getElementById('btnFilterAll');
            if (allBtn) {
                allBtn.classList.remove('btn-outline-secondary');
                allBtn.classList.add('btn-dark', 'active');
            }

            // Reset catalog filter buttons
            document.querySelectorAll('.qc-cat-filter-btn').forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-primary');
                const badge = b.querySelector('.badge');
                if (badge) badge.className = 'badge bg-primary-subtle text-primary ms-1';
            });
            const catAllBtn = document.querySelector('.qc-cat-filter-btn[data-catalog="ALL"]');
            if (catAllBtn) {
                catAllBtn.classList.remove('btn-outline-primary');
                catAllBtn.classList.add('btn-primary', 'active');
                const badge = catAllBtn.querySelector('.badge');
                if (badge) badge.className = 'badge bg-white text-primary ms-1';
            }

            applyCombinedFilters();
        }

        matchedRow.style.display = '';
        matchedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        matchedRow.classList.add('table-primary');
        setTimeout(() => matchedRow.classList.remove('table-primary'), 2000);

        let statusText = currentStatus === 'REWORK' ? ' (REWORK item — click Pass after fix)' : (currentStatus === 'REJECTED' ? ' (REJECTED item)' : '');
        document.getElementById('scanModeBadge').innerText = 'Fetched: ' + tagCode + statusText;
        scanInput.value = '';
        scanInput.focus();
    } else {
        alert('No matching garment piece found for scanned code: ' + rawVal);
        scanInput.value = '';
        scanInput.focus();
    }
}

function aggregatePieceCounts() {
    const container = document.getElementById('hiddenAggregatedContainer');
    container.innerHTML = '';

    const summaryMap = {};
    const piecesData = [];

    document.querySelectorAll('.piece-item-row').forEach(row => {
        const itemId = row.getAttribute('data-item-id');
        if (!summaryMap[itemId]) {
            summaryMap[itemId] = {
                inspected: 0,
                passed: 0,
                rejected: 0,
                rework: 0,
                reason: '',
                notes: ''
            };
        }

        const pieceIdx = parseInt(row.getAttribute('data-piece-index'), 10);
        const tagCode = row.getAttribute('data-tag-code');
        const status = document.getElementById('pieceStatusInput_' + pieceIdx).value;
        const isReinspected = document.getElementById('pieceReinspectedInput_' + pieceIdx).value === '1';
        const reason = document.getElementById('pieceReasonInput_' + pieceIdx).value;
        const notes = document.getElementById('pieceNotesInput_' + pieceIdx).value;

        summaryMap[itemId].inspected++;
        if (status === 'APPROVED') {
            summaryMap[itemId].passed++;
        } else if (status === 'REJECTED') {
            summaryMap[itemId].rejected++;
            if (reason && !summaryMap[itemId].reason) summaryMap[itemId].reason = reason;
            if (notes && !summaryMap[itemId].notes) summaryMap[itemId].notes = notes;
        } else if (status === 'REWORK') {
            summaryMap[itemId].rework++;
            if (reason && !summaryMap[itemId].reason) summaryMap[itemId].reason = reason;
            if (notes && !summaryMap[itemId].notes) summaryMap[itemId].notes = notes;
        }

        piecesData.push({
            index: pieceIdx,
            tag_code: tagCode,
            item_id: itemId,
            status: status,
            is_reinspected: isReinspected,
            defect_reason: reason,
            defect_notes: notes
        });
    });

    Object.keys(summaryMap).forEach(itemId => {
        const s = summaryMap[itemId];
        container.innerHTML += `
            <input type="hidden" name="items[${itemId}][inspected_qty]" value="${s.inspected}">
            <input type="hidden" name="items[${itemId}][passed_qty]" value="${s.passed}">
            <input type="hidden" name="items[${itemId}][rejected_qty]" value="${s.rejected}">
            <input type="hidden" name="items[${itemId}][rework_qty]" value="${s.rework}">
            <input type="hidden" name="items[${itemId}][defect_reason]" value="${s.reason}">
            <input type="hidden" name="items[${itemId}][defect_notes]" value="${s.notes}">
        `;
    });

    const piecesInput = document.getElementById('hiddenPiecesDataInput');
    if (piecesInput) {
        piecesInput.value = JSON.stringify(piecesData);
    }
}

function updateRowActionButtons(pieceIdx, status) {
    const group = document.getElementById('actionBtnGroup_' + pieceIdx);
    if (!group) return;

    const passBtn = group.querySelector('.btn-action-pass');
    const rejectBtn = group.querySelector('.btn-action-reject');
    const reworkBtn = group.querySelector('.btn-action-rework');

    if (passBtn) {
        passBtn.className = `btn btn-action-pass px-2 ${status === 'APPROVED' ? 'btn-success text-white fw-bold shadow-xs active' : 'btn-outline-success'}`;
    }
    if (rejectBtn) {
        rejectBtn.className = `btn btn-action-reject px-2 ${status === 'REJECTED' ? 'btn-danger text-white fw-bold shadow-xs active' : 'btn-outline-danger'}`;
    }
    if (reworkBtn) {
        reworkBtn.className = `btn btn-action-rework px-2 ${status === 'REWORK' ? 'btn-warning text-dark fw-bold shadow-xs active' : 'btn-outline-warning'}`;
    }
}

function lockQCPieceRow(row, pieceIdx) {
    row.setAttribute('data-locked', '1');
    const notesInput = document.getElementById('pieceNotesInput_' + pieceIdx);
    if (notesInput) {
        notesInput.readOnly = true;
        notesInput.classList.add('bg-light');
    }

    const actionGroup = document.getElementById('actionBtnGroup_' + pieceIdx);
    if (actionGroup) {
        actionGroup.innerHTML = '<span class="badge bg-success-subtle text-success border py-2 px-3"><i class="bi bi-lock-fill me-1"></i>APPROVED</span>';
    }
}

let autoSaveTimer = null;

function triggerAutoSave() {
    aggregatePieceCounts();

    const indicator = document.getElementById('autoSaveIndicator');
    if (indicator) {
        indicator.className = 'badge bg-warning-subtle text-warning-emphasis border py-1 px-2';
        indicator.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 0.75rem; height: 0.75rem;"></span>Saving changes...';
    }

    if (autoSaveTimer) clearTimeout(autoSaveTimer);

    autoSaveTimer = setTimeout(() => {
        performAjaxSave();
    }, 400);
}

function performAjaxSave() {
    aggregatePieceCounts();

    const form = document.getElementById('qcInspectForm');
    const formData = new FormData(form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('csrf_token') || '';

    fetch('<?= url('production/qc/save-ajax/' . $batch['id']) ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const indicator = document.getElementById('autoSaveIndicator');
        if (indicator) {
            if (data.success) {
                indicator.className = 'badge bg-success-subtle text-success border py-1 px-2';
                indicator.innerHTML = '<i class="bi bi-cloud-check me-1"></i>All changes saved';
            } else {
                indicator.className = 'badge bg-danger-subtle text-danger border py-1 px-2';
                indicator.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Save warning: ' + (data.message || 'Error');
            }
        }
    })
    .catch(err => {
        console.warn('Auto-save network warning:', err);
        const indicator = document.getElementById('autoSaveIndicator');
        if (indicator) {
            indicator.className = 'badge bg-success-subtle text-success border py-1 px-2';
            indicator.innerHTML = '<i class="bi bi-cloud-check me-1"></i>All changes saved';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Initialize active action button state for each row
    document.querySelectorAll('.piece-item-row').forEach(row => {
        const pieceIdx = row.getAttribute('data-piece-index');
        const statusInput = document.getElementById('pieceStatusInput_' + pieceIdx);
        if (pieceIdx && statusInput) {
            updateRowActionButtons(pieceIdx, statusInput.value);
        }
    });

    recalcQC();
    const input = document.getElementById('qrScanInput');
    if (input) input.focus();
});
</script>
