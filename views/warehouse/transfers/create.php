<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 text-gray-800"><i class="bi bi-arrow-left-right me-2 text-info"></i>Create Inter-Warehouse Transfer</h2>
        <p class="text-muted small mb-0">Move stock between physical warehouses or retail locations with real-time StockService synchronization.</p>
    </div>
    <div>
        <a href="<?= url('warehouse/transfers') ?>" class="btn btn-outline-secondary">Back to Transfers</a>
    </div>
</div>

<form method="POST" action="<?= url('warehouse/transfers/store') ?>" class="card border-0 shadow-sm rounded-3 p-4">
    <?= CSRF::field() ?>

    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Source & Destination Locations</h5>
    <?php 
        $isSourceScoped = !empty($scopedWarehouseId) || count($warehouses) <= 1;
        $destWhList = $destinationWarehouses ?? $warehouses;
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label fw-bold">Source Location <span class="text-danger">*</span></label>
            <select name="source_target" class="form-select" required <?= $isSourceScoped ? 'disabled' : '' ?>>
                <?php if (!$isSourceScoped): ?>
                    <option value="">-- Select Source Location --</option>
                <?php endif; ?>
                <optgroup label="Warehouses">
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="WAREHOUSE_<?= $wh['id'] ?>" <?= ($isSourceScoped || (!empty($scopedWarehouseId) && $scopedWarehouseId == $wh['id'])) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($wh['name']) ?><?= !empty($wh['code']) ? ' (' . htmlspecialchars($wh['code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php if (!$isSourceScoped): ?>
                <optgroup label="Retail Shops">
                    <?php foreach ($shops as $sh): ?>
                        <option value="SHOP_<?= $sh['id'] ?>"><?= htmlspecialchars($sh['name']) ?><?= !empty($sh['code']) ? ' (' . htmlspecialchars($sh['code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endif; ?>
            </select>
            <?php if ($isSourceScoped): ?>
                <input type="hidden" name="source_target" value="WAREHOUSE_<?= htmlspecialchars((string)($warehouses[0]['id'] ?? $scopedWarehouseId ?? '')) ?>">
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Destination Location <span class="text-danger">*</span></label>
            <select name="destination_target" class="form-select" required>
                <option value="">-- Select Destination Location --</option>
                <optgroup label="Warehouses">
                    <?php foreach ($destWhList as $wh): ?>
                        <?php if ($isSourceScoped && (!empty($scopedWarehouseId) ? $wh['id'] == $scopedWarehouseId : $wh['id'] == ($warehouses[0]['id'] ?? 0))) continue; ?>
                        <option value="WAREHOUSE_<?= $wh['id'] ?>"><?= htmlspecialchars($wh['name']) ?><?= !empty($wh['code']) ? ' (' . htmlspecialchars($wh['code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Retail Shops">
                    <?php foreach ($shops as $sh): ?>
                        <option value="SHOP_<?= $sh['id'] ?>"><?= htmlspecialchars($sh['name']) ?><?= !empty($sh['code']) ? ' (' . htmlspecialchars($sh['code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>
    </div>

    <!-- Outward Logistics Details Card -->
    <div class="card bg-light border-0 rounded-3 p-3 mb-4">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-truck me-2 text-info"></i>Outward Logistics Details</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">Carrier / Transport Name</label>
                <input type="text" name="carrier_name" class="form-control" placeholder="e.g. BlueDart / VRL Logistics">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">Vehicle Number</label>
                <input type="text" name="vehicle_no" class="form-control" placeholder="e.g. KA-01-EA-1234">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">Driver Contact Number</label>
                <input type="text" name="driver_phone" class="form-control" placeholder="e.g. +91 9876543210">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1">Dispatch Date & Time</label>
                <input type="datetime-local" name="dispatch_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold small mb-1">Expected Arrival Date</label>
                <input type="date" name="expected_arrival" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-boxes me-2 text-primary"></i>Transfer Items Catalog</h5>
    <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle" id="transferItemsTable">
            <thead class="table-light">
                <tr>
                    <th style="width: 35%;">3-Tier Category Hierarchy</th>
                    <th style="width: 40%;">Product Variant / SKU</th>
                    <th style="width: 15%;">Transfer Qty (pcs)</th>
                    <th style="width: 10%;" class="text-center">Remove</th>
                </tr>
            </thead>
            <tbody>
                <tr class="item-row">
                    <td>
                        <div class="row g-1">
                            <div class="col-12">
                                <select class="form-select form-select-sm cat-filter">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mt-1">
                                <select class="form-select form-select-sm subcat-filter">
                                    <option value="">-- Select Subcategory --</option>
                                </select>
                            </div>
                            <div class="col-12 mt-1">
                                <select class="form-select form-select-sm subsubcat-filter">
                                    <option value="">-- Select Sub-subcategory --</option>
                                </select>
                            </div>
                        </div>
                    </td>
                    <td>
                        <select name="variant_ids[]" class="form-select variant-select" required>
                            <option value="">-- Select Variant --</option>
                            <?php foreach ($variants as $v): 
                                $sizes = array_filter(array_map('trim', explode(',', $v['size_name'] ?? '')));
                                if (!empty($sizes)):
                                    foreach ($sizes as $sz):
                                        $dispSku = (!empty($v['size_name']) && strpos($v['sku'], $v['size_name']) !== false) 
                                            ? str_replace($v['size_name'], $sz, $v['sku']) 
                                            : ($v['sku'] . '-' . $sz);
                            ?>
                                        <option value="<?= $v['id'] ?>"
                                                data-cat="<?= $v['category_id'] ?? '' ?>"
                                                data-subcat="<?= $v['subcategory_id'] ?? '' ?>"
                                                data-subsubcat="<?= $v['sub_subcategory_id'] ?? '' ?>"
                                                data-size="<?= htmlspecialchars($sz) ?>"
                                                data-sku="<?= htmlspecialchars($dispSku) ?>">
                                            <?= htmlspecialchars($v['product_name']) ?> (Size: <?= htmlspecialchars($sz) ?><?= !empty($v['color_name']) ? ', Color: ' . htmlspecialchars($v['color_name']) : '' ?>) | SKU: <?= htmlspecialchars($dispSku) ?>
                                        </option>
                            <?php 
                                    endforeach;
                                else:
                            ?>
                                    <option value="<?= $v['id'] ?>"
                                            data-cat="<?= $v['category_id'] ?? '' ?>"
                                            data-subcat="<?= $v['subcategory_id'] ?? '' ?>"
                                            data-subsubcat="<?= $v['sub_subcategory_id'] ?? '' ?>"
                                            data-sku="<?= htmlspecialchars($v['sku']) ?>">
                                        <?= htmlspecialchars($v['product_name']) ?> | SKU: <?= htmlspecialchars($v['sku']) ?> <?= !empty($v['color_name']) ? '(' . htmlspecialchars($v['color_name']) . ')' : '' ?>
                                    </option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="quantities[]" class="form-control" min="1" value="1" required>
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <button type="button" class="btn btn-outline-info btn-sm mb-4" id="addRowBtn">
        <i class="bi bi-plus-lg me-1"></i> Add Another Variant
    </button>

    <div class="mb-4">
        <label class="form-label fw-bold">Transfer Notes / Special Instructions</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Stock balancing transfer between Central WH and City WH"></textarea>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= url('warehouse/transfers') ?>" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-info text-white px-4"><i class="bi bi-send me-1"></i> Submit Transfer Request</button>
    </div>
</form>

<script>
const subcategoriesData = <?= json_encode($subcategories ?? []) ?>;
const subSubcategoriesData = <?= json_encode($subSubcategories ?? []) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.querySelector('#transferItemsTable tbody');
    const addRowBtn = document.getElementById('addRowBtn');

    function populateSubcategories(row) {
        const catId = row.querySelector('.cat-filter').value;
        const subcatSelect = row.querySelector('.subcat-filter');
        const subsubcatSelect = row.querySelector('.subsubcat-filter');
        
        subcatSelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
        subsubcatSelect.innerHTML = '<option value="">-- Select Sub-subcategory --</option>';

        if (catId) {
            const filtered = subcategoriesData.filter(s => s.category_id == catId);
            filtered.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                subcatSelect.appendChild(opt);
            });
        }
        filterVariantOptions(row);
    }

    function populateSubSubcategories(row) {
        const subcatId = row.querySelector('.subcat-filter').value;
        const subsubcatSelect = row.querySelector('.subsubcat-filter');
        subsubcatSelect.innerHTML = '<option value="">-- Select Sub-subcategory --</option>';

        if (subcatId) {
            const filtered = subSubcategoriesData.filter(s => s.subcategory_id == subcatId);
            filtered.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                subsubcatSelect.appendChild(opt);
            });
        }
        filterVariantOptions(row);
    }

    function filterVariantOptions(row) {
        const catId = row.querySelector('.cat-filter').value;
        const subcatId = row.querySelector('.subcat-filter').value;
        const subsubcatId = row.querySelector('.subsubcat-filter').value;
        const varSelect = row.querySelector('.variant-select');
        const options = varSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return;
            let match = true;
            if (catId && opt.getAttribute('data-cat') != catId) match = false;
            if (subcatId && opt.getAttribute('data-subcat') != subcatId) match = false;
            if (subsubcatId && opt.getAttribute('data-subsubcat') != subsubcatId) match = false;

            opt.hidden = !match;
            opt.disabled = !match;
            opt.style.display = match ? '' : 'none';
        });

        const currentSelected = varSelect.options[varSelect.selectedIndex];
        if (currentSelected && currentSelected.value && (currentSelected.disabled || currentSelected.hidden || currentSelected.style.display === 'none')) {
            varSelect.value = '';
        }
    }

    function bindRowEvents(row) {
        const catSelect = row.querySelector('.cat-filter');
        const subcatSelect = row.querySelector('.subcat-filter');
        const subsubcatSelect = row.querySelector('.subsubcat-filter');

        catSelect.addEventListener('change', () => populateSubcategories(row));
        subcatSelect.addEventListener('change', () => populateSubSubcategories(row));
        subsubcatSelect.addEventListener('change', () => filterVariantOptions(row));
    }

    document.querySelectorAll('#transferItemsTable tbody tr.item-row').forEach(bindRowEvents);

    if (addRowBtn) {
        addRowBtn.addEventListener('click', function() {
            const firstRow = tableBody.querySelector('tr.item-row');
            const newRow = firstRow.cloneNode(true);
            
            newRow.querySelectorAll('select').forEach(s => s.value = '');
            newRow.querySelector('.subcat-filter').innerHTML = '<option value="">-- Select Subcategory --</option>';
            newRow.querySelector('.subsubcat-filter').innerHTML = '<option value="">-- Select Sub-subcategory --</option>';
            newRow.querySelector('input[type="number"]').value = '1';

            tableBody.appendChild(newRow);
            bindRowEvents(newRow);
        });
    }

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            if (tableBody.querySelectorAll('tr.item-row').length > 1) {
                e.target.closest('tr').remove();
            } else {
                alert('At least one transfer item line is required.');
            }
        }
    });
});
</script>
