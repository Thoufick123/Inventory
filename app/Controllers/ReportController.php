<?php
/**
 * ReportController — Comprehensive Enterprise Reporting & Analytics Suite
 * Covers Revenue, Costs, Expenses, Losses, Profitability, and Module-specific reports
 * with Monthly, Yearly, and Custom Date Range filtering and Excel / Print Export.
 */

require_once BASE_PATH . '/app/Services/ExpenseService.php';
require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';

class ReportController {

    private function requireReportPermission(string $type, string $action): void {
        $reportModules = [
            'overview' => 'reports_overview',
            'profitability' => 'reports_profitability',
            'website' => 'reports_website',
            'sales_website' => 'reports_website',
            'whatsapp' => 'reports_whatsapp',
            'sales_whatsapp' => 'reports_whatsapp',
            'online' => 'reports_online',
            'sales' => 'reports_sales',
            'sales_pos' => 'reports_sales',
            'sales_warehouse' => 'reports_sales',
            'production' => 'reports_production',
            'warehouse' => 'reports_warehouse',
            'purchase' => 'reports_purchase',
            'shop' => 'reports_shop',
            'inventory' => 'reports_inventory',
        ];
        $module = $reportModules[$type] ?? null;

        if ($module === null) {
            Response::error(404, 'The requested report type was not found.');
        }

        if (!hasPermission($module, $action) && !hasPermission($module, 'view') && !hasPermission('reports', 'view')) {
            Response::error(403, 'You do not have permission to access this report.');
        }
    }

    /**
     * Resolve Date Filtering Parameters (Monthly, Yearly, Custom, Presets)
     */
    private function resolveDateFilter(): array {
        $filterType = $_GET['filter_type'] ?? $_GET['period'] ?? '';
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');

        $fromDate = !empty($_GET['from_date']) ? trim($_GET['from_date']) : '';
        $toDate = !empty($_GET['to_date']) ? trim($_GET['to_date']) : '';
        $year = !empty($_GET['year']) ? (int)$_GET['year'] : (!empty($fromDate) ? (int)date('Y', strtotime($fromDate)) : $currentYear);
        $month = !empty($_GET['month']) ? (int)$_GET['month'] : (!empty($fromDate) ? (int)date('m', strtotime($fromDate)) : $currentMonth);

        // 1. If explicit from_date and to_date are provided (or if filterType is custom/date_range)
        if (!empty($fromDate) && !empty($toDate)) {
            $startDate = "{$fromDate} 00:00:00";
            $endDate = "{$toDate} 23:59:59";
            if ($fromDate === $toDate) {
                $label = date('d M Y', strtotime($fromDate));
            } elseif ($fromDate === date('Y-m-01', strtotime($fromDate)) && $toDate === date('Y-m-t', strtotime($fromDate))) {
                $label = date('F Y', strtotime($fromDate));
            } elseif ($fromDate === date('Y-01-01', strtotime($fromDate)) && $toDate === date('Y-12-31', strtotime($fromDate))) {
                $label = "Year " . date('Y', strtotime($fromDate));
            } else {
                $label = date('d M Y', strtotime($fromDate)) . " to " . date('d M Y', strtotime($toDate));
            }
            $filterType = 'custom';
            $year = (int)date('Y', strtotime($fromDate));
            $month = (int)date('m', strtotime($fromDate));
        } elseif ($filterType === 'today') {
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
            $label = "Today (" . date('d M Y') . ")";
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
        } elseif ($filterType === 'this_week') {
            $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week'));
            $fromDate = date('Y-m-d', strtotime('monday this week'));
            $toDate = date('Y-m-d', strtotime('sunday this week'));
            $label = "This Week (" . date('d M', strtotime('monday this week')) . " - " . date('d M Y', strtotime('sunday this week')) . ")";
        } elseif ($filterType === 'monthly') {
            $monthPadded = sprintf('%02d', $month);
            $startDate = "{$year}-{$monthPadded}-01 00:00:00";
            $lastDay = date('t', strtotime($startDate));
            $endDate = "{$year}-{$monthPadded}-{$lastDay} 23:59:59";
            $fromDate = "{$year}-{$monthPadded}-01";
            $toDate = "{$year}-{$monthPadded}-{$lastDay}";
            $label = date('F Y', strtotime($startDate));
        } elseif ($filterType === 'yearly') {
            $startDate = "{$year}-01-01 00:00:00";
            $endDate = "{$year}-12-31 23:59:59";
            $fromDate = "{$year}-01-01";
            $toDate = "{$year}-12-31";
            $label = "Year {$year} (Full Year)";
        } elseif ($filterType === 'last_month') {
            $firstDayLastMonth = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $lastDayLastMonth = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            $startDate = $firstDayLastMonth;
            $endDate = $lastDayLastMonth;
            $fromDate = date('Y-m-01', strtotime('first day of last month'));
            $toDate = date('Y-m-t', strtotime('last day of last month'));
            $label = date('F Y', strtotime($firstDayLastMonth));
            $filterType = 'monthly';
            $month = (int)date('m', strtotime($firstDayLastMonth));
            $year = (int)date('Y', strtotime($firstDayLastMonth));
        } elseif ($filterType === 'this_quarter') {
            $curQuarter = ceil($currentMonth / 3);
            $qStartMonth = sprintf('%02d', ($curQuarter - 1) * 3 + 1);
            $qEndMonth = sprintf('%02d', $curQuarter * 3);
            $startDate = "{$currentYear}-{$qStartMonth}-01 00:00:00";
            $lastDay = date('t', strtotime("{$currentYear}-{$qEndMonth}-01"));
            $endDate = "{$currentYear}-{$qEndMonth}-{$lastDay} 23:59:59";
            $fromDate = "{$currentYear}-{$qStartMonth}-01";
            $toDate = "{$currentYear}-{$qEndMonth}-{$lastDay}";
            $label = "Q{$curQuarter} {$currentYear}";
        } elseif ($filterType === 'all') {
            $startDate = '2020-01-01 00:00:00';
            $endDate = date('Y-m-d 23:59:59', strtotime('+1 year'));
            $fromDate = '2020-01-01';
            $toDate = date('Y-12-31', strtotime('+1 year'));
            $label = "All Time Records";
        } else {
            // Default: this_month
            $filterType = 'monthly';
            $monthPadded = sprintf('%02d', $currentMonth);
            $startDate = "{$currentYear}-{$monthPadded}-01 00:00:00";
            $lastDay = date('t', strtotime($startDate));
            $endDate = "{$currentYear}-{$monthPadded}-{$lastDay} 23:59:59";
            $fromDate = "{$currentYear}-{$monthPadded}-01";
            $toDate = "{$currentYear}-{$monthPadded}-{$lastDay}";
            $label = date('F Y', strtotime($startDate));
            $month = $currentMonth;
            $year = $currentYear;
        }

        return [
            'filter_type' => $filterType,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'label'       => $label,
            'year'        => $year,
            'month'       => $month,
            'from_date'   => $fromDate,
            'to_date'     => $toDate,
        ];
    }

