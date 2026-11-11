---
name: concern-production-inward-inventory
description: >-
  Guides development, debugging, and UI updates for concern-scoped production
  batch labels, cross-concern outward dispatch, piece-level inward scanning with
  partial/excess/damaged/missing reconciliation, and unified production inventory
  views (such as Sulur Shop). Use when modifying production packing, batch labels,
  Production Inward / GRN, CONCERN stock balances, or production inventory
  Cards/List and View Items pages.
---

# Concern-Scoped Production Inward & Inventory Workflow

Use this skill when working on cross-concern production batch dispatch, inward receipt scanning (e.g., Sulur Shop receiving stock without warehouse/shop modules), discrepancy reporting, or unified QC + inward stock inventory UI.

## Architecture & File Map

- **Packing & Batch Labels**:
  - Controller: `ProductionController::createBatch()`, `storeBatch()`, `printBatchQRLabel()` in [app/Controllers/ProductionController.php](../../app/Controllers/ProductionController.php)
  - Model: `ProductionBatch::getItems()` in [app/Models/ProductionBatch.php](../../app/Models/ProductionBatch.php) (resolves `approved_piece_tags` and `outward_piece_tags`)
  - Views: [views/production/batches/create.php](../../views/production/batches/create.php) (`destination_concern_id` selector requires active concern with `production_inward` module and an active production unit), [views/production/batches/label.php](../../views/production/batches/label.php) (printable batch QR label)
- **Outward Dispatch & Discrepancy Reporting**:
  - Controller: `ProductionController::outwardList()`, `storeOutward()`, `outwardDetailsAjax()` in [app/Controllers/ProductionController.php](../../app/Controllers/ProductionController.php)
  - Model & View: [app/Models/ProductionOutward.php](../../app/Models/ProductionOutward.php), [views/production/outward/index.php](../../views/production/outward/index.php) (renders `Discrepancy` table from `ProductionInwardService::getConcernBatchDiscrepancies()`)
- **Concern Inward Scanning & Reconciliation**:
  - Service: [app/Services/ProductionInwardService.php](../../app/Services/ProductionInwardService.php)
  - Controller: `productionInwardIndex()`, `receiveConcernProductionBatch()`, `updateConcernBatchReceiptStatuses()` in [app/Controllers/ProductionController.php](../../app/Controllers/ProductionController.php)
  - View: [views/production/inward/index.php](../../views/production/inward/index.php)
- **Unified Production Inventory & View Items**:
  - Controller: `productionInventory()`, `productionInventoryBatch()`, `productionInwardInventoryVariant()` in [app/Controllers/ProductionController.php](../../app/Controllers/ProductionController.php)
  - Stock Guard: `StockService::assertLocationBelongsToActiveConcern()` in [app/Services/StockService.php](../../app/Services/StockService.php)
  - Views: [views/production/inventory/index.php](../../views/production/inventory/index.php), [views/production/inventory/inward_items.php](../../views/production/inventory/inward_items.php)
- **Routes, Permissions & Migration**:
  - Routes: [routes/web.php](../../routes/web.php) (`production/inward*`, `production/inventory*`, `production/batches/label/{id}`)
  - Permissions: `Role::getCrudPermissionsByAction()` & `Role::filterCrudPermissionIds()` in [app/Models/Role.php](../../app/Models/Role.php), [database/seed_permissions.php](../../database/seed_permissions.php)
  - Migration: [database/migrations/add_production_concern_batch_receipts.php](../../database/migrations/add_production_concern_batch_receipts.php)

## End-to-End Business Rules

1. **Batch Packing -> Outward -> Pending Inward**:
   - Packing targets `production_batches.destination_concern_id` (nullable `destination_warehouse_id` stays `NULL` for concern transfers).
   - Once `production_outwards.status = 'DISPATCHED'` and `production_batches.status = 'DISPATCHED'`, `ProductionInwardService::getPendingConcernBatches()` surfaces the batch in the receiving concern's `/production/inward` screen (provided no `RECEIVED` row exists in `production_inwards` for `source_type = 'PRODUCTION_BATCH'`).

