<?php
// views/website_orders/expenses/index.php — Website & Online Commerce Operating Expenses
$summary = $summary ?? [];
$expenses = $expenses ?? [];
$categories = $categories ?? [];
$filters = $filters ?? [];
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-d');
$canCreateExpense = hasPermission('website_expenses', 'create');
$canDeleteExpense = hasPermission('website_expenses', 'delete');
$canPrintVoucher = hasPermission('website_expenses', 'voucher') || hasPermission('website_expenses', 'print');

?>

<div class="container-fluid px-4 py-3">
    <!-- Header & Navigation Breadcrumb -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: #0284c7;">
                <i class="bi bi-globe me-2"></i>Website &amp; Online Orders Operating Expenses
            </h4>
            <p class="text-muted small mb-0">Manage Payment Gateway 2% deductions, Server hosting, Meta/Google ads, Courier shipping, and E-commerce SaaS operational costs.</p>
        </div>
        <div class="d-flex gap-2 align-items-center mt-2 mt-md-0">
            <a href="<?= url('warehouse/website-orders') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Orders Hub
            </a>
            <?php if ($canCreateExpense): ?>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" style="font-weight: 600;" data-bs-toggle="modal" data-bs-target="#recordWebExpenseModal">
                <i class="bi bi-plus-circle me-1"></i> Record E-Commerce Expense
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Metric Cards (Simplified & Practical - 3 Cards) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Filtered Spend -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #0284c7 !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-primary small fw-semibold text-uppercase">Total Channel Spend</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0">₹<?= number_format($summary['total_amount'] ?? 0, 2) ?></h3>
                            <span class="badge bg-primary bg-opacity-25 text-primary rounded-pill mt-2" style="font-size: 11px;">
                                <?= number_format($summary['total_count'] ?? 0) ?> Vouchers in Period
                            </span>
                        </div>
                        <div class="p-3 bg-white rounded-circle shadow-sm text-primary">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Today's Spend -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #16a34a !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-success small fw-semibold text-uppercase">Today's Spend</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0">₹<?= number_format($summary['today_amount'] ?? 0, 2) ?></h3>
                            <span class="badge bg-success bg-opacity-25 text-success rounded-pill mt-2" style="font-size: 11px;">
                                <?= date('d M Y') ?> (<?= number_format($summary['today_count'] ?? 0) ?> logged)
                            </span>
                        </div>
                        <div class="p-3 bg-white rounded-circle shadow-sm text-success">
                            <i class="bi bi-calendar2-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. This Month's Spend -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border-left: 4px solid #7c3aed !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-purple small fw-semibold text-uppercase" style="color: #6d28d9;">This Month's Spend</span>
                            <h3 class="fw-bold text-dark mt-1 mb-0">₹<?= number_format($summary['month_amount'] ?? 0, 2) ?></h3>
                            <span class="badge bg-purple bg-opacity-25 text-dark rounded-pill mt-2" style="font-size: 11px;">
                                <?= date('F Y') ?> (<?= number_format($summary['month_count'] ?? 0) ?> vouchers)
                            </span>
                        </div>
                        <div class="p-3 bg-white rounded-circle shadow-sm text-purple">
                            <i class="bi bi-calendar3 fs-4" style="color: #7c3aed;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="<?= url('warehouse/website-orders/expenses') ?>" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm rounded-pill" value="<?= e($startDate) ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm rounded-pill" value="<?= e($endDate) ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Category</label>
                    <select name="category" class="form-select form-select-sm rounded-pill">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['category_name']) ?>" <?= ($filters['category'] ?? '') === $cat['category_name'] ? 'selected' : '' ?>>
                                <?= e($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2 align-items-end mt-3 mt-md-0">
                    <button type="submit" class="btn btn-dark btn-sm rounded-pill w-100 fw-semibold">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="<?= url('warehouse/website-orders/expenses') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Expense Records Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0 rounded-top-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-receipt-cutoff me-2 text-primary"></i>E-Commerce Operating Expense Vouchers
            </h6>
            <span class="badge bg-light text-secondary rounded-pill px-3 py-2">
                <?= count($expenses) ?> records found
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-muted text-uppercase">
                        <th class="ps-4">Voucher / Date</th>
                        <th>Category</th>
                        <th>Paid To / Vendor</th>
                        <th>Dynamic Details (Gateway / AWB)</th>
                        <th>Payment Mode</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No Website operating expenses found for the selected period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $exp): ?>
                            <?php
                            $notes = $exp['notes'] ?? '';
                            $meta = '';
                            if (preg_match('/\[Metadata: (.*?)\]/', $notes, $m)) {
                                $meta = $m[1];
                            }
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark font-monospace"><?= e($exp['expense_number']) ?></span>
                                    <div class="small text-muted"><?= date('d M Y', strtotime($exp['expense_date'] ?? $exp['created_at'])) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small fw-normal">
                                        <?= e($exp['category']) ?>
                                    </span>
                                    <div class="small text-muted text-truncate" style="max-width: 180px;">
                                        <?= e($exp['item_name'] ?: 'Online Channel Spend') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($exp['paid_to'] ?: 'Bearer') ?></div>
                                    <?php if (!empty($exp['vendor_company'])): ?>
                                        <div class="small text-muted"><?= e($exp['vendor_company']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($meta)): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach (explode('|', $meta) as $part): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace" style="font-size: 10.5px;">
                                                    <?= e(trim($part)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1 font-monospace">
                                        <?= e($exp['payment_method']) ?>
                                    </span>
                                    <?php if (!empty($exp['payment_reference'])): ?>
                                        <div class="small text-muted font-monospace mt-1" style="font-size: 10.5px;"><?= e($exp['payment_reference']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    ₹<?= number_format((float)$exp['total_cost'], 2) ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (!empty($exp['receipt_url'])): ?>
                                            <a href="<?= uploadUrl($exp['receipt_url']) ?>" target="_blank" class="btn btn-outline-secondary" title="View Cloudinary Bill / Receipt">
                                                <i class="bi bi-paperclip"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($canPrintVoucher): ?>
                                            <a href="<?= url('warehouse/website-orders/expenses/voucher/' . $exp['id']) ?>" target="_blank" class="btn btn-outline-primary" title="Print E-Commerce Voucher">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($canDeleteExpense): ?>
                                        <form method="POST" action="<?= url('warehouse/website-orders/expenses/delete/' . (int)$exp['id']) ?>" class="d-inline js-delete-web-expense" data-expense-number="<?= e($exp['expense_number']) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Expense">
                                            <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Record Website Operating Expense Modal -->
<?php if ($canCreateExpense): ?>
<div class="modal fade" id="recordWebExpenseModal" tabindex="-1" aria-labelledby="recordWebExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white px-4 py-3">
                <h5 class="modal-title fw-bold" id="recordWebExpenseModalLabel">
                    <i class="bi bi-globe me-2"></i>Record Website Expense
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('warehouse/website-orders/expenses/store') ?>" method="POST" id="webExpenseForm" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Category -->
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Expense Category <span class="text-danger">*</span></label>
                            <input type="text" name="category" class="form-control rounded-3" list="webCategoryList" placeholder="e.g. Payment Gateway Fee, Server Hosting, Courier Shipping..." required minlength="2" maxlength="100" id="webCategoryInput">
                            <datalist id="webCategoryList">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat['category_name'] ?? $cat['name'] ?? '') ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <div class="invalid-feedback" style="font-size: 11px;">Please enter or select a valid expense category.</div>
                        </div>

                        <!-- Date -->
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" id="webExpenseDate" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                            <div class="invalid-feedback" style="font-size: 11px;">Please select a valid expense date (cannot be in the future).</div>
                        </div>

                        <!-- Item / Expense Purpose -->
                        <div class="col-12 col-md-8">
                            <label class="form-label small fw-semibold text-muted">Item / Description Title <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="webItemName" class="form-control rounded-3" placeholder="e.g., Tea & Refreshments / Office Supplies / Razorpay 2% Fee / AWS Cloud Server" required minlength="2" maxlength="255">
                            <div class="invalid-feedback" style="font-size: 11px;">Please enter an item or description title (min 2 characters).</div>
                        </div>

                        <!-- Total Amount -->
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-muted">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="10000000" name="amount" id="webAmount" class="form-control rounded-3 fw-bold" placeholder="0.00" required>
                            <div class="invalid-feedback" style="font-size: 11px;">Please enter a valid amount greater than ₹0.00.</div>
                        </div>

                        <!-- Payee Name -->
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Paid To / Payee Vendor <span class="text-danger">*</span></label>
                            <input type="text" name="paid_to" id="webPaidTo" class="form-control rounded-3" placeholder="e.g., Tea Stall / Razorpay / Hostinger / Vendor" required minlength="2" maxlength="100">
                            <div class="invalid-feedback" style="font-size: 11px;">Please enter the recipient or vendor name.</div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-muted">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_method" id="webPaymentMethod" class="form-select rounded-3" required>
                                <option value="BANK_TRANSFER">Bank IMPS / NEFT / Auto-Debit</option>
                                <option value="CARD">Corporate Credit / Debit Card</option>
                                <option value="UPI">UPI / NetBanking</option>
                                <option value="CASH">Cash</option>
                                <option value="OTHER">Prepaid Wallet / Other</option>
                            </select>
                            <div class="invalid-feedback" style="font-size: 11px;">Please select a payment mode.</div>
                        </div>

                        <div class="col-12">
                            <details>
                                <summary class="small fw-semibold text-primary cursor-pointer">Additional details (optional)</summary>
                                <div class="row g-3 mt-1">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Vendor / Payee Contact #</label>
                                        <input type="tel" name="vendor_phone" id="webVendorPhone" class="form-control rounded-3" placeholder="10-digit mobile number" pattern="^[6-9][0-9]{9}$" maxlength="10">
                                        <div class="invalid-feedback" style="font-size: 11px;">Must be a valid 10-digit mobile number starting with 6, 7, 8, or 9.</div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Bill / Cash Memo / Invoice #</label>
                                        <input type="text" name="invoice_number" id="webInvoiceNumber" class="form-control rounded-3" placeholder="e.g., BILL-1029 / INV-9182" maxlength="50">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-muted">Payment Reference / UTR #</label>
                                        <input type="text" name="payment_reference" id="webPaymentRef" class="form-control rounded-3 font-monospace" placeholder="Transaction reference (optional)" maxlength="60">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-muted d-flex align-items-center justify-content-between">
                                            <span><i class="bi bi-paperclip me-1"></i>Attach Bill / Receipt / Invoice Copy</span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;"><i class="bi bi-cloud-arrow-up me-1"></i>Cloudinary CDN</span>
                                        </label>
                                        <input type="file" name="receipt_file" id="webReceiptFile" class="form-control rounded-3" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                        <div class="form-text small text-muted" style="font-size: 11px;"><i class="bi bi-cloud-check text-success me-1"></i>JPG, PNG, WEBP or PDF; max 10MB.</div>
                                        <div class="invalid-feedback" style="font-size: 11px;">Allowed file types: JPG, PNG, WEBP, PDF (max 10MB).</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-muted">Additional Remarks</label>
                                        <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Brief reason for expense..." maxlength="500"></textarea>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3 border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnSubmitWebExpense">
                        <i class="bi bi-check-circle me-1"></i> Save Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-delete-web-expense').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const number = form.dataset.expenseNumber;
            if (!confirm(`Are you sure you want to delete expense voucher ${number}? This will remove it permanently.`)) {
                event.preventDefault();
            }
        });
    });

    const form = document.getElementById('webExpenseForm');
    const phoneInput = document.getElementById('webVendorPhone');
    const amountInput = document.getElementById('webAmount');
    const receiptInput = document.getElementById('webReceiptFile');

    // Phone input: auto-strip non-digits and validate
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
            if (this.value.length > 0 && (!/^[6-9]\d{9}$/.test(this.value))) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }

    // Amount input: prevent negative and zero
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            const val = parseFloat(this.value);
            if (isNaN(val) || val <= 0) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }

    // Receipt file validation (size & extension)
    if (receiptInput) {
        receiptInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const f = this.files[0];
                const ext = f.name.split('.').pop().toLowerCase();
                const allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                if (!allowed.includes(ext) || f.size > 10 * 1024 * 1024) {
                    this.classList.add('is-invalid');
                    this.value = '';
                } else {
                    this.classList.remove('is-invalid');
                }
            }
        });
    }

    // Form submission validation
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Check phone if entered
            if (phoneInput && phoneInput.value.trim().length > 0) {
                const cleanedPhone = phoneInput.value.replace(/\D/g, '');
                if (!/^[6-9]\d{9}$/.test(cleanedPhone)) {
                    phoneInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    phoneInput.classList.remove('is-invalid');
                }
            }

            // Check amount
            if (amountInput) {
                const val = parseFloat(amountInput.value);
                if (isNaN(val) || val <= 0) {
                    amountInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    amountInput.classList.remove('is-invalid');
                }
            }

            // Check required fields & standard HTML5 validation
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value || field.value.trim() === '') {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    if (field !== phoneInput && field !== amountInput) {
                        field.classList.remove('is-invalid');
                    }
                }
            });

            if (!isValid || !form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Focus first invalid element
                const firstInvalid = form.querySelector('.is-invalid, :invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });
    }
});
</script>
