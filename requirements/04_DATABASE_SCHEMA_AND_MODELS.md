# GRM Inventory Management System — Database Schema & Data Models

## 1. Database Architecture Overview

The database is built on **MariaDB 10.4+ / MySQL 8.0+** using the `InnoDB` storage engine and `utf8mb4_unicode_ci` collation. It contains **82 normalized relational tables** organized into 10 cohesive business domains:

```text
                                  DATABASE DOMAIN MAP (82 TABLES)
+----------------------------------------------------------------------------------------------------+
| 1. AUTH & SECURITY (5 Tables)                                                                      |
|    users, roles, permissions, role_permissions, user_permissions                                   |
+----------------------------------------------------------------------------------------------------+
| 2. ORG & PHYSICAL SITES (5 Tables)                                                                 |
|    production_units, warehouses, shops, locations, sales_channels                                  |
+----------------------------------------------------------------------------------------------------+
| 3. MASTER DATA & PRODUCT CATALOG (12 Tables)                                                       |
|    categories, subcategories, sub_subcategories, brands, products, product_variants, sizes,        |
|    colors, units, sellers, seller_rolls, customers                                                 |
+----------------------------------------------------------------------------------------------------+
| 4. PRODUCTION & MANUFACTURING (14 Tables)                                                          |
|    production_programs, production_program_items, production_program_rolls, cutting_batches,        |
|    cutting_batch_rolls, cutting_items, stitching_batches, stitching_items, label_batches,          |
|    label_items, qc_batches, qc_items, production_batches, production_batch_items,                  |
|    production_outwards, roll_damages, wastage_sales, stitching_wastage_sales                       |
+----------------------------------------------------------------------------------------------------+
| 5. CENTRAL WAREHOUSE & PROCUREMENT (16 Tables)                                                     |
|    warehouse_categories, warehouse_subcategories, warehouse_sub_subcategories,                     |
|    warehouse_suppliers, purchase_orders, purchase_items, purchase_inwards, purchase_inward_items,   |
|    warehouse_allocations, allocation_items, allocation_outwards, warehouse_transfers,              |
|    transfer_items, warehouse_damages, stock_adjustments, deliveries                                |
+----------------------------------------------------------------------------------------------------+
| 6. CENTRAL STOCK LEDGER (2 Tables)                                                                 |
|    stock_balances, stock_transactions                                                              |
+----------------------------------------------------------------------------------------------------+
| 7. SALES & CHANNEL COMMERCE (4 Tables)                                                             |
|    sales, sale_items, payments, channel_damages, shop_damages                                      |
+----------------------------------------------------------------------------------------------------+
| 8. RETURNS & EXCHANGES (6 Tables)                                                                  |
|    returns, return_items, exchanges, exchange_items, warehouse_returns, warehouse_return_items     |
+----------------------------------------------------------------------------------------------------+
| 9. FINANCIAL & EXPENSES (7 Tables)                                                                 |
|    expenses, expense_categories, production_expenses, cash_expenses, cash_registers,               |
|    cash_transactions, bank_deposits                                                                |
+----------------------------------------------------------------------------------------------------+
| 10. FIXED ASSETS & SYSTEM (11 Tables)                                                              |
|     assets, asset_categories, asset_transfers, asset_maintenance_logs, asset_audits,               |
|     asset_audit_items, audit_logs, notifications, backups, settings                                |
+----------------------------------------------------------------------------------------------------+
```

---

## 2. Core Relational Schemas & Entity Definitions

### 2.1 Central Inventory Ledger (`stock_balances` & `stock_transactions`)

#### Table: `stock_balances` (Snapshot of Real-Time Stock)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | Unique balance identifier |
| `variant_id` | `INT UNSIGNED` | `NOT NULL, FK -> product_variants.id` | Specific SKU variant |
| `location_type` | `ENUM` | `NOT NULL, ('PRODUCTION','WAREHOUSE','SHOP','CHANNEL')` | Physical or logical site |
| `location_id` | `INT UNSIGNED` | `NOT NULL` | ID of unit, warehouse, shop, or channel |
| `location_code` | `VARCHAR(50)` | `NULL` | Fast code alias |
| `stock_pool` | `ENUM` | `NOT NULL DEFAULT 'AVAILABLE'` | `'AVAILABLE'`, `'RESERVED'`, `'DAMAGED'`, `'IN_PRODUCTION'` |
| `quantity` | `DECIMAL(10,2)` | `NOT NULL DEFAULT 0.00` | Current on-hand quantity |
| `rack_location` | `VARCHAR(100)` | `NULL` | Bin / Shelf coordinates |
| `updated_at` | `DATETIME` | `NOT NULL` | Timestamp of last stock balance change |

