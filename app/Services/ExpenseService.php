<?php
/**
 * ExpenseService — Enterprise Universal Multi-Channel Expense Engine
 * Handles expense recording, voucher generation, receipt uploads, ledger tracking,
 * and category resolution across Shop, Warehouse, WhatsApp, and Website channels.
 */

require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Services/CloudinaryService.php';

class ExpenseService {

    /**
     * Generate sequential voucher number based on location type and date.
     * Format: {PREFIX}-YYYYMMDD-XXXX (e.g. WH-EXP-20260929-0001, WA-EXP-20260929-0001)
     */
    public static function generateVoucherNumber(string $locationType = 'WAREHOUSE', ?string $customPrefix = null): string {
        $prefix = $customPrefix;
        if (!$prefix) {
            $prefix = match (strtoupper($locationType)) {
                'WAREHOUSE' => 'WH-EXP',
                'WHATSAPP', 'WA' => 'WA-EXP',
                'WEBSITE', 'WEB' => 'WEB-EXP',
                'PRODUCTION', 'PROD' => 'PROD-EXP',
                default => 'EXP'
            };
        }

        $today = date('Ymd');
        $searchPattern = "{$prefix}-{$today}-%";
        $lastRecord = Database::fetchOne(
            "SELECT expense_number FROM expenses WHERE expense_number LIKE ? ORDER BY id DESC LIMIT 1",
            [$searchPattern]
        );

        $seq = 1;
        $existingNumber = $lastRecord['expense_number'] ?? '';
        if (!empty($existingNumber)) {
            $parts = explode('-', $existingNumber);
            $lastSeq = (int)end($parts);
            if ($lastSeq > 0) {
                $seq = $lastSeq + 1;
            }
        }

        return sprintf('%s-%s-%04d', $prefix, $today, $seq);
    }

