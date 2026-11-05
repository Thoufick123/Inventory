<?php
/**
 * Asset Controller
 * Central Controller for Enterprise Asset Management:
 * Production Machinery, Warehouse Hardware & Material Handling, Shop POS & Fixtures, and Head Office.
 */

require_once BASE_PATH . '/app/Services/AssetService.php';
require_once BASE_PATH . '/app/Services/CloudinaryService.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/CSRF.php';

class AssetController {

    private function uploadOptionalAssetFile(string $field, array $allowedMimeTypes, string $resourceType): ?string {
        $file = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException("The selected {$field} file could not be uploaded.");
        }

        if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new RuntimeException("The selected {$field} file must be 10 MB or smaller.");
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException("The selected {$field} file is invalid.");
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new RuntimeException("The selected {$field} file type is not supported.");
        }

        $url = CloudinaryService::upload($file, 'grm_inventory/assets', $resourceType);
        if (empty($url)) {
            throw new RuntimeException("The selected {$field} file could not be saved. Check the Cloudinary configuration and try again.");
        }

        return $url;
    }

    /**
     * Parse unit filter from query string.
     */
    private function parseUnitFilter(): array {
        $unit = $_GET['unit'] ?? 'ALL';
        $locationType = null;
        $locationId = null;

        if (str_starts_with($unit, 'PU_')) {
            $locationType = 'PRODUCTION';
            $locationId = (int)substr($unit, 3);
        } elseif ($unit === 'PRODUCTION_ALL') {
            $locationType = 'PRODUCTION';
        } elseif (str_starts_with($unit, 'WH_')) {
            $locationType = 'WAREHOUSE';
            $locationId = (int)substr($unit, 3);
        } elseif ($unit === 'WAREHOUSE_ALL') {
            $locationType = 'WAREHOUSE';
        } elseif (str_starts_with($unit, 'SH_')) {
            $locationType = 'SHOP';
            $locationId = (int)substr($unit, 3);
        } elseif ($unit === 'SHOP_ALL') {
            $locationType = 'SHOP';
        } elseif ($unit === 'OFFICE') {
            $locationType = 'OFFICE';
            $locationId = 1;
        }

        return [
            'unitKey'      => $unit,
            'locationType' => $locationType,
            'locationId'   => $locationId,
        ];
    }

    /**
     * Asset Analytics & Visual Graphs Dashboard.
     */
    public function dashboard(): void {
        $unitFilter = $this->parseUnitFilter();
        $metrics = AssetService::getDashboardMetrics($unitFilter['locationType'], $unitFilter['locationId']);
        $categories = AssetService::getCategories();
        $unitsMap = AssetService::getLocationUnitsMap();
        $recentAssets = AssetService::getAssetsList(['limit' => 8]);

        Response::view('assets.dashboard', [
            'pageTitle'     => 'Asset Analytics & Operations Dashboard',
            'activeModule'  => 'assets',
            'activeMenu'    => 'asset-dashboard',
            'metrics'       => $metrics,
            'categories'    => $categories,
            'unitsMap'      => $unitsMap,
            'recentAssets'  => $recentAssets,
            'currentUnit'   => $unitFilter['unitKey'],
            'breadcrumb'    => [
                ['label' => 'Assets', 'url' => url('asset/dashboard')],
                ['label' => 'Dashboard & Graph Analytics'],
            ],
        ]);
    }

    /**
     * Main Asset Hub & Register View (List Style & Quick Reference).
     */
    public function index(): void {
        $unitFilter = $this->parseUnitFilter();
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $status = !empty($_GET['status']) ? trim($_GET['status']) : null;
        $condition = !empty($_GET['condition']) ? trim($_GET['condition']) : null;
        $search = !empty($_GET['search']) ? trim($_GET['search']) : null;

        $filters = [
            'location_type'   => $unitFilter['locationType'],
            'location_id'     => $unitFilter['locationId'],
            'category_id'     => $categoryId,
            'status'          => $status,
            'condition_state' => $condition,
            'search'          => $search,
        ];

        $metrics = AssetService::getDashboardMetrics($unitFilter['locationType'], $unitFilter['locationId']);
        $assets = AssetService::getAssetsList($filters);
        $categories = AssetService::getCategories();
        $unitsMap = AssetService::getLocationUnitsMap();

        Response::view('assets.index', [
            'pageTitle'     => 'Enterprise Asset Management',
            'activeModule'  => 'assets',
            'activeMenu'    => 'asset-list',
            'metrics'       => $metrics,
            'assets'        => $assets,
            'categories'    => $categories,
            'unitsMap'      => $unitsMap,
            'currentUnit'   => $unitFilter['unitKey'],
            'filters'       => $filters,
            'breadcrumb'    => [
                ['label' => 'Assets', 'url' => url('asset/list')],
                ['label' => 'Overview & Register'],
            ],
        ]);
    }

    /**
     * Dedicated QR & Multi-Tag Scanner Station View.
     */
    public function scanner(): void {
        $categories = AssetService::getCategories();
        $sampleAssets = AssetService::getAssetsList(['limit' => 12]);
        $unitsMap = AssetService::getLocationUnitsMap();

        Response::view('assets.scanner', [
            'pageTitle'    => 'Asset QR & Tag Scanner Station',
            'activeModule' => 'assets',
            'activeMenu'   => 'asset-scanner',
            'categories'   => $categories,
            'sampleAssets' => $sampleAssets,
            'unitsMap'     => $unitsMap,
            'breadcrumb'   => [
                ['label' => 'Assets', 'url' => url('asset/list')],
                ['label' => 'QR & Tag Scanner Station'],
            ],
        ]);
    }

    /**
     * Asset Creation Form.
     */
    public function create(): void {
        $categories = AssetService::getCategories();
        $unitsMap = AssetService::getLocationUnitsMap();
        $defaultType = $_GET['type'] ?? 'PRODUCTION';
        $defaultId = $_GET['id'] ?? '1';

        Response::view('assets.create', [
            'pageTitle'    => 'Register New Enterprise Asset',
            'activeModule' => 'assets',
            'activeMenu'   => 'asset-create',
            'categories'   => $categories,
            'unitsMap'     => $unitsMap,
            'defaultType'  => $defaultType,
            'defaultId'    => $defaultId,
            'breadcrumb'   => [
                ['label' => 'Assets', 'url' => url('asset/list')],
                ['label' => 'New Registration'],
            ],
        ]);
    }

    /**
     * Store Newly Registered Asset.
     */
    public function store(): void {
        CSRF::validate();

        $user = Session::user();
        $userId = $user['id'] ?? null;

        $data = [
            'name'              => trim($_POST['name'] ?? ''),
            'category_id'       => (int)($_POST['category_id'] ?? 1),
            'location_type'     => $_POST['location_type'] ?? 'PRODUCTION',
            'location_id'       => (int)($_POST['location_id'] ?? 1),
            'floor_area'        => trim($_POST['floor_area'] ?? ''),
            'brand'             => trim($_POST['brand'] ?? ''),
            'model_number'      => trim($_POST['model_number'] ?? ''),
            'serial_number'     => trim($_POST['serial_number'] ?? ''),
            'asset_code'        => trim($_POST['asset_code'] ?? ''),
            'barcode_qr'        => trim($_POST['barcode_qr'] ?? ''),
            'specifications'    => trim($_POST['specifications'] ?? ''),
            'purchase_date'     => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
            'purchase_cost'     => (float)($_POST['purchase_cost'] ?? 0.00),
            'vendor_name'       => trim($_POST['vendor_name'] ?? ''),
            'vendor_contact'    => trim($_POST['vendor_contact'] ?? ''),
            'invoice_number'    => trim($_POST['invoice_number'] ?? ''),
            'warranty_expiry'   => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
            'useful_life_years' => (int)($_POST['useful_life_years'] ?? 5),
            'salvage_value'     => (float)($_POST['salvage_value'] ?? 0.00),
            'status'            => $_POST['status'] ?? 'IN_USE',
            'condition_state'   => $_POST['condition_state'] ?? 'GOOD',
            'custodian_name'    => trim($_POST['custodian_name'] ?? ''),
            'notes'             => trim($_POST['notes'] ?? ''),
            'photo_url'         => null,
            'invoice_file_url'  => null,
        ];

        try {
            $data['photo_url'] = $this->uploadOptionalAssetFile(
                'photo',
                ['image/jpeg', 'image/png', 'image/webp'],
                'image'
            );
            $data['invoice_file_url'] = $this->uploadOptionalAssetFile(
                'invoice_file',
                ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                'auto'
            );

            $assetId = AssetService::createAsset($data, $userId);
            Session::flash('success', "Asset registered successfully with tag {$data['name']}!");
            Response::redirect(url('asset/view/' . $assetId));
        } catch (Exception $e) {
            Session::flash('error', "Failed to register asset: " . $e->getMessage());
            Response::redirect(url('asset/create'));
        }
    }

    /**
     * Asset 360 Profile View.
     */
    public function view(string $id): void {
        $asset = AssetService::getAssetById((int)$id);
        if (!$asset) {
            Response::error(404, "Asset #{$id} not found.");
            return;
        }

        $categories = AssetService::getCategories();
        $unitsMap = AssetService::getLocationUnitsMap();

        Response::view('assets.view', [
            'pageTitle'    => "{$asset['asset_code']} - {$asset['name']}",
            'activeModule' => 'assets',
            'activeMenu'   => 'asset-list',
            'asset'        => $asset,
            'categories'   => $categories,
            'unitsMap'     => $unitsMap,
            'breadcrumb'   => [
                ['label' => 'Assets', 'url' => url('asset/list')],
                ['label' => $asset['asset_code']],
            ],
        ]);
    }

    /**
     * Edit Asset Form.
     */
    public function edit(string $id): void {
        $asset = AssetService::getAssetById((int)$id);
        if (!$asset) {
            Response::error(404, "Asset #{$id} not found.");
            return;
        }

        $categories = AssetService::getCategories();
        $unitsMap = AssetService::getLocationUnitsMap();

        Response::view('assets.edit', [
            'pageTitle'    => "Edit Asset - {$asset['asset_code']}",
            'activeModule' => 'assets',
            'activeMenu'   => 'asset-list',
            'asset'        => $asset,
            'categories'   => $categories,
            'unitsMap'     => $unitsMap,
            'breadcrumb'   => [
                ['label' => 'Assets', 'url' => url('asset/list')],
                ['label' => $asset['asset_code'], 'url' => url('asset/view/' . $asset['id'])],
                ['label' => 'Edit'],
            ],
        ]);
    }

    /**
     * Update Existing Asset.
     */
    public function update(string $id): void {
        CSRF::validate();

        $user = Session::user();
        $userId = $user['id'] ?? null;
        $assetId = (int)$id;

        $data = [
            'name'              => trim($_POST['name'] ?? ''),
            'category_id'       => (int)($_POST['category_id'] ?? 1),
            'location_type'     => $_POST['location_type'] ?? 'PRODUCTION',
            'location_id'       => (int)($_POST['location_id'] ?? 1),
            'floor_area'        => trim($_POST['floor_area'] ?? ''),
            'brand'             => trim($_POST['brand'] ?? ''),
            'model_number'      => trim($_POST['model_number'] ?? ''),
            'serial_number'     => trim($_POST['serial_number'] ?? ''),
            'specifications'    => trim($_POST['specifications'] ?? ''),
            'purchase_date'     => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
            'purchase_cost'     => (float)($_POST['purchase_cost'] ?? 0.00),
            'vendor_name'       => trim($_POST['vendor_name'] ?? ''),
            'vendor_contact'    => trim($_POST['vendor_contact'] ?? ''),
            'invoice_number'    => trim($_POST['invoice_number'] ?? ''),
            'warranty_expiry'   => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
            'useful_life_years' => (int)($_POST['useful_life_years'] ?? 5),
            'salvage_value'     => (float)($_POST['salvage_value'] ?? 0.00),
            'status'            => $_POST['status'] ?? 'IN_USE',
            'condition_state'   => $_POST['condition_state'] ?? 'GOOD',
            'custodian_name'    => trim($_POST['custodian_name'] ?? ''),
            'notes'             => trim($_POST['notes'] ?? ''),
            'photo_url'         => null,
            'invoice_file_url'  => null,
        ];

        try {
            $data['photo_url'] = $this->uploadOptionalAssetFile(
                'photo',
                ['image/jpeg', 'image/png', 'image/webp'],
                'image'
            );
            $data['invoice_file_url'] = $this->uploadOptionalAssetFile(
                'invoice_file',
                ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                'auto'
            );

            AssetService::updateAsset($assetId, $data, $userId);
            Session::flash('success', "Asset {$assetId} updated successfully!");
            Response::redirect(url('asset/view/' . $assetId));
        } catch (Exception $e) {
            Session::flash('error', "Failed to update asset: " . $e->getMessage());
            Response::redirect(url('asset/edit/' . $assetId));
        }
    }

    /**
     * Delete Asset.
     */
    public function delete(string $id): void {
        CSRF::validate();
        try {
            AssetService::deleteAsset((int)$id);
            Session::flash('success', "Asset deleted successfully.");
        } catch (Exception $e) {
            Session::flash('error', "Failed to delete asset: " . $e->getMessage());
        }
        Response::redirect(url('asset/list'));
    }

    /**
     * Inter-Unit Transfers Register.
     */
    public function transfers(): void {
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $transfers = AssetService::getAllTransfers([
            'status' => $status,
            'search' => $search
        ]);
        $unitsMap = AssetService::getLocationUnitsMap();
        $allAssets = AssetService::getAssetsList();

        Response::view('assets.transfers', [
            'pageTitle'    => 'Inter-Unit Asset Movement & Transfers',
            'activeModule' => 'assets',
            'activeMenu'   => 'asset-transfers',
            'transfers'    => $transfers,
            'unitsMap'     => $unitsMap,
            'allAssets'    => $allAssets,
            'breadcrumb'   => [
                ['label' => 'Assets', 'url' => url('asset/list')],
                ['label' => 'Inter-Unit Transfers'],
            ],
        ]);
    }

    /**
     * Execute Inter-Unit Transfer.
     */
    public function storeTransfer(): void {
        CSRF::validate();
        $user = Session::user();
        $userId = $user['id'] ?? null;

        $targetUnit = $_POST['target_unit'] ?? 'PRODUCTION_1';
        $toLocationType = 'PRODUCTION';
        $toLocationId = 1;

        if (str_starts_with($targetUnit, 'PU_')) {
            $toLocationType = 'PRODUCTION';
            $toLocationId = (int)substr($targetUnit, 3);
        } elseif (str_starts_with($targetUnit, 'WH_')) {
            $toLocationType = 'WAREHOUSE';
            $toLocationId = (int)substr($targetUnit, 3);
        } elseif (str_starts_with($targetUnit, 'SH_')) {
            $toLocationType = 'SHOP';
            $toLocationId = (int)substr($targetUnit, 3);
        } elseif ($targetUnit === 'OFFICE_1' || $targetUnit === 'OFFICE') {
            $toLocationType = 'OFFICE';
            $toLocationId = 1;
        }

        $data = [
            'asset_id'         => (int)($_POST['asset_id'] ?? 0),
            'to_location_type' => $toLocationType,
            'to_location_id'   => $toLocationId,
            'to_floor_area'    => trim($_POST['to_floor_area'] ?? ''),
            'reason'           => trim($_POST['reason'] ?? ''),
            'notes'            => trim($_POST['notes'] ?? ''),
        ];

        try {
            $transferId = AssetService::transferAsset($data, $userId);
            Session::flash('success', "Asset transferred successfully! Movement manifest logged.");
        } catch (Exception $e) {
            Session::flash('error', "Transfer failed: " . $e->getMessage());
        }

        $redirect = $_POST['redirect_back'] ?? url('asset/transfers');
        Response::redirect($redirect);
    }

    /**
     * Maintenance & Repair Tickets Register.
     */
    public function maintenance(): void {
        $status = $_GET['status'] ?? '';
        $serviceType = $_GET['service_type'] ?? '';
        $search = $_GET['search'] ?? '';

        $logs = AssetService::getAllMaintenanceLogs([
            'status'       => $status,
            'service_type' => $serviceType,
            'search'       => $search
        ]);
        $allAssets = AssetService::getAssetsList();
        $unitsMap = AssetService::getLocationUnitsMap();
        $categories = AssetService::getCategories();

        Response::view('assets.maintenance', [
            'pageTitle'    => 'Asset Maintenance, Repairs & AMC',
            'activeModule' => 'assets',
            'activeMenu'   => 'asset-maintenance',
            'logs'         => $logs,
            'allAssets'    => $allAssets,
            'unitsMap'     => $unitsMap,
            'categories'   => $categories,
            'breadcrumb'   => [
                ['label' => 'Assets', 'url' => url('asset/list')],
                ['label' => 'Maintenance & Service Tickets'],
            ],
        ]);
    }

    /**
     * Record Maintenance Ticket.
     */
    public function storeMaintenance(): void {
        CSRF::validate();
        $user = Session::user();
        $userId = $user['id'] ?? null;

        $data = [
            'asset_id'            => (int)($_POST['asset_id'] ?? 0),
            'service_type'        => $_POST['service_type'] ?? 'ROUTINE_PREVENTIVE',
            'issue_description'   => trim($_POST['issue_description'] ?? ''),
            'action_taken'        => trim($_POST['action_taken'] ?? ''),
            'serviced_by_vendor'  => trim($_POST['serviced_by_vendor'] ?? ''),
            'technician_name'     => trim($_POST['technician_name'] ?? ''),
            'technician_contact'  => trim($_POST['technician_contact'] ?? ''),
            'service_cost'        => (float)($_POST['service_cost'] ?? 0.00),
            'parts_replaced'      => trim($_POST['parts_replaced'] ?? ''),
            'service_date'        => !empty($_POST['service_date']) ? $_POST['service_date'] : date('Y-m-d'),
            'next_due_date'       => !empty($_POST['next_due_date']) ? $_POST['next_due_date'] : null,
            'downtime_hours'      => (float)($_POST['downtime_hours'] ?? 0.0),
            'status'              => $_POST['status'] ?? 'COMPLETED',
            'asset_status'        => $_POST['asset_status'] ?? 'IN_USE',
            'condition_state'     => $_POST['condition_state'] ?? null,
        ];

        try {
            $logId = AssetService::recordMaintenance($data, $userId);
            Session::flash('success', "Maintenance record saved successfully!");
        } catch (Exception $e) {
            Session::flash('error', "Failed to log maintenance: " . $e->getMessage());
        }

        $redirect = $_POST['redirect_back'] ?? url('asset/maintenance');
        Response::redirect($redirect);
    }

    /**
     * Single Thermal Sticker QR Label Print Sheet (50x25mm / 80mm).
     */
    public function label(string $id): void {
        $asset = AssetService::getAssetById((int)$id);
        if (!$asset) {
            Response::error(404, "Asset #{$id} not found.");
            return;
        }

        Response::viewOnly('assets.label', [
            'asset' => $asset,
        ]);
    }

    /**
     * Bulk Multi-Label Printable QR Sheet & Generator Area.
     */
    public function bulkLabels(): void {
        $unitFilter = $this->parseUnitFilter();
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $status = !empty($_GET['status']) ? trim($_GET['status']) : null;
        $search = !empty($_GET['search']) ? trim($_GET['search']) : null;
        $columns = !empty($_GET['columns']) ? (int)$_GET['columns'] : 3;

        $filters = [
            'location_type' => $unitFilter['locationType'],
            'location_id'   => $unitFilter['locationId'],
            'category_id'   => $categoryId,
            'status'        => $status,
            'search'        => $search,
        ];

        $assets = AssetService::getAssetsList($filters);
        $categories = AssetService::getCategories();
        $unitsMap = AssetService::getLocationUnitsMap();

        if (($_GET['mode'] ?? '') === 'print') {
            Response::viewOnly('assets.bulk_labels', [
                'assets'      => $assets,
                'categories'  => $categories,
                'unitsMap'    => $unitsMap,
                'currentUnit' => $unitFilter['unitKey'],
                'filters'     => $filters,
                'columns'     => $columns,
            ]);
            return;
        }

        Response::view('assets.qr_list', [
            'pageTitle'   => 'Asset QR Register',
            'activeModule'=> 'assets',
            'activeMenu'  => 'asset-bulk-labels',
            'assets'      => $assets,
            'categories'  => $categories,
            'unitsMap'    => $unitsMap,
            'currentUnit' => $unitFilter['unitKey'],
            'filters'     => $filters,
            'columns'     => $columns,
            'breadcrumb'  => [
                ['label' => 'Assets', 'url' => url('asset/list')],
                ['label' => 'QR Register'],
            ],
        ]);
    }

    /**
     * Live QR / Code / Tag Scan Lookup (AJAX JSON).
     */
    public function ajaxLookup(): void {
        $code = trim($_GET['code'] ?? $_POST['code'] ?? $_GET['query'] ?? $_POST['query'] ?? '');
        if (empty($code)) {
            Response::json(['success' => false, 'message' => 'Empty code or tag number provided.'], 400);
            return;
        }

        $asset = AssetService::findByCodeOrQR($code);
        $matches = AssetService::searchMultiTags($code, 8);

        if (!$asset && empty($matches)) {
            Response::json([
                'success' => false,
                'message' => "No asset found matching QR, Tag, or Serial Number: '{$code}'.",
                'query'   => $code
            ], 404);
            return;
        }

        Response::json([
            'success' => true,
            'asset'   => $asset ?: ($matches[0] ?? null),
            'matches' => $matches,
            'count'   => count($matches),
            'query'   => $code
        ]);
    }

    /**
     * Dynamically Create New Asset Category (AJAX JSON).
     */
    public function ajaxCreateCategory(): void {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $cat = AssetService::createCategory($input);

            Response::json([
                'success'  => true,
                'message'  => "Category '{$cat['name']}' created successfully.",
                'category' => $cat
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Dynamically Create New Operational Facility Unit (AJAX JSON).
     */
    public function ajaxCreateUnit(): void {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $type = $input['type'] ?? 'PRODUCTION';
            $unit = AssetService::createFacilityUnit($type, $input);

            Response::json([
                'success' => true,
                'message' => "Facility '{$unit['name']}' created successfully.",
                'unit'    => $unit
            ]);
        } catch (Exception $e) {
            Response::json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
