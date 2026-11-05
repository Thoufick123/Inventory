<div class="page-header mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 class="mb-0"><i class="bi bi-truck-flatbed me-2 text-primary"></i><?= e($seller['name']) ?></h2>
                <?php if ($seller['is_active']): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active Supplier</span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                <?php endif; ?>
            </div>
            <span class="text-muted">
                <?php if (!empty($seller['company'])): ?>
                    <strong class="text-dark"><i class="bi bi-building me-1"></i><?= e($seller['company']) ?></strong> • 
                <?php endif; ?>
                Material Supplier & Fabric Lot Specification Sheet
            </span>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-dark shadow-sm" onclick="openFabricLotLabelsModal()">
                <i class="bi bi-qr-code me-1 text-warning"></i> Preview Labels
            </button>
            <a href="<?= url('production/sellers/' . $seller['id'] . '/labels') ?>" class="btn btn-outline-dark shadow-sm" target="_blank" title="Open full print sheet in new tab">
                <i class="bi bi-printer me-1"></i> Print Labels
            </a>
            <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
            </a>
            <a href="<?= url('production/sellers/edit/' . $seller['id']) ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-pencil me-1"></i> Edit Supplier & Rolls
            </a>
            <form method="POST" action="<?= url('production/sellers/delete/' . $seller['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Supplier \'<?= e(addslashes($seller['name'])) ?>\' and all associated fabric roll creations?');">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-outline-danger shadow-sm" title="Delete Supplier & Rolls">
                    <i class="bi bi-trash me-1"></i> Delete Supplier
                </button>
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Fabric Rolls, Specs & Batch Summary -->
    <div class="col-lg-8">
        <!-- 1. Fabric Specifications & Summary Card -->
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bi bi-layers-half me-2 text-primary"></i>Fabric Lot Overview & Specifications
                </h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <?= (int)($seller['roll_count'] ?? count($rolls)) ?> Fabric Rolls
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label class="text-muted small fw-semibold d-block">FABRIC / MATERIAL GRADE</label>
                        <div class="fs-6 fw-bold text-dark mt-1">
                            <?= e($seller['material_type'] ?: 'STANDARD FABRIC LOT') ?>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="text-muted small fw-semibold d-block">COLORS & SHADES</label>
                        <div class="mt-1">
                            <?php if (!empty($seller['colors'])): ?>
                                <?php foreach (explode(',', $seller['colors']) as $col): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1"><i class="bi bi-palette text-primary me-1"></i><?= e(trim($col)) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Commercial Metrics Cards -->
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-light rounded border text-center">
                            <span class="text-muted small d-block mb-1">TOTAL ROLLS</span>
                            <span class="fs-4 fw-bold text-primary"><?= (int)($seller['roll_count'] ?? count($rolls)) ?></span>
                            <span class="text-muted small d-block">Roll Units</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-light rounded border text-center">
                            <span class="text-muted small d-block mb-1">DESIGNS / SHADES</span>
                            <span class="fs-4 fw-bold text-warning"><?= (int)($seller['design_count'] ?? 0) ?></span>
                            <span class="text-muted small d-block"><?= (int)($seller['color_count'] ?? 0) ?> Colors</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-light rounded border text-center">
                            <span class="text-muted small d-block mb-1">TOTAL WEIGHT</span>
                            <span class="fs-4 fw-bold text-dark"><?= number_format((float)($seller['roll_weight'] ?? 0), 2) ?></span>
                            <span class="text-muted small d-block">Kilograms (kg)</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 bg-light rounded border text-center">
                            <span class="text-muted small d-block mb-1">TOTAL VALUATION</span>
                            <span class="fs-4 fw-bold text-success">₹ <?= number_format((float)($seller['roll_price'] ?? 0), 2) ?></span>
                            <?php if ((float)($seller['roll_weight'] ?? 0) > 0): ?>
                                <span class="text-muted small d-block">₹<?= number_format((float)$seller['roll_price'] / (float)$seller['roll_weight'], 1) ?>/kg</span>
                            <?php else: ?>
                                <span class="text-muted small d-block">INR</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Individual Fabric Rolls Breakdown -->
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bi bi-stack me-2 text-primary"></i>Individual Fabric Roll Specifications (<?= count($rolls) ?>)
                </h5>
                <small class="text-muted">High resolution photos, design names, counts, weights, and roll prices</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($rolls)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                        <span class="fw-semibold">No individual roll records logged yet for this supplier.</span>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th style="width: 60px;">Photo</th>
                                    <th>Roll Number / Code</th>
                                    <th>Color / Shade</th>
                                    <th>Design / Pattern</th>
                                    <th class="text-center">Designs</th>
                                    <th class="text-end">Total Stock</th>
                                    <th class="text-end text-danger bg-danger-subtle bg-opacity-25">Damages</th>
                                    <th class="text-end bg-light-subtle text-muted">Used in Programs</th>
                                    <th class="text-end bg-success-subtle fw-bold text-success">Available Balance</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 70px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totInitQty = 0; $totInitWt = 0.0; $totInitPrice = 0.0;
                                $totDmgQty = 0; $totDmgWt = 0.0; $totDmgPrice = 0.0;
                                $totUsedQty = 0; $totUsedWt = 0.0; $totUsedPrice = 0.0;
                                $totAvailQty = 0; $totAvailWt = 0.0; $totAvailPrice = 0.0;
                                foreach ($rolls as $idx => $r): 
                                    $initQty = (int)($r['quantity'] ?? 0);
                                    $initWt = (float)($r['weight'] ?? 0);
                                    $initPrice = (float)($r['price'] ?? 0);
                                    
                                    $dmgQty = (int)($r['damaged_quantity'] ?? 0);
                                    $dmgWt = (float)($r['damaged_weight'] ?? 0);
                                    $dmgPrice = (float)($r['damaged_price'] ?? 0);
                                    
                                    $usedQty = (int)($r['used_quantity'] ?? 0);
                                    $usedWt = (float)($r['used_weight'] ?? 0);
                                    $usedPrice = (float)($r['used_price'] ?? 0);
                                    
                                    $availQty = (int)($r['available_quantity'] ?? max(0, $initQty - $usedQty - $dmgQty));
                                    $availWt = (float)($r['available_weight'] ?? max(0.0, $initWt - $usedWt - $dmgWt));
                                    $availPrice = (float)($r['available_price'] ?? max(0.0, $initPrice - $usedPrice - $dmgPrice));
                                    
                                    $totInitQty += $initQty; $totInitWt += $initWt; $totInitPrice += $initPrice;
                                    $totDmgQty += $dmgQty; $totDmgWt += $dmgWt; $totDmgPrice += $dmgPrice;
                                    $totUsedQty += $usedQty; $totUsedWt += $usedWt; $totUsedPrice += $usedPrice;
                                    $totAvailQty += $availQty; $totAvailWt += $availWt; $totAvailPrice += $availPrice;
                                ?>
                                    <tr>
                                        <!-- Photo Thumbnail with Modal Zoom -->
                                        <td>
                                            <?php if (!empty($r['photo_url'])): ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <a href="<?= uploadUrl($r['photo_url']) ?>" target="_blank" title="Click to view full photo">
                                                        <img src="<?= uploadUrl($r['photo_url']) ?>" alt="Roll Photo" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'44\' height=\'44\' viewBox=\'0 0 44 44\'%3E%3Crect width=\'44\' height=\'44\' fill=\'%23e9ecef\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'sans-serif\' font-size=\'8\' fill=\'%236c757d\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                                                    </a>
                                                    <a href="<?= url('production/sellers/download-roll-photo/' . $r['id']) ?>" class="btn btn-sm btn-outline-secondary p-1" title="Download Roll Photo" download>
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;" title="No photo uploaded">
                                                    <i class="bi bi-camera fs-5 text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Roll Number & Label Code -->
                                        <td>
                                            <div class="fw-bold text-dark fs-6"><?= e($r['roll_number']) ?></div>
                                            <?php if (!empty($r['start_code'])): ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-dark text-white font-monospace" style="font-size: 11px; letter-spacing: 0.5px;" title="Fabric Label Sequence: <?= e($r['start_code']) ?><?= (!empty($r['end_code']) && $r['end_code'] !== $r['start_code']) ? ' to ' . e($r['end_code']) : '' ?>">
                                                        <i class="bi bi-qr-code text-warning me-1"></i><?= e($r['start_code']) ?><?= (!empty($r['end_code']) && $r['end_code'] !== $r['start_code']) ? '–' . e($r['end_code']) : '' ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted d-block mt-1">Item #<?= $idx + 1 ?></small>
                                        </td>

                                        <!-- Color -->
                                        <td>
                                            <?php if (!empty($r['color'])): ?>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle fw-semibold">
                                                    <i class="bi bi-palette me-1"></i><?= e($r['color']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Design Name -->
                                        <td>
                                            <?php if (!empty($r['design_name'])): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-semibold">
                                                    <i class="bi bi-brush me-1"></i><?= e($r['design_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Design Count -->
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary border">
                                                <?= (int)($r['design_count'] ?? 1) ?>
                                            </span>
                                        </td>

                                        <!-- Initial Stock -->
                                        <td class="text-end">
                                            <div class="fw-bold text-dark"><?= number_format($initQty) ?> qty</div>
                                            <small class="text-muted d-block"><?= number_format($initWt, 2) ?> kg • ₹<?= number_format($initPrice, 2) ?></small>
                                        </td>

                                        <!-- Damages -->
                                        <td class="text-end bg-danger-subtle bg-opacity-25">
                                            <?php if ($dmgQty > 0 || $dmgWt > 0): ?>
                                                <div class="fw-bold text-danger">-<?= number_format($dmgQty) ?> qty</div>
                                                <small class="text-danger d-block"><?= number_format($dmgWt, 2) ?> kg • ₹<?= number_format($dmgPrice, 2) ?></small>
                                                <div class="mt-1">
                                                    <a href="<?= url('production/damages?seller_id=' . $seller['id'] . '&search=' . urlencode($r['roll_number'])) ?>" class="badge bg-danger text-white text-decoration-none" style="font-size: 10px;" title="<?= e($r['damage_reason'] ?? 'Defect Issue Logged') ?>">
                                                        <i class="bi bi-exclamation-octagon me-1"></i><?= e($r['damage_status'] ?? 'LOGGED') ?>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">0 qty</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Used in Programs -->
                                        <td class="text-end bg-light-subtle">
                                            <?php if ($usedQty > 0): ?>
                                                <div class="fw-bold text-secondary">-<?= number_format($usedQty) ?> qty</div>
                                                <small class="text-muted d-block"><?= number_format($usedWt, 2) ?> kg • ₹<?= number_format($usedPrice, 2) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted small">0 qty</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Available Balance -->
                                        <td class="text-end bg-success-subtle">
                                            <div class="fw-bold text-success"><?= number_format($availQty) ?> qty</div>
                                            <small class="text-muted d-block"><?= number_format($availWt, 2) ?> kg • ₹<?= number_format($availPrice, 2) ?></small>
                                        </td>

                                        <!-- Status -->
                                        <td class="text-center">
                                            <?php if ($dmgWt >= $initWt && $initWt > 0): ?>
                                                <span class="badge bg-danger text-white px-2 py-1">
                                                    <i class="bi bi-exclamation-diamond me-1"></i>Damaged
                                                </span>
                                            <?php elseif ($usedQty === 0 && $dmgQty === 0 && $dmgWt == 0): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-check-circle me-1"></i>In Stock
                                                </span>
                                            <?php elseif ($availQty > 0): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                    <i class="bi bi-hourglass-split me-1"></i><?= $availQty ?> qty Left
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                    <i class="bi bi-x-circle me-1"></i>Fully Consumed
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Action (Print Label / Report Damage / Delete Roll) -->
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-dark" title="Preview or print labels for roll <?= e($r['roll_number']) ?>" aria-label="Preview or print labels for roll <?= e($r['roll_number']) ?>" onclick="openSingleRollLabelModal(<?= (int)$r['id'] ?>, '<?= e(addslashes($r['roll_number'])) ?>')">
                                                    <i class="bi bi-qr-code"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Add continuation label for roll <?= e($r['roll_number']) ?>" aria-label="Add continuation label for roll <?= e($r['roll_number']) ?>" onclick="promptAddExtraRollLabel(<?= (int)$r['id'] ?>, '<?= e(addslashes($r['roll_number'])) ?>', '<?= e(addslashes($r['end_code'] ?? '')) ?>', <?= (int)$r['quantity'] ?>)">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Report damage or return roll <?= e($r['roll_number']) ?> to supplier" aria-label="Report damage or return roll <?= e($r['roll_number']) ?> to supplier" onclick='openSupplierRollDamageModal(<?= json_encode($r) ?>)'>
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                </button>
                                                <?php if ($usedQty === 0 && empty($r['damaged_weight'])): ?>
                                                    <form method="POST" action="<?= url('production/sellers/delete-roll/' . $r['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Roll \'<?= e(addslashes($r['roll_number'])) ?>\'?');">
                                                        <?= CSRF::field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete roll <?= e($r['roll_number']) ?>" aria-label="Delete roll <?= e($r['roll_number']) ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Cannot delete: roll is assigned in production or has logged damages" aria-label="Roll cannot be deleted" disabled>
                                                        <i class="bi bi-lock-fill"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">Total Summary:</td>
                                    <td class="text-end text-dark">
                                        <?= number_format($totInitQty) ?> qty
                                        <small class="text-muted d-block fw-normal"><?= number_format($totInitWt, 2) ?> kg • ₹<?= number_format($totInitPrice, 2) ?></small>
                                    </td>
                                    <td class="text-end text-danger bg-danger-subtle bg-opacity-25">
                                        <?= $totDmgQty > 0 ? '-' . number_format($totDmgQty) . ' qty' : '0 qty' ?>
                                        <small class="text-danger d-block fw-normal"><?= $totDmgWt > 0 ? number_format($totDmgWt, 2) . ' kg' : '0.00 kg' ?></small>
                                    </td>
                                    <td class="text-end text-muted bg-light-subtle">
                                        <?= $totUsedQty > 0 ? '-' . number_format($totUsedQty) . ' qty' : '0 qty' ?>
                                        <small class="text-muted d-block fw-normal"><?= number_format($totUsedWt, 2) ?> kg</small>
                                    </td>
                                    <td class="text-end text-success bg-success-subtle">
                                        <?= number_format($totAvailQty) ?> qty
                                        <small class="text-muted d-block fw-normal"><?= number_format($totAvailWt, 2) ?> kg • ₹<?= number_format($totAvailPrice, 2) ?></small>
                                    </td>
                                    <td class="text-center" colspan="2">
                                        <span class="badge bg-primary-subtle text-primary border"><?= count($rolls) ?> Rolls Total</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Business Profile, Payment Terms & Bill Attachment -->
    <div class="col-lg-4">
        <!-- 1. Supplier Profile Card -->
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bi bi-person-vcard me-2 text-primary"></i>Supplier Profile
                </h5>
            </div>
            <div class="card-body p-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                        <span class="text-muted small">SUPPLIER NAME</span>
                        <strong class="text-dark"><?= e($seller['name']) ?></strong>
                    </li>
                    <?php if (!empty($seller['company'])): ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted small">COMPANY / MILL</span>
                            <span class="fw-bold text-primary"><?= e($seller['company']) ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($seller['phone'])): ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted small">PHONE NUMBER</span>
                            <a href="tel:<?= e($seller['phone']) ?>" class="text-decoration-none fw-semibold">
                                <i class="bi bi-telephone me-1"></i><?= e($seller['phone']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($seller['email'])): ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted small">EMAIL ADDRESS</span>
                            <a href="mailto:<?= e($seller['email']) ?>" class="text-decoration-none small">
                                <?= e($seller['email']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($seller['gst_number'])): ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted small">GSTIN / TAX ID</span>
                            <span class="badge bg-light text-dark border font-monospace"><?= e($seller['gst_number']) ?></span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($seller['city']) || !empty($seller['state'])): ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                            <span class="text-muted small">LOCATION</span>
                            <span class="fw-semibold text-dark">
                                <?= e(implode(', ', array_filter([$seller['city'] ?? '', $seller['state'] ?? '']))) ?>
                            </span>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($seller['address'])): ?>
                        <li class="list-group-item px-0 py-2">
                            <span class="text-muted small d-block mb-1">FACTORY / MILL ADDRESS</span>
                            <p class="mb-0 text-dark small bg-light p-2 rounded border"><?= nl2br(e($seller['address'])) ?></p>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>


        <!-- 3. Tax Invoice / Bill Attachment Card -->
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bi bi-file-earmark-arrow-down me-2 text-primary"></i>Bill / Invoice Attachment
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($seller['document_url'])): ?>
                    <div class="p-3 bg-light rounded border text-center">
                        <a href="<?= uploadUrl($seller['document_url']) ?>" target="_blank" class="d-block mb-3">
                            <img src="<?= uploadUrl($seller['document_url']) ?>" alt="Supplier Bill Document" class="img-fluid rounded border shadow-sm" style="max-height: 220px; object-fit: contain; background: #fff;" onerror="this.parentElement.style.display='none'">
                        </a>
                        <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-file-earmark-check text-success me-1"></i>Supplier Bill / Invoice Attached</span>
                        <div class="d-grid gap-2">
                            <a href="<?= url('production/sellers/download-bill/' . $seller['id']) ?>" class="btn btn-primary shadow-sm" download>
                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Bill File
                            </a>
                            <a href="<?= uploadUrl($seller['document_url']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open Preview in Browser
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-file-earmark-x fs-2 d-block mb-1 text-secondary"></i>
                        <small>No bill or invoice document uploaded for this supplier.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Report Damage / Supplier Return Modal for this Supplier -->
