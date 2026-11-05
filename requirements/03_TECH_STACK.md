# GRM Inventory Management System — Technology Stack

## 1. Complete Technology Matrix

| Layer / Domain | Technology | Version / Standard | Purpose & Implementation |
| :--- | :--- | :--- | :--- |
| **Runtime Environment** | PHP | `8.1+` (ZTS / NTS compatible) | High-performance server-side application logic |
| **Web Server** | Apache HTTP Server | `2.4.58+` | Front controller routing, rewrite rules, static asset delivery |
| **Relational Database** | MariaDB / MySQL | `10.4.32+` / `8.0+` | 82 relational tables, InnoDB engine, utf8mb4 collation |
| **DB Administration** | phpMyAdmin | `5.2+` | Local and production database schema management |
| **Client UI Framework** | Bootstrap | `5.3.3` | Responsive layout, grid system, modals, forms, alert components |
| **Iconography** | Bootstrap Icons | `1.11.3` | Modern, clean vector iconography across all modules |
| **Typography** | Inter Font (Google Fonts) | `300, 400, 500, 600, 700` | Clean enterprise typography and readability |
| **Client Logic** | Vanilla JavaScript | `ECMAScript 2022+` | POS billing cart, QR/barcode scanning, AJAX cascading, modals |
| **Cloud Media Storage** | Cloudinary REST API | `v1_1` | High-res product images, catalog media, digital receipt uploads |
| **Mail / SMTP Transport** | Native Socket / PHPMailer | `TLS (Port 587) / SSL (Port 465)` | Automated database backup email delivery to Gmail / SMTP |
| **Printing & Receipts** | CSS3 `@media print` | `80mm Thermal / A4 Laser` | POS receipts, A4 GST tax invoices, carton labels, gate passes |
| **Local Development** | XAMPP | `v3.3.0` | Comprehensive local development server stack |
| **Production Target** | Hostinger / cPanel VPS | `Linux / Cloud Hosting` | Zero-dependency deployment target |

---

## 2. Backend Stack & PHP Standards

### 2.1 Core Architectural Libraries:
- **PDO (PHP Data Objects)**: Native database abstraction with parameter binding (`PDO::ATTR_EMULATE_PREPARES => false`).
- **SPL Dynamic Autoloader**: Registered in [`public/index.php`](file:///c:/xampp/htdocs/grm-inventory/public/index.php) to automatically load classes from `app/Services/`, `app/Models/`, `app/Helpers/`, `app/Controllers/`, and `app/Middleware/`.
- **BCrypt Password Hashing**: Utilizes `password_hash($pass, PASSWORD_DEFAULT)` and `password_verify()`.
- **Secure Native Sessions**: Configured with strict cookie parameters (`httponly`, `samesite=Lax`, `gc_maxlifetime`).

### 2.2 Helper Toolkit ([`app/Helpers/`](file:///c:/xampp/htdocs/grm-inventory/app/Helpers/)):
- `Database.php`: Singleton PDO connection manager with auto-reconnection on timeout and nested transaction depth tracker (`beginTransaction`, `commit`, `rollback`, `inTransaction`).
- `Session.php`: Session initialization, flash messaging (`Session::flash`, `Session::getFlash`), user context access (`Session::user`, `Session::userId`).
- `CSRF.php`: Cryptographic token generation (`CSRF::generateToken`, `CSRF::tokenField`) and timing-safe validation (`CSRF::validateToken`).
- `Validator.php`: Input validation engine supporting `required`, `email`, `numeric`, `min`, `max`, `unique`, and custom rules.
- `Response.php`: Standardized JSON responses (`Response::json`), view rendering (`Response::view`), redirects (`Response::redirect`, `Response::back`), and dynamic URL generation (`url()`, `asset()`, `uploadUrl()`).

---

## 3. Database & SQL Architecture

### 3.1 Storage Specifications:
- **Engine**: `InnoDB` across all 82 tables.
- **Character Set**: `utf8mb4` with `utf8mb4_unicode_ci` collation.
- **SQL Mode**: Optimized for strict group-by compliance (`REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY','')`).
- **Money & Precision**: `DECIMAL(12,2)` for all monetary values, unit costs, and tax calculations.
- **Quantities**: `DECIMAL(10,2)` or `INT UNSIGNED` for pieces, rolls, and meters.

### 3.2 Indexing & Performance:
- Clustered primary keys on all tables (`AUTO_INCREMENT`).
- Unique indexes on business identifiers: `invoice_number`, `expense_number`, `order_number`, `code`, `sku`.
- Composite indexes on high-frequency query paths: `(location_type, location_id)`, `(expense_date)`, `(created_at)`.

---

## 4. Frontend & UI Styling System

### 4.1 Design Palette & CSS Custom Properties:
The user interface features a sleek, modern, glassmorphic aesthetic defined via CSS custom variables:

```css
:root {
    --primary: #4f46e5;         /* Indigo Primary */
    --primary-hover: #4338ca;
    --primary-light: #eef2ff;
    --success: #10b981;         /* Emerald Success */
    --warning: #f59e0b;         /* Amber Warning */
    --danger: #ef4444;          /* Rose Danger */
    --info: #06b6d4;            /* Cyan Info */
    --dark: #0f172a;            /* Slate Dark */
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --bg-body: #f8fafc;
    --card-bg: #ffffff;
    --card-border: #e2e8f0;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
}
```

### 4.2 Interactive Components:
- **Stat Metric Cards**: Dynamic numeric counters with gradient accents and comparison trend pills.
- **Search & Auto-Suggest**: Client-side debounce searching on POS and customer directories.
- **Barcode & QR Scanner Listeners**: Global keystroke buffering (`keydown` handler) to catch USB scanner inputs with instant cart population.
- **Action Modals**: Bootstrap modals for quick actions (quick-add category, stock adjustment, return inspection, QR print).

---

## 5. Media, Backup & External Integrations

### 5.1 Cloudinary Media CDN:
- Product photos, fabric swatches, and digital receipt scans upload directly through [`CloudinaryService.php`](file:///c:/xampp/htdocs/grm-inventory/app/Services/CloudinaryService.php).
- Database stores canonical HTTPS URLs and public IDs.

### 5.2 Automated SMTP Backup Delivery:
- [`BackupService.php`](file:///c:/xampp/htdocs/grm-inventory/app/Services/BackupService.php) generates SQL dumps of all 82 tables.
- Automatically creates compressed ZIP archives with timestamp naming (`grm_backup_YYYY-MM-DD_HH-mm-ss.zip`).
- Direct socket TLS SMTP transport connects to Gmail / external mail server to email the ZIP backup directly to the administrative inbox.

---

## 6. Print & Hardware Compatibility

### 6.1 Thermal POS Receipt Printing (80mm / 58mm):
- Styled via dedicated `@media print` rules.
- Automatic font scaling, compact itemized list, GST summary, QR code rendering, and cutting guide.

### 6.2 A4 Enterprise Invoices & Reports:
- Full-width formatted tables with tax breakouts (CGST/SGST/IGST), customer billing/shipping details, authorized signature blocks, and terms & conditions.

### 6.3 Gate Passes & Carton Labels:
- Formatted for continuous sticky label printers (4x6 inch / 4x2 inch) with high-density QR codes and human-readable text.
