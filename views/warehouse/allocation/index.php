<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 text-gray-800">
            <i class="bi bi-truck-flatbed text-primary me-2"></i>
            Warehouse Stock Allocations
        </h2>
        <p class="text-muted small mb-0">
            Direct stock allocations, warehouse transfers, and distribution.
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('allocation', 'create')): ?>
        <a href="<?= url('warehouse/allocation/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Allocation
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('warehouse/allocation') ?>" class="row g-2">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search allocation number or shop..." value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">-- All Statuses --</option>
                    <option value="REQUESTED" <?= ($currentStatus ?? '') === 'REQUESTED' ? 'selected' : '' ?>>REQUESTED</option>
                    <option value="APPROVED" <?= ($currentStatus ?? '') === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                    <option value="PICKING" <?= ($currentStatus ?? '') === 'PICKING' ? 'selected' : '' ?>>PICKING</option>
                    <option value="PACKING" <?= ($currentStatus ?? '') === 'PACKING' ? 'selected' : '' ?>>PACKING</option>
                    <option value="DISPATCHED" <?= ($currentStatus ?? '') === 'DISPATCHED' ? 'selected' : '' ?>>DISPATCHED</option>
                    <option value="COMPLETED" <?= ($currentStatus ?? '') === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Allocation #</th>
                    <th>Warehouse</th>
                    <th>Destination Channel</th>
                    <th>Requested Qty</th>
                    <th>Status</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allocations)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>No allocation requisitions found.</td></tr>
                <?php else: foreach ($allocations as $alc): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary font-monospace">
                            <?= htmlspecialchars($alc['allocation_number']) ?>
                            <?php if (!empty($alc['created_at'])): ?>
                                <div class="text-muted fw-normal small" style="font-size: 0.72rem;"><?= date('d M Y', strtotime($alc['created_at'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold text-dark"><?= htmlspecialchars($alc['warehouse_name']) ?></td>
                        <td>
                            <?php if (!empty($alc['shop_name']) || $alc['destination_type'] === 'SHOP' || str_starts_with((string)$alc['destination_type'], 'SHOP_')): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1"><i class="bi bi-shop me-1"></i>SHOP</span>
                                <strong><?= htmlspecialchars($alc['shop_name'] ?? ('Shop #' . ($alc['shop_id'] ?? $alc['destination_id']))) ?></strong>
                            <?php elseif (!empty($alc['dest_warehouse_name']) || $alc['destination_type'] === 'WAREHOUSE' || str_starts_with((string)$alc['destination_type'], 'WAREHOUSE_')): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle me-1"><i class="bi bi-building me-1"></i>WAREHOUSE</span>
                                <strong><?= htmlspecialchars($alc['dest_warehouse_name'] ?? ('Warehouse #' . $alc['destination_id'])) ?></strong>
                            <?php else: ?>
                                <span class="badge me-1" style="color: #6f42c1; background-color: #f3f0ff !important; border: 1px solid #d8b4fe;"><i class="bi bi-diagram-3 me-1"></i>CHANNEL</span>
                                <strong><?= htmlspecialchars($alc['channel_name'] ?? ($alc['destination_name'] ?? ($alc['channel_id'] ?? $alc['destination_id'] ? 'Channel #' . ($alc['channel_id'] ?? $alc['destination_id']) : $alc['destination_type']))) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary-subtle text-dark font-monospace fs-6 px-2.5 py-1"><?= number_format($alc['total_requested_qty']) ?> pcs</span></td>
                        <td>
                            <?php
                            $badgeClass = match($alc['status']) {
                                'REQUESTED' => 'bg-warning text-dark',
                                'APPROVED' => 'bg-info text-dark',
                                'PICKING', 'PACKING' => 'bg-primary text-white',
                                'DISPATCHED' => 'bg-success text-white',
                                'COMPLETED' => 'bg-dark text-white',
                                default => 'bg-secondary text-white'
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?> px-2.5 py-1"><?= htmlspecialchars($alc['status']) ?></span>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if ($alc['status'] === 'REQUESTED' && hasPermission('allocation', 'approve')): ?>
                                <form method="POST" action="<?= url('warehouse/allocation/approve/' . $alc['id']) ?>" class="d-inline">
                                    <?= CSRF::field() ?>
                                    <button type="submit" class="btn btn-sm btn-success me-1">
                                        <i class="bi bi-check-lg me-1"></i> Approve
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($alc['status'], ['APPROVED', 'PACKING', 'PICKING']) && hasPermission('allocation_outward', 'create')): ?>
                                <a href="<?= url('warehouse/allocation/outward') ?>" class="btn btn-sm btn-primary me-1">
                                    <i class="bi bi-truck me-1"></i> Dispatch Outward
                                </a>
                            <?php endif; ?>

                            <a href="<?= url('warehouse/allocation/view/' . $alc['id']) ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
