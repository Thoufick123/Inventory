<?php
// views/warehouse/shop_requests/index.php
$activeTab = $activeTab ?? 'shop';
$canViewAllocations = hasPermission('allocation', 'view');
$canPrintGatepass = hasPermission('allocation_outward', 'gatepass');
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h2 class="h3 mb-1 text-gray-800">
            <i class="bi bi-inboxes text-primary me-2"></i>Stock Requests
        </h2>
        <p class="text-muted small mb-0">
            Unified pipeline for Retail Shop Indents, WhatsApp Channel Requisitions, and Online Website Store Orders.
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php if ($canViewAllocations): ?>
            <a href="<?= url('warehouse/allocation') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-boxes me-1"></i> All Allocations
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Requisition Volume & Revenue Cards (Row 1) -->
<div class="row g-3 mb-3">
    <!-- Shop Requests Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 0.75rem;">Shop Stock Requisitions</span>
                        <h3 class="mb-0 mt-1 fw-bold text-primary"><?= number_format($shopStats['total_count'] ?? 0) ?></h3>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <small class="text-muted fw-semibold"><?= number_format($shopStats['total_pcs'] ?? 0) ?> pcs requested</small>
                            <?php if (($shopStats['total_value'] ?? 0) > 0): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">₹<?= number_format($shopStats['total_value'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="rounded-circle bg-primary-subtle p-3 text-primary fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Requests Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-success border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 0.75rem;">WhatsApp Requisitions</span>
                        <h3 class="mb-0 mt-1 fw-bold text-success"><?= number_format($whatsappStats['total_count'] ?? 0) ?></h3>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <small class="text-muted fw-semibold"><?= number_format($whatsappStats['total_pcs'] ?? 0) ?> pcs requested</small>
                            <?php if (($whatsappStats['total_value'] ?? 0) > 0): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">₹<?= number_format($whatsappStats['total_value'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="rounded-circle bg-success-subtle p-3 text-success fs-3 d-flex align-items-center justify-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-whatsapp"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Website Requests Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-info border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 0.75rem;">Website Online Requisitions</span>
                        <h3 class="mb-0 mt-1 fw-bold text-info-emphasis"><?= number_format($websiteStats['total_count'] ?? 0) ?></h3>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <small class="text-muted fw-semibold"><?= number_format($websiteStats['total_pcs'] ?? 0) ?> pcs requested</small>
                            <?php if (($websiteStats['total_value'] ?? 0) > 0): ?>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace">₹<?= number_format($websiteStats['total_value'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="rounded-circle bg-info-subtle p-3 text-info fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-globe"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Action Cards (Row 2) -->
