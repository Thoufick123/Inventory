# GRM Inventory Management System

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.4%2B-003545?style=for-the-badge&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)

An enterprise-grade, end-to-end apparel manufacturing, central warehousing, multi-channel distribution, and retail management platform.

---

## 🚀 Quickstart & Setup Guide

### 1. Requirements
- **Local Server**: XAMPP (Apache + MySQL/MariaDB) or Linux LAMP/LEMP stack.
- **PHP Version**: `8.1.0` or higher (with `pdo_mysql`, `curl`, `mbstring`, `openssl` extensions enabled).
- **Database**: MySQL `8.0+` or MariaDB `10.4+`.

### 2. Installation & Configuration
1. Clone or copy the repository into your web server's root directory:
   ```bash
   # In XAMPP on Windows:
   C:\xampp\htdocs\grm-inventory\
   ```

2. Configure environment variables in [`.env`](file:///.env):
   ```env
   APP_ENV=local
   APP_NAME="GRM Inventory Management"
   APP_URL=http://localhost/grm-inventory
   APP_TIMEZONE=Asia/Kolkata
   APP_DEBUG=true

   DB_HOST=127.0.0.1
   DB_PORT=3307
   DB_DATABASE=grm_inventory
   DB_USERNAME=root
   DB_PASSWORD=

   # Cloudinary (Optional, for CDN media uploads)
   CLOUDINARY_CLOUD_NAME=your_cloud_name
   CLOUDINARY_API_KEY=your_api_key
   CLOUDINARY_API_SECRET=your_api_secret

   # Automated Backup SMTP Settings
   BACKUP_EMAIL=your_email@gmail.com
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=your_email@gmail.com
   SMTP_PASS=your_google_app_password
   SMTP_ENCRYPTION=tls
   ```

3. Database Initialization:
   - Create database `grm_inventory` in phpMyAdmin / MySQL CLI.
   - Import [`database/schema.sql`](file:///c:/xampp/htdocs/grm-inventory/database/schema.sql) and [`database/seed.sql`](file:///c:/xampp/htdocs/grm-inventory/database/seed.sql).
   - Seed any missing permission records (safe to rerun):
     ```bash
     php database/seed_permissions.php
     ```
   - Run incremental migrations to verify all tables:
     ```bash
     php database/migrations/create_expenses_and_categories_tables.php
     php database/migrations/create_asset_tables.php
     php database/migrations/create_cash_flow_tables.php
     php database/migrations/add_complementary_product_columns.php
     php database/migrations/add_inward_qr_codes_to_production_batch_items.php
     php database/migrations/fix_warehouse_damage_production_inward_schema.php
     php database/migrations/create_warehouse_catalog_attribute_tables.php
     php database/migrations/add_catalog_and_supplier_document_columns.php
     php database/migrations/create_warehouse_qr_codes_table.php
     php database/migrations/add_warehouse_stock_price_edit_permission.php
     ```
   - Hostinger Git deployment updates application files, but does not update the production database. After deploying a release that adds a migration, run that migration against the Hostinger database using Hostinger's PHP terminal/SSH before using the affected feature. Then assign any new permissions to the intended roles/staff in the application.

4. Launch Application:
   - Navigate to: **[http://localhost/grm-inventory/](http://localhost/grm-inventory/)**

---

## 🔑 Default Login Credentials

| Role | Username / Email | Password | Access Scope |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin` *(or `admin@grm.com`)* | `admin123` | Full access across all 13 modules, settings & user permissions |
| **Production Staff** | `abiprasath` *(or `abi1@gmail.com`)* | `123456` | Production planning, cutting, stitching, QC, and wastage sales |

---

## 📂 System Architecture & Modules

```text
GRM INVENTORY MANAGEMENT SYSTEM
├── 1. Authentication & RBAC (Role-based permission gating)
├── 2. Production & Manufacturing (Fabric rolls, cutting, stitching, QC, labels, outward)
├── 3. Central Warehouse Hub (GRN inward, rack/bin stock, allocations, picking, packing, dispatch)
├── 4. Retail Store & POS (Barcode touch POS billing, customer 360, thermal receipts)
├── 5. Customer Returns & Exchanges (In-store & warehouse RMA, size swaps, catalog exchanges)
├── 6. Digital Channels (WhatsApp social commerce & Website eCommerce order fulfillment)
├── 7. Financial & Petty Cash (Register shifts, daily float, expense vouchers, bank deposits)
├── 8. Fixed Assets & Machinery (Plant machines, maintenance logs, inter-unit transfers)
├── 9. Executive Reporting & Analytics (Production yields, multi-channel sales, P&L statements)
└── 10. System Tools (Automated encrypted database dumps with direct SMTP Gmail delivery)
```

---

## 📚 Complete Project Documentation

Detailed specifications and architectural guides are available in the [`requirements/`](file:///c:/xampp/htdocs/grm-inventory/requirements/) directory:

1. **[Project Requirements Document (PRD)](file:///c:/xampp/htdocs/grm-inventory/requirements/01_PROJECT_REQUIREMENT_DOCUMENT.md)**: Executive summary, user roles, business goals, and non-functional requirements.
2. **[Architecture & System Design](file:///c:/xampp/htdocs/grm-inventory/requirements/02_PROJECT_DESIGN.md)**: Architectural diagrams, folder structure, stock ledger engine, and lifecycle pipelines.
3. **[Technology Stack](file:///c:/xampp/htdocs/grm-inventory/requirements/03_TECH_STACK.md)**: Complete technology matrix, backend standards, database specs, and printing compatibility.
4. **[Database Schema & Models](file:///c:/xampp/htdocs/grm-inventory/requirements/04_DATABASE_SCHEMA_AND_MODELS.md)**: 82-table relational schema map, column constraints, entity relationships, and indexes.
5. **[Module Workflows & Specifications](file:///c:/xampp/htdocs/grm-inventory/requirements/05_MODULE_WORKFLOWS_AND_SPECIFICATIONS.md)**: Step-by-step sequence diagrams and functional rules for all 10 core domains.
6. **[UI Style Guide & Design System](file:///c:/xampp/htdocs/grm-inventory/requirements/06_UI_STYLE_GUIDE_AND_DESIGN_SYSTEM.md)**: Design tokens, typography hierarchy, status badge taxonomy, POS UI rules, and `@media print` stylesheets.
7. **[API & Routing Catalog](file:///c:/xampp/htdocs/grm-inventory/requirements/07_API_AND_ROUTING_CATALOG.md)**: Complete route reference across all HTTP verbs, controllers, actions, and permission middleware.
8. **[Returns & Exchanges Blueprint](file:///c:/xampp/htdocs/grm-inventory/requirements/member_2_whatsapp_and_website_blueprint.md)**: 2-member collaborative development specifications for RMA portals.

---

## 🛡️ Security & Integrity

- **Double-Entry Stock Ledger**: All inventory modifications write to `stock_balances` with atomic transactions and immutable audit logging to `stock_transactions`.
- **Zero Raw SQL Injection Risk**: 100% prepared PDO statements with bound parameters.
- **CSRF Token Verification**: Every mutating POST request requires a cryptographically validated session CSRF token.
- **XSS Escaping**: Native `e()` view helper sanitizes output across all templates.
- **Audit Logging**: Sensitive events (price modifications, permission grants, expense approvals) automatically log to `audit_logs`.
