# Concern Isolation — Phase 1 Module and Data Map

## Purpose and status

This document records the current application's operational module map for concern isolation. It maps the configured module keys to their route families, actions, facility relationships, and principal database tables, and calls out where ownership is direct, inherited through a facility/parent record, or not yet defined.

This is a static source/schema review, not a claim that the listed workflows are already isolated. Individual queries and indirect helper calls still need to be scoped and tested during implementation.

## Confirmed isolation rules

1. A concern is an isolated operational workspace, not a fixed facility type or preset department.
2. The administrator chooses any supported combination of submodules for a concern. For example, a concern may contain all production modules, or only Shop inward, outward, billing, returns, and inventory. Those combinations are user/admin choice; they are not inferred from a concern name.
3. One concern can own multiple facilities of its enabled types. Production units, warehouses, shops, and Website/WhatsApp sales channels are facility/channel records belonging to a concern; Infrastructure Management remains the administration surface for those records.
4. Product and SKU definitions may be shared reference data. Stock quantities, stock movements, operational documents, billing, returns, exchanges, and expenses are concern-specific unless a later explicit policy says otherwise.
5. A staff user can be assigned to multiple concerns, but operates in one active concern at a time. Access requires both the staff role's action permission and the active concern's selected module.
6. Existing operational rows without a concern remain unassigned and Admin-only. They must not be copied or silently assigned when a concern is created.
7. Normal transactions cannot combine concerns. A cross-concern stock movement must use a linked source outward, in-transit state, and destination inward receipt.

