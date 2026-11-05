<?php
// views/shop/customers/view.php — Dedicated 360-Degree Customer Profile & Purchase Timeline
$cust = $customer;
$words = explode(' ', trim($cust['name']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= !empty($w) ? strtoupper($w[0]) : '';
}
if (empty($initials)) $initials = 'C';

$tierLabel = 'New / Walk-in';
$tierBadge = 'bg-secondary-subtle text-secondary border';
$tierIcon = 'bi-person';
if ($metrics['total_spend'] >= 10000) {
    $tierLabel = 'VIP Customer';
    $tierBadge = 'bg-warning-subtle text-dark border border-warning';
    $tierIcon = 'bi-star-fill text-warning';
} elseif ($metrics['total_spend'] >= 3000) {
    $tierLabel = 'Regular Customer';
    $tierBadge = 'bg-primary-subtle text-primary border border-primary';
    $tierIcon = 'bi-person-check-fill text-primary';
}
?>
<div class="container-fluid py-4">
    <!-- Top Breadcrumb & Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div class="d-flex align-items-center">
            <a href="<?= url('shop/customers') ?>" class="btn btn-sm btn-light border rounded-circle me-3" title="Back to Customers">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= url('shop/dashboard') ?>" class="text-decoration-none"><i class="bi bi-shop me-1"></i>Shop</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('shop/customers') ?>" class="text-decoration-none">Customers</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= e($cust['name']) ?></li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold mb-0 text-dark"><?= e($cust['name']) ?></h3>
                    <span class="badge <?= $tierBadge ?> rounded-pill px-3 py-1">
                        <i class="bi <?= $tierIcon ?> me-1"></i><?= $tierLabel ?>
                    </span>
                    <?php if ($cust['is_active']): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">Active</span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (hasPermission('customers', 'edit')): ?>
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                <i class="bi bi-pencil-square me-1"></i> Edit Profile
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Overview Hero Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden border-top border-4 border-primary">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-md-auto text-center text-md-start">
                    <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-inline-flex align-items-center justify-content-center border border-primary-subtle shadow-sm" 
                         style="width: 80px; height: 80px; font-size: 28px;">
                        <?= e($initials) ?>
                    </div>
                </div>
                <div class="col-12 col-md">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px;">Mobile Phone</span>
                            <div class="fw-bold font-monospace text-dark fs-6">
                                <?php if (!empty($cust['phone'])): ?>
                                    <a href="tel:<?= e($cust['phone']) ?>" class="text-decoration-none text-dark">
                                        <i class="bi bi-telephone text-primary me-1"></i><?= e($cust['phone']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px;">Email Address</span>
                            <div class="fw-bold text-dark fs-6 text-truncate" title="<?= e($cust['email'] ?? '') ?>">
                                <i class="bi bi-envelope text-primary me-1"></i><?= e(!empty($cust['email']) ? $cust['email'] : 'Not provided') ?>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px;">Location</span>
                            <div class="fw-bold text-dark fs-6">
                                <i class="bi bi-geo-alt text-primary me-1"></i><?php
                                    $locParts = array_filter([!empty($cust['city']) ? $cust['city'] : '', !empty($cust['state']) ? $cust['state'] : '']);
                                    if (!empty($locParts)) {
                                        echo e(implode(', ', $locParts));
                                    } elseif (!empty($cust['address']) && strtolower(trim($cust['address'])) !== 'test') {
                                        echo e($cust['address'] . ', TN');
                                    } else {
                                        echo 'Tamil Nadu, IN';
                                    }
                                ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lifetime Summary Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Lifetime Spend -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px;">Lifetime Spend</span>
                    <h3 class="fw-bold mb-1 text-success">₹<?= number_format($metrics['total_spend'], 2) ?></h3>
                    <small class="text-muted">
                        <?php if (!empty($metrics['total_refunds']) && $metrics['total_refunds'] > 0): ?>
                            <span class="text-secondary fw-semibold">Net: ₹<?= number_format($metrics['net_spend'], 2) ?></span> • Gross billed
                        <?php else: ?>
                            Total purchases made
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- 2. Total Invoices -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px;">Total Invoices</span>
                    <h3 class="fw-bold mb-1 text-primary"><?= number_format($metrics['total_orders']) ?></h3>
                    <small class="text-muted">Completed billings</small>
                </div>
            </div>
        </div>

        <!-- 3. Total Pieces Bought -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px;">Garments Purchased</span>
                    <h3 class="fw-bold mb-1 text-dark"><?= number_format($metrics['total_items_bought']) ?> <span class="fs-6 fw-normal text-muted">pcs</span></h3>
                    <small class="text-muted">
                        <?php if (!empty($metrics['total_items_returned']) && $metrics['total_items_returned'] > 0): ?>
                            <span class="text-warning fw-semibold"><?= (int)$metrics['net_items_kept'] ?> pcs kept</span> (<?= (int)$metrics['total_items_returned'] ?> returned/exchanged)
                        <?php else: ?>
                            Items across all bills
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- 4. Average Order Value -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size: 11px;">Avg Spend / Order</span>
                    <h3 class="fw-bold mb-1 text-info">₹<?= number_format($metrics['avg_order_value'], 2) ?></h3>
                    <small class="text-muted">Average bill value</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed 360 Activity Tabs Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="customerTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-3 px-4 border-0 border-bottom border-2 text-dark" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab">
                        <i class="bi bi-receipt me-2 text-primary"></i>Invoices &amp; Billing History (<?= count($sales) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 px-4 border-0 border-bottom border-2 text-dark" id="items-tab" data-bs-toggle="tab" data-bs-target="#items" type="button" role="tab">
                        <i class="bi bi-bag-check me-2 text-success"></i>Purchased Garments (<?= count($items_bought) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 px-4 border-0 border-bottom border-2 text-dark" id="returns-tab" data-bs-toggle="tab" data-bs-target="#returns" type="button" role="tab">
                        <i class="bi bi-arrow-left-right me-2 text-warning"></i>Returns &amp; Exchanges (<?= count($returns) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 px-4 border-0 border-bottom border-2 text-dark" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                        <i class="bi bi-person-lines-fill me-2 text-info"></i>Full Profile Details
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="customerTabContent">
                <!-- TAB 1: Invoices History -->
                <div class="tab-pane fade show active" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase" style="font-size: 11px;">
                                <tr>
                                    <th class="ps-3">Date &amp; Time</th>
                                    <th>Invoice #</th>
                                    <th class="text-center">Items</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-end">GST</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Net Total</th>
                                    <th>Pay Mode</th>
                                    <th>Staff</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sales)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <i class="bi bi-receipt fs-1 d-block mb-2 opacity-50"></i>
                                            No invoice records found for this customer yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales as $s): ?>
                                        <tr>
                                            <td class="ps-3 small">
                                                <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($s['created_at'])) ?></div>
                                                <small class="text-muted"><?= date('h:i A', strtotime($s['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <a href="javascript:void(0)" onclick="openExactBillModal(<?= $s['id'] ?>)" class="fw-bold font-monospace text-primary text-decoration-none d-inline-flex align-items-center gap-1" title="Click to view exact bill">
                                                    <i class="bi bi-receipt"></i> <?= e($s['invoice_number']) ?>
                                                </a>
                                                <?php if (!empty($s['has_return'])): ?>
                                                    <div class="mt-1">
                                                        <span class="badge bg-warning-subtle text-dark border border-warning font-monospace" style="font-size: 10px;">
                                                            <i class="bi bi-arrow-left-right me-1"></i><?= e($s['return_type'] ?? 'EXCHANGE') ?>: <?= e($s['return_number']) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border font-monospace">
                                                    <?= (int)($s['total_qty'] ?? $s['items_count'] ?? 1) ?> pcs
                                                </span>
                                            </td>
                                            <td class="text-end small">₹<?= number_format((float)$s['subtotal'], 2) ?></td>
                                            <td class="text-end small text-success">+₹<?= number_format((float)($s['cgst_amount'] + $s['sgst_amount'] + $s['igst_amount']), 2) ?></td>
                                            <td class="text-end small text-danger">
                                                <?= (float)$s['discount_amount'] > 0 ? '-₹' . number_format((float)$s['discount_amount'], 2) : '—' ?>
                                            </td>
                                            <td class="text-end fw-bold text-dark fs-6">
                                                ₹<?= number_format((float)$s['total_amount'], 2) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-1" style="font-size: 11px;">
                                                    <?= e($s['payment_mode']) ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted">
                                                <?= e($s['staff_name'] ?? 'Cashier') ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" onclick="openExactBillModal(<?= $s['id'] ?>)" title="View Exact Bill">
                                                        <i class="bi bi-eye-fill me-1"></i> View Bill
                                                    </button>
                                                    <a href="<?= url('shop/pos/invoice/' . $s['id']) ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2 text-secondary" title="Print POS Thermal Receipt">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: Purchased Garments Log -->
                <div class="tab-pane fade" id="items" role="tabpanel" aria-labelledby="items-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase" style="font-size: 11px;">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Garment Product Details</th>
                                    <th>SKU &amp; Serial Tag</th>
                                    <th>Size / Color</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Invoice Ref</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items_bought)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="bi bi-bag-check fs-1 d-block mb-2 opacity-50"></i>
                                            No item purchase history recorded yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items_bought as $it): ?>
                                        <tr>
                                            <td class="ps-3 small text-muted">
                                                <?= date('d M Y', strtotime($it['sale_date'])) ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= e($it['product_name']) ?></div>
                                                <small class="text-muted"><?= e($it['product_code']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border font-monospace"><?= e($it['sku']) ?></span>
                                                <?php if (!empty($it['scanned_qr_codes'])): ?>
                                                    <div class="badge bg-dark font-monospace text-warning mt-1 d-inline-block" style="font-size: 10px;">
                                                        <i class="bi bi-qr-code me-1"></i><?= e($it['scanned_qr_codes']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <span class="badge bg-light text-dark border"><?= e(!empty($it['size_name']) ? $it['size_name'] : 'Free') ?></span>
                                                <span class="badge bg-light text-dark border"><?= e(!empty($it['color_name']) ? $it['color_name'] : 'Standard') ?></span>
                                            </td>
                                            <td class="text-center fw-bold">
                                                <?= (int)$it['quantity'] ?>
                                            </td>
                                            <td class="text-end small">₹<?= number_format((float)$it['unit_price'], 2) ?></td>
                                            <td class="text-end fw-bold text-primary">₹<?= number_format((float)($it['total_amount'] ?? ($it['unit_price'] * $it['quantity'])), 2) ?></td>
                                            <td>
                                                <?php if (!empty($it['is_returned'])): ?>
                                                    <span class="badge bg-warning-subtle text-dark border border-warning" title="Returned / Exchanged: <?= e($it['return_number'] ?? '') ?>">
                                                        <i class="bi bi-arrow-left-right me-1"></i><?= e($it['return_type'] ?? 'Returned') ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="bi bi-check-circle me-1"></i>Active / Kept
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <a href="javascript:void(0)" onclick="openExactBillModal(<?= $it['sale_id'] ?>)" class="font-monospace text-primary fw-bold text-decoration-none small d-inline-flex align-items-center gap-1" title="View Exact Bill">
                                                    <i class="bi bi-receipt"></i> <?= e($it['invoice_number']) ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: Returns & Exchanges -->
                <div class="tab-pane fade" id="returns" role="tabpanel" aria-labelledby="returns-tab">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase" style="font-size: 11px;">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Return Ref #</th>
                                    <th>Invoice #</th>
                                    <th>Return Reason</th>
                                    <th class="text-end">Refund / Credit</th>
                                    <th>Type</th>
                                    <th>Processed By</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($returns)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-shield-check fs-1 d-block mb-2 text-success opacity-50"></i>
                                            Zero returns or exchanges recorded for this customer.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($returns as $ret): ?>
                                        <tr>
                                            <td class="ps-3 small text-muted">
                                                <?= date('d M Y', strtotime($ret['created_at'])) ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold font-monospace text-danger"><?= e($ret['return_number']) ?></span>
                                            </td>
                                            <td>
                                                <a href="javascript:void(0)" onclick="openExactBillModal(<?= $ret['sale_id'] ?>)" class="font-monospace text-primary fw-bold text-decoration-none small d-inline-flex align-items-center gap-1" title="View Exact Bill">
                                                    <i class="bi bi-receipt"></i> <?= e($ret['invoice_number']) ?>
                                                </a>
                                            </td>
                                            <td class="small text-muted"><?= e($ret['reason'] ?? 'N/A') ?></td>
                                            <td class="text-end fw-bold text-danger">
                                                ₹<?= number_format((float)$ret['refund_amount'], 2) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1" style="font-size: 11px;">
                                                    <?= e($ret['return_type'] ?? 'RETURN') ?>
                                                </span>
                                            </td>
                                            <td class="small text-muted"><?= e($ret['processed_by_name'] ?? 'Staff') ?></td>
                                            <td class="text-end pe-3">
                                                <a href="<?= url('shop/returns/invoice/' . $ret['id']) ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill px-3 text-danger">
                                                    <i class="bi bi-receipt me-1"></i> Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: Full Profile Details -->
                <div class="tab-pane fade p-4" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-vcard text-primary me-2"></i>Contact Information</h6>
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th class="bg-light text-muted w-35 small">Customer ID</th>
                                        <td class="font-monospace fw-bold">#<?= $cust['id'] ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">Full Name</th>
                                        <td class="fw-bold text-dark"><?= e($cust['name']) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">Mobile Phone</th>
                                        <td class="font-monospace fw-bold text-primary"><?= e($cust['phone'] ?? 'N/A') ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">Email Address</th>
                                        <td><?= e($cust['email'] ?? 'Not provided') ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">GSTIN Number</th>
                                        <td class="font-monospace"><?= e($cust['gst_number'] ?? 'Not registered') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-12 col-md-6">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-geo-alt text-primary me-2"></i>Address &amp; Metadata</h6>
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th class="bg-light text-muted w-35 small">Street Address</th>
                                        <td><?= nl2br(e($cust['address'] ?? 'Not provided')) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">City / Town</th>
                                        <td class="fw-semibold text-dark"><?= e(!empty($cust['city']) ? $cust['city'] : (!empty($cust['state']) ? $cust['state'] : 'Karur')) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">State</th>
                                        <td><?= e(!empty($cust['state']) ? $cust['state'] : 'Tamil Nadu') ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">Member Since</th>
                                        <td><?= date('d M Y, h:i A', strtotime($cust['created_at'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">Last Profile Update</th>
                                        <td><?= !empty($cust['updated_at']) ? date('d M Y, h:i A', strtotime($cust['updated_at'])) : '—' ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted small">Account Status</th>
                                        <td>
                                            <?php if ($cust['is_active']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active for POS Billing</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: Edit Customer Profile
     ========================================== -->
<?php if (hasPermission('customers', 'edit')): ?>
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="<?= url('shop/customers/update/' . $cust['id']) ?>">
                <div class="modal-header bg-light border-0 py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold text-dark" id="editModalTitle">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Customer Profile
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-1">Customer Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" value="<?= e($cust['name']) ?>" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">Mobile Phone <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control form-control-sm" value="<?= e($cust['phone']) ?>" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="<?= e($cust['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark mb-1">Street Address</label>
                            <input type="text" name="address" class="form-control form-control-sm" value="<?= e($cust['address'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">City / Town</label>
                            <input type="text" name="city" class="form-control form-control-sm" value="<?= e($cust['city'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">State</label>
                            <input type="text" name="state" class="form-control form-control-sm" value="<?= e($cust['state'] ?? 'Tamil Nadu') ?>">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-bold text-dark mb-1">GSTIN Number</label>
                            <input type="text" name="gst_number" class="form-control form-control-sm font-monospace text-uppercase" value="<?= e($cust['gst_number'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="is_active" id="editCustStatus" value="1" <?= $cust['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-semibold text-dark" for="editCustStatus">Account is Active for POS Billing</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==========================================
     MODAL: Exact Tax Invoice & Thermal Bill Viewer
     ========================================== -->
<div class="modal fade" id="exactBillModal" tabindex="-1" aria-labelledby="exactBillModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-receipt-cutoff fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2 flex-wrap" id="exactBillModalTitle">
                            Tax Invoice &amp; Exact POS Bill: 
                            <span id="modalBillInvNum" class="font-monospace text-primary fw-bold"></span>
                        </h5>
                        <small class="text-muted" id="modalBillSubMeta">Completed Purchase Record</small>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border" role="group">
                            <button type="button" class="btn btn-sm rounded-pill active fw-bold px-3" id="btnModeThermal" onclick="switchBillModalView('thermal')">
                                <i class="bi bi-receipt me-1"></i> Thermal POS Slip
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill fw-bold px-3 text-muted" id="btnModeDetailed" onclick="switchBillModalView('detailed')">
                                <i class="bi bi-grid-3x3-gap me-1"></i> Itemized Audit View
                            </button>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-0 bg-light">
                <!-- Loading Spinner -->
                <div id="billModalLoading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="text-muted small fw-semibold">Loading exact bill details...</div>
                </div>

                <!-- Main Bill Content -->
                <div id="billModalContent" class="p-4">
                    <!-- VIEW 1: THERMAL POS RECEIPT SLIP -->
                    <div id="billThermalView" class="d-flex justify-content-center">
                        <div class="thermal-receipt-card shadow-sm">
                            <!-- GRM Stylized Logo -->
                            <div class="text-center mb-2">
                                <div class="thermal-logo-box">
                                    <svg viewBox="0 0 150 75" width="120" height="55" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <pattern id="stippleThermal" width="3" height="3" patternUnits="userSpaceOnUse">
                                                <circle cx="1.5" cy="1.5" r="0.6" fill="#000" />
                                            </pattern>
                                        </defs>
                                        <rect width="150" height="75" fill="url(#stippleThermal)" opacity="0.3" />
                                        <rect width="150" height="75" fill="none" stroke="#000" stroke-width="1.5" />
                                        <text x="75" y="52" font-family="'Times New Roman', Georgia, serif" font-size="50" font-weight="900" text-anchor="middle" fill="#000" letter-spacing="-2">GRM</text>
                                    </svg>
                                </div>
                            </div>

                            <!-- Store Header -->
                            <div class="thermal-title" id="thShopName">GRM SHOP</div>
                            <div class="thermal-address" id="thShopAddress">
                                SULUR KMCH HOSPITAL<br>
                                OPPOSITE, TRICHY<br>
                                ROAD, SULUR, COIMBATORE
                            </div>
                            <div class="thermal-contact">
                                Phone No: <span id="thShopPhone">8807144175, 8610612625</span><br>
                                <span id="thShopEmailLine" style="display: none;">EMail: <span id="thShopEmail"></span></span>
                            </div>
                            <div class="thermal-gst">
                                GST NO: <span id="thShopGst">33BFQPQ7507C1ZX</span>
                            </div>

                            <div class="thermal-divider-dashed"></div>

                            <!-- Bill Metadata -->
                            <div class="thermal-meta-row">
                                <span>Bill No: <strong id="thBillNumber">--</strong></span>
                                <span>Date: <strong id="thBillDate">--</strong></span>
                            </div>
                            <div class="thermal-meta-row">
                                <span>BID: <strong id="thBillId">--</strong></span>
                                <span>Staff: <strong id="thStaffName">Cashier</strong></span>
                            </div>

                            <div class="thermal-divider-dashed"></div>

                            <!-- Customer & Payment Mode -->
                            <div class="thermal-meta-row">
                                <span>To: <strong id="thCustomerName">WALK-IN</strong></span>
                                <span>Pay: <strong id="thPayMode" class="text-uppercase">CASH</strong></span>
                            </div>
                            <div class="thermal-meta-row" id="thCustomerPhoneRow">
                                <span>Ph: <strong id="thCustomerPhone">--</strong></span>
                                <span id="thCustGstSpan"></span>
                            </div>
                            <div class="thermal-meta-row small" id="thSplitNotesRow" style="display: none; font-size: 10px; font-weight: 700;">
                                <span id="thSplitNotesText"></span>
                            </div>

                            <div class="thermal-divider-dashed"></div>

                            <!-- Items Table -->
                            <table class="thermal-items-table">
                                <thead>
                                    <tr>
                                        <th style="text-align: left; width: 42%;">Item</th>
                                        <th style="text-align: center; width: 10%;">Qty</th>
                                        <th style="text-align: right; width: 16%;">Price</th>
                                        <th style="text-align: right; width: 16%;">Disc</th>
                                        <th style="text-align: right; width: 16%;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="thItemsBody">
                                    <!-- Populated via JavaScript -->
                                </tbody>
                            </table>

                            <div class="thermal-divider-dashed"></div>

                            <!-- Financial Totals -->
                            <div class="thermal-totals-row">
                                <span>Sub Total (Taxable):</span>
                                <span id="thSubtotal">₹0.00</span>
                            </div>
                            <div class="thermal-totals-row">
                                <span>Total GST:</span>
                                <span id="thTotalGst">+₹0.00</span>
                            </div>
                            <div class="thermal-totals-row" id="thDiscRow">
                                <span>Gross Discount:</span>
                                <span id="thDiscount" class="text-danger">-₹0.00</span>
                            </div>
                            <div class="thermal-totals-row" id="thRoundOffRow" style="display: none;">
                                <span>Round Off:</span>
                                <span id="thRoundOff">₹0.00</span>
                            </div>

                            <div class="thermal-divider-solid"></div>

                            <!-- Net Total -->
                            <div class="thermal-net-row">
                                <span>NET AMOUNT:</span>
                                <span id="thNetTotal">₹0.00</span>
                            </div>

                            <div class="thermal-divider-double"></div>

                            <!-- Terms & Footer -->
                            <div class="thermal-terms">
                                <strong>Terms &amp; Conditions:</strong><br>
                                1. Goods once sold can only be exchanged within 7 days.<br>
                                2. Bill &amp; Price Tag mandatory for exchange.<br>
                                3. No cash refund under any circumstances.
                            </div>
                            <div class="thermal-visit-again">
                                ★ THANK YOU VISIT AGAIN ★
                            </div>
                        </div>
                    </div>

                    <!-- VIEW 2: ITEMIZED AUDIT & TAX BREAKDOWN -->
                    <div id="billDetailedView" style="display: none;">
                        <!-- Customer & Bill Overview Card -->
                        <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                            <div class="card-body p-3">
                                <div class="row g-3 align-items-center">
                                    <div class="col-sm-6 col-md-3">
                                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Invoice Number</small>
                                        <div class="fw-bold font-monospace text-primary fs-6" id="detInvNum">--</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Billing Date &amp; Time</small>
                                        <div class="fw-bold text-dark fs-6" id="detSaleDate">--</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Billing Cashier</small>
                                        <div class="fw-bold text-dark fs-6" id="detCashier">--</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Payment Mode</small>
                                        <div>
                                            <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-1 fs-7" id="detPayMode">--</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Garment Items Table -->
                        <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white overflow-hidden">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bag-check-fill text-success me-2"></i>Billed Garment Items &amp; Serial Tags</h6>
                                <span class="badge bg-light text-dark border" id="detItemCountBadge">0 items</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-muted small text-uppercase" style="font-size: 11px;">
                                        <tr>
                                            <th class="ps-3">#</th>
                                            <th>Product Details</th>
                                            <th>SKU &amp; Piece Serial Tags</th>
                                            <th>Size / Color</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">MRP / Rate</th>
                                            <th class="text-end">Discount</th>
                                            <th class="text-end">Tax (GST)</th>
                                            <th class="text-end pe-3">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detItemsBody">
                                        <!-- Populated via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 4 Financial Metric Cards -->
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Subtotal (Taxable)</small>
                                    <h5 class="fw-bold text-dark mt-1 mb-0" id="detSubtotal">₹0.00</h5>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Total GST</small>
                                    <h5 class="fw-bold text-success mt-1 mb-0" id="detGst">+₹0.00</h5>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-white rounded-4 border shadow-sm text-center">
                                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 11px;">Discount</small>
                                    <h5 class="fw-bold text-danger mt-1 mb-0" id="detDiscount">-₹0.00</h5>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-3 bg-primary-subtle rounded-4 border border-primary-subtle shadow-sm text-center">
                                    <small class="text-primary text-uppercase fw-bold" style="font-size: 11px;">Net Paid Amount</small>
                                    <h5 class="fw-bold text-primary mt-1 mb-0" id="detNetTotal">₹0.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-none" onclick="copyCurrentBillNo()">
                        <i class="bi bi-clipboard me-1"></i> Copy Invoice #
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a id="billModalFullPageLink" href="#" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Full Page Receipt
                    </a>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="printThermalFromModal()">
                        <i class="bi bi-printer-fill me-1"></i> Print Bill
                    </button>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 shadow-none" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Embedded Thermal Slip Styles for Modal -->
<style>
.thermal-receipt-card {
    width: 330px;
    max-width: 100%;
    background: #fff;
    padding: 20px 14px;
    color: #000;
    font-family: 'Courier New', Courier, Consolas, Monaco, monospace;
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.35;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}
.thermal-logo-box {
    display: inline-block;
    border: 1.5px solid #000;
    padding: 4px 10px;
    background: #fff;
}
.thermal-title {
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}
.thermal-address {
    text-align: center;
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    line-height: 1.3;
    margin-top: 2px;
}
.thermal-contact, .thermal-gst {
    text-align: center;
    font-size: 10.5px;
    font-weight: 600;
    margin-top: 2px;
}
.thermal-gst {
    font-weight: 800;
    font-size: 11px;
}
.thermal-divider-dashed {
    border-top: 1px dashed #000;
    margin: 6px 0;
}
.thermal-divider-solid {
    border-top: 1px solid #000;
    margin: 6px 0;
}
.thermal-divider-double {
    border-top: 2px solid #000;
    margin: 7px 0;
}
.thermal-meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.35;
}
.thermal-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    font-weight: 600;
}
.thermal-items-table th {
    font-weight: 800;
    padding: 3px 0;
    border-bottom: 1px dashed #000;
}
.thermal-items-table td {
    padding: 2px 0;
    vertical-align: top;
}
.thermal-item-name {
    font-weight: 700;
    padding-top: 3px;
    word-break: break-word;
}
.thermal-item-tag {
    font-size: 9.5px;
    font-weight: 600;
    display: block;
    color: #111;
}
.thermal-totals-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 600;
    margin: 2px 0;
}
.thermal-net-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    font-weight: 900;
    margin: 4px 0;
}
.thermal-terms {
    font-size: 9.5px;
    font-weight: 600;
    line-height: 1.35;
    margin-top: 4px;
}
.thermal-visit-again {
    text-align: center;
    font-size: 11px;
    font-weight: 800;
    margin-top: 8px;
    letter-spacing: 0.5px;
}
</style>

<!-- Embedded JavaScript Logic for Exact Bill Modal -->
<script>
// Pre-cached sales data passed from server for instant modal rendering
const PRELOADED_SALES_MAP = <?= json_encode(array_column($sales, null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let CURRENT_ACTIVE_SALE_ID = null;
let CURRENT_ACTIVE_INVOICE_NUM = '';
let billModalInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('exactBillModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        billModalInstance = new bootstrap.Modal(modalEl);
    }
});

function openExactBillModal(saleId) {
    CURRENT_ACTIVE_SALE_ID = saleId;
    const invUrl = '<?= url("shop/pos/invoice") ?>/' + saleId;
    document.getElementById('billModalFullPageLink').href = invUrl;

    // Reset views
    switchBillModalView('thermal');

    if (!billModalInstance) {
        const modalEl = document.getElementById('exactBillModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            billModalInstance = new bootstrap.Modal(modalEl);
        }
    }
    if (billModalInstance) {
        billModalInstance.show();
    }

    // Check preloaded cache first for instant render
    if (PRELOADED_SALES_MAP && PRELOADED_SALES_MAP[saleId] && PRELOADED_SALES_MAP[saleId].items && PRELOADED_SALES_MAP[saleId].items.length > 0) {
        populateExactBillModal(PRELOADED_SALES_MAP[saleId]);
    } else {
        document.getElementById('billModalLoading').style.display = 'block';
        document.getElementById('billModalContent').style.display = 'none';
    }

    // Always fetch fresh/complete details from server API
    fetch('<?= url("api/shop/sales") ?>/' + saleId + '?type=sale')
        .then(res => res.json())
        .then(res => {
            document.getElementById('billModalLoading').style.display = 'none';
            document.getElementById('billModalContent').style.display = 'block';
            if (res.success && res.data) {
                const invoiceObj = res.data.invoice || res.data;
                const saleData = (invoiceObj && invoiceObj.sale) ? Object.assign({}, invoiceObj.sale) : (res.data.sale ? Object.assign({}, res.data.sale) : Object.assign({}, invoiceObj));
                saleData.items = (invoiceObj && invoiceObj.items) ? invoiceObj.items : (res.data.items || saleData.items || []);
                populateExactBillModal(saleData);
            }
        })
        .catch(err => {
            console.error('Error fetching bill details:', err);
            document.getElementById('billModalLoading').style.display = 'none';
            document.getElementById('billModalContent').style.display = 'block';
        });
}

function populateExactBillModal(sale) {
    CURRENT_ACTIVE_INVOICE_NUM = sale.invoice_number || ('INV-' + sale.id);
    document.getElementById('modalBillInvNum').textContent = CURRENT_ACTIVE_INVOICE_NUM;
    document.getElementById('modalBillSubMeta').textContent = `Billed on ${formatDate(sale.created_at)} • ${sale.shop_name || 'GRM Retail Store'}`;

    const subtotal = parseFloat(sale.subtotal || 0);
    const cgst = parseFloat(sale.cgst_amount || 0);
    const sgst = parseFloat(sale.sgst_amount || 0);
    const igst = parseFloat(sale.igst_amount || 0);
    const totalGst = cgst + sgst + igst;
    const discount = parseFloat(sale.discount_amount || 0);
    const netTotal = parseFloat(sale.total_amount || 0);
    const roundOff = parseFloat(sale.round_off || (netTotal - (subtotal + totalGst - discount)));

    // 1. POPULATE THERMAL VIEW
    document.getElementById('thShopName').textContent = sale.shop_name || 'GRM SHOP';
    if (sale.shop_address && sale.shop_address.length > 10) {
        document.getElementById('thShopAddress').innerHTML = sale.shop_address.replace(/\n/g, '<br>');
    }
    if (sale.shop_phone) {
        document.getElementById('thShopPhone').textContent = sale.shop_phone;
    }
    if (sale.shop_gstin) {
        document.getElementById('thShopGst').textContent = sale.shop_gstin;
    }

    let billNo = sale.invoice_number || ('INV-' + sale.id);
    const matchNo = billNo.match(/(\d+)$/);
    if (matchNo) billNo = matchNo[1];

    document.getElementById('thBillNumber').textContent = billNo;
    document.getElementById('thBillDate').textContent = formatSimpleDate(sale.created_at);
    document.getElementById('thBillId').textContent = sale.id || '1026';
    document.getElementById('thStaffName').textContent = sale.staff_name || 'Cashier';
    document.getElementById('thCustomerName').textContent = (sale.customer_name || 'WALK-IN').toUpperCase();
    document.getElementById('thPayMode').textContent = sale.payment_mode === 'CASH_UPI' ? 'CASH + UPI' : (sale.payment_mode || 'CASH');

    if (sale.customer_phone) {
        document.getElementById('thCustomerPhoneRow').style.display = 'flex';
        document.getElementById('thCustomerPhone').textContent = sale.customer_phone;
    } else {
        document.getElementById('thCustomerPhoneRow').style.display = 'none';
    }

    // Split Payment note
    const splitRow = document.getElementById('thSplitNotesRow');
    if (sale.payment_mode === 'CASH_UPI' && sale.notes && sale.notes.includes('Split Payment')) {
        splitRow.style.display = 'flex';
        document.getElementById('thSplitNotesText').textContent = sale.notes;
    } else {
        splitRow.style.display = 'none';
    }

    // Render Thermal Item Rows
    const thBody = document.getElementById('thItemsBody');
    thBody.innerHTML = '';
    const items = sale.items || [];

    items.forEach(it => {
        const qty = parseInt(it.quantity) || 1;
        const price = parseFloat(it.unit_price || 0);
        const disc = parseFloat(it.discount_amount || 0);
        const amt = parseFloat(it.total_amount || (qty * price - disc));
        const tags = it.scanned_qr_codes ? it.scanned_qr_codes : (it.sku || '');

        const trName = document.createElement('tr');
        trName.className = 'thermal-item-name';
        trName.innerHTML = `
            <td colspan="5">
                ${escapeHtml(it.product_name || 'Garment')} ${it.size_name ? '- ' + escapeHtml(it.size_name) : ''} ${it.color_name ? '(' + escapeHtml(it.color_name) + ')' : ''}
                <span class="thermal-item-tag">Tag: ${escapeHtml(tags)}</span>
            </td>
        `;

        const trData = document.createElement('tr');
        trData.innerHTML = `
            <td></td>
            <td style="text-align: center;">${qty}</td>
            <td style="text-align: right;">${price.toFixed(2)}</td>
            <td style="text-align: right;">${disc > 0 ? disc.toFixed(2) : '0.00'}</td>
            <td style="text-align: right;">${amt.toFixed(2)}</td>
        `;

        thBody.appendChild(trName);
        thBody.appendChild(trData);
    });

    document.getElementById('thSubtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('thTotalGst').textContent = '+₹' + totalGst.toFixed(2);
    document.getElementById('thDiscount').textContent = discount > 0 ? '-₹' + discount.toFixed(2) : '₹0.00';
    document.getElementById('thNetTotal').textContent = '₹' + netTotal.toFixed(2);

    const roundRow = document.getElementById('thRoundOffRow');
    if (Math.abs(roundOff) > 0.009) {
        roundRow.style.display = 'flex';
        document.getElementById('thRoundOff').textContent = (roundOff > 0 ? '+' : '') + '₹' + roundOff.toFixed(2);
    } else {
        roundRow.style.display = 'none';
    }

    // 2. POPULATE DETAILED AUDIT VIEW
    document.getElementById('detInvNum').textContent = CURRENT_ACTIVE_INVOICE_NUM;
    document.getElementById('detSaleDate').textContent = formatDate(sale.created_at);
    document.getElementById('detCashier').textContent = sale.staff_name || 'Staff Cashier';
    document.getElementById('detPayMode').textContent = sale.payment_mode || 'CASH';
    document.getElementById('detItemCountBadge').textContent = items.length + (items.length === 1 ? ' item' : ' items');

    const detBody = document.getElementById('detItemsBody');
    detBody.innerHTML = '';
    items.forEach((it, idx) => {
        const qty = parseInt(it.quantity) || 1;
        const price = parseFloat(it.unit_price || 0);
        const mrp = parseFloat(it.mrp || it.variant_mrp || price);
        const disc = parseFloat(it.discount_amount || 0);
        const gstPct = parseFloat(it.gst_percentage || 5.0);
        const taxAmt = parseFloat(it.tax_total || it.cgst_amount || 0) * 2;
        const amt = parseFloat(it.total_amount || (qty * price - disc));

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-3 fw-bold">${idx + 1}</td>
            <td>
                <div class="fw-bold text-dark">${escapeHtml(it.product_name || 'Garment Item')}</div>
                <small class="text-muted font-monospace">${escapeHtml(it.product_code || '')}</small>
            </td>
            <td>
                <span class="badge bg-light text-dark border font-monospace">${escapeHtml(it.sku || '--')}</span>
                ${it.scanned_qr_codes ? `<div class="badge bg-dark text-warning font-monospace d-inline-block mt-1"><i class="bi bi-qr-code me-1"></i>${escapeHtml(it.scanned_qr_codes)}</div>` : ''}
            </td>
            <td>
                <span class="badge bg-light text-dark border">${escapeHtml(it.size_name || 'Free')}</span>
                <span class="badge bg-light text-dark border">${escapeHtml(it.color_name || 'Standard')}</span>
            </td>
            <td class="text-center fw-bold">${qty}</td>
            <td class="text-end font-monospace">₹${price.toFixed(2)}</td>
            <td class="text-end font-monospace text-danger">${disc > 0 ? '-₹' + disc.toFixed(2) : '—'}</td>
            <td class="text-end font-monospace text-success">${gstPct.toFixed(1)}%</td>
            <td class="text-end pe-3 fw-bold text-dark font-monospace fs-6">₹${amt.toFixed(2)}</td>
        `;
        detBody.appendChild(tr);
    });

    document.getElementById('detSubtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('detGst').textContent = '+₹' + totalGst.toFixed(2);
    document.getElementById('detDiscount').textContent = discount > 0 ? '-₹' + discount.toFixed(2) : '₹0.00';
    document.getElementById('detNetTotal').textContent = '₹' + netTotal.toFixed(2);
}

function switchBillModalView(mode) {
    const btnThermal = document.getElementById('btnModeThermal');
    const btnDetailed = document.getElementById('btnModeDetailed');
    const viewThermal = document.getElementById('billThermalView');
    const viewDetailed = document.getElementById('billDetailedView');

    if (mode === 'thermal') {
        btnThermal.className = 'btn btn-sm rounded-pill active fw-bold px-3 btn-dark text-white';
        btnDetailed.className = 'btn btn-sm rounded-pill fw-bold px-3 text-muted';
        viewThermal.style.display = 'flex';
        viewDetailed.style.display = 'none';
    } else {
        btnThermal.className = 'btn btn-sm rounded-pill fw-bold px-3 text-muted';
        btnDetailed.className = 'btn btn-sm rounded-pill active fw-bold px-3 btn-primary text-white';
        viewThermal.style.display = 'none';
        viewDetailed.style.display = 'block';
    }
}

function printThermalFromModal() {
    if (!CURRENT_ACTIVE_SALE_ID) return;
    const invUrl = '<?= url("shop/pos/invoice") ?>/' + CURRENT_ACTIVE_SALE_ID;
    const printWin = window.open(invUrl, '_blank');
    if (printWin) {
        printWin.focus();
    }
}

function copyCurrentBillNo() {
    if (!CURRENT_ACTIVE_INVOICE_NUM) return;
    navigator.clipboard.writeText(CURRENT_ACTIVE_INVOICE_NUM).then(() => {
        alert('Invoice Number copied: ' + CURRENT_ACTIVE_INVOICE_NUM);
    });
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
           d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function formatSimpleDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
