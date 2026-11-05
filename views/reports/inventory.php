<?php
// views/reports/inventory.php
// Real-Time Inventory Valuation, Stock Health & Location Audit Report
$reportType = 'inventory';
$totals = $inventoryData['totals'] ?? [];
$potentialProfit = ($totals['selling_value'] ?? 0) - ($totals['cost_value'] ?? 0);
$profitMarginPct = ($totals['selling_value'] ?? 0) > 0 ? round(($potentialProfit / $totals['selling_value']) * 100, 1) : 0;
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold text-dark">
            <i class="bi bi-boxes text-primary me-2"></i>Inventory Valuation &amp; Stock Health Reports
        </h4>
        <div class="text-muted small mt-1">Real-time stock valuation by cost and retail price across central warehouses and retail stores</div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. INVENTORY VALUATION KPI SCOREBOARD -->
<!-- ========================================================================= -->
<div class="row g-3 mb-4">
    <!-- Total In-Stock Quantity -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Stock on Hand</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totals['total_qty'] ?? 0) ?> pcs</div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-box-seam me-1"></i><?= count($inventoryData['items'] ?? []) ?> Unique SKU Entries
                    </div>
                </div>
                <div class="bg-primary text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Capital / Cost Valuation -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 4px solid #64748b !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-secondary" style="font-size: 0.72rem; letter-spacing: 0.5px;">Asset Cost Valuation (COGS)</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($totals['cost_value'] ?? 0, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-cash me-1"></i>Direct Capital Tied Up
                    </div>
                </div>
                <div class="bg-secondary text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-coin fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Retail / Selling Valuation -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 0.5px;">Retail Market Valuation</div>
                    <div class="fs-4 fw-bold text-success mt-1 font-monospace">₹<?= number_format($totals['selling_value'] ?? 0, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-tag me-1"></i>Potential Retail Revenue
                    </div>
                </div>
                <div class="bg-success text-white p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Potential Profit Margin -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #d97706 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase small fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 0.5px;">Potential Gross Profit</div>
                    <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹<?= number_format($potentialProfit, 2) ?></div>
                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                        <span class="badge bg-warning text-dark me-1"><?= $profitMarginPct ?>%</span> Estimated Unrealized Margin
                    </div>
                </div>
                <div class="bg-warning text-dark p-2.5 rounded-3 shadow-xs">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. INVENTORY FILTER BAR -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('reports/inventory') ?>" class="row g-2 align-items-end">
            <!-- Location Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Stock Location</label>
                <select name="location_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL" <?= $locationType === 'ALL' ? 'selected' : '' ?>>All Locations (Warehouse &amp; Stores)</option>
                    <option value="WAREHOUSE" <?= $locationType === 'WAREHOUSE' ? 'selected' : '' ?>>Central Warehouses Only</option>
                    <option value="SHOP" <?= $locationType === 'SHOP' ? 'selected' : '' ?>>Retail Stores Only</option>
                </select>
            </div>

            <!-- Stock Condition State -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Condition State</label>
                <select name="stock_state" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="ALL" <?= $stockState === 'ALL' ? 'selected' : '' ?>>All Conditions</option>
                    <option value="AVAILABLE" <?= $stockState === 'AVAILABLE' ? 'selected' : '' ?>>Available Active Stock</option>
                    <option value="DAMAGED" <?= $stockState === 'DAMAGED' ? 'selected' : '' ?>>Damaged / Quarantine Stock</option>
                    <option value="RESERVED" <?= $stockState === 'RESERVED' ? 'selected' : '' ?>>Reserved Stock</option>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Product Category</label>
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-md-3 d-flex gap-2 align-items-center">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1 fw-semibold">
                    <i class="bi bi-funnel-fill me-1"></i>Filter
                </button>
                <a href="<?= url('reports/export/inventory') ?>?location_type=<?= $locationType ?>&stock_state=<?= $stockState ?>&category_id=<?= $categoryId ?>" 
                   class="btn btn-sm btn-outline-success fw-semibold">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export
                </a>
                <a href="<?= url('reports/print/inventory') ?>?location_type=<?= $locationType ?>&stock_state=<?= $stockState ?>&category_id=<?= $categoryId ?>" 
                   target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold">
                    <i class="bi bi-printer me-1"></i>Print
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. DETAILED INVENTORY VALUATION REGISTER -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-0 border-bottom d-flex align-items-center justify-content-between">
        <div>
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Stock Valuation &amp; Location Audit
            </h5>
            <div class="small text-muted">Real-time inventory balances and valuation across facilities</div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">SKU / Barcode</th>
                        <th>Product Description</th>
                        <th>Category</th>
                        <th>Attributes</th>
                        <th>Facility Location</th>
                        <th>Condition</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Unit Selling</th>
                        <th class="text-end">Total Cost Val</th>
                        <th class="pe-4 text-end">Total Selling Val</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventoryData['items'])): ?>
                        <tr><td colspan="11" class="text-center py-5 text-muted">No inventory records found matching your filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventoryData['items'] as $item): ?>
                            <tr>
                                <td class="ps-4 fw-semibold font-monospace text-primary"><?= e($item['sku']) ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($item['product_name']) ?></div>
                                    <div class="small text-muted font-monospace"><?= e($item['product_code']) ?></div>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= e($item['category_name']) ?></span></td>
                                <td class="small text-muted">
                                    <?= e($item['size_name'] ?: '-') ?> &bull; <?= e($item['color_name'] ?: '-') ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi <?= $item['location_type'] === 'WAREHOUSE' ? 'bi-building' : 'bi-shop' ?> me-1"></i>
                                        <?= e($item['location_name'] ?? $item['location_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $item['stock_state'] === 'AVAILABLE' ? 'bg-success-subtle text-success' : ($item['stock_state'] === 'DAMAGED' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') ?>">
                                        <?= e($item['stock_state']) ?>
                                    </span>
                                </td>
                                <td class="text-end font-monospace fw-bold text-dark"><?= number_format($item['quantity']) ?></td>
                                <td class="text-end font-monospace text-muted">₹<?= number_format($item['purchase_price'] ?: $item['production_cost'], 2) ?></td>
                                <td class="text-end font-monospace text-dark">₹<?= number_format($item['selling_price'] ?: $item['mrp'], 2) ?></td>
                                <td class="text-end font-monospace text-secondary">₹<?= number_format($item['cost_val'], 2) ?></td>
                                <td class="pe-4 text-end font-monospace fw-bold text-success">₹<?= number_format($item['sell_val'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($inventoryData['items'])): ?>
                <tfoot class="table-light fw-bold border-top">
                    <tr>
                        <td colspan="6" class="ps-4 text-uppercase">Grand Totals (<?= count($inventoryData['items']) ?> SKUs)</td>
                        <td class="text-end font-monospace"><?= number_format($totals['total_qty'] ?? 0) ?> pcs</td>
                        <td colspan="2"></td>
                        <td class="text-end font-monospace text-secondary">₹<?= number_format($totals['cost_value'] ?? 0, 2) ?></td>
                        <td class="pe-4 text-end font-monospace text-success">₹<?= number_format($totals['selling_value'] ?? 0, 2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
