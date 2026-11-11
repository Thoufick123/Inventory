# Inventory Workspace Rules

## Concern Isolation & Active Module Boundaries
- **Concern scope is mandatory**: Every operational read, lookup, mutation, and stock movement must validate against `currentConcernId()` (and `currentProductionUnitId()` when staff is assigned to a specific production unit). Never fall back to global or unscoped records for ordinary concern-scoped staff.
- **Permissions require both role and concern module**: `hasPermission($module, $action)` in `app/Helpers/Response.php` checks role permission AND `concernHasModule($module)` (except global admin modules). Never bypass `PermissionMiddleware` or router-level policy in `routes/web.php`.
- **Retired modules stay closed**: Warehouse, Shop, Website Orders, WhatsApp Orders, Infrastructure CRUD (`admin/{production-units,warehouses,shops,channels}/`), and retired reports are blocked in `Router::isRetiredModuleRoute()` (`routes/web.php`), `isRetiredConcernModule()` (`app/Helpers/Response.php`), and `Role::getHierarchicalPermissions()` (`app/Models/Role.php`). Do not re-expose or route new concern features (such as Sulur Shop) through retired `/shop/*` or `/warehouse/*` endpoints—use concern-scoped Production & Inventory workflows instead.
- **`production_inward.receive` permission**: `Role::getCrudPermissionsByAction()` maps `production_inward` `receive` under the `create` column and `Role::filterCrudPermissionIds()` explicitly preserves it. Keep both aligned whenever modifying permission matrices.

## Concern-Scoped Production Inventory & Inward Rules
- **`CONCERN` stock location does not require a facility module**: Concerns (e.g., Sulur Shop) can hold shared inventory directly with `location_type = 'CONCERN'` and `location_id = $concernId`. `StockService::assertLocationBelongsToActiveConcern('CONCERN', $locationId)` validates against active `concerns` directly without calling `Concern::hasFacilityModules()`.
- **No pre-receipt stock credit**: Packing or outward-dispatching a production batch to `destination_concern_id` must NOT credit destination stock. Destination stock (`AVAILABLE` and `DAMAGED`) is posted only when `ProductionInwardService::receiveConcernBatch()` records a `PRODUCTION_BATCH` receipt (`production_inwards` / `production_inward_items`).
- **Piece-level QR verification & discrepancies**:
  - Dispatched batches must have one unique QR tag per piece (`outward_piece_tags`). Duplicate QR scans and unknown QR codes outside `expectedTags` + `extraTags` (`approved_piece_tags`) must be rejected.
  - Partial/short deliveries, damaged pieces, and recognized excess approved pieces are allowed and explicitly recorded (`missing_codes`, `damaged_codes`, `excess_codes`); `production_outwards.status` becomes `'DISCREPANCY'` when any damaged, missing, or excess count is > 0, or `'RECEIVED'` when exact.
  - Post-receipt corrections via `ProductionInwardService::updateConcernBatchPieceStatuses()` must mutate `stock_balances` by delta (never double-posting) and keep `production_inward_items`, `production_batch_items`, `production_batches`, and `production_outwards` synchronized inside a transaction.
- **Unified Production Inventory UI**: Keep QC-output batches and concern/unit inwarded stock (`ProductionInwardService::getStockAcrossUnits()`) together on `views/production/inventory/index.php` rather than creating a separate inward-stock screen. Preserve working List/Cards switching (`switchBatchView`), live search/pagination, and the `View Items` flows (`production/inventory/batch/{id}` and `production/inventory/inward/{variantId}`).

## Verification
After modifying concern, permission, inward, or inventory code, run PHP syntax checks (`php -l`) on touched files and run the regression suite:
```powershell
php tests/production_concern_inward_scans_test.php
php tests/production_inward_permission_test.php
php tests/stock_concern_location_test.php
php tests/concern_isolation_rollout_test.php
php tests/concern_retired_modules_test.php
```
Note: On Windows PowerShell in this environment, `rg` is not installed—use `Select-String` instead.