#### Table: `stock_transactions` (Immutable Audit Log)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | Unique transaction ID |
| `transaction_number` | `VARCHAR(60)` | `NOT NULL, UNIQUE` | Formatted identifier (`TXN-YYYYMMDD-XXXX`) |
| `variant_id` | `INT UNSIGNED` | `NOT NULL, FK -> product_variants.id` | SKU reference |
| `transaction_type` | `VARCHAR(50)` | `NOT NULL` | `'INWARD'`, `'OUTWARD'`, `'RESERVATION'`, `'TRANSFER'`, `'SALE'`, `'RETURN'`, `'ADJUSTMENT'` |
| `source_type` | `VARCHAR(50)` | `NULL` | Source location type |
| `source_id` | `INT UNSIGNED` | `NULL` | Source location ID |
| `dest_type` | `VARCHAR(50)` | `NULL` | Destination location type |
| `dest_id` | `INT UNSIGNED` | `NULL` | Destination location ID |
| `quantity` | `DECIMAL(10,2)` | `NOT NULL` | Transacted quantity |
| `previous_balance` | `DECIMAL(10,2)` | `NOT NULL` | Balance before transaction |
| `new_balance` | `DECIMAL(10,2)` | `NOT NULL` | Balance after transaction |
| `reference_type` | `VARCHAR(50)` | `NOT NULL` | `'SALE'`, `'PO'`, `'ALLOCATION'`, `'PRODUCTION_BATCH'`, `'RETURN'` |
| `reference_id` | `INT UNSIGNED` | `NOT NULL` | ID in reference table |
| `created_by` | `INT UNSIGNED` | `NOT NULL, FK -> users.id` | User who authorized movement |
| `created_at` | `DATETIME` | `NOT NULL` | Timestamp |

---

### 2.2 Operational Expenses & Petty Cash (`expenses` & `expense_categories`)

#### Table: `expenses` (Enterprise Operational & Channel Expense Registry)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | Unique expense record ID |
| `expense_number` | `VARCHAR(50)` | `NOT NULL, UNIQUE` | E.g. `EXP-202609-0001` |
| `location_type` | `ENUM` | `NOT NULL` | `'PRODUCTION'`, `'WAREHOUSE'`, `'SHOP'`, `'CHANNEL'`, `'OFFICE'`, `'WHATSAPP'`, `'WEBSITE'` |
| `location_id` | `INT UNSIGNED` | `NULL` | Specific Unit / Warehouse / Shop ID |
| `category` | `VARCHAR(100)` | `NOT NULL` | E.g. `'Freight'`, `'Packaging'`, `'Electricity'` |
| `item_name` | `VARCHAR(255)` | `NOT NULL` | Expense item / line title |
| `quantity` | `DECIMAL(10,2)` | `NOT NULL DEFAULT 1.00` | Quantity purchased |
| `unit_cost` | `DECIMAL(12,2)` | `NOT NULL DEFAULT 0.00` | Cost per unit |
| `total_cost` | `DECIMAL(12,2)` | `NOT NULL` | Total expense amount |
| `paid_amount` | `DECIMAL(12,2)` | `NOT NULL` | Amount settled |
| `payment_status` | `ENUM` | `NOT NULL` | `'PAID'`, `'PENDING'`, `'PARTIAL'`, `'APPROVED'`, `'CANCELLED'` |
| `payment_method` | `ENUM` | `NOT NULL` | `'CASH'`, `'UPI'`, `'BANK_TRANSFER'`, `'CARD'`, `'PETTY_CASH'`, `'CHEQUE'`, `'CREDIT'` |
| `paid_to` | `VARCHAR(200)` | `NOT NULL` | Vendor / Payee name |
| `expense_date` | `DATE` | `NOT NULL` | Effective date of expense |
| `receipt_url` | `VARCHAR(500)` | `NULL` | Uploaded receipt image URL |
| `created_by` | `INT UNSIGNED` | `FK -> users.id` | Staff who submitted expense |
| `approved_by` | `INT UNSIGNED` | `FK -> users.id` | Manager who approved expense |

---

### 2.3 Returns & Customer Exchanges (`returns` & `exchanges`)

#### Table: `returns` (Customer & Inward Returns)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | Return transaction ID |
| `return_number` | `VARCHAR(50)` | `NOT NULL, UNIQUE` | E.g. `RET-202609-0001` |
| `source_type` | `ENUM` | `NOT NULL DEFAULT 'SHOP'` | `'SHOP'`, `'WAREHOUSE_PURCHASE'`, `'WHATSAPP'`, `'WEBSITE'` |
| `sale_id` | `INT UNSIGNED` | `NULL, FK -> sales.id` | Original retail / channel sale |
| `purchase_order_id` | `INT UNSIGNED` | `NULL, FK -> purchase_orders.id` | Original direct purchase |
| `customer_id` | `INT UNSIGNED` | `NULL, FK -> customers.id` | Customer record |
| `total_amount` | `DECIMAL(12,2)` | `NOT NULL` | Refund / credit value |
| `refund_status` | `ENUM` | `NOT NULL` | `'COMPLETED'`, `'PENDING'`, `'REJECTED'` |
| `refund_method` | `VARCHAR(50)` | `NOT NULL` | `'CASH'`, `'UPI'`, `'EXCHANGE'`, `'CREDIT_NOTE'` |
| `created_at` | `DATETIME` | `NOT NULL` | Date of return |

#### Table: `exchanges` (Replacement & Size/Color Swaps)
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | Exchange record ID |
| `exchange_number` | `VARCHAR(50)` | `NOT NULL, UNIQUE` | E.g. `EXC-202609-0001` |
| `return_id` | `INT UNSIGNED` | `NOT NULL, FK -> returns.id` | Linked return transaction |
| `sale_id` | `INT UNSIGNED` | `NULL, FK -> sales.id` | Original sale |
| `purchase_order_id` | `INT UNSIGNED` | `NULL, FK -> purchase_orders.id` | Direct purchase |
| `price_difference` | `DECIMAL(12,2)` | `NOT NULL DEFAULT 0.00` | `+ve` (Customer Pays) / `-ve` (Store Refunds) |
| `settlement_mode` | `VARCHAR(50)` | `NOT NULL DEFAULT 'CASH'` | Cash / UPI / Card settlement |
| `created_at` | `DATETIME` | `NOT NULL` | Date of exchange |
