<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 text-gray-800"><i class="bi bi-arrow-left-right me-2 text-info"></i>Concern-to-Concern Transfers</h2>
        <p class="text-muted small mb-0">Follow every transfer from source dispatch through transit to destination receipt and discrepancy recording.</p>
    </div>
    <div>
        <?php if (hasPermission('warehouse', 'create')): ?>
        <a href="<?= url('warehouse/transfers/create') ?>" class="btn btn-info text-white">
            <i class="bi bi-plus-lg me-1"></i> New Transfer Request
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Transfer</th>
                    <th>Source Concern / Facility → Destination Concern / Facility</th>
                    <th>Outward Logistics</th>
                    <th>Total Items</th>
                    <th>Status</th>
                    <th>Requested By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No stock transfers recorded.</td></tr>
                <?php else: foreach ($transfers as $trf): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($trf['transfer_number']) ?></div>
                            <small class="text-muted"><?= date('d M Y, h:i A', strtotime($trf['created_at'])) ?></small>
                        </td>
                        <td>
                            <div><span class="badge bg-light text-dark border">From: <?= htmlspecialchars($trf['source_name']) ?></span></div>
                            <div class="mt-1"><span class="badge bg-light text-dark border">To: <?= htmlspecialchars($trf['destination_name']) ?></span></div>
                        </td>
                        <td>
                            <?php if (!empty($trf['carrier_name']) || !empty($trf['vehicle_no'])): ?>
                                <div class="small fw-semibold text-dark"><i class="bi bi-truck me-1 text-primary"></i><?= htmlspecialchars($trf['carrier_name'] ?? 'N/A') ?></div>
                                <div class="small text-muted">Vehicle: <code><?= htmlspecialchars($trf['vehicle_no'] ?? 'N/A') ?></code></div>
                                <?php if (!empty($trf['driver_phone'])): ?>
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($trf['driver_phone']) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">Standard Transport</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-bold text-dark"><?= number_format($trf['total_qty']) ?> pcs</span></td>
                        <td>
                            <?php
                            $st = $trf['status'];
                            if ($st === 'RECEIVED' || $st === 'COMPLETED') {
                                $badgeStyle = 'bg-success';
                            } elseif ($st === 'IN_TRANSIT') {
                                $badgeStyle = 'bg-primary';
                            } elseif ($st === 'DISPATCHED') {
                                $badgeStyle = 'bg-info text-white';
                            } elseif ($st === 'APPROVED') {
                                $badgeStyle = 'bg-secondary text-white';
                            } else {
                                $badgeStyle = 'bg-warning text-dark';
                            }
                            ?>
                            <span class="badge <?= $badgeStyle ?>"><?= htmlspecialchars($st) ?></span>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($trf['requested_by_name'] ?? 'Admin') ?></small></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <!-- Print Gate Pass Modal Trigger -->
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-gatepass" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#gatePassModal"
                                        data-transfer='<?= htmlspecialchars(json_encode($trf), ENT_QUOTES, 'UTF-8') ?>'>
                                    <i class="bi bi-printer me-1"></i> Gate Pass
                                </button>

                                <?php if ($trf['status'] === 'REQUESTED'
                                    && (int)$trf['source_concern_id'] === (int)currentConcernId()
                                    && hasPermission('warehouse', 'approve')): ?>
                                    <form method="POST" action="<?= url('warehouse/transfers/approve/' . $trf['id']) ?>" class="d-inline">
                                        <?= CSRF::field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-info">Approve & Dispatch</button>
                                    </form>
                                    <?php elseif ($trf['status'] === 'IN_TRANSIT'
                                        && (int)$trf['destination_concern_id'] === (int)currentConcernId()
                                        && hasPermission('warehouse', 'receive')): ?>
                                        <button type="button" class="btn btn-sm btn-success btn-receive-transfer"
                                                data-bs-toggle="modal" data-bs-target="#receiveTransferModal"
                                                data-transfer='<?= htmlspecialchars(json_encode($trf), ENT_QUOTES, 'UTF-8') ?>'>
                                            <i class="bi bi-box-arrow-in-down me-1"></i> Receive & Inspect
                                        </button>
                                    <?php elseif ($trf['status'] === 'RECEIVED'): ?>
                                        <span class="btn btn-sm btn-light disabled"><i class="bi bi-check-all text-success me-1"></i> Received</span>
                                    <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="receiveTransferModal" tabindex="-1" aria-labelledby="receiveTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="receiveTransferForm" action="#">
                <?= CSRF::field() ?>
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="receiveTransferModalLabel"><i class="bi bi-box-arrow-in-down me-2"></i>Confirm Transfer Receipt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Enter the physically received quantity and how many of those items arrived damaged. Any dispatched quantity not received is recorded as a discrepancy.</p>
                    <div class="fw-semibold mb-3" id="receiveTransferNumber"></div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Dispatched</th><th>Received</th><th>Damaged</th><th>Missing discrepancy</th></tr>
                            </thead>
                            <tbody id="receiveTransferItems"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Confirm Inward</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gate Pass Modal -->
