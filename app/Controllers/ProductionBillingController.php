<?php

require_once BASE_PATH . '/app/Services/ProductionBillingService.php';
require_once BASE_PATH . '/app/Services/ProductionBillingReturnsService.php';
require_once BASE_PATH . '/app/Services/ConcernTransactionService.php';

class ProductionBillingController
{
    public function returnsIndex(): void
    {
        $tab = $_GET['tab'] ?? 'all';
        if (!is_string($tab) || !in_array($tab, ['all', 'returns', 'exchanges'], true)) {
            $tab = 'all';
        }
        try {
            $schemaIssues = ProductionBillingReturnsService::getSchemaIssues();
            if ($schemaIssues) {
                Response::view('production.billing.returns_setup_error', [
                    'pageTitle' => 'Production Returns Setup Required',
                    'activeModule' => 'production',
                    'activeMenu' => 'prod-billing-returns',
                    'schemaIssues' => $schemaIssues,
                ]);
                return;
            }

            Response::view('production.billing.returns', [
                'pageTitle' => 'Production Billing Returns & Exchanges',
                'activeModule' => 'production',
                'activeMenu' => 'prod-billing-returns',
                'summary' => ProductionBillingReturnsService::getSummary(),
                'recentReturns' => ProductionBillingReturnsService::getHistory($tab),
                'currentTab' => $tab,
                'breadcrumb' => [
                    ['label' => 'Production', 'url' => url('production/dashboard')],
                    ['label' => 'Billing', 'url' => url('production/billing')],
                    ['label' => 'Returns & Exchanges'],
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('Production Billing Returns page failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            Response::view('production.billing.returns_setup_error', [
                'pageTitle' => 'Production Returns Setup Error',
                'activeModule' => 'production',
                'activeMenu' => 'prod-billing-returns',
                'schemaIssues' => [],
                'queryFailed' => true,
                'diagnosticMessage' => function_exists('isSuperAdmin') && isSuperAdmin() ? $e->getMessage() : null,
            ]);
        }
    }

    public function returnsLookup(): void
    {
        $query = trim((string)($_GET['query'] ?? ''));
        if ($query === '') {
            Response::json(['success' => false, 'message' => 'Enter a Production Bill number or customer phone.'], 400);
            return;
        }
        try {
            $data = ProductionBillingReturnsService::lookupBill($query);
            if (!$data) {
                Response::json(['success' => false, 'message' => 'No Production Bill matched that number or phone.'], 404);
                return;
            }
            Response::json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function processReturn(): void
    {
        $payload = $_POST;
        $rawInput = file_get_contents('php://input');
        if ($rawInput !== '') {
            $jsonPayload = json_decode($rawInput, true);
            if (is_array($jsonPayload)) {
                $payload = array_merge($payload, $jsonPayload);
            }
        }
        $csrfToken = $payload['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!CSRF::validate($csrfToken)) {
            Response::json(['success' => false, 'message' => 'Security token expired or invalid form submission.'], 403);
            return;
        }

        try {
            $result = ProductionBillingReturnsService::process($payload, (int)(Session::userId() ?? 0));
            Response::json([
                'success' => true,
                'message' => 'Production return ' . $result['return_number'] . ' processed.',
                'return_id' => $result['return_id'],
                'return_number' => $result['return_number'],
                'invoice_url' => url('production/billing/returns/print/' . $result['return_id']),
                'details_url' => url('production/billing/returns/view/' . $result['return_id']),
            ]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function viewReturn(string $id): void
    {
        $data = ProductionBillingReturnsService::getDetails((int)$id);
        if (!$data) {
            Response::redirect(url('production/billing/returns'), 'error', 'Production return was not found.');
            return;
        }
        Response::view('production.billing.return_view', [
            'pageTitle' => 'Production Return ' . $data['return']['return_number'],
            'activeModule' => 'production',
            'activeMenu' => 'prod-billing-returns',
            'return' => $data['return'],
            'returnItems' => $data['items'],
        ]);
    }

    public function printReturn(string $id): void
    {
        $data = ProductionBillingReturnsService::getDetails((int)$id);
        if (!$data) {
            Response::redirect(url('production/billing/returns'), 'error', 'Production return was not found.');
            return;
        }
        Response::viewOnly('production.billing.return_invoice', [
            'return' => $data['return'],
            'returnItems' => $data['items'],
            'sellerCompany' => SettingsService::getGroup('company'),
        ]);
    }

    public function billList(): void
    {
        $concernId = ConcernTransactionService::activeConcernId();
        $assignedUnitId = ProductionBillingService::resolveAssignedUnitId();
        if ($assignedUnitId === 0) {
            Response::view('production.billing.index', [
                'pageTitle' => 'Production Billing',
                'activeModule' => 'production',
                'activeMenu' => 'prod-billing',
                'bills' => [],
            ]);
            return;
        }

        $sql = "SELECT pb.*,
                       (SELECT COUNT(*) FROM production_bill_items pbi WHERE pbi.bill_id = pb.id) AS line_count
                FROM production_bills pb
                WHERE pb.concern_id = ?";
        $params = [$concernId];
        if ($assignedUnitId !== null) {
            $sql .= ' AND pb.production_unit_id = ?';
            $params[] = $assignedUnitId;
        }
        $sql .= ' ORDER BY pb.bill_date DESC, pb.id DESC';
        $bills = Database::fetchAll($sql, $params);

        Response::view('production.billing.index', [
            'pageTitle' => 'Production Billing',
            'activeModule' => 'production',
            'activeMenu' => 'prod-billing',
            'bills' => $bills,
        ]);
    }

    public function createBill(): void
    {
        $this->renderForm(null);
    }

    public function editBill(string $id): void
    {
        $bill = $this->findBill((int)$id);
        if (!$bill) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }
        if ($this->billHasReturns((int)$id)) {
            Response::redirect(url('production/billing/view/' . (int)$id), 'error', 'A bill with processed returns or exchanges cannot be edited.');
            return;
        }

        $this->renderForm($bill);
    }

    public function viewBill(string $id): void
    {
        $bill = $this->loadBill((int)$id);
        if (!$bill) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }

        Response::view('production.billing.view', [
            'pageTitle' => 'Production Bill ' . $bill['bill_number'],
            'activeModule' => 'production',
            'activeMenu' => 'prod-billing',
            'bill' => $bill,
            'billItems' => $this->loadBillItems((int)$bill['id']),
        ]);
    }

    public function printBill(string $id): void
    {
        $bill = $this->loadBill((int)$id);
        if (!$bill) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }

        Response::viewOnly('production.billing.print', [
            'bill' => $bill,
            'billItems' => $this->loadBillItems((int)$bill['id']),
            'sellerCompany' => SettingsService::getGroup('company'),
        ]);
    }

    public function searchInventory(): void
    {
        if (!hasPermission('production_billing', 'create') && !hasPermission('production_billing', 'edit')) {
            Response::error(403, 'You do not have permission to search Production Billing inventory.');
            return;
        }

        $query = trim((string)($_GET['q'] ?? ''));
        if ($query === '') {
            Response::jsonSuccess('Inventory items loaded.', ['items' => []]);
            return;
        }

        $billId = max(0, (int)($_GET['bill_id'] ?? 0));
        $items = $this->getAvailableInventory($billId);
        $needle = strtoupper($query);
        $matches = [];

        foreach ($items as $item) {
            $matchingTags = array_values(array_filter(
                $item['available_tags'],
                static fn(string $tag): bool => str_contains(strtoupper($tag), $needle)
            ));
            $metadataMatch = stripos($item['product_name'], $query) !== false
                || stripos((string)$item['sku'], $query) !== false
                || stripos((string)$item['size_name'], $query) !== false
                || stripos((string)$item['color_name'], $query) !== false
                || stripos((string)$item['program_number'], $query) !== false;

            if ($metadataMatch || $matchingTags) {
                $item['available_tags'] = $matchingTags ?: $item['available_tags'];
                $matches[] = $item;
                if (count($matches) >= 20) {
                    break;
                }
            }
        }

        Response::jsonSuccess('Inventory items loaded.', ['items' => $matches]);
    }

    public function storeBill(): void
    {
        $this->persistBill(null);
    }

    public function updateBill(string $id): void
    {
        $billId = (int)$id;
        if (!$this->findBill($billId)) {
            Response::redirect('production/billing', 'error', 'Bill not found.');
            return;
        }
        if ($this->billHasReturns($billId)) {
            Response::redirect(url('production/billing/view/' . $billId), 'error', 'A bill with processed returns or exchanges cannot be edited.');
            return;
        }
        $this->persistBill($billId);
    }

    public function deleteBill(string $id): void
    {
        $billId = (int)$id;
        if (!$this->findBill($billId)) {
            Response::redirect(url('production/billing'), 'error', 'Bill not found in the active production unit.');
            return;
        }
        if ($this->billHasReturns($billId)) {
            Response::redirect(url('production/billing/view/' . $billId), 'error', 'A bill with processed returns or exchanges cannot be deleted.');
            return;
        }
        try {
            $concernId = ConcernTransactionService::activeConcernId();
            $assignedUnitId = ProductionBillingService::resolveAssignedUnitId();
            if ($assignedUnitId === 0) {
                Response::redirect(url('production/billing'), 'error', 'Bill not found in the active production unit.');
                return;
            }
            Database::beginTransaction();
            ProductionBillingService::restoreBillStock(
                $billId,
                $concernId,
                (int)(Auth::id() ?? 0),
                'PRODUCTION_BILL_DELETE'
            );
            $deleteSql = 'DELETE FROM production_bills WHERE id = ? AND concern_id = ?';
            $deleteParams = [$billId, $concernId];
            if ($assignedUnitId !== null) {
                $deleteSql .= ' AND production_unit_id = ?';
                $deleteParams[] = $assignedUnitId;
            }
            Database::execute($deleteSql, $deleteParams);
            Database::commit();
            Response::redirect('production/billing', 'success', 'Production bill deleted.');
        } catch (Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            error_log('ProductionBillingController::deleteBill failed: ' . $e->getMessage());
            Response::redirect('production/billing', 'error', 'Unable to delete this bill.');
        }
    }

    private function renderForm(?array $bill): void
    {
        $billItems = $bill ? $this->loadBillItems((int)$bill['id']) : [];
        Response::view('production.billing.form', [
            'pageTitle' => $bill ? 'Edit Production Bill' : 'Create Production Bill',
            'activeModule' => 'production',
            'activeMenu' => 'prod-billing',
            'bill' => $bill,
            'billItems' => $billItems,
        ]);
    }

    private function findBill(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $concernId = ConcernTransactionService::activeConcernId();
        $assignedUnitId = ProductionBillingService::resolveAssignedUnitId();
        if ($assignedUnitId === 0) {
            return null;
        }
        $sql = 'SELECT * FROM production_bills WHERE id = ? AND concern_id = ?';
        $params = [$id, $concernId];
        if ($assignedUnitId !== null) {
            $sql .= ' AND production_unit_id = ?';
            $params[] = $assignedUnitId;
        }
        return Database::fetchOne($sql, $params);
    }

    private function billHasReturns(int $billId): bool
    {
        return (bool)Database::fetchOne('SELECT id FROM production_bill_returns WHERE bill_id = ? AND concern_id = ? LIMIT 1', [$billId, ConcernTransactionService::activeConcernId()]);
    }

    private function loadBill(int $id): ?array
    {
        return $this->findBill($id);
    }

    private function loadBillItems(int $billId): array
    {
        $concernId = ConcernTransactionService::activeConcernId();
        $items = Database::fetchAll(
            "SELECT pbi.*
             FROM production_bill_items pbi
             WHERE pbi.bill_id = ?
             ORDER BY pbi.id",
            [$billId]
        );

        $tagsByItem = [];
        if ($items) {
            $tagRows = Database::fetchAll(
                "SELECT bill_item_id, tag_code
                 FROM production_bill_item_tags
                 WHERE bill_id = ?
                 ORDER BY id",
                [$billId]
            );
            foreach ($tagRows as $tagRow) {
                $tagsByItem[(int)$tagRow['bill_item_id']][] = (string)$tagRow['tag_code'];
            }
        }
        foreach ($items as &$item) {
            $item['tags'] = $tagsByItem[(int)$item['id']] ?? [];
            $item['item_key'] = ProductionBillingService::inferBillItemKey($concernId, $billId, $item, $item['tags']);
        }
        unset($item);
        return $items;
    }

    private function getAvailableInventory(int $excludeBillId = 0): array
    {
        return ProductionBillingService::getAvailableInventory($excludeBillId);
    }

    private function activeProductionUnitId(): ?int
    {
        return ProductionBillingService::defaultProductionUnitId();
    }

    private function persistBill(?int $billId): void
    {
        $isNewBill = $billId === null;
        $concernId = ConcernTransactionService::activeConcernId();
        $assignedUnitId = ProductionBillingService::resolveAssignedUnitId();
        if ($assignedUnitId === 0) {
            Response::redirect('production/billing', 'error', 'No production unit is assigned in the active concern.');
            return;
        }
        $unitId = $this->activeProductionUnitId();
        if ($billId !== null) {
            $existingBill = $this->findBill($billId);
            if (!$existingBill) {
                Response::redirect(url('production/billing'), 'error', 'Bill not found in the active production unit.');
                return;
            }
            if ($assignedUnitId !== null && (int)$existingBill['production_unit_id'] !== $assignedUnitId) {
                Response::redirect(url('production/billing'), 'error', 'Bill not found in the active production unit.');
                return;
            }
        }
        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $companyAddress = trim((string)($_POST['company_address'] ?? ''));
        $companyPhone = trim((string)($_POST['company_phone'] ?? ''));
        $companyEmail = trim((string)($_POST['company_email'] ?? ''));
        $companyGstin = trim((string)($_POST['company_gstin'] ?? ''));
        $customerName = trim((string)($_POST['customer_name'] ?? ''));
        $customerPhone = preg_replace('/\s+/', '', (string)($_POST['customer_phone'] ?? ''));
        $customerAddress = trim((string)($_POST['customer_address'] ?? ''));
        $customerCity = trim((string)($_POST['customer_city'] ?? ''));
        $customerState = trim((string)($_POST['customer_state'] ?? ''));
        $customerGstin = trim((string)($_POST['customer_gstin'] ?? ''));
        $buyerType = strtoupper(trim((string)($_POST['buyer_type'] ?? 'COMPANY')));
        $billDate = trim((string)($_POST['bill_date'] ?? date('Y-m-d')));
        $paymentMethod = strtoupper(trim((string)($_POST['payment_method'] ?? 'CASH')));
        $submittedItems = $_POST['items'] ?? [];

        if (!in_array($buyerType, ['COMPANY', 'CUSTOMER'], true)) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Select whether this bill is for a company or customer.');
            return;
        }
        if (($buyerType === 'COMPANY' && $companyName === '') || ($buyerType === 'CUSTOMER' && ($customerName === '' || $customerPhone === ''))) {
            $requiredMessage = $buyerType === 'COMPANY'
                ? 'Company name is required.'
                : 'Customer name and phone are required.';
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', $requiredMessage);
            return;
        }
        if ($buyerType === 'COMPANY') {
            $customerName = null;
            $customerPhone = null;
            $customerAddress = null;
            $customerCity = null;
            $customerState = null;
            $customerGstin = null;
        } else {
            $companyName = null;
            $companyAddress = null;
            $companyPhone = null;
            $companyEmail = null;
            $companyGstin = null;
        }
        $dateParts = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $billDate, $dateMatch) ? $dateMatch : null;
        if (!$dateParts || !checkdate((int)$dateParts[2], (int)$dateParts[3], (int)$dateParts[1])) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'The bill date is invalid.');
            return;
        }
        if (!in_array($paymentMethod, ['CASH', 'UPI', 'CASH_UPI', 'CARD', 'NET_BANKING', 'CREDIT'], true)) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Select a valid payment method.');
            return;
        }

