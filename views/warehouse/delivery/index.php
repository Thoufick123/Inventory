<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 text-gray-800"><i class="bi bi-truck me-2 text-primary"></i>Delivery & Dispatch Pipeline Tracker</h2>
        <p class="text-muted small mb-0">Unified dispatch pipeline across Shop Allocations, Production Outwards, and Inter-Warehouse Transfers.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Reference Pass</th>
                    <th>Origin (Source)</th>
                    <th>Destination Channel</th>
                    <th>Item Volume</th>
                    <th>Pipeline Status</th>
                    <th>Created At</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deliveries)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No active deliveries in transit.</td></tr>
                <?php else: foreach ($deliveries as $del): ?>
                    <?php
                    $canTrackDelivery = match ($del['type']) {
                        'Shop Allocation' => hasPermission('allocation', 'view'),
                        'Production Outward' => hasPermission('inward', 'view'),
                        'Inter-Warehouse Transfer' => hasPermission('warehouse', 'view'),
                        default => false,
                    };
                    ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($del['type']) ?></span></td>
                        <td class="fw-bold text-primary"><?= htmlspecialchars($del['reference_number']) ?></td>
                        <td><?= htmlspecialchars($del['source']) ?></td>
                        <td><?= htmlspecialchars($del['destination']) ?></td>
                        <td><?= number_format($del['item_count']) ?> pcs</td>
                        <td>
                            <?php
                            $stClass = match($del['status']) {
                                'APPROVED' => 'bg-info text-dark',
                                'PICKING', 'PACKING' => 'bg-primary',
                                'DISPATCHED', 'IN_TRANSIT' => 'bg-warning text-dark',
                                'COMPLETED', 'RECEIVED' => 'bg-success',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $stClass ?>"><?= htmlspecialchars($del['status']) ?></span>
                        </td>
                        <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime($del['created_at'])) ?></small></td>
                        <td class="text-end">
                            <?php if ($canTrackDelivery): ?>
                            <a href="<?= url($del['action_url']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-right-circle me-1"></i> Track
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