`Role::getHierarchicalPermissions()` is the source of the selectable submodule keys. `Role::DEPT_MODULE_MAP` is a legacy staff/department grouping; it must not be treated as a fixed concern-type list. See [Role.php](../app/Models/Role.php#L15) and [Concern.php](../app/Models/Concern.php#L186).

## Access and ownership model in the current code

| Concern-isolation concept | Current representation | Phase 1 implication |
|---|---|---|
| Concern and selected modules | `concerns`, `concern_modules` | `module_key` is the admin-selected feature boundary. |
| Staff membership | `user_concerns` | Membership grants eligibility to select a concern; it does not itself grant module actions. |
| Active concern | PHP session key `active_concern_id` | Operational reads/writes must resolve and validate this ID on every request. |
| Role/action access | `permissions`, `role_permissions`, and `user_permissions` | Must be checked together with active-concern module membership. |
| Facility ownership | Nullable `concern_id` on selected facility/operational tables from the ownership migration | Facility ownership must be checked on list, lookup, detail, update, and delete—not just create. |
| Historical/unassigned ownership | `concern_id IS NULL` | Not visible to ordinary concern-scoped staff; no automatic backfill. |

### Phase 2 — concern module access enforcement

Phase 2 adds the concern's selected module boundary to `hasPermission()`, so existing navigation checks and controller-level permission checks require both a role action and the active concern's module. `PermissionMiddleware` reports missing concern selection/module separately from a missing role action. Authenticated routes without that middleware use a router-level module/action policy, including report export/print endpoints and module APIs; an unclassified authenticated route fails closed. Production inward and cash-flow permissions are now included in the selectable concern-module hierarchy.

The temporary router/middleware lockout remains for most non-Super-Admin operational routes because the subsequent data-isolation phases are not complete. A selected module therefore does not imply that staff can safely use every workflow yet. This feature gate does not itself isolate database rows or make unassigned historical records visible. See [PermissionMiddleware.php](../app/Middleware/PermissionMiddleware.php), [Response.php](../app/Helpers/Response.php), and [web.php](../routes/web.php).

## Facility and stock ownership columns currently added by migration

The incremental migration adds nullable `concern_id` to:

| Ownership family | Tables with a direct `concern_id` added by the migration |
|---|---|
| Facilities/channels | `production_units`, `warehouses`, `shops`, `sales_channels` |
| Stock ledger | `stock_balances`, `stock_transactions`, `stock_adjustments` |
| Production output | `production_batches`, `production_batch_items`, `production_outwards` |
| Production receipts | `production_inwards`, `production_inward_items` |
| Warehouse distribution | `warehouse_transfers`, `transfer_items`, `warehouse_allocations`, `allocation_items`, `allocation_outwards` |

The migration intentionally leaves existing values `NULL`; it does not backfill or duplicate records. Its table list is not the complete list of operational tables below. See [add_concern_ownership_to_inventory.php](../database/migrations/add_concern_ownership_to_inventory.php#L10).

## Module, route, facility, and table map

Routes below refer to route families in [web.php](../routes/web.php). A family includes its list/create/store/view/edit/update/delete/status/approve/receive/print/export actions and associated AJAX/lookup endpoints where present; route-specific exceptions are noted. The table lists are the principal operational tables, including important joins and stock side effects, not every lookup/reference table.

### Production

| Concern-selectable key(s) | Route/action families | Facility relationship | Principal tables and isolation notes |
|---|---|---|---|
| `production` | `/production/dashboard` | Aggregates work associated with production units; dashboard currently filters through `production_units.concern_id`. | `production_programs`, `production_units`, `cutting_batches`, `stitching_batches`, `qc_batches`, `production_batches`, `production_outwards`, `production_inwards`, related items and products. Dashboard scoping does not imply all linked detail/action routes are scoped. |
| `programs`, `cutting`, `stitching`, `labels`, `qc` | `/production/programs`, `/production/cutting`, `/production/stitching`, `/production/labels`, `/production/qc`; create/update/approve/status/delete/inspection plus detail and scan APIs. | Programs point to `production_unit_id`; most stage records point to a program. | `production_programs`, `production_program_items`, `production_program_rolls`, `cutting_batches`, `cutting_items`, `cutting_batch_rolls`, `stitching_batches`, `stitching_items`, `label_batches`, `label_items`, `production_deleted_label_codes`, `qc_batches`, `qc_items`, plus `production_units`, `products`, `product_variants`, `seller_rolls`, users. Several stage tables have no direct concern column in the ownership migration; their owner must be validated through the program/unit chain. |
| `sellers`, `damages`, `catalog`, `wastage`, `stitching_wastage` | `/production/sellers` and roll/photo/label APIs; production damages; catalog; wastage receipts; stitching-wastage receipts. | Seller and roll records feed production programs; damages/wastage are production floor activity. | `sellers`, `seller_rolls`, `seller_roll_deleted_labels`, `roll_damages`, `roll_damage_reasons`, `wastage_sales`, `stitching_wastage_sales`, plus `products`, categories and program/production records. Direct concern ownership is not established by the current ownership migration. |
| Route module key requires confirmation | `/production/inventory-damages/**` and scan API; list/create/view/status update for finished-goods damage. | Damage records reference a production unit, QC batch, program, and product. | `production_inventory_damages`, `qc_batches`, `qc_items`, `production_programs`, `production_units`, products/variants. The damage table has `production_unit_id` but no direct concern column and is not in the ownership migration. |
| `batches`, `inventory`, `outward` | `/production/batches`, `/production/inventory`, `/production/outward`; create/approve/dispatch, labels, QR/detail APIs. | Production batch links to a program/unit and may dispatch to a warehouse. | `production_batches`, `production_batch_items`, `production_outwards`, `production_programs`, `production_units`, `warehouses`, `stock_balances`, `stock_transactions`, products/variants, QC and users. Some core batch/outward/stock tables have direct nullable ownership; facility endpoints and cross-facility validation still need implementation. |
| `production_inward` (route permission key; not selectable in current hierarchy) | `/production/inward`; receive and lookup. | Receives into a production unit from a warehouse, shop, or other source flow. | `production_inwards`, `production_inward_items`, source batch/allocation/receipt records, `production_units`, `warehouses`, `shops`, `stock_balances`, `stock_transactions`, products/variants. Direct concern columns exist on inward records from the migration, but source and destination must be proven compatible. Add this key to the selectable hierarchy or define the intended controlling module. |
| `production_billing`, `production_billing_returns` | `/production/billing`; bills, item search, print, returns lookup/process/invoice. | Inventory is linked through QC batch → program → production unit; validate this chain for billing and return operations. | `production_bills`, `production_bill_items`, `production_bill_item_tags`, `production_bill_returns`, `production_bill_return_items`, `production_return_inventory`, and QC/program/product/variant records. These billing/return tables are not in the concern ownership migration; the facility path must not be inferred only from the route prefix. |
| `production_expenses` | `/production/expenses`; expense CRUD/receipt and item lookup/quick-add. | Linked to a production unit, production work, or both; the exact parent must be established per record. | `production_expenses`, `production_expense_items`, related program/unit/user and expense item/reference data. Not in the concern ownership migration. |
| `production_cash` (route permission key; not selectable in current hierarchy) | `/production/cash`; open/close register, expense, cash-in, bank deposit, vouchers. | Register is keyed by `production_unit_id`. | `production_cash_registers`, `production_cash_transactions`, `production_cash_expenses`, `production_cash_deposits`, `production_units`, users. These tables are created by a separate migration and have no direct concern column. Add the key to the selectable module hierarchy or otherwise define how the admin enables this workflow. |
| `warehouse_rework` | `/production/warehouse-rework`; create/status/complete/redispatch/delete. | Rework bridges an originating warehouse item/outward and a production unit. | `production_warehouse_reworks`, warehouse damage/outward/batch/variant/product records, production units, users. No direct concern column is added by the ownership migration; both ends require explicit validation. |

### Warehouse and inventory

| Concern-selectable key(s) | Route/action families | Facility relationship | Principal tables and isolation notes |
|---|---|---|---|
| `warehouse` | `/warehouse/dashboard`; `/warehouse/transfers` create/approve/receive. | Dashboard and standard transfers use warehouse/shop facilities. The existing transfer workflow is facility-level and is not by itself the requested concern-to-concern outward/in-transit/inward protocol. | `warehouses`, `shops`, `warehouse_transfers`, `transfer_items`, `stock_balances`, `stock_transactions`, products/variants. Transfer facility IDs must be checked against the active concern. |
| `inward` | `/warehouse/inward`; receive, verify, scan, cross-check, batch-history and QR/label endpoints. | Receives production output and purchase/vendor goods into a warehouse. | `production_batches`, `production_batch_items`, `production_outwards`, `purchase_orders`, `purchase_items`, `purchase_inwards`, `purchase_inward_items`, `warehouses`, `warehouse_damages`, `stock_balances`, `stock_transactions`. |
| `stock` | `/warehouse/stock`, detail, QR regeneration, quick inward, price edits, adjustments and approval; stock hierarchy APIs. | Stock balance is keyed by location type/id, including warehouses and shops. | `stock_balances`, `stock_transactions`, `stock_adjustments`, `product_variants`, `products`, `warehouses`, `shops`, `locations`, category/brand/size/color data. The stock service currently performs location-based reads/writes; the concern must also be carried/validated. |
| `damage` | `/warehouse/damage`; create/view/resolve and scan lookup. | Damage belongs to a warehouse/stock location. | `warehouse_damages`, warehouses, product/variant data, `stock_balances`, `stock_transactions`. Damage tables are not in the ownership migration. |
| `allocation`, `allocation_outward`, `wh_batches` | `/warehouse/allocation` create/approve/pick/pack/dispatch; allocation outward/delivered/gatepass; allocation QR APIs. | Typically warehouse → shop/channel; may involve multiple facilities, each of which must be owned/allowed by the transaction's concern. | `warehouse_allocations`, `allocation_items`, `allocation_outwards`, `warehouses`, `shops`, `sales_channels`, stock tables, products/variants and category data. Allocation tables have nullable concern fields, but transaction endpoints must verify all endpoints belong to the same concern. |
| `returns` | `/warehouse/returns`; receive/view/inspect/classify. | Returns from shop/channel to warehouse. | `warehouse_returns`, `warehouse_return_items`, `warehouse_allocations`, `allocation_items`, source/destination facilities, stock balances/transactions. These return tables are not included in the ownership migration. |
| `shop_requests` | `/warehouse/shop-requests`; list/create/store/view. | Shop request to warehouse; online channel stock requests have separate channel workflows. | Request records, shops, warehouses, product/variant and allocation data; confirm the exact backing request table and its ownership in implementation. |
| `wh_sellers`, `purchases`, `purchases_returns` | `/warehouse/sellers`, seller products/quick-add, direct purchase create/edit/receive, purchase returns lookup/process/view/invoice. | Supplier procurement is received into a warehouse; purchase return records must remain with the originating concern/facility. | `warehouse_suppliers`, `sellers`, `purchase_orders`, `purchase_items`, `purchase_inwards`, `purchase_inward_items`, `returns`, `return_items`, customers, stock balances/transactions. Purchase and return tables are not in the ownership migration. |
| `wh_catalog`, `wh_labels` | `/warehouse/catalog`; category tree, attribute/default product APIs, full hierarchy create/update; QR labels and warehouse QR endpoints. | Catalog/reference data supports warehouse assortment and barcode operations. | `warehouse_categories`, `warehouse_subcategories`, `warehouse_sub_subcategories`, `wh_category_attributes`, `wh_category_default_products`, `warehouse_qr_codes`, product/variant and brand/category data. Confirm whether each catalog layer is shared reference data or concern-specific assortment. |
| `warehouse_expenses` | `/warehouse/expenses`; list/create/store/voucher/delete. | Expense is associated with a warehouse location. | Shared `expenses`, `expense_categories`, users, warehouse. Expense table is not in the ownership migration; `location_type/location_id` must resolve to an owned facility. |
| `sales_channels` | Infrastructure and channel setup/quick-add APIs. | A channel record represents a Website/WhatsApp connection and belongs to one concern; `channel_type` describes it, not the concern type. | `sales_channels` and channel-linked allocations, sales, damage, returns and stock. The migration adds `concern_id` to channels; channel selectors and CRUD routes still need ownership enforcement. |

### Shop, Website, and WhatsApp operations

| Concern-selectable key(s) | Route/action families | Facility relationship | Principal tables and isolation notes |
|---|---|---|---|
| `shop`, `pos`, `sales`, `customers` | `/shop/dashboard`, `/shop/pos` scan/customer lookup/sale/invoice, `/shop/sales` and detail; customer directory CRUD. | Every POS operation must use a shop owned by the active concern. Customer records may be linked to sales and history. | `sales`, `sale_items`, `customers`, `product_variants`, `products`, shop records and cash ledger tables. `sales` is a shared table for shop and online channels, so `sale_type`, `shop_id`, and channel discriminator checks must be consistent. Customer directory sharing is a policy decision still to resolve. |
| `shop_inward`, `shop_stock`, `shop_damage` | `/shop/inward` receive/verify/scan/draft/lookup; `/shop/inventory` details/request/price update/search; `/shop/damage` create/status/lookup. | Shop receives from warehouse allocation and holds its own inventory. | `warehouse_allocations`, `allocation_items`, `allocation_outwards`, `shops`, `stock_balances`, `stock_transactions`, `shop_damages`, products/variants, QR/labels. Shop and stock facility validation is required on all APIs. |
| `shop_returns`, `return_to_warehouse` | `/shop/returns` lookup/process/view/invoice; return-to-warehouse create/dispatch/receipt paths. | Customer return is tied to original shop sale; warehouse return crosses facilities. | Shared `returns`, `return_items`, `exchanges`, `exchange_items`, `sales`, `sale_items`, `customers`, stock ledgers, shop/warehouse and allocation records. Controller and generic service return paths both need checks against the originating shop and concern. |
| `shop_cash` | `/shop/cash`; register opening/closing, expenses, cash-in, deposits, vouchers/status. | Shop register uses `shop_id`. | `cash_registers`, `cash_transactions`, `cash_expenses`, `bank_deposits`, shops, sales, users. The cash schema is shop-linked but not directly concern-tagged; ownership must be validated through the shop ID. |
| `whatsapp_dashboard`, `whatsapp_orders`, `whatsapp_inward`, `whatsapp_inventory`, `whatsapp_damage`, `whatsapp_returns`, `whatsapp_return_to_wh`, `whatsapp_customers`, `whatsapp_expenses` | `/warehouse/whatsapp-orders/**` dashboard/history, inward, allocations/inventory, requests, damage/outward, returns, customers, expenses and warehouse-return endpoints; plus `/sales/whatsapp/**` order operations. | Channel selectors resolve only WhatsApp channels owned by the active concern; demo/global channel fallbacks have been removed. | Shared `sales`, `sale_items`, `returns`, `return_items`, `exchanges`, `exchange_items`, `customers`; `sales_channels`, `warehouse_allocations`, `allocation_items`, stock balances/transactions, `channel_damages`, channel receipts/expenses and facility links. Discriminator-based ownership must not permit reading or processing another channel's record. |
| `website_dashboard`, `website_orders`, `website_inward`, `website_inventory`, `website_damage`, `website_returns`, `website_return_to_wh`, `website_customers`, `website_expenses` | `/warehouse/website-orders/**` dashboard/order/inward/inventory/damage/return/customer/expense families and `/sales/website/**` order processing; includes APIs, invoice, manifest and return-to-warehouse actions. | Selectors resolve only Website channels owned by the active concern; demo/global channel fallbacks have been removed. | Shared sales, return/exchange, customer, stock and allocation tables; `sales_channels`, `channel_damages`, products/variants and channel operational records. Operational transaction ownership remains a separate data-isolation requirement. |

### Reports, assets, shared master data, and administration

| Module key(s) | Route/action families | Facility relationship | Principal tables and isolation notes |
|---|---|---|---|
| `reports_overview`, `reports_profitability`, `reports_sales`, `reports_online`, `reports_website`, `reports_whatsapp`, `reports_production`, `reports_warehouse`, `reports_purchase`, `reports_shop`, `reports_inventory`, `reports` | `/reports`, `/reports/overview`, `/reports/{profitability,sales,online,website,whatsapp,production,warehouse,purchase,shop,inventory}`, plus `/reports/export/{type}`, `/reports/pdf/{type}`, `/reports/print/{type}`. | Reports combine facility, date, sale/channel and inventory data. Each report and all export/print handlers must apply concern scope, not only the screen's permission. | `sales`, `sale_items`, `returns`, `purchase_orders`, `purchase_items`, production tables, warehouse allocations/damages, stock balances, expenses and facility tables, plus customers/suppliers/products. Reports are cross-module joins and are not covered by the core ownership migration. |
| `assets`, `asset_scanner`, `asset_transfers`, `asset_maintenance`, `asset_bulk_labels` | `/asset/**` dashboard/list/scanner/create/view/edit/delete, inter-unit transfers, maintenance, label and lookup/category APIs. | Asset location is represented by `location_type` plus `location_id` pointing to production unit, warehouse, shop, or office. Asset queries derive scope from that owning facility; Office assets are Super Admin-only. | `assets`, `asset_categories`, `asset_transfers`, `asset_maintenance_logs`, `asset_audits`, and facility/user records. Asset-location ownership is enforced in `AssetService`; historical transaction isolation outside the asset-to-facility chain remains separate. |
| `master` | `/master/**` reference data CRUD and `/api/**` quick-add/search/cascade endpoints. Product/category/brand/size/color/units routes often delegate to production catalog; locations are warehouse racks/shelves/bins. | Products and SKU definitions may be shared. A storage location belongs to a warehouse; warehouse facilities themselves are concern-owned. Supplier/customer/business directories need an explicit sharing policy. | Shared/reference candidates: `products`, `product_variants`, `categories`, `subcategories`, `sub_subcategories`, `brands`, `sizes`, `colors`, `units`. Warehouse-specific records: `locations`, warehouse catalog tables, sellers/suppliers and customers as policy requires. Auth-only AJAX endpoints need module checks. |
| `staff`, `roles` | `/admin/staff/**`, `/admin/roles/**`, concern administration under `/admin/staff/concerns/**`; staff and role assignment/actions. | Staff membership is many-to-many with concerns; active concern is per session. Staff's single facility fields are separate from membership and must be compatible with active concern. | `users`, `roles`, `permissions`, `role_permissions`, `user_permissions`, `concerns`, `concern_modules`, `user_concerns`; facility tables for staff assignments. |
| `dashboard` | `/admin/dashboard` executive dashboard and Infrastructure Management tabs. | Infrastructure panel manages multiple facilities/channels within the selected concern. Current dashboard aggregation itself loads facilities and operational totals globally. | Facility tables, products, stock, sales, production programs/QC, allocations, returns, audit logs; those aggregate queries are not concern-filtered. Treat this as Admin-wide until separately scoped. |
| `audit`, `settings`, `backup`, `notifications` | `/system/audit/**`, `/system/settings/**`, `/system/backup/**`, `/system/notifications/**` and unread API. | Audit may need concern attribution for operational actions; company-wide settings/backups are system-wide unless a deliberate concern-specific settings policy is introduced. Notifications are user/system records. | `audit_logs`, `settings`, `backups`, `notifications`, users and all data included in backups. Backup/restore and wipe operations are inherently global and should remain restricted to authorized administrators. |

## Ownership and boundary gaps recorded for later phases

1. **The ownership migration is intentionally partial.** Many operational tables in the map do not have a direct concern field: programs and process-stage records, suppliers/purchases, customer sales/returns/exchanges, warehouse/shop/channel damages and returns, expenses, cash ledgers, assets, and reporting data. Each must either gain direct ownership or have a rigorously validated parent facility/document chain.
2. **Stock ledger APIs currently use location identity.** The domain path goes through location type/id and variant; concern must be derived from and checked against the facility on each read/write. Adding a nullable `concern_id` column alone does not enforce that equality.
3. **Shared sales/returns tables create cross-workflow risk.** Shop POS, Website, and WhatsApp share `sales`, `returns`, and exchange tables. Every lookup, detail, invoice, return, exchange, payment, stock mutation, and report must validate both the transaction's concern and its channel/shop discriminator.
4. **Concern-owned facility enforcement (Phase 3).** Production units, warehouses, shops, and sales channels are created with the active concern and their list/find/update/delete/status/quick-add paths are concern-scoped. Shared selector helpers and staff facility assignment validation use the active concern and module family. Asset location reads derive ownership from the facility. Remaining operational transaction queries are tracked separately and are not implied to be complete by this phase.
5. **Current facility transfers are not yet the agreed cross-concern transfer protocol.** Warehouse allocations and warehouse transfers support existing facility movements. They do not prove the required linked source outward → in-transit → destination inward lifecycle between concerns.
6. **Module selection and route keys have mismatches.** Production cash routes use `production_cash`, and production inward routes use `production_inward`; both appear in route/legacy department references but are absent from the selectable concern hierarchy. These modules cannot currently be chosen through the hierarchy-backed concern editor. Add and describe them or explicitly map their routes to another selectable key. Warehouse, Shop, Website, and WhatsApp route middleware keys were compared with the hierarchy and are present.
7. **Some routes do not declare a module permission at route level.** In particular, master-data and several quick-add/search endpoints, some facility APIs, report export/print, and selected Website handlers use only authentication. They need explicit permission/module checks and concern validation before staff access is enabled.
8. **Reference-data sharing still needs a precise policy.** Shared product definitions are confirmed. Customer, seller/supplier, category/catalog customizations, asset categories, and global settings should not be assumed shared or isolated without deciding which are reference records and which contain concern-specific business data.
9. **Super Admin/global views are intentionally separate from staff scope.** Existing unassigned records and genuinely system-wide settings/backup operations remain available only to authorized Admin workflows; ordinary active-concern screens must not include them.

## Phase 1 completion checklist

- [x] Record user-confirmed isolation rules and flexible module selection (no fixed concern archetypes).
- [x] Distinguish concerns (operational boundary) from facilities/channels (locations within a concern).
- [x] Inventory selectable module keys and corresponding route families, including APIs, lifecycle actions, reports, and administrative surfaces.
- [x] Map principal tables and facility/parent relationships for production, warehouse/inventory, shop/POS, Website/WhatsApp, reports, assets, and shared/system data.
- [x] Record the direct ownership columns added by the current migration and the major uncovered table families.
- [x] Record route/permission and business-data classification gaps for subsequent isolation phases.

Phase 1 is complete as a reviewed implementation map. The open policies and gaps above are explicit inputs to the next implementation phase; they are not silently treated as completed isolation.

## Phase 3 — facility ownership implementation status

- [x] New production units, warehouses, shops, and Website/WhatsApp channels require an active concern and persist its ID.
- [x] Facility/channel list and lookup models filter by the active concern; assigned staff are additionally restricted to their assigned facility.
- [x] Infrastructure forms and alternate quick-add endpoints scope reads, edits, deletes, and activation changes to the active concern.
- [x] Shared facility/channel selectors filter by concern, compatible module family, and staff facility assignment.
- [x] Staff assignments are validated against the staff member's concern memberships, enabled module family, facility activity, and channel type.
- [x] Asset create/edit/search/list/dashboard/history paths derive scope from the owned facility; Office assets are restricted to Super Admin.
- [x] Warehouse return lookup/list/status updates and related allocation-tag reads are restricted through concern-owned warehouse/channel records.
- [x] Inward batch/allocation lookup and sales-channel stock resolution no longer use global/demo facility IDs; they validate active concern and assigned warehouse/channel scope.

PHP syntax checks and `git diff --check` passed for the implementation. Database-backed workflow tests were not run in this environment; the checklist records implemented guards and does not claim verification against live records. Phase 3 does not complete the separate end-to-end concern scoping of every operational transaction, report, or cross-concern transfer workflow described in the gap list.

## Phase 7 — legacy and unassigned data

- Existing rows with `concern_id IS NULL` remain unassigned; concern creation does not backfill or copy operational data.
- Super Admin can use **Staff → Legacy Concern Data** (`/admin/legacy-data`) to compare unassigned counts, total counts, and per-concern counts for ownership-enabled operational tables.
- The console lists only rows that remain unassigned. Super Admin may deliberately assign up to 50 selected row IDs at a time to an active concern. Each assignment is conditional on the row still having a null concern, performed transactionally, and written to the audit log.
- The tool only updates the selected table rows. Related records with their own `concern_id` must be reviewed and assigned separately; source/destination transfer workflow data is excluded from generic assignment.
- No migration or backfill is required for Phase 7. The ownership migrations must already be applied for their tables to appear as available.

## Production concern setup behavior

- Production billing and supplier purchases are currently associated with a production unit as well as the concern. Production billing must also draw from finished inventory generated by production/QC workflows.
- The concern module selector accepts production billing and billing returns as production-compatible modules, so unit selectors do not hide concern-owned units when those are the only production modules selected.
- Saving a concern with production operational modules creates a concern-owned **Main Production Unit** if the concern has none. This is a new empty facility, not inherited or copied legacy data; additional units remain manageable through Infrastructure Management.
- Existing concerns can receive the missing default unit by opening and saving the concern once after deployment. A new empty unit enables the workflow pages, but billing inventory remains empty until production/QC stock exists.

## Phase 8 — gradual operational module rollout

- Staff operational routes pass through the central router rollout gate after authentication and role/active-concern module checks. The gate covers routes with explicit `PermissionMiddleware`, the scoped concern-transfer middleware, and routes whose permissions are inferred by the router. An authenticated operational route without a resolvable module policy remains fail-closed.
- The temporary blanket lockout is now a module allowlist in `config/concern_module_rollout.php`. A module is enabled for staff only when it is listed there **and** the existing route permission checks confirm that the active concern selected it and the staff role grants the requested action.
- The allowlist is intentionally empty. The isolation map records outstanding unscoped workflows, and database-backed end-to-end checks have not been performed, so no operational module currently meets the release criterion. This leaves staff operational routes unavailable while Super Admin retains the existing administrative access.
- The prior special bypasses for `/production/dashboard` and concern transfers have been removed for staff. Dashboards, APIs, quick actions, writes, reports, and prints do not become available based on navigation visibility alone.
- To roll out a module, first complete and test its whole route/API/action/report workflow, then add its exact selectable module key to the allowlist. Keep incomplete related module keys disabled. Do not add a key based solely on a successful syntax check or a scoped list page.
- Global staff/system administration routes remain governed by their existing global permissions and are not operational module rollout entries.

## Phase 9 — regression verification status

- [x] Added a dependency-free route regression harness at `tests/concern_isolation_rollout_test.php`. It dispatches every authenticated non-global operational route through the real router and asserts the route cannot reach a controller while operational modules remain disabled.
- [x] The harness checks direct production URLs under different active concern module selections, denied role permissions, an altered transfer receipt ID, and AJAX requests.
- [ ] Database-backed two-concern tests remain required for facility ownership, stock mutations, billing, returns, transfer receipt/discrepancies, linked-row integrity, unassigned legacy visibility, reports/search/export/print, and refresh/replay behavior.
- [ ] No operational module should be added to the rollout allowlist until its complete database-backed isolation suite passes against an isolated test database. The current allowlist stays empty and staff operations remain locked out.

Phase 9 is **not fully complete**: the staff lockout and route-gate behavior are regression tested, but the repository has no safe test database or existing fixture framework configured here. The full cross-concern data-path acceptance criteria cannot be verified without a dedicated test database and representative fixtures; no production or developer database was modified.