        $inventoryList = $this->getAvailableInventory($billId ?? 0);
        $inventoryByKey = [];
        foreach ($inventoryList as $entry) {
            $inventoryByKey[(string)$entry['item_key']] = $entry;
        }

        $itemPayload = [];
        $usedTags = [];
        $bucketCounts = [];
        foreach (is_array($submittedItems) ? $submittedItems : [] as $submitted) {
            if (!is_array($submitted)) {
                continue;
            }
            $tags = array_values(array_unique(array_filter(array_map(
                static fn($tag): string => trim((string)$tag),
                (array)($submitted['tags'] ?? [])
            ))));
            if (!$tags) {
                continue;
            }
            $stock = ProductionBillingService::resolveBillItemEntry($submitted, $inventoryByKey, $inventoryList);
            if (!$stock) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'One or more selected items are no longer available.');
                return;
            }

            $availableTagMap = array_fill_keys(array_map('strtoupper', $stock['available_tags']), true);
            foreach ($tags as $tag) {
                $normalized = strtoupper($tag);
                if (!isset($availableTagMap[$normalized]) || isset($usedTags[$normalized])) {
                    Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'A scanned QR code is unavailable or duplicated. Please scan the item again.');
                    return;
                }
                $usedTags[$normalized] = true;
            }
            if (($stock['source_kind'] ?? '') === 'INWARD') {
                $bucketKey = $stock['item_key'];
                $nextCount = ($bucketCounts[$bucketKey] ?? 0) + count($tags);
                if ($nextCount > (int)($stock['stock_qty'] ?? 0)) {
                    Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Requested quantity exceeds available concern stock.');
                    return;
                }
                $bucketCounts[$bucketKey] = $nextCount;
            }

            $unitPrice = filter_var($submitted['unit_price'] ?? null, FILTER_VALIDATE_FLOAT);
            $gstType = strtoupper(trim((string)($submitted['gst_type'] ?? 'BOTH')));
            $taxInputType = strtoupper(trim((string)($submitted['tax_input_type'] ?? 'PERCENT')));
            $sgstRate = filter_var($submitted['sgst_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
            $cgstRate = filter_var($submitted['cgst_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
            $igstRate = filter_var($submitted['igst_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
            $validGstTypes = ['SGST', 'IGST', 'CGST', 'BOTH', 'EXEMPTED'];
            $validTaxInputTypes = ['PERCENT', 'FIXED'];
            if (
                $unitPrice === false || $unitPrice < 0 ||
                !in_array($gstType, $validGstTypes, true) ||
                !in_array($taxInputType, $validTaxInputTypes, true) ||
                $sgstRate === false || $cgstRate === false || $igstRate === false ||
                min($sgstRate, $cgstRate, $igstRate) < 0 ||
                ($taxInputType === 'PERCENT' && max($sgstRate, $cgstRate, $igstRate) > 100)
            ) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'A product price or GST rate is invalid.');
                return;
            }

            if ($gstType === 'EXEMPTED') {
                $sgstRate = $cgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'SGST') {
                $cgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'CGST') {
                $sgstRate = $igstRate = 0.0;
            } elseif ($gstType === 'IGST') {
                $sgstRate = $cgstRate = 0.0;
            } else {
                $igstRate = 0.0;
            }

            $itemPayload[] = [
                'stock' => $stock,
                'tags' => $tags,
                'quantity' => count($tags),
                'unit_price' => round((float)$unitPrice, 2),
                'gst_type' => $gstType,
                'tax_input_type' => $taxInputType,
                'sgst_rate' => round((float)$sgstRate, 3),
                'cgst_rate' => round((float)$cgstRate, 3),
                'igst_rate' => round((float)$igstRate, 3),
            ];
        }
        if (!$itemPayload) {
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Scan at least one available Production Inventory QR item.');
            return;
        }

        $totals = [
            'gross_amount' => 0.0, 'discount_amount' => 0.0, 'subtotal' => 0.0,
            'cgst_amount' => 0.0, 'sgst_amount' => 0.0, 'igst_amount' => 0.0,
            'tax_amount' => 0.0, 'total_amount' => 0.0,
        ];
        foreach ($itemPayload as &$item) {
            $stock = $item['stock'];
            $qty = $item['quantity'];
            $mrp = max(0, (float)$stock['mrp']);
            $unitPrice = $item['unit_price'];
            $gross = round($mrp * $qty, 2);
            $taxable = round($unitPrice * $qty, 2);
            $discount = max(0, round($gross - $taxable, 2));
            $taxAmount = static function (float $rate) use ($item, $taxable, $qty): float {
                if ($rate <= 0) {
                    return 0.0;
                }
                return round(
                    $item['tax_input_type'] === 'PERCENT' ? $taxable * $rate / 100 : $rate * $qty,
                    2
                );
            };
            $cgst = $taxAmount($item['cgst_rate']);
            $sgst = $taxAmount($item['sgst_rate']);
            $igst = $taxAmount($item['igst_rate']);
            $tax = round($cgst + $sgst + $igst, 2);
            $lineTotal = round($taxable + $tax, 2);
            $gstPercentage = $item['tax_input_type'] === 'PERCENT'
                ? $item['cgst_rate'] + $item['sgst_rate'] + $item['igst_rate']
                : 0.0;
            $item['gst_percentage'] = round($gstPercentage, 3);
            $item['amounts'] = [
                'mrp' => $mrp,
                'discount_percentage' => $mrp > 0 ? round(($discount / $gross) * 100, 3) : 0,
                'discount_amount' => $discount,
                'taxable_amount' => $taxable,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => $igst,
                'tax_amount' => $tax,
                'total_amount' => $lineTotal,
            ];
            $totals['gross_amount'] += $gross;
            $totals['discount_amount'] += $discount;
            $totals['subtotal'] += $taxable;
            $totals['cgst_amount'] += $cgst;
            $totals['sgst_amount'] += $sgst;
            $totals['igst_amount'] += $igst;
            $totals['tax_amount'] += $tax;
            $totals['total_amount'] += $lineTotal;
        }
        unset($item);
        $taxType = count(array_unique(array_column($itemPayload, 'gst_type'))) === 1
            && $itemPayload[0]['gst_type'] === 'IGST'
            ? 'IGST'
            : 'CGST_SGST';
        foreach ($totals as &$amount) {
            $amount = round($amount, 2);
        }
        unset($amount);

        $cashAmount = 0.0;
        $upiAmount = 0.0;
        if ($paymentMethod === 'CASH') {
            $cashAmount = $totals['total_amount'];
        } elseif ($paymentMethod === 'UPI') {
            $upiAmount = $totals['total_amount'];
        } elseif ($paymentMethod === 'CASH_UPI') {
            $cashInput = filter_var($_POST['cash_amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $upiInput = filter_var($_POST['upi_amount'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($cashInput === false || $upiInput === false || $cashInput < 0 || $upiInput < 0) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Enter valid non-negative cash and UPI amounts.');
                return;
            }
            $cashAmount = round((float)$cashInput, 2);
            $upiAmount = round((float)$upiInput, 2);
            if (abs(($cashAmount + $upiAmount) - $totals['total_amount']) > 0.01) {
                Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', 'Cash and UPI amounts must add up to the bill total.');
                return;
            }
        }

        $paymentStatus = $paymentMethod === 'CREDIT' ? 'PENDING' : 'PAID';
        $header = [
            $buyerType,
            $companyName,
            $companyAddress,
            $companyPhone,
            $companyEmail,
            $companyGstin,
            $customerName,
            $customerPhone,
            $customerAddress,
            $customerCity,
            $customerState,
            $customerGstin,
            $billDate,
            $paymentMethod,
            $paymentStatus,
            $taxType,
            $cashAmount,
            $upiAmount,
            trim((string)($_POST['upi_reference'] ?? '')),
            $totals['gross_amount'],
            $totals['discount_amount'],
            $totals['subtotal'],
            $totals['cgst_amount'],
            $totals['sgst_amount'],
            $totals['igst_amount'],
            $totals['tax_amount'],
            $totals['total_amount'],
            trim((string)($_POST['notes'] ?? '')),
        ];

        $userId = (int)(Auth::id() ?? 0);
        Database::beginTransaction();
        try {
            if ($billId === null) {
                $billNumber = 'PB-' . date('Y') . '-' . date('mdHis') . '-' . random_int(10, 99);
                Database::execute(
                    "INSERT INTO production_bills
                     (bill_number, concern_id, production_unit_id, buyer_type, company_name, company_address, company_phone, company_email, company_gstin,
                      customer_name, customer_phone, customer_address, customer_city, customer_state, customer_gstin,
                      bill_date, payment_method, payment_status, tax_type, cash_amount, upi_amount, upi_reference,
                      gross_amount, discount_amount, subtotal, cgst_amount, sgst_amount, igst_amount, tax_amount,
                      total_amount, notes, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array_merge([$billNumber, $concernId, $unitId], $header, [$userId ?: null])
                );
                $billId = (int)Database::lastInsertId();
            } else {
                $billNumber = (string)($existingBill['bill_number'] ?? ('PB-' . $billId));
                ProductionBillingService::restoreBillStock($billId, $concernId, $userId, 'PRODUCTION_BILL_EDIT');
                $updateSql = "UPDATE production_bills SET buyer_type = ?, company_name = ?, company_address = ?, company_phone = ?, company_email = ?,
                     company_gstin = ?, customer_name = ?, customer_phone = ?, customer_address = ?, customer_city = ?,
                     customer_state = ?, customer_gstin = ?, bill_date = ?, payment_method = ?, payment_status = ?,
                     tax_type = ?, cash_amount = ?, upi_amount = ?, upi_reference = ?, gross_amount = ?, discount_amount = ?,
                     subtotal = ?, cgst_amount = ?, sgst_amount = ?, igst_amount = ?, tax_amount = ?, total_amount = ?, notes = ?
                     WHERE id = ? AND concern_id = ?";
                $updateParams = array_merge($header, [$billId, $concernId]);
                if ($assignedUnitId !== null) {
                    $updateSql .= ' AND production_unit_id = ?';
                    $updateParams[] = $assignedUnitId;
                }
                Database::execute($updateSql, $updateParams);
                Database::execute("DELETE FROM production_bill_items WHERE bill_id = ?", [$billId]);
                Database::execute("DELETE FROM production_bill_item_tags WHERE bill_id = ?", [$billId]);
            }

            $inwardStockDeductions = [];
            foreach ($itemPayload as $item) {
                $stock = $item['stock'];
                $amounts = $item['amounts'];
                Database::execute(
                    "INSERT INTO production_bill_items
                     (bill_id, qc_item_id, variant_id, product_name, sku, size_name, color_name, quantity, mrp,
                      unit_price, discount_percentage, discount_amount, gst_percentage, gst_type, tax_input_type,
                      sgst_rate, cgst_rate, igst_rate, taxable_amount, cgst_amount, sgst_amount, igst_amount,
                      tax_amount, total_amount)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $billId, $stock['qc_item_id'], $stock['variant_id'] ?: null,
                        $stock['product_name'], $stock['sku'], $stock['size_name'], $stock['color_name'],
                        $item['quantity'], $amounts['mrp'], $item['unit_price'], $amounts['discount_percentage'],
                        $amounts['discount_amount'], $item['gst_percentage'], $item['gst_type'], $item['tax_input_type'],
                        $item['sgst_rate'], $item['cgst_rate'], $item['igst_rate'], $amounts['taxable_amount'],
                        $amounts['cgst_amount'], $amounts['sgst_amount'], $amounts['igst_amount'],
                        $amounts['tax_amount'], $amounts['total_amount'],
                    ]
                );
                $billItemId = (int)Database::lastInsertId();
                if (($stock['source_kind'] ?? '') === 'INWARD'
                    && !empty($stock['stock_location_type'])
                    && !empty($stock['stock_location_id'])
                    && !empty($stock['variant_id'])) {
                    $deductKey = (int)$stock['variant_id'] . '|' . $stock['stock_location_type'] . '|' . (int)$stock['stock_location_id'];
                    if (!isset($inwardStockDeductions[$deductKey])) {
                        $inwardStockDeductions[$deductKey] = [
                            'variant_id' => (int)$stock['variant_id'],
                            'location_type' => (string)$stock['stock_location_type'],
                            'location_id' => (int)$stock['stock_location_id'],
                            'quantity' => 0,
                        ];
                    }
                    $inwardStockDeductions[$deductKey]['quantity'] += $item['quantity'];
                }
                foreach ($item['tags'] as $tag) {
                    $returnedInventory = Database::fetchOne(
                        "SELECT id FROM production_return_inventory WHERE new_tag_code = ? AND status = 'AVAILABLE' FOR UPDATE",
                        [$tag]
                    );
                    $existingClaim = Database::fetchOne(
                        'SELECT id, status FROM production_bill_item_tags WHERE tag_code = ? FOR UPDATE',
                        [$tag]
                    );
                    if ($existingClaim) {
                        if ($existingClaim['status'] !== 'RETURNED' || !$returnedInventory) {
                            throw new RuntimeException('A selected QR is already claimed by another bill or marked damaged.');
                        }
                        Database::execute(
                            "UPDATE production_bill_item_tags
                             SET bill_id = ?, bill_item_id = ?, qc_item_id = ?, status = 'BILLED'
                             WHERE id = ? AND status = 'RETURNED'",
                            [$billId, $billItemId, $stock['qc_item_id'], (int)$existingClaim['id']]
                        );
                    } else {
                        Database::execute(
                            "INSERT INTO production_bill_item_tags (bill_id, bill_item_id, qc_item_id, tag_code, status) VALUES (?, ?, ?, ?, 'BILLED')",
                            [$billId, $billItemId, $stock['qc_item_id'], $tag]
                        );
                    }
                    if ($returnedInventory) {
                        Database::execute(
                            "UPDATE production_return_inventory SET status = 'BILLED', updated_at = NOW()
                             WHERE id = ? AND status = 'AVAILABLE'",
                            [(int)$returnedInventory['id']]
                        );
                    }
                }
            }
            if ($inwardStockDeductions) {
                ProductionBillingService::decreaseBillStockBuckets($inwardStockDeductions, $billId, $billNumber, $userId);
            }
            Database::commit();
            Response::redirect('production/billing/view/' . $billId, 'success', $isNewBill ? 'Production bill created.' : 'Production bill saved.');
        } catch (Throwable $e) {
            Database::rollback();
            error_log('ProductionBillingController::persistBill failed: ' . $e->getMessage());
            $message = str_contains($e->getMessage(), 'uniq_production_bill_tag')
                ? 'A QR code was just billed in another transaction. Refresh inventory and try again.'
                : 'Unable to save the production bill. Please verify the details and try again.';
            Response::redirect($billId ? 'production/billing/edit/' . $billId : 'production/billing/create', 'error', $message);
        }
    }
}
