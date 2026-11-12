<div class="page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <h2 class="mb-1"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit <?= e($purchase['purchase_number']) ?></h2>
        <span class="text-muted">Update this purchase and review its fabric, supply, and tax totals.</span>
    </div>
</div>

<?php
$fabricItems = array_values(array_filter($items, static fn(array $item): bool => $item['item_type'] === 'FABRIC_ROLL'));
$supplyItems = array_values(array_filter($items, static fn(array $item): bool => $item['item_type'] === 'GENERAL_SUPPLY'));
$finishedItems = array_values(array_filter($items, static fn(array $item): bool => $item['item_type'] === 'FINISHED_GOODS'));
$fabricDescription = $fabricItems[0]['item_name'] ?? '';
$knownUnits = ['PCS', 'KG', 'METRE', 'LITRE', 'BOX', 'PACK', 'PAIR', 'SET', 'BUNDLE'];
$taxTypeForItem = static function (array $item): string {
    $type = $item['gst_type'] ?? '';
    if (in_array($type, ['SGST', 'CGST', 'IGST', 'BOTH', 'EXEMPTED'], true)) return $type;
    if ((float)($item['sgst_amount'] ?? 0) > 0 && (float)($item['cgst_amount'] ?? 0) > 0) return 'BOTH';
    if ((float)($item['igst_amount'] ?? 0) > 0) return 'IGST';
    if ((float)($item['sgst_amount'] ?? 0) > 0) return 'SGST';
    if ((float)($item['cgst_amount'] ?? 0) > 0) return 'CGST';
    return 'EXEMPTED';
};
$taxValueForItem = static function (array $item, string $type): float {
    if ($type === 'IGST') return (float)($item['igst_value'] ?? 0);
    if ($type === 'CGST') return (float)($item['cgst_value'] ?? 0);
    return (float)($item['sgst_value'] ?? 0);
};
?>

