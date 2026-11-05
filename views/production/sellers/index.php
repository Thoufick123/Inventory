<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-truck-flatbed me-2 text-primary"></i>Production Material Suppliers</h2>
        <span class="text-muted">Manage raw fabric mills, multi-roll specifications, design names, bills, and payment terms</span>
    </div>
    <?php if (hasPermission('sellers', 'create')): ?>
    <a href="<?= url('production/sellers/create') ?>" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> Add Material Supplier
    </a>
    <?php endif; ?>
</div>

<!-- Search & Filter Card -->
<div class="card mb-4 border shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="<?= url('production/sellers') ?>" id="sellerFilterForm" class="row g-2 align-items-center">
            <!-- Search Bar with Live Filter & Clear Button -->
            <div class="col-lg-5 col-md-12">
                <label class="form-label small text-muted fw-semibold mb-1">Search Suppliers &amp; Fabrics</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-primary"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           name="search" 
                           id="sellerSearchInput" 
                           class="form-control fw-semibold" 
                           placeholder="Search supplier, mill, fabric type, colors, phone, or city..." 
                           value="<?= e($search ?? '') ?>" 
                           oninput="liveFilterSellers()">
                    <?php if (!empty($search)): ?>
                        <a href="<?= url('production/sellers') ?>" class="btn btn-outline-secondary" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Material / Fabric Type Filter -->
            <div class="col-lg-3 col-md-4 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Fabric / Material</label>
                <select name="material_type" id="filterMaterialType" class="form-select form-select" onchange="liveFilterSellers()">
                    <option value="">All Fabric Types</option>
                    <?php foreach ($materialTypes as $mt): ?>
                        <?php if (!empty($mt['material_type'])): ?>
                            <option value="<?= e($mt['material_type']) ?>" <?= ($materialType === $mt['material_type']) ? 'selected' : '' ?>>
                                <?= e($mt['material_type']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- City Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">City / Location</label>
                <select name="city" id="filterCity" class="form-select form-select" onchange="liveFilterSellers()">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                        <?php if (!empty($c['city'])): ?>
                            <option value="<?= e($c['city']) ?>" <?= ($city === $c['city']) ? 'selected' : '' ?>>
                                <?= e($c['city']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <label class="form-label small text-muted fw-semibold mb-1">Status</label>
                <div class="d-flex gap-1">
                    <select name="status" id="filterStatus" class="form-select form-select" onchange="liveFilterSellers()">
                        <option value="">All Statuses</option>
                        <option value="1" <?= ($status === '1') ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($status === '0') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button type="submit" class="btn btn-primary" title="Filter Records">
                        <i class="bi bi-filter"></i>
                    </button>
                    <?php if (!empty($search) || $status !== '' || !empty($city) || !empty($materialType)): ?>
                        <a href="<?= url('production/sellers') ?>" class="btn btn-outline-danger" title="Reset All Filters">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Suppliers Table -->
<div class="card border shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0 fw-bold">
            <i class="bi bi-truck-flatbed text-primary me-2"></i>Material Suppliers &amp; Fabric Mills
        </h5>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold" id="sellerCountBadge">
            <?= count($sellers) ?> Suppliers
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="suppliersTable">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 200px;">Supplier / Mill</th>
                        <th style="min-width: 260px;">Fabric &amp; Multi-Roll Specs</th>
                        <th style="min-width: 170px;">Roll, Color &amp; Design</th>
                        <th style="min-width: 150px;">Weight &amp; Price</th>
                        <th style="min-width: 90px;">Bill / Doc</th>
                        <th>Status</th>
                        <th class="text-end" style="min-width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Live Empty Search State Row -->
                    <tr id="emptySupplierLiveRow" style="display: none;">
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-search fs-1 d-block mb-2 text-secondary opacity-75"></i>
                            <h6 class="fw-bold text-dark mb-1">No Matching Material Suppliers Found</h6>
                            <p class="text-muted small mb-0">Try changing your search term or clearing the active filters.</p>
                        </td>
                    </tr>
                    <?php if (empty($sellers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                <span class="fw-semibold">No material suppliers or fabric rolls registered yet.</span>
                                <div class="mt-2">
                                    <a href="<?= url('production/sellers/create') ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Add Your First Material Supplier
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sellers as $s): ?>
                            <?php
                                $rollNumbers = implode(' ', array_filter(array_column($s['rolls'] ?? [], 'roll_number')));
                                $designNames = implode(' ', array_filter(array_column($s['rolls'] ?? [], 'design_name')));
                                $rollColors  = implode(' ', array_filter(array_column($s['rolls'] ?? [], 'color')));
                            ?>
                            <tr class="supplier-row"
                                data-name="<?= strtolower(e($s['name'])) ?>"
                                data-company="<?= strtolower(e($s['company'] ?? '')) ?>"
                                data-phone="<?= strtolower(e($s['phone'] ?? '')) ?>"
                                data-city="<?= strtolower(e($s['city'] ?? '')) ?>"
                                data-gst="<?= strtolower(e($s['gst_number'] ?? '')) ?>"
                                data-material="<?= strtolower(e($s['material_type'] ?? '')) ?>"
                                data-colors="<?= strtolower(e($s['colors'] ?? '')) ?> <?= strtolower(e($rollColors)) ?>"
                                data-rolls="<?= strtolower(e($rollNumbers)) ?>"
                                data-designs="<?= strtolower(e($designNames)) ?>"
                                data-status="<?= $s['is_active'] ? '1' : '0' ?>">
                                <!-- Supplier / Mill Name & Contacts -->
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= e($s['name']) ?></div>
                                    <?php if (!empty($s['company'])): ?>
                                        <div class="text-primary small fw-semibold"><i class="bi bi-building me-1"></i><?= e($s['company']) ?></div>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-1">
                                        <?php if (!empty($s['phone'])): ?>
                                            <i class="bi bi-telephone me-1"></i><?= e($s['phone']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($s['city'])): ?>
                                            <span class="ms-1"><i class="bi bi-geo-alt me-1"></i><?= e($s['city']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($s['gst_number'])): ?>
                                        <div class="small mt-1"><span class="badge bg-light text-secondary border">GST: <?= e($s['gst_number']) ?></span></div>
                                    <?php endif; ?>
                                </td>

                                <!-- Fabric Roll Specs, Multi-Roll Thumbnails & Design Names -->
                                <td>
                                    <div class="d-flex align-items-start gap-2">
                                        <?php if (!empty($s['rolls']) && count($s['rolls']) > 0): ?>
                                            <!-- Multi-roll thumbnails gallery -->
                                            <div class="d-flex flex-wrap gap-1" style="max-width: 110px;">
                                                <?php 
                                                $displayedPhotos = 0;
                                                foreach ($s['rolls'] as $rIdx => $rItem): 
                                                    if (!empty($rItem['photo_url']) && $displayedPhotos < 2):
                                                        $displayedPhotos++;
                                                ?>
                                                    <a href="<?= uploadUrl($rItem['photo_url']) ?>" target="_blank" title="<?= e($rItem['roll_number'] . ' - ' . ($rItem['color'] ?: '') . ' ' . ($rItem['design_name'] ?: '')) ?>" class="d-inline-block position-relative">
                                                        <img src="<?= uploadUrl($rItem['photo_url']) ?>" alt="Roll" class="rounded border shadow-sm" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'44\' height=\'44\' viewBox=\'0 0 44 44\'%3E%3Crect width=\'44\' height=\'44\' fill=\'%23e9ecef\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'sans-serif\' font-size=\'8\' fill=\'%236c757d\'%3ERoll%3C/text%3E%3C/svg%3E'">
                                                    </a>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                if ($displayedPhotos === 0 && !empty($s['roll_photo'])):
                                                ?>
                                                    <a href="<?= uploadUrl($s['roll_photo']) ?>" target="_blank" title="Sample Roll" class="d-inline-block position-relative">
                                                        <img src="<?= uploadUrl($s['roll_photo']) ?>" alt="Roll" class="rounded border shadow-sm" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'44\' height=\'44\' viewBox=\'0 0 44 44\'%3E%3Crect width=\'44\' height=\'44\' fill=\'%23e9ecef\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'sans-serif\' font-size=\'8\' fill=\'%236c757d\'%3ERoll%3C/text%3E%3C/svg%3E'">
                                                    </a>
                                                <?php elseif ($displayedPhotos === 0): ?>
                                                    <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;" title="No photo">
                                                        <i class="bi bi-layers fs-5 text-secondary"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif (!empty($s['roll_photo'])): ?>
                                            <a href="<?= uploadUrl($s['roll_photo']) ?>" target="_blank" title="Click to view roll photo" class="d-inline-block position-relative flex-shrink-0">
                                                <img src="<?= uploadUrl($s['roll_photo']) ?>" alt="Roll Photo" class="rounded border shadow-sm" style="width: 48px; height: 48px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'48\' height=\'48\' viewBox=\'0 0 48 48\'%3E%3Crect width=\'48\' height=\'48\' fill=\'%23e9ecef\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'sans-serif\' font-size=\'9\' fill=\'%236c757d\'%3ENo Img%3C/text%3E%3C/svg%3E'">
                                            </a>
                                        <?php else: ?>
                                            <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 48px; height: 48px;" title="No roll photo uploaded">
                                                <i class="bi bi-image fs-5 text-secondary"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <div class="fw-semibold text-dark small">
                                                <?= e($s['material_type'] ?: 'STANDARD FABRIC MATERIAL') ?>
                                            </div>
                                            <?php if (!empty($s['colors'])): ?>
                                                <div class="small text-muted mt-1">
                                                    <i class="bi bi-palette2 me-1 text-primary"></i><?= e($s['colors']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted d-block mt-1">
                                                Added <?= formatDate($s['created_at']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <!-- Roll Count, Color Count & Design Count -->
                                <td>
                                    <div>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                            <i class="bi bi-stack me-1"></i><?= (int)($s['roll_count'] ?? 0) ?> Rolls
                                        </span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php if ((int)($s['color_count'] ?? 0) > 0): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                <i class="bi bi-palette me-1"></i><?= (int)$s['color_count'] ?> Colors
                                            </span>
                                        <?php endif; ?>
                                        <?php if ((int)($s['design_count'] ?? 0) > 0): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                <i class="bi bi-brush me-1"></i><?= (int)$s['design_count'] ?> Designs
                                            </span>
                                        <?php endif; ?>
                                        <?php if (empty($s['color_count']) && empty($s['design_count']) && !empty($s['color_design_count'])): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border">
                                                <?= (int)$s['color_design_count'] ?> Shades/Prints
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Weight & Total Price -->
                                <td>
                                    <div class="fw-bold text-dark">
                                        <i class="bi bi-speedometer2 me-1 text-muted"></i><?= number_format((float)($s['roll_weight'] ?? 0), 2) ?> kg
                                    </div>
                                    <div class="text-success fw-bold small mt-1">
                                        ₹ <?= number_format((float)($s['roll_price'] ?? 0), 2) ?>
                                    </div>
                                    <?php if ((float)($s['roll_weight'] ?? 0) > 0 && (float)($s['roll_price'] ?? 0) > 0): ?>
                                        <small class="text-muted d-block">
                                            ₹<?= number_format((float)$s['roll_price'] / (float)$s['roll_weight'], 1) ?>/kg
                                        </small>
                                    <?php endif; ?>
                                </td>



                                <!-- Document / Bill -->
                                <td>
                                    <?php if (!empty($s['document_url'])): ?>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= url('production/sellers/download-bill/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary" title="Download Document / Bill" download>
                                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Bill
                                            </a>
                                            <a href="<?= uploadUrl($s['document_url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Open preview in new tab">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td>
                                    <?php if ($s['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- View Modal Button -->
                                        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewSellerModal_<?= $s['id'] ?>" title="Quick View Supplier Details & Fabric Rolls">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <!-- Full View Link -->
                                        <a href="<?= url('production/sellers/view/' . $s['id']) ?>" class="btn btn-outline-secondary" title="Open Full View Sheet">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <!-- Print Labels Link -->
                                        <a href="<?= url('production/sellers/' . $s['id'] . '/labels') ?>" class="btn btn-outline-dark" target="_blank" title="Print Fabric QR Labels">
                                            <i class="bi bi-qr-code"></i>
                                        </a>
                                        <!-- Edit Link -->
                                        <?php if (hasPermission('sellers', 'edit')): ?>
                                        <a href="<?= url('production/sellers/edit/' . $s['id']) ?>" class="btn btn-outline-primary" title="Edit Supplier & Rolls">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <!-- Delete Supplier & Rolls -->
                                        <?php if (hasPermission('sellers', 'delete')): ?>
                                        <form method="POST" action="<?= url('production/sellers/delete/' . $s['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Supplier \'<?= e(addslashes($s['name'])) ?>\' and its associated roll creations? This action cannot be undone.');">
                                            <?= CSRF::field() ?>
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Supplier & Rolls">
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
    <!-- Pagination Controls Footer -->
    <div class="card-footer bg-white py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-3" id="sellersPaginationFooter">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="small text-muted fw-medium" id="sellersPageInfo">
                Showing 1 to 10 of <?= count($sellers) ?> suppliers
            </span>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0 fw-medium">Show:</label>
                <select id="sellersPageSizeSelect" class="form-select form-select-sm" style="width: auto;">
                    <option value="10" selected>10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>
        <nav aria-label="Suppliers Pagination">
            <ul class="pagination pagination-modern mb-0" id="sellersPaginationList">
            </ul>
        </nav>
    </div>
</div>

<!-- View Supplier Modals -->
<?php if (!empty($sellers)): ?>
    <?php foreach ($sellers as $s): ?>
        <div class="modal fade" id="viewSellerModal_<?= $s['id'] ?>" tabindex="-1" aria-labelledby="viewSellerModalLabel_<?= $s['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-truck-flatbed fs-4 text-primary"></i>
                            <div>
                                <h5 class="modal-title fw-bold text-dark mb-0" id="viewSellerModalLabel_<?= $s['id'] ?>">
                                    <?= e($s['name']) ?>
                                    <?php if (!empty($s['company'])): ?>
                                        <span class="text-muted fw-normal fs-6"> (<?= e($s['company']) ?>)</span>
                                    <?php endif; ?>
                                </h5>
                                <small class="text-muted">Material Supplier Specification & Multi-Roll Lot Sheet</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($s['is_active']): ?>
                                <span class="badge bg-success">Active Supplier</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Key Stats Summary Header -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 bg-light rounded border text-center">
                                    <span class="text-muted small d-block mb-1">TOTAL ROLLS</span>
                                    <span class="fs-4 fw-bold text-primary"><?= (int)($s['roll_count'] ?? count($s['rolls'] ?? [])) ?></span>
                                    <span class="text-muted small d-block">Fabric Rolls</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 bg-light rounded border text-center">
                                    <span class="text-muted small d-block mb-1">DESIGNS / COLORS</span>
                                    <span class="fs-4 fw-bold text-warning"><?= (int)($s['design_count'] ?? 0) ?></span>
                                    <span class="text-muted small d-block"><?= (int)($s['color_count'] ?? 0) ?> Colors</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 bg-light rounded border text-center">
                                    <span class="text-muted small d-block mb-1">TOTAL WEIGHT</span>
                                    <span class="fs-4 fw-bold text-dark"><?= number_format((float)($s['roll_weight'] ?? 0), 2) ?> kg</span>
                                    <span class="text-muted small d-block">Net Weight</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 bg-light rounded border text-center">
                                    <span class="text-muted small d-block mb-1">TOTAL PRICE</span>
                                    <span class="fs-4 fw-bold text-success">₹ <?= number_format((float)($s['roll_price'] ?? 0), 2) ?></span>
                                    <?php if ((float)($s['roll_weight'] ?? 0) > 0): ?>
                                        <span class="text-muted small d-block">₹<?= number_format((float)$s['roll_price'] / (float)$s['roll_weight'], 1) ?>/kg</span>
                                    <?php else: ?>
                                        <span class="text-muted small d-block">Valuation</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Supplier Profile & Commercial Terms Grid -->
                        <div class="row g-3 mb-4">
                            <!-- Supplier Profile Info -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-vcard text-primary me-2"></i>Supplier Profile</h6>
                                    <div class="row g-2 small">
                                        <div class="col-4 text-muted">Contact Name:</div>
                                        <div class="col-8 fw-bold text-dark"><?= e($s['name']) ?></div>

                                        <div class="col-4 text-muted">Company / Mill:</div>
                                        <div class="col-8 text-dark"><?= e($s['company'] ?: '—') ?></div>

                                        <div class="col-4 text-muted">Phone:</div>
                                        <div class="col-8 text-dark"><?= e($s['phone'] ?: '—') ?></div>

                                        <div class="col-4 text-muted">Email:</div>
                                        <div class="col-8 text-dark"><?= e($s['email'] ?: '—') ?></div>

                                        <div class="col-4 text-muted">GST Number:</div>
                                        <div class="col-8"><?= !empty($s['gst_number']) ? '<span class="badge bg-white text-dark border font-monospace">' . e($s['gst_number']) . '</span>' : '—' ?></div>

                                        <div class="col-4 text-muted">Location:</div>
                                        <div class="col-8 text-dark"><?= e(implode(', ', array_filter([$s['city'] ?? '', $s['state'] ?? '']))) ?: '—' ?></div>

                                        <div class="col-4 text-muted">Address:</div>
                                        <div class="col-8 text-dark"><?= e($s['address'] ?: '—') ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Commercial Terms & Bill Attachment -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-credit-card-2-front text-primary me-2"></i>Commercial Terms & Bill</h6>
                                        <div class="mb-3">
                                            <span class="text-muted small d-block mb-1">FABRIC GRADE:</span>
                                            <div class="fw-bold text-dark"><?= e($s['material_type'] ?: 'Standard Fabric Material') ?></div>
                                        </div>

                                    </div>

                                    <div>
                                        <span class="text-muted small d-block mb-1">BILL / INVOICE ATTACHMENT:</span>
                                        <?php if (!empty($s['document_url'])): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="<?= uploadUrl($s['document_url']) ?>" target="_blank" class="d-inline-block flex-shrink-0" title="Click to preview bill image">
                                                    <img src="<?= uploadUrl($s['document_url']) ?>" alt="Bill Document" class="rounded border shadow-sm" style="width: 38px; height: 38px; object-fit: cover;" onerror="this.parentElement.style.display='none'">
                                                </a>
                                                <a href="<?= url('production/sellers/download-bill/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary" download title="Download Bill file to your computer">
                                                    <i class="bi bi-file-earmark-arrow-down me-1"></i> View / Download Bill
                                                </a>
                                                <a href="<?= uploadUrl($s['document_url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Open bill preview in new tab">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small"><em>No invoice attached</em></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Individual Fabric Rolls Table -->
                        <div class="card border">
                            <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                                <h6 class="mb-0 fw-bold"><i class="bi bi-stack text-primary me-2"></i>Fabric Rolls & Design Breakdown (<?= count($s['rolls'] ?? []) ?>)</h6>
                                <span class="text-muted small">Photos, Design Names, Counts, Weights & Prices</span>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($s['rolls'])): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                                        <small>No individual roll cards recorded for this lot.</small>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;">Photo</th>
                                                    <th>Roll Code</th>
                                                    <th>Color / Shade</th>
                                                    <th>Design Name / Pattern</th>
                                                    <th class="text-center">Designs</th>
                                                    <th class="text-end">Total Stock</th>
                                                    <th class="text-end text-danger">Damages</th>
                                                    <th class="text-end bg-success-subtle text-success fw-bold">Available Balance</th>
                                                    <th class="text-end">Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($s['rolls'] as $rIdx => $rItem): 
                                                    $rTotQty = (int)($rItem['quantity'] ?? 0);
                                                    $rTotWt = (float)($rItem['weight'] ?? 0);
                                                    $rDmgQty = (int)($rItem['damaged_quantity'] ?? 0);
                                                    $rDmgWt = (float)($rItem['damaged_weight'] ?? 0);
                                                    $rAvailQty = (int)($rItem['available_quantity'] ?? max(0, $rTotQty - (int)($rItem['used_quantity'] ?? 0) - $rDmgQty));
                                                    $rAvailWt = (float)($rItem['available_weight'] ?? max(0.0, $rTotWt - (float)($rItem['used_weight'] ?? 0) - $rDmgWt));
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <?php if (!empty($rItem['photo_url'])): ?>
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <a href="<?= uploadUrl($rItem['photo_url']) ?>" target="_blank" title="Click to view high-res photo">
                                                                        <img src="<?= uploadUrl($rItem['photo_url']) ?>" alt="Roll Photo" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'44\' height=\'44\' viewBox=\'0 0 44 44\'%3E%3Crect width=\'44\' height=\'44\' fill=\'%23e9ecef\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-family=\'sans-serif\' font-size=\'8\' fill=\'%236c757d\'%3ENo Img%3C/text%3E%3C/svg%3E'">
                                                                    </a>
                                                                    <a href="<?= url('production/sellers/download-roll-photo/' . $rItem['id']) ?>" class="btn btn-sm btn-outline-secondary p-1" title="Download Roll Photo" download>
                                                                        <i class="bi bi-download"></i>
                                                                    </a>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;">
                                                                    <i class="bi bi-camera text-secondary"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="fw-bold text-dark"><?= e($rItem['roll_number']) ?></div>
                                                            <?php if (!empty($rItem['start_code'])): ?>
                                                                <div class="mt-1">
                                                                    <span class="badge bg-dark text-white font-monospace" style="font-size: 10px;">
                                                                        <i class="bi bi-qr-code text-warning me-1"></i><?= e($rItem['start_code']) ?><?= (!empty($rItem['end_code']) && $rItem['end_code'] !== $rItem['start_code']) ? '–' . e($rItem['end_code']) : '' ?>
                                                                    </span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <small class="text-muted d-block mt-1">Item #<?= $rIdx + 1 ?></small>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($rItem['color'])): ?>
                                                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                                    <?= e($rItem['color']) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($rItem['design_name'])): ?>
                                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                                    <?= e($rItem['design_name']) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-secondary-subtle text-secondary border">
                                                                <?= (int)($rItem['design_count'] ?? 1) ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="fw-bold text-dark"><?= number_format($rTotQty) ?> qty</div>
                                                            <small class="text-muted d-block"><?= number_format($rTotWt, 2) ?> kg</small>
                                                        </td>
                                                        <td class="text-end">
                                                            <?php if ($rDmgQty > 0 || $rDmgWt > 0): ?>
                                                                <div class="fw-bold text-danger"><?= number_format($rDmgQty) ?> qty</div>
                                                                <small class="text-danger d-block"><?= number_format($rDmgWt, 2) ?> kg</small>
                                                            <?php else: ?>
                                                                <span class="text-muted small">0 qty</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end bg-success-subtle">
                                                            <div class="fw-bold text-success"><?= number_format($rAvailQty) ?> qty</div>
                                                            <small class="text-success d-block"><?= number_format($rAvailWt, 2) ?> kg</small>
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="fw-bold text-dark">₹ <?= number_format((float)($rItem['price'] ?? 0), 2) ?></span>
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
                    <div class="modal-footer bg-light py-2 border-top">
                        <form method="POST" action="<?= url('production/sellers/delete/' . $s['id']) ?>" class="me-auto d-inline" onsubmit="return confirm('Are you sure you want to delete Supplier \'<?= e(addslashes($s['name'])) ?>\' and all its roll creations?');">
                            <?= CSRF::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i> Delete Supplier
                            </button>
                        </form>
                        <a href="<?= url('production/sellers/' . $s['id'] . '/labels') ?>" target="_blank" class="btn btn-sm btn-dark" title="Print Fabric QR Labels">
                            <i class="bi bi-qr-code me-1"></i> Print Labels
                        </a>
                        <a href="<?= url('production/sellers/view/' . $s['id']) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Full Page View
                        </a>
                        <a href="<?= url('production/sellers/edit/' . $s['id']) ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i> Edit Supplier
                        </a>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
let sellerPagination;
function initSellersPagination() {
    if (typeof window.initClientPagination === 'function') {
        sellerPagination = window.initClientPagination({
            container: '#suppliersTable tbody',
            itemSelector: 'tr.supplier-row',
            footer: '#sellersPaginationFooter',
            infoEl: '#sellersPageInfo',
            paginationList: '#sellersPaginationList',
            pageSizeSelect: '#sellersPageSizeSelect',
            emptyStateEl: '#emptySupplierLiveRow',
            pageSize: 10,
            itemName: 'suppliers'
        });
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSellersPagination);
} else {
    initSellersPagination();
}

function liveFilterSellers() {
    const searchInput = document.getElementById('sellerSearchInput');
    const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

    const materialFilter = document.getElementById('filterMaterialType');
    const selectedMaterial = materialFilter ? materialFilter.value.trim().toLowerCase() : '';

    const cityFilter = document.getElementById('filterCity');
    const selectedCity = cityFilter ? cityFilter.value.trim().toLowerCase() : '';

    const statusFilter = document.getElementById('filterStatus');
    const selectedStatus = statusFilter ? statusFilter.value.trim() : '';

    const rows = document.querySelectorAll('.supplier-row');
    const totalCount = rows.length;

    const matchFn = (row) => {
        const name = row.getAttribute('data-name') || '';
        const company = row.getAttribute('data-company') || '';
        const phone = row.getAttribute('data-phone') || '';
        const city = row.getAttribute('data-city') || '';
        const gst = row.getAttribute('data-gst') || '';
        const material = row.getAttribute('data-material') || '';
        const colors = row.getAttribute('data-colors') || '';
        const rolls = row.getAttribute('data-rolls') || '';
        const designs = row.getAttribute('data-designs') || '';
        const status = row.getAttribute('data-status') || '';

        const matchesQuery = !query || 
            name.includes(query) || 
            company.includes(query) || 
            phone.includes(query) || 
            city.includes(query) || 
            gst.includes(query) || 
            material.includes(query) || 
            colors.includes(query) || 
            rolls.includes(query) || 
            designs.includes(query);

        const matchesMaterial = !selectedMaterial || material === selectedMaterial;
        const matchesCity = !selectedCity || city === selectedCity;
        const matchesStatus = selectedStatus === '' || status === selectedStatus;

        return matchesQuery && matchesMaterial && matchesCity && matchesStatus;
    };

    let visibleCount = 0;
    if (sellerPagination) {
        visibleCount = sellerPagination.filter(matchFn);
    } else {
        rows.forEach(row => {
            if (matchFn(row)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        const emptyRow = document.getElementById('emptySupplierLiveRow');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }

    const badge = document.getElementById('sellerCountBadge');
    if (badge) {
        if (query || selectedMaterial || selectedCity || selectedStatus !== '') {
            badge.textContent = `${visibleCount} of ${totalCount} Suppliers`;
            badge.className = 'badge bg-warning text-dark border border-warning rounded-pill px-3 py-2 fw-semibold';
        } else {
            badge.textContent = `${totalCount} Suppliers`;
            badge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold';
        }
    }
}
</script>
