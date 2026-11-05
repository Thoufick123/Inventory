# GRM Inventory Management System — API & Routing Catalog

## 1. Routing Overview

All application routes are defined in [`routes/web.php`](file:///c:/xampp/htdocs/grm-inventory/routes/web.php) and dispatched via the central `Router` class.
The router supports parameter matching via `{param}` placeholders (e.g. `admin/staff/edit/{id}`) and executes declared middleware prior to invoking the controller action.

---

## 2. Authentication & Administration Routes

| Method | Path | Controller | Action | Middleware | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | `AuthController` | `loginForm` | *None* | Root login page |
| `GET` | `/login` | `AuthController` | `loginForm` | *None* | Sign in view |
| `POST` | `/login` | `AuthController` | `login` | *None* | Authenticate user credentials & set session |
| `GET` | `/logout` | `AuthController` | `logout` | *None* | Terminate session & redirect to login |
| `GET` | `/admin/dashboard` | `AdminController` | `dashboard` | `Auth` | System executive overview & metrics |
| `GET` | `/admin/staff` | `AdminController` | `staffList` | `Auth, Perm:staff,view` | Employee & staff user directory |
| `GET` | `/admin/staff/create`| `AdminController` | `createStaff` | `Auth, Perm:staff,create` | Staff onboarding form |
| `POST`| `/admin/staff/store` | `AdminController` | `storeStaff` | `Auth, Perm:staff,create` | Save new user account |
| `GET` | `/admin/staff/edit/{id}` | `AdminController`| `editStaff` | `Auth, Perm:staff,edit` | Edit staff credentials & role |
| `POST`| `/admin/staff/update/{id}`| `AdminController`| `updateStaff` | `Auth, Perm:staff,edit` | Update staff record |
| `POST`| `/admin/staff/toggle/{id}`| `AdminController`| `toggleStaff` | `Auth, Perm:staff,edit` | Activate / Deactivate staff |
| `GET` | `/admin/roles` | `AdminController` | `roles` | `Auth, Perm:roles,view` | Role list & permission overview |
| `GET` | `/admin/roles/permissions/{id}` | `AdminController` | `managePermissions` | `Auth, Perm:roles,edit` | Matrix permission editor |
| `POST`| `/admin/roles/permissions/{id}` | `AdminController` | `savePermissions` | `Auth, Perm:roles,edit` | Save assigned permissions |
| `GET` | `/admin/audit` | `SystemController`| `auditLogs` | `Auth, Perm:audit,view` | Organization-wide audit trail |

---

## 3. Production & Manufacturing Routes

| Method | Path | Controller | Action | Middleware | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/production/dashboard` | `ProductionController` | `dashboard` | `Auth, Perm:production,view` | Factory KPIs, Unit 1 & Unit 2 summary |
| `GET` | `/production/sellers` | `ProductionController` | `sellerList` | `Auth, Perm:sellers,view` | Fabric roll vendors directory |
| `POST`| `/production/sellers/store` | `ProductionController` | `storeSeller` | `Auth, Perm:sellers,create` | Register fabric vendor & roll batch |
| `GET` | `/production/damages` | `ProductionController` | `damageList` | `Auth, Perm:damages,view` | Fabric roll defective log |
| `GET` | `/production/catalog` | `ProductionController` | `catalogList` | `Auth, Perm:catalog,view` | Garment design styles & variants |
| `POST`| `/production/catalog/store` | `ProductionController` | `storeCatalog` | `Auth, Perm:catalog,create` | Create garment product master |
| `GET` | `/production/programs` | `ProductionController` | `programList` | `Auth, Perm:programs,view` | Manufacturing planning programs |
| `POST`| `/production/programs/store`| `ProductionController` | `storeProgram` | `Auth, Perm:programs,create` | Save cutting & stitching program |
| `POST`| `/production/programs/approve/{id}` | `ProductionController` | `approveProgram` | `Auth, Perm:programs,approve` | Approve manufacturing order |
| `GET` | `/production/cutting` | `ProductionController` | `cuttingList` | `Auth, Perm:cutting,view` | Cutting section job cards |
| `POST`| `/production/cutting/store` | `ProductionController` | `storeCutting` | `Auth, Perm:cutting,create` | Record cut layers & roll meters |
| `GET` | `/production/wastage` | `ProductionController` | `wastageList` | `Auth, Perm:wastage,view` | Fabric scrap selling ledger |
| `GET` | `/production/stitching`| `ProductionController` | `stitchingList` | `Auth, Perm:stitching,view` | Tailor bundle stitching tracking |
| `GET` | `/production/qc` | `ProductionController` | `qcList` | `Auth, Perm:qc,view` | Quality control inspection portal |
| `POST`| `/production/qc/store` | `ProductionController` | `storeQC` | `Auth, Perm:qc,create` | Log A-Grade Pass & B-Grade Rework |
| `GET` | `/production/inventory`| `ProductionController` | `productionInventory` | `Auth, Perm:inventory,view` | Post-QC finished goods stock |
| `GET` | `/production/batches` | `ProductionController` | `batchList` | `Auth, Perm:batches,view` | Finished cartons & barcode labels |
| `GET` | `/production/outward` | `ProductionController` | `outwardList` | `Auth, Perm:outward,view` | Outward dispatch & gate passes |
| `GET` | `/production/expenses` | `ProductionController` | `expensesList` | `Auth, Perm:production_expenses,view` | Plant machinery & unit expenses |

---

## 4. Central Warehouse & Operations Routes

| Method | Path | Controller | Action | Middleware | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/warehouse/dashboard` | `WarehouseController` | `dashboard` | `Auth` | Central warehouse operations center |
| `GET` | `/warehouse/inward` | `WarehouseController` | `inwardList` | `Auth` | GRN receipt & QR verification |
| `POST`| `/warehouse/inward/store` | `WarehouseController` | `storeInward` | `Auth` | Receive factory batch into stock |
| `GET` | `/warehouse/stock` | `WarehouseController` | `stockList` | `Auth` | Real-time multi-bin stock balances |
| `GET` | `/warehouse/damage` | `WarehouseController` | `damageList` | `Auth` | Warehouse defective goods pool |
| `GET` | `/warehouse/allocation`| `WarehouseController` | `allocationList`| `Auth` | Store allocation orders |
| `POST`| `/warehouse/allocation/store` | `WarehouseController` | `storeAllocation` | `Auth` | Create shop allocation order |
| `POST`| `/warehouse/allocation/pick/{id}` | `WarehouseController` | `pickAllocation` | `Auth` | Confirm bin picking |
| `POST`| `/warehouse/allocation/pack/{id}` | `WarehouseController` | `packAllocation` | `Auth` | Pack cartons with barcode stickers |
| `POST`| `/warehouse/allocation/dispatch/{id}` | `WarehouseController` | `dispatchAllocation` | `Auth` | Security outward & gate pass |
| `GET` | `/warehouse/purchases` | `WarehouseController` | `purchaseList` | `Auth` | Direct vendor/customer purchases |
| `GET` | `/warehouse/purchases/returns` | `WarehouseController` | `purchaseReturnsIndex` | `Auth` | Direct purchase returns & RMA |
| `POST`| `/warehouse/purchases/returns/process` | `WarehouseController` | `processPurchaseReturn` | `Auth` | Process return/exchange transaction |
| `GET` | `/warehouse/transfers` | `WarehouseController` | `transferList` | `Auth` | Inter-location stock transfers |
| `GET` | `/warehouse/adjustments` | `WarehouseController` | `adjustmentList` | `Auth` | Stock count reconciliations |
| `GET` | `/warehouse/expenses` | `WarehouseController` | `expensesIndex` | `Auth` | Operational packaging & freight costs |

---

## 5. Retail Shop & Point of Sale (POS) Routes

| Method | Path | Controller | Action | Middleware | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/shop/dashboard` | `ShopController` | `dashboard` | `Auth` | Live store POS & sales overview |
| `GET` | `/shop/pos` | `ShopController` | `posInterface` | `Auth` | Fast scanner touch POS checkout |
| `GET` | `/shop/pos/scan/{code}` | `ShopController` | `scanQR` | `Auth` | Fast AJAX barcode lookup |
| `POST`| `/shop/pos/sale` | `ShopController` | `processSale` | `Auth` | Execute sale & print thermal slip |
| `GET` | `/shop/pos/invoice/{id}` | `ShopController` | `printInvoice` | `Auth` | Render 80mm / A4 customer bill |
| `GET` | `/shop/customers` | `ShopController` | `customersList` | `Auth` | Customer 360 CRM directory |
| `GET` | `/shop/inward` | `ShopController` | `inwardList` | `Auth` | Store receipt of warehouse allocation |
| `GET` | `/shop/inventory` | `ShopController` | `inventoryList` | `Auth` | Shop on-hand stock balances |
| `GET` | `/shop/returns` | `ShopController` | `returnList` | `Auth` | In-store customer return history |
| `POST`| `/shop/returns/process/{id}` | `ShopController` | `processReturn` | `Auth` | Process return & refund cash/UPI |
| `GET` | `/shop/exchanges` | `ShopController` | `exchangeList` | `Auth` | Same/multi-product exchange history |
| `GET` | `/shop/return-to-warehouse` | `ShopController` | `returnToWarehouseList` | `Auth` | Return defective/unsold to warehouse |
| `GET` | `/shop/cash` | `CashController` | `index` | `Auth` | Petty cash drawer register |
| `POST`| `/shop/cash/open` | `CashController` | `openRegister` | `Auth` | Open daily shift float |
| `POST`| `/shop/cash/expense` | `CashController` | `storeExpense` | `Auth` | Record daily tea/refreshment voucher |
| `POST`| `/shop/cash/close` | `CashController` | `closeRegister` | `Auth` | Close shift & lock cash drawer |

---

## 6. Digital Channels (WhatsApp & Website) Routes

| Method | Path | Controller | Action | Middleware | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/warehouse/whatsapp-orders` | `WhatsappController` | `index` | `Auth` | WhatsApp order management dashboard |
| `GET` | `/warehouse/whatsapp-orders/inward` | `WhatsappController` | `whatsappInwardIndex` | `Auth` | Channel stock allocations |
| `GET` | `/warehouse/whatsapp-orders/allocations` | `WhatsappController` | `whatsappAllocations` | `Auth` | Channel outward stock allocations & transfer notes |
| `GET` | `/warehouse/whatsapp-orders/customers` | `WhatsappController` | `whatsappCustomers` | `Auth` | WhatsApp buyer directory & CRM spend metrics |
| `GET` | `/warehouse/whatsapp-orders/damage` | `WhatsappController` | `whatsappDamageIndex` | `Auth` | Channel transit defective pool |
| `GET` | `/warehouse/whatsapp-orders/returns` | `WhatsappController` | `whatsappReturnsIndex` | `Auth` | Social commerce returns & exchanges |
| `GET` | `/warehouse/whatsapp-orders/expenses`| `WhatsappController` | `expensesIndex` | `Auth` | WhatsApp API & ad marketing costs |
| `GET` | `/warehouse/website-orders` | `WebsiteController` | `index` | `Auth` | Website eCommerce order dashboard |
| `GET` | `/warehouse/website-orders/inward` | `WebsiteController` | `websiteInwardIndex` | `Auth` | eCommerce warehouse allocations |
| `GET` | `/warehouse/website-orders/allocations` | `WebsiteController` | `websiteAllocations` | `Auth` | Website dispatch allocations & transfers |
| `GET` | `/warehouse/website-orders/customers` | `WebsiteController` | `websiteCustomers` | `Auth` | Website customer directory & lifetime value |
| `GET` | `/warehouse/website-orders/returns` | `WebsiteController` | `websiteReturnsIndex` | `Auth` | Online RMA customer returns |
| `GET` | `/warehouse/website-orders/expenses`| `WebsiteController` | `expensesIndex` | `Auth` | Server, Shopify plugins & ad costs |

---

## 7. Reporting & Financial Analytics Routes

| Method | Path | Controller | Action | Middleware | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/reports` | `ReportController` | `index` | `Auth, Perm:reports,view` | Executive management report hub |
| `GET` | `/reports/production` | `ReportController` | `productionReports` | `Auth, Perm:reports,view` | Manufacturing yield & efficiency |
| `GET` | `/reports/warehouse` | `ReportController` | `warehouseReports` | `Auth, Perm:reports,view` | Logistics, turnover & damages |
| `GET` | `/reports/sales` | `ReportController` | `salesReports` | `Auth, Perm:reports,view` | Multi-channel sales & GST liabilities |
| `GET` | `/reports/website` | `ReportController` | `websiteReports` | `Auth, Perm:reports,view` | eCommerce sales, AOV & ad ROI |
| `GET` | `/reports/whatsapp` | `ReportController` | `whatsappReports` | `Auth, Perm:reports,view` | WhatsApp orders & staff sales |
| `GET` | `/reports/profitability` | `ReportController` | `profitabilityReports` | `Auth, Perm:reports,view` | Comprehensive P&L Financial Report |
