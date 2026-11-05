<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-6">
                <code><?= e($product['code']) ?></code>
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle">Own Production Style</span>
        </div>
        <h2 class="mb-1 fw-bold"><?= e($product['name']) ?></h2>
        <div class="text-muted small d-flex align-items-center gap-2 flex-wrap">
            <span><i class="bi bi-folder2 me-1"></i><?= e($product['category_name'] ?? 'General') ?></span>
            <?php if (!empty($product['subcategory_name'])): ?>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                <span><?= e($product['subcategory_name']) ?></span>
            <?php endif; ?>
            <?php if (!empty($product['sub_subcategory_name'])): ?>
                <i class="bi bi-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                <span><?= e($product['sub_subcategory_name']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('production/programs/create?product_id=' . $product['id']) ?>" class="btn btn-primary">
            <i class="bi bi-kanban me-1"></i> Start Production Program
        </a>
        <a href="<?= url('production/catalog/edit/' . $product['id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit Style
        </a>
        <form method="POST" action="<?= url('production/catalog/delete/' . $product['id']) ?>" class="d-inline mb-0" onsubmit="return confirm('Are you sure you want to delete this product style?');">
            <?= CSRF::field() ?>
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-trash me-1"></i> Delete Style
            </button>
        </form>
        <a href="<?= url('production/catalog') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Catalog
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Image & Core Info -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center p-3">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= e($product['image_url']) ?>" class="img-fluid rounded border shadow-sm w-100" style="max-height: 320px; object-fit: contain;" alt="<?= e($product['name']) ?>">
                <?php else: ?>
                    <div class="py-5 bg-light rounded text-muted border">
                        <i class="bi bi-image fs-1 d-block mb-2 text-secondary"></i>
                        <span>No Photo Uploaded</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="card-title mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Style Details</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Style Code:</span>
                        <span class="fw-semibold font-monospace text-primary"><?= e($product['code']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Brand / Label:</span>
                        <span class="fw-semibold"><?= e($product['brand_name'] ?? 'In-House') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Main Category:</span>
                        <span class="fw-semibold"><?= e($product['category_name'] ?? 'General') ?></span>
                    </li>
                    <?php if (!empty($product['subcategory_name'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Sub Category:</span>
                        <span class="fw-semibold"><?= e($product['subcategory_name']) ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($product['sub_subcategory_name'])): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="text-muted small">Sub Sub Category:</span>
                        <span class="fw-semibold"><?= e($product['sub_subcategory_name']) ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if (!empty($product['description'])): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold"><i class="bi bi-card-text me-2 text-primary"></i>Description</h6>
                </div>
                <div class="card-body text-muted small">
                    <?= nl2br(e($product['description'])) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Variants Breakdown & Production Programs -->
    <div class="col-lg-8">
        <!-- Variant Items Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-boxes me-2 text-primary"></i> Garment Variants (<?= count($variants) ?> Items)
                </h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Active Variants</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Size</th>
                                <th>Color</th>
                                <th>SKU</th>
                                <th class="text-end">Prod. Cost</th>
                                <th class="text-end">MRP</th>
                                <th class="text-end">Selling Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($variants)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No variants configured for this style.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($variants as $v): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark border px-2 py-1">
                                                <?= e($v['size_code'] ?? ($v['size_name'] ?? 'Free Size')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($v['color_hex'])): ?>
                                                    <span style="display:inline-block; width:14px; height:14px; border-radius:50%; background-color:<?= e($v['color_hex']) ?>; border:1px solid #ccc;"></span>
                                                <?php endif; ?>
                                                <span class="fw-semibold small"><?= e($v['color_name'] ?? 'Standard') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <code><?= e($v['sku']) ?></code>
                                        </td>
                                        <td class="text-end fw-semibold text-primary">
                                            <?= formatCurrency((float)$v['production_cost']) ?>
                                        </td>
                                        <td class="text-end text-muted">
                                            <?= formatCurrency((float)$v['mrp']) ?>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            <?= formatCurrency((float)$v['selling_price']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Production Programs Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <i class="bi bi-kanban me-2 text-primary"></i> Linked Production Programs
                </h5>
                <a href="<?= url('production/programs/create?product_id=' . $product['id']) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i> Plan New Program
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Program Code</th>
                                <th>Unit</th>
                                <th>Target Qty</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($programs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No production programs initiated for this style yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($programs as $prg): ?>
                                    <tr>
                                        <td><code><?= e($prg['program_number'] ?? $prg['code'] ?? ('PRG-' . $prg['id'])) ?></code></td>
                                        <td><?= e($prg['unit_name'] ?? '—') ?></td>
                                        <td class="fw-semibold"><?= (int)($prg['planned_quantity'] ?? $prg['target_pieces'] ?? 0) ?> pcs</td>
                                        <td>
                                            <span class="badge bg-secondary"><?= e($prg['status']) ?></span>
                                        </td>
                                        <td class="small text-muted"><?= formatDate($prg['created_at']) ?></td>
                                        <td>
                                            <a href="<?= url('production/programs/view/' . $prg['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
