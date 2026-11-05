<?php
/**
 * Bulk Asset QR Sticker Generator & Printing Studio
 */
$selectedUnit = $currentUnit ?? 'ALL';
$selectedCat = (int)($filters['category_id'] ?? 0);
$selectedStatus = $filters['status'] ?? '';
$columns = $columns ?? 3;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Asset QR Sticker Generator — GRM Apparels</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .generator-control-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .a4-sheet {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 8mm;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            border-radius: 8px;
        }
        .labels-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3.5mm;
        }
        .labels-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4.5mm;
        }
        .sticker-card {
            border: 1px dashed #cbd5e1;
            padding: 2.5mm;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 2.5mm;
            background: #fff;
            page-break-inside: avoid;
            height: 27mm;
            box-sizing: border-box;
        }
        .sticker-qr {
            width: 21mm;
            height: 21mm;
            flex-shrink: 0;
        }
        .sticker-qr img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .sticker-info {
            font-size: 6.5pt;
            line-height: 1.15;
            color: #000;
            overflow: hidden;
            width: calc(100% - 23.5mm);
        }
        .sticker-brand {
            font-size: 6pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #000;
            border-bottom: 0.5px solid #000;
            padding-bottom: 1px;
            margin-bottom: 1.5px;
            letter-spacing: 0.5px;
        }
        .sticker-code {
            font-size: 7.5pt;
            font-weight: 900;
            font-family: monospace;
            color: #000;
        }
        .sticker-name {
            font-weight: 600;
            font-size: 6pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #111;
        }
        .sticker-loc {
            font-size: 5.5pt;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sticker-sn {
            font-size: 5pt;
            font-family: monospace;
            color: #555;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .a4-sheet {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
                border-radius: 0;
            }
            .sticker-card {
                border: 0.5px solid #94a3b8;
            }
        }
    </style>
</head>
<body>