    /**
     * 1. EXECUTIVE OVERVIEW & MASTER P&L COMMAND CENTER
     */
    public function index(): void {
        $filter = $this->resolveDateFilter();
        $pnl = $this->getProfitabilityMetrics($filter['start_date'], $filter['end_date']);
        $salesSummary = $this->getSalesSummary($filter['start_date'], $filter['end_date']);
        $productionSummary = $this->getProductionSummary($filter['start_date'], $filter['end_date']);
        $warehouseSummary = $this->getWarehouseSummary($filter['start_date'], $filter['end_date']);
        $inventorySummary = $this->getInventorySummary();

        // 12-Month Trend Data for Charts
        $monthlyTrend = $this->get12MonthTrend((int)$filter['year']);

        Response::view('reports.overview', [
            'pageTitle'         => 'Executive Reports & Analytics Dashboard',
            'activeModule'      => 'reports',
            'activeMenu'        => 'reports-overview',
            'filter'            => $filter,
            'pnl'               => $pnl,
            'salesSummary'      => $salesSummary,
            'productionSummary' => $productionSummary,
            'warehouseSummary'  => $warehouseSummary,
            'inventorySummary'  => $inventorySummary,
            'monthlyTrend'      => $monthlyTrend,
            'breadcrumb'        => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Executive Overview & P&L'],
            ],
        ]);
    }

    /**
     * 2. REVENUE, LOSS & PROFITABILITY DETAILED REPORT
     */
    public function profitabilityReports(): void {
        $filter = $this->resolveDateFilter();
        $pnl = $this->getProfitabilityMetrics($filter['start_date'], $filter['end_date']);
        
        // Detailed breakdown items
        $revenueDetails = $this->getRevenueLineItems($filter['start_date'], $filter['end_date']);
        $costDetails = $this->getCostLineItems($filter['start_date'], $filter['end_date']);
        $expenseDetails = $this->getExpenseLineItems($filter['start_date'], $filter['end_date']);
        $lossDetails = $this->getLossLineItems($filter['start_date'], $filter['end_date']);

        Response::view('reports.profitability', [
            'pageTitle'      => 'Profit & Loss (P&L) Financial Report',
            'activeModule'   => 'reports',
            'activeMenu'     => 'reports-profitability',
            'filter'         => $filter,
            'pnl'            => $pnl,
            'revenueDetails' => $revenueDetails,
            'costDetails'    => $costDetails,
            'expenseDetails' => $expenseDetails,
            'lossDetails'    => $lossDetails,
            'breadcrumb'     => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Profitability & P&L'],
            ],
        ]);
    }

    /**
     * 3. SALES & INVOICE MULTI-CHANNEL REPORTS
     */
    public function salesReports(): void {
        $filter = $this->resolveDateFilter();
        $channel = $_GET['channel'] ?? 'all';
        $paymentMethod = $_GET['payment_method'] ?? 'all';

        $salesData = $this->getDetailedSalesReport($filter['start_date'], $filter['end_date'], $channel, $paymentMethod);

        // Fetch shops, warehouses, production units for per-location breakdowns
        $shops = function_exists('getScopedShops') ? getScopedShops() : [];
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];
        $productionUnits = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : [];

        Response::view('reports.sales', [
            'pageTitle'       => 'Multi-Channel Sales & Invoice Report',
            'activeModule'    => 'reports',
            'activeMenu'      => 'reports-sales',
            'filter'          => $filter,
            'channel'         => $channel,
            'paymentMethod'   => $paymentMethod,
            'salesData'       => $salesData,
            'shops'           => $shops,
            'warehouses'      => $warehouses,
            'productionUnits' => $productionUnits,
            'breadcrumb'      => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Sales Reports'],
            ],
        ]);
    }

    /**
     * 3A. WEBSITE E-COMMERCE CHANNEL REPORTS & ANALYTICS
     */
    public function websiteReports(): void {
        $filter = $this->resolveDateFilter();
        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'payment_status' => trim($_GET['payment_status'] ?? 'all'),
            'payment_mode'   => trim($_GET['payment_mode'] ?? 'all'),
            'order_status'   => trim($_GET['order_status'] ?? 'all'),
        ];

        $websiteData = $this->getDetailedWebsiteReport($filter['start_date'], $filter['end_date'], $filters);

        Response::view('reports.website', [
            'pageTitle'    => 'Website E-Commerce Sales & Orders Report',
            'activeModule' => 'reports',
            'activeMenu'   => 'reports-website',
            'filter'       => $filter,
            'filters'      => $filters,
            'reportData'   => $websiteData,
            'breadcrumb'   => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Website Reports'],
            ],
        ]);
    }

    /**
     * 3B. WHATSAPP & SOCIAL COMMERCE CHANNEL REPORTS & ANALYTICS
     */
    public function whatsappReports(): void {
        $filter = $this->resolveDateFilter();
        $filters = [
            'search'         => trim($_GET['search'] ?? ''),
            'payment_status' => trim($_GET['payment_status'] ?? 'all'),
            'payment_mode'   => trim($_GET['payment_mode'] ?? 'all'),
            'order_status'   => trim($_GET['order_status'] ?? 'all'),
            'biller_name'    => trim($_GET['biller_name'] ?? 'all'),
        ];

        $whatsappData = $this->getDetailedWhatsappReport($filter['start_date'], $filter['end_date'], $filters);

        Response::view('reports.whatsapp', [
            'pageTitle'    => 'WhatsApp Orders & Social Commerce Sales Report',
            'activeModule' => 'reports',
            'activeMenu'   => 'reports-whatsapp',
            'filter'       => $filter,
            'filters'      => $filters,
            'reportData'   => $whatsappData,
            'breadcrumb'   => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'WhatsApp Reports'],
            ],
        ]);
    }

    /**
     * 3C. ONLINE & DIGITAL COMMERCE CONSOLIDATED REPORT
     */
    public function onlineReports(): void {
        $filter = $this->resolveDateFilter();
        $websiteData = $this->getDetailedWebsiteReport($filter['start_date'], $filter['end_date']);
        $whatsappData = $this->getDetailedWhatsappReport($filter['start_date'], $filter['end_date']);

        Response::view('reports.online', [
            'pageTitle'    => 'Digital & Online Channels Consolidated Report',
            'activeModule' => 'reports',
            'activeMenu'   => 'reports-online',
            'filter'       => $filter,
            'websiteData'  => $websiteData,
            'whatsappData' => $whatsappData,
            'breadcrumb'   => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Online & Digital Channels'],
            ],
        ]);
    }

    /**
     * 4. PRODUCTION & MANUFACTURING REPORTS
     */
    public function productionReports(): void {
        $filter = $this->resolveDateFilter();
        $unitId = !empty($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

        $productionData = $this->getDetailedProductionReport($filter['start_date'], $filter['end_date'], $unitId);
        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : [];

        Response::view('reports.production', [
            'pageTitle'      => 'Production & Manufacturing Reports',
            'activeModule'   => 'reports',
            'activeMenu'     => 'reports-production',
            'filter'         => $filter,
            'unitId'         => $unitId,
            'units'          => $units,
            'productionData' => $productionData,
            'breadcrumb'     => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Production Reports'],
            ],
        ]);
    }

    /**
     * 5. WAREHOUSE & INVENTORY MOVEMENTS REPORTS
     */
    public function warehouseReports(): void {
        $filter = $this->resolveDateFilter();
        $warehouseId = !empty($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

        $warehouseData = $this->getDetailedWarehouseReport($filter['start_date'], $filter['end_date'], $warehouseId);
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : [];

        Response::view('reports.warehouse', [
            'pageTitle'     => 'Warehouse Logistics & Damage Reports',
            'activeModule'  => 'reports',
            'activeMenu'    => 'reports-warehouse',
            'filter'        => $filter,
            'warehouseId'   => $warehouseId,
            'warehouses'    => $warehouses,
            'warehouseData' => $warehouseData,
            'breadcrumb'    => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Warehouse Reports'],
            ],
        ]);
    }

    /**
     * 6. SUPPLIER PURCHASES & PROCUREMENT REPORTS
     */
    public function purchaseReports(): void {
        $filter = $this->resolveDateFilter();
        $supplierId = !empty($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;

        $purchaseData = $this->getDetailedPurchaseReport($filter['start_date'], $filter['end_date'], $supplierId);
        $suppliers = Database::fetchAll("SELECT id, name, company FROM warehouse_suppliers WHERE is_active = 1 ORDER BY name ASC");

        Response::view('reports.purchase', [
            'pageTitle'    => 'Supplier Procurement & Purchase Orders',
            'activeModule' => 'reports',
            'activeMenu'   => 'reports-purchase',
            'filter'       => $filter,
            'supplierId'   => $supplierId,
            'suppliers'    => $suppliers,
            'purchaseData' => $purchaseData,
            'breadcrumb'   => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Purchase Reports'],
            ],
        ]);
    }

    /**
     * 7. RETAIL SHOP & POS CASH SHIFT REPORTS
     */
    public function shopReports(): void {
        $filter = $this->resolveDateFilter();
        $shopId = !empty($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;

        $shopData = $this->getDetailedShopReport($filter['start_date'], $filter['end_date'], $shopId);
        $shops = function_exists('getScopedShops') ? getScopedShops() : [];

        Response::view('reports.shop', [
            'pageTitle'    => 'Retail Stores & POS Cash Flow Reports',
            'activeModule' => 'reports',
            'activeMenu'   => 'reports-shop',
            'filter'       => $filter,
            'shopId'       => $shopId,
            'shops'        => $shops,
            'shopData'     => $shopData,
            'breadcrumb'   => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Shop Reports'],
            ],
        ]);
    }

    /**
     * 8. INVENTORY VALUATION & STOCK HEALTH REPORTS
     */
    public function inventoryReports(): void {
        $locationType = $_GET['location_type'] ?? 'ALL';
        $stockState = $_GET['stock_state'] ?? 'ALL';
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

        $inventoryData = $this->getDetailedInventoryReport($locationType, $stockState, $categoryId);
        $categories = Database::fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");

        Response::view('reports.inventory', [
            'pageTitle'     => 'Inventory Valuation & Reorder Reports',
            'activeModule'  => 'reports',
            'activeMenu'    => 'reports-inventory',
            'locationType'  => $locationType,
            'stockState'    => $stockState,
            'categoryId'    => $categoryId,
            'categories'    => $categories,
            'inventoryData' => $inventoryData,
            'breadcrumb'    => [
                ['label' => 'Reports', 'url' => url('reports')],
                ['label' => 'Inventory Reports'],
            ],
        ]);
    }

    /**
     * 9. PRINT / PDF EXPORT READY VIEW
     */
    public function printReport(string $type): void {
        $this->requireReportPermission($type, 'print');
        $filter = $this->resolveDateFilter();
        $data = $this->collectReportDataForExport($type, $filter);

        Response::viewOnly('reports.print', [
            'type'       => $type,
            'filter'     => $filter,
            'reportData' => $data,
        ]);
    }

    public function downloadReportPdf(string $type): void {
        $this->requireReportPermission($type, 'export');
        $filter = $this->resolveDateFilter();
        $data = $this->collectReportDataForExport($type, $filter);
        $pdf = $this->buildReportPdf($type, $filter, $data);
        $filename = 'GRM_' . strtoupper($type) . '_REPORT_' . date('Ymd_His') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, no-store, max-age=0');
        echo $pdf;
        exit;
    }

    private function buildReportPdf(string $type, array $filter, array $data): string {
        $pageWidth = 842;
        $pageHeight = 595;
        $margin = 32;
        $contentWidth = $pageWidth - ($margin * 2);
        $pages = [];
        $stream = '';
        $y = 30;
        $cleanText = static function ($value): string {
            if (!is_scalar($value) && $value !== null) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            }
            $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$value);
            if ($text === false) {
                $text = preg_replace('/[^\x20-\x7E]/', '?', (string)$value);
            }
            return preg_replace('/[^\x20-\x7E]/', '?', $text);
        };
        $drawRect = static function (float $x, float $top, float $width, float $height, array $fill, ?array $stroke = null) use (&$stream, $pageHeight): void {
            $stream .= "q\n";
            $stream .= implode(' ', $fill) . " rg\n";
            if ($stroke !== null) {
                $stream .= implode(' ', $stroke) . " RG\n0.7 w\n";
            }
            $stream .= sprintf("%.2f %.2f %.2f %.2f re %s\n", $x, $pageHeight - $top - $height, $width, $height, $stroke === null ? 'f' : 'B');
            $stream .= "Q\n";
        };
        $drawText = static function (float $x, float $top, string $text, float $size = 9, array $color = [0.12, 0.16, 0.25], bool $bold = false) use (&$stream, $pageHeight, $cleanText): void {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $cleanText($text));
            $stream .= sprintf(
                "BT /F%d %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
                $bold ? 2 : 1,
                $size,
                $color[0],
                $color[1],
                $color[2],
                $x,
                $pageHeight - $top - $size,
                $escaped
            );
        };
        $wrapText = static function (string $text, float $width, float $size) use ($cleanText): array {
            $text = $cleanText($text);
            $limit = max(8, (int)floor($width / max(3.5, $size * 0.53)));
            $lines = [];
            foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $text)) as $part) {
                $wrapped = wordwrap($part, $limit, "\n", true);
                $lines = array_merge($lines, explode("\n", $wrapped));
            }
            return $lines ?: [''];
        };
        $startPage = static function (bool $continued = false) use (&$pages, &$stream, &$y, $drawText, $drawRect, $margin, $contentWidth, $data): void {
            if ($stream !== '') {
                $pages[] = $stream;
            }
            $stream = '';
            $y = 30;
            if ($continued) {
                $drawText($margin, $y, $data['title'] ?? 'Report', 12, [0.12, 0.16, 0.35], true);
                $drawText($margin + $contentWidth - 180, $y + 2, 'REPORT DETAILS - CONTINUED', 8, [0.38, 0.4, 0.52], true);
                $drawRect($margin, $y + 20, $contentWidth, 1, [0.38, 0.22, 0.83]);
                $y += 34;
            }
        };
        $ensureSpace = static function (float $height) use (&$y, $startPage, $pageHeight): void {
            if ($y + $height > $pageHeight - 28) {
                $startPage(true);
            }
        };
        $drawSectionTitle = static function (string $title) use (&$y, $drawText, $drawRect, $margin, $ensureSpace): void {
            $ensureSpace(24);
            $drawRect($margin, $y + 2, 4, 13, [0.42, 0.22, 0.9]);
            $drawText($margin + 11, $y, $title, 11, [0.12, 0.14, 0.28], true);
            $y += 21;
        };

        $title = $data['title'] ?? strtoupper($type) . ' REPORT';
        $drawRect(0, 0, $pageWidth, 7, [0.39, 0.22, 0.87]);
        $drawRect($margin, $y, 36, 36, [0.39, 0.22, 0.87]);
        $drawText($margin + 8, $y + 10, 'GRM', 12, [1, 1, 1], true);
        $drawText($margin + 48, $y - 1, $title, 19, [0.08, 0.1, 0.25], true);
        $drawText($margin + 48, $y + 23, 'Digital & operational report register', 9, [0.35, 0.38, 0.48]);
        $drawText($pageWidth - 205, $y + 1, strtoupper($type) . ' REPORT', 8, [0.39, 0.22, 0.87], true);
        $drawText($pageWidth - 205, $y + 17, 'Period: ' . ($filter['label'] ?? ''), 8, [0.22, 0.24, 0.33]);
        $y += 48;

        $dateRange = ($filter['start_date'] ?? '') . ' to ' . ($filter['end_date'] ?? '');
        $metaGap = 9;
        $metaWidth = ($contentWidth - ($metaGap * 2)) / 3;
        foreach ([
            ['Date Range', $dateRange],
            ['Generated On', date('Y-m-d H:i:s')],
            ['Records', number_format(count($data['rows'] ?? [])) . ' total records'],
        ] as $index => [$label, $value]) {
            $x = $margin + ($index * ($metaWidth + $metaGap));
            $drawRect($x, $y, $metaWidth, 39, [0.96, 0.95, 1], [0.86, 0.83, 0.97]);
            $drawText($x + 10, $y + 6, $label, 7, [0.39, 0.35, 0.55], true);
            $valueLines = $wrapText($value, $metaWidth - 20, 7.5);
            $drawText($x + 10, $y + 19, $valueLines[0] ?? '', 7.5, [0.12, 0.15, 0.27]);
            if (isset($valueLines[1])) {
                $drawText($x + 10, $y + 28, $valueLines[1], 7, [0.12, 0.15, 0.27]);
            }
        }
        $y += 49;

        $summary = $data['summary_kpis'] ?? [];
        if ($summary) {
            $drawSectionTitle('Summary Overview');
            $columnsPerRow = 4;
            $cardGap = 8;
            $cardWidth = ($contentWidth - ($cardGap * ($columnsPerRow - 1))) / $columnsPerRow;
            foreach (array_chunk($summary, $columnsPerRow, true) as $summaryRow) {
                $prepared = [];
                $rowHeight = 44;
                foreach ($summaryRow as $label => $value) {
                    $labelLines = $wrapText((string)$label, $cardWidth - 18, 6.5);
                    $valueLines = $wrapText((string)$value, $cardWidth - 18, 9);
                    $cardHeight = 12 + (count($labelLines) * 8) + (count($valueLines) * 10);
                    $rowHeight = max($rowHeight, $cardHeight);
                    $prepared[] = [$labelLines, $valueLines];
                }
                $ensureSpace($rowHeight + 5);
                foreach ($prepared as $index => [$labelLines, $valueLines]) {
                    $x = $margin + ($index * ($cardWidth + $cardGap));
                    $drawRect($x, $y, $cardWidth, $rowHeight, [0.95, 0.94, 1], [0.86, 0.83, 0.97]);
                    $drawRect($x, $y, 3, $rowHeight, [0.39, 0.22, 0.87]);
                    $textY = $y + 6;
                    foreach ($labelLines as $line) {
                        $drawText($x + 9, $textY, $line, 6.5, [0.38, 0.37, 0.48], true);
                        $textY += 8;
                    }
                    $textY += 2;
                    foreach ($valueLines as $line) {
                        $drawText($x + 9, $textY, $line, 9, [0.08, 0.1, 0.25], true);
                        $textY += 10;
                    }
                }
                $y += $rowHeight + 7;
            }
        }

        $columns = $data['columns'] ?? [];
        $rows = $data['rows'] ?? [];
        $drawSectionTitle('Report Details (' . count($rows) . ' records)');
        if (!$rows) {
            $drawRect($margin, $y, $contentWidth, 34, [0.97, 0.97, 0.99], [0.88, 0.88, 0.91]);
            $drawText($margin + 12, $y + 11, 'No records found for the selected reporting period.', 9, [0.38, 0.4, 0.48]);
            $y += 42;
        }

        foreach ($rows as $recordIndex => $row) {
            $fields = [];
            foreach (array_values($row) as $columnIndex => $value) {
                $label = $columns[$columnIndex] ?? ('Column ' . ($columnIndex + 1));
                $valueLines = $wrapText((string)$value, ($contentWidth / 2) - 25, 7.5);
                $fields[] = [
                    'label' => $wrapText((string)$label, 110, 6.5),
                    'value' => $valueLines,
                ];
            }

            $leftFields = array_slice($fields, 0, (int)ceil(count($fields) / 2));
            $rightFields = array_slice($fields, count($leftFields));
            $fieldRows = max(count($leftFields), count($rightFields));
            $fieldRowHeights = [];
            $recordHeight = 20 + 8;
            for ($fieldRow = 0; $fieldRow < $fieldRows; $fieldRow++) {
                $left = $leftFields[$fieldRow] ?? null;
                $right = $rightFields[$fieldRow] ?? null;
                $lines = max(
                    $left ? count($left['label']) + count($left['value']) : 0,
                    $right ? count($right['label']) + count($right['value']) : 0
                );
                $fieldRowHeights[$fieldRow] = max(21, ($lines * 8) + 5);
                $recordHeight += $fieldRowHeights[$fieldRow];
            }

            $ensureSpace($recordHeight + 5);
            $drawRect($margin, $y, $contentWidth, $recordHeight, [1, 1, 1], [0.85, 0.83, 0.94]);
            $drawRect($margin, $y, $contentWidth, 20, [0.93, 0.9, 1]);
            $drawText($margin + 10, $y + 5, 'Record ' . ($recordIndex + 1), 8, [0.23, 0.15, 0.48], true);

            $fieldY = $y + 25;
            $halfWidth = $contentWidth / 2;
            foreach ($fieldRowHeights as $fieldRow => $fieldRowHeight) {
                foreach ([[$leftFields[$fieldRow] ?? null, $margin + 10], [$rightFields[$fieldRow] ?? null, $margin + $halfWidth + 8]] as [$field, $x]) {
                    if ($field === null) {
                        continue;
                    }
                    $textY = $fieldY;
                    foreach ($field['label'] as $labelLine) {
                        $drawText($x, $textY, $labelLine, 6.2, [0.43, 0.43, 0.53], true);
                        $textY += 7;
                    }
                    foreach ($field['value'] as $valueLine) {
                        $drawText($x, $textY, $valueLine, 7.5, [0.12, 0.14, 0.23]);
                        $textY += 8;
                    }
                }
                $fieldY += $fieldRowHeight;
            }
            $y += $recordHeight + 6;
        }

        $totals = $data['totals'] ?? [];
        $totalCards = [];
        foreach ($totals as $index => $value) {
            if ($value === '' || $value === null || strtoupper((string)$value) === 'TOTALS') {
                continue;
            }
            $totalCards[] = [
                $columns[$index] ?? ('Total ' . ($index + 1)),
                (string)$value,
            ];
        }
        if ($totalCards) {
            $drawSectionTitle('Totals');
            $columnsPerRow = 4;
            $cardGap = 8;
            $cardWidth = ($contentWidth - ($cardGap * ($columnsPerRow - 1))) / $columnsPerRow;
            foreach (array_chunk($totalCards, $columnsPerRow) as $totalRow) {
                $prepared = [];
                $rowHeight = 36;
                foreach ($totalRow as $index => [$label, $value]) {
                    $labelLines = $wrapText($label, $cardWidth - 18, 6.3);
                    $valueLines = $wrapText($value, $cardWidth - 18, 8.5);
                    $rowHeight = max($rowHeight, 12 + count($labelLines) * 7 + count($valueLines) * 9);
                    $prepared[] = [$labelLines, $valueLines];
                }
                $ensureSpace($rowHeight + 7);
                foreach ($prepared as $index => [$labelLines, $valueLines]) {
                    $x = $margin + ($index * ($cardWidth + $cardGap));
                    $drawRect($x, $y, $cardWidth, $rowHeight, [0.92, 0.98, 0.94], [0.82, 0.92, 0.85]);
                    $textY = $y + 5;
                    foreach ($labelLines as $line) {
                        $drawText($x + 9, $textY, $line, 6.3, [0.31, 0.39, 0.35], true);
                        $textY += 7;
                    }
                    $textY += 2;
                    foreach ($valueLines as $line) {
                        $drawText($x + 9, $textY, $line, 8.5, [0.08, 0.25, 0.14], true);
                        $textY += 9;
                    }
                }
                $y += $rowHeight + 7;
            }
        }

        if ($stream !== '') {
            $pages[] = $stream;
        }

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];
        $pageReferences = [];
        foreach ($pages as $pageIndex => $pageStream) {
            $pageObjectId = 5 + ($pageIndex * 2);
            $contentObjectId = $pageObjectId + 1;
            $pageReferences[] = $pageObjectId . ' 0 R';
            $footer = sprintf('BT /F1 7 Tf 0.45 0.46 0.53 rg 1 0 0 1 %.2f 16 Tm (Page %d of %d) Tj ET', $pageWidth - 90, $pageIndex + 1, count($pages));
            $pageStream .= $footer . "\n";
            $objects[$pageObjectId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageWidth . ' ' . $pageHeight . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObjectId . ' 0 R >>';
            $objects[$contentObjectId] = '<< /Length ' . strlen($pageStream) . " >>\nstream\n" . $pageStream . "endstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageReferences) . '] /Count ' . count($pageReferences) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $objectId => $objectBody) {
            $offsets[$objectId] = strlen($pdf);
            $pdf .= $objectId . " 0 obj\n" . $objectBody . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 " . $objectCount . "\n0000000000 65535 f \n";
        for ($objectId = 1; $objectId < $objectCount; $objectId++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectId] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . $objectCount . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    /**
     * 10. UNIFIED EXCEL DATA EXPORT ENGINE
     */
    public function exportReport(string $type): void {
        $this->requireReportPermission($type, 'export');
        $filter = $this->resolveDateFilter();
        $data = $this->collectReportDataForExport($type, $filter);
        $data['detail_sheets'] = $this->getExportProductSheets($type, $data);
        $workbook = $this->buildExcelWorkbook($type, $filter, $data);
        $filename = 'GRM_' . strtoupper($type) . '_REPORT_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($workbook));
        header('Cache-Control: private, no-store, max-age=0');
        echo $workbook;
        exit;
    }

    private function getExportProductSheets(string $type, array $data): array {
        $sources = [];
        if ($type === 'online') {
            $sources = [
                ['name' => 'Website', 'data' => $data['raw_data']['website'] ?? []],
                ['name' => 'WhatsApp', 'data' => $data['raw_data']['whatsapp'] ?? []],
            ];
        } elseif ($type === 'website' || $type === 'sales_website') {
            $sources = [['name' => 'Website', 'data' => $data['raw_data'] ?? []]];
        } elseif ($type === 'whatsapp' || $type === 'sales_whatsapp') {
            $sources = [['name' => 'WhatsApp', 'data' => $data['raw_data'] ?? []]];
        }

        $productRows = [];
        foreach ($sources as $source) {
            $reportData = $source['data'];
            $itemsMap = $reportData['itemsMap'] ?? [];
            $ordersById = [];
            foreach (($reportData['orders'] ?? []) as $order) {
                $ordersById[(string)$order['id']] = $order;
            }

            foreach ($itemsMap as $saleId => $items) {
                $order = $ordersById[(string)$saleId] ?? [];
                foreach ($items as $item) {
                    $productRows[] = [
                        $order['invoice_number'] ?? '',
                        $source['name'],
                        $order['customer_name'] ?? '',
                        $order['customer_phone'] ?? '',
                        $item['product_code'] ?? '',
                        $item['sku'] ?? '',
                        $item['product_name'] ?? '',
                        $item['size_name'] ?? '',
                        $item['color_name'] ?? '',
                        (float)($item['quantity'] ?? 0),
                        (float)($item['unit_price'] ?? 0),
                        (float)($item['total_amount'] ?? 0),
                        $order['created_at'] ?? '',
                    ];
                }
            }
        }

        if (!$sources) {
            return [];
        }

        return [[
            'name' => 'Product Details',
            'title' => 'Sold Products, Codes and Amounts',
            'columns' => ['Order / Invoice #', 'Channel', 'Customer Name', 'Customer Contact', 'Product Code', 'SKU', 'Product Name', 'Size', 'Color', 'Quantity', 'Unit Price', 'Line Amount', 'Order Date'],
            'rows' => $productRows,
            'totals' => [],
            'summary_kpis' => [],
        ]];
    }

    private function buildExcelWorkbook(string $type, array $filter, array $data): string {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Excel export requires the PHP ZipArchive extension.');
        }

        $sheets = [];
        if (!empty($data['summary_kpis'])) {
            $summaryRows = [];
            foreach ($data['summary_kpis'] as $label => $value) {
                $summaryRows[] = [(string)$label, (string)$value];
            }
            $sheets[] = [
                'name' => 'Summary',
                'title' => $data['title'] ?? strtoupper($type) . ' REPORT',
                'columns' => ['Summary Metric', 'Value'],
                'rows' => $summaryRows,
                'totals' => [],
                'summary_kpis' => [],
            ];
        }
        $sheets[] = [
            'name' => 'Records',
            'title' => $data['title'] ?? strtoupper($type) . ' REPORT',
            'columns' => $data['columns'] ?? [],
            'rows' => $data['rows'] ?? [],
            'totals' => $data['totals'] ?? [],
            'summary_kpis' => [],
        ];
        foreach (($data['detail_sheets'] ?? []) as $sheet) {
            $sheets[] = $sheet;
        }

        $sheetXml = [];
        foreach ($sheets as $index => $sheet) {
            $sheetXml[] = $this->buildExcelSheetXml($sheet, $filter, $index === 0);
        }
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach ($sheets as $index => $sheet) {
            $workbookXml .= '<sheet name="' . $this->excelXmlEscape($sheet['name']) . '" sheetId="' . ($index + 1) . '" r:id="rId' . ($index + 1) . '"/>';
        }
        $workbookXml .= '</sheets></workbook>';

        $relationships = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($sheets as $index => $_sheet) {
            $relationships .= '<Relationship Id="rId' . ($index + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($index + 1) . '.xml"/>';
        }
        $relationships .= '<Relationship Id="rId' . (count($sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($sheets as $index => $_sheet) {
            $contentTypes .= '<Override PartName="/xl/worksheets/sheet' . ($index + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $contentTypes .= '</Types>';

        $rootRelationships = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FF172554"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEDE9FE"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF5B35C5"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFE5E7EB"/></left><right style="thin"><color rgb="FFE5E7EB"/></right><top style="thin"><color rgb="FFE5E7EB"/></top><bottom style="thin"><color rgb="FFE5E7EB"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0"><alignment horizontal="right" vertical="center"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

        $tempPath = tempnam(sys_get_temp_dir(), 'grm-report-');
        if ($tempPath === false) {
            throw new RuntimeException('Unable to allocate a temporary file for the Excel export.');
        }
        try {
            $archive = new ZipArchive();
            if ($archive->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the Excel workbook archive.');
            }
            foreach ([
                '[Content_Types].xml' => $contentTypes,
                '_rels/.rels' => $rootRelationships,
                'xl/workbook.xml' => $workbookXml,
                'xl/_rels/workbook.xml.rels' => $relationships,
                'xl/styles.xml' => $styles,
            ] as $path => $contents) {
                if (!$archive->addFromString($path, $contents)) {
                    throw new RuntimeException('Unable to write the Excel workbook structure.');
                }
            }
            foreach ($sheetXml as $index => $xml) {
                if (!$archive->addFromString('xl/worksheets/sheet' . ($index + 1) . '.xml', $xml)) {
                    throw new RuntimeException('Unable to write an Excel report sheet.');
                }
            }
            if (!$archive->close()) {
                throw new RuntimeException('Unable to finish writing the Excel workbook.');
            }
            $workbook = file_get_contents($tempPath);
            if ($workbook === false) {
                throw new RuntimeException('Unable to read the completed Excel workbook.');
            }
            return $workbook;
        } finally {
            if (is_file($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    private function buildExcelSheetXml(array $sheet, array $filter, bool $includeSummary): string {
        $rows = [];
        $rowNumber = 1;
        $columns = $sheet['columns'] ?? [];
        $columnCount = max(1, count($columns));
        $lastColumn = $this->excelColumnName($columnCount);
        $widths = [];
        foreach ($columns as $index => $column) {
            $maxLength = strlen((string)$column);
            foreach (($sheet['rows'] ?? []) as $record) {
                $maxLength = max($maxLength, strlen((string)($record[$index] ?? '')));
            }
            $minimumWidth = $includeSummary ? ($index === 0 ? 30 : 42) : 14;
            $widths[] = min(60, max($minimumWidth, $maxLength + 3));
        }
        $rows[] = '<row r="' . $rowNumber . '" ht="26" customHeight="1">' . $this->excelCell('A1', $sheet['title'], 1) . '</row>';
        $rowNumber++;
        if ($includeSummary) {
            $rows[] = '<row r="' . $rowNumber . '">' . $this->excelCell('A' . $rowNumber, 'Reporting Period', 2) . $this->excelCell('B' . $rowNumber, $filter['label'] ?? '') . '</row>';
            $rowNumber++;
            $rows[] = '<row r="' . $rowNumber . '">' . $this->excelCell('A' . $rowNumber, 'Date Range', 2) . $this->excelCell('B' . $rowNumber, ($filter['start_date'] ?? '') . ' to ' . ($filter['end_date'] ?? '')) . '</row>';
            $rowNumber++;
            $rows[] = '<row r="' . $rowNumber . '">' . $this->excelCell('A' . $rowNumber, 'Generated On', 2) . $this->excelCell('B' . $rowNumber, date('Y-m-d H:i:s')) . '</row>';
            $rowNumber++;
            foreach (($sheet['summary_kpis'] ?? []) as $label => $value) {
                $rows[] = '<row r="' . $rowNumber . '">' . $this->excelCell('A' . $rowNumber, (string)$label, 2) . $this->excelCell('B' . $rowNumber, (string)$value) . '</row>';
                $rowNumber++;
            }
            $rowNumber++;
        } else {
            $rowNumber++;
        }

        $headerRow = $rowNumber;
        $headerCells = '';
        foreach ($columns as $index => $column) {
            $headerCells .= $this->excelCell($this->excelColumnName($index + 1) . $rowNumber, (string)$column, 3);
        }
        $rows[] = '<row r="' . $rowNumber . '" ht="32" customHeight="1">' . $headerCells . '</row>';
        $rowNumber++;

        foreach (($sheet['rows'] ?? []) as $record) {
            $cells = '';
            $rowHeight = 24;
            foreach (array_values($record) as $index => $value) {
                $columnName = (string)($columns[$index] ?? '');
                $cellRef = $this->excelColumnName($index + 1) . $rowNumber;
                $cells .= $this->excelCell($cellRef, $value, 0, $columnName);
                $cellText = is_scalar($value) || $value === null ? (string)$value : json_encode($value);
                $rowHeight = max($rowHeight, min(300, (ceil(strlen((string)$cellText) / max(10, ($widths[$index] ?? 14) - 2)) * 14) + 8));
            }
            $rows[] = '<row r="' . $rowNumber . '" customHeight="1" ht="' . $rowHeight . '">' . $cells . '</row>';
            $rowNumber++;
        }

        if (!empty($sheet['totals'])) {
            $cells = '';
            foreach (array_values($sheet['totals']) as $index => $value) {
                $cells .= $this->excelCell($this->excelColumnName($index + 1) . $rowNumber, $value, 2, (string)($columns[$index] ?? ''));
            }
            $rows[] = '<row r="' . $rowNumber . '" ht="24" customHeight="1">' . $cells . '</row>';
            $rowNumber++;
        }
        $lastRow = max($headerRow, $rowNumber - 1);

        $widthXml = [];
        foreach ($widths as $index => $width) {
            $widthXml[] = '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $headerRow . '" topLeftCell="A' . ($headerRow + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . implode('', $widthXml) . '</cols><sheetData>' . implode('', $rows) . '</sheetData>'
            . '<autoFilter ref="A' . $headerRow . ':' . $lastColumn . $lastRow . '"/>'
            . '<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '</worksheet>';
    }

    private function excelCell(string $reference, $value, int $style = 0, string $columnName = ''): string {
        if (is_int($value) || is_float($value)) {
            $numberFormat = preg_match('/amount|price|revenue|discount|paid|tax|cost|spend|loss|profit|total \(inr\)|\(inr\)/i', $columnName) ? 4 : $style;
            return '<c r="' . $reference . '" s="' . $numberFormat . '" t="n"><v>' . (string)$value . '</v></c>';
        }

        $text = is_scalar($value) || $value === null ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->excelXmlEscape($text) . '</t></is></c>';
    }

    private function excelXmlEscape(string $value): string {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function excelColumnName(int $number): string {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = intdiv($number, 26);
        }
        return $name;
    }

    // =========================================================================
    // DATA COMPUTATION & AGGREGATION ENGINES
    // =========================================================================

    /**
     * Compute Master Profitability & P&L Metrics
     */
    private function getProfitabilityMetrics(string $startDate, string $endDate): array {
        $concernId = ConcernTransactionService::activeConcernId();
        // 1. REVENUE
        // A. Direct Customer Purchases in Warehouse
        $whSales = Database::fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as total,
                    COALESCE(SUM(discount_amount), 0) as discount,
                    COALESCE(SUM(tax_amount), 0) as tax,
                    COALESCE(SUM(paid_amount), 0) as paid,
                    COUNT(id) as count
             FROM purchase_orders
             WHERE concern_id = ? AND created_at BETWEEN ? AND ?
               AND (customer_id IS NOT NULL OR warehouse_supplier_id IS NULL)",
            [$concernId, $startDate, $endDate]
        );

        // B. Retail Shop POS Sales
        $shopSales = Database::fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as total,
                    COALESCE(SUM(discount_amount), 0) as discount,
                    COALESCE(SUM(tax_amount), 0) as tax,
                    COALESCE(SUM(paid_amount), 0) as paid,
                    COUNT(id) as count
             FROM sales
             WHERE concern_id = ? AND created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );

        // C. Wastage Sales (Cutting scrap)
        $cuttingWastage = Database::fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as total,
                    COALESCE(SUM(weight), 0) as total_kg,
                    COUNT(id) as count
             FROM wastage_sales
             WHERE concern_id = ? AND (sale_date BETWEEN ? AND ? OR created_at BETWEEN ? AND ?)",
            [$concernId, $startDate, $endDate, $startDate, $endDate]
        );

        // D. Stitching Wastage Sales
        $stitchingWastage = Database::fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as total,
                    COALESCE(SUM(quantity), 0) as total_qty,
                    COUNT(id) as count
             FROM stitching_wastage_sales
             WHERE concern_id = ? AND (sale_date BETWEEN ? AND ? OR created_at BETWEEN ? AND ?)",
            [$concernId, $startDate, $endDate, $startDate, $endDate]
        );

        $grossRevenue = (float)$whSales['total'] + (float)$shopSales['total'] + (float)$cuttingWastage['total'] + (float)$stitchingWastage['total'];
        $totalDiscountGiven = (float)$whSales['discount'] + (float)$shopSales['discount'];
        $totalTaxCollected = (float)$whSales['tax'] + (float)$shopSales['tax'];

        // 2. COST OF GOODS SOLD (COGS)
        // A. Production Batches produced
        $prodCost = Database::fetchOne(
            "SELECT COALESCE(SUM(total_cost), 0) as total_cost,
                    COALESCE(SUM(total_qty), 0) as total_pieces
             FROM production_batches pb
             JOIN production_programs pp ON pb.program_id = pp.id
             JOIN production_units pu ON pp.production_unit_id = pu.id
             WHERE pu.concern_id = ? AND pb.created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );

        // B. External Supplier Purchases
        $suppPurchases = Database::fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) as total
             FROM purchase_orders
             WHERE concern_id = ? AND created_at BETWEEN ? AND ?
               AND warehouse_supplier_id IS NOT NULL",
            [$concernId, $startDate, $endDate]
        );

        $directCost = (float)$prodCost['total_cost'] + (float)$suppPurchases['total'];

        // 3. OPERATING & OVERHEAD EXPENSES
        // A. Production Unit 1 & Unit 2 Expenses
        $prodExpenses = Database::fetchOne(
            "SELECT COALESCE(SUM(total_cost), 0) as total,
                    COUNT(id) as count
             FROM production_expenses
             WHERE concern_id = ? AND (expense_date BETWEEN ? AND ? OR created_at BETWEEN ? AND ?)",
            [$concernId, $startDate, $endDate, $startDate, $endDate]
        );

        // B. Shop Cash Expenses
        $shopExpenses = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total,
                    COUNT(id) as count
             FROM cash_expenses
             WHERE concern_id = ? AND created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );

        // C. Module & Channel Expenses (Warehouse, WhatsApp, Website from unified expenses table)
        $moduleExpenses = Database::fetchOne(
            "SELECT COALESCE(SUM(total_cost), 0) as total,
                    COALESCE(SUM(CASE WHEN location_type = 'WAREHOUSE' THEN total_cost ELSE 0 END), 0) as wh_total,
                    COALESCE(SUM(CASE WHEN location_type IN ('WHATSAPP', 'CHANNEL') THEN total_cost ELSE 0 END), 0) as wa_total,
                    COALESCE(SUM(CASE WHEN location_type = 'WEBSITE' THEN total_cost ELSE 0 END), 0) as web_total,
                    COUNT(id) as count
             FROM expenses
             WHERE concern_id = ? AND (expense_date BETWEEN ? AND ? OR created_at BETWEEN ? AND ?)",
            [$concernId, $startDate, $endDate, $startDate, $endDate]
        );

        // D. Asset Maintenance Costs
        $assetMaintenance = Database::fetchOne(
            "SELECT COALESCE(SUM(service_cost), 0) as total,
                    COUNT(id) as count
             FROM asset_maintenance_logs
             WHERE (service_date BETWEEN ? AND ? OR created_at BETWEEN ? AND ?)",
            [$startDate, $endDate, $startDate, $endDate]
        );

        $operatingExpenses = (float)$prodExpenses['total'] + (float)$shopExpenses['total'] + (float)($moduleExpenses['total'] ?? 0) + (float)$assetMaintenance['total'];

        // 4. RECORDED LOSSES & DEFECT SCRAP
        // A. Warehouse Damage Cases
        $whDamage = Database::fetchOne(
            "SELECT COALESCE(SUM(total_loss), 0) as total_loss,
                    COALESCE(SUM(quantity), 0) as total_qty,
                    COUNT(id) as count
             FROM warehouse_damages
             WHERE warehouse_id IN (SELECT id FROM warehouses WHERE concern_id = ?)
               AND created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );

        // B. Fabric Roll Damages
        $rollDamage = Database::fetchOne(
            "SELECT COALESCE(SUM(loss_amount), 0) as total_loss,
                    COALESCE(SUM(damaged_quantity), 0) as total_meters,
                    COUNT(id) as count
             FROM roll_damages
             WHERE created_at BETWEEN ? AND ?",
            [$startDate, $endDate]
        );

        // C. Defective Customer Returns
        $returnLoss = Database::fetchOne(
            "SELECT COALESCE(SUM((wri.damaged_qty + wri.defective_qty) * COALESCE(pv.purchase_price, pv.selling_price, 0)), 0) as return_loss,
                    COALESCE(SUM(wri.damaged_qty + wri.defective_qty), 0) as defect_qty
             FROM warehouse_return_items wri
             JOIN warehouse_returns wr ON wri.warehouse_return_id = wr.id
             LEFT JOIN product_variants pv ON wri.variant_id = pv.id
             WHERE wr.concern_id = ? AND wr.created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );

        $recordedLosses = (float)$whDamage['total_loss'] + (float)$rollDamage['total_loss'] + (float)$returnLoss['return_loss'];

        // 5. PROFITABILITY CALCULATIONS
        $grossProfit = $grossRevenue - $directCost;
        $netProfit = $grossRevenue - ($directCost + $operatingExpenses + $recordedLosses);
        $grossMarginPct = $grossRevenue > 0 ? round(($grossProfit / $grossRevenue) * 100, 2) : 0;
        $netMarginPct = $grossRevenue > 0 ? round(($netProfit / $grossRevenue) * 100, 2) : 0;

        return [
            'gross_revenue'       => $grossRevenue,
            'wh_sales_revenue'    => (float)$whSales['total'],
            'shop_sales_revenue'  => (float)$shopSales['total'],
            'wastage_revenue'     => (float)$cuttingWastage['total'] + (float)$stitchingWastage['total'],
            'cutting_wastage_rev' => (float)$cuttingWastage['total'],
            'stitch_wastage_rev'  => (float)$stitchingWastage['total'],
            'total_discount'      => $totalDiscountGiven,
            'total_tax'           => $totalTaxCollected,
            'direct_cost'         => $directCost,
            'production_cost'     => (float)$prodCost['total_cost'],
            'supplier_purchases'  => (float)$suppPurchases['total'],
            'operating_expenses'  => $operatingExpenses,
            'production_expenses' => (float)$prodExpenses['total'],
            'shop_expenses'       => (float)$shopExpenses['total'],
            'warehouse_expenses'  => (float)($moduleExpenses['wh_total'] ?? 0),
            'whatsapp_expenses'   => (float)($moduleExpenses['wa_total'] ?? 0),
            'website_expenses'    => (float)($moduleExpenses['web_total'] ?? 0),
            'asset_maintenance'   => (float)$assetMaintenance['total'],
            'recorded_losses'     => $recordedLosses,
            'wh_damage_loss'      => (float)$whDamage['total_loss'],
            'roll_damage_loss'    => (float)$rollDamage['total_loss'],
            'return_defect_loss'  => (float)$returnLoss['return_loss'],
            'gross_profit'        => $grossProfit,
            'net_profit'          => $netProfit,
            'gross_margin_pct'    => $grossMarginPct,
            'net_margin_pct'      => $netMarginPct,
            'invoices_count'      => (int)$whSales['count'] + (int)$shopSales['count'],
        ];
    }

    /**
     * Sales Summary for Dashboard Cards
     */
    private function getSalesSummary(string $startDate, string $endDate): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $wh = Database::fetchOne(
            "SELECT COUNT(id) as orders, COALESCE(SUM(total_amount), 0) as revenue
             FROM purchase_orders WHERE concern_id = ? AND created_at BETWEEN ? AND ? AND (customer_id IS NOT NULL OR warehouse_supplier_id IS NULL)",
            [$concernId, $startDate, $endDate]
        );
        $pos = Database::fetchOne(
            "SELECT COUNT(id) as sales, COALESCE(SUM(total_amount), 0) as revenue
             FROM sales WHERE concern_id = ? AND created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );

        $totRevenue = (float)$wh['revenue'] + (float)$pos['revenue'];
        $totCount = (int)$wh['orders'] + (int)$pos['sales'];
        $avgOrder = $totCount > 0 ? round($totRevenue / $totCount, 2) : 0;

        return [
            'total_revenue' => $totRevenue,
            'total_orders'  => $totCount,
            'avg_order_val' => $avgOrder,
            'wh_revenue'    => (float)$wh['revenue'],
            'pos_revenue'   => (float)$pos['revenue'],
        ];
    }

    /**
     * Production Summary for Dashboard Cards
     */
    private function getProductionSummary(string $startDate, string $endDate): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $prog = Database::fetchOne(
            "SELECT COUNT(pp.id) as count, COALESCE(SUM(pp.planned_quantity), 0) as planned, COALESCE(SUM(pp.completed_quantity), 0) as completed
             FROM production_programs pp
             JOIN production_units pu ON pu.id = pp.production_unit_id
             WHERE pu.concern_id = ? AND (pp.created_at BETWEEN ? AND ? OR pp.start_date BETWEEN ? AND ?)",
            [$concernId, $startDate, $endDate, $startDate, $endDate]
        );
        $cutting = Database::fetchOne(
            "SELECT COALESCE(SUM(cut_qty), 0) as cut, COALESCE(SUM(wastage_weight), 0) as waste_kg
             FROM cutting_batches cb
             JOIN production_units pu ON pu.id = cb.production_unit_id
             WHERE pu.concern_id = ? AND cb.created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );
        $qc = Database::fetchOne(
            "SELECT COALESCE(SUM(passed_qty), 0) as passed, COALESCE(SUM(rejected_qty), 0) as rejected
             FROM qc_batches qb
             JOIN production_programs pp ON pp.id = qb.program_id
             JOIN production_units pu ON pu.id = pp.production_unit_id
             WHERE pu.concern_id = ? AND qb.created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );

        $totInspected = (int)$qc['passed'] + (int)$qc['rejected'];
        $passRate = $totInspected > 0 ? round(($qc['passed'] / $totInspected) * 100, 1) : 100;

        return [
            'programs_count'  => (int)$prog['count'],
            'planned_qty'     => (int)$prog['planned'],
            'completed_qty'   => (int)$prog['completed'],
            'cut_qty'         => (int)$cutting['cut'],
            'cut_wastage_kg'  => (float)$cutting['waste_kg'],
            'qc_passed'       => (int)$qc['passed'],
            'qc_rejected'     => (int)$qc['rejected'],
            'qc_pass_rate'    => $passRate,
        ];
    }

    /**
     * Warehouse Summary for Dashboard Cards
     */
    private function getWarehouseSummary(string $startDate, string $endDate): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $alloc = Database::fetchOne(
            "SELECT COUNT(ai.id) as count, COALESCE(SUM(COALESCE(ai.dispatched_qty, ai.approved_qty, ai.requested_qty, 0)), 0) as total_qty
             FROM allocation_items ai
             JOIN warehouse_allocations wa ON ai.allocation_id = wa.id
             JOIN warehouses w ON w.id = wa.warehouse_id
             WHERE w.concern_id = ? AND wa.created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );
        $damages = Database::fetchOne(
            "SELECT COUNT(wd.id) as count, COALESCE(SUM(wd.quantity), 0) as total_qty, COALESCE(SUM(wd.total_loss), 0) as total_loss
             FROM warehouse_damages wd
             JOIN warehouses w ON w.id = wd.warehouse_id
             WHERE w.concern_id = ? AND wd.created_at BETWEEN ? AND ?",
            [$concernId, $startDate, $endDate]
        );

        return [
            'allocated_qty'  => (int)$alloc['total_qty'],
            'damages_count'  => (int)$damages['count'],
            'damaged_pieces' => (int)$damages['total_qty'],
            'damage_loss'    => (float)$damages['total_loss'],
        ];
    }

    /**
     * Real-time Inventory Summary for Valuation
     */
    private function getInventorySummary(): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return [
                'wh_qty' => 0, 'wh_cost_val' => 0, 'wh_selling_val' => 0,
                'shop_qty' => 0, 'shop_selling_val' => 0, 'total_stock_qty' => 0,
                'total_valuation' => 0, 'damaged_qty' => 0,
            ];
        }
        $whStock = Database::fetchOne(
            "SELECT COALESCE(SUM(sb.quantity), 0) as total_qty,
                    COALESCE(SUM(sb.quantity * COALESCE(pv.purchase_price, pv.selling_price, 0)), 0) as cost_val,
                    COALESCE(SUM(sb.quantity * COALESCE(pv.selling_price, pv.mrp, 0)), 0) as selling_val
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             JOIN warehouses w ON w.id = sb.location_id AND w.concern_id = sb.concern_id
             WHERE sb.stock_state = 'AVAILABLE' AND sb.location_type = 'WAREHOUSE' AND sb.concern_id = ?",
            [$concernId]
        );
        $shopStock = Database::fetchOne(
            "SELECT COALESCE(SUM(sb.quantity), 0) as total_qty,
                    COALESCE(SUM(sb.quantity * COALESCE(pv.selling_price, pv.mrp, 0)), 0) as selling_val
             FROM stock_balances sb
             JOIN product_variants pv ON sb.variant_id = pv.id
             JOIN shops sh ON sh.id = sb.location_id AND sh.concern_id = sb.concern_id
             WHERE sb.stock_state = 'AVAILABLE' AND sb.location_type = 'SHOP' AND sb.concern_id = ?",
            [$concernId]
        );
        $damagedStock = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as total_qty,
                    COALESCE(SUM(quantity * 300), 0) as loss_val
             FROM stock_balances sb
             WHERE sb.stock_state = 'DAMAGED' AND sb.concern_id = ?",
            [$concernId]
        );

        return [
            'wh_qty'          => (int)$whStock['total_qty'],
            'wh_cost_val'     => (float)$whStock['cost_val'],
            'wh_selling_val'  => (float)$whStock['selling_val'],
            'shop_qty'        => (int)$shopStock['total_qty'],
            'shop_selling_val'=> (float)$shopStock['selling_val'],
            'total_stock_qty' => (int)$whStock['total_qty'] + (int)$shopStock['total_qty'],
            'total_valuation' => (float)$whStock['selling_val'] + (float)$shopStock['selling_val'],
            'damaged_qty'     => (int)$damagedStock['total_qty'],
        ];
    }

    /**
     * 12-Month Profitability & Revenue Trend for Year Charts
     */
    private function get12MonthTrend(int $year): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $mPadded = sprintf('%02d', $m);
            $sDate = "{$year}-{$mPadded}-01 00:00:00";
            $lDay = date('t', strtotime($sDate));
            $eDate = "{$year}-{$mPadded}-{$lDay} 23:59:59";
            $monthName = date('M', strtotime($sDate));

            $salesRev = Database::fetchOne(
                "SELECT (
                    COALESCE((SELECT SUM(total_amount) FROM purchase_orders WHERE concern_id = ? AND created_at BETWEEN ? AND ? AND (customer_id IS NOT NULL OR warehouse_supplier_id IS NULL)), 0) +
                    COALESCE((SELECT SUM(total_amount) FROM sales WHERE concern_id = ? AND created_at BETWEEN ? AND ?), 0) +
                    COALESCE((SELECT SUM(total_amount) FROM wastage_sales WHERE concern_id = ? AND sale_date BETWEEN ? AND ?), 0) +
                    COALESCE((SELECT SUM(total_amount) FROM stitching_wastage_sales WHERE concern_id = ? AND sale_date BETWEEN ? AND ?), 0)
                ) as total_revenue",
                [$concernId, $sDate, $eDate, $concernId, $sDate, $eDate, $concernId, $sDate, $eDate, $concernId, $sDate, $eDate]
            );

            $expenseVal = Database::fetchOne(
                "SELECT (
                    COALESCE((SELECT SUM(total_cost) FROM production_expenses WHERE concern_id = ? AND expense_date BETWEEN ? AND ?), 0) +
                    COALESCE((SELECT SUM(amount) FROM cash_expenses WHERE concern_id = ? AND created_at BETWEEN ? AND ?), 0) +
                    COALESCE((SELECT SUM(total_cost) FROM expenses WHERE concern_id = ? AND (expense_date BETWEEN ? AND ? OR created_at BETWEEN ? AND ?)), 0)
                ) as total_expense",
                [$concernId, $sDate, $eDate, $concernId, $sDate, $eDate, $concernId, $sDate, $eDate, $sDate, $eDate]
            );

            $lossVal = Database::fetchOne(
                "SELECT (
                    COALESCE((SELECT SUM(total_loss) FROM warehouse_damages WHERE warehouse_id IN (SELECT id FROM warehouses WHERE concern_id = ?) AND created_at BETWEEN ? AND ?), 0) +
                    COALESCE((SELECT SUM(loss_amount) FROM roll_damages WHERE created_at BETWEEN ? AND ?), 0)
                ) as total_loss",
                [$concernId, $sDate, $eDate, $sDate, $eDate]
            );

            $rev = (float)($salesRev['total_revenue'] ?? 0);
            $exp = (float)($expenseVal['total_expense'] ?? 0);
            $loss = (float)($lossVal['total_loss'] ?? 0);
            $net = $rev - ($exp + $loss);

            $months[] = [
                'month_num'  => $m,
                'month_name' => $monthName,
                'revenue'    => $rev,
                'expense'    => $exp,
                'loss'       => $loss,
                'net_profit' => $net,
            ];
        }
        return $months;
    }

    /**
     * Line Items for P&L Statements
     */
    private function getRevenueLineItems(string $startDate, string $endDate): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $whSales = Database::fetchAll(
            "SELECT po.id, po.po_number as ref_no, 'Direct Customer Sale' as source, c.name as customer_name,
                    po.payment_method, po.total_amount, po.discount_amount, po.tax_amount, po.created_at
             FROM purchase_orders po
             LEFT JOIN customers c ON po.customer_id = c.id
             WHERE po.concern_id = ? AND po.created_at BETWEEN ? AND ?
               AND (po.customer_id IS NOT NULL OR po.warehouse_supplier_id IS NULL)
             ORDER BY po.id DESC LIMIT 50",
            [$concernId, $startDate, $endDate]
        );

        $posSales = Database::fetchAll(
            "SELECT s.id, s.invoice_number as ref_no, 'Retail Shop POS' as source, COALESCE(c.name, s.customer_name, 'Walk-in Customer') as customer_name,
                    s.payment_mode as payment_method, s.total_amount, s.discount_amount, s.tax_amount, s.created_at
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.concern_id = ? AND s.created_at BETWEEN ? AND ?
             ORDER BY s.id DESC LIMIT 50",
            [$concernId, $startDate, $endDate]
        );

        $wastage = Database::fetchAll(
            "SELECT ws.id, ws.sale_number as ref_no, 'Cutting Wastage Sale' as source, ws.buyer_name as customer_name,
                    ws.payment_method, ws.total_amount, 0 as discount_amount, 0 as tax_amount, ws.sale_date as created_at
             FROM wastage_sales ws
             WHERE ws.concern_id = ? AND (ws.sale_date BETWEEN ? AND ? OR ws.created_at BETWEEN ? AND ?)
             ORDER BY ws.id DESC LIMIT 50",
            [$concernId, $startDate, $endDate, $startDate, $endDate]
        );

        return array_merge($whSales, $posSales, $wastage);
    }

    private function getCostLineItems(string $startDate, string $endDate): array {
        $concernId = ConcernTransactionService::activeConcernId();
        return Database::fetchAll(
            "SELECT pb.id, pb.batch_number as ref_no, p.name as product_name, pb.total_qty, pb.cost_per_piece, pb.total_cost, pb.created_at
             FROM production_batches pb
             LEFT JOIN production_programs pp ON pb.program_id = pp.id
             JOIN production_units pu ON pp.production_unit_id = pu.id
             LEFT JOIN products p ON pp.product_id = p.id
             WHERE pu.concern_id = ? AND pb.created_at BETWEEN ? AND ?
             ORDER BY pb.id DESC LIMIT 50",
            [$concernId, $startDate, $endDate]
        );
    }

    private function getExpenseLineItems(string $startDate, string $endDate): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $prodExp = Database::fetchAll(
            "SELECT pe.id, pe.expense_number as ref_no, pe.production_unit as facility, pe.category, pe.item_name,
                    pe.total_cost as amount, pe.payment_status, pe.expense_date
             FROM production_expenses pe
             WHERE pe.concern_id = ? AND (pe.expense_date BETWEEN ? AND ? OR pe.created_at BETWEEN ? AND ?)
             ORDER BY pe.id DESC LIMIT 30",
            [$concernId, $startDate, $endDate, $startDate, $endDate]
        );

        $modExp = Database::fetchAll(
            "SELECT e.id, e.expense_number as ref_no, e.location_name as facility, e.category, e.item_name,
                    e.total_cost as amount, e.payment_status, e.expense_date
             FROM expenses e
             WHERE e.concern_id = ? AND (e.expense_date BETWEEN ? AND ? OR e.created_at BETWEEN ? AND ?)
             ORDER BY e.id DESC LIMIT 30",
            [$concernId, $startDate, $endDate, $startDate, $endDate]
        );

        return array_merge($prodExp, $modExp);
    }

    private function getLossLineItems(string $startDate, string $endDate): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $whLosses = Database::fetchAll(
            "SELECT wd.id, wd.damage_number as ref_no, 'Warehouse Damage' as loss_type, p.name as product_name,
                    wd.quantity, wd.cost_per_unit, wd.total_loss as amount, wd.status, wd.damage_reason as reason, wd.created_at
             FROM warehouse_damages wd
             JOIN warehouses w ON w.id = wd.warehouse_id
             LEFT JOIN product_variants pv ON wd.variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE w.concern_id = ? AND wd.created_at BETWEEN ? AND ?
             ORDER BY wd.id DESC LIMIT 50",
            [$concernId, $startDate, $endDate]
        );

        $rollLosses = Database::fetchAll(
            "SELECT rd.id, rd.damage_number as ref_no, 'Fabric Roll Damage' as loss_type, 'Raw Fabric' as product_name,
                    rd.damaged_quantity as quantity, 0 as cost_per_unit, rd.loss_amount as amount, rd.status, rd.damage_reason as reason, rd.created_at
             FROM roll_damages rd
             WHERE rd.concern_id = ? AND rd.created_at BETWEEN ? AND ?
             ORDER BY rd.id DESC LIMIT 50",
            [$concernId, $startDate, $endDate]
        );

        return array_merge($whLosses, $rollLosses);
    }

    /**
     * Detailed Sales Report Builder — Multi-Channel with Per-Channel Breakdown
     */
    private function getDetailedSalesReport(string $startDate, string $endDate, string $channel = 'all', string $paymentMethod = 'all'): array {
        $concernId = ConcernTransactionService::activeConcernId();
        // ─── 1. Warehouse Direct Customer Sales (purchase_orders with customers) ───
        $sql1 = "SELECT po.id, po.po_number as invoice_no, 'Warehouse POS' as channel,
                        c.name as customer_name, c.phone as customer_phone,
                        po.total_amount, po.discount_amount, po.tax_amount, po.paid_amount,
                        po.payment_method, po.payment_status, po.status, po.created_at,
                        po.warehouse_id, w.name as location_name
                 FROM purchase_orders po
                 LEFT JOIN customers c ON po.customer_id = c.id
                 LEFT JOIN warehouses w ON po.warehouse_id = w.id
                 WHERE po.concern_id = ? AND po.created_at BETWEEN ? AND ?
                   AND (po.customer_id IS NOT NULL OR po.warehouse_supplier_id IS NULL)";
        $params1 = [$concernId, $startDate, $endDate];
        if ($paymentMethod !== 'all') {
            $sql1 .= " AND po.payment_method = ?";
            $params1[] = $paymentMethod;
        }
        $sql1 .= " ORDER BY po.id DESC";
        $whOrders = Database::fetchAll($sql1, $params1);

        // ─── 2. Shop POS Sales (all shops combined, with shop_id) ───
        $sql2 = "SELECT s.id, s.invoice_number as invoice_no, 
                        CASE 
                            WHEN UPPER(s.sale_type) = 'WHATSAPP' OR UPPER(s.sales_channel) = 'WHATSAPP' THEN 'WhatsApp'
                            WHEN UPPER(s.sale_type) = 'WEBSITE' OR UPPER(s.sales_channel) = 'WEBSITE' THEN 'Website'
                            ELSE 'Retail POS'
                        END as channel,
                        COALESCE(c.name, s.customer_name, 'Walk-in') as customer_name,
                        COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                        s.total_amount, s.discount_amount, s.tax_amount, s.paid_amount,
                        s.payment_mode as payment_method, s.payment_status, s.status, s.created_at,
                        s.shop_id, COALESCE(sh.name, 'Unknown Shop') as location_name,
                        s.sale_type, s.sales_channel
                 FROM sales s
                 LEFT JOIN customers c ON s.customer_id = c.id
                 LEFT JOIN shops sh ON s.shop_id = sh.id
                 WHERE s.concern_id = ? AND s.created_at BETWEEN ? AND ?";
        $params2 = [$concernId, $startDate, $endDate];
        if ($paymentMethod !== 'all') {
            $sql2 .= " AND s.payment_mode = ?";
            $params2[] = $paymentMethod;
        }
        $sql2 .= " ORDER BY s.id DESC";
        $shopSales = Database::fetchAll($sql2, $params2);

        // ─── Separate by channel ───
        $whatsappOrders = [];
        $websiteOrders = [];
        $retailPOSSales = [];

        foreach ($shopSales as $row) {
            $ch = $row['channel'];
            if ($ch === 'WhatsApp') {
                $whatsappOrders[] = $row;
            } elseif ($ch === 'Website') {
                $websiteOrders[] = $row;
            } else {
                $retailPOSSales[] = $row;
            }
        }

        // ─── Build per-shop breakdown from retail POS ───
        $shopBreakdown = [];
        foreach ($retailPOSSales as $row) {
            $sid = (int)($row['shop_id'] ?? 0);
            $sname = $row['location_name'] ?? 'Unknown Shop';
            if (!isset($shopBreakdown[$sid])) {
                $shopBreakdown[$sid] = [
                    'shop_name'    => $sname,
                    'orders'       => [],
                    'total_amount' => 0, 'total_paid' => 0, 'total_discount' => 0, 'total_tax' => 0,
                ];
            }
            $shopBreakdown[$sid]['orders'][] = $row;
            $shopBreakdown[$sid]['total_amount'] += (float)$row['total_amount'];
            $shopBreakdown[$sid]['total_paid'] += (float)$row['paid_amount'];
            $shopBreakdown[$sid]['total_discount'] += (float)$row['discount_amount'];
            $shopBreakdown[$sid]['total_tax'] += (float)$row['tax_amount'];
        }

        // ─── Build per-warehouse breakdown from warehouse POS ───
        $warehouseBreakdown = [];
        foreach ($whOrders as $row) {
            $wid = (int)($row['warehouse_id'] ?? 0);
            $wname = $row['location_name'] ?? 'Unknown Warehouse';
            if (!isset($warehouseBreakdown[$wid])) {
                $warehouseBreakdown[$wid] = [
                    'warehouse_name' => $wname,
                    'orders'         => [],
                    'total_amount'   => 0, 'total_paid' => 0, 'total_discount' => 0, 'total_tax' => 0,
                ];
            }
            $warehouseBreakdown[$wid]['orders'][] = $row;
            $warehouseBreakdown[$wid]['total_amount'] += (float)$row['total_amount'];
            $warehouseBreakdown[$wid]['total_paid'] += (float)$row['paid_amount'];
            $warehouseBreakdown[$wid]['total_discount'] += (float)$row['discount_amount'];
            $warehouseBreakdown[$wid]['total_tax'] += (float)$row['tax_amount'];
        }

        // ─── Merge all for grand totals ───
        $merged = array_merge($whOrders, $shopSales);
        usort($merged, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        $totAmount = array_sum(array_column($merged, 'total_amount'));
        $totDiscount = array_sum(array_column($merged, 'discount_amount'));
        $totTax = array_sum(array_column($merged, 'tax_amount'));
        $totPaid = array_sum(array_column($merged, 'paid_amount'));
        $totPending = max(0, $totAmount - $totPaid);

        // Payment Mode Breakdown
        $modes = [];
        foreach ($merged as $row) {
            $pm = strtoupper($row['payment_method'] ?? 'OTHER');
            if (!isset($modes[$pm])) $modes[$pm] = ['count' => 0, 'amount' => 0];
            $modes[$pm]['count']++;
            $modes[$pm]['amount'] += (float)$row['total_amount'];
        }

        // ─── Helper to aggregate channel totals ───
        $aggregateChannel = function(array $rows) {
            return [
                'orders'         => $rows,
                'total_amount'   => array_sum(array_column($rows, 'total_amount')),
                'total_paid'     => array_sum(array_column($rows, 'paid_amount')),
                'total_discount' => array_sum(array_column($rows, 'discount_amount')),
                'total_tax'      => array_sum(array_column($rows, 'tax_amount')),
                'total_pending'  => max(0, array_sum(array_column($rows, 'total_amount')) - array_sum(array_column($rows, 'paid_amount'))),
                'count'          => count($rows),
            ];
        };

        return [
            'invoices'       => $merged,
            'total_amount'   => $totAmount,
            'total_discount' => $totDiscount,
            'total_tax'      => $totTax,
            'total_paid'     => $totPaid,
            'total_pending'  => $totPending,
            'total_orders'   => count($merged),
            'payment_modes'  => $modes,
            // Per-channel breakdowns
            'channels'       => [
                'warehouse_pos'  => $aggregateChannel($whOrders),
                'shop_pos'       => $aggregateChannel($retailPOSSales),
                'whatsapp'       => $aggregateChannel($whatsappOrders),
                'website'        => $aggregateChannel($websiteOrders),
            ],
            'shop_breakdown'      => $shopBreakdown,
            'warehouse_breakdown' => $warehouseBreakdown,
        ];
    }

    /**
     * Detailed Website Report Builder
     */
    private function getDetailedWebsiteReport(string $startDate, string $endDate, array $filters = []): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $where = ["s.concern_id = ?", "(s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE' OR s.notes LIKE '%Website%' OR s.invoice_number LIKE 'WEB-%')"];
        $params = [$concernId];
        $where[] = "s.created_at BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;

        if (!empty($filters['search'])) {
            $where[] = "(s.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ?)";
            $sw = '%' . $filters['search'] . '%';
            $params[] = $sw; $params[] = $sw; $params[] = $sw; $params[] = $sw; $params[] = $sw;
        }
        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $where[] = "s.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['payment_mode']) && $filters['payment_mode'] !== 'all') {
            $where[] = "s.payment_mode = ?";
            $params[] = $filters['payment_mode'];
        }
        if (!empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $where[] = "s.status = ?";
            $params[] = $filters['order_status'];
        }

        $whereClause = implode(' AND ', $where);

        $orders = Database::fetchAll(
            "SELECT s.*, 
                    COALESCE(c.name, s.customer_name, 'Website Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                    COALESCE(c.city, '-') as customer_city,
                    c.email as customer_email,
                    (SELECT COUNT(DISTINCT variant_id) FROM sale_items WHERE sale_id = s.id) as distinct_variants_count,
                    (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_pcs,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_items_count
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE {$whereClause}
             ORDER BY s.created_at DESC",
            $params
        );

        $totAmount = array_sum(array_column($orders, 'total_amount'));
        $totPaid = array_sum(array_column($orders, 'paid_amount'));
        $totDiscount = array_sum(array_column($orders, 'discount_amount'));
        $totTax = array_sum(array_map(fn($r) => (float)($r['gst_amount'] ?? $r['tax_amount'] ?? 0), $orders));
        $totPcs = array_sum(array_column($orders, 'total_pcs'));
        $totPending = max(0, $totAmount - $totPaid);
        $orderCount = count($orders);
        $avgOrderValue = $orderCount > 0 ? round($totAmount / $orderCount, 2) : 0;

        // Payment Mode & Order Status Breakdown
        $paymentModes = [];
        $orderStatuses = [];
        foreach ($orders as $row) {
            $pm = strtoupper($row['payment_mode'] ?? $row['payment_method'] ?? 'ONLINE');
            if (!isset($paymentModes[$pm])) $paymentModes[$pm] = ['count' => 0, 'amount' => 0];
            $paymentModes[$pm]['count']++;
            $paymentModes[$pm]['amount'] += (float)$row['total_amount'];

            $os = strtoupper($row['status'] ?? 'DELIVERED');
            if (!isset($orderStatuses[$os])) $orderStatuses[$os] = 0;
            $orderStatuses[$os]++;
        }

        // Top 10 Selling Products in Website
        $topProducts = Database::fetchAll(
            "SELECT pv.id as variant_id, pv.sku, p.name as product_name, p.code as product_code,
                    cat.name as category_name, sz.name as size_name, clr.name as color_name,
                    SUM(si.quantity) as total_sold_qty,
                    SUM(si.total_amount) as total_revenue,
                    COUNT(DISTINCT s.id) as total_orders
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE s.concern_id = ?
               AND (s.sales_channel = 'WEBSITE' OR s.sale_type = 'WEBSITE' OR s.notes LIKE '%Website%' OR s.invoice_number LIKE 'WEB-%')
               AND s.created_at BETWEEN ? AND ?
             GROUP BY pv.id, pv.sku, p.name, p.code, cat.name, sz.name, clr.name
             ORDER BY total_sold_qty DESC, total_revenue DESC
             LIMIT 10",
            [$concernId, $startDate, $endDate]
        );

        // Fetch Line Items for expandable table
        $saleIds = array_column($orders, 'id');
        $itemsMap = [];
        if (!empty($saleIds)) {
            $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
            $rawItems = Database::fetchAll(
                "SELECT si.*, pv.sku, p.name as product_name, p.code as product_code,
                        sz.name as size_name, clr.name as color_name
                 FROM sale_items si
                 JOIN product_variants pv ON si.variant_id = pv.id
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN sizes sz ON pv.size_id = sz.id
                 LEFT JOIN colors clr ON pv.color_id = clr.id
                 WHERE si.sale_id IN ($placeholders)
                 ORDER BY si.id ASC",
                $saleIds
            );
            foreach ($rawItems as $it) {
                $itemsMap[$it['sale_id']][] = $it;
            }
        }

        // Calculate channel operating expense summary
        $channelExpenses = ExpenseService::getSummary('WEBSITE', null, $startDate, $endDate);

        return [
            'orders'          => $orders,
            'itemsMap'        => $itemsMap,
            'topProducts'     => $topProducts,
            'paymentModes'    => $paymentModes,
            'orderStatuses'   => $orderStatuses,
            'channelExpenses' => $channelExpenses,
            'totals'          => [
                'total_amount'    => $totAmount,
                'total_paid'      => $totPaid,
                'total_pending'   => $totPending,
                'total_discount'  => $totDiscount,
                'total_tax'       => $totTax,
                'total_pcs'       => $totPcs,
                'total_orders'    => $orderCount,
                'avg_order_value' => $avgOrderValue,
                'channel_expenses'=> (float)($channelExpenses['total_amount'] ?? 0),
                'net_channel_margin' => max(0, $totAmount - (float)($channelExpenses['total_amount'] ?? 0)),
            ],
        ];
    }

    /**
     * Detailed WhatsApp Report Builder
     */
    private function getDetailedWhatsappReport(string $startDate, string $endDate, array $filters = []): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $where = ["s.concern_id = ?", "(s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%' OR s.invoice_number LIKE 'WA-%')"];
        $params = [$concernId];
        $where[] = "s.created_at BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;

        if (!empty($filters['search'])) {
            $where[] = "(s.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ? OR s.biller_name LIKE ? OR s.employee_name LIKE ?)";
            $sw = '%' . $filters['search'] . '%';
            $params[] = $sw; $params[] = $sw; $params[] = $sw; $params[] = $sw; $params[] = $sw; $params[] = $sw; $params[] = $sw;
        }
        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $where[] = "s.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['payment_mode']) && $filters['payment_mode'] !== 'all') {
            $where[] = "s.payment_mode = ?";
            $params[] = $filters['payment_mode'];
        }
        if (!empty($filters['order_status']) && $filters['order_status'] !== 'all') {
            $where[] = "s.status = ?";
            $params[] = $filters['order_status'];
        }
        if (!empty($filters['biller_name']) && $filters['biller_name'] !== 'all') {
            $where[] = "(s.biller_name = ? OR u.full_name = ?)";
            $params[] = $filters['biller_name'];
            $params[] = $filters['biller_name'];
        }

        $whereClause = implode(' AND ', $where);

        $orders = Database::fetchAll(
            "SELECT s.*, 
                    COALESCE(c.name, s.customer_name, 'WhatsApp Customer') as customer_name,
                    COALESCE(c.phone, s.customer_phone, '-') as customer_phone,
                    COALESCE(c.city, '-') as customer_city,
                    c.email as customer_email,
                    COALESCE(NULLIF(s.biller_name, ''), NULLIF(u.full_name, ''), 'WhatsApp Sales Agent') as biller_name,
                    (SELECT COUNT(DISTINCT variant_id) FROM sale_items WHERE sale_id = s.id) as distinct_variants_count,
                    (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_pcs,
                    (SELECT COALESCE(SUM(quantity), 0) FROM sale_items WHERE sale_id = s.id) as total_items_count
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN users u ON s.biller_id = u.id OR s.billing_staff_id = u.id
             WHERE {$whereClause}
             ORDER BY s.created_at DESC",
            $params
        );

        $totAmount = array_sum(array_column($orders, 'total_amount'));
        $totPaid = array_sum(array_column($orders, 'paid_amount'));
        $totDiscount = array_sum(array_column($orders, 'discount_amount'));
        $totTax = array_sum(array_map(fn($r) => (float)($r['gst_amount'] ?? $r['tax_amount'] ?? 0), $orders));
        $totPcs = array_sum(array_column($orders, 'total_pcs'));
        $totPending = max(0, $totAmount - $totPaid);
        $orderCount = count($orders);
        $avgOrderValue = $orderCount > 0 ? round($totAmount / $orderCount, 2) : 0;

        // Payment Mode & Order Status Breakdown
        $paymentModes = [];
        $orderStatuses = [];
        foreach ($orders as $row) {
            $pm = strtoupper($row['payment_mode'] ?? $row['payment_method'] ?? 'UPI');
            if (!isset($paymentModes[$pm])) $paymentModes[$pm] = ['count' => 0, 'amount' => 0];
            $paymentModes[$pm]['count']++;
            $paymentModes[$pm]['amount'] += (float)$row['total_amount'];

            $os = strtoupper($row['status'] ?? 'DELIVERED');
            if (!isset($orderStatuses[$os])) $orderStatuses[$os] = 0;
            $orderStatuses[$os]++;
        }

        // Top 10 Selling Products in WhatsApp
        $topProducts = Database::fetchAll(
            "SELECT pv.id as variant_id, pv.sku, p.name as product_name, p.code as product_code,
                    cat.name as category_name, sz.name as size_name, clr.name as color_name,
                    SUM(si.quantity) as total_sold_qty,
                    SUM(si.total_amount) as total_revenue,
                    COUNT(DISTINCT s.id) as total_orders
             FROM sale_items si
             JOIN sales s ON si.sale_id = s.id
             JOIN product_variants pv ON si.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN sizes sz ON pv.size_id = sz.id
             LEFT JOIN colors clr ON pv.color_id = clr.id
             WHERE s.concern_id = ?
               AND (s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%' OR s.invoice_number LIKE 'WA-%')
               AND s.created_at BETWEEN ? AND ?
             GROUP BY pv.id, pv.sku, p.name, p.code, cat.name, sz.name, clr.name
             ORDER BY total_sold_qty DESC, total_revenue DESC
             LIMIT 10",
            [$concernId, $startDate, $endDate]
        );

        // Staff / Biller Breakdown
        $staffBreakdown = Database::fetchAll(
            "SELECT COALESCE(NULLIF(s.biller_name, ''), NULLIF(u.full_name, ''), 'WhatsApp Sales Agent') as staff_name,
                    COUNT(s.id) as total_orders,
                    SUM(s.total_amount) as total_sales,
                    SUM(s.paid_amount) as total_collected,
                    COALESCE(SUM(si_agg.total_qty), 0) as total_pieces
             FROM sales s
             LEFT JOIN users u ON s.biller_id = u.id OR s.billing_staff_id = u.id
             LEFT JOIN (SELECT sale_id, SUM(quantity) as total_qty FROM sale_items GROUP BY sale_id) si_agg ON s.id = si_agg.sale_id
             WHERE s.concern_id = ?
               AND (s.sales_channel = 'WHATSAPP' OR s.sale_type = 'WHATSAPP' OR s.notes LIKE '%WhatsApp%' OR s.invoice_number LIKE 'WA-%')
               AND s.created_at BETWEEN ? AND ?
             GROUP BY staff_name
             ORDER BY total_sales DESC",
            [$concernId, $startDate, $endDate]
        );

        // Fetch Line Items for expandable table
        $saleIds = array_column($orders, 'id');
        $itemsMap = [];
        if (!empty($saleIds)) {
            $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
            $rawItems = Database::fetchAll(
                "SELECT si.*, pv.sku, p.name as product_name, p.code as product_code,
                        sz.name as size_name, clr.name as color_name
                 FROM sale_items si
                 JOIN product_variants pv ON si.variant_id = pv.id
                 JOIN products p ON pv.product_id = p.id
                 LEFT JOIN sizes sz ON pv.size_id = sz.id
                 LEFT JOIN colors clr ON pv.color_id = clr.id
                 WHERE si.sale_id IN ($placeholders)
                 ORDER BY si.id ASC",
                $saleIds
            );
            foreach ($rawItems as $it) {
                $itemsMap[$it['sale_id']][] = $it;
            }
        }

        // Calculate WhatsApp channel operating expense summary
        $channelExpenses = ExpenseService::getSummary('WHATSAPP', null, $startDate, $endDate);

        return [
            'orders'          => $orders,
            'itemsMap'        => $itemsMap,
            'topProducts'     => $topProducts,
            'staffBreakdown'  => $staffBreakdown,
            'paymentModes'    => $paymentModes,
            'orderStatuses'   => $orderStatuses,
            'channelExpenses' => $channelExpenses,
            'totals'          => [
                'total_amount'    => $totAmount,
                'total_paid'      => $totPaid,
                'total_pending'   => $totPending,
                'total_discount'  => $totDiscount,
                'total_tax'       => $totTax,
                'total_pcs'       => $totPcs,
                'total_orders'    => $orderCount,
                'avg_order_value' => $avgOrderValue,
                'channel_expenses'=> (float)($channelExpenses['total_amount'] ?? 0),
                'net_channel_margin' => max(0, $totAmount - (float)($channelExpenses['total_amount'] ?? 0)),
            ],
        ];
    }

    /**
     * Detailed Production Report Builder
     */
    private function getDetailedProductionReport(string $startDate, string $endDate, int $unitId = 0): array {
        $concernId = ConcernTransactionService::activeConcernId();
        if ($unitId > 0) {
            ConcernTransactionService::assertFacility('PRODUCTION', $unitId);
        }
        $pSql = "SELECT pp.*, p.name as product_name, p.code as product_code, pu.name as unit_name
                 FROM production_programs pp
                 LEFT JOIN products p ON pp.product_id = p.id
                 LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
                 WHERE pu.concern_id = ?
                   AND (pp.created_at BETWEEN ? AND ? OR pp.start_date BETWEEN ? AND ?)";
        $pParams = [$concernId, $startDate, $endDate, $startDate, $endDate];
        if ($unitId > 0) {
            $pSql .= " AND pp.production_unit_id = ?";
            $pParams[] = $unitId;
        }
        $pSql .= " ORDER BY pp.id DESC";
        $programs = Database::fetchAll($pSql, $pParams);

        $cuttingSql = "SELECT cb.*, pp.program_number, pu.name as unit_name
             FROM cutting_batches cb
             LEFT JOIN production_programs pp ON cb.program_id = pp.id
             LEFT JOIN production_units pu ON cb.production_unit_id = pu.id
             WHERE pu.concern_id = ? AND cb.created_at BETWEEN ? AND ?";
        $cuttingParams = [$concernId, $startDate, $endDate];
        if ($unitId > 0) {
            $cuttingSql .= " AND cb.production_unit_id = ?";
            $cuttingParams[] = $unitId;
        }
        $cuttingSql .= " ORDER BY cb.id DESC";
        $cutting = Database::fetchAll($cuttingSql, $cuttingParams);

        $stitchingSql = "SELECT sb.*, pp.program_number, pu.name as unit_name
             FROM stitching_batches sb
             LEFT JOIN production_programs pp ON sb.program_id = pp.id
             LEFT JOIN production_units pu ON sb.production_unit_id = pu.id
             WHERE pu.concern_id = ? AND sb.created_at BETWEEN ? AND ?";
        $stitchingParams = [$concernId, $startDate, $endDate];
        if ($unitId > 0) {
            $stitchingSql .= " AND sb.production_unit_id = ?";
            $stitchingParams[] = $unitId;
        }
        $stitchingSql .= " ORDER BY sb.id DESC";
        $stitching = Database::fetchAll($stitchingSql, $stitchingParams);

        $qcSql = "SELECT qb.*, pp.program_number
             FROM qc_batches qb
             LEFT JOIN production_programs pp ON qb.program_id = pp.id
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             WHERE pu.concern_id = ? AND qb.created_at BETWEEN ? AND ?";
        $qcParams = [$concernId, $startDate, $endDate];
        if ($unitId > 0) {
            $qcSql .= " AND pp.production_unit_id = ?";
            $qcParams[] = $unitId;
        }
        $qcSql .= " ORDER BY qb.id DESC";
        $qc = Database::fetchAll($qcSql, $qcParams);

        $expenseSql = "SELECT * FROM production_expenses
             WHERE concern_id = ? AND (expense_date BETWEEN ? AND ? OR created_at BETWEEN ? AND ?)";
        $expenseParams = [$concernId, $startDate, $endDate, $startDate, $endDate];
        if ($unitId > 0) {
            $expenseSql .= " AND production_unit_id = ?";
            $expenseParams[] = $unitId;
        }
        $expenseSql .= " ORDER BY id DESC";
        $expenses = Database::fetchAll($expenseSql, $expenseParams);

        $wastageSql = "SELECT * FROM wastage_sales
             WHERE concern_id = ? AND (sale_date BETWEEN ? AND ? OR created_at BETWEEN ? AND ?)";
        $wastageParams = [$concernId, $startDate, $endDate, $startDate, $endDate];
        if ($unitId > 0) {
            $wastageSql .= " AND production_unit_id = ?";
            $wastageParams[] = $unitId;
        }
        $wastageSql .= " ORDER BY id DESC";
        $wastageSales = Database::fetchAll($wastageSql, $wastageParams);

        return [
            'programs'      => $programs,
            'cutting'       => $cutting,
            'stitching'     => $stitching,
            'qc'            => $qc,
            'expenses'      => $expenses,
            'wastage_sales' => $wastageSales,
            'totals'        => [
                'planned_qty'     => array_sum(array_column($programs, 'planned_quantity')),
                'completed_qty'   => array_sum(array_column($programs, 'completed_quantity')),
                'cut_qty'         => array_sum(array_column($cutting, 'cut_qty')),
                'cutting_wastage' => array_sum(array_column($cutting, 'wastage_weight')),
                'stitched_qty'    => array_sum(array_column($stitching, 'stitched_qty')),
                'qc_passed'       => array_sum(array_column($qc, 'passed_qty')),
                'qc_rejected'     => array_sum(array_column($qc, 'rejected_qty')),
                'expense_total'   => array_sum(array_column($expenses, 'total_cost')),
                'wastage_revenue' => array_sum(array_column($wastageSales, 'total_amount')),
            ]
        ];
    }

    /**
     * Detailed Warehouse Report Builder
     */
    private function getDetailedWarehouseReport(string $startDate, string $endDate, int $warehouseId = 0): array {
        $concernId = ConcernTransactionService::activeConcernId();
        if ($warehouseId > 0) {
            ConcernTransactionService::assertFacility('WAREHOUSE', $warehouseId);
        }
        // Dispatches / Allocations
        $aSql = "SELECT wa.*, w.name as warehouse_name, s.name as shop_name,
                        COALESCE(ai_agg.item_count, 0) as item_count,
                        COALESCE(ai_agg.total_qty, 0) as total_qty
                 FROM warehouse_allocations wa
                 LEFT JOIN warehouses w ON wa.warehouse_id = w.id
                 LEFT JOIN shops s ON wa.shop_id = s.id
                 LEFT JOIN (
                     SELECT allocation_id, COUNT(id) as item_count, SUM(COALESCE(dispatched_qty, approved_qty, requested_qty, 0)) as total_qty
                     FROM allocation_items
                     GROUP BY allocation_id
                 ) ai_agg ON ai_agg.allocation_id = wa.id
                 WHERE w.concern_id = ? AND wa.created_at BETWEEN ? AND ?";
        $aParams = [$concernId, $startDate, $endDate];
        if ($warehouseId > 0) {
            $aSql .= " AND wa.warehouse_id = ?";
            $aParams[] = $warehouseId;
        }
        $aSql .= " ORDER BY wa.id DESC";
        $allocations = Database::fetchAll($aSql, $aParams);

        // Third-Party Supplier Purchases & Procurement to this Warehouse
        $pSql = "SELECT po.*, 
                        COALESCE(s.name, sel.name, 'Third-Party Vendor') as supplier_name, 
                        COALESCE(s.company, sel.company, 'External Vendor') as supplier_company,
                        w.name as warehouse_name,
                        COALESCE(pi_agg.item_count, 0) as item_count,
                        COALESCE(pi_agg.total_qty, 0) as total_qty
                 FROM purchase_orders po
                 LEFT JOIN warehouse_suppliers s ON po.warehouse_supplier_id = s.id
                 LEFT JOIN sellers sel ON po.seller_id = sel.id
                 LEFT JOIN warehouses w ON po.warehouse_id = w.id
                 LEFT JOIN (
                     SELECT purchase_order_id, COUNT(id) as item_count, SUM(quantity) as total_qty
                     FROM purchase_items
                     GROUP BY purchase_order_id
                 ) pi_agg ON pi_agg.purchase_order_id = po.id
                 WHERE po.concern_id = ? AND po.created_at BETWEEN ? AND ?
                   AND (po.warehouse_supplier_id IS NOT NULL OR po.seller_id IS NOT NULL)";
        $pParams = [$concernId, $startDate, $endDate];
        if ($warehouseId > 0) {
            $pSql .= " AND po.warehouse_id = ?";
            $pParams[] = $warehouseId;
        }
        $pSql .= " ORDER BY po.id DESC";
        $thirdPartyBuys = Database::fetchAll($pSql, $pParams);

        // Damages
        $dSql = "SELECT wd.*, w.name as warehouse_name, p.name as product_name, pv.sku
                 FROM warehouse_damages wd
                 LEFT JOIN warehouses w ON wd.warehouse_id = w.id
                 LEFT JOIN product_variants pv ON wd.variant_id = pv.id
                 LEFT JOIN products p ON pv.product_id = p.id
                 WHERE w.concern_id = ? AND wd.created_at BETWEEN ? AND ?";
        $dParams = [$concernId, $startDate, $endDate];
        if ($warehouseId > 0) {
            $dSql .= " AND wd.warehouse_id = ?";
            $dParams[] = $warehouseId;
        }
        $dSql .= " ORDER BY wd.id DESC";
        $damages = Database::fetchAll($dSql, $dParams);

        // Adjustments
        $adj = Database::fetchAll(
            "SELECT sa.*, w.name as warehouse_name, pv.sku, p.name as product_name
             FROM stock_adjustments sa
             LEFT JOIN warehouses w ON sa.warehouse_id = w.id
             LEFT JOIN product_variants pv ON sa.variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE w.concern_id = ? AND sa.created_at BETWEEN ? AND ?
             ORDER BY sa.id DESC",
            [$concernId, $startDate, $endDate]
        );

        return [
            'allocations'      => $allocations,
            'third_party_buys' => $thirdPartyBuys,
            'damages'          => $damages,
            'adjustments'      => $adj,
            'totals'           => [
                'dispatched_qty'   => array_sum(array_column($allocations, 'total_qty')),
                'purchased_spend'  => array_sum(array_column($thirdPartyBuys, 'total_amount')),
                'purchased_qty'    => array_sum(array_column($thirdPartyBuys, 'total_qty')),
                'damaged_qty'      => array_sum(array_column($damages, 'quantity')),
                'damage_loss'      => array_sum(array_column($damages, 'total_loss')),
            ]
        ];
    }

    /**
     * Detailed Purchase / Supplier Report Builder (Third-Party Buys)
     */
    private function getDetailedPurchaseReport(string $startDate, string $endDate, int $supplierId = 0): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $sql = "SELECT po.*, 
                       COALESCE(s.name, sel.name, 'Third-Party Vendor') as supplier_name, 
                       COALESCE(s.company, sel.company, 'External Vendor') as supplier_company,
                       w.name as warehouse_name,
                       COALESCE(pi_agg.item_count, 0) as item_count,
                       COALESCE(pi_agg.total_qty, 0) as total_qty
                FROM purchase_orders po
                LEFT JOIN warehouse_suppliers s ON po.warehouse_supplier_id = s.id
                LEFT JOIN sellers sel ON po.seller_id = sel.id
                LEFT JOIN warehouses w ON po.warehouse_id = w.id
                LEFT JOIN (
                    SELECT purchase_order_id, COUNT(id) as item_count, SUM(quantity) as total_qty
                    FROM purchase_items
                    GROUP BY purchase_order_id
                ) pi_agg ON pi_agg.purchase_order_id = po.id
                WHERE po.concern_id = ? AND po.created_at BETWEEN ? AND ?
                  AND (po.warehouse_supplier_id IS NOT NULL OR po.seller_id IS NOT NULL)";
        $params = [$concernId, $startDate, $endDate];
        if ($supplierId > 0) {
            $sql .= " AND (po.warehouse_supplier_id = ? OR po.seller_id = ?)";
            $params[] = $supplierId;
            $params[] = $supplierId;
        }
        $sql .= " ORDER BY po.id DESC";
        $pos = Database::fetchAll($sql, $params);

        return [
            'orders' => $pos,
            'totals' => [
                'total_spend'  => array_sum(array_column($pos, 'total_amount')),
                'total_qty'    => array_sum(array_column($pos, 'total_qty')),
                'total_orders' => count($pos),
            ]
        ];
    }

    /**
     * Detailed Shop & POS Report Builder
     */
    private function getDetailedShopReport(string $startDate, string $endDate, int $shopId = 0): array {
        $concernId = ConcernTransactionService::activeConcernId();
        $sSql = "SELECT s.*, sh.name as shop_name, c.name as customer_name
                 FROM sales s
                 LEFT JOIN shops sh ON s.shop_id = sh.id
                 LEFT JOIN customers c ON s.customer_id = c.id
                 WHERE s.concern_id = ? AND s.created_at BETWEEN ? AND ?";
        $sParams = [$concernId, $startDate, $endDate];
        if ($shopId > 0) {
            $sSql .= " AND s.shop_id = ?";
            $sParams[] = $shopId;
        }
        $sSql .= " ORDER BY s.id DESC";
        $sales = Database::fetchAll($sSql, $sParams);

        $eSql = "SELECT ce.*, sh.name as shop_name
                 FROM cash_expenses ce
                 LEFT JOIN shops sh ON ce.shop_id = sh.id
                 WHERE ce.concern_id = ? AND ce.created_at BETWEEN ? AND ?";
        $eParams = [$concernId, $startDate, $endDate];
        if ($shopId > 0) {
            $eSql .= " AND ce.shop_id = ?";
            $eParams[] = $shopId;
        }
        $eSql .= " ORDER BY ce.id DESC";
        $expenses = Database::fetchAll($eSql, $eParams);

        return [
            'sales'    => $sales,
            'expenses' => $expenses,
            'totals'   => [
                'total_sales'    => array_sum(array_column($sales, 'total_amount')),
                'total_expenses' => array_sum(array_column($expenses, 'amount')),
                'sales_count'    => count($sales),
            ]
        ];
    }

    /**
     * Detailed Inventory Valuation Report Builder
     */
    private function getDetailedInventoryReport(string $locationType = 'ALL', string $stockState = 'ALL', int $categoryId = 0): array {
        $concernId = function_exists('currentConcernId') ? currentConcernId() : null;
        if ($concernId === null) {
            return ['items' => [], 'totals' => ['total_qty' => 0, 'cost_value' => 0, 'selling_value' => 0]];
        }
        $warehouseIds = array_map('intval', array_column(getScopedWarehouses(), 'id'));
        $shopIds = array_map('intval', array_column(getScopedShops(), 'id'));
        $productionUnitIds = array_map('intval', array_column(getScopedProductionUnits(), 'id'));
        $channelIds = array_map('intval', array_column(getScopedSalesChannels(), 'id'));
        $warehouseList = $warehouseIds ? implode(',', $warehouseIds) : '0';
        $shopList = $shopIds ? implode(',', $shopIds) : '0';
        $productionUnitList = $productionUnitIds ? implode(',', $productionUnitIds) : '0';
        $channelList = $channelIds ? implode(',', $channelIds) : '0';
        $sql = "SELECT sb.*, pv.sku, pv.mrp, pv.selling_price, pv.purchase_price, pv.production_cost,
                       p.name as product_name, p.code as product_code,
                       sz.name as size_name, col.name as color_name,
                       COALESCE(cat.name, 'General') as category_name,
                       CASE 
                           WHEN sb.location_type = 'WAREHOUSE' THEN w.name
                           WHEN sb.location_type = 'SHOP' THEN sh.name
                           WHEN sb.location_type = 'PRODUCTION_UNIT' THEN pu.name
                           WHEN sb.location_type = 'CHANNEL' THEN ch.name
                           ELSE 'Production'
                       END as location_name
                FROM stock_balances sb
                JOIN product_variants pv ON sb.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                LEFT JOIN sizes sz ON pv.size_id = sz.id
                LEFT JOIN colors col ON pv.color_id = col.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                LEFT JOIN warehouses w ON sb.location_type = 'WAREHOUSE' AND sb.location_id = w.id AND w.concern_id = sb.concern_id
                LEFT JOIN shops sh ON sb.location_type = 'SHOP' AND sb.location_id = sh.id AND sh.concern_id = sb.concern_id
                LEFT JOIN production_units pu ON sb.location_type = 'PRODUCTION_UNIT' AND sb.location_id = pu.id AND pu.concern_id = sb.concern_id
                LEFT JOIN sales_channels ch ON sb.location_type = 'CHANNEL' AND sb.location_id = ch.id AND ch.concern_id = sb.concern_id
                WHERE sb.concern_id = ?
                  AND ((sb.location_type = 'WAREHOUSE' AND sb.location_id IN ({$warehouseList}) AND w.id IS NOT NULL)
                    OR (sb.location_type = 'SHOP' AND sb.location_id IN ({$shopList}) AND sh.id IS NOT NULL)
                    OR (sb.location_type = 'PRODUCTION_UNIT' AND sb.location_id IN ({$productionUnitList}) AND pu.id IS NOT NULL)
                    OR (sb.location_type = 'CHANNEL' AND sb.location_id IN ({$channelList}) AND ch.id IS NOT NULL)
                    OR (sb.location_type = 'OFFICE' AND " . (isSuperAdmin() ? '1=1' : '1=0') . "))";
        $params = [$concernId];

        if ($locationType !== 'ALL') {
            $sql .= " AND sb.location_type = ?";
            $params[] = $locationType;
        }
        if ($stockState !== 'ALL') {
            $sql .= " AND sb.stock_state = ?";
            $params[] = $stockState;
        }
        if ($categoryId > 0) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY p.name ASC, pv.sku ASC";
        $items = Database::fetchAll($sql, $params);

        $totQty = 0;
        $totValCost = 0;
        $totValSell = 0;

        foreach ($items as &$it) {
            $cost = (float)($it['purchase_price'] > 0 ? $it['purchase_price'] : ($it['production_cost'] > 0 ? $it['production_cost'] : $it['selling_price']));
            $sell = (float)($it['selling_price'] > 0 ? $it['selling_price'] : $it['mrp']);
            $qty = (float)$it['quantity'];

            $it['cost_val'] = $cost * $qty;
            $it['sell_val'] = $sell * $qty;

            $totQty += $qty;
            $totValCost += $it['cost_val'];
            $totValSell += $it['sell_val'];
        }
        unset($it);

        return [
            'items'  => $items,
            'totals' => [
                'total_qty'     => $totQty,
                'cost_value'    => $totValCost,
                'selling_value' => $totValSell,
            ]
        ];
    }

    /**
     * Unified Export Formatter for Excel & Print
     */
    private function collectReportDataForExport(string $type, array $filter): array {
        switch ($type) {
            case 'profitability':
            case 'overview':
                $pnl = $this->getProfitabilityMetrics($filter['start_date'], $filter['end_date']);
                $revLines = $this->getRevenueLineItems($filter['start_date'], $filter['end_date']);
                $lossLines = $this->getLossLineItems($filter['start_date'], $filter['end_date']);

                $rows = [];
                foreach ($revLines as $r) {
                    $rows[] = ['REVENUE', $r['ref_no'], $r['source'], $r['customer_name'], date('d-m-Y H:i', strtotime($r['created_at'])), round($r['total_amount'], 2), 'Active Inflow'];
                }
                foreach ($lossLines as $l) {
                    $rows[] = ['LOSS', $l['ref_no'], $l['loss_type'], $l['product_name'] . ' (' . $l['reason'] . ')', date('d-m-Y H:i', strtotime($l['created_at'])), '-' . round($l['amount'], 2), $l['status']];
                }

                return [
                    'title'        => 'Profit & Loss (P&L) Financial Statement',
                    'summary_kpis' => [
                        'Gross Revenue (Sales + Wastage)' => 'Rs. ' . number_format($pnl['gross_revenue'], 2),
                        'Direct Cost of Goods Sold'       => 'Rs. ' . number_format($pnl['direct_cost'], 2),
                        'Operating & Factory Expenses'    => 'Rs. ' . number_format($pnl['operating_expenses'], 2),
                        'Recorded Losses & Defect Scrap'  => 'Rs. ' . number_format($pnl['recorded_losses'], 2),
                        'Gross Profit'                    => 'Rs. ' . number_format($pnl['gross_profit'], 2) . " ({$pnl['gross_margin_pct']}%)",
                        'Net Profit'                      => 'Rs. ' . number_format($pnl['net_profit'], 2) . " ({$pnl['net_margin_pct']}%)",
                    ],
                    'columns'      => ['Stream Type', 'Reference No', 'Channel / Category', 'Party / Product Description', 'Date', 'Amount (INR)', 'Status'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', '', '', 'Net: Rs. ' . number_format($pnl['net_profit'], 2), 'Margin: ' . $pnl['net_margin_pct'] . '%'],
                    'raw_data'     => $pnl,
                ];

            case 'website':
            case 'sales_website':
                $search = trim($_GET['search'] ?? '');
                $paymentStatus = trim($_GET['payment_status'] ?? 'all');
                $paymentMode = trim($_GET['payment_mode'] ?? 'all');
                $orderStatus = trim($_GET['order_status'] ?? 'all');
                $data = $this->getDetailedWebsiteReport($filter['start_date'], $filter['end_date'], [
                    'search'         => $search,
                    'payment_status' => $paymentStatus,
                    'payment_mode'   => $paymentMode,
                    'order_status'   => $orderStatus,
                ]);
                $rows = [];
                foreach ($data['orders'] as $inv) {
                    $rows[] = [
                        $inv['invoice_number'],
                        'Website',
                        $inv['customer_name'],
                        $inv['customer_phone'],
                        $inv['customer_email'] ?? '',
                        $inv['customer_city'] ?? '-',
                        $inv['payment_mode'] ?? 'Online',
                        $inv['payment_status'],
                        $inv['status'],
                        $inv['total_pcs'] . ' pcs',
                        round($inv['total_amount'], 2),
                        round($inv['discount_amount'], 2),
                        round($inv['gst_amount'] ?? $inv['tax_amount'] ?? 0, 2),
                        round($inv['paid_amount'], 2),
                        date('d-m-Y H:i', strtotime($inv['created_at'])),
                    ];
                }

                return [
                    'title'        => 'Website E-Commerce Sales & Orders Register',
                    'summary_kpis' => [
                        'Total Orders Count'  => number_format($data['totals']['total_orders']),
                        'Total Units Sold'    => number_format($data['totals']['total_pcs']) . ' pcs',
                        'Gross Website Sales' => 'Rs. ' . number_format($data['totals']['total_amount'], 2),
                        'Settled / Paid'      => 'Rs. ' . number_format($data['totals']['total_paid'], 2),
                        'Pending Balance'     => 'Rs. ' . number_format($data['totals']['total_pending'], 2),
                        'Total Discounts'     => 'Rs. ' . number_format($data['totals']['total_discount'], 2),
                        'GST Tax Collected'   => 'Rs. ' . number_format($data['totals']['total_tax'], 2),
                    ],
                    'columns'      => ['Order #', 'Channel', 'Customer Name', 'Contact', 'Email', 'City', 'Payment Mode', 'Payment Status', 'Order Status', 'Items (Pcs)', 'Gross Total (INR)', 'Discount (INR)', 'GST (INR)', 'Paid (INR)', 'Date'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', '', '', '', '', '', '', $data['totals']['total_pcs'] . ' pcs', number_format($data['totals']['total_amount'], 2), number_format($data['totals']['total_discount'], 2), number_format($data['totals']['total_tax'], 2), number_format($data['totals']['total_paid'], 2), ''],
                    'raw_data'     => $data,
                ];

            case 'whatsapp':
            case 'sales_whatsapp':
                $search = trim($_GET['search'] ?? '');
                $paymentStatus = trim($_GET['payment_status'] ?? 'all');
                $paymentMode = trim($_GET['payment_mode'] ?? 'all');
                $orderStatus = trim($_GET['order_status'] ?? 'all');
                $billerName = trim($_GET['biller_name'] ?? 'all');
                $data = $this->getDetailedWhatsappReport($filter['start_date'], $filter['end_date'], [
                    'search'         => $search,
                    'payment_status' => $paymentStatus,
                    'payment_mode'   => $paymentMode,
                    'order_status'   => $orderStatus,
                    'biller_name'    => $billerName,
                ]);
                $rows = [];
                foreach ($data['orders'] as $inv) {
                    $rows[] = [
                        $inv['invoice_number'],
                        'WhatsApp',
                        $inv['customer_name'],
                        $inv['customer_phone'],
                        $inv['customer_email'] ?? '',
                        $inv['customer_city'] ?? '-',
                        $inv['biller_name'] ?? 'Sales Agent',
                        $inv['payment_mode'] ?? 'UPI',
                        $inv['payment_status'],
                        $inv['status'],
                        $inv['total_pcs'] . ' pcs',
                        round($inv['total_amount'], 2),
                        round($inv['discount_amount'], 2),
                        round($inv['gst_amount'] ?? $inv['tax_amount'] ?? 0, 2),
                        round($inv['paid_amount'], 2),
                        date('d-m-Y H:i', strtotime($inv['created_at'])),
                    ];
                }

                return [
                    'title'        => 'WhatsApp Social Commerce Sales & Orders Register',
                    'summary_kpis' => [
                        'Total Orders Count'   => number_format($data['totals']['total_orders']),
                        'Total Units Sold'     => number_format($data['totals']['total_pcs']) . ' pcs',
                        'Gross WhatsApp Sales' => 'Rs. ' . number_format($data['totals']['total_amount'], 2),
                        'Total Collected'      => 'Rs. ' . number_format($data['totals']['total_paid'], 2),
                        'Pending Receivables'  => 'Rs. ' . number_format($data['totals']['total_pending'], 2),
                        'Total Discounts'      => 'Rs. ' . number_format($data['totals']['total_discount'], 2),
                    ],
                    'columns'      => ['Order / Invoice #', 'Channel', 'Customer Name', 'WhatsApp Contact', 'Email', 'City', 'Biller Staff', 'Payment Mode', 'Payment Status', 'Order Status', 'Items (Pcs)', 'Gross Total (INR)', 'Discount (INR)', 'GST (INR)', 'Paid (INR)', 'Date'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', '', '', '', '', '', '', '', $data['totals']['total_pcs'] . ' pcs', number_format($data['totals']['total_amount'], 2), number_format($data['totals']['total_discount'], 2), number_format($data['totals']['total_tax'], 2), number_format($data['totals']['total_paid'], 2), ''],
                    'raw_data'     => $data,
                ];

            case 'online':
                $webData = $this->getDetailedWebsiteReport($filter['start_date'], $filter['end_date']);
                $waData = $this->getDetailedWhatsappReport($filter['start_date'], $filter['end_date']);
                $allDigital = array_merge($webData['orders'], $waData['orders']);
                usort($allDigital, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

                $rows = [];
                foreach ($allDigital as $inv) {
                    $chName = (strtoupper($inv['sale_type'] ?? '') === 'WEBSITE' || strtoupper($inv['sales_channel'] ?? '') === 'WEBSITE') ? 'Website' : 'WhatsApp';
                    $rows[] = [
                        $inv['invoice_number'],
                        $chName,
                        $inv['customer_name'],
                        $inv['customer_phone'],
                        $inv['customer_email'] ?? '',
                        $inv['customer_city'] ?? '-',
                        $inv['biller_name'] ?? 'Sales Agent',
                        $inv['payment_mode'] ?? 'Online',
                        $inv['payment_status'],
                        $inv['total_pcs'] . ' pcs',
                        round($inv['total_amount'], 2),
                        round($inv['discount_amount'], 2),
                        round($inv['paid_amount'], 2),
                        date('d-m-Y H:i', strtotime($inv['created_at'])),
                    ];
                }
                $totAmt = array_sum(array_column($allDigital, 'total_amount'));
                $totPaid = array_sum(array_column($allDigital, 'paid_amount'));
                $totDisc = array_sum(array_column($allDigital, 'discount_amount'));
                $totPcs = array_sum(array_column($allDigital, 'total_pcs'));

                return [
                    'title'        => 'Digital & Online Channels (Website + WhatsApp) Sales Register',
                    'summary_kpis' => [
                        'Total Digital Orders'  => number_format(count($allDigital)),
                        'Total Units Sold'      => number_format($totPcs) . ' pcs',
                        'Website Gross Sales'   => 'Rs. ' . number_format($webData['totals']['total_amount'], 2),
                        'WhatsApp Gross Sales'  => 'Rs. ' . number_format($waData['totals']['total_amount'], 2),
                        'Total Digital Revenue' => 'Rs. ' . number_format($totAmt, 2),
                        'Total Collected'       => 'Rs. ' . number_format($totPaid, 2),
                    ],
                    'columns'      => ['Invoice / Order #', 'Channel', 'Customer Name', 'Contact', 'Email', 'City', 'Biller / Agent', 'Payment Mode', 'Status', 'Units Sold', 'Gross Total (INR)', 'Discount (INR)', 'Paid (INR)', 'Date'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', '', '', '', '', '', '', number_format($totPcs) . ' pcs', number_format($totAmt, 2), number_format($totDisc, 2), number_format($totPaid, 2), ''],
                    'raw_data'     => ['website' => $webData, 'whatsapp' => $waData],
                ];

            case 'sales':
            case 'sales_pos':
            case 'sales_warehouse':
                $channel = $_GET['channel'] ?? 'all';
                if ($type === 'sales_whatsapp') $channel = 'whatsapp';
                if ($type === 'sales_website') $channel = 'website';
                if ($type === 'sales_pos') $channel = 'shop_pos';
                if ($type === 'sales_warehouse') $channel = 'warehouse_pos';

                $paymentMethod = $_GET['payment_method'] ?? 'all';
                $data = $this->getDetailedSalesReport($filter['start_date'], $filter['end_date'], $channel, $paymentMethod);
                
                // If specific subchannel was requested
                $invoicesList = $data['invoices'];
                if ($channel === 'whatsapp') {
                    $invoicesList = $data['channels']['whatsapp']['orders'] ?? [];
                } elseif ($channel === 'website') {
                    $invoicesList = $data['channels']['website']['orders'] ?? [];
                } elseif ($channel === 'shop_pos') {
                    $invoicesList = $data['channels']['shop_pos']['orders'] ?? [];
                } elseif ($channel === 'warehouse_pos') {
                    $invoicesList = $data['channels']['warehouse_pos']['orders'] ?? [];
                }

                $rows = [];
                foreach ($invoicesList as $inv) {
                    $rows[] = [
                        $inv['invoice_no'],
                        $inv['channel'],
                        $inv['customer_name'],
                        $inv['customer_phone'],
                        $inv['payment_method'],
                        $inv['payment_status'],
                        round($inv['total_amount'], 2),
                        round($inv['discount_amount'], 2),
                        round($inv['tax_amount'], 2),
                        round($inv['paid_amount'], 2),
                        date('d-m-Y H:i', strtotime($inv['created_at'])),
                    ];
                }

                $totAmt = array_sum(array_column($invoicesList, 'total_amount'));
                $totDisc = array_sum(array_column($invoicesList, 'discount_amount'));
                $totTax = array_sum(array_column($invoicesList, 'tax_amount'));
                $totPaid = array_sum(array_column($invoicesList, 'paid_amount'));
                $totPending = max(0, $totAmt - $totPaid);

                return [
                    'title'        => 'Multi-Channel Sales & Invoice Register',
                    'summary_kpis' => [
                        'Total Orders Count' => count($invoicesList),
                        'Total Gross Sales'  => 'Rs. ' . number_format($totAmt, 2),
                        'Total Discounts'    => 'Rs. ' . number_format($totDisc, 2),
                        'GST Tax Collected'  => 'Rs. ' . number_format($totTax, 2),
                        'Total Paid Amount'  => 'Rs. ' . number_format($totPaid, 2),
                        'Pending Receivable' => 'Rs. ' . number_format($totPending, 2),
                    ],
                    'columns'      => ['Invoice / Order #', 'Channel', 'Customer Name', 'Phone', 'Payment Mode', 'Status', 'Total (INR)', 'Disc (INR)', 'GST (INR)', 'Paid (INR)', 'Date'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', '', '', '', number_format($totAmt, 2), number_format($totDisc, 2), number_format($totTax, 2), number_format($totPaid, 2), ''],
                    'raw_data'     => $data,
                ];

            case 'production':
                $unitId = !empty($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
                $data = $this->getDetailedProductionReport($filter['start_date'], $filter['end_date'], $unitId);
                $rows = [];
                foreach ($data['programs'] as $p) {
                    $rows[] = [
                        $p['program_number'],
                        $p['unit_name'] ?? 'Unit',
                        $p['product_name'] ?? '-',
                        $p['planned_quantity'],
                        $p['completed_quantity'],
                        $p['status'],
                        date('d-m-Y', strtotime($p['start_date'] ?? $p['created_at'])),
                    ];
                }

                return [
                    'title'        => 'Production & Manufacturing Program Analysis',
                    'summary_kpis' => [
                        'Planned Pieces'      => $data['totals']['planned_qty'] . ' pcs',
                        'Completed Pieces'    => $data['totals']['completed_qty'] . ' pcs',
                        'Cut Pieces'          => $data['totals']['cut_qty'] . ' pcs',
                        'Cutting Wastage'     => number_format($data['totals']['cutting_wastage'], 2) . ' kg',
                        'QC Pass Rate'        => $data['totals']['qc_passed'] . ' passed / ' . $data['totals']['qc_rejected'] . ' rejected',
                        'Production Expenses' => 'Rs. ' . number_format($data['totals']['expense_total'], 2),
                        'Wastage Revenue'     => 'Rs. ' . number_format($data['totals']['wastage_revenue'], 2),
                    ],
                    'columns'      => ['Program #', 'Production Unit', 'Product / Style', 'Planned Qty', 'Completed Qty', 'Status', 'Start Date'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', $data['totals']['planned_qty'], $data['totals']['completed_qty'], '', ''],
                    'raw_data'     => $data,
                ];

            case 'warehouse':
                $warehouseId = !empty($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
                $data = $this->getDetailedWarehouseReport($filter['start_date'], $filter['end_date'], $warehouseId);
                $rows = [];
                foreach ($data['allocations'] as $a) {
                    $rows[] = [
                        'ALLOCATION',
                        $a['allocation_number'],
                        $a['warehouse_name'] ?? 'WH',
                        $a['shop_name'] ?? 'Channel',
                        $a['total_qty'] . ' pcs',
                        $a['status'],
                        date('d-m-Y H:i', strtotime($a['created_at'])),
                    ];
                }
                foreach ($data['damages'] as $d) {
                    $rows[] = [
                        'DAMAGE LOSS',
                        $d['damage_number'],
                        $d['warehouse_name'] ?? 'WH',
                        $d['product_name'] . ' (' . $d['sku'] . ')',
                        $d['quantity'] . ' pcs',
                        round($d['total_loss'], 2),
                        date('d-m-Y H:i', strtotime($d['created_at'])),
                    ];
                }

                return [
                    'title'        => 'Warehouse Allocations, Movements & Damage Losses',
                    'summary_kpis' => [
                        'Total Dispatched Pcs'  => $data['totals']['dispatched_qty'] . ' pcs',
                        'Total Damaged Pcs'     => $data['totals']['damaged_qty'] . ' pcs',
                        'Total Estimated Loss'  => 'Rs. ' . number_format($data['totals']['damage_loss'], 2),
                    ],
                    'columns'      => ['Record Type', 'Ref Number', 'Origin Warehouse', 'Destination / Product', 'Quantity', 'Status / Value', 'Logged Date'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', '', 'Dispatched: ' . $data['totals']['dispatched_qty'] . ' | Damaged: ' . $data['totals']['damaged_qty'], 'Loss: Rs. ' . number_format($data['totals']['damage_loss'], 2), ''],
                    'raw_data'     => $data,
                ];

            case 'purchase':
                $supplierId = !empty($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
                $data = $this->getDetailedPurchaseReport($filter['start_date'], $filter['end_date'], $supplierId);
                $rows = [];
                foreach ($data['orders'] as $po) {
                    $rows[] = [
                        $po['po_number'],
                        $po['supplier_name'] ?? $po['supplier_company'] ?? 'Supplier',
                        $po['warehouse_name'] ?? 'WH',
                        $po['total_qty'] . ' pcs',
                        round($po['total_amount'], 2),
                        $po['payment_status'],
                        $po['status'],
                        date('d-m-Y', strtotime($po['order_date'] ?? $po['created_at'])),
                    ];
                }

                return [
                    'title'        => 'Supplier Procurement & Purchase Orders',
                    'summary_kpis' => [
                        'Total POs Count'   => $data['totals']['total_orders'],
                        'Total Spend (INR)' => 'Rs. ' . number_format($data['totals']['total_spend'], 2),
                        'Total Ordered Qty' => $data['totals']['total_qty'] . ' pcs',
                    ],
                    'columns'      => ['PO Number', 'Supplier / Vendor', 'Destination WH', 'Total Quantity', 'Total Amount (INR)', 'Payment Status', 'Order Status', 'Order Date'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', $data['totals']['total_qty'] . ' pcs', number_format($data['totals']['total_spend'], 2), '', '', ''],
                    'raw_data'     => $data,
                ];

            case 'shop':
                $shopId = !empty($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
                $data = $this->getDetailedShopReport($filter['start_date'], $filter['end_date'], $shopId);
                $rows = [];
                foreach ($data['sales'] as $s) {
                    $rows[] = [
                        'POS SALE',
                        $s['invoice_number'],
                        $s['shop_name'] ?? 'Shop',
                        $s['customer_name'] ?? 'Walk-in',
                        round($s['total_amount'], 2),
                        $s['payment_mode'],
                        date('d-m-Y H:i', strtotime($s['created_at'])),
                    ];
                }
                foreach ($data['expenses'] as $e) {
                    $rows[] = [
                        'EXPENSE VOUCHER',
                        $e['voucher_number'] ?? 'VCH-' . $e['id'],
                        $e['shop_name'] ?? 'Shop',
                        $e['expense_category'] . ' (' . ($e['paid_to'] ?? '') . ')',
                        '-' . round($e['amount'], 2),
                        $e['payment_mode'] ?? 'CASH',
                        date('d-m-Y H:i', strtotime($e['created_at'])),
                    ];
                }

                return [
                    'title'        => 'Retail Shop Sales & Petty Cash Activity',
                    'summary_kpis' => [
                        'Total Retail Sales' => 'Rs. ' . number_format($data['totals']['total_sales'], 2),
                        'Total Shop Expenses'=> 'Rs. ' . number_format($data['totals']['total_expenses'], 2),
                        'Net Shop Drawer'    => 'Rs. ' . number_format($data['totals']['total_sales'] - $data['totals']['total_expenses'], 2),
                    ],
                    'columns'      => ['Activity Type', 'Voucher / Invoice #', 'Retail Outlet', 'Particulars / Customer', 'Amount (INR)', 'Payment Mode', 'Date'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', '', 'Net: Rs. ' . number_format($data['totals']['total_sales'] - $data['totals']['total_expenses'], 2), '', ''],
                    'raw_data'     => $data,
                ];

            case 'inventory':
            default:
                $locationType = $_GET['location_type'] ?? 'ALL';
                $stockState = $_GET['stock_state'] ?? 'ALL';
                $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
                $data = $this->getDetailedInventoryReport($locationType, $stockState, $categoryId);
                $rows = [];
                foreach ($data['items'] as $it) {
                    $rows[] = [
                        $it['sku'],
                        $it['product_name'],
                        $it['category_name'],
                        $it['size_name'] . ' / ' . $it['color_name'],
                        $it['location_type'] . ': ' . $it['location_name'],
                        $it['stock_state'],
                        $it['quantity'],
                        round($it['purchase_price'] > 0 ? $it['purchase_price'] : $it['selling_price'], 2),
                        round($it['selling_price'] > 0 ? $it['selling_price'] : $it['mrp'], 2),
                        round($it['sell_val'], 2),
                    ];
                }

                return [
                    'title'        => 'Inventory Valuation & Stock Health Report',
                    'summary_kpis' => [
                        'Total On-Hand Quantity' => number_format($data['totals']['total_qty']) . ' pcs',
                        'Total Cost Valuation'   => 'Rs. ' . number_format($data['totals']['cost_value'], 2),
                        'Total Retail Valuation' => 'Rs. ' . number_format($data['totals']['selling_value'], 2),
                    ],
                    'columns'      => ['SKU', 'Product Name', 'Category', 'Variant', 'Location', 'Stock State', 'Quantity (pcs)', 'Unit Cost (INR)', 'Unit Price (INR)', 'Stock Value (INR)'],
                    'rows'         => $rows,
                    'totals'       => ['TOTALS', '', '', '', '', '', number_format($data['totals']['total_qty']), '', '', number_format($data['totals']['selling_value'], 2)],
                    'raw_data'     => $data,
                ];
        }
    }
}
