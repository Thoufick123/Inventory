# GRM Inventory Management System — Project Requirements Document (PRD)

## 1. Executive Summary

**GRM Inventory Management System** is an enterprise-grade, end-to-end apparel manufacturing, warehousing, multi-channel distribution, and retail management platform. It coordinates the entire garment supply chain:
- **Raw Material Procurement & Production Units** (Fabric rolls, cutting, stitching, labeling, and QC)
- **Centralized Warehouse Hub** (Production GRN, 3rd-party vendor purchases, rack/shelf/bin storage, picking, packing, dispatch, gate passes, and stock adjustments)
- **Multi-Channel Distribution** (Retail Shop / POS, WhatsApp Social Commerce, and eCommerce Website Orders)
- **Customer Lifecycle & RMA** (In-store and online customer returns, size/color exchanges, and catalog swaps)
- **Financial, Operational & Asset Control** (Petty cash registers, operational expenses, plant machinery assets, audit trails, and automated email database backups)

---

## 2. Business Goals & Core Objectives

1. **Single Immutable Stock Ledger**: Centralized `StockService` ensuring zero discrepancy across physical warehouses, retail stores, WhatsApp channels, and website stores.
2. **End-to-End Traceability**: Every garment is traceable from raw fabric roll seller, cutting batch, stitching unit, and QC inspection to retail billing or courier dispatch.
3. **Multi-Unit Manufacturing Control**: Production programs, cutting job cards, fabric wastage sales, stitching output, and quality control grades (A-Grade Pass, B-Grade Rework, Reject).
4. **Fast POS & Multi-Channel Billing**: Real-time barcode/QR scanner integration, customer credit, discounts, GST calculation, thermal receipt printing, and A4 tax invoices.
5. **Comprehensive Operational Accounting**: Channel-level and warehouse-level expense tracking, shift-based petty cash flow, and executive P&L financial analytics.

---

## 3. User Roles & Access Control Hierarchy

The system enforces strict Role-Based Access Control (RBAC) across 10 defined operational roles:

| Role ID | Role Name | Scope & Department | Primary Permissions |
| :---: | :--- | :--- | :--- |
| **1** | **System Administrator** | Organization-wide (`GENERAL`) | Unrestricted access across all modules, user administration, role permissions, audit logs, system backups, and settings. |
| **2** | **Production Manager** | Production Units (`PRODUCTION`) | Production programs, fabric roll inventory, cutting batches, stitching job cards, wastage sales, and machinery maintenance. |
| **3** | **Cutting Master** | Cutting Section (`PRODUCTION`) | Cutting batch creation, roll consumption, layer planning, bundle generation, and cutting wastage logging. |
| **4** | **Stitching Supervisor** | Stitching Units (`PRODUCTION`) | Stitching allocation, bundle issue/receive, daily piece production, and stitching wastage sales. |
| **5** | **QC Inspector** | Quality Control (`PRODUCTION`) | Finished garment inspection, A-Grade approval, B-Grade rework tagging, and rejection reporting. |
| **6** | **Warehouse Manager** | Central Warehouse (`WAREHOUSE`) | Inward GRN receipt, rack/bin placement, store allocation, picking, packing, dispatch, gate passes, vendor purchases, and stock transfers. |
| **7** | **Warehouse Staff** | Central Warehouse (`WAREHOUSE`) | Barcode/QR scanning, carton packing, damage inspection, physical stock verification, and inward verification. |
| **8** | **Shop Manager** | Retail Stores (`SHOP`) | Store stock verification, pricing, customer CRM, staff shift opening/closing, petty cash expenses, and sales reports. |
| **9** | **Cashier / POS Billing** | Retail POS (`SHOP`) | Barcode POS billing, invoice generation, cash/UPI payment collection, customer returns, and size/color exchanges. |
| **10** | **Sales Channel Staff** | Digital Channels (`CHANNEL`) | WhatsApp customer order processing, website order fulfillment, courier dispatch, RMA returns, and channel expenses. |

---

## 4. High-Level Functional Modules