<!-- Interactive Facility & Warehouse QR Generator Toolbar (Hidden when printing) -->
<div class="container-fluid max-w-6xl mb-4 no-print" style="max-width: 1100px;">
    <div class="generator-control-card p-4 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 pb-3 border-bottom border-secondary border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-qr-code text-primary fs-3"></i>
                <div>
                    <h5 class="fw-bold mb-0 text-white">Bulk Asset QR Sticker Generator Studio</h5>
                    <span class="text-secondary small">Filter by Warehouse, Production Unit, or Shop to generate print-ready sticker batches.</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('asset/bulk-labels') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Asset List
                </a>
                <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow">
                    <i class="bi bi-printer-fill me-1"></i> Print <?= count($assets) ?> QR Stickers
                </button>
            </div>
        </div>

        <form method="GET" action="<?= url('asset/bulk-labels') ?>" class="row g-2 align-items-end">
            <input type="hidden" name="mode" value="print">
            <?php if (!empty($filters['search'])): ?>
                <input type="hidden" name="search" value="<?= e($filters['search']) ?>">
            <?php endif; ?>
            <!-- Facility / Unit Selector -->
            <div class="col-md-4">
                <label class="form-label small text-secondary fw-semibold mb-1">
                    <i class="bi bi-buildings me-1 text-info"></i> Target Facility / Warehouse
                </label>
                <select name="unit" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                    <option value="ALL" <?= $selectedUnit === 'ALL' ? 'selected' : '' ?>>🌐 All Facilities &amp; Plants (Global)</option>
                    
                    <optgroup label="🏢 Central Warehouses">
                        <option value="WAREHOUSE_ALL" <?= $selectedUnit === 'WAREHOUSE_ALL' ? 'selected' : '' ?>>All Warehouses Combined</option>
                        <?php foreach (($unitsMap['warehouses'] ?? []) as $wh): ?>
                            <option value="WH_<?= $wh['id'] ?>" <?= $selectedUnit === 'WH_' . $wh['id'] ? 'selected' : '' ?>>🏢 <?= e($wh['name']) ?> (<?= e($wh['code'] ?: 'WH'.$wh['id']) ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>

                    <optgroup label="🏭 Production Units">
                        <option value="PRODUCTION_ALL" <?= $selectedUnit === 'PRODUCTION_ALL' ? 'selected' : '' ?>>All Production Units Combined</option>
                        <?php foreach (($unitsMap['production'] ?? []) as $pu): ?>
                            <option value="PU_<?= $pu['id'] ?>" <?= $selectedUnit === 'PU_' . $pu['id'] ? 'selected' : '' ?>>🏭 <?= e($pu['name']) ?> (<?= e($pu['code'] ?: 'PU'.$pu['id']) ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>

                    <optgroup label="🛍️ Retail Flagship Shops">
                        <option value="SHOP_ALL" <?= $selectedUnit === 'SHOP_ALL' ? 'selected' : '' ?>>All Retail Shops Combined</option>
                        <?php foreach (($unitsMap['shops'] ?? []) as $sh): ?>
                            <option value="SH_<?= $sh['id'] ?>" <?= $selectedUnit === 'SH_' . $sh['id'] ? 'selected' : '' ?>>🛍️ <?= e($sh['name']) ?> (<?= e($sh['code'] ?: 'SH'.$sh['id']) ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>

                    <optgroup label="🏛️ Head Office">
                        <option value="OFFICE" <?= $selectedUnit === 'OFFICE' ? 'selected' : '' ?>>🏛️ GRM Head Office &amp; Admin (HQ)</option>
                    </optgroup>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="col-md-3">
                <label class="form-label small text-secondary fw-semibold mb-1">
                    <i class="bi bi-tags me-1 text-primary"></i> Category
                </label>
                <select name="category_id" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $selectedCat === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-2">
                <label class="form-label small text-secondary fw-semibold mb-1">
                    <i class="bi bi-activity me-1 text-success"></i> Status
                </label>
                <select name="status" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="IN_USE" <?= $selectedStatus === 'IN_USE' ? 'selected' : '' ?>>In Use (Active)</option>
                    <option value="IDLE" <?= $selectedStatus === 'IDLE' ? 'selected' : '' ?>>Idle Reserve</option>
                    <option value="IN_MAINTENANCE" <?= $selectedStatus === 'IN_MAINTENANCE' ? 'selected' : '' ?>>In Maintenance</option>
                </select>
            </div>

            <!-- Layout Columns -->
            <div class="col-md-2">
                <label class="form-label small text-secondary fw-semibold mb-1">
                    <i class="bi bi-grid-3x3 me-1 text-warning"></i> Sheet Grid
                </label>
                <select name="columns" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="this.form.submit()">
                    <option value="3" <?= $columns === 3 ? 'selected' : '' ?>>3 Columns / Sheet</option>
                    <option value="2" <?= $columns === 2 ? 'selected' : '' ?>>2 Columns / Sheet</option>
                </select>
            </div>

            <!-- Filter Submit -->
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Apply</button>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-center text-muted small px-2">
        <div>
            <i class="bi bi-check-circle-fill text-success me-1"></i> Generated <strong><?= count($assets) ?></strong> Asset Stickers ready for print.
        </div>
        <div>
            Paper Standard: <strong>A4 Portrait</strong> &bull; Thermal / Laser Sheet compatible.
        </div>
    </div>
</div>

<!-- A4 Printable Sheet Container -->
<div class="a4-sheet">
    <?php if (empty($assets)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-qr-code fs-1 text-secondary mb-2 d-block"></i>
            <h5>No assets matched the selected facility and category filters.</h5>
            <p class="small">Try selecting "All Facilities" or changing your category filter above.</p>
        </div>
    <?php else: ?>
        <div class="<?= $columns === 2 ? 'labels-grid-2' : 'labels-grid-3' ?>">
            <?php foreach ($assets as $a): ?>
                <div class="sticker-card">
                    <div class="sticker-qr">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($a['barcode_qr'] ?? $a['asset_code']) ?>" alt="QR">
                    </div>
                    <div class="sticker-info">
                        <div class="sticker-brand">GRM APPARELS</div>
                        <div class="sticker-code"><?= e($a['asset_code']) ?></div>
                        <div class="sticker-name"><?= e($a['name']) ?></div>
                        <div class="sticker-loc"><?= e($a['location_display'] ?? '') ?></div>
                        <?php if (!empty($a['serial_number'])): ?>
                            <div class="sticker-sn">SN: <?= e($a['serial_number']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
