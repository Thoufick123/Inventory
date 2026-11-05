# GRM Inventory Management System — UI Style Guide & Design System

## 1. Design Philosophy

The user interface of GRM Inventory is designed to deliver a **premium, responsive, and ultra-fast enterprise experience**:
- **Clean Aesthetic**: Modern slate backgrounds with vibrant indigo, emerald, and amber accent colors.
- **Glassmorphism & Depth**: Subtle border radii (`12px` - `16px`), soft drop shadows, and glass card effects.
- **Micro-Animations**: Smooth hover transitions, interactive modal slide-ins, and button state elevations.
- **High Information Density**: Clean tabular data with badge status indicators, monospace SKU pills, and real-time metric cards.
- **Hardware Integration**: High-contrast, glare-resistant layouts optimized for warehouse tablet usage and fast touch POS screens.

---

## 2. Color Palette & Design Tokens

```css
:root {
    /* Brand & Accent Colors */
    --primary: #4f46e5;             /* Indigo 600 - Primary Brand */
    --primary-hover: #4338ca;       /* Indigo 700 - Hover State */
    --primary-light: #eef2ff;       /* Indigo 50 - Subtle Background Tint */
    --primary-glow: rgba(79, 70, 229, 0.25);

    /* Functional Status Colors */
    --success: #10b981;             /* Emerald 500 - Approved / Paid / Active */
    --success-light: #ecfdf5;
    --warning: #f59e0b;             /* Amber 500 - Pending / Under Inspection */
    --warning-light: #fffbeb;
    --danger: #ef4444;              /* Rose 500 - Rejected / Damaged / Overdue */
    --danger-light: #fef2f2;
    --info: #06b6d4;                /* Cyan 500 - Allocation / Processing */
    --info-light: #ecfeff;

    /* Neutrals & Typography */
    --slate-900: #0f172a;           /* Sidebar & Deep Text */
    --slate-800: #1e293b;           /* Headings & Dark Accents */
    --slate-600: #475569;           /* Secondary Body Text */
    --slate-400: #94a3b8;           /* Muted Icons & Placeholders */
    --slate-200: #e2e8f0;           /* Borders & Dividers */
    --slate-100: #f1f5f9;           /* Secondary Card Backgrounds */
    --slate-50:  #f8fafc;           /* App Background */

    /* Typography */
    --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-mono: 'JetBrains Mono', 'Fira Code', 'SF Mono', Consolas, monospace;

    /* Shadows & Elevation */
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.04);

    /* Border Radii */
    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 14px;
    --radius-xl: 20px;
}
```

---

## 3. Typography Scale & Hierarchy

| Element | Font Weight | Size (rem / px) | Line Height | Tracking / Transform | Example / Use Case |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Page Title** (`h1`) | Bold (`700`) | `1.75rem / 28px` | `1.25` | `-0.02em` | Module Header (`Production Dashboard`) |
| **Section Title** (`h2`) | SemiBold (`600`) | `1.35rem / 22px` | `1.3` | `-0.01em` | Card Group (`Recent Cutting Batches`) |
| **Card Title** (`h3`) | SemiBold (`600`) | `1.10rem / 18px` | `1.4` | `normal` | Sub-card header (`Stock Inward Details`) |
| **Body Primary** | Regular (`400`) | `0.9375rem / 15px` | `1.5` | `normal` | Table rows, general descriptions |
| **Small / Meta** | Medium (`500`) | `0.8125rem / 13px` | `1.4` | `normal` | Timestamps, user avatars, labels |
| **SKU / Code** (`code`) | Medium (`500`) | `0.875rem / 14px` | `1.2` | Monospace | `SKU-DRS-BLK-M`, `PO-2026-0012` |

---

## 4. Reusable UI Components

### 4.1 Interactive KPI Stat Cards
```html
<div class="card stat-card shadow-sm border-0 rounded-4">
    <div class="card-body p-4 d-flex align-items-center justify-content-between">
        <div>
            <span class="text-secondary fw-semibold text-uppercase fs-xs">Total Available Stock</span>
            <h2 class="fw-bold text-slate-800 my-1">42,850 Pcs</h2>
            <span class="badge bg-success-subtle text-success rounded-pill fw-semibold fs-xs">
                <i class="bi bi-arrow-up-right me-1"></i>+4.8% this week
            </span>
        </div>
        <div class="stat-icon-box bg-primary-subtle text-primary rounded-4 p-3">
            <i class="bi bi-box-seam-fill fs-2"></i>
        </div>
    </div>
</div>
```

### 4.2 Status Badge Taxonomy
- **Approved / Active / Paid**: `<span class="badge bg-success text-white">ACTIVE</span>`
- **Pending / In Progress / Partial**: `<span class="badge bg-warning text-dark">PENDING</span>`
- **Rejected / Damaged / Cancelled**: `<span class="badge bg-danger text-white">DAMAGED</span>`
- **Reserved / Dispatched**: `<span class="badge bg-info text-white">DISPATCHED</span>`

---

## 5. POS (Point of Sale) & Scanner Interface Guidelines

1. **High Contrast Display**: Bold product cards with large pricing typography to avoid cashier eye strain.
2. **Keyboard Accelerators**:
   - `F1`: Focus search box / barcode scan.
   - `F2`: Instant customer lookup modal.
   - `F4`: Apply discount or coupon.
   - `F8`: Complete checkout & trigger thermal print.
   - `ESC`: Clear cart or dismiss modal.
3. **Cart Summary Drawer**: Fixed right-hand checkout sidebar with persistent subtotal, CGST, SGST, round-off, and one-click quick cash buttons (₹500, ₹1,000, ₹2,000).

---

## 6. Print Stylesheet Specifications (`@media print`)

### 6.1 Thermal Receipt Printer (80mm POS):
```css
@media print {
    @page {
        margin: 0;
        size: 80mm auto;
    }
    body {
        margin: 0;
        padding: 6px;
        font-family: 'Inter', monospace;
        font-size: 12px;
        color: #000;
        background: #fff;
    }
    .no-print, .btn, .navbar, .sidebar { display: none !important; }
    .receipt-table { width: 100%; border-collapse: collapse; }
    .receipt-table th, .receipt-table td { padding: 4px 0; }
    .receipt-divider { border-top: 1px dashed #000; margin: 6px 0; }
}
```

### 6.2 A4 GST Tax Invoices & Gate Passes:
- Standard `A4 portrait` with margin `10mm`.
- Professional two-column header with company details, logo, GSTIN, and barcode.
- Two-column bottom footer with bank NEFT/RTGS details, terms & conditions, and authorized signatory signature box.