<form method="POST" action="<?= url('production/purchases/update/' . $purchase['id']) ?>" enctype="multipart/form-data" id="editProductionPurchaseForm">
    <?= CSRF::field() ?>
    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Purchase &amp; Supplier Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Supplier *</label>
                    <input class="form-control" value="<?= e($purchase['seller_company'] ?: $purchase['seller_name']) ?><?= $purchase['seller_company'] ? ' — ' . e($purchase['seller_name']) : '' ?>" disabled>
                </div>
                <div class="col-md-3"><label class="form-label required">Purchase Date *</label><input type="date" name="purchase_date" class="form-control" value="<?= e($purchase['purchase_date']) ?>" required></div>
                <div class="col-md-4"><label class="form-label">Invoice / Bill Number</label><input name="invoice_number" class="form-control" maxlength="100" value="<?= e($purchase['invoice_number']) ?>"></div>
                <div class="col-md-6">
                    <label class="form-label">Tax Invoice / Bill Attachment (Optional)</label>
                    <input type="file" name="document" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <small class="text-muted">JPG, PNG, WEBP or PDF up to 5 MB.
                        <?php if (!empty($purchase['document_url'])): ?>Current attachment: <a href="<?= uploadUrl($purchase['document_url']) ?>" target="_blank" rel="noopener">View file</a><?php endif; ?>
                    </small>
                </div>
                <div class="col-md-6"><label class="form-label">Purchase Notes</label><input name="notes" class="form-control" maxlength="500" value="<?= e($purchase['notes']) ?>"></div>
                <div class="col-12">
                    <div class="border rounded bg-light p-3">
                        <div class="fw-semibold mb-3"><i class="bi bi-person-vcard me-1 text-primary"></i>Selected Supplier Information</div>
                        <div class="row g-3">
                            <div class="col-md-4"><small class="text-muted d-block">Contact Person</small><?= e($purchase['seller_name'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">Company</small><?= e($purchase['seller_company'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">Phone</small><?= e($purchase['seller_phone'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">Email</small><?= e($purchase['seller_email'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">GSTIN / Tax ID</small><?= e($purchase['seller_gst_number'] ?: '—') ?></div>
                            <div class="col-md-4"><small class="text-muted d-block">City / State</small><?= e(trim(($purchase['seller_city'] ?? '') . ', ' . ($purchase['seller_state'] ?? ''), ', ') ?: '—') ?></div>
                            <div class="col-md-6"><small class="text-muted d-block">Business Address</small><?= e($purchase['seller_address'] ?: '—') ?></div>
                            <div class="col-md-6"><small class="text-muted d-block">Payment Terms</small><?= e($purchase['seller_payment_terms'] ?: '—') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-layers-half me-2 text-primary"></i>Fabric Roll &amp; Material Supply Details</h5>
            <small class="text-muted">Sticker ranges for existing rolls stay unchanged.</small>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label required">Fabric / Material Description *</label>
                <input type="text" id="editFabricDescription" class="form-control text-uppercase" maxlength="150" value="<?= e($fabricDescription) ?>" <?= $fabricItems ? 'required' : '' ?>>
                <small class="text-muted">The description is applied to all fabric rolls in this purchase.</small>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-stack me-1 text-primary"></i>Individual Fabric Rolls (<span id="rollCountBadge"><?= count($fabricItems) ?></span>)</h6>
                <span class="text-muted small">Roll RQ and QR ranges are kept fixed while editing.</span>
            </div>
            <div id="rollsContainer" class="d-flex flex-column gap-3 mb-4">
                <?php foreach ($fabricItems as $index => $item): ?>
                    <?php
                    $key = (int)$item['id'];
                    $gstType = $taxTypeForItem($item);
                    $taxValue = $taxValueForItem($item, $gstType);
                    $cgstValue = $gstType === 'BOTH' ? (float)($item['cgst_value'] ?? 0) : 0;
                    ?>
                    <div class="roll-card card border shadow-sm" data-item-key="<?= $key ?>" data-item-id="<?= $key ?>" data-sequence-start="<?= (int)($item['start_seq'] ?? 0) ?>" data-start-code="<?= e($item['start_code'] ?? '') ?>" data-photo-url="<?= e(!empty($item['photo_url']) ? uploadUrl($item['photo_url']) : '') ?>">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <strong><i class="bi bi-layers text-primary me-1"></i><span class="roll-title">ROLL #<?= $index + 1 ?></span>
                                <span class="badge bg-dark roll-code-text"><?= e($item['start_code'] ?? '') ?><?= !empty($item['end_code']) && $item['end_code'] !== ($item['start_code'] ?? '') ? '–' . e($item['end_code']) : '' ?></span>
                            </strong>
                        </div>
                        <div class="card-body p-3">
                            <input type="hidden" name="items[<?= $key ?>][id]" value="<?= $key ?>">
                            <input type="hidden" name="items[<?= $key ?>][item_type]" value="FABRIC_ROLL">
                            <input type="hidden" name="items[<?= $key ?>][item_name]" class="roll-item-name" value="<?= e($item['item_name']) ?>">
                            <input type="hidden" name="items[<?= $key ?>][quantity]" value="<?= e($item['quantity']) ?>">
                            <input type="hidden" name="items[<?= $key ?>][unit]" value="PCS">
                            <div class="row g-3">
                                <div class="col-lg-2 col-md-4"><label class="form-label required">Roll No. / Code *</label><input name="items[<?= $key ?>][roll_number]" class="form-control roll-number text-uppercase" maxlength="50" value="<?= e($item['roll_number']) ?>" required></div>
                                <div class="col-lg-2 col-md-4"><label class="form-label required">Color / Shade *</label><input name="items[<?= $key ?>][color]" class="form-control roll-color text-uppercase" maxlength="100" value="<?= e($item['color']) ?>" required></div>
                                <div class="col-lg-2 col-md-4"><label class="form-label required">Design Name / Pattern *</label><input name="items[<?= $key ?>][design_name]" class="form-control roll-design text-uppercase" maxlength="150" value="<?= e($item['design_name']) ?>" required></div>
                                <div class="col-lg-1 col-md-3"><label class="form-label required">Designs *</label><input type="number" min="1" step="1" name="items[<?= $key ?>][design_count]" class="form-control roll-design-count" value="<?= (int)$item['design_count'] ?>" required></div>
                                <div class="col-lg-1 col-md-3"><label class="form-label">RQ *</label><input class="form-control" value="<?= number_format((float)$item['quantity'], 0) ?>" disabled></div>
                                <div class="col-lg-2 col-md-3"><label class="form-label required">Weight (kg) *</label><div class="input-group"><input type="number" min="0.01" step="0.01" name="items[<?= $key ?>][weight_kg]" class="form-control roll-weight" value="<?= e($item['weight_kg']) ?>" required><span class="input-group-text">kg</span></div></div>
                                <div class="col-lg-2 col-md-3"><label class="form-label required">Price (₹) *</label><div class="input-group"><span class="input-group-text">₹</span><input type="number" min="0" step="0.01" name="items[<?= $key ?>][unit_price]" class="form-control roll-price" value="<?= e($item['unit_price']) ?>" required></div></div>
                                <div class="col-lg-3 col-md-4"><label class="form-label">GST</label><select name="items[<?= $key ?>][gst_type]" class="form-select item-gst-type">
                                    <?php foreach (['SGST'=>'SGST','IGST'=>'IGST','CGST'=>'CGST','BOTH'=>'Both (CGST and SGST)','EXEMPTED'=>'Exempted'] as $type => $label): ?><option value="<?= e($type) ?>" <?= $gstType === $type ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                                </select></div>
                                <div class="col-lg-3 col-md-4 tax-mode-field <?= ($item['tax_input_type'] ?? 'PERCENT') === '' || $gstType === 'EXEMPTED' ? 'd-none' : '' ?>"><label class="form-label">Tax Input Type</label><select name="items[<?= $key ?>][tax_mode]" class="form-select item-tax-mode"><option value="PERCENT" <?= ($item['tax_input_type'] ?? '') !== 'AMOUNT' ? 'selected' : '' ?>>Percentage (%)</option><option value="AMOUNT" <?= ($item['tax_input_type'] ?? '') === 'AMOUNT' ? 'selected' : '' ?>>Amount (₹)</option></select></div>
                                <div class="col-lg-3 col-md-4 tax-value-field <?= $gstType === 'EXEMPTED' ? 'd-none' : '' ?>"><label class="form-label item-tax-label"><?= $gstType === 'BOTH' ? 'SGST' : e($gstType) ?></label><input type="number" min="0" step="0.01" name="items[<?= $key ?>][tax_value]" class="form-control item-tax-value" value="<?= e($taxValue) ?>"></div>
                                <div class="col-lg-3 col-md-4 tax-cgst-field <?= $gstType !== 'BOTH' ? 'd-none' : '' ?>"><label class="form-label item-cgst-label">CGST</label><input type="number" min="0" step="0.01" name="items[<?= $key ?>][cgst_tax_value]" class="form-control item-cgst-value" value="<?= e($cgstValue) ?>"></div>
                                <div class="col-md-6">
                                    <label class="form-label">Roll Sample Photo <small class="text-muted">(Optional — JPG, PNG, WEBP)</small></label>
                                    <input type="file" name="roll_photo_<?= $key ?>" class="form-control roll-photo" accept=".jpg,.jpeg,.png,.webp">
                                    <?php if (!empty($item['photo_url'])): ?><small class="d-block mt-1"><a href="<?= uploadUrl($item['photo_url']) ?>" target="_blank" rel="noopener">View current roll photo</a></small><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold small"><i class="bi bi-table me-1 text-primary"></i>Fabric Rolls Inventory &amp; Details Table</span>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-dark" id="previewPurchaseLabels"><i class="bi bi-qr-code text-warning me-1"></i>Preview Labels</button>
                        <span class="badge bg-secondary-subtle text-secondary border" id="tableRollCountBadge"><?= count($fabricItems) ?> <?= count($fabricItems) === 1 ? 'Roll' : 'Rolls' ?></span>
                    </div>
                </div>
                <div class="table-responsive"><table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                    <thead class="table-light small text-uppercase"><tr><th class="text-center">#</th><th>Photo</th><th>Roll Code</th><th>Color / Shade</th><th>Design / Pattern</th><th class="text-center">Designs</th><th class="text-end">Total RQ</th><th class="text-end">Weight</th><th class="text-end">Price</th><th>GST Type</th><th class="text-end">Tax</th><th class="text-end bg-success-subtle text-success">Total Value</th><th class="text-center">Status</th></tr></thead>
                    <tbody id="rollDetailsTableBody"></tbody>
                </table></div>
            </div>
            <div class="p-3 bg-primary-subtle rounded border d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><strong class="text-primary"><i class="bi bi-calculator me-1"></i>Batch Metrics Summary</strong><small class="d-block text-muted">Totals are calculated from the rolls in this purchase.</small></div>
                <div class="d-flex flex-wrap gap-4">
                    <div><small class="text-muted d-block">Total Rolls</small><strong id="summaryTotalRolls">0 Rolls</strong></div>
                    <div><small class="text-muted d-block">Total RQ</small><strong id="summaryTotalRQ">0 RQ</strong></div>
                    <div><small class="text-muted d-block">Colors / Designs</small><strong id="summaryColorsDesigns">0 Colors • 0 Designs</strong></div>
                    <div><small class="text-muted d-block">Total Weight</small><strong id="summaryTotalWeight">0.00 kg</strong></div>
                    <div><small class="text-muted d-block">Total incl. Tax</small><strong id="summaryTotalPrice" class="text-success">₹0.00</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><h5 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-success"></i>Other Garment Supplies</h5><small class="text-muted">Optional non-fabric products received on this invoice.</small></div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addSupply"><i class="bi bi-plus-circle me-1"></i>Add Supply</button>
        </div>
        <div class="card-body" id="suppliesContainer">
            <div id="emptySupplyMessage" class="small text-muted <?= $supplyItems ? 'd-none' : '' ?>">No general supplies added.</div>
            <div id="supplyRows">
                <?php foreach ($supplyItems as $item): ?>
                    <?php
                    $key = (int)$item['id'];
                    $gstType = $taxTypeForItem($item);
                    $taxValue = $taxValueForItem($item, $gstType);
                    $cgstValue = $gstType === 'BOTH' ? (float)($item['cgst_value'] ?? 0) : 0;
                    $knownUnit = in_array($item['unit'], $knownUnits, true);
                    ?>
                    <div class="row g-2 align-items-end supply-row mb-3" data-item-id="<?= $key ?>">
                        <input type="hidden" name="items[<?= $key ?>][id]" value="<?= $key ?>">
                        <input type="hidden" name="items[<?= $key ?>][item_type]" value="GENERAL_SUPPLY">
                        <div class="col-md-4"><label class="form-label">Item Name / Description *</label><input name="items[<?= $key ?>][item_name]" class="form-control supply-name" maxlength="255" value="<?= e($item['item_name']) ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Quantity *</label><input type="number" min="0.001" step="0.001" name="items[<?= $key ?>][quantity]" class="form-control supply-quantity" value="<?= e($item['quantity']) ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Unit *</label><select name="items[<?= $key ?>][unit]" class="form-select supply-unit" required>
                            <?php foreach (['PCS'=>'Pieces','KG'=>'Kilograms','METRE'=>'Metres','LITRE'=>'Litres','BOX'=>'Boxes','PACK'=>'Packs','PAIR'=>'Pairs','SET'=>'Sets','BUNDLE'=>'Bundles'] as $unit => $label): ?><option value="<?= e($unit) ?>" <?= $item['unit'] === $unit ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                            <?php if (!$knownUnit): ?><option value="OTHER" selected>Other</option><?php endif; ?>
                        </select></div>
                        <div class="col-md-2 custom-unit <?= $knownUnit ? 'd-none' : '' ?>"><label class="form-label">Unit Name *</label><input name="items[<?= $key ?>][custom_unit]" class="form-control custom-unit-name" maxlength="30" value="<?= !$knownUnit ? e($item['unit']) : '' ?>" <?= !$knownUnit ? 'required' : '' ?>></div>
                        <div class="col-md-2"><label class="form-label">Unit Price (₹) *</label><input type="number" min="0" step="0.01" name="items[<?= $key ?>][unit_price]" class="form-control supply-price" value="<?= e($item['unit_price']) ?>" required></div>
                        <div class="col-md-3"><label class="form-label">GST</label><select name="items[<?= $key ?>][gst_type]" class="form-select item-gst-type"><?php foreach (['SGST'=>'SGST','IGST'=>'IGST','CGST'=>'CGST','BOTH'=>'Both (CGST and SGST)','EXEMPTED'=>'Exempted'] as $type => $label): ?><option value="<?= e($type) ?>" <?= $gstType === $type ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3 tax-mode-field <?= $gstType === 'EXEMPTED' ? 'd-none' : '' ?>"><label class="form-label">Tax Input Type</label><select name="items[<?= $key ?>][tax_mode]" class="form-select item-tax-mode"><option value="PERCENT" <?= ($item['tax_input_type'] ?? '') !== 'AMOUNT' ? 'selected' : '' ?>>Percentage (%)</option><option value="AMOUNT" <?= ($item['tax_input_type'] ?? '') === 'AMOUNT' ? 'selected' : '' ?>>Amount (₹)</option></select></div>
                        <div class="col-md-3 tax-value-field <?= $gstType === 'EXEMPTED' ? 'd-none' : '' ?>"><label class="form-label item-tax-label"><?= $gstType === 'BOTH' ? 'SGST' : e($gstType) ?></label><input type="number" min="0" step="0.01" name="items[<?= $key ?>][tax_value]" class="form-control item-tax-value" value="<?= e($taxValue) ?>"></div>
                        <div class="col-md-3 tax-cgst-field <?= $gstType !== 'BOTH' ? 'd-none' : '' ?>"><label class="form-label item-cgst-label">CGST</label><input type="number" min="0" step="0.01" name="items[<?= $key ?>][cgst_tax_value]" class="form-control item-cgst-value" value="<?= e($cgstValue) ?>"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-supply" title="Remove supply"><i class="bi bi-trash"></i></button></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card border shadow-sm mt-3">
                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between"><span class="fw-bold small"><i class="bi bi-table me-1 text-success"></i>Other Garment Supplies Details Table</span><span class="badge bg-secondary-subtle text-secondary border" id="supplyTableCountBadge">0 Items</span></div>
                <div class="table-responsive"><table class="table table-hover table-bordered align-middle mb-0" style="font-size:13px">
                    <thead class="table-light small text-uppercase"><tr><th class="text-center">#</th><th>Item Name / Description</th><th class="text-end">Quantity</th><th>Unit</th><th class="text-end">Unit Price</th><th class="text-end">Base Value</th><th>GST Type</th><th class="text-end">Tax</th><th class="text-end bg-success-subtle text-success">Total Value</th></tr></thead>
                    <tbody id="supplyDetailsTableBody"></tbody><tfoot id="supplyDetailsTableFoot" class="table-light fw-bold"></tfoot>
                </table></div>
            </div>
        </div>
    </div>

    <?php if (!empty($finishedItems)): ?>
    <div class="card shadow-sm border mb-4" id="editFinishedGoodsCard">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><h5 class="mb-0 fw-bold"><i class="bi bi-boxes me-2 text-success"></i>Finished Goods Items</h5><small class="text-muted">Update quantities, prices, or GST for purchased finished goods.</small></div>
            <span class="badge bg-success-subtle text-success border"><?= count($finishedItems) ?> Item(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:13px">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Product &amp; Variant</th>
                            <th style="width:130px;">Qty (PCS) *</th>
                            <th style="width:130px;">Unit Price *</th>
                            <th style="width:140px;">GST Type</th>
                            <th style="width:220px;">Tax Rate / Amount</th>
                            <th class="text-end" style="width:110px;">Tax</th>
                            <th class="text-end bg-success-subtle text-success" style="width:120px;">Line Total</th>
                            <th style="width:45px;"></th>
                        </tr>
                    </thead>
                    <tbody id="editFinishedGoodsBody">
                        <?php foreach ($finishedItems as $item):
                            $key = (int)$item['id'];
                            $gstType = $taxTypeForItem($item);
                            $taxValue = $taxValueForItem($item, $gstType);
                            $cgstValue = $gstType === 'BOTH' ? (float)($item['cgst_value'] ?? 0) : 0;
                        ?>
                            <tr class="fg-row" data-item-id="<?= $key ?>">
                                <td>
                                    <input type="hidden" name="items[<?= $key ?>][id]" value="<?= $key ?>">
                                    <input type="hidden" name="items[<?= $key ?>][item_type]" value="FINISHED_GOODS">
                                    <input type="hidden" name="items[<?= $key ?>][variant_id]" value="<?= (int)($item['variant_id'] ?? 0) ?>">
                                    <input type="hidden" name="items[<?= $key ?>][sku]" value="<?= e($item['sku'] ?? '') ?>">
                                    <input type="hidden" name="items[<?= $key ?>][size_name]" value="<?= e($item['size_name'] ?? '') ?>">
                                    <input type="hidden" name="items[<?= $key ?>][color_name]" value="<?= e($item['color_name'] ?? '') ?>">
                                    <input type="hidden" name="items[<?= $key ?>][unit]" value="PCS">
                                    <input type="text" name="items[<?= $key ?>][item_name]" class="form-control form-control-sm fw-semibold text-uppercase mb-1" value="<?= e($item['item_name']) ?>" required>
                                    <div class="small text-muted font-monospace">
                                        <?= e($item['sku'] ?? '') ?>
                                        <?php if (!empty($item['size_name']) || !empty($item['color_name'])): ?>
                                            &middot; <?= e(trim(($item['size_name'] ?? '') . ' / ' . ($item['color_name'] ?? ''), ' /')) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><input type="number" step="1" min="1" name="items[<?= $key ?>][quantity]" class="form-control form-control-sm fg-quantity fw-semibold" value="<?= (int)$item['quantity'] ?>" required></td>
                                <td><input type="number" step="0.01" min="0" name="items[<?= $key ?>][unit_price]" class="form-control form-control-sm fg-price fw-semibold" value="<?= number_format((float)$item['unit_price'], 2, '.', '') ?>" required></td>
                                <td>
                                    <select name="items[<?= $key ?>][gst_type]" class="form-select form-select-sm item-gst-type">
                                        <?php foreach (['EXEMPTED'=>'Exempted','BOTH'=>'SGST + CGST','SGST'=>'SGST','CGST'=>'CGST','IGST'=>'IGST'] as $type => $label): ?>
                                            <option value="<?= e($type) ?>" <?= $gstType === $type ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="row g-1 align-items-center">
                                        <div class="col-4 tax-mode-field">
                                            <select name="items[<?= $key ?>][tax_mode]" class="form-select form-select-sm item-tax-mode">
                                                <option value="PERCENT" <?= ($item['tax_input_type'] ?? '') !== 'AMOUNT' ? 'selected' : '' ?>>%</option>
                                                <option value="AMOUNT" <?= ($item['tax_input_type'] ?? '') === 'AMOUNT' ? 'selected' : '' ?>>₹</option>
                                            </select>
                                        </div>
                                        <div class="col-4 tax-value-field">
                                            <input type="number" step="0.01" min="0" name="items[<?= $key ?>][tax_value]" class="form-control form-control-sm item-tax-value" value="<?= e($taxValue) ?>">
                                            <small class="d-none item-tax-label"></small>
                                        </div>
                                        <div class="col-4 tax-cgst-field">
                                            <input type="number" step="0.01" min="0" name="items[<?= $key ?>][cgst_tax_value]" class="form-control form-control-sm item-cgst-value" value="<?= e($cgstValue) ?>">
                                            <small class="d-none item-cgst-label"></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end fg-line-tax">₹0.00</td>
                                <td class="text-end fw-bold text-success fg-line-total">₹0.00</td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-fg" title="Remove item"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot id="editFinishedGoodsFoot" class="table-light fw-bold"></tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card border shadow-sm mb-4"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2"><span>Subtotal</span><strong id="purchaseSubtotal">₹0.00</strong></div>
        <div class="d-flex justify-content-between align-items-center mb-2"><span>Taxes</span><strong id="purchaseTaxes">₹0.00</strong></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between align-items-center"><strong>Total Price</strong><strong class="fs-5 text-success" id="purchaseTotal">₹0.00</strong></div>
    </div></div>
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= url('production/purchases') ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Save Purchase Changes</button>
    </div>
</form>

<div class="modal fade" id="editPurchaseLabelsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title"><i class="bi bi-qr-code me-2 text-warning"></i>Fabric Roll Labels Preview</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body bg-light"><div id="editPurchaseLabelCards" class="d-flex flex-wrap justify-content-center gap-3"></div></div>
            <div class="modal-footer"><span class="small text-muted me-auto" id="editPurchaseLabelCount"></span><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
<style>
.purchase-label-card{width:250px;min-height:130px;border:1.5px solid #0f172a;border-radius:6px;background:#fff;padding:8px;display:flex;gap:8px;align-items:center}
.purchase-label-qr{width:76px;height:76px;flex-shrink:0}
.purchase-label-qr img,.purchase-label-qr canvas{width:100%!important;height:100%!important}
.purchase-label-code{font-family:monospace;font-size:10px;font-weight:800;word-break:break-all}
.purchase-label-info{font-size:9px;overflow-wrap:anywhere}
</style>
<template id="editSupplyTemplate">
    <div class="row g-2 align-items-end supply-row mb-3">
        <div class="col-md-4"><label class="form-label">Item Name / Description *</label><input class="form-control supply-name" maxlength="255" required></div>
        <div class="col-md-2"><label class="form-label">Quantity *</label><input type="number" class="form-control supply-quantity" min="0.001" step="0.001" value="1" required></div>
        <div class="col-md-2"><label class="form-label">Unit *</label><select class="form-select supply-unit"><option value="PCS">Pieces</option><option value="KG">Kilograms</option><option value="METRE">Metres</option><option value="LITRE">Litres</option><option value="BOX">Boxes</option><option value="PACK">Packs</option><option value="PAIR">Pairs</option><option value="SET">Sets</option><option value="BUNDLE">Bundles</option><option value="OTHER">Other</option></select></div>
        <div class="col-md-2 custom-unit d-none"><label class="form-label">Unit Name *</label><input class="form-control custom-unit-name" maxlength="30"></div>
        <div class="col-md-2"><label class="form-label">Unit Price (₹) *</label><input type="number" class="form-control supply-price" min="0" step="0.01" value="0" required></div>
        <div class="col-md-3"><label class="form-label">GST</label><select class="form-select item-gst-type"><option value="SGST">SGST</option><option value="IGST">IGST</option><option value="CGST">CGST</option><option value="BOTH">Both (CGST and SGST)</option><option value="EXEMPTED" selected>Exempted</option></select></div>
        <div class="col-md-3 tax-mode-field d-none"><label class="form-label">Tax Input Type</label><select class="form-select item-tax-mode"><option value="PERCENT">Percentage (%)</option><option value="AMOUNT">Amount (₹)</option></select></div>
        <div class="col-md-3 tax-value-field d-none"><label class="form-label item-tax-label">Tax</label><input type="number" class="form-control item-tax-value" min="0" max="100" step="0.01" value="0"></div>
        <div class="col-md-3 tax-cgst-field d-none"><label class="form-label item-cgst-label">CGST</label><input type="number" class="form-control item-cgst-value" min="0" max="100" step="0.01" value="0"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-supply" title="Remove supply"><i class="bi bi-trash"></i></button></div>
    </div>
</template>

<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>
(() => {
    const form = document.getElementById('editProductionPurchaseForm');
    const rollsContainer = document.getElementById('rollsContainer');
    const suppliesContainer = document.getElementById('supplyRows');
    const money = value => '₹' + (Number(value) || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const safe = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));

    function calculateTax(row, base) {
        const type = row.querySelector('.item-gst-type').value;
        const mode = row.querySelector('.item-tax-mode').value;
        const value = Number(row.querySelector('.item-tax-value').value) || 0;
        const cgstValue = Number(row.querySelector('.item-cgst-value').value) || 0;
        const taxAmount = amount => mode === 'AMOUNT' ? amount : Math.round(base * amount) / 100;
        const sgst = ['SGST', 'BOTH'].includes(type) ? taxAmount(value) : 0;
        const cgst = type === 'CGST' ? taxAmount(value) : (type === 'BOTH' ? taxAmount(cgstValue) : 0);
        const igst = type === 'IGST' ? taxAmount(value) : 0;
        return {type, sgst, cgst, igst, amount: sgst + cgst + igst};
    }

    function updateTaxFields(row) {
        const type = row.querySelector('.item-gst-type').value;
        const mode = row.querySelector('.item-tax-mode').value;
        const active = type !== 'EXEMPTED';
        row.querySelector('.tax-mode-field').classList.toggle('d-none', !active);
        row.querySelector('.tax-value-field').classList.toggle('d-none', !active);
        row.querySelector('.tax-cgst-field').classList.toggle('d-none', type !== 'BOTH');
        row.querySelector('.item-tax-value').required = active;
        row.querySelector('.item-cgst-value').required = type === 'BOTH';
        row.querySelector('.item-tax-label').textContent = `${type === 'BOTH' ? 'SGST' : type} (${mode === 'PERCENT' ? '%' : '₹'})`;
        row.querySelector('.item-cgst-label').textContent = `CGST (${mode === 'PERCENT' ? '%' : '₹'})`;
        [row.querySelector('.item-tax-value'), row.querySelector('.item-cgst-value')].forEach(input => {
            if (mode === 'PERCENT') input.max = '100';
            else input.removeAttribute('max');
        });
    }

    function updateSummary() {
        const rolls = Array.from(rollsContainer.querySelectorAll('.roll-card'));
        const supplyRows = Array.from(suppliesContainer.querySelectorAll('.supply-row')).filter(row => !row.classList.contains('d-none'));
        const rollTableBody = document.getElementById('rollDetailsTableBody');
        const rollTableRows = [];
        let subtotal = 0, taxes = 0, total = 0, totalWeight = 0, totalRq = 0;
        const colors = new Set(), designs = new Set();
        document.getElementById('editFabricDescription').required = rolls.length > 0;
        rolls.forEach((card, index) => {
            const key = card.dataset.itemKey;
            const nameInput = card.querySelector('.roll-item-name');
            nameInput.value = document.getElementById('editFabricDescription').value.trim().toUpperCase();
            const price = Number(card.querySelector('.roll-price').value) || 0;
            const weight = Number(card.querySelector('.roll-weight').value) || 0;
            const quantity = Number(card.querySelector(`[name="items[${key}][quantity]"]`).value) || 0;
            const tax = calculateTax(card, price);
            const startCode = card.querySelector('.roll-code-text').textContent.trim();
            const photoFile = card.querySelector('.roll-photo').files[0];
            const photoUrl = card.dataset.photoUrl;
            const photoHtml = photoFile
                ? '<span class="text-muted"><i class="bi bi-image"></i></span>'
                : (photoUrl ? `<a href="${safe(photoUrl)}" target="_blank" rel="noopener"><img src="${safe(photoUrl)}" alt="Roll" style="width:38px;height:38px;object-fit:cover"></a>` : '<span class="text-muted"><i class="bi bi-camera"></i></span>');
            subtotal += price;
            taxes += tax.amount;
            total += price + tax.amount;
            totalWeight += weight;
            totalRq += quantity;
            if (card.querySelector('.roll-color').value.trim()) colors.add(card.querySelector('.roll-color').value.trim().toUpperCase());
            if (card.querySelector('.roll-design').value.trim()) designs.add(card.querySelector('.roll-design').value.trim().toUpperCase());
            const taxLabel = tax.type === 'BOTH' ? 'SGST + CGST' : tax.type;
            card.querySelector('.roll-title').textContent = `ROLL #${index + 1}`;
            rollTableRows.push(`<tr><td class="text-center">${index + 1}</td><td>${photoHtml}</td><td><strong>${safe(card.querySelector('.roll-number').value)}</strong><small class="d-block font-monospace">${safe(startCode || '—')}</small></td><td>${safe(card.querySelector('.roll-color').value || '—')}</td><td>${safe(card.querySelector('.roll-design').value || '—')}</td><td class="text-center">${Number(card.querySelector('.roll-design-count').value) || 0}</td><td class="text-end">${quantity.toLocaleString('en-IN')} RQ</td><td class="text-end">${weight.toFixed(2)} kg</td><td class="text-end">${money(price)}</td><td>${taxLabel}</td><td class="text-end">${money(tax.amount)}</td><td class="text-end fw-bold text-success">${money(price + tax.amount)}</td><td class="text-center"><span class="badge bg-info-subtle text-info">Existing roll</span></td></tr>`);
        });
        document.getElementById('rollCountBadge').textContent = rolls.length;
        document.getElementById('tableRollCountBadge').textContent = `${rolls.length} ${rolls.length === 1 ? 'Roll' : 'Rolls'}`;
        document.getElementById('summaryTotalRolls').textContent = `${rolls.length} ${rolls.length === 1 ? 'Roll' : 'Rolls'}`;
        document.getElementById('summaryTotalRQ').textContent = `${totalRq.toLocaleString('en-IN')} RQ`;
        document.getElementById('summaryColorsDesigns').textContent = `${colors.size} Colors • ${designs.size} Designs`;
        document.getElementById('summaryTotalWeight').textContent = `${totalWeight.toFixed(2)} kg`;
        document.getElementById('summaryTotalPrice').textContent = money(rolls.reduce((sum, row) => {
            const price = Number(row.querySelector('.roll-price').value) || 0;
            return sum + price + calculateTax(row, price).amount;
        }, 0));
        rollTableBody.innerHTML = rollTableRows.length ? rollTableRows.join('') : '<tr><td colspan="13" class="text-center text-muted py-4">No fabric rolls in this purchase.</td></tr>';

        const supplyTableBody = document.getElementById('supplyDetailsTableBody');
        const supplyTableFoot = document.getElementById('supplyDetailsTableFoot');
        const supplyTableRows = [];
        let supplyBase = 0, supplyTax = 0, supplyTotal = 0;
        supplyRows.forEach((row, index) => {
            const quantity = Number(row.querySelector('.supply-quantity').value) || 0;
            const unitPrice = Number(row.querySelector('.supply-price').value) || 0;
            const base = quantity * unitPrice;
            const tax = calculateTax(row, base);
            const unitOption = row.querySelector('.supply-unit').selectedOptions[0];
            const unit = unitOption.value === 'OTHER' ? row.querySelector('.custom-unit-name').value : unitOption.textContent;
            supplyBase += base;
            supplyTax += tax.amount;
            supplyTotal += base + tax.amount;
            supplyTableRows.push(`<tr><td class="text-center">${index + 1}</td><td>${safe(row.querySelector('.supply-name').value.trim() || '—')}</td><td class="text-end">${quantity.toLocaleString('en-IN', {maximumFractionDigits: 3})}</td><td>${safe(unit)}</td><td class="text-end">${money(unitPrice)}</td><td class="text-end">${money(base)}</td><td>${tax.type === 'BOTH' ? 'SGST + CGST' : tax.type}</td><td class="text-end">${money(tax.amount)}</td><td class="text-end fw-bold text-success">${money(base + tax.amount)}</td></tr>`);
        });
        document.getElementById('supplyTableCountBadge').textContent = `${supplyRows.length} ${supplyRows.length === 1 ? 'Item' : 'Items'}`;
        supplyTableBody.innerHTML = supplyTableRows.length ? supplyTableRows.join('') : '<tr><td colspan="9" class="text-center text-muted py-4">Add garment supplies to see their purchase details.</td></tr>';
        supplyTableFoot.innerHTML = supplyTableRows.length ? `<tr><td colspan="5" class="text-end">Totals</td><td class="text-end">${money(supplyBase)}</td><td></td><td class="text-end">${money(supplyTax)}</td><td class="text-end text-success">${money(supplyTotal)}</td></tr>` : '';

        let fgBase = 0, fgTax = 0, fgTotal = 0;
        const fgRows = Array.from(document.querySelectorAll('#editFinishedGoodsBody tr.fg-row')).filter(row => !row.classList.contains('d-none'));
        fgRows.forEach(row => {
            const quantity = Number(row.querySelector('.fg-quantity').value) || 0;
            const unitPrice = Number(row.querySelector('.fg-price').value) || 0;
            const base = quantity * unitPrice;
            const tax = calculateTax(row, base);
            const rowTotal = base + tax.amount;
            fgBase += base;
            fgTax += tax.amount;
            fgTotal += rowTotal;
            row.querySelector('.fg-line-tax').textContent = money(tax.amount);
            row.querySelector('.fg-line-total').textContent = money(rowTotal);
        });
        const fgFoot = document.getElementById('editFinishedGoodsFoot');
        if (fgFoot) {
            fgFoot.innerHTML = fgRows.length
                ? `<tr><td colspan="5" class="text-end">Finished Goods Totals (Base: ${money(fgBase)})</td><td class="text-end">${money(fgTax)}</td><td class="text-end text-success">${money(fgTotal)}</td><td></td></tr>`
                : '';
        }

        subtotal += supplyBase + fgBase;
        taxes += supplyTax + fgTax;
        total += supplyTotal + fgTotal;
        document.getElementById('purchaseSubtotal').textContent = money(subtotal);
        document.getElementById('purchaseTaxes').textContent = money(taxes);
        document.getElementById('purchaseTotal').textContent = money(total);
    }

    function addSupplyRow() {
        const row = document.getElementById('editSupplyTemplate').content.firstElementChild.cloneNode(true);
        const key = `new-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
        const itemKey = `new_${key.replace(/[^A-Za-z0-9_]/g, '')}`;
        const names = {'.supply-name':'item_name','.supply-quantity':'quantity','.supply-unit':'unit','.supply-price':'unit_price','.item-gst-type':'gst_type','.item-tax-mode':'tax_mode','.item-tax-value':'tax_value','.item-cgst-value':'cgst_tax_value'};
        Object.entries(names).forEach(([selector, field]) => row.querySelector(selector).name = `items[${itemKey}][${field}]`);
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = `items[${itemKey}][item_type]`;
        typeInput.value = 'GENERAL_SUPPLY';
        row.appendChild(typeInput);
        configureSupplyRow(row);
        suppliesContainer.appendChild(row);
        document.getElementById('emptySupplyMessage').classList.add('d-none');
        updateSummary();
    }

    function previewLabels() {
        const cards = document.getElementById('editPurchaseLabelCards');
        cards.innerHTML = '';
        let labelCount = 0;
        const material = document.getElementById('editFabricDescription').value.trim().toUpperCase();
        const supplier = <?= json_encode($purchase['seller_company'] ?: $purchase['seller_name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        rollsContainer.querySelectorAll('.roll-card').forEach((card, index) => {
            const quantity = Number(card.querySelector(`[name="items[${card.dataset.itemKey}][quantity]"]`).value) || 0;
            const start = Number(card.dataset.sequenceStart) || 0;
            const startCode = card.dataset.startCode;
            const prefix = startCode.replace(/\d+$/, '');
            for (let piece = 0; piece < quantity; piece++) {
                const code = `${prefix}${String(start + piece).padStart(2, '0')}`;
                const sticker = document.createElement('div');
                sticker.className = 'purchase-label-card';
                sticker.innerHTML = `<div class="purchase-label-qr"></div><div class="purchase-label-info"><div class="fw-bold">${safe(supplier)}</div><div class="purchase-label-code">${safe(code)}</div><div>${safe(card.querySelector('.roll-number').value || `ROLL #${index + 1}`)}</div><div>${safe(material)}</div><div>${safe(card.querySelector('.roll-color').value)} · ${safe(card.querySelector('.roll-design').value)}</div><div>${(Number(card.querySelector('.roll-weight').value) || 0).toFixed(2)} kg · Piece ${piece + 1} of ${quantity}</div></div>`;
                cards.appendChild(sticker);
                const qrTarget = sticker.querySelector('.purchase-label-qr');
                if (window.QRCode) new QRCode(qrTarget, {text: `${code}|${supplier}|${card.querySelector('.roll-number').value}|${material}`, width: 76, height: 76, correctLevel: QRCode.CorrectLevel.M});
                else qrTarget.textContent = code;
                labelCount++;
            }
        });
        document.getElementById('editPurchaseLabelCount').textContent = `${labelCount} ${labelCount === 1 ? 'label' : 'labels'}`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editPurchaseLabelsModal')).show();
    }

    function configureSupplyRow(row) {
        row.querySelectorAll('input,select').forEach(input => {
            input.addEventListener('input', updateSummary);
            input.addEventListener('change', updateSummary);
        });
        row.querySelector('.item-gst-type').addEventListener('change', () => { updateTaxFields(row); updateSummary(); });
        row.querySelector('.item-tax-mode').addEventListener('change', () => { updateTaxFields(row); updateSummary(); });
        row.querySelector('.supply-unit').addEventListener('change', event => {
            const custom = event.target.value === 'OTHER';
            const field = row.querySelector('.custom-unit');
            const input = row.querySelector('.custom-unit-name');
            field.classList.toggle('d-none', !custom);
            input.required = custom;
            if (custom) input.name = event.target.name.replace('[unit]', '[custom_unit]');
            else input.removeAttribute('name');
            updateSummary();
        });
        row.querySelector('.remove-supply').addEventListener('click', () => {
            const itemId = row.dataset.itemId;
            if (itemId) {
                let removeField = row.querySelector('.remove-item-value');
                if (!removeField) {
                    removeField = document.createElement('input');
                    removeField.type = 'hidden';
                    removeField.name = `items[${itemId}][remove_item]`;
                    removeField.className = 'remove-item-value';
                    removeField.value = '1';
                    row.appendChild(removeField);
                }
                row.classList.add('d-none');
                row.querySelectorAll('input,select').forEach(input => {
                    if (!input.classList.contains('remove-item-value') && !input.name.endsWith('[id]') && !input.name.endsWith('[item_type]')) input.disabled = true;
                });
            } else {
                row.remove();
            }
            if (!suppliesContainer.querySelector('.supply-row:not(.d-none)')) document.getElementById('emptySupplyMessage').classList.remove('d-none');
            updateSummary();
        });
        updateTaxFields(row);
    }

    rollsContainer.querySelectorAll('.roll-card').forEach(card => {
        card.querySelectorAll('input,select').forEach(input => {
            input.addEventListener('input', updateSummary);
            input.addEventListener('change', updateSummary);
        });
        card.querySelector('.item-gst-type').addEventListener('change', () => { updateTaxFields(card); updateSummary(); });
        card.querySelector('.item-tax-mode').addEventListener('change', () => { updateTaxFields(card); updateSummary(); });
        updateTaxFields(card);
    });
    document.querySelectorAll('#editFinishedGoodsBody tr.fg-row').forEach(row => {
        row.querySelectorAll('input,select').forEach(input => {
            input.addEventListener('input', updateSummary);
            input.addEventListener('change', updateSummary);
        });
        row.querySelector('.item-gst-type')?.addEventListener('change', () => { updateTaxFields(row); updateSummary(); });
        row.querySelector('.item-tax-mode')?.addEventListener('change', () => { updateTaxFields(row); updateSummary(); });
        row.querySelector('.remove-fg')?.addEventListener('click', () => {
            const itemId = row.dataset.itemId;
            if (itemId) {
                let removeField = row.querySelector('.remove-item-value');
                if (!removeField) {
                    removeField = document.createElement('input');
                    removeField.type = 'hidden';
                    removeField.name = `items[${itemId}][remove_item]`;
                    removeField.className = 'remove-item-value';
                    removeField.value = '1';
                    row.appendChild(removeField);
                }
                row.classList.add('d-none');
                row.querySelectorAll('input,select').forEach(input => {
                    if (!input.classList.contains('remove-item-value') && !input.name.endsWith('[id]') && !input.name.endsWith('[item_type]')) input.disabled = true;
                });
            }
            updateSummary();
        });
        updateTaxFields(row);
    });
    suppliesContainer.querySelectorAll('.supply-row').forEach(configureSupplyRow);
    document.getElementById('editFabricDescription').addEventListener('input', updateSummary);
    document.getElementById('addSupply').addEventListener('click', addSupplyRow);
    document.getElementById('previewPurchaseLabels').addEventListener('click', previewLabels);
    form.addEventListener('submit', event => {
        const hasRoll = rollsContainer.querySelector('.roll-card');
        const hasSupply = suppliesContainer.querySelector('.supply-row:not(.d-none)');
        const hasFg = document.querySelector('#editFinishedGoodsBody tr.fg-row:not(.d-none)');
        if (!hasRoll && !hasSupply && !hasFg) {
            event.preventDefault();
            alert('Keep at least one finished goods line, fabric roll, or general supply.');
        }
    });
    updateSummary();
})();
</script>
