<?php
/**
 * Permissions Matrix Seeder
 * Seeds all required module and submodule granular permissions into the database.
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/app/Helpers/Database.php';

$seedSqlPath = BASE_PATH . '/database/seed.sql';
$seedSql = file_get_contents($seedSqlPath);
if ($seedSql === false) {
    throw new RuntimeException("Unable to read the permission seed source: {$seedSqlPath}");
}

preg_match_all("/INSERT INTO `permissions`[^;]*?VALUES\\s*(.*?);/is", $seedSql, $permissionBlocks);
if (empty($permissionBlocks[1])) {
    throw new RuntimeException('No permission records were found in database/seed.sql.');
}

$definitions = [];
foreach ($permissionBlocks[1] as $permissionBlock) {
    preg_match_all(
        "/\\(\\s*'\\d+'\\s*,\\s*'((?:\\\\\\\\.|[^'])*)'\\s*,\\s*'((?:\\\\\\\\.|[^'])*)'\\s*,\\s*'((?:\\\\\\\\.|[^'])*)'\\s*,/s",
        $permissionBlock,
        $permissionRows,
        PREG_SET_ORDER
    );
    foreach ($permissionRows as $permissionRow) {
        $definitions[] = [
            'module' => stripslashes($permissionRow[1]),
            'action' => stripslashes($permissionRow[2]),
            'desc' => stripslashes($permissionRow[3]),
        ];
    }
}

$definitions = array_merge($definitions, [
    ['module' => 'inventory', 'action' => 'delete', 'desc' => 'Delete an available Production inventory QR piece'],
    ['module' => 'production_billing', 'action' => 'view', 'desc' => 'View Production Bills'],
    ['module' => 'production_billing', 'action' => 'create', 'desc' => 'Create Production Bills'],
    ['module' => 'production_billing', 'action' => 'edit', 'desc' => 'Edit Production Bills'],
    ['module' => 'production_billing', 'action' => 'delete', 'desc' => 'Delete Production Bills'],
    ['module' => 'production_billing', 'action' => 'print', 'desc' => 'Print Production Bills'],
    ['module' => 'production_billing_returns', 'action' => 'view', 'desc' => 'View Production Billing Returns & Exchanges'],
    ['module' => 'production_billing_returns', 'action' => 'create', 'desc' => 'Create Production Billing Return or Exchange'],
    ['module' => 'production_billing_returns', 'action' => 'process', 'desc' => 'Process Production Billing Return or Exchange'],
    ['module' => 'production_billing_returns', 'action' => 'print', 'desc' => 'Print Production Billing Return or Exchange'],

    // WhatsApp Channel Hub
    ['module' => 'whatsapp_orders', 'action' => 'view', 'desc' => 'View WhatsApp B2B Orders and history'],
    ['module' => 'whatsapp_orders', 'action' => 'create', 'desc' => 'Create new WhatsApp B2B order'],
    ['module' => 'whatsapp_orders', 'action' => 'process', 'desc' => 'Process and dispatch WhatsApp order'],

    ['module' => 'whatsapp_inward', 'action' => 'view', 'desc' => 'View WhatsApp stock inward and receipts'],
    ['module' => 'whatsapp_inward', 'action' => 'create', 'desc' => 'Create WhatsApp inward receipt'],
    ['module' => 'whatsapp_inward', 'action' => 'receive', 'desc' => 'Receive and confirm inward stock'],

    ['module' => 'whatsapp_inventory', 'action' => 'view', 'desc' => 'View WhatsApp channel inventory'],
    ['module' => 'whatsapp_inventory', 'action' => 'edit', 'desc' => 'Edit WhatsApp inventory details & stock prices'],
    ['module' => 'whatsapp_inventory', 'action' => 'adjust', 'desc' => 'Adjust WhatsApp stock levels'],

    ['module' => 'whatsapp_damage', 'action' => 'view', 'desc' => 'View WhatsApp defective and damaged items'],
    ['module' => 'whatsapp_damage', 'action' => 'create', 'desc' => 'Log new WhatsApp damage entry'],
    ['module' => 'whatsapp_damage', 'action' => 'outward', 'desc' => 'Dispatch damaged goods outward'],
    ['module' => 'whatsapp_damage', 'action' => 'print', 'desc' => 'Print damage gate pass and carton labels'],

    ['module' => 'whatsapp_returns', 'action' => 'view', 'desc' => 'View WhatsApp returns and exchanges'],
    ['module' => 'whatsapp_returns', 'action' => 'create', 'desc' => 'Create return request for WhatsApp'],
    ['module' => 'whatsapp_returns', 'action' => 'process', 'desc' => 'Process return and restock/refund'],
    ['module' => 'whatsapp_returns', 'action' => 'print', 'desc' => 'Print WhatsApp return credit notes'],

    ['module' => 'whatsapp_expenses', 'action' => 'view', 'desc' => 'View WhatsApp channel expenses'],
    ['module' => 'whatsapp_expenses', 'action' => 'create', 'desc' => 'Record WhatsApp operational expense'],
    ['module' => 'whatsapp_expenses', 'action' => 'delete', 'desc' => 'Delete WhatsApp expense record'],
    ['module' => 'whatsapp_expenses', 'action' => 'voucher', 'desc' => 'Generate and print expense voucher'],
    ['module' => 'whatsapp_expenses', 'action' => 'print', 'desc' => 'Print WhatsApp expense summary'],

    // Website eCommerce Hub
    ['module' => 'website_orders', 'action' => 'view', 'desc' => 'View eCommerce website orders'],
    ['module' => 'website_orders', 'action' => 'create', 'desc' => 'Create eCommerce order manually'],
    ['module' => 'website_orders', 'action' => 'process', 'desc' => 'Process website order and packing'],

    ['module' => 'website_inward', 'action' => 'view', 'desc' => 'View website stock inward and receipts'],
    ['module' => 'website_inward', 'action' => 'receive', 'desc' => 'Receive and confirm website inward stock'],

    ['module' => 'website_inventory', 'action' => 'view', 'desc' => 'View website dedicated inventory'],
    ['module' => 'website_inventory', 'action' => 'adjust', 'desc' => 'Adjust website stock levels'],

    ['module' => 'website_damage', 'action' => 'view', 'desc' => 'View website damaged goods'],
    ['module' => 'website_damage', 'action' => 'create', 'desc' => 'Log website damage entry'],
    ['module' => 'website_damage', 'action' => 'outward', 'desc' => 'Dispatch website damaged goods outward'],
    ['module' => 'website_damage', 'action' => 'print', 'desc' => 'Print website damage gate pass and labels'],

    ['module' => 'website_returns', 'action' => 'view', 'desc' => 'View website returns and RTO parcels'],
    ['module' => 'website_returns', 'action' => 'create', 'desc' => 'Initiate website return / exchange'],
    ['module' => 'website_returns', 'action' => 'process', 'desc' => 'Process website return and restocking'],
    ['module' => 'website_returns', 'action' => 'print', 'desc' => 'Print website return invoices and slips'],

    ['module' => 'website_expenses', 'action' => 'view', 'desc' => 'View website eCommerce expenses'],
    ['module' => 'website_expenses', 'action' => 'create', 'desc' => 'Record website hosting/ad/courier expense'],
    ['module' => 'website_expenses', 'action' => 'delete', 'desc' => 'Delete website expense record'],
    ['module' => 'website_expenses', 'action' => 'voucher', 'desc' => 'Print website expense voucher'],
    ['module' => 'website_expenses', 'action' => 'print', 'desc' => 'Print website expense report'],

    // Warehouse Operational Expenses
    ['module' => 'warehouse_expenses', 'action' => 'view', 'desc' => 'View warehouse operational expenses'],
    ['module' => 'warehouse_expenses', 'action' => 'create', 'desc' => 'Record warehouse freight/utility expense'],
    ['module' => 'warehouse_expenses', 'action' => 'delete', 'desc' => 'Delete warehouse expense record'],
    ['module' => 'warehouse_expenses', 'action' => 'voucher', 'desc' => 'Print warehouse expense payment voucher'],
    ['module' => 'warehouse_expenses', 'action' => 'print', 'desc' => 'Print warehouse expense statement'],
    ['module' => 'warehouse_expenses', 'action' => 'export', 'desc' => 'Export warehouse expense records'],

    // Shop Damage Management
    ['module' => 'shop_damage', 'action' => 'view', 'desc' => 'View shop floor defect and damaged items'],
    ['module' => 'shop_damage', 'action' => 'create', 'desc' => 'Record shop damaged product'],
    ['module' => 'shop_damage', 'action' => 'edit', 'desc' => 'Edit shop damage record'],
    ['module' => 'shop_damage', 'action' => 'delete', 'desc' => 'Delete shop damage record'],
    ['module' => 'shop_damage', 'action' => 'process', 'desc' => 'Process salvage or write-off'],

    // Shop Stock & Pricing Management
    ['module' => 'shop_stock', 'action' => 'edit', 'desc' => 'Edit Shop Product Selling Price & MRP'],
    ['module' => 'shop_stock', 'action' => 'edit_price', 'desc' => 'Quick Edit Retail Selling Price and MRP'],

    // Granular Reports & Business Intelligence
    ['module' => 'reports_overview', 'action' => 'view', 'desc' => 'View Executive Overview and P&L Report'],
    ['module' => 'reports_overview', 'action' => 'export', 'desc' => 'Export Executive Overview data to Excel/CSV'],
    ['module' => 'reports_overview', 'action' => 'print', 'desc' => 'Print Executive Overview statement'],

    ['module' => 'reports_profitability', 'action' => 'view', 'desc' => 'View Profitability and Margin Report'],
    ['module' => 'reports_profitability', 'action' => 'export', 'desc' => 'Export Profitability report'],
    ['module' => 'reports_profitability', 'action' => 'print', 'desc' => 'Print Profitability statement'],

    ['module' => 'reports_sales', 'action' => 'view', 'desc' => 'View Multi-Channel Sales Performance Report'],
    ['module' => 'reports_sales', 'action' => 'export', 'desc' => 'Export Multi-Channel Sales report'],
    ['module' => 'reports_sales', 'action' => 'print', 'desc' => 'Print Multi-Channel Sales report'],

    ['module' => 'reports_website', 'action' => 'view', 'desc' => 'View Website eCommerce Analytics Report'],
    ['module' => 'reports_website', 'action' => 'export', 'desc' => 'Export Website eCommerce analytics'],
    ['module' => 'reports_website', 'action' => 'print', 'desc' => 'Print Website report'],

    ['module' => 'reports_whatsapp', 'action' => 'view', 'desc' => 'View WhatsApp B2B Sales Report'],
    ['module' => 'reports_whatsapp', 'action' => 'export', 'desc' => 'Export WhatsApp B2B sales data'],
    ['module' => 'reports_whatsapp', 'action' => 'print', 'desc' => 'Print WhatsApp B2B report'],

    ['module' => 'reports_production', 'action' => 'view', 'desc' => 'View Production Floor & Output Report'],
    ['module' => 'reports_production', 'action' => 'export', 'desc' => 'Export Production floor report'],
    ['module' => 'reports_production', 'action' => 'print', 'desc' => 'Print Production summary report'],

    ['module' => 'reports_warehouse', 'action' => 'view', 'desc' => 'View Warehouse Inward & Dispatch Report'],
    ['module' => 'reports_warehouse', 'action' => 'export', 'desc' => 'Export Warehouse storage and dispatch report'],
    ['module' => 'reports_warehouse', 'action' => 'print', 'desc' => 'Print Warehouse report'],

    ['module' => 'reports_purchase', 'action' => 'view', 'desc' => 'View Direct Purchases & Supplier Report'],
    ['module' => 'reports_purchase', 'action' => 'export', 'desc' => 'Export Purchases and supplier procurement report'],
    ['module' => 'reports_purchase', 'action' => 'print', 'desc' => 'Print Purchase procurement report'],

    ['module' => 'reports_shop', 'action' => 'view', 'desc' => 'View Retail Shop & POS Performance Report'],
    ['module' => 'reports_shop', 'action' => 'export', 'desc' => 'Export Retail Shop sales report'],
    ['module' => 'reports_shop', 'action' => 'print', 'desc' => 'Print Retail Shop performance report'],

    ['module' => 'reports_inventory', 'action' => 'view', 'desc' => 'View Enterprise Stock Valuation Report'],
    ['module' => 'reports_inventory', 'action' => 'export', 'desc' => 'Export Stock Valuation & Inventory report'],
    ['module' => 'reports_inventory', 'action' => 'print', 'desc' => 'Print Inventory summary report'],

    // Warehouse Direct Purchases Returns & Exchanges
    ['module' => 'purchases_returns', 'action' => 'view', 'desc' => 'View Purchases Returns & Exchanges'],
    ['module' => 'purchases_returns', 'action' => 'create', 'desc' => 'Create Purchase Return / Exchange request'],
    ['module' => 'purchases_returns', 'action' => 'process', 'desc' => 'Process and Restock/Settle Return'],
    ['module' => 'purchases_returns', 'action' => 'print', 'desc' => 'Print Purchase Return Invoice / Voucher'],
    ['module' => 'purchases_returns', 'action' => 'delete', 'desc' => 'Delete Purchase Return record'],

    // WhatsApp Dashboard
    ['module' => 'whatsapp_dashboard', 'action' => 'view', 'desc' => 'View WhatsApp Channel Dashboard'],

    // Website Dashboard
    ['module' => 'website_dashboard', 'action' => 'view', 'desc' => 'View Website eCommerce Dashboard'],

    ['module' => 'reports_online', 'action' => 'view', 'desc' => 'View Combined Online Channel Reports'],
    ['module' => 'reports_online', 'action' => 'export', 'desc' => 'Export Combined Online report'],
    ['module' => 'reports_online', 'action' => 'print', 'desc' => 'Print Combined Online report'],

    // Production Labels
    ['module' => 'labels', 'action' => 'delete', 'desc' => 'Delete Garment Label Batch'],

    // Production Inward / GRN
    ['module' => 'production_inward', 'action' => 'view', 'desc' => 'View production-unit inward receipts and stock'],
    ['module' => 'production_inward', 'action' => 'receive', 'desc' => 'Receive products into a production unit'],

    // Production Rework from Warehouse
    ['module' => 'warehouse_rework', 'action' => 'view', 'desc' => 'View Warehouse Rework Requests and History'],
    ['module' => 'warehouse_rework', 'action' => 'create', 'desc' => 'Receive and Log damaged goods from warehouse for rework'],
    ['module' => 'warehouse_rework', 'action' => 'edit', 'desc' => 'Update rework status, tailor assignment, and repair outcomes'],
    ['module' => 'warehouse_rework', 'action' => 'delete', 'desc' => 'Delete rework entries'],
    ['module' => 'warehouse_rework', 'action' => 'dispatch', 'desc' => 'Re-dispatch repaired goods back to warehouse'],

    // Permission actions currently required by route middleware.
    ['module' => 'wh_sellers', 'action' => 'delete', 'desc' => 'Delete warehouse supplier'],
    ['module' => 'whatsapp_customers', 'action' => 'view', 'desc' => 'View WhatsApp channel customers'],
    ['module' => 'whatsapp_return_to_wh', 'action' => 'view', 'desc' => 'View WhatsApp returns to warehouse'],
    ['module' => 'whatsapp_return_to_wh', 'action' => 'create', 'desc' => 'Create WhatsApp return to warehouse'],
    ['module' => 'whatsapp_return_to_wh', 'action' => 'gatepass', 'desc' => 'Print WhatsApp return-to-warehouse gate pass'],
    ['module' => 'whatsapp_return_to_wh', 'action' => 'print', 'desc' => 'Print WhatsApp return-to-warehouse labels'],
    ['module' => 'website_customers', 'action' => 'view', 'desc' => 'View Website channel customers'],
    ['module' => 'website_return_to_wh', 'action' => 'view', 'desc' => 'View Website returns to warehouse'],
    ['module' => 'website_return_to_wh', 'action' => 'create', 'desc' => 'Create Website return to warehouse'],
    ['module' => 'website_return_to_wh', 'action' => 'gatepass', 'desc' => 'Print Website return-to-warehouse gate pass'],
    ['module' => 'website_return_to_wh', 'action' => 'print', 'desc' => 'Print Website return-to-warehouse labels'],
    ['module' => 'warehouse', 'action' => 'create', 'desc' => 'Create warehouse transfer'],
    ['module' => 'warehouse', 'action' => 'edit', 'desc' => 'Update warehouse details'],
    ['module' => 'warehouse', 'action' => 'delete', 'desc' => 'Delete warehouse'],
    ['module' => 'warehouse', 'action' => 'approve', 'desc' => 'Approve warehouse transfer'],
    ['module' => 'warehouse', 'action' => 'receive', 'desc' => 'Receive warehouse transfer'],
    ['module' => 'stock', 'action' => 'edit', 'desc' => 'Edit warehouse stock prices (MRP and selling price)'],
    ['module' => 'assets', 'action' => 'print', 'desc' => 'Print asset labels'],
]);

$inserted = 0;
foreach ($definitions as $def) {
    $slug = $def['module'] . '.' . $def['action'];
    $exists = Database::fetchOne("SELECT id FROM permissions WHERE module = ? AND action = ?", [$def['module'], $def['action']]);
    if (!$exists) {
        Database::execute(
            "INSERT INTO permissions (module, action, description, slug, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$def['module'], $def['action'], $def['desc'], $slug]
        );
        $inserted++;
    }
}

echo "Permissions Seeding Finished. New records added: {$inserted}\n";
