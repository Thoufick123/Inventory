<?php
$sourceMode = $sourceMode ?? 'qc';
$inwardLocations = $inwardLocations ?? [];
$selectedInward = $selectedInward ?? null;
$canDispatchNow = !empty($canDispatchNow);
$defaultBillingOn = $sourceMode === 'scan';
?>
<div class="page-header mb-4">
    <h2><i class="bi bi-box-seam me-2 text-success"></i>Pack Finished Production Batch</h2>
    <span class="text-muted">Pack QC-passed, scanned, or inwarded inventory pieces, optionally generate a Sale Bill, and dispatch to the receiving concern</span>
</div>

<?php if ($selectedQC && ($selectedQC['remaining_qty'] ?? 1) <= 0 && ($selectedQC['already_packed_qty'] ?? 0) > 0): ?>
    <div class="alert alert-info border border-info-subtle shadow-sm p-3 mb-4 rounded-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle">
                    <i class="bi bi-info-circle-fill fs-2"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1 text-dark">QC Batch #<?= $selectedQC['id'] ?> is Already Fully Packed</h5>
                    <p class="text-muted small mb-0">
                        All <strong><?= number_format($selectedQC['total_qc_passed'] ?? $selectedQC['passed_qty']) ?> passed pieces</strong> from this QC inspection have already been packed into finished batch(es) and dispatched. There are 0 remaining pieces to pack.
                    </p>
                    <?php if (!empty($existingBatches)): ?>
                    <div class="mt-2 d-flex gap-2 flex-wrap align-items-center">
                        <span class="small fw-semibold text-dark">Packed In:</span>
                        <?php foreach ($existingBatches as $eb): ?>
                            <span class="badge bg-white text-dark border shadow-xs p-2">
                                <i class="bi bi-box-seam me-1 text-primary"></i> <strong>Batch #<?= e($eb['batch_number']) ?></strong> &bull; <?= $eb['total_qty'] ?> pcs &bull; Status: <span class="badge bg-<?= $eb['status'] === 'DISPATCHED' ? 'warning-subtle text-dark border' : 'success' ?>"><?= e($eb['status']) ?></span>
                                <?php if (!empty($eb['destination_concern_name']) || !empty($eb['warehouse_name'])): ?>
                                    &rarr; <?= e($eb['destination_concern_name'] ?: $eb['warehouse_name']) ?>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('production/batches' . (!empty($existingBatches[0]['batch_number']) ? '?search=' . urlencode($existingBatches[0]['batch_number']) : '')) ?>" class="btn btn-primary fw-semibold">
                    <i class="bi bi-box-seam me-1"></i> View Finished Batch
                </a>
                <a href="<?= url('production/inventory') ?>" class="btn btn-outline-secondary fw-semibold">
                    <i class="bi bi-boxes me-1"></i> View Inventory
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('production/batches/store') ?>" id="packBatchForm">
    <?= CSRF::field() ?>
    <input type="hidden" name="source_mode" id="source_mode" value="<?= e($sourceMode) ?>">

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Packing Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Stock Source</label>
                        <div class="btn-group w-100" role="group" aria-label="Stock source">
                            <a href="<?= url('production/batches/create?source_mode=qc') ?>"
                               class="btn btn-sm <?= $sourceMode === 'qc' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <i class="bi bi-patch-check me-1"></i>QC Batches
                            </a>
                            <a href="<?= url('production/batches/create?source_mode=scan') ?>"
                               class="btn btn-sm <?= $sourceMode === 'scan' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <i class="bi bi-qr-code-scan me-1"></i>Scan QR
                            </a>
                            <a href="<?= url('production/batches/create?source_mode=inward') ?>"
                               class="btn btn-sm <?= $sourceMode === 'inward' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <i class="bi bi-box2-heart me-1"></i>Inwarded Stock
                            </a>
                        </div>
                    </div>

                    <?php if ($sourceMode === 'scan'): ?>
                        <input type="hidden" name="program_id" id="program_id" value="">
                        <div class="alert alert-primary bg-primary bg-opacity-10 border-primary border-opacity-25 small mb-3">
                            <div class="fw-bold text-primary mb-1"><i class="bi bi-qr-code-scan me-1"></i>Billing-Style QR Scanning</div>
                            Scan or search any available QR code, SKU, or product from your concern's unsold, undamaged QC or inwarded inventory on the right to pack and bill in one step.
                        </div>
                    <?php elseif ($sourceMode === 'inward'): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold required">Select Inward Inventory Source <span class="text-danger">*</span></label>
                            <select name="source_location" id="source_location" class="form-select border-primary" required onchange="handleInwardLocationChange(this.value)">
                                <option value="">-- Select Inward Location --</option>
                                <?php foreach ($inwardLocations as $loc): ?>
                                    <option value="<?= e($loc['location_key']) ?>" <?= ($selectedInward && $selectedInward['location_key'] === $loc['location_key']) ? 'selected' : '' ?>>
                                        <?= e($loc['location_label']) ?> [<?= number_format((int)$loc['total_qty']) ?> pcs available]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small text-muted">Pack QR pieces held in Concern / Unit inward inventory.</div>
                            <?php if (empty($inwardLocations)): ?>
                                <div class="alert alert-warning py-2 px-3 small mt-2 mb-0">
                                    <i class="bi bi-info-circle me-1"></i> No available inwarded stock is currently ready to pack.
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="program_id" id="program_id" value="">
                        <?php if ($selectedInward): ?>
                            <div class="mb-3 p-3 bg-light rounded border">
                                <div class="small text-muted fw-semibold">Source Location</div>
                                <div class="fw-bold text-dark"><?= e($selectedInward['location_label']) ?></div>
                                <div class="small text-success fw-semibold mt-1">
                                    <i class="bi bi-check-circle-fill me-1"></i><?= number_format((int)$selectedInward['total_qty']) ?> pcs available across <?= count($items) ?> variant(s)
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Select QC Inspection Batch <span class="text-danger">*</span></label>
                        <select name="qc_batch_id" id="qc_batch_id" class="form-select <?= empty($selectedQC) ? 'border-primary' : '' ?>" required onchange="handleQCBatchChange(this.value)">
                            <option value="">-- Select QC Batch --</option>
                            <?php foreach ($qcBatches as $qb): ?>
                                <option value="<?= $qb['id'] ?>" <?= ($selectedQC && (int)$selectedQC['id'] === (int)$qb['id']) ? 'selected' : '' ?>>
                                    QC #<?= $qb['id'] ?> (<?= e($qb['program_number'] ?? '') ?> — <?= e($qb['product_name'] ?? '') ?>) [<?= number_format($qb['remaining_qty'] ?? $qb['passed_qty']) ?> pcs remaining / <?= number_format($qb['total_qc_passed'] ?? $qb['passed_qty']) ?> pcs passed]
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small text-muted">Select a QC-passed batch to load approved quantities.</div>
                        <?php if (empty($qcBatches) && !$selectedQC): ?>
                            <div class="alert alert-warning py-2 px-3 small mt-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i> All QC passed batches have already been fully packed into finished batches. <a href="<?= url('production/qc/create') ?>" class="alert-link">Create a QC inspection</a>.
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($selectedQC): ?>
                        <input type="hidden" name="program_id" id="program_id" value="<?= $selectedQC['program_id'] ?>">
                        <div class="mb-3 p-3 bg-light rounded border">
                            <div class="small text-muted fw-semibold">Program &amp; Product</div>
                            <div class="fw-bold text-dark"><?= e($selectedQC['program_number']) ?> — <?= e($selectedQC['product_name']) ?></div>
                            <div class="row g-2 mt-2 pt-2 border-top">
                                <div class="col-4">
                                    <div class="small text-muted">Total Passed</div>
                                    <div class="fw-bold text-dark"><?= number_format($selectedQC['total_qc_passed'] ?? $selectedQC['passed_qty']) ?> pcs</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Already Packed</div>
                                    <div class="fw-bold text-danger"><?= number_format($selectedQC['already_packed_qty'] ?? 0) ?> pcs</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Remaining</div>
                                    <div class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i><?= number_format($selectedQC['remaining_qty'] ?? $selectedQC['passed_qty']) ?> pcs</div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="program_id" id="program_id" value="">
                    <?php endif; ?>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Destination Concern <span class="text-danger">*</span></label>
                        <select name="destination_concern_id" id="destination_concern_id" class="form-select" required>
                            <option value="">-- Select Receiving Concern --</option>
                            <?php foreach ($destinationConcerns as $concern): ?>
                                <option value="<?= (int)$concern['id'] ?>"><?= e($concern['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small text-muted">The selected concern will receive this batch in its Production Inward module after dispatch.</div>
                        <?php if (empty($destinationConcerns)): ?>
                            <div class="alert alert-warning py-2 px-3 small mt-2 mb-0">No other active concern currently has Production Inward enabled.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold required">Total Box / Carton Count <span class="text-danger">*</span></label>
                        <input type="number" name="box_count" id="box_count" class="form-control" min="1" value="1" required placeholder="Enter number of boxes...">
                        <div class="form-text small text-muted">Total number of boxes or cartons packed for this batch.</div>
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Batch Billing &amp; Settlement</label>
                        <div class="btn-group w-100" role="group" aria-label="Sale bill mode">
                            <input type="radio" class="btn-check" name="generate_sale_bill" id="modeTransferOnly" value="0" <?= !$defaultBillingOn ? 'checked' : '' ?> onchange="toggleBatchBillingMode()">
                            <label class="btn btn-sm btn-outline-secondary" for="modeTransferOnly">
                                <i class="bi bi-arrow-left-right me-1"></i>No Sale Bill
                            </label>
                            <input type="radio" class="btn-check" name="generate_sale_bill" id="modeWithSaleBill" value="1" <?= $defaultBillingOn ? 'checked' : '' ?> onchange="toggleBatchBillingMode()">
                            <label class="btn btn-sm btn-outline-success fw-semibold" for="modeWithSaleBill">
                                <i class="bi bi-receipt-cutoff me-1"></i>Generate Sale Bill
                            </label>
                        </div>
                        <div class="form-text small text-muted">Generate a Sale Bill (Company / Customer) right here alongside packing &amp; outward dispatch.</div>
                    </div>

                    <div id="saleBillFieldsSection" class="<?= $defaultBillingOn ? '' : 'd-none' ?>">
                        <?php if ($canDispatchNow): ?>
                        <div class="p-3 bg-warning-subtle border border-warning-subtle rounded mb-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="dispatchNowToggle" name="dispatch_now" value="1" <?= $sourceMode === 'scan' ? 'checked' : '' ?> onchange="toggleDispatchNowFields()">
                                <label class="form-check-label fw-bold text-dark" for="dispatchNowToggle">
                                    Dispatch Outward Now (One-Step Outward)
                                </label>
                            </div>
                            <div class="small text-muted mt-1">Creates &amp; approves the batch, dispatches the Production Outward, and issues the Sale Bill together. Leave off to approve &amp; dispatch later from Batches / Outward.</div>

                            <div id="dispatchNowFields" class="mt-3 pt-2 border-top border-warning-subtle <?= $sourceMode === 'scan' ? '' : 'd-none' ?>">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Vehicle Number <span class="text-danger">*</span></label>
                                        <input type="text" name="vehicle_number" id="vehicle_number" class="form-control form-control-sm text-uppercase" placeholder="TN 39 AB 1234">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Dispatch Date &amp; Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="dispatch_date" id="dispatch_date" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Driver Name <span class="text-danger">*</span></label>
                                        <input type="text" name="driver_name" id="driver_name" class="form-control form-control-sm" placeholder="Driver full name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Driver Phone (10 digits) <span class="text-danger">*</span></label>
                                        <input type="text" name="driver_contact" id="driver_contact" class="form-control form-control-sm" maxlength="15" placeholder="9876543210">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">Transporter <span class="text-danger">*</span></label>
                                        <input type="text" name="transporter_name" id="transporter_name" class="form-control form-control-sm" placeholder="Transport / Tempo">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold mb-1">LR / Waybill #</label>
                                        <input type="text" name="lr_number" id="lr_number" class="form-control form-control-sm" placeholder="Optional">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold mb-1">Outward Notes</label>
                                        <input type="text" name="outward_notes" id="outward_notes" class="form-control form-control-sm" placeholder="Optional dispatch remarks">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="p-3 bg-light border rounded">
                            <div class="fw-bold text-dark small mb-2"><i class="bi bi-building me-1 text-success"></i>Sale Bill Party &amp; Payment</div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold mb-1">Bill To</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="buyer_type" id="batchBuyerCompany" value="COMPANY" checked onchange="toggleBatchBuyerType()">
                                    <label class="btn btn-sm btn-outline-primary" for="batchBuyerCompany">Company</label>
                                    <input type="radio" class="btn-check" name="buyer_type" id="batchBuyerCustomer" value="CUSTOMER" onchange="toggleBatchBuyerType()">
                                    <label class="btn btn-sm btn-outline-primary" for="batchBuyerCustomer">Customer</label>
                                </div>
                            </div>

                            <div id="batchCompanyFields">
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold mb-1">Company Name <span class="text-danger">*</span></label>
                                    <input type="text" name="company_name" id="batch_company_name" class="form-control form-control-sm" placeholder="Receiving Concern / Company Name">
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Phone</label>
                                        <input type="text" name="company_phone" class="form-control form-control-sm" placeholder="Optional">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">GSTIN</label>
                                        <input type="text" name="company_gstin" class="form-control form-control-sm text-uppercase" placeholder="Optional">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Email</label>
                                    <input type="email" name="company_email" class="form-control form-control-sm" placeholder="Optional">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Company Address</label>
                                    <textarea name="company_address" class="form-control form-control-sm" rows="2" placeholder="Optional"></textarea>
                                </div>
                            </div>

                            <div id="batchCustomerFields" class="d-none">
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold mb-1">Customer Name <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name" id="batch_customer_name" class="form-control form-control-sm" placeholder="Customer Full Name">
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold mb-1">Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="customer_phone" id="batch_customer_phone" class="form-control form-control-sm" placeholder="Mobile Number">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">GSTIN</label>
                                        <input type="text" name="customer_gstin" class="form-control form-control-sm text-uppercase" placeholder="Optional">
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">City</label>
                                        <input type="text" name="customer_city" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">State</label>
                                        <input type="text" name="customer_state" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Address</label>
                                    <textarea name="customer_address" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Bill Date <span class="text-danger">*</span></label>
                                    <input type="date" name="bill_date" id="batch_bill_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold mb-1">Payment Method <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="batch_payment_method" class="form-select form-select-sm" onchange="toggleBatchPaymentSplit()">
                                        <option value="CREDIT" selected>Credit (Receivable)</option>
                                        <option value="CASH">Cash</option>
                                        <option value="UPI">UPI</option>
                                        <option value="CASH_UPI">Cash + UPI</option>
                                        <option value="NET_BANKING">Net Banking</option>
                                        <option value="CARD">Card</option>
                                    </select>
                                </div>
                            </div>

                            <div id="batchSplitPaymentFields" class="row g-2 mb-2 d-none">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Cash Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="cash_amount" id="batch_cash_amount" class="form-control form-control-sm" value="0.00">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small mb-1">UPI Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="upi_amount" id="batch_upi_amount" class="form-control form-control-sm" value="0.00">
                                </div>
                            </div>

                            <div id="batchUpiRefField" class="mb-2 d-none">
                                <label class="form-label small mb-1">UPI Reference / UTR</label>
                                <input type="text" name="upi_reference" id="batch_upi_reference" class="form-control form-control-sm" placeholder="Transaction reference">
                            </div>

                            <div>
                                <label class="form-label small mb-1">Sale Bill Notes</label>
                                <input type="text" name="billing_notes" class="form-control form-control-sm" placeholder="Optional invoice notes">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm h-100 d-flex flex-column">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 flex-wrap gap-2">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="bi bi-<?= $sourceMode === 'scan' ? 'qr-code-scan' : 'grid-3x3' ?> me-2 text-primary"></i>
                        <?= $sourceMode === 'scan' ? 'Scanned QR Items to Pack & Bill' : 'Packed Quantities' ?>
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark fs-6 px-3 py-2 billing-col <?= $defaultBillingOn ? '' : 'd-none' ?>" id="totalBillBadge">₹0.00</span>
                        <span class="badge bg-success fs-6 px-3 py-2" id="totalPackedBadge">0 pcs Packed</span>
                    </div>
                </div>
                <div class="card-body p-0 flex-grow-1 d-flex flex-column">
                    <?php if ($sourceMode === 'scan'): ?>
                        <div class="p-3 bg-light border-bottom">
                            <label for="scanSearchInput" class="form-label fw-semibold mb-1">
                                <i class="bi bi-upc-scan me-1 text-primary"></i>Scan QR Code or Search SKU / Product
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="search" id="scanSearchInput" class="form-control" placeholder="Scan QR code and press Enter, or type product name / SKU..." autocomplete="off">
                                <button type="button" class="btn btn-primary" onclick="runBatchScanSearch(false)">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="runBatchScanSearch(true)">
                                    <i class="bi bi-list-ul me-1"></i>Show Available
                                </button>
                            </div>
                            <div class="small text-muted mt-1">Scanning an exact QR code adds it automatically; searching by product or SKU lets you add one or multiple available QR pieces.</div>
                            <div id="scanSearchResults" class="mt-2"></div>
                        </div>

                        <div class="table-responsive flex-grow-1">
                            <table class="table table-hover align-middle mb-0" id="scanCartTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product &amp; Variant</th>
                                        <th>Scanned QR Code(s)</th>
                                        <th style="width: 90px;" class="text-center">Qty</th>
                                        <th class="billing-col <?= $defaultBillingOn ? '' : 'd-none' ?>" style="width: 310px;">Rate, Discount &amp; GST</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="scanCartBody">
                                    <tr id="scanCartEmptyRow">
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="bi bi-qr-code fs-1 d-block mb-2 opacity-50"></i>
                                            No QR pieces added yet. Scan a QR code or search inventory above.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (($sourceMode === 'inward' && (!$selectedInward || empty($items))) || ($sourceMode === 'qc' && !$selectedQC)): ?>
                        <div class="p-5 text-center text-muted my-auto">
                            <div class="mb-3">
                                <div class="d-inline-flex p-3 rounded-circle bg-light">
                                    <i class="bi bi-arrow-left-circle fs-1 text-primary"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold text-dark"><?= $sourceMode === 'inward' ? 'No Inwarded Inventory Available' : 'No QC Batch Selected' ?></h5>
                            <p class="text-muted mb-0"><?= $sourceMode === 'inward' ? 'Select an inward inventory location on the left with available QR pieces to pack.' : 'Please select a QC inspection batch from the dropdown on the left to load the approved variant quantities.' ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="packedItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Variant</th>
                                        <th><?= $sourceMode === 'inward' ? 'Available Stock' : 'QC Status &amp; Remaining' ?></th>
                                        <th><?= $sourceMode === 'inward' ? 'Inward QR Code(s) to Pack' : 'Approved QR Code(s) to Pack' ?></th>
                                        <th style="width: 140px;">Qty to Pack <span class="text-danger">*</span></th>
                                        <th class="billing-col <?= $defaultBillingOn ? '' : 'd-none' ?>" style="min-width: 280px;">Sale Bill Rate &amp; GST</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $idx => $it):
                                        if ($sourceMode === 'inward') {
                                            $remQty = (int)($it['packable_qty'] ?? 0);
                                            $availablePieceTags = array_values($it['available_piece_tags'] ?? []);
                                            $packQty = min($remQty, count($availablePieceTags));
                                            $isZero = ($packQty <= 0);
                                            $rejectedQty = 0;
                                            $reworkQty = 0;
                                            $labelPrefix = trim(($it['product_name'] ?? '') . ' — ');
                                        } else {
                                            $remQty = (int)($it['remaining_qty'] ?? $it['passed_qty']);
                                            $availablePieceTags = array_values($it['available_piece_tags'] ?? []);
                                            $hasAvailableTags = !empty($availablePieceTags);
                                            $packQty = $hasAvailableTags ? min($remQty, count($availablePieceTags)) : $remQty;
                                            $isZero = ($packQty <= 0);
                                            $rejectedQty = (int)($it['rejected_qty'] ?? 0);
                                            $reworkQty = (int)($it['rework_qty'] ?? 0);
                                            $labelPrefix = '';
                                        }
                                        $hasAvailableTags = !empty($availablePieceTags);
                                        $mrpVal = max(0.0, (float)($it['mrp'] ?? $it['base_price'] ?? 0));
                                        $unitVal = max(0.0, (float)($it['unit_price'] ?? $it['discount_price'] ?? $mrpVal));
                                        if ($mrpVal <= 0 && $unitVal > 0) $mrpVal = $unitVal;
                                        $discPctVal = ($mrpVal > 0 && $unitVal < $mrpVal) ? round((($mrpVal - $unitVal) / $mrpVal) * 100, 2) : 0;
                                        $gstPctVal = max(0.0, (float)($it['gst_percentage'] ?? 0));
                                        $defaultGstType = $gstPctVal > 0 ? 'BOTH' : 'EXEMPTED';
                                        $halfGst = round($gstPctVal / 2, 3);
                                    ?>
                                        <tr class="variant-item-row billing-calc-row <?= $isZero ? 'table-light opacity-75' : '' ?>" data-variant-name="<?= e($labelPrefix . ($it['size_name'] ?? $it['display_size'] ?? 'Standard') . ' / ' . ($it['color_name'] ?? $it['display_color'] ?? 'Assorted')) ?>" data-passed="<?= $packQty ?>" data-is-zero="<?= $isZero ? '1' : '0' ?>" data-mrp="<?= number_format($mrpVal, 2, '.', '') ?>">
                                            <td>
                                                <input type="hidden" name="items[<?= $idx ?>][qc_item_id]" value="<?= (int)($it['id'] ?? 0) ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][variant_id]" value="<?= e($it['variant_id'] ?? '') ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][size_name]" value="<?= e($it['size_name'] ?? $it['display_size'] ?? '') ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][color_name]" value="<?= e($it['color_name'] ?? $it['display_color'] ?? '') ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][sku]" value="<?= e($it['sku'] ?? '') ?>">
                                                <div class="fw-bold text-dark"><?= e($labelPrefix . ($it['size_name'] ?? $it['display_size'] ?? 'Standard') . ' / ' . ($it['color_name'] ?? $it['display_color'] ?? 'Assorted')) ?></div>
                                                <small class="text-muted font-monospace"><?= e($it['sku'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php if ($isZero): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                                            <i class="bi bi-slash-circle me-1"></i>0 pcs Available
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                            <i class="bi bi-box-seam me-1"></i><?= number_format($remQty) ?> pcs Available
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($sourceMode === 'inward'): ?>
                                                    <small class="text-muted d-block mt-1">
                                                        Source: <?= e($it['grn_label'] ?: 'Inward Stock') ?>
                                                    </small>
                                                <?php elseif (($it['already_packed'] ?? 0) > 0): ?>
                                                    <small class="text-muted d-block mt-1">
                                                        Total Passed: <?= number_format($it['passed_qty']) ?> pcs (<?= number_format($it['already_packed']) ?> already packed)
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted d-block mt-1">
                                                        Total Passed: <?= number_format($it['passed_qty']) ?> pcs
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($hasAvailableTags): ?>
                                                    <div class="small fw-semibold text-success mb-1">
                                                        <i class="bi bi-patch-check-fill me-1"></i>
                                                        <?= number_format(count($availablePieceTags)) ?> approved QR code(s) available
                                                    </div>
                                                    <?php if ($rejectedQty > 0 || $reworkQty > 0): ?>
                                                        <div class="small text-danger mb-2">
                                                            <?= $rejectedQty > 0 ? number_format($rejectedQty) . ' rejected' : '' ?>
                                                            <?= $rejectedQty > 0 && $reworkQty > 0 ? ' · ' : '' ?>
                                                            <?= $reworkQty > 0 ? number_format($reworkQty) . ' need rework' : '' ?> QR code(s) excluded.
                                                            Re-inspect and pass them in QC before packing.
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="piece-tag-picker">
                                                        <input type="search"
                                                               class="form-control form-control-sm piece-tag-search mb-2"
                                                               placeholder="Search QR codes..."
                                                               aria-label="Search QR codes for <?= e($it['sku'] ?? '') ?>"
                                                               oninput="filterPieceTags(this)"
                                                               <?= $isZero ? 'disabled' : '' ?>>
                                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                                            <small class="text-muted piece-tag-count" aria-live="polite"></small>
                                                            <div class="d-flex gap-1">
                                                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" onclick="selectPieceTagsForQuantity(this)" <?= $isZero ? 'disabled' : '' ?>>Select Qty</button>
                                                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="clearPieceTags(this)" <?= $isZero ? 'disabled' : '' ?>>Clear</button>
                                                            </div>
                                                        </div>
                                                        <div class="piece-tag-options <?= $isZero ? 'opacity-50' : '' ?>">
                                                        <?php foreach ($availablePieceTags as $tagIndex => $tag): ?>
                                                            <label class="piece-tag-option" data-piece-tag-option>
                                                                <input type="checkbox"
                                                                       name="items[<?= $idx ?>][piece_tags][]"
                                                                       class="form-check-input piece-tags-input mt-0"
                                                                       value="<?= e($tag) ?>"
                                                                       <?= $tagIndex < $packQty ? 'checked' : '' ?>
                                                                       <?= $isZero ? 'disabled' : '' ?>
                                                                       onchange="syncPieceTagSelection(this)">
                                                                <span class="font-monospace"><?= e($tag) ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                        </div>
                                                        <div class="small text-muted mt-1">Check QR codes to pack. The quantity updates to match your selection.</div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="small text-muted">Individual QC QR codes are unavailable.</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           name="items[<?= $idx ?>][quantity]" 
                                                           class="form-control form-control-sm packed-input fw-semibold <?= $isZero ? 'bg-light text-muted border-secondary-subtle' : '' ?>" 
                                                           min="0" 
                                                           max="<?= $packQty ?>"
                                                           value="<?= $packQty ?>"
                                                           <?= $isZero ? 'disabled readonly tabindex="-1"' : '' ?>
                                                           oninput="calcPackedTotal(); updatePieceTagCounts(); recalcBatchBillingTotals()">
                                                    <span class="input-group-text <?= $isZero ? 'bg-light text-muted' : 'bg-light text-muted' ?>">pcs</span>
                                                </div>
                                                <?php if ($isZero): ?>
                                                    <div class="small text-muted mt-1"><i class="bi bi-lock-fill me-1"></i>0 pcs remaining (Disabled)</div>
                                                <?php else: ?>
                                                    <div class="small text-muted mt-1">Max: <?= number_format($packQty) ?> pcs</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="billing-col <?= $defaultBillingOn ? '' : 'd-none' ?>">
                                                <?php $disAttr = ($isZero || !$defaultBillingOn) ? 'disabled' : ''; $zeroAttr = $isZero ? 'data-zero-locked="1"' : ''; ?>
                                                <div class="small text-muted mb-1">MRP: ₹<?= number_format($mrpVal, 2) ?></div>
                                                <div class="row g-1 mb-1">
                                                    <div class="col-5">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.01" min="0" max="100"
                                                                   class="form-control bill-disc-input"
                                                                   value="<?= number_format($discPctVal, 2, '.', '') ?>"
                                                                   title="Discount %"
                                                                   <?= $disAttr ?> <?= $zeroAttr ?>
                                                                   oninput="syncRowPriceFromDiscount(this)">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-7">
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">₹</span>
                                                            <input type="number" step="0.01" min="0"
                                                                   name="items[<?= $idx ?>][unit_price]"
                                                                   class="form-control bill-price-input fw-semibold"
                                                                   value="<?= number_format($unitVal, 2, '.', '') ?>"
                                                                   title="Unit Price Before Tax"
                                                                   <?= $disAttr ?> <?= $zeroAttr ?>
                                                                   oninput="syncRowDiscountFromPrice(this)">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row g-1 align-items-center mb-1">
                                                    <div class="col-7">
                                                        <select name="items[<?= $idx ?>][gst_type]" class="form-select form-select-sm bill-gst-type" <?= $disAttr ?> <?= $zeroAttr ?> onchange="toggleRowGstInputs(this); recalcBatchBillingTotals()">
                                                            <option value="BOTH" <?= $defaultGstType === 'BOTH' ? 'selected' : '' ?>>CGST+SGST</option>
                                                            <option value="CGST">CGST</option>
                                                            <option value="SGST">SGST</option>
                                                            <option value="IGST">IGST</option>
                                                            <option value="EXEMPTED" <?= $defaultGstType === 'EXEMPTED' ? 'selected' : '' ?>>Exempted</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-5">
                                                        <select name="items[<?= $idx ?>][tax_input_type]" class="form-select form-select-sm bill-tax-mode" <?= $disAttr ?> <?= $zeroAttr ?> onchange="recalcBatchBillingTotals()">
                                                            <option value="PERCENT" selected>%</option>
                                                            <option value="FIXED">₹/pc</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row g-1 mb-1">
                                                    <div class="col-4">
                                                        <input type="number" step="0.01" min="0" name="items[<?= $idx ?>][cgst_rate]" class="form-control form-control-sm bill-cgst-input" value="<?= number_format($defaultGstType === 'BOTH' ? $halfGst : 0, 2, '.', '') ?>" placeholder="C" title="CGST" <?= $disAttr ?> <?= $zeroAttr ?> oninput="recalcBatchBillingTotals()">
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="number" step="0.01" min="0" name="items[<?= $idx ?>][sgst_rate]" class="form-control form-control-sm bill-sgst-input" value="<?= number_format($defaultGstType === 'BOTH' ? $halfGst : 0, 2, '.', '') ?>" placeholder="S" title="SGST" <?= $disAttr ?> <?= $zeroAttr ?> oninput="recalcBatchBillingTotals()">
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="number" step="0.01" min="0" name="items[<?= $idx ?>][igst_rate]" class="form-control form-control-sm bill-igst-input" value="0.00" placeholder="I" title="IGST" <?= $disAttr ?> <?= $zeroAttr ?> oninput="recalcBatchBillingTotals()">
                                                    </div>
                                                </div>
                                                <div class="small text-end fw-bold text-primary">Line: ₹<span class="bill-line-total">0.00</span></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div id="batchBillingSummary" class="mt-auto p-3 bg-light border-top billing-col <?= $defaultBillingOn ? '' : 'd-none' ?>">
                        <div class="row g-2 text-center text-md-start small">
                            <div class="col-6 col-md-2">
                                <div class="text-muted">Gross MRP</div>
                                <div class="fw-semibold" id="sumGross">₹0.00</div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="text-muted">Discount</div>
                                <div class="fw-semibold text-success" id="sumDiscount">-₹0.00</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Taxable Subtotal</div>
                                <div class="fw-bold" id="sumSubtotal">₹0.00</div>
                            </div>
                            <div class="col-6 col-md-2">
                                <div class="text-muted">GST Tax</div>
                                <div class="fw-semibold" id="sumTax">₹0.00</div>
                            </div>
                            <div class="col-12 col-md-3 text-md-end">
                                <div class="text-muted">Sale Bill Total</div>
                                <div class="fs-5 fw-bold text-primary" id="sumGrandTotal">₹0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center border-top">
                    <a href="<?= url('production/batches') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </a>
                    <?php if ($sourceMode === 'qc' && $selectedQC && ($selectedQC['remaining_qty'] ?? 1) <= 0 && ($selectedQC['already_packed_qty'] ?? 0) > 0): ?>
                        <a href="<?= url('production/batches' . (!empty($existingBatches[0]['batch_number']) ? '?search=' . urlencode($existingBatches[0]['batch_number']) : '')) ?>" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-box-seam me-1"></i> View Finished Batch (<?= e($existingBatches[0]['batch_number'] ?? '') ?>)
                        </a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSavePackedBatch">
                            <i class="bi bi-check2-circle me-1"></i> <span id="btnSavePackedBatchLabel">Save Packed Batch</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.piece-tag-options {
    max-height: 180px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.25rem 0.5rem;
}

.piece-tag-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.2rem 0;
    cursor: pointer;
    overflow-wrap: anywhere;
}

.piece-tag-option:hover {
    background: #f8f9fa;
}
</style>

<script>
const BATCH_SCAN_URL = '<?= url('api/production/batches/search') ?>';
let scanCartState = {};
let scanRowSeq = 0;

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function handleQCBatchChange(qcBatchId) {
    if (qcBatchId) {
        window.location.href = '<?= url('production/batches/create') ?>?source_mode=qc&qc_batch_id=' + encodeURIComponent(qcBatchId);
    } else {
        window.location.href = '<?= url('production/batches/create?source_mode=qc') ?>';
    }
}

function handleInwardLocationChange(locationKey) {
    if (locationKey) {
        window.location.href = '<?= url('production/batches/create') ?>?source_mode=inward&source_location=' + encodeURIComponent(locationKey);
    } else {
        window.location.href = '<?= url('production/batches/create?source_mode=inward') ?>';
    }
}

function isBillingEnabled() {
    return document.querySelector('input[name="generate_sale_bill"]:checked')?.value === '1';
}

function updateSubmitButtonLabel() {
    const lbl = document.getElementById('btnSavePackedBatchLabel');
    if (!lbl) return;
    if (!isBillingEnabled()) {
        lbl.textContent = 'Save Packed Batch';
        return;
    }
    const dispatchChecked = document.getElementById('dispatchNowToggle')?.checked;
    lbl.textContent = dispatchChecked
        ? 'Pack, Dispatch Outward & Generate Sale Bill'
        : 'Pack & Generate Sale Bill';
}

function toggleBatchBillingMode() {
    const enabled = isBillingEnabled();
    document.getElementById('saleBillFieldsSection')?.classList.toggle('d-none', !enabled);
    document.querySelectorAll('.billing-col').forEach(el => {
        el.classList.toggle('d-none', !enabled);
        el.querySelectorAll('input, select, textarea').forEach(ctrl => {
            if (ctrl.getAttribute('data-zero-locked') === '1') {
                ctrl.disabled = true;
            } else {
                ctrl.disabled = !enabled;
            }
        });
    });
    document.querySelectorAll('.bill-gst-type').forEach(sel => toggleRowGstInputs(sel));
    updateSubmitButtonLabel();
    recalcBatchBillingTotals();
}

function toggleDispatchNowFields() {
    const on = Boolean(document.getElementById('dispatchNowToggle')?.checked);
    document.getElementById('dispatchNowFields')?.classList.toggle('d-none', !on);
    updateSubmitButtonLabel();
}

function toggleBatchBuyerType() {
    const type = document.querySelector('input[name="buyer_type"]:checked')?.value || 'COMPANY';
    document.getElementById('batchCompanyFields')?.classList.toggle('d-none', type !== 'COMPANY');
    document.getElementById('batchCustomerFields')?.classList.toggle('d-none', type !== 'CUSTOMER');
}

function toggleBatchPaymentSplit() {
    const method = document.getElementById('batch_payment_method')?.value || 'CREDIT';
    document.getElementById('batchSplitPaymentFields')?.classList.toggle('d-none', method !== 'CASH_UPI');
    document.getElementById('batchUpiRefField')?.classList.toggle('d-none', method !== 'UPI' && method !== 'CASH_UPI');
}

function calcPackedTotal() {
    let total = 0;
    document.querySelectorAll('.packed-input').forEach(inp => {
        if (!inp.disabled) {
            const val = parseInt(inp.value, 10);
            if (!isNaN(val) && val > 0) {
                total += val;
            }
        }
    });
    const badge = document.getElementById('totalPackedBadge');
    if (badge) {
        badge.innerText = total.toLocaleString() + ' pcs Packed';
    }
    return total;
}

function updatePieceTagCounts() {
    document.querySelectorAll('.variant-item-row').forEach(row => {
        const options = row.querySelector('.piece-tag-options');
        const countLabel = row.querySelector('.piece-tag-count');
        const quantityInput = row.querySelector('.packed-input');
        if (!options || !countLabel || !quantityInput) return;

        const selectedCount = options.querySelectorAll('.piece-tags-input:checked').length;
        countLabel.textContent = `${selectedCount} selected / ${quantityInput.value || 0} qty`;
    });
}

function syncPieceTagSelection(input) {
    if (!input) return;
    const row = input.closest('.variant-item-row');
    const options = row?.querySelector('.piece-tag-options');
    const quantityInput = row?.querySelector('.packed-input');
    if (!options || !quantityInput) return;

    quantityInput.value = options.querySelectorAll('.piece-tags-input:checked').length;
    quantityInput.classList.remove('is-invalid', 'border-danger');
    options.classList.remove('is-invalid', 'border-danger');
    updatePieceTagCounts();
    calcPackedTotal();
    recalcBatchBillingTotals();
}

function filterPieceTags(input) {
    const search = input.value.trim().toLowerCase();
    const options = input.closest('.piece-tag-picker')?.querySelectorAll('[data-piece-tag-option]') ?? [];
    options.forEach(option => {
        option.hidden = !option.textContent.toLowerCase().includes(search);
    });
}

function selectPieceTagsForQuantity(button) {
    const row = button.closest('.variant-item-row');
    const options = row?.querySelector('.piece-tag-options');
    const quantityInput = row?.querySelector('.packed-input');
    if (!options || !quantityInput) return;

    const quantity = Math.min(
        parseInt(quantityInput.max, 10) || 0,
        Math.max(0, parseInt(quantityInput.value, 10) || 0)
    );
    options.querySelectorAll('.piece-tags-input').forEach((checkbox, index) => {
        checkbox.checked = index < quantity;
    });
    syncPieceTagSelection(options.querySelector('.piece-tags-input'));
}

function clearPieceTags(button) {
    const row = button.closest('.variant-item-row');
    const options = row?.querySelector('.piece-tag-options');
    if (!options) return;

    options.querySelectorAll('.piece-tags-input').forEach(checkbox => {
        checkbox.checked = false;
    });
    syncPieceTagSelection(options.querySelector('.piece-tags-input'));
}

function syncRowPriceFromDiscount(discInput) {
    const row = discInput.closest('.billing-calc-row');
    if (!row) return;
    const mrp = parseFloat(row.getAttribute('data-mrp') || '0') || 0;
    const discPct = Math.max(0, Math.min(100, parseFloat(discInput.value) || 0));
    const priceInput = row.querySelector('.bill-price-input');
    if (priceInput && mrp > 0) {
        priceInput.value = (mrp * (1 - discPct / 100)).toFixed(2);
    }
    recalcBatchBillingTotals();
}

function syncRowDiscountFromPrice(priceInput) {
    const row = priceInput.closest('.billing-calc-row');
    if (!row) return;
    const mrp = parseFloat(row.getAttribute('data-mrp') || '0') || 0;
    const price = Math.max(0, parseFloat(priceInput.value) || 0);
    const discInput = row.querySelector('.bill-disc-input');
    if (discInput && mrp > 0) {
        const pct = price >= mrp ? 0 : ((mrp - price) / mrp) * 100;
        discInput.value = Math.max(0, Math.min(100, pct)).toFixed(2);
    }
    recalcBatchBillingTotals();
}

function toggleRowGstInputs(selectEl) {
    const row = selectEl?.closest('.billing-calc-row');
    if (!row) return;
    const type = selectEl.value;
    const rowLocked = selectEl.disabled;
    const cgst = row.querySelector('.bill-cgst-input');
    const sgst = row.querySelector('.bill-sgst-input');
    const igst = row.querySelector('.bill-igst-input');
    if (!cgst || !sgst || !igst) return;

    if (rowLocked || !isBillingEnabled()) {
        cgst.disabled = sgst.disabled = igst.disabled = true;
        return;
    }

    cgst.disabled = !(type === 'BOTH' || type === 'CGST');
    sgst.disabled = !(type === 'BOTH' || type === 'SGST');
    igst.disabled = !(type === 'IGST');
    if (cgst.disabled) cgst.value = '0.00';
    if (sgst.disabled) sgst.value = '0.00';
    if (igst.disabled) igst.value = '0.00';
}

function recalcBatchBillingTotals() {
    let totalGross = 0;
    let totalSubtotal = 0;
    let totalTax = 0;
    let totalGrand = 0;

    document.querySelectorAll('.billing-calc-row').forEach(row => {
        const qtyInput = row.querySelector('.packed-input');
        const qty = qtyInput && !qtyInput.disabled ? Math.max(0, parseInt(qtyInput.value, 10) || 0) : 0;
        const mrp = parseFloat(row.getAttribute('data-mrp') || '0') || 0;
        const price = Math.max(0, parseFloat(row.querySelector('.bill-price-input')?.value || '0') || 0);
        const gstType = row.querySelector('.bill-gst-type')?.value || 'EXEMPTED';
        const taxMode = row.querySelector('.bill-tax-mode')?.value || 'PERCENT';
        const cRate = Math.max(0, parseFloat(row.querySelector('.bill-cgst-input')?.value || '0') || 0);
        const sRate = Math.max(0, parseFloat(row.querySelector('.bill-sgst-input')?.value || '0') || 0);
        const iRate = Math.max(0, parseFloat(row.querySelector('.bill-igst-input')?.value || '0') || 0);

        const gross = Math.round(mrp * qty * 100) / 100;
        const taxable = Math.round(price * qty * 100) / 100;

        let activeRateSum = 0;
        if (gstType === 'BOTH') activeRateSum = cRate + sRate;
        else if (gstType === 'CGST') activeRateSum = cRate;
        else if (gstType === 'SGST') activeRateSum = sRate;
        else if (gstType === 'IGST') activeRateSum = iRate;

        let tax = 0;
        if (activeRateSum > 0 && qty > 0) {
            tax = taxMode === 'PERCENT'
                ? Math.round((taxable * activeRateSum / 100) * 100) / 100
                : Math.round(activeRateSum * qty * 100) / 100;
        }
        const lineTotal = Math.round((taxable + tax) * 100) / 100;

        const lineSpan = row.querySelector('.bill-line-total');
        if (lineSpan) lineSpan.textContent = lineTotal.toFixed(2);

        if (qty > 0) {
            totalGross += gross;
            totalSubtotal += taxable;
            totalTax += tax;
            totalGrand += lineTotal;
        }
    });

    const discount = Math.max(0, totalGross - totalSubtotal);
    const setTxt = (id, text) => {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    };
    setTxt('sumGross', '₹' + totalGross.toFixed(2));
    setTxt('sumDiscount', '-₹' + discount.toFixed(2));
    setTxt('sumSubtotal', '₹' + totalSubtotal.toFixed(2));
    setTxt('sumTax', '₹' + totalTax.toFixed(2));
    setTxt('sumGrandTotal', '₹' + totalGrand.toFixed(2));
    setTxt('totalBillBadge', '₹' + totalGrand.toFixed(2));
}

function getAllScannedTagsSet() {
    const set = new Set();
    Object.values(scanCartState).forEach(entry => {
        entry.tags.forEach(t => set.add(t.toUpperCase()));
    });
    return set;
}

function runBatchScanSearch(showAll = false) {
    const input = document.getElementById('scanSearchInput');
    const q = showAll ? '' : (input?.value.trim() || '');
    const box = document.getElementById('scanSearchResults');
    if ((!q && !showAll) || !box) {
        if (box) box.innerHTML = '';
        return;
    }
    if (showAll && input) {
        input.value = '';
    }
    box.innerHTML = '<div class="small text-muted py-1"><span class="spinner-border spinner-border-sm me-1"></span>Loading available inventory...</div>';

    const queryUrl = showAll
        ? (BATCH_SCAN_URL + '?all=1')
        : (BATCH_SCAN_URL + '?q=' + encodeURIComponent(q));

    fetch(queryUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(payload => {
            if (!payload.success) {
                throw new Error(payload.message || 'Search failed.');
            }
            const items = payload.data?.items || [];
            const already = getAllScannedTagsSet();

            // Exact single tag scan: add immediately without forcing another click
            if (q !== '') {
                const upperQ = q.toUpperCase();
                let exactItem = null;
                let exactTag = null;
                items.forEach(item => {
                    (item.available_tags || []).forEach(tag => {
                        if (String(tag).toUpperCase() === upperQ) {
                            exactItem = item;
                            exactTag = String(tag).toUpperCase();
                        }
                    });
                });

                if (exactItem && exactTag) {
                    if (!already.has(exactTag)) {
                        addScanTagsToCart(exactItem, [exactTag]);
                        input.value = '';
                        box.innerHTML = `<div class="alert alert-success py-1 px-2 small mb-0"><i class="bi bi-check-circle me-1"></i>Added QR <strong>${escapeHtml(exactTag)}</strong> (${escapeHtml(exactItem.product_name)})</div>`;
                    } else {
                        input.select();
                        box.innerHTML = `<div class="alert alert-warning py-1 px-2 small mb-0">QR <strong>${escapeHtml(exactTag)}</strong> is already in your pack list.</div>`;
                    }
                    return;
                }
            }

            if (items.length === 0) {
                const emptyText = showAll
                    ? 'No available unsold/undamaged QR pieces found in the active concern right now.'
                    : 'No available unsold/undamaged QR pieces matched your search in the active concern.';
                box.innerHTML = `<div class="alert alert-secondary py-2 px-3 small mb-0">${emptyText}</div>`;
                return;
            }

            window._scanSearchItems = items;
            let html = '<div class="list-group list-group-flush border rounded bg-white" style="max-height:240px;overflow-y:auto;">';
            items.forEach((item, idx) => {
                const freeTags = (item.available_tags || []).filter(t => !already.has(String(t).toUpperCase()));
                const price = parseFloat(item.unit_price || item.mrp || 0).toFixed(2);
                const locLabel = item.location_label ? ` &bull; ${escapeHtml(item.location_label)}` : '';
                html += `<div class="list-group-item py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <div>
                            <div class="fw-bold small">${escapeHtml(item.product_name)} — ${escapeHtml(item.size_name)} / ${escapeHtml(item.color_name)}</div>
                            <div class="text-muted" style="font-size:0.75rem;">SKU: <span class="font-monospace">${escapeHtml(item.sku)}</span> &bull; ₹${price}${locLabel}</div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-success-subtle text-success border">${freeTags.length} QR left</span>
                            ${freeTags.length > 0 ? `<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="addAllSearchTags(${idx})">Add All (${freeTags.length})</button>` : ''}
                        </div>
                    </div>`;
                if (freeTags.length > 0) {
                    html += '<div class="d-flex flex-wrap gap-1 mt-2">';
                    freeTags.slice(0, 20).forEach(tag => {
                        html += `<button type="button" class="btn btn-xs btn-outline-dark font-monospace py-0 px-2" style="font-size:0.75rem;" onclick="addSingleSearchTag(${idx}, '${escapeHtml(tag)}')">+ ${escapeHtml(tag)}</button>`;
                    });
                    if (freeTags.length > 20) {
                        html += `<span class="small text-muted align-self-center">+${freeTags.length - 20} more (use Add All or scan exact QR)</span>`;
                    }
                    html += '</div>';
                }
                html += '</div>';
            });
            html += '</div>';
            box.innerHTML = html;
        })
        .catch(err => {
            box.innerHTML = `<div class="alert alert-danger py-2 px-3 small mb-0">${escapeHtml(err.message)}</div>`;
        });
}

function addSingleSearchTag(itemIdx, tag) {
    const item = window._scanSearchItems?.[itemIdx];
    if (!item) return;
    addScanTagsToCart(item, [tag]);
    const hasQuery = Boolean(document.getElementById('scanSearchInput')?.value.trim());
    runBatchScanSearch(!hasQuery);
}

function addAllSearchTags(itemIdx) {
    const item = window._scanSearchItems?.[itemIdx];
    if (!item) return;
    const already = getAllScannedTagsSet();
    const free = (item.available_tags || []).filter(t => !already.has(String(t).toUpperCase()));
    if (free.length > 0) {
        addScanTagsToCart(item, free);
    }
    const hasQuery = Boolean(document.getElementById('scanSearchInput')?.value.trim());
    runBatchScanSearch(!hasQuery);
}

function addScanTagsToCart(item, tagsToAdd) {
    const key = String(item.item_key);
    const already = getAllScannedTagsSet();
    if (!scanCartState[key]) {
        const mrp = Math.max(0, parseFloat(item.mrp || item.unit_price || 0) || 0);
        const unitPrice = Math.max(0, parseFloat(item.unit_price || mrp || 0) || 0);
        const gstPct = Math.max(0, parseFloat(item.gst_percentage || 0) || 0);
        scanCartState[key] = {
            seq: ++scanRowSeq,
            item_key: key,
            qc_item_id: parseInt(item.qc_item_id || '0', 10) || 0,
            product_name: item.product_name || 'Item',
            size_name: item.size_name || '',
            color_name: item.color_name || '',
            sku: item.sku || '',
            source_kind: item.source_kind || 'QC',
            mrp: mrp > 0 ? mrp : unitPrice,
            unit_price: unitPrice,
            gst_percentage: gstPct,
            gst_type: gstPct > 0 ? 'BOTH' : 'EXEMPTED',
            tax_input_type: 'PERCENT',
            cgst_rate: gstPct > 0 ? +(gstPct / 2).toFixed(2) : 0,
            sgst_rate: gstPct > 0 ? +(gstPct / 2).toFixed(2) : 0,
            igst_rate: 0,
            tags: []
        };
    }
    tagsToAdd.forEach(rawTag => {
        const t = String(rawTag).toUpperCase().trim();
        if (t && !already.has(t)) {
            scanCartState[key].tags.push(t);
            already.add(t);
        }
    });
    renderScanCart();
}

function removeScanTag(itemKey, tag) {
    if (!scanCartState[itemKey]) return;
    scanCartState[itemKey].tags = scanCartState[itemKey].tags.filter(t => t !== tag);
    if (scanCartState[itemKey].tags.length === 0) {
        delete scanCartState[itemKey];
    }
    renderScanCart();
}

function removeScanGroup(itemKey) {
    delete scanCartState[itemKey];
    renderScanCart();
}

function syncScanRowFields(itemKey) {
    const entry = scanCartState[itemKey];
    const row = document.querySelector(`tr[data-scan-key="${CSS.escape(itemKey)}"]`);
    if (!entry || !row) return;
    entry.unit_price = Math.max(0, parseFloat(row.querySelector('.bill-price-input')?.value || '0') || 0);
    entry.gst_type = row.querySelector('.bill-gst-type')?.value || 'EXEMPTED';
    entry.tax_input_type = row.querySelector('.bill-tax-mode')?.value || 'PERCENT';
    entry.cgst_rate = Math.max(0, parseFloat(row.querySelector('.bill-cgst-input')?.value || '0') || 0);
    entry.sgst_rate = Math.max(0, parseFloat(row.querySelector('.bill-sgst-input')?.value || '0') || 0);
    entry.igst_rate = Math.max(0, parseFloat(row.querySelector('.bill-igst-input')?.value || '0') || 0);
}

function renderScanCart() {
    const tbody = document.getElementById('scanCartBody');
    if (!tbody) return;
    const keys = Object.keys(scanCartState);
    if (keys.length === 0) {
        tbody.innerHTML = `<tr id="scanCartEmptyRow">
            <td colspan="5" class="text-center text-muted py-5">
                <i class="bi bi-qr-code fs-1 d-block mb-2 opacity-50"></i>
                No QR pieces added yet. Scan a QR code or search inventory above.
            </td>
        </tr>`;
        calcPackedTotal();
        recalcBatchBillingTotals();
        return;
    }

    const billingOn = isBillingEnabled();
    const disAttr = billingOn ? '' : 'disabled';
    let html = '';
    keys.forEach((key, i) => {
        const entry = scanCartState[key];
        const discPct = entry.mrp > 0 && entry.unit_price < entry.mrp
            ? (((entry.mrp - entry.unit_price) / entry.mrp) * 100).toFixed(2)
            : '0.00';
        const tagBadges = entry.tags.map(t => `
            <span class="badge bg-light text-dark border font-monospace d-inline-flex align-items-center gap-1 me-1 mb-1">
                ${escapeHtml(t)}
                <input type="hidden" name="scan_items[${i}][tags][]" value="${escapeHtml(t)}">
                <button type="button" class="btn-close" style="font-size:0.55rem;" aria-label="Remove" onclick="removeScanTag('${escapeHtml(key)}', '${escapeHtml(t)}')"></button>
            </span>
        `).join('');

        html += `<tr class="variant-item-row billing-calc-row" data-scan-key="${escapeHtml(key)}" data-mrp="${entry.mrp.toFixed(2)}">
            <td>
                <input type="hidden" name="scan_items[${i}][item_key]" value="${escapeHtml(entry.item_key)}">
                <input type="hidden" name="scan_items[${i}][qc_item_id]" value="${entry.qc_item_id}">
                <div class="fw-bold text-dark">${escapeHtml(entry.product_name)}</div>
                <div class="small text-muted">${escapeHtml(entry.size_name)} / ${escapeHtml(entry.color_name)}</div>
                <small class="text-muted font-monospace">${escapeHtml(entry.sku)}</small>
                <span class="badge bg-secondary-subtle text-secondary ms-1">${escapeHtml(entry.source_kind)}</span>
            </td>
            <td>${tagBadges}</td>
            <td class="text-center">
                <input type="hidden" class="packed-input" value="${entry.tags.length}">
                <span class="badge bg-primary-subtle text-primary border px-2 py-1 fs-6">${entry.tags.length}</span>
            </td>
            <td class="billing-col ${billingOn ? '' : 'd-none'}">
                <div class="small text-muted mb-1">MRP: ₹${entry.mrp.toFixed(2)}</div>
                <div class="row g-1 mb-1">
                    <div class="col-5">
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" max="100" class="form-control bill-disc-input" value="${discPct}" ${disAttr} oninput="syncRowPriceFromDiscount(this); syncScanRowFields('${escapeHtml(key)}')">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-7">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" name="scan_items[${i}][unit_price]" class="form-control bill-price-input fw-semibold" value="${entry.unit_price.toFixed(2)}" ${disAttr} oninput="syncRowDiscountFromPrice(this); syncScanRowFields('${escapeHtml(key)}')">
                        </div>
                    </div>
                </div>
                <div class="row g-1 align-items-center mb-1">
                    <div class="col-7">
                        <select name="scan_items[${i}][gst_type]" class="form-select form-select-sm bill-gst-type" ${disAttr} onchange="toggleRowGstInputs(this); syncScanRowFields('${escapeHtml(key)}'); recalcBatchBillingTotals()">
                            <option value="BOTH" ${entry.gst_type === 'BOTH' ? 'selected' : ''}>CGST+SGST</option>
                            <option value="CGST" ${entry.gst_type === 'CGST' ? 'selected' : ''}>CGST</option>
                            <option value="SGST" ${entry.gst_type === 'SGST' ? 'selected' : ''}>SGST</option>
                            <option value="IGST" ${entry.gst_type === 'IGST' ? 'selected' : ''}>IGST</option>
                            <option value="EXEMPTED" ${entry.gst_type === 'EXEMPTED' ? 'selected' : ''}>Exempted</option>
                        </select>
                    </div>
                    <div class="col-5">
                        <select name="scan_items[${i}][tax_input_type]" class="form-select form-select-sm bill-tax-mode" ${disAttr} onchange="syncScanRowFields('${escapeHtml(key)}'); recalcBatchBillingTotals()">
                            <option value="PERCENT" ${entry.tax_input_type === 'PERCENT' ? 'selected' : ''}>%</option>
                            <option value="FIXED" ${entry.tax_input_type === 'FIXED' ? 'selected' : ''}>₹/pc</option>
                        </select>
                    </div>
                </div>
                <div class="row g-1 mb-1">
                    <div class="col-4"><input type="number" step="0.01" min="0" name="scan_items[${i}][cgst_rate]" class="form-control form-control-sm bill-cgst-input" value="${entry.cgst_rate.toFixed(2)}" ${disAttr} oninput="syncScanRowFields('${escapeHtml(key)}'); recalcBatchBillingTotals()"></div>
                    <div class="col-4"><input type="number" step="0.01" min="0" name="scan_items[${i}][sgst_rate]" class="form-control form-control-sm bill-sgst-input" value="${entry.sgst_rate.toFixed(2)}" ${disAttr} oninput="syncScanRowFields('${escapeHtml(key)}'); recalcBatchBillingTotals()"></div>
                    <div class="col-4"><input type="number" step="0.01" min="0" name="scan_items[${i}][igst_rate]" class="form-control form-control-sm bill-igst-input" value="${entry.igst_rate.toFixed(2)}" ${disAttr} oninput="syncScanRowFields('${escapeHtml(key)}'); recalcBatchBillingTotals()"></div>
                </div>
                <div class="small text-end fw-bold text-primary">Line: ₹<span class="bill-line-total">0.00</span></div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" title="Remove row" onclick="removeScanGroup('${escapeHtml(key)}')"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
    tbody.querySelectorAll('.bill-gst-type').forEach(sel => toggleRowGstInputs(sel));
    calcPackedTotal();
    recalcBatchBillingTotals();
}

document.addEventListener('DOMContentLoaded', function() {
    calcPackedTotal();
    updatePieceTagCounts();
    toggleBatchBillingMode();
    toggleBatchBuyerType();
    toggleBatchPaymentSplit();
    recalcBatchBillingTotals();

    const concernSelect = document.getElementById('destination_concern_id');
    const companyInput = document.getElementById('batch_company_name');
    if (concernSelect && companyInput) {
        concernSelect.addEventListener('change', function() {
            const optText = concernSelect.value ? concernSelect.options[concernSelect.selectedIndex].text.trim() : '';
            const curVal = companyInput.value.trim();
            if (!curVal || curVal === (companyInput.dataset.autoFilledName || '')) {
                companyInput.value = optText;
                companyInput.dataset.autoFilledName = optText;
            }
        });
    }

    const scanInput = document.getElementById('scanSearchInput');
    if (scanInput) {
        scanInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                runBatchScanSearch(false);
            }
        });
        runBatchScanSearch(true);
    }

    const form = document.getElementById('packBatchForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const sourceModeVal = document.getElementById('source_mode')?.value || 'qc';
            const qcBatchSelect = document.getElementById('qc_batch_id');
            const sourceLocSelect = document.getElementById('source_location');
            const concernSelectEl = document.getElementById('destination_concern_id');
            const boxCountInput = document.getElementById('box_count');

            if (sourceModeVal === 'inward') {
                if (!sourceLocSelect || !sourceLocSelect.value) {
                    e.preventDefault();
                    alert('Please select an Inward Inventory Source first.');
                    sourceLocSelect?.focus();
                    return false;
                }
            } else if (sourceModeVal === 'qc') {
                if (!qcBatchSelect || !qcBatchSelect.value) {
                    e.preventDefault();
                    alert('Please select a QC Inspection Batch first.');
                    qcBatchSelect?.focus();
                    return false;
                }
            }

            if (!concernSelectEl || !concernSelectEl.value) {
                e.preventDefault();
                alert('Please select a Destination Concern.');
                concernSelectEl?.focus();
                return false;
            }

            const boxVal = parseInt(boxCountInput?.value, 10);
            if (!boxCountInput || isNaN(boxVal) || boxVal < 1) {
                e.preventDefault();
                alert('Total Box / Carton Count is required and must be at least 1.');
                boxCountInput?.focus();
                return false;
            }

            if (sourceModeVal === 'scan') {
                const scanTotal = calcPackedTotal();
                if (scanTotal <= 0) {
                    e.preventDefault();
                    alert('Scan and add at least 1 available QR code to pack.');
                    document.getElementById('scanSearchInput')?.focus();
                    return false;
                }
            } else {
                const packedInputs = document.querySelectorAll('.packed-input');
                if (packedInputs.length === 0) {
                    e.preventDefault();
                    alert('No variant items available to pack.');
                    return false;
                }
                let totalPacked = 0;
                let hasError = false;
                packedInputs.forEach(inp => {
                    if (inp.disabled) return;
                    const tr = inp.closest('.variant-item-row');
                    const variantLabel = tr ? tr.getAttribute('data-variant-name') : 'Variant';
                    const maxPassed = tr ? parseInt(tr.getAttribute('data-passed'), 10) : 0;
                    const val = parseInt(inp.value, 10);
                    if (isNaN(val) || val < 0 || val > maxPassed) {
                        e.preventDefault();
                        alert(`Invalid quantity for ${variantLabel} (0 to ${maxPassed} pcs).`);
                        inp.focus();
                        hasError = true;
                        return;
                    }
                    const pieceTagOptions = tr ? tr.querySelector('.piece-tag-options') : null;
                    const selectedCount = pieceTagOptions ? pieceTagOptions.querySelectorAll('.piece-tags-input:checked').length : 0;
                    if (pieceTagOptions && selectedCount !== val) {
                        e.preventDefault();
                        alert(`Select exactly ${val} QR code(s) for ${variantLabel}.`);
                        hasError = true;
                        return;
                    }
                    if (isBillingEnabled() && val > 0 && !pieceTagOptions) {
                        e.preventDefault();
                        alert(`Sale Bill generation requires individual piece QR codes for ${variantLabel}.`);
                        hasError = true;
                        return;
                    }
                    totalPacked += val;
                });
                if (hasError) return false;
                if (totalPacked <= 0) {
                    e.preventDefault();
                    alert('Please enter a packed quantity greater than 0 for at least one available variant item.');
                    return false;
                }
            }

            if (isBillingEnabled()) {
                const buyerType = document.querySelector('input[name="buyer_type"]:checked')?.value || 'COMPANY';
                if (buyerType === 'COMPANY') {
                    const compName = document.getElementById('batch_company_name')?.value.trim();
                    if (!compName) {
                        e.preventDefault();
                        alert('Enter Company Name for the Sale Bill.');
                        document.getElementById('batch_company_name')?.focus();
                        return false;
                    }
                } else {
                    const custName = document.getElementById('batch_customer_name')?.value.trim();
                    const custPhone = document.getElementById('batch_customer_phone')?.value.trim();
                    if (!custName || !custPhone) {
                        e.preventDefault();
                        alert('Enter Customer Name and Phone for the Sale Bill.');
                        document.getElementById('batch_customer_name')?.focus();
                        return false;
                    }
                }

                const method = document.getElementById('batch_payment_method')?.value || 'CREDIT';
                if (method === 'CASH_UPI') {
                    const cashVal = parseFloat(document.getElementById('batch_cash_amount')?.value || '0') || 0;
                    const upiVal = parseFloat(document.getElementById('batch_upi_amount')?.value || '0') || 0;
                    const grand = parseFloat((document.getElementById('sumGrandTotal')?.textContent || '0').replace(/[^0-9.]/g, '')) || 0;
                    if (Math.abs((cashVal + upiVal) - grand) > 0.05) {
                        e.preventDefault();
                        alert(`Cash (₹${cashVal.toFixed(2)}) + UPI (₹${upiVal.toFixed(2)}) must equal the Sale Bill total (₹${grand.toFixed(2)}).`);
                        return false;
                    }
                }

                if (document.getElementById('dispatchNowToggle')?.checked) {
                    const veh = document.getElementById('vehicle_number')?.value.trim();
                    const drv = document.getElementById('driver_name')?.value.trim();
                    const ph = (document.getElementById('driver_contact')?.value || '').replace(/[^0-9]/g, '');
                    const trp = document.getElementById('transporter_name')?.value.trim();
                    const dt = document.getElementById('dispatch_date')?.value;
                    if (!veh || !drv || ph.length !== 10 || !trp || !dt) {
                        e.preventDefault();
                        alert('Fill in vehicle number, driver name, 10-digit driver phone, transporter, and dispatch date to dispatch outward immediately.');
                        return false;
                    }
                }
            }

            return true;
        });
    }
});
</script>
