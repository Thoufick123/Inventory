<?php
/**
 * Master Controller
 * Handles master data management including Warehouse Locations (Racks, Shelves, Bins),
 * Categories, Products, Brands, Sizes, Colors, Sellers, and Units.
 */

require_once BASE_PATH . '/app/Models/Warehouse.php';
require_once BASE_PATH . '/app/Models/Location.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class MasterController {

    // =========================================================================
    // LOCATIONS (Warehouse Racks, Shelves, Bins)
    // =========================================================================

    public function locations(): void {
        $scopedWarehouseId = function_exists('currentWarehouseId') ? currentWarehouseId() : null;
        $warehouseId = $scopedWarehouseId !== null ? (string)$scopedWarehouseId : ($_GET['warehouse_id'] ?? '');
        $search = $_GET['search'] ?? '';

        $filters = [];
        if (!empty($warehouseId)) $filters['warehouse_id'] = $warehouseId;
        if (!empty($search)) $filters['search'] = $search;

        $locations = Location::getAll($filters);
        $warehouses = function_exists('getScopedWarehouses') ? getScopedWarehouses() : Warehouse::getAll(['is_active' => 1]);

        Response::view('master.locations.index', [
            'pageTitle'          => 'Warehouse Locations (Racks & Shelves)',
            'activeModule'       => 'master',
            'activeMenu'         => 'locations',
            'locations'          => $locations,
            'warehouses'         => $warehouses,
            'warehouseId'        => $warehouseId,
            'scopedWarehouseId'  => $scopedWarehouseId,
            'search'             => $search,
            'breadcrumb'         => [
                ['label' => 'Master Data', 'url' => url('master/locations')],
                ['label' => 'Locations'],
            ],
        ]);
    }

    public function storeLocation(): void {
        try {
            $scopedWarehouseId = function_exists('currentWarehouseId') ? currentWarehouseId() : null;
            $whId = $scopedWarehouseId !== null ? $scopedWarehouseId : (int)$_POST['warehouse_id'];

            $data = [
                'warehouse_id' => $whId,
                'rack'         => trim($_POST['rack'] ?? ''),
                'shelf'        => trim($_POST['shelf'] ?? ''),
                'bin'          => trim($_POST['bin'] ?? ''),
                'label'        => trim($_POST['label'] ?? ''),
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ];

            if (empty($data['warehouse_id']) || empty($data['rack'])) {
                Response::redirect(url('master/locations'), 'error', 'Warehouse and Rack are required.');
            }

            $id = Location::create($data);
            AuditService::log('master', 'create', 'locations', $id, null, $data);
            Response::redirect(url('master/locations'), 'success', 'Storage location added successfully!');
        } catch (Exception $e) {
            Response::redirect(url('master/locations'), 'error', 'Error creating location: ' . $e->getMessage());
        }
    }

    public function updateLocation(string $id): void {
        $locId = (int)$id;
        try {
            $scopedWarehouseId = function_exists('currentWarehouseId') ? currentWarehouseId() : null;
            $whId = $scopedWarehouseId !== null ? $scopedWarehouseId : (int)$_POST['warehouse_id'];

            $data = [
                'warehouse_id' => $whId,
                'rack'         => trim($_POST['rack'] ?? ''),
                'shelf'        => trim($_POST['shelf'] ?? ''),
                'bin'          => trim($_POST['bin'] ?? ''),
                'label'        => trim($_POST['label'] ?? ''),
                'is_active'    => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ];

            Location::update($locId, $data);
            AuditService::log('master', 'edit', 'locations', $locId, null, $data);
            Response::redirect(url('master/locations'), 'success', 'Location updated successfully!');
        } catch (Exception $e) {
            Response::redirect(url('master/locations'), 'error', 'Error updating location: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // OTHER MASTER DATA DELEGATIONS
    // =========================================================================

    public function categories(): void { Response::redirect(url('production/catalog')); }
    public function createCategory(): void { Response::redirect(url('production/catalog')); }
    public function storeCategory(): void { Response::redirect(url('production/catalog')); }
    public function editCategory(string $id): void { Response::redirect(url('production/catalog')); }
    public function updateCategory(string $id): void { Response::redirect(url('production/catalog')); }
    public function deleteCategory(string $id): void { Response::redirect(url('production/catalog')); }

    public function subcategories(): void { Response::redirect(url('production/catalog')); }
    public function createSubcategory(): void { Response::redirect(url('production/catalog')); }
    public function storeSubcategory(): void { Response::redirect(url('production/catalog')); }
    public function editSubcategory(string $id): void { Response::redirect(url('production/catalog')); }
    public function updateSubcategory(string $id): void { Response::redirect(url('production/catalog')); }

    public function products(): void { Response::redirect(url('production/catalog')); }
    public function createProduct(): void { Response::redirect(url('production/catalog')); }
    public function storeProduct(): void { Response::redirect(url('production/catalog')); }
    public function editProduct(string $id): void { Response::redirect(url('production/catalog')); }
    public function updateProduct(string $id): void { Response::redirect(url('production/catalog')); }
    public function viewProduct(string $id): void { Response::redirect(url('production/catalog')); }
    public function deleteProduct(string $id): void { Response::redirect(url('production/catalog')); }

    public function variants(string $id): void { Response::redirect(url('production/catalog')); }
    public function storeVariant(string $id): void { Response::redirect(url('production/catalog')); }
    public function updateVariant(string $id): void { Response::redirect(url('production/catalog')); }
    public function deleteVariant(string $id): void { Response::redirect(url('production/catalog')); }

    public function brands(): void { Response::redirect(url('production/catalog')); }
    public function storeBrand(): void { Response::redirect(url('production/catalog')); }
    public function updateBrand(string $id): void { Response::redirect(url('production/catalog')); }

    public function sizes(): void { Response::redirect(url('production/catalog')); }
    public function storeSize(): void { Response::redirect(url('production/catalog')); }
    public function updateSize(string $id): void { Response::redirect(url('production/catalog')); }

    public function colors(): void { Response::redirect(url('production/catalog')); }
    public function storeColor(): void { Response::redirect(url('production/catalog')); }
    public function updateColor(string $id): void { Response::redirect(url('production/catalog')); }

    public function sellers(): void { Response::redirect(url('production/sellers')); }
    public function createSeller(): void { Response::redirect(url('production/sellers/create')); }
    public function storeSeller(): void { Response::redirect(url('production/sellers')); }
    public function editSeller(string $id): void { Response::redirect(url('production/sellers/edit/' . $id)); }
    public function updateSeller(string $id): void { Response::redirect(url('production/sellers')); }

    public function customers(): void { Response::redirect(url('admin/dashboard'), 'info', 'Customer management'); }
    public function createCustomer(): void { Response::redirect(url('admin/dashboard')); }
    public function storeCustomer(): void { Response::redirect(url('admin/dashboard')); }
    public function editCustomer(string $id): void { Response::redirect(url('admin/dashboard')); }
    public function updateCustomer(string $id): void { Response::redirect(url('admin/dashboard')); }

    public function units(): void { Response::redirect(url('production/catalog')); }
    public function storeUnit(): void { Response::redirect(url('production/catalog')); }
    public function updateUnit(string $id): void { Response::redirect(url('production/catalog')); }
}