    /**
     * Get active expense categories for a given department/module scope.
     */
    public static function getCategories(string $scope = 'WAREHOUSE'): array {
        $scopeClean = strtoupper(trim($scope));
        $cats = Database::fetchAll(
            "SELECT *, name as category_name FROM expense_categories 
             WHERE (department_scope = ? OR department_scope = 'GENERAL' OR department_scope = 'ALL') AND is_active = 1 
             ORDER BY name ASC",
            [$scopeClean]
        );

        $generalCommon = [
            ['name' => 'Tea, Coffee & Refreshments', 'category_name' => 'Tea, Coffee & Refreshments', 'code' => 'GEN_TEA'],
            ['name' => 'Staff Food & Snacks', 'category_name' => 'Staff Food & Snacks', 'code' => 'GEN_SNACKS'],
            ['name' => 'Office Stationery & Printing', 'category_name' => 'Office Stationery & Printing', 'code' => 'GEN_STATIONERY'],
            ['name' => 'Cleaning, Housekeeping & Water', 'category_name' => 'Cleaning, Housekeeping & Water', 'code' => 'GEN_CLEANING'],
            ['name' => 'Electricity & Power Backup', 'category_name' => 'Electricity & Power Backup', 'code' => 'GEN_POWER'],
            ['name' => 'Repairs & Maintenance', 'category_name' => 'Repairs & Maintenance', 'code' => 'GEN_MAINTENANCE'],
            ['name' => 'Miscellaneous / General Expense', 'category_name' => 'Miscellaneous / General Expense', 'code' => 'GEN_MISC'],
        ];

        if (empty($cats)) {
            // Default fallbacks if categories table is unpopulated for this scope
            $specific = match ($scopeClean) {
                'WAREHOUSE' => [
                    ['name' => 'Packaging Materials & Cartons', 'category_name' => 'Packaging Materials & Cartons', 'code' => 'WH_PACKAGING'],
                    ['name' => 'Logistics & Freight Shipping', 'category_name' => 'Logistics & Freight Shipping', 'code' => 'WH_FREIGHT'],
                    ['name' => 'Barcode Labels & Thermal Ribbons', 'category_name' => 'Barcode Labels & Thermal Ribbons', 'code' => 'WH_LABELS'],
                    ['name' => 'Porter / Coolie Handling Charges', 'category_name' => 'Porter / Coolie Handling Charges', 'code' => 'WH_PORTER'],
                    ['name' => 'Vehicle Fuel & Local Transport', 'category_name' => 'Vehicle Fuel & Local Transport', 'code' => 'WH_TRANSPORT'],
                ],
                'WHATSAPP' => [
                    ['name' => 'Courier & Parcel Dispatch (Delhivery/DTDC)', 'category_name' => 'Courier & Parcel Dispatch (Delhivery/DTDC)', 'code' => 'CHAN_COURIER'],
                    ['name' => 'WhatsApp Cloud API & Meta Software', 'category_name' => 'WhatsApp Cloud API & Meta Software', 'code' => 'CHAN_SOFTWARE'],
                    ['name' => 'Flyer & Poly Packaging Pouches', 'category_name' => 'Flyer & Poly Packaging Pouches', 'code' => 'CHAN_PACKAGING'],
                    ['name' => 'Customer Return Pickup Charges (RTO)', 'category_name' => 'Customer Return Pickup Charges (RTO)', 'code' => 'CHAN_RETURNS'],
                    ['name' => 'SIM Recharge & Mobile Support', 'category_name' => 'SIM Recharge & Mobile Support', 'code' => 'CHAN_SIM'],
                    ['name' => 'Sales Staff Allowance / Commission', 'category_name' => 'Sales Staff Allowance / Commission', 'code' => 'CHAN_STAFF'],
                ],
                'WEBSITE' => [
                    ['name' => 'Payment Gateway Charges (Razorpay/Stripe 2%)', 'category_name' => 'Payment Gateway Charges (Razorpay/Stripe 2%)', 'code' => 'CHAN_GATEWAY'],
                    ['name' => 'Cloud Server Hosting & Domain (AWS/Hostinger)', 'category_name' => 'Cloud Server Hosting & Domain (AWS/Hostinger)', 'code' => 'CHAN_HOSTING'],
                    ['name' => 'Courier Partner Shipping Fees (Shiprocket)', 'category_name' => 'Courier Partner Shipping Fees (Shiprocket)', 'code' => 'CHAN_COURIER'],
                    ['name' => 'Digital Ads (Meta / Google Spend)', 'category_name' => 'Digital Ads (Meta / Google Spend)', 'code' => 'CHAN_ADS'],
                    ['name' => 'Packaging Flyers & Bubble Mailers', 'category_name' => 'Packaging Flyers & Bubble Mailers', 'code' => 'CHAN_PACKAGING'],
                    ['name' => 'Software Plugins & SaaS Subscriptions', 'category_name' => 'Software Plugins & SaaS Subscriptions', 'code' => 'CHAN_SOFTWARE'],
                ],
                default => [
                    ['name' => 'Petty Cash Till Expenses', 'category_name' => 'Petty Cash Till Expenses', 'code' => 'SHOP_PETTY'],
                    ['name' => 'Store Rent & Maintenance', 'category_name' => 'Store Rent & Maintenance', 'code' => 'SHOP_RENT'],
                ]
            };
            return array_merge($specific, $generalCommon);
        }

        return array_merge($cats, $generalCommon);
    }