2. **QR Normalization & Scanning Rules**:
   - Both JS (`views/production/inward/index.php`) and PHP (`ProductionInwardService::normalizeScannedPieceTag()`) accept:
     - Plain QR codes (trimmed, uppercased)
     - JSON QR payloads (`tag_code`, `piece_tag`, `qr_code`, or `code`)
     - Pipe-delimited codes (`TAG|SKU` -> `TAG`)
   - **Full batch QR scan**: Scanning `batch_number` marks all `expectedTags` as `ACCEPTED` and submits immediately, ONLY when `$labelsComplete` is true (every dispatched piece has a unique QR tag).
   - **Individual piece scans**:
     - Every dispatched tag is in `$expectedTags`; recognized approved tags from the same QC/label batch not included in `outward_piece_tags` are in `$extraTags` (`approved_piece_tags`).
     - Short/partial receipts are allowed: unscanned `expectedTags` become `missing_codes` / `missing_qr_codes`.
     - Scanned tags in `$extraTags` are recorded with `is_excess = true` in `excess_codes` / `excess_qty`.
     - Duplicate scans or tags outside `$expectedTags + $extraTags` throw `InvalidArgumentException`.

3. **Stock Posting & Piece Status Corrections**:
   - `receiveConcernBatch()` validates `StockService::assertLocationBelongsToActiveConcern($destinationType, $destinationId)`.
   - `CONCERN` destination requires `destinationId === currentConcernId()` and does NOT require a facility module (`PRODUCTION` / `WAREHOUSE` / `SHOP`) to be enabled.
   - Verified (`ACCEPTED`) pieces credit `stock_state = 'AVAILABLE'` (`INWARD`); `DAMAGED` pieces credit `stock_state = 'DAMAGED'` (`INWARD_DAMAGED`). Missing pieces are not credited.
   - `production_outwards.status` is set to `'DISCREPANCY'` if `totalDamaged > 0 || totalMissing > 0 || totalExcess > 0`, else `'RECEIVED'`.
   - `updateConcernBatchPieceStatuses()` lets authorized staff switch individual pieces between `VERIFIED`, `DAMAGED`, and `MISSING` and applies stock deltas via `applyReceiptStockDelta()`.

4. **Unified Production Inventory & View Items UI**:
   - `ProductionController::productionInventory()` loads both QC batches (`$batches`) and inwarded stock (`$transferredStock = ProductionInwardService::getStockAcrossUnits($inventoryUnitId)`).
   - `views/production/inventory/index.php` renders both inside the same list table (`#inventoryBatchList`) and card grid (`#inventoryBatchesGrid`):
     - Use `.batch-list-row` (`data-filter-match`, `data-search-text`) for table rows and `.batch-card-col` (`data-search-text`) for cards so `switchBatchView('list' | 'cards')`, `filterBatchesLive()`, and `renderBatchPagination()` handle QC and inward items uniformly.
   - Inward stock rows/cards link to `production/inventory/inward/{variantId}?location_type=...&location_id=...`, handled by `ProductionController::productionInwardInventoryVariant()` and rendered by `views/production/inventory/inward_items.php`:
     - Supports `Cards`, `List`, and `By Variant` toggles (`.inward-view-button`), live QR/receipt/batch/status search (`#inwardItemSearch`), and per-piece `Copy QR number` / `Print this item` actions.

## Verification Checklist

Run these commands after any change to inward scanning, permissions, concern stock, or inventory views:

```powershell
php -l app/Controllers/ProductionController.php
php -l app/Services/ProductionInwardService.php
php -l app/Services/StockService.php
php tests/production_concern_inward_scans_test.php
php tests/production_inward_permission_test.php
php tests/stock_concern_location_test.php
php tests/concern_isolation_rollout_test.php
php tests/concern_retired_modules_test.php
```