<div class="modal fade" id="supplierRollDamageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-danger">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold fs-6">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Report Defective Roll / Supplier Return
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= url('production/damages/store') ?>" enctype="multipart/form-data" id="supplierRollDamageForm">
                <?= CSRF::field() ?>
                <input type="hidden" name="seller_id" value="<?= (int)$seller['id'] ?>">
                <input type="hidden" name="seller_roll_id" id="inViewRollId">
                <input type="hidden" name="redirect_url" value="<?= url('production/sellers/view/' . $seller['id']) ?>">

                <div class="modal-body p-4">
                    <!-- Selected Roll Callout Info -->
                    <div class="p-3 bg-light rounded border mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="small text-muted d-block">SELECTED FABRIC ROLL</span>
                            <strong class="text-dark fs-6" id="inViewRollNumber">ROLL #01</strong>
                            <span class="text-muted mx-2">•</span>
                            <span class="badge bg-info-subtle text-info border" id="inViewRollColor">Color</span>
                            <span class="badge bg-warning-subtle text-warning border" id="inViewRollDesign">Design</span>
                            <div class="mt-1 d-flex gap-1 flex-wrap" id="inViewRollRates"></div>
                        </div>
                        <div class="text-end">
                            <span class="small text-muted d-block">AVAILABLE BALANCE</span>
                            <strong class="text-success fs-6" id="inViewRollBalance">0.00 kg</strong>
                            <small class="text-muted d-block" id="inViewRollPrice">₹0.00</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Defect Reason (Either Reason or Notes is required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold" id="inViewReasonLabel">
                                Damage / Defect Reason <span class="text-danger" id="inViewReasonReqStar">*</span>
                            </label>
                            <select name="damage_reason" id="inViewReasonSelect" class="form-select form-select-lg" onchange="syncInViewReasonAndNotes()">
                                <option value="">-- Choose Defect Reason --</option>
                                <option value="Fabric Stain / Oil Dirt">Fabric Stain / Oil Dirt (Permanent oil marks, dye stains)</option>
                                <option value="Weaving / Knitting Defect">Weaving / Knitting Defect (Loom fault, miss-pick, loose yarn)</option>
                                <option value="Color Bleed / Shade Variation">Color Bleed / Shade Variation (Irreversible shade variation)</option>
                                <option value="Torn / Hole / Cut Fabric">Torn / Hole / Cut Fabric (Physical cut or puncture)</option>
                                <option value="Moisture / Water Damage">Moisture / Water Damage (Wet roll, fungus, water discoloration)</option>
                                <option value="Needle / Machinery Tear">Needle / Machinery Tear (Torn selvedge or pin holes)</option>
                                <option value="Shortage / Misprint Defect">Shortage / Misprint Defect (Short meterage or print smudge)</option>
                                <option value="Other Fabric Damage">Other Fabric Damage (Specify in notes below)</option>
                            </select>
                            <small class="text-muted d-block mt-1" id="inViewReasonHelp">Select defect type or describe custom reason in notes below</small>
                        </div>

                        <!-- Damaged Quantity (Cannot exceed available stock) -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold required">Damaged Quantity</label>
                            <div class="input-group">
                                <input type="number" name="damaged_quantity" id="inViewDamagedQty" class="form-control" value="1" min="1" required oninput="onViewDamageQtyChange()">
                                <span class="input-group-text bg-light">qty</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="inViewQtyHelp">1 qty = 0.00 kg</small>
                        </div>

                        <!-- Damaged Weight (Disabled with clear visibility - Auto-calculated) -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-dark">
                                Damaged Weight <span class="badge bg-secondary-subtle text-secondary border ms-1">Auto</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="damaged_weight" id="inViewDamagedWeight" class="form-control bg-light fw-bold text-dark" placeholder="0.00" readonly tabindex="-1" style="background-color: #f1f3f5 !important; cursor: not-allowed;">
                                <span class="input-group-text bg-light">kg</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="inViewWeightHelp">Rate: ₹0.00 / kg</small>
                        </div>

                        <!-- Estimated Loss Valuation (Disabled with clear visibility - Auto-calculated) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">
                                <i class="bi bi-currency-rupee me-1"></i>Estimated Loss Valuation <span class="badge bg-danger-subtle text-danger border ms-1">Auto</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-danger-subtle text-danger fw-bold">₹</span>
                                <input type="number" step="0.01" name="loss_amount" id="inViewEstimatedLoss" class="form-control fw-bold text-danger bg-light fs-6" placeholder="0.00" readonly tabindex="-1" style="background-color: #f1f3f5 !important; cursor: not-allowed;">
                                <span class="input-group-text bg-light text-muted small" id="inViewRateBadge">₹0.00 / kg</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="inViewLossCalcHelp">Calculated: 0.00 kg × ₹0.00/kg</small>
                        </div>

                        <!-- Photo Upload (Required) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold required">
                                <i class="bi bi-camera me-1 text-danger"></i>Defect Photo / Evidence
                            </label>
                            <input type="file" name="photo" id="inViewPhotoInput" class="form-control" accept="image/*,application/pdf" required>
                            <small class="text-muted d-block mt-1">Attach photo proof for supplier claim</small>
                        </div>

                        <!-- Defect Notes (Either Reason or Notes is required) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" id="inViewNotesLabel">
                                Defect Notes &amp; Observations <span class="text-danger" id="inViewNotesReqStar" style="display:none;">*</span>
                                <small class="text-muted fw-normal" id="inViewNotesStatusText">(Optional if dropdown reason is selected)</small>
                            </label>
                            <textarea name="defect_notes" id="inViewDefectNotes" class="form-control" rows="2" placeholder="Describe defect details or reason (e.g. 10 meters affected by weaving knots...)" oninput="syncInViewReasonAndNotes()"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Log Defect &amp; Deduct Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentInViewRollMetrics = null;

function getInViewRollMetrics(r) {
    const totQty = parseInt(r.quantity) || 1;
    const totWeight = parseFloat(r.weight) || 0;
    const totPrice = parseFloat(r.price) || 0;
    const availQty = parseInt(r.available_quantity !== undefined ? r.available_quantity : r.quantity) || totQty;
    const availWeight = parseFloat(r.available_weight !== undefined ? r.available_weight : r.weight) || totWeight;
    
    const weightPerPc = totQty > 0 ? (totWeight / totQty) : totWeight;
    const ratePerKg = totWeight > 0 ? (totPrice / totWeight) : 0;
    const pricePerPc = totQty > 0 ? (totPrice / totQty) : totPrice;
    
    return {
        totQty,
        totWeight,
        totPrice,
        availQty,
        availWeight,
        weightPerPc,
        ratePerKg,
        pricePerPc
    };
}

function syncInViewReasonAndNotes() {
    const reasonSelect = document.getElementById('inViewReasonSelect');
    const notesInput = document.getElementById('inViewDefectNotes');
    const reasonStar = document.getElementById('inViewReasonReqStar');
    const notesStar = document.getElementById('inViewNotesReqStar');
    const notesStatus = document.getElementById('inViewNotesStatusText');

    if (!reasonSelect || !notesInput) return;

    const reasonVal = reasonSelect.value.trim();
    const notesVal = notesInput.value.trim();

    if (reasonVal !== '' && reasonVal !== 'Other Fabric Damage') {
        if (notesStar) notesStar.style.display = 'none';
        if (notesStatus) notesStatus.textContent = '(Optional — defect reason selected)';
        if (reasonStar) reasonStar.style.display = '';
        notesInput.removeAttribute('required');
    } else if (reasonVal === 'Other Fabric Damage') {
        if (notesStar) notesStar.style.display = '';
        if (notesStatus) notesStatus.textContent = '(Required — please specify custom defect in notes)';
        if (reasonStar) reasonStar.style.display = '';
        notesInput.setAttribute('required', 'required');
    } else if (notesVal !== '') {
        if (notesStar) notesStar.style.display = '';
        if (notesStatus) notesStatus.textContent = '(Custom defect reason entered in notes)';
        if (reasonStar) reasonStar.style.display = 'none';
        notesInput.removeAttribute('required');
    } else {
        if (notesStar) notesStar.style.display = 'none';
        if (notesStatus) notesStatus.textContent = '(Required if no dropdown reason selected)';
        if (reasonStar) reasonStar.style.display = '';
    }
}

function updateInViewCalculations(m, qty, weight) {
    if (!m) return;
    const lossVal = weight * m.ratePerKg;
    
    const lossInput = document.getElementById('inViewEstimatedLoss');
    if (lossInput) lossInput.value = lossVal.toFixed(2);

    const rateBadge = document.getElementById('inViewRateBadge');
    if (rateBadge) rateBadge.textContent = `₹${m.ratePerKg.toFixed(2)} / kg`;

    const qtyHelp = document.getElementById('inViewQtyHelp');
    if (qtyHelp) qtyHelp.textContent = `1 roll = ${m.weightPerPc.toFixed(2)} kg (Max available: ${m.availQty} qty)`;

    const weightHelp = document.getElementById('inViewWeightHelp');
    if (weightHelp) weightHelp.textContent = `Rate: ₹${m.ratePerKg.toFixed(2)} / kg`;

    const lossHelp = document.getElementById('inViewLossCalcHelp');
    if (lossHelp) lossHelp.textContent = `Calculated: ${weight.toFixed(2)} kg × ₹${m.ratePerKg.toFixed(2)}/kg = ₹${lossVal.toFixed(2)}`;
}

function onViewDamageQtyChange() {
    if (!currentInViewRollMetrics) return;
    const m = currentInViewRollMetrics;

    const qtyInput = document.getElementById('inViewDamagedQty');
    let qty = parseInt(qtyInput.value) || 0;
    const maxQty = m.availQty > 0 ? m.availQty : m.totQty;

    if (qty > maxQty) {
        alert(`Damaged Quantity (${qty} qty) cannot exceed the available roll quantity (${maxQty} qty) in supplier stock.`);
        qty = maxQty;
        qtyInput.value = maxQty;
    } else if (qty < 1 && qtyInput.value !== '') {
        qty = 1;
        qtyInput.value = 1;
    }

    const calcWeight = qty * m.weightPerPc;
    const weightInput = document.getElementById('inViewDamagedWeight');
    if (weightInput) {
        weightInput.value = calcWeight.toFixed(2);
    }
    
    updateInViewCalculations(m, qty, calcWeight);
}

function onViewDamageWeightChange() {
    if (!currentInViewRollMetrics) return;
    const m = currentInViewRollMetrics;

    const weight = parseFloat(document.getElementById('inViewDamagedWeight').value) || 0;
    const qty = parseInt(document.getElementById('inViewDamagedQty').value) || 1;
    
    updateInViewCalculations(m, qty, weight);
}

function openSupplierRollDamageModal(r) {
    currentInViewRollMetrics = getInViewRollMetrics(r);
    const m = currentInViewRollMetrics;

    document.getElementById('inViewRollId').value = r.id;
    document.getElementById('inViewRollNumber').textContent = r.roll_number || 'Roll';
    document.getElementById('inViewRollColor').textContent = r.color || 'Standard';
    document.getElementById('inViewRollDesign').textContent = r.design_name || 'Plain';
    
    document.getElementById('inViewRollRates').innerHTML = 
        `<span class="badge bg-success-subtle text-success border me-1">1 kg = ₹${m.ratePerKg.toFixed(2)}</span>` +
        `<span class="badge bg-primary-subtle text-primary border">1 roll = ${m.weightPerPc.toFixed(2)} kg (₹${m.pricePerPc.toFixed(2)})</span>`;

    if (m.availWeight > 0) {
        document.getElementById('inViewRollBalance').innerHTML = `<span class="text-success fw-bold">${m.availWeight.toFixed(2)} kg</span> <small class="text-muted">(${m.availQty} qty left / ${m.totWeight.toFixed(2)} kg total)</small>`;
    } else {
        document.getElementById('inViewRollBalance').innerHTML = `<span class="text-dark fw-bold">${m.totWeight.toFixed(2)} kg Total</span> <span class="badge bg-secondary-subtle text-secondary ms-1">In Production Program</span>`;
    }
    document.getElementById('inViewRollPrice').textContent = `Total Lot: ₹${m.totPrice.toFixed(2)}`;

    const defaultQty = 1;
    const qtyInput = document.getElementById('inViewDamagedQty');
    if (qtyInput) {
        qtyInput.value = defaultQty;
        qtyInput.max = m.availQty > 0 ? m.availQty : m.totQty;
    }

    const defaultWeight = defaultQty * m.weightPerPc;
    const weightInput = document.getElementById('inViewDamagedWeight');
    if (weightInput) {
        weightInput.value = defaultWeight.toFixed(2);
        weightInput.max = (m.availWeight > 0 ? m.availWeight : m.totWeight).toFixed(2);
    }

    updateInViewCalculations(m, defaultQty, defaultWeight);
    syncInViewReasonAndNotes();

    const modal = new bootstrap.Modal(document.getElementById('supplierRollDamageModal'));
    modal.show();
}

// Form submit validation for view modal
document.addEventListener('DOMContentLoaded', () => {
    const viewForm = document.getElementById('supplierRollDamageForm');
    if (viewForm) {
        viewForm.addEventListener('submit', function(e) {
            const reason = document.getElementById('inViewReasonSelect').value.trim();
            const notes = document.getElementById('inViewDefectNotes').value.trim();
            const qty = parseInt(document.getElementById('inViewDamagedQty').value) || 0;
            const photoInput = document.getElementById('inViewPhotoInput');

            if (currentInViewRollMetrics) {
                const m = currentInViewRollMetrics;
                const maxQty = m.availQty > 0 ? m.availQty : m.totQty;
                if (qty > maxQty) {
                    alert(`Damaged Quantity (${qty} qty) cannot exceed the available roll quantity (${maxQty} qty).`);
                    e.preventDefault();
                    return false;
                }
                if (qty < 1) {
                    alert('Damaged Quantity must be at least 1 qty.');
                    e.preventDefault();
                    return false;
                }
            }

            if (!reason && !notes) {
                alert('Please select a Damage / Defect Reason OR provide details in Defect Notes & Observations.');
                e.preventDefault();
                return false;
            }

            if (photoInput && photoInput.hasAttribute('required') && (!photoInput.files || photoInput.files.length === 0)) {
                alert('Defect Photo / Evidence is required. Please attach a clear photo of the damaged roll.');
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<!-- Interactive Modal for Fabric Roll Labels Preview -->
<div class="modal fade" id="fabricRollLabelsModal" tabindex="-1" aria-labelledby="labelModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-qr-code fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="labelModalTitle">Fabric Roll Thermal Labels Preview</h5>
                        <small class="text-white-50" id="labelModalSubtitle">Standard 250px × 130px thermal sticker format</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm" id="btnModalAddExtra" onclick="modalAddExtraLabel()" title="Generate next sequential label">
                        <i class="bi bi-plus-circle me-1"></i> + Extra Label
                    </button>
                    <button type="button" class="btn btn-sm btn-warning fw-bold shadow-sm" id="btnModalPrintSelected" onclick="printModalSelectedStickers()" style="display: none;">
                        <i class="bi bi-printer me-1"></i> Print Selected (<span id="modalSelectedCount">0</span>)
                    </button>
                    <button type="button" class="btn btn-sm btn-primary fw-bold shadow-sm" onclick="printModalStickers()">
                        <i class="bi bi-printer me-1"></i> Print All
                    </button>
                    <a href="#" target="_blank" class="btn btn-sm btn-outline-light" id="labelModalFullPageLink" title="Open dedicated print page in new tab">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Print Page
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-4 bg-light">
                <div id="labelModalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading labels...</span>
                    </div>
                    <p class="mt-2 text-muted fw-semibold">Generating QR codes & sticker previews...</p>
                </div>
                <div id="labelModalContent" class="d-none">
                    <div class="stickers-modal-grid d-flex flex-wrap gap-3 justify-content-center" id="modalStickersContainer">
                        <!-- Sticker cards rendered dynamically -->
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-2 justify-content-between">
                <div class="small text-muted" id="labelModalCountInfo">0 Stickers Loaded</div>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-sticker-card {
    width: 250px;
    min-height: 130px;
    background: #fff;
    border: 1.5px solid #0f172a;
    border-radius: 6px;
    padding: 8px 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-align: left;
}
.modal-sticker-header {
    border-bottom: 1px dashed #cbd5e1;
    padding-bottom: 3px;
    margin-bottom: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-sticker-title {
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 0.5px;
    color: #0f172a;
    text-transform: uppercase;
    line-height: 1.1;
}
.modal-sticker-supplier {
    font-size: 7.5px;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
    line-height: 1;
}
.modal-sticker-body {
    display: flex;
    align-items: center;
    gap: 8px;
}
.modal-qr-wrapper {
    width: 72px;
    flex-shrink: 0;
    text-align: center;
}
.modal-qr-box {
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
.modal-qr-box img,
.modal-qr-box canvas {
    max-width: 100% !important;
    max-height: 100% !important;
    display: block;
}
.modal-code-text {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-weight: 800;
    font-size: 10px;
    color: #0f172a;
    letter-spacing: 0.8px;
    margin-top: 2px;
    line-height: 1;
    text-align: center;
}
.modal-details-col {
    flex-grow: 1;
    overflow: hidden;
    line-height: 1.15;
}
.modal-roll-badge {
    font-size: 10.5px;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.modal-material-text {
    font-size: 7.5px;
    color: #475569;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}
.modal-spec-row {
    margin-top: 3px;
    font-size: 8px;
    color: #334155;
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.modal-spec-item {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.modal-piece-seq-badge {
    display: inline-block;
    background: #0f172a;
    color: #fff;
    padding: 1px 4px;
    border-radius: 3px;
    font-size: 7.5px;
    font-weight: 700;
    letter-spacing: 0.3px;
    margin-top: 3px;
}
</style>

<script src="<?= asset('js/qrcode.min.js') ?>"></script>
<script>if (typeof QRCode === 'undefined') document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');</script>
<script>
let currentLabelModalUrl = '';

function printModalStickers() {
    if (currentLabelModalUrl) {
        const win = window.open(currentLabelModalUrl, '_blank');
        if (win) {
            win.focus();
            setTimeout(() => { win.print(); }, 800);
        }
    }
}

function openFabricLotLabelsModal() {
    const sellerId = <?= (int)$seller['id'] ?>;
    const sellerName = <?= json_encode($seller['company'] ?: $seller['name']) ?>;
    document.getElementById('labelModalTitle').textContent = 'Fabric Lot Labels — ' + sellerName;
    document.getElementById('labelModalSubtitle').textContent = 'Thermal QR stickers for all fabric rolls in this lot';
    
    currentLabelModalUrl = '<?= url("production/sellers/" . $seller["id"] . "/labels") ?>';
    document.getElementById('labelModalFullPageLink').href = currentLabelModalUrl;

    loadAndRenderStickers('<?= url("api/sellers/" . $seller["id"] . "/roll-labels-ajax") ?>');
}

function openSingleRollLabelModal(rollId, rollNumber) {
    currentActiveRollId = rollId;
    const sellerId = <?= (int)$seller['id'] ?>;
    document.getElementById('labelModalTitle').textContent = 'Fabric Roll Label — ' + rollNumber;
    document.getElementById('labelModalSubtitle').textContent = 'Thermal QR sticker sequence for ' + rollNumber;
    
    currentLabelModalUrl = '<?= url("production/sellers/roll-labels/") ?>' + rollId;
    document.getElementById('labelModalFullPageLink').href = currentLabelModalUrl;

    loadAndRenderStickers('<?= url("api/sellers/" . $seller["id"] . "/roll-labels-ajax") ?>?roll_id=' + rollId);
}

function loadAndRenderStickers(ajaxUrl) {
    const modalEl = document.getElementById('fabricRollLabelsModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const loadingEl = document.getElementById('labelModalLoading');
    const contentEl = document.getElementById('labelModalContent');
    const container = document.getElementById('modalStickersContainer');
    const countInfo = document.getElementById('labelModalCountInfo');

    loadingEl.classList.remove('d-none');
    contentEl.classList.add('d-none');
    container.innerHTML = '';

    fetch(ajaxUrl)
        .then(res => res.json())
        .then(data => {
            loadingEl.classList.add('d-none');
            contentEl.classList.remove('d-none');

            if (!data.success || !data.stickers || data.stickers.length === 0) {
                container.innerHTML = '<div class="alert alert-warning text-center w-100">No fabric roll stickers available for this selection.</div>';
                countInfo.textContent = '0 Stickers';
                return;
            }

            countInfo.textContent = data.stickers.length + ' Label Sticker' + (data.stickers.length === 1 ? '' : 's') + ' Ready to Print';

            data.stickers.forEach((stk, idx) => {
                const card = document.createElement('div');
                card.className = 'modal-sticker-card';
                card.id = 'modal_stk_card_' + idx;
                
                const safeColor = stk.color ? `Color: ${stk.color}` : '';
                const safeDesign = stk.design_name ? `Design: ${stk.design_name}` : '';
                const safeWeight = stk.weight > 0 ? `Weight: ${Number(stk.weight).toFixed(2)} kg` : '';

                card.innerHTML = `
                    <div class="modal-sticker-header">
                        <div class="modal-sticker-title text-truncate">GRM RAW MATERIAL</div>
                        <div class="modal-sticker-supplier text-truncate" style="max-width: 120px;" title="${stk.supplier_name}">${stk.supplier_name}</div>
                    </div>
                    <div class="modal-sticker-body">
                        <div class="modal-qr-wrapper">
                            <div class="modal-qr-box" id="modal_qr_${idx}"></div>
                            <div class="modal-code-text">${stk.code}</div>
                        </div>
                        <div class="modal-details-col">
                            <div class="modal-roll-badge" title="${stk.roll_number}">${stk.roll_number}</div>
                            <div class="modal-material-text" title="${stk.material_type}">${stk.material_type}</div>
                            <div class="modal-spec-row">
                                ${safeColor ? `<div class="modal-spec-item"><strong>Color:</strong> ${stk.color}</div>` : ''}
                                ${safeDesign ? `<div class="modal-spec-item"><strong>Design:</strong> ${stk.design_name}</div>` : ''}
                                ${safeWeight ? `<div class="modal-spec-item"><strong>Weight:</strong> ${Number(stk.weight).toFixed(2)} kg</div>` : ''}
                            </div>
                            <div class="modal-piece-seq-badge">
                                PIECE ${stk.piece_index} OF ${stk.total_qty}
                            </div>
                        </div>
                    </div>
                `;

                container.appendChild(card);

                // Render QR code
                const qrBox = document.getElementById('modal_qr_' + idx);
                if (qrBox && window.QRCode) {
                    new QRCode(qrBox, {
                        text: stk.qr_payload || stk.code,
                        width: 64,
                        height: 64,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            });
        })
        .catch(err => {
            loadingEl.classList.add('d-none');
            contentEl.classList.remove('d-none');
            container.innerHTML = '<div class="alert alert-danger text-center w-100">Failed to load stickers: ' + err.message + '</div>';
        });
}
</script>


<!-- Modal: Add Extra Roll Label Prompt -->
<div class="modal fade" id="viewAddExtraLabelModal" tabindex="-1" aria-labelledby="viewAddExtraLabelTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="viewAddExtraLabelTitle">
                    <i class="bi bi-plus-circle text-success me-2"></i>Generate Extra Continuation Label
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="viewExtraRollId" value="">
                <p class="text-muted small mb-3">
                    Generate sequential extra sticker(s) for Roll <strong class="text-dark" id="viewExtraRollNumber"></strong> continuing after the last piece code.
                </p>

                <div class="p-3 rounded bg-light border mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Current Total Pieces:</span>
                        <strong class="text-dark" id="viewExtraCurrentQty">10 pcs</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Last Sequence Code:</span>
                        <span class="badge bg-secondary font-monospace" id="viewExtraLastCode">FB112660</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Number of Extra Labels to Generate</label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" onclick="adjustViewExtraCount(-1)">-</button>
                        <input type="number" class="form-control text-center fw-bold" id="viewExtraCountInput" value="1" min="1" max="50">
                        <button class="btn btn-outline-secondary" type="button" onclick="adjustViewExtraCount(1)">+</button>
                    </div>
                    <small class="text-muted">Typically 1 extra label to replace damaged sticker or extend roll.</small>
                </div>

                <div class="alert alert-info py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i>The system will assign the exact continuation sequence code and update database piece counts.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success fw-bold" id="btnConfirmViewExtra" onclick="submitViewAddExtraLabel()">
                    <span id="btnConfirmViewExtraSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                    <i class="bi bi-check-circle me-1" id="btnConfirmViewExtraIcon"></i> Generate & Save Label
                </button>
            </div>
        </div>
    </div>
</div>
<script>
let currentActiveRollId = 0;
let modalStickersData = [];

function promptAddExtraRollLabel(rollId, rollNumber, endCode, qty) {
    currentActiveRollId = rollId;
    document.getElementById('viewExtraRollId').value = rollId;
    document.getElementById('viewExtraRollNumber').textContent = rollNumber;
    document.getElementById('viewExtraCurrentQty').textContent = qty + ' pcs';
    document.getElementById('viewExtraLastCode').textContent = endCode || 'N/A';
    document.getElementById('viewExtraCountInput').value = 1;

    const modalEl = document.getElementById('viewAddExtraLabelModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function adjustViewExtraCount(delta) {
    const input = document.getElementById('viewExtraCountInput');
    let val = parseInt(input.value, 10) || 1;
    val = Math.max(1, Math.min(50, val + delta));
    input.value = val;
}

function submitViewAddExtraLabel() {
    const rollId = document.getElementById('viewExtraRollId').value;
    const count = parseInt(document.getElementById('viewExtraCountInput').value, 10) || 1;

    const btn = document.getElementById('btnConfirmViewExtra');
    const spinner = document.getElementById('btnConfirmViewExtraSpinner');
    const icon = document.getElementById('btnConfirmViewExtraIcon');

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');

    const formData = new FormData();
    formData.append('_csrf_token', '<?= CSRF::token() ?>');
    formData.append('count', count);
    formData.append('ajax', '1');

    fetch('<?= url("production/sellers/add-extra-label/") ?>' + rollId, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '<?= CSRF::token() ?>',
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

        const modalEl = document.getElementById('viewAddExtraLabelModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        alert(data.message || 'Extra continuation label generated successfully!');
        window.location.reload();
    })
    .catch(err => {
        btn.disabled = false;
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
        alert('Request failed: ' + err.message);
    });
}

function modalAddExtraLabel() {
    if (currentActiveRollId > 0) {
        promptAddExtraRollLabel(currentActiveRollId, 'Roll', '', 0);
    } else {
        // Lot mode: default to first roll or prompt
        const firstRollBtn = document.querySelector('button[onclick*="promptAddExtraRollLabel"]');
        if (firstRollBtn) {
            firstRollBtn.click();
        } else {
            alert('Please select a specific roll to generate an extra label.');
        }
    }
}

function updateModalSelectedCount() {
    const checked = document.querySelectorAll('.modal-stk-chk:checked');
    const count = checked.length;
    const btn = document.getElementById('btnModalPrintSelected');
    const countSpan = document.getElementById('modalSelectedCount');

    if (countSpan) countSpan.textContent = count;
    if (btn) {
        btn.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

function printModalSingleSticker(code) {
    if (currentLabelModalUrl) {
        const url = currentLabelModalUrl + (currentLabelModalUrl.includes('?') ? '&' : '?') + 'search=' + encodeURIComponent(code);
        const win = window.open(url, '_blank');
        if (win) {
            win.focus();
        }
    }
}

function printModalSelectedStickers() {
    if (currentLabelModalUrl) {
        window.open(currentLabelModalUrl, '_blank');
    }
}
</script>
