<?php
// views/whatsapp_orders/damage/outward_list.php — WhatsApp Damaged Stock Outward Batches
?>

<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/whatsapp-orders') ?>">WhatsApp Orders</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('warehouse/whatsapp-orders/damage') ?>">Damage Management</a></li>
                    <li class="breadcrumb-item active">Outward Batches</li>
                </ol>
            </nav>
            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-up-right text-danger"></i> Damaged Stock Outward Batches
            </h4>
            <div class="text-muted small">Batches of damaged stock dispatched to central warehouse for inspection and write-off.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('warehouse/whatsapp-orders/damage') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-exclamation-octagon me-1"></i> Damaged Items List
            </a>
            <a href="<?= url('warehouse/whatsapp-orders/damage/outward/create') ?>" class="btn btn-danger">
                <i class="bi bi-plus-circle me-1"></i> New Outward Batch
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-white-50 text-uppercase fw-semibold">Total Outward Batches</div>
                            <div class="fs-4 fw-bold"><?= number_format($kpi['total_batches'] ?? count($batches ?? [])) ?></div>
                        </div>
                        <i class="bi bi-boxes fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-dark-50 text-uppercase fw-semibold">Pending / In Transit</div>
                            <div class="fs-4 fw-bold"><?= number_format($kpi['in_transit'] ?? 0) ?></div>
                        </div>
                        <i class="bi bi-truck fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-white-50 text-uppercase fw-semibold">Received at Warehouse</div>
                            <div class="fs-4 fw-bold"><?= number_format($kpi['completed'] ?? 0) ?></div>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-white-50 text-uppercase fw-semibold">Total Pieces Dispatched</div>
                            <div class="fs-4 fw-bold"><?= number_format($kpi['total_pieces'] ?? 0) ?> pcs</div>
                        </div>
                        <i class="bi bi-stack fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Batches Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h6 class="card-title mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-danger"></i>Dispatched Return Batches</h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($batches)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <h6>No Damaged Stock Outward Batches Created Yet</h6>
                    <p class="small">When you have damaged stock in the WhatsApp section, bundle and dispatch them here.</p>
                    <a href="<?= url('warehouse/whatsapp-orders/damage/outward/create') ?>" class="btn btn-sm btn-danger mt-2">
                        <i class="bi bi-plus-circle me-1"></i> Create First Outward Batch
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Return Batch No.</th>
                                <th>Dispatch Date</th>
                                <th>Destination Warehouse</th>
                                <th>Transport / Vehicle</th>
                                <th class="text-center">Total Quantity</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batches as $batch): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($batch['return_number'] ?? ('RET-WA-' . $batch['id'])) ?></div>
                                        <small class="text-muted">By <?= htmlspecialchars($batch['created_by_name'] ?? 'User') ?></small>
                                    </td>
                                    <td>
                                        <div><?= date('M d, Y', strtotime($batch['created_at'])) ?></div>
                                        <small class="text-muted"><?= date('h:i A', strtotime($batch['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($batch['warehouse_name'] ?? 'Warehouse #' . $batch['warehouse_id']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($batch['warehouse_code'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($batch['vehicle_no'])): ?>
                                            <div><i class="bi bi-truck me-1 text-muted"></i><code><?= htmlspecialchars($batch['vehicle_no']) ?></code></div>
                                        <?php endif; ?>
                                        <small class="text-muted"><?= htmlspecialchars($batch['carrier_name'] ?? 'Direct Transfer') ?></small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger fs-6"><?= number_format($batch['total_quantity'] ?? $batch['items_count'] ?? 0) ?> pcs</span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $status = $batch['status'] ?? 'IN_TRANSIT';
                                        $badgeClass = match($status) {
                                            'PENDING', 'REQUESTED' => 'bg-warning text-dark',
                                            'IN_TRANSIT', 'APPROVED' => 'bg-primary text-white',
                                            'RECEIVED', 'COMPLETED' => 'bg-success text-white',
                                            'REJECTED' => 'bg-danger text-white',
                                            default => 'bg-secondary text-white'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> px-2 py-1"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url('warehouse/whatsapp-orders/damage/outward/view/' . $batch['id']) ?>" class="btn btn-outline-secondary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= url('warehouse/whatsapp-orders/damage/outward/gatepass/' . $batch['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print Gate Pass">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </a>
                                            <a href="<?= url('warehouse/whatsapp-orders/damage/outward/label/' . $batch['id']) ?>" target="_blank" class="btn btn-outline-danger" title="Print Carton QR Label">
                                                <i class="bi bi-qr-code"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