<div class="row g-3 mb-4">
    <!-- Shop Pending Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 0.75rem;">Shop Pending Requests</span>
                        <h4 class="mb-0 mt-1 fw-bold text-warning"><?= number_format($shopStats['pending_count'] ?? 0) ?></h4>
                        <small class="text-warning fw-semibold"><i class="bi bi-hourglass-split me-1"></i>Awaiting warehouse action</small>
                    </div>
                    <div class="text-warning opacity-75 fs-2"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Pending Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 0.75rem;">WhatsApp Pending Requests</span>
                        <h4 class="mb-0 mt-1 fw-bold text-warning"><?= number_format($whatsappStats['pending_count'] ?? 0) ?></h4>
                        <small class="text-warning fw-semibold"><i class="bi bi-hourglass-split me-1"></i>Awaiting fulfillment</small>
                    </div>
                    <div class="text-warning opacity-75 fs-2"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Website Pending Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block" style="font-size: 0.75rem;">Website Pending Requests</span>
                        <h4 class="mb-0 mt-1 fw-bold text-warning"><?= number_format($websiteStats['pending_count'] ?? 0) ?></h4>
                        <small class="text-warning fw-semibold"><i class="bi bi-hourglass-split me-1"></i>Awaiting fulfillment</small>
                    </div>
                    <div class="text-warning opacity-75 fs-2"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Form -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('warehouse/shop-requests') ?>" id="stockRequestFilterForm" class="row g-2 align-items-center">
            <input type="hidden" name="tab" id="activeTabInput" value="<?= htmlspecialchars($activeTab) ?>">
            
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search Request #, Store/Channel, Notes..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- All Statuses --</option>
                    <option value="REQUESTED" <?= ($_GET['status'] ?? '') === 'REQUESTED' ? 'selected' : '' ?>>REQUESTED (Pending)</option>
                    <option value="APPROVED" <?= ($_GET['status'] ?? '') === 'APPROVED' ? 'selected' : '' ?>>APPROVED</option>
                    <option value="PICKING" <?= ($_GET['status'] ?? '') === 'PICKING' ? 'selected' : '' ?>>PICKING</option>
                    <option value="PACKING" <?= ($_GET['status'] ?? '') === 'PACKING' ? 'selected' : '' ?>>PACKING</option>
                    <option value="DISPATCHED" <?= ($_GET['status'] ?? '') === 'DISPATCHED' ? 'selected' : '' ?>>DISPATCHED</option>
                    <option value="COMPLETED" <?= ($_GET['status'] ?? '') === 'COMPLETED' ? 'selected' : '' ?>>COMPLETED / RECEIVED</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="destination_id" class="form-select">
                    <option value="">-- All Destinations --</option>
                    <optgroup label="Retail Stores">
                        <?php foreach (($shops ?? []) as $s): ?>
                            <option value="shop_<?= $s['id'] ?>" <?= (($_GET['destination_id'] ?? '') === 'shop_' . $s['id'] || ($_GET['shop_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                                🏬 <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Sales Channels">
                        <?php foreach (($channels ?? []) as $c): ?>
                            <option value="chan_<?= $c['id'] ?>" <?= (($_GET['destination_id'] ?? '') === 'chan_' . $c['id']) ? 'selected' : '' ?>>
                                <?= ($c['channel_type'] === 'WHATSAPP') ? '💬' : '🌐' ?> <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="<?= url('warehouse/shop-requests?tab=' . $activeTab) ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Dynamic Channel Tabs Navigation -->
<ul class="nav nav-pills nav-fill bg-white p-2 rounded-3 mb-4 shadow-sm border" id="requestChannelTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link text-start py-2.5 px-3 fw-bold <?= $activeTab === 'shop' ? 'active' : '' ?>" 
                id="tab-btn-shop" 
                data-bs-toggle="tab" 
                data-bs-target="#tab-shop" 
                type="button" 
                role="tab" 
                onclick="switchTabParam('shop')">
            <div class="d-flex align-items-center justify-content-between">
                <span><i class="bi bi-shop me-2 text-primary"></i>Shop Stock Requisitions</span>
                <span class="badge <?= $activeTab === 'shop' ? 'bg-white text-primary' : 'bg-primary-subtle text-primary border border-primary-subtle' ?> rounded-pill px-2.5">
                    <?= count($shopRequests ?? []) ?>
                </span>
            </div>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-start py-2.5 px-3 fw-bold <?= $activeTab === 'whatsapp' ? 'active' : '' ?>" 
                id="tab-btn-whatsapp" 
                data-bs-toggle="tab" 
                data-bs-target="#tab-whatsapp" 
                type="button" 
                role="tab" 
                onclick="switchTabParam('whatsapp')">
            <div class="d-flex align-items-center justify-content-between">
                <span><i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp Stock Requisitions</span>
                <span class="badge <?= $activeTab === 'whatsapp' ? 'bg-white text-success' : 'bg-success-subtle text-success border border-success-subtle' ?> rounded-pill px-2.5">
                    <?= count($whatsappRequests ?? []) ?>
                </span>
            </div>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-start py-2.5 px-3 fw-bold <?= $activeTab === 'website' ? 'active' : '' ?>" 
                id="tab-btn-website" 
                data-bs-toggle="tab" 
                data-bs-target="#tab-website" 
                type="button" 
                role="tab" 
                onclick="switchTabParam('website')">
            <div class="d-flex align-items-center justify-content-between">
                <span><i class="bi bi-globe me-2 text-info"></i>Website Stock Requisitions</span>
                <span class="badge <?= $activeTab === 'website' ? 'bg-white text-info' : 'bg-info-subtle text-info-emphasis border border-info-subtle' ?> rounded-pill px-2.5">
                    <?= count($websiteRequests ?? []) ?>
                </span>
            </div>
        </button>
    </li>
</ul>

<!-- Tabs Content Container -->
<div class="tab-content" id="requestChannelTabsContent">
    
    <!-- ========================================================================= -->
    <!-- TAB 1: SHOP STOCK REQUISITIONS -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade <?= $activeTab === 'shop' ? 'show active' : '' ?>" id="tab-shop" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-shop me-2 text-primary"></i>Shop Stock Requisitions List
                </h6>
                <span class="badge bg-light text-dark border font-monospace">
                    <?= count($shopRequests ?? []) ?> records
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Request #</th>
                            <th>Destination Retail Store</th>
                            <th>Source Warehouse</th>
                            <th>Requested Qty</th>
                            <th>Dispatched Qty</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shopRequests)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-shop fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <h6 class="fw-semibold text-dark">No Shop Requests Found</h6>
                                    <p class="small text-muted mb-3">No store stock requisitions match the selected criteria.</p>
                                    <?php if ($canCreateShopRequest): ?>
                                        <a href="<?= url('warehouse/shop-requests/create') ?>" class="btn btn-primary btn-sm px-3">
                                            <i class="bi bi-plus-lg me-1"></i> Create First Shop Request
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: foreach ($shopRequests as $r): ?>
                            <?php 
                                $statusClass = match($r['status'] ?? 'REQUESTED') {
                                    'REQUESTED' => 'bg-warning text-dark',
                                    'APPROVED'  => 'bg-info text-dark',
                                    'PICKING', 'PACKING' => 'bg-primary text-white',
                                    'DISPATCHED', 'COMPLETED', 'DELIVERED', 'RECEIVED' => 'bg-success text-white',
                                    default => 'bg-secondary text-white'
                                };
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold font-monospace">
                                    <a href="<?= url('warehouse/shop-requests/view/' . $r['id']) ?>" class="text-decoration-none text-primary">
                                        <?= htmlspecialchars($r['allocation_number']) ?>
                                    </a>
                                    <?php if (!empty($r['created_at'])): ?>
                                        <div class="text-muted fw-normal small" style="font-size: 0.72rem;"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small font-monospace">
                                            <i class="bi bi-shop me-1"></i><?= htmlspecialchars($r['shop_code'] ?? 'SHOP') ?>
                                        </span>
                                        <strong class="text-dark"><?= htmlspecialchars($r['shop_name'] ?? 'Retail Store') ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($r['warehouse_name'] ?? 'Central Warehouse') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace fs-7 px-2.5 py-1">
                                        <?= number_format($r['total_requested_qty'] ?? 0) ?> pcs
                                    </span>
                                    <?php if (!empty($r['item_count'])): ?>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><?= (int)$r['item_count'] ?> item style(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($r['total_dispatched_qty'] ?? 0) > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fs-7 px-2.5 py-1">
                                            <?= number_format($r['total_dispatched_qty']) ?> pcs
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">0 pcs</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-dark fw-semibold d-block"><?= htmlspecialchars($r['requested_by_name'] ?? 'Store Staff') ?></small>
                                    <?php if (!empty($r['notes'])): ?>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 140px;" title="<?= htmlspecialchars($r['notes']) ?>"><?= htmlspecialchars($r['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?> px-2.5 py-1 small fw-semibold">
                                        <?= htmlspecialchars($r['status']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end text-nowrap">
                                    <a href="<?= url('warehouse/shop-requests/view/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary px-2.5 py-1" title="View Shop Request Details">
                                        <i class="bi bi-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: WHATSAPP STOCK REQUISITIONS -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade <?= $activeTab === 'whatsapp' ? 'show active' : '' ?>" id="tab-whatsapp" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp Stock Requisitions List
                </h6>
                <span class="badge bg-light text-dark border font-monospace">
                    <?= count($whatsappRequests ?? []) ?> records
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Request / Requisition #</th>
                            <th>Destination WhatsApp Channel</th>
                            <th>Source Warehouse</th>
                            <th>Requested Qty</th>
                            <th>Dispatched Qty</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($whatsappRequests)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-whatsapp fs-1 d-block mb-2 text-success opacity-50"></i>
                                    <h6 class="fw-semibold text-dark">No WhatsApp Requests Found</h6>
                                    <p class="small text-muted mb-3">No WhatsApp channel stock requisitions match the selected criteria.</p>
                                    <?php if ($canCreateWhatsappRequest): ?>
                                        <a href="<?= url('warehouse/whatsapp-orders/requests/create') ?>" class="btn btn-success btn-sm px-3">
                                            <i class="bi bi-plus-lg me-1"></i> Create WhatsApp Request
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: foreach ($whatsappRequests as $r): ?>
                            <?php 
                                $statusClass = match($r['status'] ?? 'REQUESTED') {
                                    'REQUESTED' => 'bg-warning text-dark',
                                    'APPROVED'  => 'bg-info text-dark',
                                    'PICKING', 'PACKING' => 'bg-primary text-white',
                                    'DISPATCHED', 'COMPLETED', 'DELIVERED', 'RECEIVED' => 'bg-success text-white',
                                    default => 'bg-secondary text-white'
                                };
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold font-monospace">
                                    <?php if ($canViewAllocations): ?>
                                        <a href="<?= url('warehouse/allocation/view/' . $r['id'] . '?from=requests') ?>" class="text-decoration-none text-success">
                                            <?= htmlspecialchars($r['allocation_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($r['allocation_number']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($r['created_at'])): ?>
                                        <div class="text-muted fw-normal small" style="font-size: 0.72rem;"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle small font-monospace">
                                            <i class="bi bi-whatsapp me-1"></i><?= htmlspecialchars($r['channel_code'] ?? 'WA') ?>
                                        </span>
                                        <strong class="text-dark"><?= htmlspecialchars($r['channel_name'] ?? 'WhatsApp Channel') ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($r['warehouse_name'] ?? 'Central Warehouse') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace fs-7 px-2.5 py-1">
                                        <?= number_format($r['total_requested_qty'] ?? 0) ?> pcs
                                    </span>
                                    <?php if (!empty($r['item_count'])): ?>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><?= (int)$r['item_count'] ?> item style(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($r['total_dispatched_qty'] ?? 0) > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fs-7 px-2.5 py-1">
                                            <?= number_format($r['total_dispatched_qty']) ?> pcs
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">0 pcs</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-dark fw-semibold d-block"><?= htmlspecialchars($r['requested_by_name'] ?? 'Channel Manager') ?></small>
                                    <?php if (!empty($r['notes'])): ?>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 140px;" title="<?= htmlspecialchars($r['notes']) ?>"><?= htmlspecialchars($r['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?> px-2.5 py-1 small fw-semibold">
                                        <?= htmlspecialchars($r['status']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($canViewAllocations): ?>
                                            <a href="<?= url('warehouse/allocation/view/' . $r['id'] . '?from=requests') ?>" class="btn btn-outline-success px-2.5 py-1" title="View Requisition Details">
                                                <i class="bi bi-eye me-1"></i> View
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($canPrintGatepass && in_array($r['status'], ['DISPATCHED', 'COMPLETED', 'DELIVERED', 'RECEIVED'])): ?>
                                            <a href="<?= url('warehouse/allocation/gatepass/' . $r['id']) ?>" target="_blank" class="btn btn-outline-secondary px-2 py-1" title="Print Gate Pass">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: WEBSITE STOCK REQUISITIONS -->
    <!-- ========================================================================= -->
    <div class="tab-pane fade <?= $activeTab === 'website' ? 'show active' : '' ?>" id="tab-website" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-globe me-2 text-info"></i>Website Stock Requisitions List
                </h6>
                <span class="badge bg-light text-dark border font-monospace">
                    <?= count($websiteRequests ?? []) ?> records
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Request / Order #</th>
                            <th>Destination Website Store</th>
                            <th>Source Warehouse</th>
                            <th>Requested Qty</th>
                            <th>Dispatched Qty</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($websiteRequests)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-globe fs-1 d-block mb-2 text-info opacity-50"></i>
                                    <h6 class="fw-semibold text-dark">No Website Requests Found</h6>
                                    <p class="small text-muted mb-3">No website channel stock requisitions match the selected criteria.</p>
                                </td>
                            </tr>
                        <?php else: foreach ($websiteRequests as $r): ?>
                            <?php 
                                $statusClass = match($r['status'] ?? 'REQUESTED') {
                                    'REQUESTED' => 'bg-warning text-dark',
                                    'APPROVED'  => 'bg-info text-dark',
                                    'PICKING', 'PACKING' => 'bg-primary text-white',
                                    'DISPATCHED', 'COMPLETED', 'DELIVERED', 'RECEIVED' => 'bg-success text-white',
                                    default => 'bg-secondary text-white'
                                };
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold font-monospace">
                                    <?php if ($canViewAllocations): ?>
                                        <a href="<?= url('warehouse/allocation/view/' . $r['id'] . '?from=requests') ?>" class="text-decoration-none text-info-emphasis">
                                            <?= htmlspecialchars($r['allocation_number']) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($r['allocation_number']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($r['created_at'])): ?>
                                        <div class="text-muted fw-normal small" style="font-size: 0.72rem;"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle small font-monospace">
                                            <i class="bi bi-globe me-1"></i><?= htmlspecialchars($r['channel_code'] ?? 'WEB') ?>
                                        </span>
                                        <strong class="text-dark"><?= htmlspecialchars($r['channel_name'] ?? 'Website Store') ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($r['warehouse_name'] ?? 'Central Warehouse') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace fs-7 px-2.5 py-1">
                                        <?= number_format($r['total_requested_qty'] ?? 0) ?> pcs
                                    </span>
                                    <?php if (!empty($r['item_count'])): ?>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><?= (int)$r['item_count'] ?> item style(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($r['total_dispatched_qty'] ?? 0) > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fs-7 px-2.5 py-1">
                                            <?= number_format($r['total_dispatched_qty']) ?> pcs
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">0 pcs</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-dark fw-semibold d-block"><?= htmlspecialchars($r['requested_by_name'] ?? 'E-Commerce Admin') ?></small>
                                    <?php if (!empty($r['notes'])): ?>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 140px;" title="<?= htmlspecialchars($r['notes']) ?>"><?= htmlspecialchars($r['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?> px-2.5 py-1 small fw-semibold">
                                        <?= htmlspecialchars($r['status']) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($canViewAllocations): ?>
                                            <a href="<?= url('warehouse/allocation/view/' . $r['id'] . '?from=requests') ?>" class="btn btn-outline-info px-2.5 py-1 text-dark" title="View Requisition Details">
                                                <i class="bi bi-eye me-1"></i> View
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($canPrintGatepass && in_array($r['status'], ['DISPATCHED', 'COMPLETED', 'DELIVERED', 'RECEIVED'])): ?>
                                            <a href="<?= url('warehouse/allocation/gatepass/' . $r['id']) ?>" target="_blank" class="btn btn-outline-secondary px-2 py-1" title="Print Gate Pass">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
function switchTabParam(tabKey) {
    const input = document.getElementById('activeTabInput');
    if (input) {
        input.value = tabKey;
    }
    // Update URL query string seamlessly without full reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabKey);
    window.history.replaceState({}, '', url);
}

// Ensure active tab on load matches URL if hash or param exists
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam && ['shop', 'whatsapp', 'website'].includes(tabParam)) {
        const btn = document.getElementById('tab-btn-' + tabParam);
        if (btn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(btn).show();
        }
    }
});
</script>