```text
+----------------------------------------------------------------------------------------------------+
|                                    GRM INVENTORY MANAGEMENT SYSTEM                                 |
+----------------------------------------------------------------------------------------------------+
  │
  ├── 1. AUTHENTICATION & SECURITY
  │     ├── Role-Based Access Control (RBAC) & Dynamic Middleware Authorization
  │     ├── Session Security (HTTPOnly, SameSite, Lifetime Expiry & Auto-Regeneration)
  │     ├── CSRF Protection on all mutating state actions
  │     └── Sensitive Action Audit Logging (IP, User, Action, Diff, Timestamp)
  │
  ├── 2. PRODUCTION & MANUFACTURING SUITE (Unit 1 & Unit 2)
  │     ├── Fabric Sellers & Roll Inventory (Meters, GSM, Roll QR, Batch Tracking)
  │     ├── Fabric & Roll Damage Tracking with resolution workflows
  │     ├── Production Catalog & Styling Master
  │     ├── Production Programs & Approval Workflows
  │     ├── Cutting Section & Layer Job Cards
  │     ├── Cutting Wastage Selling & Cash Inflow
  │     ├── Stitching Section & Tailor Bundle Tracking
  │     ├── Stitching Wastage Selling & Receipts
  │     ├── Barcode & QR Label Batch Generation
  │     ├── Quality Control (QC) Inspection (Pass, Rework, Reject)
  │     ├── Post-QC Finished Garments Inventory
  │     └── Production Outward & Gate Pass Generation
  │
  ├── 3. CENTRAL WAREHOUSE & INVENTORY HUB
  │     ├── Production GRN Inward & QR Cross-Checking
  │     ├── 3rd-Party Vendor Purchases & Inward Billing
  │     ├── Direct Customer Purchases & Customer Orders
  │     ├── Multi-Location Stock (Racks, Shelves, Bins)
  │     ├── Warehouse Store Allocations (Picking -> Packing -> Dispatch -> Delivery)
  │     ├── Intra-Warehouse & Inter-Location Stock Transfers
  │     ├── Warehouse Defective / Damage Management
  │     ├── Stock Adjustments with reason codes & audit approval
  │     └── Warehouse Operational Expenses & Petty Cash
  │
  ├── 4. RETAIL SHOP & POS (Point of Sale)
  │     ├── Store Stock Inward Verification & Draft Reconciliation
  │     ├── Touch/Scanner POS Fast Billing & Quick Key Shortcuts
  │     ├── Customer 360 CRM & Purchase History Lookup
  │     ├── Real-time Discounts, GST Computation & Thermal Printing
  │     ├── In-Store Customer Returns & Same/Multi-Product Exchanges
  │     ├── Return to Warehouse (RTV) with Gate Pass & Carton Stickers
  │     ├── Daily Cash Register Shifts (Opening, Cash-In, Expenses, Bank Deposits, Closing)
  │     └── Store Defective Item Logging
  │
  ├── 5. E-COMMERCE & DIGITAL SALES CHANNELS
  │     ├── WhatsApp Social Commerce Order Management & Catalog Allocations
  │     ├── Website E-Commerce Orders & Fulfillment Queue
  │     ├── Channel Outward Dispatch, Courier AWB Manifests & Gate Passes
  │     ├── Online Customer Returns, Size Exchanges & RMA Portal
  │     └── Digital Marketing, API & Channel-Specific Operational Expenses
  │
  ├── 6. FIXED ASSET & MACHINERY MANAGEMENT
  │     ├── Plant Machinery & IT Equipment Register
  │     ├── Asset Maintenance, Servicing & Repair Logging
  │     ├── Inter-Unit Asset Transfers & Depreciation Tracking
  │     └── Physical Asset Audits & Condition Verification
  │
  ├── 7. EXECUTIVE REPORTING & ANALYTICS
  │     ├── Executive Multi-Unit Summary Dashboard
  │     ├── Production & Manufacturing Efficiency Reports
  │     ├── Warehouse Stock, Movement & Damage Reports
  │     ├── Multi-Channel Sales & Invoice Analysis
  │     ├── WhatsApp & Website Channel Performance Reports
  │     └── Profit & Loss (P&L) Financial Statements
  │
  └── 8. SYSTEM TOOLS & BACKUP
        ├── Automated Database Dump & ZIP Compression
        ├── SMTP Email Backup Delivery (Google App Password / Port 587)
        └── System Configuration & Timezone Control
```

---

## 5. Non-Functional Requirements

1. **Performance**: Server response times under **120ms** for database queries and sub-second page loads.
2. **Concurrency**: Row-level locking (`SELECT ... FOR UPDATE`) and ACID transactions during all stock movements and POS checkout operations.
3. **Security**:
   - Zero raw SQL concatenation (100% prepared statements).
   - XSS sanitization (`e()` helper on all view outputs).
   - CSRF tokens validated on every POST action.
   - Password encryption using PHP `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt).
4. **Portability**: Native PHP 8.1+ code with zero heavy framework bloat, deployable immediately on standard XAMPP or Hostinger cPanel.
5. **Data Resilience**: Automated daily / on-demand database backup compression and encrypted SMTP transport.
