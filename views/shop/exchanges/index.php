<?php
// views/shop/exchanges/index.php — Product Exchanges Portal
?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="badge bg-info-subtle text-info-emphasis mb-1"><i class="bi bi-arrow-left-right me-1"></i>Exchanges & Replacements</div>
            <h3 class="fw-bold mb-1 text-dark">Product Exchanges Portal</h3>
            <p class="text-muted small mb-0">Exchange items for different sizes or colors and settle price differences easily.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('shop/returns') ?>" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Start Exchange via Returns
            </a>
        </div>
    </div>

    <!-- Exchange Workflow Step Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="fw-bold text-primary mb-1"><i class="bi bi-1-circle-fill me-1"></i> Step 1: Find Sale</div>
                <p class="small text-muted mb-0">Search original invoice by invoice number or customer mobile number.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-warning">
                <div class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-2-circle-fill me-1"></i> Step 2: Restock Old Item</div>
                <p class="small text-muted mb-0">Select returned item condition (Good for restock / Damaged).</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="fw-bold text-success mb-1"><i class="bi bi-3-circle-fill me-1"></i> Step 3: Issue New Item</div>
                <p class="small text-muted mb-0">Scan new replacement QR in POS and adjust credit note difference.</p>
            </div>
        </div>
    </div>

    <!-- Exchanges History Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Exchanges History</h5>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 font-monospace" id="exchangesCountBadge">
                    <?= count($exchanges ?? []) ?> Records
                </span>
            </div>
            <!-- Live Search Bar -->
            <div class="input-group input-group-sm" style="min-width: 240px; max-width: 320px;">
                <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="exchangeSearchInput" class="form-control bg-light border-start-0 rounded-end-pill font-monospace" 
                       placeholder="Search exchange #, invoice, customer..." autocomplete="off">
            </div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0" id="exchangesTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Exchange #</th>
                        <th>Original Invoice</th>
                        <th>Customer</th>
                        <th>Amount Adjusted</th>
                        <th class="text-end pe-4">Date</th>
                    </tr>
                </thead>
                <tbody id="exchangesTableBody">
                    <?php if (empty($exchanges)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                No exchange records found. You can initiate exchanges from the Returns portal.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exchanges as $ex): 
                            $searchHaystack = strtolower(e(($ex['return_number'] ?? '') . ' ' . ($ex['invoice_number'] ?? '') . ' ' . ($ex['customer_name'] ?? '')));
                        ?>
                            <tr class="exchange-row" data-search="<?= $searchHaystack ?>">
                                <td class="ps-4 fw-bold text-dark"><?= e($ex['return_number']) ?></td>
                                <td><span class="font-monospace text-primary fw-semibold"><?= e($ex['invoice_number']) ?></span></td>
                                <td><?= e($ex['customer_name'] ?? 'Walk-in Customer') ?></td>
                                <td class="fw-bold text-success">₹<?= number_format((float)$ex['refund_amount'], 2) ?></td>
                                <td class="text-end pe-4"><small class="text-muted"><?= formatDateTime($ex['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('exchangeSearchInput');
    const tableBody = document.getElementById('exchangesTableBody');
    const countBadge = document.getElementById('exchangesCountBadge');
    const rows = Array.from(document.querySelectorAll('.exchange-row'));

    function applyExchangeFilters() {
        if (!rows.length) return;
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            if (!query || searchData.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (countBadge) {
            countBadge.textContent = `${visibleCount} Records`;
        }

        let emptyRow = document.getElementById('noExchangesMatchRow');
        if (visibleCount === 0) {
            if (!emptyRow && tableBody) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'noExchangesMatchRow';
                emptyRow.innerHTML = `
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold text-secondary">No Matching Exchanges</h6>
                        <p class="small text-muted mb-0">No records match "${query}".</p>
                    </td>
                `;
                tableBody.appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyExchangeFilters);
        searchInput.addEventListener('keyup', applyExchangeFilters);
    }
});
</script>
