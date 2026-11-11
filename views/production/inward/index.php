<?php
$locationJson = json_encode($locations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$destinationLabel = $destinationType === 'CONCERN' ? 'Concern Inventory' : 'Production Unit';
?>
<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-box-arrow-in-down me-2 text-primary"></i>Production Inward / GRN</h2>
        <span class="text-muted">Receive products from other concerns, warehouses, shops, and production units.</span>
    </div>
    <a href="<?= url('production/inventory') ?>" class="btn btn-outline-secondary"><i class="bi bi-boxes me-1"></i>Production Inventory</a>
</div>

<style>
.piece-qr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
    gap: .75rem;
}
.piece-qr-card {
    min-width: 0;
    padding: .5rem;
    border: 1px solid #dee2e6;
    border-radius: .5rem;
    background: #fff;
    text-align: center;
}
.piece-qr-code {
    min-height: 88px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.piece-qr-code img,
.piece-qr-code canvas {
    max-width: 100%;
    height: auto;
}
.piece-qr-number {
    display: block;
    overflow-wrap: anywhere;
    font-size: .75rem;
}
</style>

<form method="GET" action="<?= url('production/inward') ?>" class="row g-2 align-items-end mb-3">
    <?php if ($scopedUnitId === 0): ?>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Destination Inventory</label>
            <div class="form-control bg-light text-danger">No production unit assigned in active concern</div>
        </div>
    <?php else: ?>
        <div class="col-md-4">
            <label class="form-label small fw-semibold" for="destinationFilter">Destination Inventory</label>
            <select class="form-select" name="destination" id="destinationFilter" onchange="this.form.submit()">
                <option value="CONCERN" <?= $destinationType === 'CONCERN' ? 'selected' : '' ?>>Concern Inventory (shared)</option>
                <?php foreach ($units as $unit): ?>
                    <option value="UNIT:<?= (int)$unit['id'] ?>" <?= $destinationType === 'PRODUCTION_UNIT' && (int)$unitId === (int)$unit['id'] ? 'selected' : '' ?>><?= e($unit['name']) ?> (<?= e($unit['code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-md-5">
        <label class="form-label small fw-semibold" for="inwardSearch">Search GRN, batch, or concern</label>
        <input class="form-control" type="search" name="search" id="inwardSearch" value="<?= e($search) ?>" placeholder="GRN number, batch number, source...">
    </div>
    <div class="col-auto"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
</form>

<?php if (!empty($pendingConcernBatches)): ?>
<div class="card border-primary shadow-sm mb-4">
    <div class="card-header bg-primary bg-opacity-10 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-1 fw-bold"><i class="bi bi-truck me-2 text-primary"></i>Production Batches Awaiting Receipt</h5>
            <small class="text-muted">Scan individual piece labels to verify condition, or scan the batch QR to inward all dispatched pieces as accepted into <?= e(strtolower($destinationLabel)) ?>.</small>
        </div>
        <span class="badge bg-primary rounded-pill"><?= count($pendingConcernBatches) ?> awaiting</span>
    </div>
    <div class="card-body">
        <?php foreach ($pendingConcernBatches as $batch): ?>
            <div class="border rounded p-3 mb-3">
                <?php
                    $expectedTagMap = [];
                    $extraTagMap = [];
                    $tagCount = 0;
                    $tagSet = [];
                    $labelsComplete = true;
                    foreach ($batch['items'] as $item) {
                        $tags = is_array($item['piece_tags'] ?? null) ? $item['piece_tags'] : [];
                        $tagCount += count($tags);
                        if (count($tags) !== (int)$item['expected_qty']) {
                            $labelsComplete = false;
                        }
                        foreach ($tags as $tag) {
                            if (isset($tagSet[$tag])) {
                                $labelsComplete = false;
                            }
                            $tagSet[$tag] = true;
                            $expectedTagMap[$tag] = [
                                'product' => $item['product_name'],
                                'sku' => $item['sku'],
                                'variant' => trim(($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? '')),
                                'expected' => true,
                            ];
                        }
                    }
                    foreach ($batch['items'] as $item) {
                        $approvedTags = is_array($item['approved_piece_tags'] ?? null) ? $item['approved_piece_tags'] : [];
                        foreach ($approvedTags as $tag) {
                            $tag = strtoupper(trim((string)$tag));
                            if ($tag !== '' && !isset($expectedTagMap[$tag])) {
                                $extraTagMap[$tag] = [
                                    'product' => $item['product_name'],
                                    'sku' => $item['sku'],
                                    'variant' => trim(($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? '')),
                                    'expected' => false,
                                ];
                            }
                        }
                    }
                    $expectedPieceCount = array_sum(array_map(static fn(array $item): int => (int)$item['expected_qty'], $batch['items']));
                    $labelsComplete = $labelsComplete && $tagCount === $expectedPieceCount && $expectedPieceCount > 0;
                    $expectedTagJson = json_encode($expectedTagMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                    $extraTagJson = json_encode($extraTagMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                ?>
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <div class="fw-bold font-monospace text-primary"><?= e($batch['batch_number']) ?></div>
                        <div><?= e($batch['product_name']) ?> <span class="text-muted">· Program <?= e($batch['program_number']) ?></span></div>
                        <small class="text-muted">
                            From <?= e($batch['source_concern_name']) ?> · Outward <?= e($batch['outward_number']) ?>
                            <?php if (!empty($batch['vehicle_number'])): ?> · <?= e($batch['vehicle_number']) ?><?php endif; ?>
                            · <?= e(formatDate($batch['dispatch_date'])) ?>
                        </small>
                    </div>
                    <span class="badge bg-warning-subtle text-dark border">Dispatched · <?= number_format((int)$batch['total_qty']) ?> pcs</span>
                </div>
                <?php if (empty($batch['items'])): ?>
                    <div class="alert alert-warning py-2 mb-0">This dispatched batch has no receivable product lines. Contact the sending concern.</div>
                <?php elseif ($canReceive): ?>
                    <?php if (!$labelsComplete): ?>
                        <div class="alert alert-warning py-2 mb-3">This batch does not have one unique QR label for every dispatched piece. Contact the sending concern to correct the dispatched labels before receiving.</div>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('production/inward/receive-batch') ?>" class="concern-batch-scan" data-batch-number="<?= e($batch['batch_number']) ?>" data-expected-tags="<?= e($expectedTagJson ?: '{}') ?>" data-extra-tags="<?= e($extraTagJson ?: '{}') ?>" data-expected-count="<?= (int)$expectedPieceCount ?>" data-labels-complete="<?= $labelsComplete ? '1' : '0' ?>">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="production_batch_id" value="<?= (int)$batch['id'] ?>">
                        <input type="hidden" name="destination_type" value="<?= e($destinationType) ?>">
                        <?php if ($destinationType === 'PRODUCTION_UNIT'): ?>
                            <input type="hidden" name="destination_unit_id" value="<?= (int)$unitId ?>">
                        <?php endif; ?>
                        <input type="hidden" name="scan_data" value="[]">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-3">
                                <thead class="table-light"><tr><th>Product</th><th>SKU</th><th class="text-end">Dispatched</th><th>Product QR labels</th></tr></thead>
                                <tbody>
                                <?php foreach ($batch['items'] as $item): ?>
                                    <tr>
                                        <td><?= e($item['product_name']) ?><?= !empty($item['size_name']) || !empty($item['color_name']) ? ' · ' . e(trim(($item['size_name'] ?? '') . ' ' . ($item['color_name'] ?? ''))) : '' ?></td>
                                        <td class="font-monospace"><?= e($item['sku']) ?></td>
                                        <td class="text-end"><?= number_format((int)$item['expected_qty']) ?></td>
                                        <td>
                                            <?php $pieceTags = is_array($item['piece_tags'] ?? null) ? $item['piece_tags'] : []; ?>
                                            <?php if ($pieceTags): ?>
                                                <details class="piece-qr-list">
                                                    <summary class="text-primary fw-semibold" role="button">Show QR labels (<?= number_format(count($pieceTags)) ?>)</summary>
                                                    <div class="piece-qr-grid mt-3">
                                                        <?php foreach ($pieceTags as $pieceTag): ?>
                                                            <div class="piece-qr-card">
                                                                <div class="piece-qr-code" data-qr-code="<?= e($pieceTag) ?>"></div>
                                                                <code class="piece-qr-number"><?= e($pieceTag) ?></code>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </details>
                                            <?php else: ?>
                                                <span class="text-danger small">No piece QR labels</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Scan individual product QR label</label>
                                <div class="input-group">
                                    <input class="form-control font-monospace piece-scan-input" type="text" autocomplete="off" placeholder="Scan QR code, then press Enter" <?= $labelsComplete ? '' : 'disabled' ?>>
                                    <button class="btn btn-primary piece-scan-button" type="button" <?= $labelsComplete ? '' : 'disabled' ?>><i class="bi bi-upc-scan me-1"></i>Scan</button>
                                </div>
                                <?php if (!empty($extraTagMap)): ?>
                                    <details class="mb-3">
                                        <summary class="small text-muted" role="button">Recognized QR labels not dispatched in this batch (<?= count($extraTagMap) ?>)</summary>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <?php foreach (array_keys($extraTagMap) as $extraTag): ?>
                                                <code class="border rounded bg-light px-2 py-1"><?= e($extraTag) ?></code>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Scanned condition</label>
                                <select class="form-select piece-condition" <?= $labelsComplete ? '' : 'disabled' ?>>
                                    <option value="ACCEPTED">Accepted / good</option>
                                    <option value="DAMAGED">Damaged</option>
                                </select>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="badge bg-secondary piece-scan-progress">0 / <?= (int)$expectedPieceCount ?> scanned</span>
                                <div class="small text-muted piece-scan-counts mt-1">Accepted 0 · Damaged 0</div>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 small piece-scan-message" role="status">Scan individual piece labels, or scan the outward batch QR to immediately inward all dispatched pieces as accepted. Recognized extra approved labels are recorded as overages.</div>
                        <div class="d-flex flex-wrap gap-2 mb-2 piece-status-filters" role="group" aria-label="Filter batch pieces">
                            <button type="button" class="btn btn-sm btn-primary" data-piece-filter="all">All pieces</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-piece-filter="pending">Pending (<?= (int)$expectedPieceCount ?>)</button>
                            <button type="button" class="btn btn-sm btn-outline-success" data-piece-filter="verified">Verified (0)</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-piece-filter="damaged">Damage (0)</button>
                        </div>
                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th>Product</th><th>QR label</th><th>Status / condition</th></tr></thead>
                                <tbody class="piece-scan-log"><tr><td colspan="3" class="text-center text-muted py-3">No product labels scanned.</td></tr></tbody>
                            </table>
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-9"><label class="form-label small fw-semibold">Receipt notes</label><input class="form-control" name="notes" maxlength="1000" placeholder="Optional delivery or discrepancy note"></div>
                            <div class="col-md-3 d-grid"><button class="btn btn-success post-piece-receipt" disabled><i class="bi bi-check2-circle me-1"></i>Post Inward Receipt</button></div>
                        </div>
                        <small class="text-muted d-block mt-2">Only scanned accepted products are added to available stock. Scanned damaged products are recorded separately.</small>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning py-2 mb-0">You have view access only. Contact a staff member with Production Inward receive permission.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-uppercase text-muted">GRN Receipts</div><div class="fs-3 fw-bold"><?= number_format($stats['receipt_count']) ?></div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-uppercase text-muted">Total Received</div><div class="fs-3 fw-bold text-primary"><?= number_format($stats['received_quantity']) ?> <span class="fs-6 fw-normal">pcs</span></div></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-uppercase text-muted">Available In <?= e($destinationLabel) ?></div><div class="fs-3 fw-bold text-success"><?= number_format($stats['available_quantity']) ?> <span class="fs-6 fw-normal">pcs</span></div></div></div></div>
</div>

<?php if ($canReceive && $destinationType === 'PRODUCTION_UNIT' && $unitId): ?>
<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div><h5 class="mb-0 fw-bold"><i class="bi bi-upc-scan me-2 text-primary"></i>Receive Products</h5><small class="text-muted">Warehouse batches can be received by batch number; scan individual product labels from any source.</small></div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearScans" title="Clear scanned items"><i class="bi bi-trash3 me-1"></i>Clear</button>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('production/inward/receive') ?>" id="productionInwardForm">
            <?= CSRF::field() ?>
            <input type="hidden" name="destination_unit_id" value="<?= (int)$unitId ?>">
            <input type="hidden" name="items_json" id="itemsJson" value="[]">
            <input type="hidden" name="batch_number" id="batchNumber" value="">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="sourceType">Source Concern</label>
                    <select class="form-select" id="sourceType" name="source_type" required>
                        <option value="WAREHOUSE">Warehouse</option>
                        <option value="SHOP">Shop</option>
                        <option value="PRODUCTION_UNIT">Production Unit</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="sourceId">Source Location</label>
                    <select class="form-select" id="sourceId" name="source_id" required></select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold" for="scanCode">Scan batch number or product QR / SKU</label>
                    <div class="input-group">
                        <input class="form-control font-monospace" id="scanCode" type="text" autocomplete="off" placeholder="Scan code, then press Enter" <?= $unitId ? '' : 'disabled' ?>>
                        <button class="btn btn-primary" type="button" id="lookupCode"><i class="bi bi-upc-scan me-1"></i>Scan</button>
                    </div>
                </div>
                <div class="col-12"><label class="form-label" for="inwardNotes">Notes <span class="text-muted">(optional)</span></label><input class="form-control" id="inwardNotes" name="notes" maxlength="1000" placeholder="Delivery note, condition, or reference"></div>
            </div>
            <div class="alert alert-info py-2 small mb-3" id="scanMessage" role="status">Choose the source location, then scan products or a warehouse batch number.</div>
            <div class="table-responsive border rounded mb-3">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Product</th><th>SKU</th><th>Size / Color</th><th class="text-end">Qty</th><th class="text-end">Available Source Stock</th></tr></thead>
                    <tbody id="scannedItems"><tr><td colspan="5" class="text-center text-muted py-4">No products scanned.</td></tr></tbody>
                    <tfoot class="table-light"><tr><th colspan="3" class="text-end">Total</th><th class="text-end" id="totalScannedQty">0</th><th></th></tr></tfoot>
                </table>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-success" type="submit" id="postReceipt" disabled><i class="bi bi-check2-circle me-1"></i>Post Inward &amp; Credit Production Stock</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card border shadow-sm mb-4">
    <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="bi bi-journal-check me-2 text-primary"></i>Production Inward Ledger</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Inward / GRN</th><th>Source</th><th>Destination Concern</th><th>Production Batch</th><th class="text-center">Lines</th><th class="text-end">Received Pieces</th><th>Received By</th><th>Status</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php if (empty($receipts)): ?>
                <tr><td colspan="9" class="text-center text-muted py-5">No inward receipts found for this destination inventory.</td></tr>
            <?php else: foreach ($receipts as $receipt): ?>
                <tr>
                    <td><strong class="font-monospace"><?= e($receipt['grn_number']) ?></strong><small class="d-block text-muted"><?= e(formatDate($receipt['received_at'])) ?></small></td>
                    <td><span class="badge bg-light text-dark border"><?= e(ucwords(strtolower(str_replace('_', ' ', $receipt['source_type'])))) ?></span><div><?= e($receipt['source_name'] ?? 'Unknown source') ?></div></td>
                    <td><?= e($receipt['destination_name']) ?></td>
                    <td class="font-monospace"><?= e($receipt['source_batch_number'] ?: '—') ?></td>
                    <td class="text-center"><?= number_format((int)$receipt['line_count']) ?></td>
                    <td class="text-end fw-semibold"><?= number_format((int)$receipt['total_quantity']) ?> pcs</td>
                    <td><?= e($receipt['received_by_name'] ?? 'System') ?></td>
                    <td><span class="badge <?= $receipt['status'] === 'RECEIVED' ? 'bg-success' : 'bg-secondary' ?>"><?= e($receipt['status']) ?></span></td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary fw-semibold" data-inward-details-toggle="inwardReceiptDetails<?= (int)$receipt['id'] ?>" aria-expanded="false"><i class="bi bi-eye me-1"></i>View</button></td>
                </tr>
                <tr class="table-light"><td colspan="9" class="py-2">
                    <?php
                        $receiptPieces = [];
                        $verifiedCount = 0;
                        $damagedCount = 0;
                        $pendingCount = 0;
                        $excessCount = 0;
                        foreach ($receipt['items'] as $item) {
                            $scannedCodes = $item['scanned_piece_codes'] ?? ProductionInwardService::decodePieceCodeList($item['scanned_codes'] ?? null);
                            $damagedCodes = array_fill_keys($item['damaged_piece_codes'] ?? ProductionInwardService::decodePieceCodeList($item['damaged_codes'] ?? null), true);
                            $missingCodes = $item['missing_piece_codes'] ?? ProductionInwardService::decodePieceCodeList($item['missing_codes'] ?? null);
                            $missingCodeSet = array_fill_keys($missingCodes, true);
                            $excessCodeSet = array_fill_keys($item['excess_piece_codes'] ?? ProductionInwardService::decodePieceCodeList($item['excess_codes'] ?? null), true);
                            $seenCodes = [];
                            foreach ($scannedCodes as $code) {
                                $code = trim((string)$code);
                                if ($code === '') continue;
                                $status = isset($missingCodeSet[$code]) ? 'pending' : (isset($damagedCodes[$code]) ? 'damaged' : 'verified');
                                $isExcess = isset($excessCodeSet[$code]) && $status !== 'pending';
                                $receiptPieces[] = [
                                    'product' => $item['product_name'],
                                    'sku' => $item['sku'],
                                    'code' => $code,
                                    'status' => $status,
                                    'is_excess' => $isExcess,
                                ];
                                $seenCodes[$code] = true;
                                if ($status === 'damaged') $damagedCount++;
                                elseif ($status === 'pending') $pendingCount++;
                                else $verifiedCount++;
                                if ($isExcess) $excessCount++;
                            }
                            foreach ($missingCodes as $code) {
                                if (isset($seenCodes[$code])) continue;
                                $receiptPieces[] = [
                                    'product' => $item['product_name'],
                                    'sku' => $item['sku'],
                                    'code' => $code,
                                    'status' => 'pending',
                                    'is_excess' => false,
                                ];
                                $pendingCount++;
                            }
                        }
                    ?>
                    <details class="receipt-piece-details" id="inwardReceiptDetails<?= (int)$receipt['id'] ?>">
                        <summary class="fw-semibold text-primary" role="button">
                            View batch pieces · <?= $verifiedCount + $damagedCount ?> scanned · <?= $verifiedCount ?> verified · <?= $damagedCount ?> damaged · <?= $pendingCount ?> pending/missing<?= $excessCount > 0 ? ' · ' . $excessCount . ' excess' : '' ?>
                        </summary>
                        <?php $canUpdateBatchStatus = $canReceive && $receipt['source_type'] === 'PRODUCTION_BATCH'; ?>
                        <?php if ($canUpdateBatchStatus): ?>
                            <form method="POST" action="<?= url('production/inward/update-batch-statuses') ?>" class="receipt-piece-status-form">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="inward_id" value="<?= (int)$receipt['id'] ?>">
                                <input type="hidden" name="destination_type" value="<?= e($receipt['destination_type']) ?>">
                                <input type="hidden" name="destination_unit_id" value="<?= (int)($receipt['destination_unit_id'] ?? 0) ?>">
                                <input type="hidden" name="piece_statuses" value="{}">
                        <?php endif; ?>
                        <div class="d-flex flex-wrap gap-2 my-2" role="group" aria-label="Filter receipt pieces">
                            <button type="button" class="btn btn-sm btn-primary" data-receipt-filter="all">All pieces</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-receipt-filter="pending">Pending (<?= $pendingCount ?>)</button>
                            <button type="button" class="btn btn-sm btn-outline-success" data-receipt-filter="verified">Verified (<?= $verifiedCount ?>)</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-receipt-filter="damaged">Damage (<?= $damagedCount ?>)</button>
                            <?php if ($excessCount > 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-info" data-receipt-filter="excess">Excess (<?= $excessCount ?>)</button>
                            <?php endif; ?>
                        </div>
                        <?php if ($receiptPieces): ?>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th>Product / SKU</th><th>Piece QR</th><th>Status</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($receiptPieces as $piece): ?>
                                        <tr data-receipt-piece-status="<?= e($piece['status']) ?>" data-receipt-piece-excess="<?= !empty($piece['is_excess']) ? '1' : '0' ?>">
                                            <td><?= e($piece['product']) ?> · <?= e($piece['sku']) ?></td>
                                            <td class="font-monospace">
                                                <?= e($piece['code']) ?>
                                                <?php if (!empty($piece['is_excess'])): ?>
                                                    <span class="badge bg-info text-dark ms-1">Excess</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($canUpdateBatchStatus): ?>
                                                    <select class="form-select form-select-sm receipt-piece-status" data-code="<?= e($piece['code']) ?>">
                                                        <option value="VERIFIED" <?= $piece['status'] === 'verified' ? 'selected' : '' ?>>Verified / accepted</option>
                                                        <option value="DAMAGED" <?= $piece['status'] === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                                                        <option value="MISSING" <?= $piece['status'] === 'pending' ? 'selected' : '' ?>>Pending / missing</option>
                                                    </select>
                                                <?php else: ?>
                                                    <span class="badge <?= $piece['status'] === 'damaged' ? 'bg-danger' : ($piece['status'] === 'pending' ? 'bg-secondary' : 'bg-success') ?>"><?= $piece['status'] === 'pending' ? 'Pending / missing' : ucfirst($piece['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="small text-muted">This receipt has no piece-level QR details.</div>
                        <?php endif; ?>
                        <?php if ($canUpdateBatchStatus): ?>
                                <div class="text-end mt-2">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save piece status changes</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </details>
                </td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="card-header bg-white"><h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-success"></i>Current Available Stock — <?= e($destinationLabel) ?></h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Product</th><th>SKU</th><th>Size / Color</th><th class="text-end">Available</th><th class="text-end">Selling Price</th></tr></thead>
            <tbody>
            <?php if (empty($unitStock)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No available stock in this destination inventory.</td></tr>
            <?php else: foreach ($unitStock as $stock): ?>
                <tr><td><?= e($stock['product_name']) ?></td><td class="font-monospace"><?= e($stock['sku']) ?></td><td><?= e(trim(($stock['size_name'] ?? '') . ' ' . ($stock['color_name'] ?? '')) ?: '—') ?></td><td class="text-end fw-semibold"><?= number_format((float)$stock['quantity']) ?></td><td class="text-end">₹<?= number_format((float)$stock['selling_price'], 2) ?></td></tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canReceive || !empty($receipts)): ?>
<script>
(() => {
    document.querySelectorAll('.piece-qr-list').forEach(details => {
        details.addEventListener('toggle', () => {
            if (!details.open) return;
            details.querySelectorAll('.piece-qr-code:not([data-generated])').forEach(container => {
                const code = container.dataset.qrCode || '';
                if (!code) return;
                if (typeof QRCode === 'undefined') {
                    container.textContent = 'QR unavailable';
                    container.classList.add('text-danger', 'small');
                    container.dataset.generated = '1';
                    return;
                }
                new QRCode(container, {
                    text: code,
                    width: 84,
                    height: 84,
                    correctLevel: QRCode.CorrectLevel.M
                });
                container.dataset.generated = '1';
            });
        });
    });

    document.querySelectorAll('.receipt-piece-details').forEach(details => {
        const filterButtons = details.querySelectorAll('[data-receipt-filter]');
        const rows = details.querySelectorAll('[data-receipt-piece-status]');
        filterButtons.forEach(button => button.addEventListener('click', () => {
            const filter = button.dataset.receiptFilter || 'all';
            filterButtons.forEach(item => {
                item.className = `btn btn-sm ${item === button ? 'btn-primary' : 'btn-outline-secondary'}`;
            });
            rows.forEach(row => {
                if (filter === 'excess') {
                    row.hidden = row.dataset.receiptPieceExcess !== '1';
                } else {
                    row.hidden = filter !== 'all' && row.dataset.receiptPieceStatus !== filter;
                }
            });
        }));
    });

    document.querySelectorAll('[data-inward-details-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const details = document.getElementById(button.dataset.inwardDetailsToggle || '');
            if (!details) return;
            details.open = !details.open;
            button.setAttribute('aria-expanded', details.open ? 'true' : 'false');
            button.innerHTML = details.open
                ? '<i class="bi bi-eye-slash me-1"></i>Hide'
                : '<i class="bi bi-eye me-1"></i>View';
        });
    });

    document.querySelectorAll('.receipt-piece-status-form').forEach(form => {
        form.addEventListener('submit', event => {
            const statuses = {};
            form.querySelectorAll('.receipt-piece-status').forEach(select => {
                statuses[select.dataset.code] = select.value;
            });
            if (Object.keys(statuses).length === 0) {
                event.preventDefault();
                return;
            }
            form.querySelector('[name="piece_statuses"]').value = JSON.stringify(statuses);
            const verified = Object.values(statuses).filter(status => status === 'VERIFIED').length;
            const damaged = Object.values(statuses).filter(status => status === 'DAMAGED').length;
            const missing = Object.values(statuses).filter(status => status === 'MISSING').length;
            if (!confirm(`Save piece statuses? ${verified} verified, ${damaged} damaged, ${missing} pending/missing. Stock balances will be adjusted.`)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.concern-batch-scan').forEach(form => {
        const expected = JSON.parse(form.dataset.expectedTags || '{}');
        const extras = JSON.parse(form.dataset.extraTags || '{}');
        const requiredCount = Number(form.dataset.expectedCount || 0);
        const completeLabels = form.dataset.labelsComplete === '1';
        const input = form.querySelector('.piece-scan-input');
        const scanButton = form.querySelector('.piece-scan-button');
        const condition = form.querySelector('.piece-condition');
        const hiddenScans = form.querySelector('[name="scan_data"]');
        const progress = form.querySelector('.piece-scan-progress');
        const counts = form.querySelector('.piece-scan-counts');
        const message = form.querySelector('.piece-scan-message');
        const log = form.querySelector('.piece-scan-log');
        const submit = form.querySelector('.post-piece-receipt');
        const scans = new Map();
        const statusFilters = form.querySelectorAll('[data-piece-filter]');
        let activeFilter = 'all';

        function normalize(raw) {
            let code = String(raw || '').trim();
            if (code.startsWith('{') && code.endsWith('}')) {
                try {
                    const payload = JSON.parse(code);
                    code = payload.tag_code || payload.piece_tag || payload.qr_code || payload.code || '';
                } catch (_) {
                    code = '';
                }
            } else if (code.includes('|')) {
                code = code.split('|', 1)[0];
            }
            return code.trim().toUpperCase();
        }

        function render() {
            const scanned = [...scans.entries()];
            const accepted = scanned.filter(([, scan]) => scan.condition === 'ACCEPTED').length;
            const damaged = scanned.length - accepted;
            const missingTags = Object.keys(expected).filter(tag => !scans.has(tag));
            const excessTags = scanned.map(([tag]) => tag).filter(tag => !Object.prototype.hasOwnProperty.call(expected, tag));
            progress.textContent = `${scanned.length} scanned · ${missingTags.length} missing · ${excessTags.length} extra`;
            progress.className = `badge rounded-pill piece-scan-progress ${missingTags.length === 0 && excessTags.length === 0 && damaged === 0 ? 'bg-success' : 'bg-warning text-dark'}`;
            counts.textContent = `Accepted ${accepted} · Damaged ${damaged} · Missing ${missingTags.length} · Extra ${excessTags.length}`;
            hiddenScans.value = JSON.stringify(scanned.map(([tag, scan]) => ({tag, condition: scan.condition})));
            submit.disabled = !completeLabels || scanned.length === 0;
            statusFilters.forEach(button => {
                const filter = button.dataset.pieceFilter;
                const count = filter === 'pending' ? missingTags.length
                    : filter === 'verified' ? accepted
                    : filter === 'damaged' ? damaged
                    : Object.keys(expected).length + excessTags.length;
                const title = filter === 'all' ? 'All pieces' : filter === 'pending' ? 'Pending'
                    : filter === 'verified' ? 'Verified' : 'Damage';
                button.textContent = `${title} (${count})`;
                button.className = `btn btn-sm ${activeFilter === filter
                    ? (filter === 'damaged' ? 'btn-danger' : filter === 'verified' ? 'btn-success' : 'btn-primary')
                    : 'btn-outline-secondary'}`;
            });

            const pieces = Object.entries(expected).map(([tag, details]) => ({
                tag, details, scan: scans.get(tag) || null,
                status: !scans.has(tag) ? 'pending' : scans.get(tag).condition === 'DAMAGED' ? 'damaged' : 'verified'
            }));
            scanned.forEach(([tag, scan]) => {
                if (!Object.prototype.hasOwnProperty.call(expected, tag)) {
                    pieces.push({
                        tag, details: scan, scan,
                        status: scan.condition === 'DAMAGED' ? 'damaged' : 'verified'
                    });
                }
            });
            const visiblePieces = pieces.filter(piece => activeFilter === 'all' || piece.status === activeFilter);
            log.replaceChildren();
            if (visiblePieces.length === 0) {
                const row = log.insertRow();
                const cell = row.insertCell();
                cell.colSpan = 3;
                cell.className = 'text-center text-muted py-3';
                cell.textContent = activeFilter === 'pending' ? 'No pending pieces.' : 'No pieces in this category.';
                return;
            }
            visiblePieces.forEach(({tag, details, scan, status}) => {
                const isExcessPiece = !Object.prototype.hasOwnProperty.call(expected, tag);
                const row = log.insertRow();
                const product = row.insertCell();
                product.textContent = [details.product, details.variant].filter(Boolean).join(' · ');
                const label = row.insertCell();
                label.className = 'font-monospace';
                label.textContent = tag;
                if (isExcessPiece) {
                    const excessBadge = document.createElement('span');
                    excessBadge.className = 'badge bg-info text-dark ms-1';
                    excessBadge.textContent = 'Excess';
                    label.appendChild(excessBadge);
                }
                const state = row.insertCell();
                if (!scan) {
                    state.innerHTML = '<span class="badge bg-secondary">Pending scan</span>';
                    return;
                }
                const controlWrap = document.createElement('div');
                controlWrap.className = 'd-flex align-items-center gap-2';
                const selector = document.createElement('select');
                selector.className = 'form-select form-select-sm piece-scan-condition';
                selector.dataset.tag = tag;
                const acceptedOption = new Option('Accepted / good', 'ACCEPTED');
                const damagedOption = new Option('Damaged', 'DAMAGED');
                selector.add(acceptedOption);
                selector.add(damagedOption);
                selector.value = scan.condition;
                controlWrap.appendChild(selector);
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-secondary py-0 px-2';
                removeBtn.title = 'Remove scan';
                removeBtn.textContent = '×';
                removeBtn.addEventListener('click', () => {
                    scans.delete(tag);
                    render();
                });
                controlWrap.appendChild(removeBtn);
                state.appendChild(controlWrap);
            });
        }

        function scan() {
            const tag = normalize(input.value);
            input.value = '';
            if (!tag) return;
            if (tag === normalize(form.dataset.batchNumber || '')) {
                if (!completeLabels) {
                    message.className = 'alert alert-danger py-2 small piece-scan-message';
                    message.textContent = 'This batch cannot be received by batch QR because it does not have one unique piece QR for every dispatched item.';
                    input.focus();
                    return;
                }
                scans.clear();
                Object.entries(expected).forEach(([pieceTag, details]) => {
                    scans.set(pieceTag, {...details, condition: 'ACCEPTED'});
                });
                render();
                message.className = 'alert alert-success py-2 small piece-scan-message';
                message.textContent = `Batch ${tag} scanned. All ${scans.size} dispatched pieces are marked accepted and will be inwarded now.`;
                form.requestSubmit(submit);
                return;
            }
            const details = expected[tag] || extras[tag];
            if (!details) {
                message.className = 'alert alert-danger py-2 small piece-scan-message';
                message.textContent = 'This QR label cannot be verified as an approved piece for this batch.';
            } else if (scans.has(tag)) {
                message.className = 'alert alert-warning py-2 small piece-scan-message';
                message.textContent = 'This product has already been scanned.';
            } else {
                scans.set(tag, {...details, condition: condition.value});
                const isExtra = !Object.prototype.hasOwnProperty.call(expected, tag);
                message.className = `alert ${isExtra ? 'alert-warning' : 'alert-success'} py-2 small piece-scan-message`;
                message.textContent = `${details.product} scanned${isExtra ? ' as an extra piece' : ''}.`;
                render();
            }
            input.focus();
        }

        scanButton.addEventListener('click', scan);
        statusFilters.forEach(button => button.addEventListener('click', () => {
            activeFilter = button.dataset.pieceFilter || 'all';
            render();
        }));
        log.addEventListener('change', event => {
            const selector = event.target.closest('.piece-scan-condition');
            if (!selector) return;
            const scan = scans.get(selector.dataset.tag);
            if (!scan) return;
            scan.condition = selector.value;
            message.className = 'alert alert-success py-2 small piece-scan-message';
            message.textContent = `${selector.dataset.tag} updated to ${selector.value === 'DAMAGED' ? 'Damaged' : 'Accepted / good'}.`;
            render();
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                scan();
            }
        });
        form.addEventListener('submit', event => {
            if (!completeLabels || scans.size === 0) {
                event.preventDefault();
                message.className = 'alert alert-warning py-2 small piece-scan-message';
                message.textContent = 'Scan at least one product label before posting this receipt.';
                return;
            }
            const missingTags = Object.keys(expected).filter(tag => !scans.has(tag));
            const excessTags = [...scans.keys()].filter(tag => !Object.prototype.hasOwnProperty.call(expected, tag));
            const damageCount = [...scans.values()].filter(scan => scan.condition === 'DAMAGED').length;
            let confirmation = `Post this receipt? Scanned ${scans.size} of ${requiredCount} dispatched pieces.`;
            if (missingTags.length || excessTags.length || damageCount) {
                confirmation += `\n\nDiscrepancy: ${missingTags.length} missing, ${excessTags.length} extra, ${damageCount} damaged.`;
                if (missingTags.length) confirmation += `\nMissing QR: ${missingTags.join(', ')}`;
                if (excessTags.length) confirmation += `\nExtra QR: ${excessTags.join(', ')}`;
                confirmation += '\nThe sending concern will see these details.';
            }
            if (!confirm(confirmation)) {
                event.preventDefault();
            }
        });
        render();
    });
})();
</script>
<?php endif; ?>

<?php if ($canReceive && $destinationType === 'PRODUCTION_UNIT' && $unitId): ?>
<script>
(() => {
    const locations = <?= $locationJson ?: '{}' ?>;
    const currentUnitId = <?= (int)$unitId ?>;
    const sourceType = document.getElementById('sourceType');
    const sourceId = document.getElementById('sourceId');
    const scanCode = document.getElementById('scanCode');
    const scanMessage = document.getElementById('scanMessage');
    const scannedItems = document.getElementById('scannedItems');
    const itemsJson = document.getElementById('itemsJson');
    const batchNumber = document.getElementById('batchNumber');
    const postReceipt = document.getElementById('postReceipt');
    const cart = new Map();
    let batchMode = false;

    function updateSources() {
        const type = sourceType.value;
        const options = locations[type] || [];
        sourceId.replaceChildren(new Option('Select source location', ''));
        options.filter(item => !(type === 'PRODUCTION_UNIT' && Number(item.id) === currentUnitId))
            .forEach(item => sourceId.add(new Option(`${item.name} (${item.code})`, item.id)));
    }

    function render() {
        if (cart.size === 0) {
            scannedItems.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No products scanned.</td></tr>';
            document.getElementById('totalScannedQty').textContent = '0';
            itemsJson.value = '[]';
            postReceipt.disabled = true;
            return;
        }
        let total = 0;
        const items = [...cart.values()];
        scannedItems.innerHTML = items.map(item => {
            total += Number(item.quantity);
            const displayName = item.product_name || 'Product';
            const available = item.available === undefined ? 'Batch' : Number(item.available).toLocaleString();
            return `<tr><td>${escapeHtml(displayName)}</td><td class="font-monospace">${escapeHtml(item.sku || '')}</td><td>${escapeHtml([item.size_name, item.color_name].filter(Boolean).join(' / ') || '—')}</td><td class="text-end fw-semibold">${Number(item.quantity).toLocaleString()}</td><td class="text-end">${available}</td></tr>`;
        }).join('');
        document.getElementById('totalScannedQty').textContent = total.toLocaleString();
        itemsJson.value = JSON.stringify(items.map(({variant_id, quantity, codes}) => ({variant_id, quantity, codes})));
        postReceipt.disabled = false;
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    }

    async function lookup() {
        const code = scanCode.value.trim();
        if (!code) return;
        if (batchMode && cart.size > 0) {
            scanMessage.className = 'alert alert-warning py-2 small mb-3';
            scanMessage.textContent = 'Clear the current batch before scanning another code.';
            return;
        }
        if (!batchMode && cart.size > 0 && batchNumber.value) {
            scanMessage.className = 'alert alert-warning py-2 small mb-3';
            scanMessage.textContent = 'Clear the current batch before scanning individual products.';
            return;
        }
        if (cart.size > 0 && batchNumber.value) {
            scanMessage.className = 'alert alert-warning py-2 small mb-3';
            scanMessage.textContent = 'Clear the current batch before scanning individual products.';
            return;
        }
        const query = new URLSearchParams({source_type: sourceType.value, source_id: sourceId.value, code});
        try {
            const response = await fetch(`<?= url('api/production/inward/lookup') ?>?${query}`, {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Code could not be matched.');
            const result = payload.result;
            if (result.type === 'batch') {
                if (cart.size > 0) throw new Error('Clear scanned products before loading a batch.');
                batchMode = true;
                batchNumber.value = result.batch_number;
                result.items.forEach(item => cart.set(Number(item.variant_id), {...item, codes: []}));
                scanMessage.className = 'alert alert-success py-2 small mb-3';
                scanMessage.textContent = `Batch ${result.batch_number} loaded. Verify the lines, then post the receipt.`;
            } else {
                if (batchMode) throw new Error('Clear the loaded batch before scanning individual products.');
                const item = result.variant;
                const key = Number(item.id);
                const current = cart.get(key) || {...item, variant_id: key, quantity: 0, codes: [], available: result.available};
                if (current.quantity >= Number(result.available)) throw new Error(`Only ${Number(result.available)} unit(s) are available at this source.`);
                current.quantity += 1;
                current.codes.push(code);
                cart.set(key, current);
                scanMessage.className = 'alert alert-success py-2 small mb-3';
                scanMessage.textContent = `${item.product_name} scanned. ${current.quantity} unit(s) added.`;
            }
            render();
        } catch (error) {
            scanMessage.className = 'alert alert-danger py-2 small mb-3';
            scanMessage.textContent = error.message;
        } finally {
            scanCode.value = '';
            scanCode.focus();
        }
    }

    sourceType.addEventListener('change', () => {
        updateSources();
        cart.clear();
        batchMode = false;
        batchNumber.value = '';
        render();
    });
    sourceId.addEventListener('change', () => {
        cart.clear();
        batchMode = false;
        batchNumber.value = '';
        render();
    });
    document.getElementById('lookupCode').addEventListener('click', lookup);
    scanCode.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookup();
        }
    });
    document.getElementById('clearScans').addEventListener('click', () => {
        cart.clear();
        batchMode = false;
        batchNumber.value = '';
        scanMessage.className = 'alert alert-info py-2 small mb-3';
        scanMessage.textContent = 'Scans cleared. Choose the source location, then scan products or a warehouse batch number.';
        render();
        scanCode.focus();
    });
    document.getElementById('productionInwardForm').addEventListener('submit', event => {
        if (cart.size === 0 || !sourceId.value) {
            event.preventDefault();
            scanMessage.className = 'alert alert-warning py-2 small mb-3';
            scanMessage.textContent = 'Select the source location and scan at least one product or batch.';
            return;
        }
        if (!confirm('Post this inward receipt and transfer the scanned stock into Production?')) event.preventDefault();
    });

    updateSources();
    render();
})();
</script>
<?php endif; ?>