<div class="modal fade" id="gatePassModal" tabindex="-1" aria-labelledby="gatePassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="gatePassModalLabel"><i class="bi bi-file-earmark-text me-2"></i>Outward Transfer Gate Pass</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="gatePassPrintContent">
                <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                    <div>
                        <h4 class="fw-bold mb-0">GRM LOGISTICS GATE PASS</h4>
                        <div class="small text-muted">Inter-Warehouse Stock Outward Dispatch</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold fs-5 text-primary" id="gpNumber">TRF-0000</div>
                        <div class="small text-muted" id="gpDate">Date: <?= date('Y-m-d') ?></div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold text-muted small mb-2">SOURCE WAREHOUSE</h6>
                            <div class="fw-bold text-dark" id="gpSource">Central Warehouse</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-light border-0 p-3">
                            <h6 class="fw-bold text-muted small mb-2">DESTINATION LOCATION</h6>
                            <div class="fw-bold text-dark" id="gpDestination">City Store Branch</div>
                        </div>
                    </div>
                </div>

                <div class="card border p-3 mb-4">
                    <h6 class="fw-bold border-bottom pb-2 mb-2 text-dark"><i class="bi bi-truck me-2"></i>Carrier & Vehicle Logistics Details</h6>
                    <div class="row g-2 small">
                        <div class="col-6"><strong>Carrier Name:</strong> <span id="gpCarrier">N/A</span></div>
                        <div class="col-6"><strong>Vehicle Number:</strong> <span id="gpVehicle">N/A</span></div>
                        <div class="col-6"><strong>Driver Contact:</strong> <span id="gpDriver">N/A</span></div>
                        <div class="col-6"><strong>Dispatch Timestamp:</strong> <span id="gpDispatchAt">N/A</span></div>
                        <div class="col-12"><strong>Expected Arrival Date:</strong> <span id="gpExpectedArrival">N/A</span></div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2">Dispatched Items Manifest</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product Variant / SKU</th>
                                <th class="text-end">Dispatched</th>
                                <th class="text-end">Received</th>
                                <th class="text-end">Damaged</th>
                                <th class="text-end">Missing</th>
                            </tr>
                        </thead>
                        <tbody id="gpItemsTable">
                            <tr><td colspan="6" class="text-center text-muted">Loading items...</td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold table-light">
                                <td colspan="5" class="text-end">Total Dispatched Goods:</td>
                                <td class="text-end" id="gpTotalQty">0 pcs</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row text-center mt-5 pt-3 border-top">
                    <div class="col-4">
                        <div class="border-top border-dark pt-1 small">Dispatch Security Sign</div>
                    </div>
                    <div class="col-4">
                        <div class="border-top border-dark pt-1 small">Driver / Carrier Sign</div>
                    </div>
                    <div class="col-4">
                        <div class="border-top border-dark pt-1 small">Warehouse Manager</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printGatePass()"><i class="bi bi-printer me-1"></i> Print Gate Pass</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const receiveBaseUrl = <?= json_encode(url('warehouse/transfers/receive/')) ?>;
    document.querySelectorAll('.btn-receive-transfer').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const transfer = JSON.parse(this.getAttribute('data-transfer'));
            document.getElementById('receiveTransferNumber').textContent = transfer.transfer_number;
            document.getElementById('receiveTransferForm').action = receiveBaseUrl + transfer.id;
            const tbody = document.getElementById('receiveTransferItems');
            tbody.replaceChildren();
            transfer.items.forEach(function(item) {
                const row = document.createElement('tr');
                const label = document.createElement('td');
                label.textContent = (item.product_name || 'Product') + ' (' + (item.sku || '') + ')';
                row.appendChild(label);

                const shipped = Number(item.transferred_qty || item.quantity);
                const shippedCell = document.createElement('td');
                shippedCell.textContent = shipped;
                row.appendChild(shippedCell);

                const receivedCell = document.createElement('td');
                const received = document.createElement('input');
                received.type = 'number';
                received.name = 'received_qty[' + item.id + ']';
                received.min = '0';
                received.max = String(shipped);
                received.required = true;
                received.className = 'form-control form-control-sm received-qty';
                received.dataset.shipped = String(shipped);
                receivedCell.appendChild(received);
                row.appendChild(receivedCell);

                const damagedCell = document.createElement('td');
                const damaged = document.createElement('input');
                damaged.type = 'number';
                damaged.name = 'damaged_qty[' + item.id + ']';
                damaged.min = '0';
                damaged.max = String(shipped);
                damaged.value = '0';
                damaged.className = 'form-control form-control-sm damaged-qty';
                damagedCell.appendChild(damaged);
                row.appendChild(damagedCell);

                const discrepancyCell = document.createElement('td');
                discrepancyCell.className = 'text-center discrepancy-qty';
                discrepancyCell.textContent = '—';
                row.appendChild(discrepancyCell);
                received.addEventListener('input', function() {
                    discrepancyCell.textContent = Math.max(0, shipped - (Number(received.value) || 0));
                });
                tbody.appendChild(row);
            });
        });
    });

    document.querySelectorAll('.btn-gatepass').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const trf = JSON.parse(this.getAttribute('data-transfer'));
            document.getElementById('gpNumber').textContent = trf.transfer_number;
            document.getElementById('gpDate').textContent = 'Date: ' + new Date(trf.created_at).toLocaleDateString();
            document.getElementById('gpSource').textContent = trf.source_name;
            document.getElementById('gpDestination').textContent = trf.destination_name;
            document.getElementById('gpCarrier').textContent = trf.carrier_name || 'Standard Carrier';
            document.getElementById('gpVehicle').textContent = trf.vehicle_no || 'N/A';
            document.getElementById('gpDriver').textContent = trf.driver_phone || 'N/A';
            document.getElementById('gpDispatchAt').textContent = trf.dispatch_at || trf.created_at;
            document.getElementById('gpExpectedArrival').textContent = trf.expected_arrival || 'Next Business Day';
            document.getElementById('gpTotalQty').textContent = (trf.total_qty || 0) + ' pcs';

            const tbody = document.getElementById('gpItemsTable');
            tbody.innerHTML = '';
            if (trf.items && trf.items.length > 0) {
                trf.items.forEach((item, idx) => {
                    const tr = document.createElement('tr');
                    const numberCell = document.createElement('td');
                    numberCell.textContent = String(idx + 1);
                    tr.appendChild(numberCell);

                    const productCell = document.createElement('td');
                    const productName = document.createElement('strong');
                    productName.textContent = item.product_name || 'Variant';
                    const sku = document.createElement('small');
                    sku.className = 'text-muted d-block';
                    sku.textContent = 'SKU: ' + (item.sku || '');
                    productCell.append(productName, sku);
                    tr.appendChild(productCell);

                    [
                        item.transferred_qty || item.quantity,
                        item.received_qty || 0,
                        item.damaged_qty || 0,
                        item.discrepancy_qty || 0
                    ].forEach((quantity, columnIndex) => {
                        const cell = document.createElement('td');
                        cell.className = columnIndex === 0 ? 'text-end fw-bold' : 'text-end';
                        cell.textContent = String(quantity);
                        tr.appendChild(cell);
                    });
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Total Quantity: ${trf.total_qty} pcs</td></tr>`;
            }
        });
    });
});

function printGatePass() {
    const content = document.getElementById('gatePassPrintContent').innerHTML;
    const printWindow = window.open('', '_blank', 'width=800,height=900');
    printWindow.document.write('<html><head><title>Transfer Gate Pass</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4">' + content + '<script>window.onload = function() { window.print(); window.close(); }<\/script></body></html>');
    printWindow.document.close();
}
</script>