    /**
     * Record an Expense for any Module (Warehouse, WhatsApp, Website, Shop).
     */
    public static function recordExpense(array $payload, int $userId): array {
        $locationType = strtoupper(trim($payload['location_type'] ?? 'WAREHOUSE'));
        $locationId = (int)($payload['location_id'] ?? 0);
        $locationName = trim($payload['location_name'] ?? '');

        // Resolve location name if not provided
        if (empty($locationName)) {
            if ($locationType === 'WAREHOUSE') {
                $wh = Database::fetchOne("SELECT name, code FROM warehouses WHERE id = ?", [$locationId]);
                $locationName = $wh ? "{$wh['name']} ({$wh['code']})" : 'Main Warehouse';
            } elseif (in_array($locationType, ['CHANNEL', 'WHATSAPP', 'WEBSITE'])) {
                $chan = Database::fetchOne("SELECT name, code FROM sales_channels WHERE id = ?", [$locationId]);
                $locationName = $chan ? "{$chan['name']}" : ($locationType === 'WHATSAPP' ? 'WhatsApp Channel' : 'Website Channel');
            } elseif ($locationType === 'SHOP') {
                $sh = Database::fetchOne("SELECT name, code FROM shops WHERE id = ?", [$locationId]);
                $locationName = $sh ? "{$sh['name']}" : 'Retail Shop';
            }
        }

        $rawAmount = $payload['amount'] ?? $payload['total_cost'] ?? 0;
        if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
            throw new InvalidArgumentException("Expense amount must be a valid positive number greater than 0.00.");
        }
        $amount = round((float)$rawAmount, 2);
        if ($amount > 10000000) {
            throw new InvalidArgumentException("Expense amount exceeds the allowable limit (max ₹1,00,00,000).");
        }

        $category = trim($payload['category'] ?? $payload['expense_category'] ?? '');
        if (empty($category) || mb_strlen($category) < 2) {
            throw new InvalidArgumentException("Please select or enter a valid Expense Category (minimum 2 characters).");
        }
        if (mb_strlen($category) > 100) {
            throw new InvalidArgumentException("Expense Category cannot exceed 100 characters.");
        }

        $itemName = trim($payload['item_name'] ?? $payload['description'] ?? '');
        if (empty($itemName) || mb_strlen($itemName) < 2) {
            throw new InvalidArgumentException("Please enter an Item / Description title (minimum 2 characters).");
        }
        if (mb_strlen($itemName) > 255) {
            throw new InvalidArgumentException("Item / Description title cannot exceed 255 characters.");
        }

        $paidTo = trim($payload['paid_to'] ?? $payload['vendor_name'] ?? '');
        if (empty($paidTo) || mb_strlen($paidTo) < 2) {
            throw new InvalidArgumentException("Please enter a valid Payee / Vendor name (minimum 2 characters).");
        }
        if (mb_strlen($paidTo) > 100) {
            throw new InvalidArgumentException("Payee / Vendor name cannot exceed 100 characters.");
        }

        $expenseDate = !empty($payload['expense_date']) ? trim($payload['expense_date']) : date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate)) {
            throw new InvalidArgumentException("Please select a valid expense date in YYYY-MM-DD format.");
        }

        $vendorPhone = trim($payload['vendor_phone'] ?? '');
        if ($vendorPhone !== '') {
            $digits = preg_replace('/\D/', '', $vendorPhone);
            if (strlen($digits) !== 10 || !preg_match('/^[6-9]\d{9}$/', $digits)) {
                throw new InvalidArgumentException("Vendor / Payee Contact must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9 (e.g. 9876543210).");
            }
            $vendorPhone = $digits;
        }

        $invoiceNumber = trim($payload['invoice_number'] ?? $payload['bill_number'] ?? '');
        if ($invoiceNumber !== '' && mb_strlen($invoiceNumber) < 2) {
            throw new InvalidArgumentException("Bill / Cash Memo number must be at least 2 characters if provided.");
        }

        $validPaymentMethods = ['CASH', 'UPI', 'BANK_TRANSFER', 'CARD', 'OTHER'];
        $paymentMethod = strtoupper(trim($payload['payment_method'] ?? $payload['payment_mode'] ?? 'CASH'));
        if (!in_array($paymentMethod, $validPaymentMethods, true)) {
            $paymentMethod = 'CASH';
        }

        $paymentStatus = strtoupper(trim($payload['payment_status'] ?? 'PAID'));
        $paidAmount = isset($payload['paid_amount']) ? (float)$payload['paid_amount'] : ($paymentStatus === 'PAID' ? $amount : 0);
        $paymentReference = trim($payload['payment_reference'] ?? $payload['transaction_ref'] ?? '');
        $vendorCompany = trim($payload['vendor_company'] ?? '');
        $notes = trim($payload['notes'] ?? '');
        $receiptUrl = trim($payload['receipt_url'] ?? '');

        // Handle optional bill / invoice / receipt file upload (Cloudinary Cloud Storage)
        $fileKey = null;
        if (!empty($_FILES['receipt_file']['name']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
            $fileKey = 'receipt_file';
        } elseif (!empty($_FILES['receipt']['name']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $fileKey = 'receipt';
        } elseif (!empty($_FILES['bill_file']['name']) && $_FILES['bill_file']['error'] === UPLOAD_ERR_OK) {
            $fileKey = 'bill_file';
        }

        if ($fileKey !== null) {
            $file = $_FILES[$fileKey];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                throw new InvalidArgumentException("Invalid file type for receipt copy. Allowed formats: JPG, PNG, WEBP, PDF.");
            }
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new InvalidArgumentException("Receipt file size cannot exceed 10 MB.");
            }
            $folder = 'grm_inventory/expenses/' . strtolower($locationType);
            $uploadedUrl = CloudinaryService::upload($file, $folder, 'auto');
            if (!empty($uploadedUrl)) {
                $receiptUrl = $uploadedUrl;
            }
        }

        // Specific module dynamic metadata packed into notes/description if provided
        $extraInfo = [];
        if (!empty($payload['transporter_name'])) $extraInfo[] = "Transporter: " . trim($payload['transporter_name']);
        if (!empty($payload['vehicle_number']))   $extraInfo[] = "Vehicle: " . trim($payload['vehicle_number']);
        if (!empty($payload['waybill_number']))   $extraInfo[] = "LR/Waybill: " . trim($payload['waybill_number']);
        if (!empty($payload['courier_partner']))  $extraInfo[] = "Courier: " . trim($payload['courier_partner']);
        if (!empty($payload['tracking_number']))  $extraInfo[] = "AWB: " . trim($payload['tracking_number']);
        if (!empty($payload['order_invoice_tag']))$extraInfo[] = "Linked Order: " . trim($payload['order_invoice_tag']);
        if (!empty($payload['gateway_provider'])) $extraInfo[] = "Gateway: " . trim($payload['gateway_provider']);

        if (!empty($extraInfo)) {
            $metaStr = implode(' | ', $extraInfo);
            $notes = !empty($notes) ? "{$notes}\n[Metadata: {$metaStr}]" : "[Metadata: {$metaStr}]";
        }

        // Generate voucher code
        $voucherPrefix = match ($locationType) {
            'WAREHOUSE' => 'WH-EXP',
            'WHATSAPP' => 'WA-EXP',
            'WEBSITE' => 'WEB-EXP',
            default => 'EXP'
        };
        $voucherNumber = self::generateVoucherNumber($locationType, $voucherPrefix);

        Database::beginTransaction();
        try {
            Database::execute(
                "INSERT INTO expenses (
                    expense_number, location_type, location_id, location_name,
                    category, item_name, description, quantity, unit_type, unit_cost, tax_amount,
                    total_cost, paid_amount, payment_status, payment_method, payment_reference,
                    paid_to, vendor_phone, vendor_company, invoice_number, expense_date,
                    receipt_url, notes, created_by, approved_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, 1.00, 'unit', ?, 0.00,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, NOW(), NOW()
                )",
                [
                    $voucherNumber,
                    $locationType,
                    $locationId,
                    $locationName,
                    $category,
                    $itemName,
                    $notes,
                    $amount,
                    $amount,
                    $paidAmount,
                    $paymentStatus,
                    $paymentMethod,
                    $paymentReference,
                    $paidTo,
                    $vendorPhone,
                    $vendorCompany,
                    $invoiceNumber,
                    $expenseDate,
                    $receiptUrl,
                    $notes,
                    $userId,
                    $userId
                ]
            );

            $expenseId = (int)Database::lastInsertId();

            AuditService::log('expense_management', 'RECORD_EXPENSE', 'expenses', $expenseId, null, [
                'expense_number' => $voucherNumber,
                'location_type'  => $locationType,
                'location_name'  => $locationName,
                'category'       => $category,
                'amount'         => $amount,
                'paid_to'        => $paidTo,
                'payment_method' => $paymentMethod
            ]);

            Database::commit();

            return [
                'id'             => $expenseId,
                'expense_number' => $voucherNumber,
                'amount'         => $amount,
                'category'       => $category,
                'paid_to'        => $paidTo,
                'location_name'  => $locationName,
                'receipt_url'    => $receiptUrl,
            ];
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Retrieve scoped expense records for a module/location.
     */
    public static function getExpenses(string $locationType, ?int $locationId = null, array $filters = []): array {
        $where = ["e.location_type = ?"];
        $params = [strtoupper(trim($locationType))];

        if ($locationId !== null && $locationId > 0) {
            $where[] = "e.location_id = ?";
            $params[] = $locationId;
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $where[] = "(e.expense_number LIKE ? OR e.category LIKE ? OR e.item_name LIKE ? OR e.paid_to LIKE ? OR e.invoice_number LIKE ? OR e.notes LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s]);
        }

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $where[] = "e.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $where[] = "e.payment_method = ?";
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $where[] = "e.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = "e.expense_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "e.expense_date <= ?";
            $params[] = $filters['end_date'];
        }

        $whereClause = implode(' AND ', $where);
        $limit = (int)($filters['limit'] ?? 100);

        return Database::fetchAll(
            "SELECT e.*, u.full_name as created_by_name, au.full_name as approved_by_name
             FROM expenses e
             LEFT JOIN users u ON e.created_by = u.id
             LEFT JOIN users au ON e.approved_by = au.id
             WHERE {$whereClause}
             ORDER BY e.expense_date DESC, e.id DESC LIMIT {$limit}",
            $params
        );
    }

    /**
     * Compute Grand KPI Summary and Category Distributions for a Location/Module.
     */
    public static function getSummary(string $locationType, ?int $locationId = null, ?string $startDate = null, ?string $endDate = null): array {
        $baseWhere = ["location_type = ?"];
        $baseParams = [strtoupper(trim($locationType))];

        if ($locationId !== null && $locationId > 0) {
            $baseWhere[] = "location_id = ?";
            $baseParams[] = $locationId;
        }

        $baseWhereClause = implode(' AND ', $baseWhere);

        $where = $baseWhere;
        $params = $baseParams;

        if (!empty($startDate)) {
            $where[] = "expense_date >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "expense_date <= ?";
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        $tot = Database::fetchOne(
            "SELECT 
                COUNT(id) as total_count,
                COALESCE(SUM(total_cost), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(CASE WHEN payment_status = 'PENDING' OR payment_status = 'PARTIAL' THEN (total_cost - paid_amount) ELSE 0 END), 0) as total_pending,
                COALESCE(SUM(CASE WHEN payment_method = 'CASH' OR payment_method = 'PETTY_CASH' THEN paid_amount ELSE 0 END), 0) as cash_outflow,
                COALESCE(SUM(CASE WHEN payment_method = 'UPI' OR payment_method = 'BANK_TRANSFER' OR payment_method = 'CARD' THEN paid_amount ELSE 0 END), 0) as digital_outflow
             FROM expenses
             WHERE {$whereClause}",
            $params
        );

        // Today's metrics (Curdate)
        $todayDate = date('Y-m-d');
        $todayTot = Database::fetchOne(
            "SELECT 
                COUNT(id) as today_count,
                COALESCE(SUM(total_cost), 0) as today_amount
             FROM expenses
             WHERE {$baseWhereClause} AND expense_date = ?",
            array_merge($baseParams, [$todayDate])
        );

        // Current calendar month metrics
        $monthStart = date('Y-m-01');
        $monthTot = Database::fetchOne(
            "SELECT 
                COUNT(id) as month_count,
                COALESCE(SUM(total_cost), 0) as month_amount
             FROM expenses
             WHERE {$baseWhereClause} AND expense_date >= ? AND expense_date <= ?",
            array_merge($baseParams, [$monthStart, $todayDate])
        );

        // Unpaid / Pending Bills
        $unpaidTot = Database::fetchOne(
            "SELECT 
                COUNT(id) as unpaid_count,
                COALESCE(SUM(CASE WHEN total_cost > paid_amount THEN (total_cost - paid_amount) ELSE (CASE WHEN payment_status = 'PENDING' THEN total_cost ELSE 0 END) END), 0) as unpaid_amount
             FROM expenses
             WHERE {$baseWhereClause} AND (payment_status IN ('PENDING', 'PARTIAL') OR total_cost > paid_amount)",
            $baseParams
        );

        $categoryBreakdown = Database::fetchAll(
            "SELECT category, COUNT(id) as count, COALESCE(SUM(total_cost), 0) as amount
             FROM expenses
             WHERE {$whereClause}
             GROUP BY category
             ORDER BY amount DESC",
            $params
        );

        $paymentModeBreakdown = Database::fetchAll(
            "SELECT payment_method, COUNT(id) as count, COALESCE(SUM(total_cost), 0) as amount
             FROM expenses
             WHERE {$whereClause}
             GROUP BY payment_method
             ORDER BY amount DESC",
            $params
        );

        return [
            'total_count'      => (int)($tot['total_count'] ?? 0),
            'total_amount'     => (float)($tot['total_amount'] ?? 0),
            'total_paid'       => (float)($tot['total_paid'] ?? 0),
            'total_pending'    => (float)($tot['total_pending'] ?? 0),
            'cash_outflow'     => (float)($tot['cash_outflow'] ?? 0),
            'digital_outflow'  => (float)($tot['digital_outflow'] ?? 0),
            'today_count'      => (int)($todayTot['today_count'] ?? 0),
            'today_amount'     => (float)($todayTot['today_amount'] ?? 0),
            'month_count'      => (int)($monthTot['month_count'] ?? 0),
            'month_amount'     => (float)($monthTot['month_amount'] ?? 0),
            'unpaid_count'     => (int)($unpaidTot['unpaid_count'] ?? 0),
            'unpaid_amount'    => (float)($unpaidTot['unpaid_amount'] ?? 0),
            'categories'       => $categoryBreakdown,
            'payment_modes'    => $paymentModeBreakdown,
        ];
    }

    /**
     * Get Complete Voucher Details for Printing.
     */
    public static function getVoucherDetails(int $id): ?array {
        return Database::fetchOne(
            "SELECT e.*, u.full_name as created_by_name, au.full_name as approved_by_name,
                    u.email as creator_email
             FROM expenses e
             LEFT JOIN users u ON e.created_by = u.id
             LEFT JOIN users au ON e.approved_by = au.id
             WHERE e.id = ?",
            [$id]
        );
    }

    /**
     * Delete / Cancel an expense record.
     */
    public static function deleteExpense(int $id, int $userId): bool {
        $exp = Database::fetchOne("SELECT * FROM expenses WHERE id = ?", [$id]);
        if (!$exp) return false;

        Database::execute("DELETE FROM expenses WHERE id = ?", [$id]);

        AuditService::log('expense_management', 'DELETE_EXPENSE', 'expenses', $id, $exp, [
            'deleted_by' => $userId,
            'expense_number' => $exp['expense_number'] ?? $id
        ]);

        return true;
    }
}
