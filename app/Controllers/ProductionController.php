<?php
/**
 * Production Controller
 * Handles all production submodules: Dashboard, Sellers, Catalog, Programs,
 * Cutting, Stitching, Labels, QC, Packing Batches, Outward, and Reports.
 */

require_once BASE_PATH . '/app/Services/ProductionService.php';
require_once BASE_PATH . '/app/Services/AuditService.php';
require_once BASE_PATH . '/app/Services/CloudinaryService.php';
require_once BASE_PATH . '/app/Models/QCBatch.php';
require_once BASE_PATH . '/app/Models/QCItem.php';
require_once BASE_PATH . '/app/Models/LabelBatch.php';

class ProductionController {

    /**
     * Production Dashboard.
     */
    public function dashboard(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $stats = ProductionService::getDashboardStats($scopedUnitId);
        Response::view('production.dashboard', array_merge($stats, [
            'pageTitle'    => 'Production Dashboard',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-dashboard',
            'scopedUnitId' => $scopedUnitId,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Dashboard'],
            ],
        ]));
    }

    // =========================================================================
    // SELLERS (Material Suppliers)
    // =========================================================================

    public function sellerList(): void {
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $city = trim($_GET['city'] ?? '');
        $materialType = trim($_GET['material_type'] ?? '');

        $sql = "SELECT s.* FROM sellers s WHERE s.seller_type = 'MATERIAL_SUPPLIER'";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                s.name LIKE ? 
                OR s.company LIKE ? 
                OR s.phone LIKE ? 
                OR s.city LIKE ? 
                OR s.state LIKE ? 
                OR s.gst_number LIKE ? 
                OR s.material_type LIKE ? 
                OR s.colors LIKE ?
                OR s.payment_terms LIKE ?
                OR EXISTS (
                    SELECT 1 FROM seller_rolls sr 
                    WHERE sr.seller_id = s.id 
                    AND (sr.roll_number LIKE ? OR sr.color LIKE ? OR sr.design_name LIKE ? OR sr.notes LIKE ?)
                )
            )";
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s, $s, $s, $s, $s, $s]);
        }

        if ($status !== '') {
            $sql .= " AND s.is_active = ?";
            $params[] = (int)$status;
        }

        if ($city !== '') {
            $sql .= " AND s.city = ?";
            $params[] = $city;
        }

        if ($materialType !== '') {
            $sql .= " AND s.material_type = ?";
            $params[] = $materialType;
        }

        $sql .= " ORDER BY s.created_at DESC";
        $sellers = Database::fetchAll($sql, $params);

        foreach ($sellers as &$sItem) {
            $sItem['rolls'] = Database::fetchAll(
                "SELECT sr.*,
                        COALESCE(u.used_quantity, 0) as used_quantity,
                        COALESCE(u.used_weight, 0) as used_weight,
                        COALESCE(u.used_price, 0) as used_price,
                        COALESCE(dmg.damaged_quantity, 0) as damaged_quantity,
                        COALESCE(dmg.damaged_weight, 0) as damaged_weight,
                        COALESCE(dmg.damaged_price, 0) as damaged_price,
                        GREATEST(0, sr.quantity - COALESCE(u.used_quantity, 0) - COALESCE(dmg.damaged_quantity, 0)) as available_quantity,
                        GREATEST(0.0, sr.weight - COALESCE(u.used_weight, 0) - COALESCE(dmg.damaged_weight, 0)) as available_weight,
                        GREATEST(0.0, sr.price - COALESCE(u.used_price, 0) - COALESCE(dmg.damaged_price, 0)) as available_price,
                        dmg.damage_reason,
                        dmg.damage_status
                 FROM seller_rolls sr
                 LEFT JOIN (
                     SELECT ppr.seller_roll_id, 
                            SUM(ppr.roll_qty) as used_quantity,
                            SUM(ppr.roll_weight) as used_weight,
                            SUM(ppr.roll_price) as used_price
                     FROM production_program_rolls ppr
                     JOIN production_programs pp ON ppr.production_program_id = pp.id
                     WHERE pp.is_active = 1 AND pp.status != 'CANCELLED'
                     GROUP BY ppr.seller_roll_id
                 ) u ON sr.id = u.seller_roll_id
                 LEFT JOIN (
                     SELECT rd.seller_roll_id,
                            SUM(rd.damaged_quantity) as damaged_quantity,
                            SUM(rd.damaged_weight) as damaged_weight,
                            SUM(rd.loss_amount) as damaged_price,
                            MAX(rd.damage_reason) as damage_reason,
                            MAX(rd.status) as damage_status
                     FROM roll_damages rd
                     WHERE rd.status NOT IN ('CLOSED', 'CANCELLED')
                     GROUP BY rd.seller_roll_id
                 ) dmg ON sr.id = dmg.seller_roll_id
                 WHERE sr.seller_id = ?
                 ORDER BY sr.id ASC",
                [$sItem['id']]
            );
        }
        unset($sItem);

        // Fetch distinct filter values
        $cities = Database::fetchAll("SELECT DISTINCT city FROM sellers WHERE seller_type = 'MATERIAL_SUPPLIER' AND city IS NOT NULL AND TRIM(city) != '' ORDER BY city ASC");
        $materialTypes = Database::fetchAll("SELECT DISTINCT material_type FROM sellers WHERE seller_type = 'MATERIAL_SUPPLIER' AND material_type IS NOT NULL AND TRIM(material_type) != '' ORDER BY material_type ASC");

        Response::view('production.sellers.index', [
            'pageTitle'      => 'Production Material Suppliers',
            'activeModule'   => 'production',
            'activeMenu'     => 'prod-sellers',
            'sellers'        => $sellers,
            'search'         => $search,
            'status'         => $status,
            'city'           => $city,
            'materialType'   => $materialType,
            'cities'         => $cities,
            'materialTypes'  => $materialTypes,
            'breadcrumb'     => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Suppliers'],
            ],
        ]);
    }

    public function createSeller(): void {
        Response::view('production.sellers.create', [
            'pageTitle'    => 'Add Material Supplier',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-sellers',
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Suppliers', 'url' => url('production/sellers')],
                ['label' => 'Add Supplier'],
            ],
        ]);
    }

    public function viewSeller(string $id): void {
        $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ? AND seller_type = 'MATERIAL_SUPPLIER'", [(int)$id]);
        if (!$seller) {
            Response::error(404, 'Material Supplier not found.');
        }

        $rolls = Database::fetchAll(
            "SELECT sr.*,
                    COALESCE(u.used_quantity, 0) as used_quantity,
                    COALESCE(u.used_weight, 0) as used_weight,
                    COALESCE(u.used_price, 0) as used_price,
                    COALESCE(dmg.damaged_quantity, 0) as damaged_quantity,
                    COALESCE(dmg.damaged_weight, 0) as damaged_weight,
                    COALESCE(dmg.damaged_price, 0) as damaged_price,
                    GREATEST(0, sr.quantity - COALESCE(u.used_quantity, 0) - COALESCE(dmg.damaged_quantity, 0)) as available_quantity,
                    GREATEST(0.0, sr.weight - COALESCE(u.used_weight, 0) - COALESCE(dmg.damaged_weight, 0)) as available_weight,
                    GREATEST(0.0, sr.price - COALESCE(u.used_price, 0) - COALESCE(dmg.damaged_price, 0)) as available_price,
                    dmg.damage_reason,
                    dmg.damage_status,
                    dmg.damage_id
             FROM seller_rolls sr
             LEFT JOIN (
                 SELECT ppr.seller_roll_id, 
                        SUM(ppr.roll_qty) as used_quantity,
                        SUM(ppr.roll_weight) as used_weight,
                        SUM(ppr.roll_price) as used_price
                 FROM production_program_rolls ppr
                 JOIN production_programs pp ON ppr.production_program_id = pp.id
                 WHERE pp.is_active = 1 AND pp.status != 'CANCELLED'
                 GROUP BY ppr.seller_roll_id
             ) u ON sr.id = u.seller_roll_id
             LEFT JOIN (
                 SELECT rd.seller_roll_id,
                        SUM(rd.damaged_quantity) as damaged_quantity,
                        SUM(rd.damaged_weight) as damaged_weight,
                        SUM(rd.loss_amount) as damaged_price,
                        MAX(rd.damage_reason) as damage_reason,
                        MAX(rd.status) as damage_status,
                        MAX(rd.id) as damage_id
                 FROM roll_damages rd
                 WHERE rd.status NOT IN ('CLOSED', 'CANCELLED')
                 GROUP BY rd.seller_roll_id
             ) dmg ON sr.id = dmg.seller_roll_id
             WHERE sr.seller_id = ?
             ORDER BY sr.id ASC",
            [(int)$id]
        );

        Response::view('production.sellers.view', [
            'pageTitle'    => 'Supplier Details — ' . ($seller['name'] ?? ''),
            'activeModule' => 'production',
            'activeMenu'   => 'prod-sellers',
            'seller'       => $seller,
            'rolls'        => $rolls,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Suppliers', 'url' => url('production/sellers')],
                ['label' => $seller['name']],
            ],
        ]);
    }

    public function storeSeller(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $validator = Validator::make($_POST, [
            'name'          => 'required|minLength:2|maxLength:200',
            'company'       => 'required|minLength:2|maxLength:300',
            'phone'         => 'required',
            'email'         => 'required|maxLength:255',
            'city'          => 'required|maxLength:100',
            'state'         => 'required|maxLength:100',
            'address'       => 'required|maxLength:500',
            'material_type' => 'required|maxLength:150',
            'gst_number'    => 'maxLength:20',
            'payment_terms' => 'maxLength:200',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::back();
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        if (preg_match('/[0-9]/', $name)) {
            Session::flash('error', 'Supplier Name cannot contain numbers. Only alphabetic characters allowed.');
            Response::back();
        }

        $company = mb_strtoupper(trim($_POST['company'] ?? ''));
        if (preg_match('/[0-9]/', $company)) {
            Session::flash('error', 'Company / Mill Name cannot contain numbers.');
            Response::back();
        }

        $phone = trim($_POST['phone'] ?? '');
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            Session::flash('error', 'Phone number must be exactly 10 digits (e.g. 9876543210).');
            Response::back();
        }

        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            Session::flash('error', 'Email address must be a valid email address (e.g. name@domain.in, name@gmail.com).');
            Response::back();
        }

        $city = mb_strtoupper(trim($_POST['city'] ?? ''));
        if (preg_match('/[0-9]/', $city)) {
            Session::flash('error', 'City / District cannot contain numbers.');
            Response::back();
        }

        $state = mb_strtoupper(trim($_POST['state'] ?? ''));
        if (preg_match('/[0-9]/', $state)) {
            Session::flash('error', 'State cannot contain numbers.');
            Response::back();
        }

        $address = mb_strtoupper(trim($_POST['address'] ?? ''));
        $materialType = mb_strtoupper(trim($_POST['material_type'] ?? ''));

        // Validate GST if provided
        $gstNumber = strtoupper(trim($_POST['gst_number'] ?? ''));
        if ($gstNumber !== '' && !preg_match('/^[0-9A-Z]{15}$/', $gstNumber)) {
            Session::flash('error', 'GST Number must be a valid 15-character alphanumeric code.');
            Response::back();
        }

        // Validate rolls are present and all fields filled
        if (empty($_POST['rolls']) || !is_array($_POST['rolls'])) {
            Session::flash('error', 'Please add at least 1 fabric roll.');
            Response::back();
        }

        foreach ($_POST['rolls'] as $rIdx => $rCheck) {
            $rNum = trim($rCheck['roll_number'] ?? '');
            $rCol = trim($rCheck['color'] ?? '');
            $rDes = trim($rCheck['design_name'] ?? '');
            $rCnt = (int)($rCheck['design_count'] ?? 0);
            $rQty = (int)($rCheck['quantity'] ?? 0);
            $rWgt = (float)($rCheck['weight'] ?? 0);
            $rPrc = (float)($rCheck['price'] ?? 0);

            if ($rNum === '' || $rCol === '' || $rDes === '' || $rCnt < 1 || $rQty < 1 || $rWgt <= 0 || $rPrc <= 0) {
                Session::flash('error', "All fields in Roll #" . ($rIdx + 1) . " (Code, Color, Design, Designs Count, Qty, Weight, Price) are required.");
                Response::back();
            }
        }

        // Enforce required Bill / Invoice / Document upload
        if (empty($_FILES['document']['name']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Tax Invoice / Bill document is required. Please choose and upload the bill file (JPG, PNG, WEBP, PDF).');
            Response::back();
            return;
        }

        $documentUrl = null;
        if (!empty($_FILES['document']['name']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $fileExt = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            $fileSize = $_FILES['document']['size'];

            if (!in_array($fileExt, $allowedExtensions, true)) {
                Session::flash('error', 'Invalid document format. Allowed formats: JPG, JPEG, PNG, WEBP, PDF.');
                Response::back();
                return;
            }

            if ($fileSize > 5 * 1024 * 1024) {
                Session::flash('error', 'Document file size must not exceed 5MB.');
                Response::back();
                return;
            }

            $documentUrl = CloudinaryService::upload($_FILES['document'], 'grm_inventory/sellers/documents', 'auto');
        }

        // Handle Fabric Roll Photo upload
        $rollPhotoUrl = null;
        if (!empty($_FILES['roll_photo']['name']) && $_FILES['roll_photo']['error'] === UPLOAD_ERR_OK) {
            $allowedImgExt = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExt = strtolower(pathinfo($_FILES['roll_photo']['name'], PATHINFO_EXTENSION));
            $fileSize = $_FILES['roll_photo']['size'];

            if (!in_array($fileExt, $allowedImgExt, true)) {
                Session::flash('error', 'Invalid roll photo format. Allowed image formats: JPG, JPEG, PNG, WEBP.');
                Response::back();
            }

            if ($fileSize > 5 * 1024 * 1024) {
                Session::flash('error', 'Roll photo file size must not exceed 5MB.');
                Response::back();
            }

            $rollPhotoUrl = CloudinaryService::upload($_FILES['roll_photo'], 'grm_inventory/sellers/rolls', 'image');
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $company = mb_strtoupper(trim($_POST['company'] ?? ''));
        $city = mb_strtoupper(trim($_POST['city'] ?? ''));
        $state = mb_strtoupper(trim($_POST['state'] ?? ''));
        $address = mb_strtoupper(trim($_POST['address'] ?? ''));
        $paymentTerms = mb_strtoupper(trim($_POST['payment_terms'] ?? ''));
        $materialType = mb_strtoupper(trim($_POST['material_type'] ?? ''));
        $colors = mb_strtoupper(trim($_POST['colors'] ?? ''));
        $rollCount = max(0, (int)($_POST['roll_count'] ?? 0));
        $colorCount = max(0, (int)($_POST['color_count'] ?? 0));
        $designCount = max(0, (int)($_POST['design_count'] ?? 0));
        $colorDesignCount = max(0, (int)($_POST['color_design_count'] ?? ($colorCount + $designCount)));
        $rollWeight = max(0.0, (float)($_POST['roll_weight'] ?? 0));
        $rollPrice = max(0.0, (float)($_POST['roll_price'] ?? 0));

        Database::execute(
            "INSERT INTO sellers (name, company, gst_number, phone, email, address, city, state, payment_terms, document_url, material_type, colors, roll_photo, roll_count, color_count, design_count, color_design_count, roll_weight, roll_price, seller_type, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'MATERIAL_SUPPLIER', 1)",
            [
                $name,
                $company,
                $gstNumber,
                $phone,
                $email,
                $address,
                $city,
                $state,
                $paymentTerms,
                $documentUrl,
                $materialType,
                $colors,
                $rollPhotoUrl,
                $rollCount,
                $colorCount,
                $designCount,
                $colorDesignCount,
                $rollWeight,
                $rollPrice,
            ]
        );

        $sellerId = (int)Database::lastInsertId();

        // Process dynamic multiple rolls if submitted
        if (!empty($_POST['rolls']) && is_array($_POST['rolls'])) {
            $computedRollCount = 0;
            $computedTotalWeight = 0.0;
            $computedTotalPrice = 0.0;
            $colorsList = [];
            $designsList = [];
            $firstRollPhoto = $rollPhotoUrl;

            $seqInfo = ProductionService::getNextFabricRollSequence(1);
            $runningSeq = (int)$seqInfo['start_seq'];
            $prefix = $seqInfo['prefix'];

            foreach ($_POST['rolls'] as $index => $r) {
                $rollNum = mb_strtoupper(trim($r['roll_number'] ?? ('ROLL #' . ($computedRollCount + 1))));
                $rollColor = mb_strtoupper(trim($r['color'] ?? ''));
                $rollDesign = mb_strtoupper(trim($r['design_name'] ?? ''));
                $rDesignCount = max(1, (int)($r['design_count'] ?? 1));
                $rQty = max(1, (int)($r['quantity'] ?? ($r['qty'] ?? 1)));
                $rWeight = max(0.0, (float)($r['weight'] ?? 0));
                $rPrice = max(0.0, (float)($r['price'] ?? 0));
                $individualPhoto = null;

                // Handle individual roll photo upload
                if (!empty($_FILES["roll_photo_{$index}"]['name']) && $_FILES["roll_photo_{$index}"]['error'] === UPLOAD_ERR_OK) {
                    $f = $_FILES["roll_photo_{$index}"];
                    $fExt = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    if (in_array($fExt, ['jpg', 'jpeg', 'png', 'webp'], true) && $f['size'] <= 5 * 1024 * 1024) {
                        $uploaded = CloudinaryService::upload($f, 'grm_inventory/sellers/rolls', 'image');
                        if ($uploaded) {
                            $individualPhoto = $uploaded;
                        }
                    }
                }

                if ($firstRollPhoto === null && $individualPhoto !== null) {
                    $firstRollPhoto = $individualPhoto;
                }

                $rollStartSeq = $runningSeq;
                $rollEndSeq = $runningSeq + $rQty - 1;
                $rollStartCode = sprintf('%s%02d', $prefix, $rollStartSeq);
                $rollEndCode = sprintf('%s%02d', $prefix, $rollEndSeq);
                $rollQrCode = ($rQty > 1) ? ($rollStartCode . '-' . $rollEndCode) : $rollStartCode;
                $runningSeq = $rollEndSeq + 1;

                Database::execute(
                    "INSERT INTO seller_rolls (seller_id, roll_number, color, design_name, design_count, quantity, weight, price, photo_url, start_seq, end_seq, start_code, end_code, qr_code)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $sellerId,
                        $rollNum,
                        $rollColor,
                        $rollDesign,
                        $rDesignCount,
                        $rQty,
                        $rWeight,
                        $rPrice,
                        $individualPhoto,
                        $rollStartSeq,
                        $rollEndSeq,
                        $rollStartCode,
                        $rollEndCode,
                        $rollQrCode,
                    ]
                );

                $computedRollCount++;
                $computedTotalWeight += $rWeight;
                $computedTotalPrice += $rPrice;
                if ($rollColor !== '') $colorsList[] = $rollColor;
                if ($rollDesign !== '') $designsList[] = $rollDesign;
            }

            $uniqueColors = array_values(array_unique(array_filter($colorsList)));
            $uniqueDesigns = array_values(array_unique(array_filter($designsList)));

            Database::execute(
                "UPDATE sellers SET 
                    roll_count = ?, 
                    color_count = ?, 
                    design_count = ?, 
                    color_design_count = ?, 
                    roll_weight = ?, 
                    roll_price = ?, 
                    colors = ?, 
                    roll_photo = COALESCE(?, roll_photo) 
                 WHERE id = ?",
                [
                    $computedRollCount,
                    count($uniqueColors),
                    count($uniqueDesigns),
                    count($uniqueColors) + count($uniqueDesigns),
                    $computedTotalWeight,
                    $computedTotalPrice,
                    !empty($uniqueColors) ? implode(', ', $uniqueColors) : $colors,
                    $firstRollPhoto,
                    $sellerId
                ]
            );
        }

        AuditService::log(
            'production',
            'CREATE',
            'sellers',
            $sellerId,
            null,
            [
                'Supplier Name'  => $name,
                'Company / Mill' => $company,
                'Phone'          => $phone,
                'Email'          => $email,
                'Material Type'  => $materialType,
                'City'           => $city,
                'State'          => $state,
                'Address'        => $address,
                'GST Number'     => !empty($gstNumber) ? $gstNumber : 'N/A',
                'Payment Terms'  => !empty($paymentTerms) ? $paymentTerms : 'N/A',
                'Total Rolls'    => !empty($computedRollCount) ? $computedRollCount : $rollCount,
                'Total Weight'   => number_format(!empty($computedTotalWeight) ? $computedTotalWeight : $rollWeight, 2) . ' kg',
                'Total Price'    => '₹' . number_format(!empty($computedTotalPrice) ? $computedTotalPrice : $rollPrice, 2),
                'Colors'         => !empty($uniqueColors) ? implode(', ', $uniqueColors) : ($colors ?: 'N/A'),
            ]
        );

        Session::flash('success', 'Material supplier and fabric rolls saved successfully.');
        Response::redirect(url('production/sellers'));
    }

    public function editSeller(string $id): void {
        $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ? AND seller_type = 'MATERIAL_SUPPLIER'", [(int)$id]);
        if (!$seller) {
            Response::error(404, 'Supplier not found.');
        }

        $rolls = Database::fetchAll(
            "SELECT sr.*,
                    COALESCE(u.used_quantity, 0) as used_quantity,
                    COALESCE(u.used_weight, 0) as used_weight,
                    COALESCE(u.used_price, 0) as used_price,
                    COALESCE(dmg.damaged_quantity, 0) as damaged_quantity,
                    COALESCE(dmg.damaged_weight, 0) as damaged_weight,
                    COALESCE(dmg.damaged_price, 0) as damaged_price,
                    GREATEST(0, sr.quantity - COALESCE(u.used_quantity, 0) - COALESCE(dmg.damaged_quantity, 0)) as available_quantity,
                    GREATEST(0.0, sr.weight - COALESCE(u.used_weight, 0) - COALESCE(dmg.damaged_weight, 0)) as available_weight,
                    GREATEST(0.0, sr.price - COALESCE(u.used_price, 0) - COALESCE(dmg.damaged_price, 0)) as available_price,
                    dmg.damage_reason,
                    dmg.damage_status,
                    dmg.damage_id
             FROM seller_rolls sr
             LEFT JOIN (
                 SELECT ppr.seller_roll_id, 
                        SUM(ppr.roll_qty) as used_quantity,
                        SUM(ppr.roll_weight) as used_weight,
                        SUM(ppr.roll_price) as used_price
                 FROM production_program_rolls ppr
                 JOIN production_programs pp ON ppr.production_program_id = pp.id
                 WHERE pp.is_active = 1 AND pp.status != 'CANCELLED'
                 GROUP BY ppr.seller_roll_id
             ) u ON sr.id = u.seller_roll_id
             LEFT JOIN (
                 SELECT rd.seller_roll_id,
                        SUM(rd.damaged_quantity) as damaged_quantity,
                        SUM(rd.damaged_weight) as damaged_weight,
                        SUM(rd.loss_amount) as damaged_price,
                        MAX(rd.damage_reason) as damage_reason,
                        MAX(rd.status) as damage_status,
                        MAX(rd.id) as damage_id
                 FROM roll_damages rd
                 WHERE rd.status NOT IN ('CLOSED', 'CANCELLED')
                 GROUP BY rd.seller_roll_id
             ) dmg ON sr.id = dmg.seller_roll_id
             WHERE sr.seller_id = ?
             ORDER BY sr.id ASC",
            [(int)$id]
        );

        Response::view('production.sellers.edit', [
            'pageTitle'    => 'Edit Material Supplier',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-sellers',
            'seller'       => $seller,
            'rolls'        => $rolls,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Suppliers', 'url' => url('production/sellers')],
                ['label' => 'Edit Supplier'],
            ],
        ]);
    }

    public function updateSeller(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $existing = Database::fetchOne("SELECT * FROM sellers WHERE id = ?", [(int)$id]);
        if (!$existing) {
            Session::flash('error', 'Supplier not found.');
            Response::redirect(url('production/sellers'));
        }

        $validator = Validator::make($_POST, [
            'name'          => 'required|minLength:2|maxLength:200',
            'company'       => 'required|minLength:2|maxLength:300',
            'phone'         => 'required',
            'email'         => 'required|maxLength:255',
            'city'          => 'required|maxLength:100',
            'state'         => 'required|maxLength:100',
            'address'       => 'required|maxLength:500',
            'material_type' => 'required|maxLength:150',
            'gst_number'    => 'maxLength:20',
            'payment_terms' => 'maxLength:200',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::back();
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        if (preg_match('/[0-9]/', $name)) {
            Session::flash('error', 'Supplier Name cannot contain numbers. Only alphabetic characters allowed.');
            Response::back();
        }

        $company = mb_strtoupper(trim($_POST['company'] ?? ''));
        if (preg_match('/[0-9]/', $company)) {
            Session::flash('error', 'Company / Mill Name cannot contain numbers.');
            Response::back();
        }

        $phone = trim($_POST['phone'] ?? '');
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            Session::flash('error', 'Phone number must be exactly 10 digits (e.g. 9876543210).');
            Response::back();
        }

        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            Session::flash('error', 'Email address must be a valid email address (e.g. name@domain.in, name@gmail.com).');
            Response::back();
        }

        $city = mb_strtoupper(trim($_POST['city'] ?? ''));
        if (preg_match('/[0-9]/', $city)) {
            Session::flash('error', 'City / District cannot contain numbers.');
            Response::back();
        }

        $state = mb_strtoupper(trim($_POST['state'] ?? ''));
        if (preg_match('/[0-9]/', $state)) {
            Session::flash('error', 'State cannot contain numbers.');
            Response::back();
        }

        $address = mb_strtoupper(trim($_POST['address'] ?? ''));
        $materialType = mb_strtoupper(trim($_POST['material_type'] ?? ''));
        $gstNumber = !empty($_POST['gst_number']) ? mb_strtoupper(trim($_POST['gst_number'])) : null;
        if ($gstNumber !== null && $gstNumber !== '' && !preg_match('/^[0-9A-Z]{15}$/', $gstNumber)) {
            Session::flash('error', 'GST Number must be a valid 15-character alphanumeric code.');
            Response::back();
        }

        $paymentTerms = !empty($_POST['payment_terms']) ? mb_strtoupper(trim($_POST['payment_terms'])) : null;
        $colors = !empty($_POST['colors']) ? mb_strtoupper(trim($_POST['colors'])) : null;
        $rollCount = max(0, (int)($_POST['roll_count'] ?? 0));
        $rollWeight = max(0.0, (float)($_POST['roll_weight'] ?? 0));
        $rollPrice = max(0.0, (float)($_POST['roll_price'] ?? 0));
        $colorCount = max(0, (int)($_POST['color_count'] ?? 0));
        $designCount = max(0, (int)($_POST['design_count'] ?? 0));
        $colorDesignCount = max(0, (int)($_POST['color_design_count'] ?? ($colorCount + $designCount)));

        // Validate rolls are present and all fields filled
        if (empty($_POST['rolls']) || !is_array($_POST['rolls'])) {
            Session::flash('error', 'Please add at least 1 fabric roll.');
            Response::back();
        }

        foreach ($_POST['rolls'] as $rIdx => $rCheck) {
            $rNum = trim($rCheck['roll_number'] ?? '');
            $rCol = trim($rCheck['color'] ?? '');
            $rDes = trim($rCheck['design_name'] ?? '');
            $rCnt = (int)($rCheck['design_count'] ?? 0);
            $rQty = (int)($rCheck['quantity'] ?? 0);
            $rWgt = (float)($rCheck['weight'] ?? 0);
            $rPrc = (float)($rCheck['price'] ?? 0);

            if ($rNum === '' || $rCol === '' || $rDes === '' || $rCnt < 1 || $rQty < 1 || $rWgt <= 0 || $rPrc <= 0) {
                Session::flash('error', "All fields in Roll #" . ($rIdx + 1) . " (Code, Color, Design, Designs Count, Qty, Weight, Price) are required.");
                Response::back();
            }
        }

        // Handle document attachment
        $documentUrl = $existing['document_url'] ?? null;
        if (empty($documentUrl) && (empty($_FILES['document']['name']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK)) {
            Session::flash('error', 'Tax Invoice / Bill document is required. Please upload the bill file (JPG, PNG, WEBP, PDF).');
            Response::back();
            return;
        }

        if (!empty($_FILES['document']['name']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['document'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

            if (!in_array($ext, $allowed, true)) {
                Session::flash('error', 'Invalid document format. Allowed: JPG, PNG, WEBP, PDF.');
                Response::back();
                return;
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                Session::flash('error', 'Document file size must not exceed 5MB.');
                Response::back();
                return;
            }

            $uploaded = CloudinaryService::upload($file, 'grm_inventory/sellers/documents', 'auto');
            if ($uploaded) {
                $documentUrl = $uploaded;
            }
        }

        // Handle primary sample photo
        $rollPhotoUrl = $existing['roll_photo'] ?? null;
        if (!empty($_FILES['roll_photo']['name']) && $_FILES['roll_photo']['error'] === UPLOAD_ERR_OK) {
            $photoFile = $_FILES['roll_photo'];
            $pExt = strtolower(pathinfo($photoFile['name'], PATHINFO_EXTENSION));
            $pAllowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($pExt, $pAllowed, true) && $photoFile['size'] <= 5 * 1024 * 1024) {
                $pUploaded = CloudinaryService::upload($photoFile, 'grm_inventory/sellers/photos', 'image');
                if ($pUploaded) {
                    $rollPhotoUrl = $pUploaded;
                }
            }
        }

        Database::execute(
            "UPDATE sellers SET
                name = ?,
                company = ?,
                phone = ?,
                email = ?,
                gst_number = ?,
                address = ?,
                city = ?,
                state = ?,
                payment_terms = ?,
                document_url = ?,
                material_type = ?,
                colors = ?,
                roll_photo = ?,
                roll_count = ?,
                color_count = ?,
                design_count = ?,
                color_design_count = ?,
                roll_weight = ?,
                roll_price = ?,
                is_active = ?,
                updated_at = NOW()
             WHERE id = ?",
            [
                $name,
                $company,
                $phone,
                $email,
                $gstNumber,
                $address,
                $city,
                $state,
                $paymentTerms,
                $documentUrl,
                $materialType,
                $colors,
                $rollPhotoUrl,
                $rollCount,
                $colorCount,
                $designCount,
                $colorDesignCount,
                $rollWeight,
                $rollPrice,
                isset($_POST['is_active']) ? 1 : 0,
                (int)$id,
            ]
        );

        // Manage multiple rolls re-sync while preserving existing roll IDs
        $keptRollIds = [];
        if (!empty($_POST['rolls']) && is_array($_POST['rolls'])) {
            $computedRollCount = 0;
            $computedTotalWeight = 0.0;
            $computedTotalPrice = 0.0;
            $colorsList = [];
            $designsList = [];
            $firstRollPhoto = $rollPhotoUrl;

            $seqInfo = ProductionService::getNextFabricRollSequence(1);
            $runningSeq = (int)$seqInfo['start_seq'];
            $prefix = $seqInfo['prefix'];

            foreach ($_POST['rolls'] as $index => $r) {
                $rollId = !empty($r['id']) ? (int)$r['id'] : 0;
                $rollNum = mb_strtoupper(trim($r['roll_number'] ?? ('ROLL #' . ($computedRollCount + 1))));
                $rollColor = mb_strtoupper(trim($r['color'] ?? ''));
                $rollDesign = mb_strtoupper(trim($r['design_name'] ?? ''));
                $rDesignCount = max(1, (int)($r['design_count'] ?? 1));
                $rQty = max(1, (int)($r['quantity'] ?? ($r['qty'] ?? 1)));
                $rWeight = max(0.0, (float)($r['weight'] ?? 0));
                $rPrice = max(0.0, (float)($r['price'] ?? 0));
                $individualPhoto = !empty($r['existing_photo']) ? $r['existing_photo'] : null;

                // Check new photo upload
                if (!empty($_FILES["roll_photo_{$index}"]['name']) && $_FILES["roll_photo_{$index}"]['error'] === UPLOAD_ERR_OK) {
                    $f = $_FILES["roll_photo_{$index}"];
                    $fExt = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    if (in_array($fExt, ['jpg', 'jpeg', 'png', 'webp'], true) && $f['size'] <= 5 * 1024 * 1024) {
                        $uploaded = CloudinaryService::upload($f, 'grm_inventory/sellers/rolls', 'image');
                        if ($uploaded) {
                            $individualPhoto = $uploaded;
                        }
                    }
                }

                if ($firstRollPhoto === null && $individualPhoto !== null) {
                    $firstRollPhoto = $individualPhoto;
                }

                if ($rollId > 0) {
                    $existingRoll = Database::fetchOne("SELECT * FROM seller_rolls WHERE id = ? AND seller_id = ?", [$rollId, (int)$id]);
                    if ($existingRoll) {
                        $rollStartSeq = !empty($existingRoll['start_seq']) ? (int)$existingRoll['start_seq'] : null;
                        $rollEndSeq = !empty($existingRoll['end_seq']) ? (int)$existingRoll['end_seq'] : null;
                        $rollStartCode = $existingRoll['start_code'] ?? null;
                        $rollEndCode = $existingRoll['end_code'] ?? null;
                        $rollQrCode = $existingRoll['qr_code'] ?? null;

                        if (empty($rollStartCode) || (int)$existingRoll['quantity'] !== $rQty) {
                            $rollStartSeq = $runningSeq;
                            $rollEndSeq = $runningSeq + $rQty - 1;
                            $rollStartCode = sprintf('%s%02d', $prefix, $rollStartSeq);
                            $rollEndCode = sprintf('%s%02d', $prefix, $rollEndSeq);
                            $rollQrCode = ($rQty > 1) ? ($rollStartCode . '-' . $rollEndCode) : $rollStartCode;
                            $runningSeq = $rollEndSeq + 1;
                        }

                        Database::execute(
                            "UPDATE seller_rolls 
                             SET roll_number = ?, color = ?, design_name = ?, design_count = ?, quantity = ?, weight = ?, price = ?, photo_url = ?,
                                 start_seq = ?, end_seq = ?, start_code = ?, end_code = ?, qr_code = ?
                             WHERE id = ?",
                            [$rollNum, $rollColor, $rollDesign, $rDesignCount, $rQty, $rWeight, $rPrice, $individualPhoto, $rollStartSeq, $rollEndSeq, $rollStartCode, $rollEndCode, $rollQrCode, $rollId]
                        );
                        $keptRollIds[] = $rollId;
                    } else {
                        $rollStartSeq = $runningSeq;
                        $rollEndSeq = $runningSeq + $rQty - 1;
                        $rollStartCode = sprintf('%s%02d', $prefix, $rollStartSeq);
                        $rollEndCode = sprintf('%s%02d', $prefix, $rollEndSeq);
                        $rollQrCode = ($rQty > 1) ? ($rollStartCode . '-' . $rollEndCode) : $rollStartCode;
                        $runningSeq = $rollEndSeq + 1;

                        Database::execute(
                            "INSERT INTO seller_rolls (seller_id, roll_number, color, design_name, design_count, quantity, weight, price, photo_url, start_seq, end_seq, start_code, end_code, qr_code)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [(int)$id, $rollNum, $rollColor, $rollDesign, $rDesignCount, $rQty, $rWeight, $rPrice, $individualPhoto, $rollStartSeq, $rollEndSeq, $rollStartCode, $rollEndCode, $rollQrCode]
                        );
                        $keptRollIds[] = (int)Database::lastInsertId();
                    }
                } else {
                    $rollStartSeq = $runningSeq;
                    $rollEndSeq = $runningSeq + $rQty - 1;
                    $rollStartCode = sprintf('%s%02d', $prefix, $rollStartSeq);
                    $rollEndCode = sprintf('%s%02d', $prefix, $rollEndSeq);
                    $rollQrCode = ($rQty > 1) ? ($rollStartCode . '-' . $rollEndCode) : $rollStartCode;
                    $runningSeq = $rollEndSeq + 1;

                    Database::execute(
                        "INSERT INTO seller_rolls (seller_id, roll_number, color, design_name, design_count, quantity, weight, price, photo_url, start_seq, end_seq, start_code, end_code, qr_code)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [(int)$id, $rollNum, $rollColor, $rollDesign, $rDesignCount, $rQty, $rWeight, $rPrice, $individualPhoto, $rollStartSeq, $rollEndSeq, $rollStartCode, $rollEndCode, $rollQrCode]
                    );
                    $keptRollIds[] = (int)Database::lastInsertId();
                }

                $computedRollCount++;
                $computedTotalWeight += $rWeight;
                $computedTotalPrice += $rPrice;
                if ($rollColor !== '') $colorsList[] = $rollColor;
                if ($rollDesign !== '') $designsList[] = $rollDesign;
            }

            if (!empty($keptRollIds)) {
                $inClause = implode(',', array_fill(0, count($keptRollIds), '?'));
                Database::execute("DELETE FROM seller_rolls WHERE seller_id = ? AND id NOT IN ($inClause)", array_merge([(int)$id], $keptRollIds));
            } else {
                Database::execute("DELETE FROM seller_rolls WHERE seller_id = ?", [(int)$id]);
            }

            $uniqueColors = array_values(array_unique(array_filter($colorsList)));
            $uniqueDesigns = array_values(array_unique(array_filter($designsList)));

            // Update aggregated metrics
            Database::execute(
                "UPDATE sellers SET 
                    roll_count = ?, 
                    color_count = ?, 
                    design_count = ?, 
                    color_design_count = ?, 
                    roll_weight = ?, 
                    roll_price = ?, 
                    colors = ?,
                    roll_photo = COALESCE(roll_photo, ?)
                 WHERE id = ?",
                [
                    $computedRollCount,
                    count($uniqueColors),
                    count($uniqueDesigns),
                    count($uniqueColors) + count($uniqueDesigns),
                    $computedTotalWeight,
                    $computedTotalPrice,
                    !empty($uniqueColors) ? implode(', ', $uniqueColors) : $colors,
                    $firstRollPhoto,
                    (int)$id
                ]
            );
        }

        $oldSnapshot = [
            'Supplier Name'  => $existing['name'] ?? '',
            'Company / Mill' => $existing['company'] ?? '',
            'Phone'          => $existing['phone'] ?? '',
            'Email'          => $existing['email'] ?? '',
            'Material Type'  => $existing['material_type'] ?? '',
            'City'           => $existing['city'] ?? '',
            'State'          => $existing['state'] ?? '',
            'GST Number'     => !empty($existing['gst_number']) ? $existing['gst_number'] : 'N/A',
            'Payment Terms'  => !empty($existing['payment_terms']) ? $existing['payment_terms'] : 'N/A',
            'Total Rolls'    => (int)($existing['roll_count'] ?? 0),
            'Total Weight'   => number_format((float)($existing['roll_weight'] ?? 0), 2) . ' kg',
            'Total Price'    => '₹' . number_format((float)($existing['roll_price'] ?? 0), 2),
            'Status'         => !empty($existing['is_active']) ? 'Active' : 'Inactive',
        ];

        $newSnapshot = [
            'Supplier Name'  => $name,
            'Company / Mill' => $company,
            'Phone'          => $phone,
            'Email'          => $email,
            'Material Type'  => $materialType,
            'City'           => $city,
            'State'          => $state,
            'GST Number'     => !empty($gstNumber) ? $gstNumber : 'N/A',
            'Payment Terms'  => !empty($paymentTerms) ? $paymentTerms : 'N/A',
            'Total Rolls'    => !empty($computedRollCount) ? $computedRollCount : $rollCount,
            'Total Weight'   => number_format(!empty($computedTotalWeight) ? $computedTotalWeight : $rollWeight, 2) . ' kg',
            'Total Price'    => '₹' . number_format(!empty($computedTotalPrice) ? $computedTotalPrice : $rollPrice, 2),
            'Status'         => isset($_POST['is_active']) ? 'Active' : 'Inactive',
        ];

        AuditService::log(
            'production',
            'UPDATE',
            'sellers',
            (int)$id,
            $oldSnapshot,
            $newSnapshot
        );

        Session::flash('success', 'Supplier and fabric rolls updated successfully.');
        Response::redirect(url('production/sellers'));
    }

    public function deleteSeller(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ?", [(int)$id]);
        if (!$seller) {
            Session::flash('error', 'Supplier not found.');
            Response::redirect(url('production/sellers'));
            return;
        }

        // Check if any fabric rolls from this supplier are used in active production programs or cutting batches
        $usedPrograms = Database::fetchAll(
            "SELECT DISTINCT pp.program_number, pp.name as program_name
             FROM production_program_rolls ppr
             JOIN production_programs pp ON ppr.production_program_id = pp.id
             WHERE ppr.seller_id = ? AND (pp.is_active = 1 OR pp.is_active IS NULL) AND pp.status != 'CANCELLED'",
            [(int)$id]
        );

        if (empty($usedPrograms)) {
            $usedPrograms = Database::fetchAll(
                "SELECT pp.program_number, pp.name as program_name
                 FROM production_programs pp
                 WHERE (pp.seller_id = ? OR pp.seller_roll_id IN (SELECT id FROM seller_rolls WHERE seller_id = ?))
                   AND (pp.is_active = 1 OR pp.is_active IS NULL) AND pp.status != 'CANCELLED'",
                [(int)$id, (int)$id]
            );
        }

        if (!empty($usedPrograms)) {
            $progNames = array_map(fn($p) => $p['program_number'] . (!empty($p['program_name']) ? " ({$p['program_name']})" : ''), $usedPrograms);
            Session::flash('error', "Cannot delete Supplier '{$seller['name']}' because fabric rolls from this lot are assigned to active Production Programs: " . implode(', ', array_slice($progNames, 0, 3)) . ". Please unassign or cancel these programs first.");
            Response::redirect(url('production/sellers'));
            return;
        }

        // Delete all associated seller rolls
        Database::execute("DELETE FROM seller_rolls WHERE seller_id = ?", [(int)$id]);

        // Delete the seller record
        Database::execute("DELETE FROM sellers WHERE id = ?", [(int)$id]);

        $delSnapshot = [
            'Supplier Name'  => $seller['name'] ?? '',
            'Company / Mill' => $seller['company'] ?? '',
            'Phone'          => $seller['phone'] ?? '',
            'Email'          => $seller['email'] ?? '',
            'Material Type'  => $seller['material_type'] ?? '',
            'City'           => $seller['city'] ?? '',
            'Total Rolls'    => (int)($seller['roll_count'] ?? 0),
            'Total Price'    => '₹' . number_format((float)($seller['roll_price'] ?? 0), 2),
        ];

        AuditService::log('production', 'DELETE', 'sellers', (int)$id, $delSnapshot, null);

        Session::flash('success', "Material Supplier '{$seller['name']}' and associated fabric rolls deleted successfully.");
        Response::redirect(url('production/sellers'));
    }

    public function deleteSellerRoll(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $roll = Database::fetchOne("SELECT * FROM seller_rolls WHERE id = ?", [(int)$id]);
        if (!$roll) {
            Session::flash('error', 'Fabric roll not found.');
            Response::back();
            return;
        }

        $sellerId = (int)$roll['seller_id'];

        // Check if this roll is assigned in any active production programs
        $used = Database::fetchOne(
            "SELECT pp.program_number, pp.name as program_name 
             FROM production_program_rolls ppr
             JOIN production_programs pp ON ppr.production_program_id = pp.id
             WHERE ppr.seller_roll_id = ? AND (pp.is_active = 1 OR pp.is_active IS NULL) AND pp.status != 'CANCELLED'
             LIMIT 1",
            [(int)$id]
        );

        if (!$used) {
            $used = Database::fetchOne(
                "SELECT pp.program_number, pp.name as program_name 
                 FROM production_programs pp
                 WHERE pp.seller_roll_id = ? AND (pp.is_active = 1 OR pp.is_active IS NULL) AND pp.status != 'CANCELLED'
                 LIMIT 1",
                [(int)$id]
            );
        }

        if ($used) {
            $progLabel = $used['program_number'] . (!empty($used['program_name']) ? " ({$used['program_name']})" : '');
            Session::flash('error', "Cannot delete Roll '{$roll['roll_number']}' because it is assigned to active Program {$progLabel}.");
            Response::back();
            return;
        }

        // Delete roll
        Database::execute("DELETE FROM seller_rolls WHERE id = ?", [(int)$id]);

        // Recalculate seller summary totals
        $remainingRolls = Database::fetchAll("SELECT * FROM seller_rolls WHERE seller_id = ?", [$sellerId]);
        $computedRollCount = count($remainingRolls);
        $computedTotalWeight = array_sum(array_column($remainingRolls, 'weight'));
        $computedTotalPrice = array_sum(array_column($remainingRolls, 'price'));
        $uniqueColors = array_unique(array_filter(array_map('trim', array_column($remainingRolls, 'color'))));
        $uniqueDesigns = array_unique(array_filter(array_map('trim', array_column($remainingRolls, 'design_name'))));

        Database::execute(
            "UPDATE sellers SET 
                roll_count = ?, 
                color_count = ?, 
                design_count = ?, 
                color_design_count = ?, 
                roll_weight = ?, 
                roll_price = ?, 
                colors = ?
             WHERE id = ?",
            [
                $computedRollCount,
                count($uniqueColors),
                count($uniqueDesigns),
                count($uniqueColors) + count($uniqueDesigns),
                $computedTotalWeight,
                $computedTotalPrice,
                !empty($uniqueColors) ? implode(', ', $uniqueColors) : '',
                $sellerId
            ]
        );

        $delRollSnapshot = [
            'Roll Number'  => $roll['roll_number'] ?? '',
            'Color'        => $roll['color'] ?? '',
            'Design'       => $roll['design_name'] ?? '',
            'Quantity'     => (int)($roll['quantity'] ?? 0),
            'Weight'       => number_format((float)($roll['weight'] ?? 0), 2) . ' kg',
            'Price'        => '₹' . number_format((float)($roll['price'] ?? 0), 2),
        ];

        AuditService::log('production', 'DELETE', 'seller_rolls', (int)$id, $delRollSnapshot, null);

        Session::flash('success', "Fabric Roll '{$roll['roll_number']}' deleted successfully.");
        Response::back();
    }

    public function ajaxSellerRolls(string $id): void {
        $sellerId = (int)$id;
        $excludeDamageId = max(0, (int)($_GET['exclude_damage_id'] ?? 0));
        $rolls = Database::fetchAll(
            "SELECT sr.id, sr.roll_number, sr.color, sr.design_name, sr.quantity, sr.weight, sr.price, sr.photo_url,
                    COALESCE(u.used_quantity, 0) as used_quantity,
                    COALESCE(u.used_weight, 0) as used_weight,
                    COALESCE(dmg.damaged_quantity, 0) as damaged_quantity,
                    COALESCE(dmg.damaged_weight, 0) as damaged_weight,
                    GREATEST(0, sr.quantity - COALESCE(dmg.damaged_quantity, 0)) as available_quantity,
                    GREATEST(0.0, sr.weight - COALESCE(dmg.damaged_weight, 0)) as available_weight
             FROM seller_rolls sr
             LEFT JOIN (
                 SELECT ppr.seller_roll_id, 
                        SUM(ppr.roll_qty) as used_quantity,
                        SUM(ppr.roll_weight) as used_weight
                 FROM production_program_rolls ppr
                 JOIN production_programs pp ON ppr.production_program_id = pp.id
                 WHERE pp.is_active = 1 AND pp.status != 'CANCELLED'
                 GROUP BY ppr.seller_roll_id
             ) u ON sr.id = u.seller_roll_id
             LEFT JOIN (
                 SELECT rd.seller_roll_id,
                        SUM(rd.damaged_quantity) as damaged_quantity,
                        SUM(rd.damaged_weight) as damaged_weight
                 FROM roll_damages rd
                 WHERE rd.status NOT IN ('CLOSED', 'CANCELLED')
                   AND (? = 0 OR rd.id != ?)
                 GROUP BY rd.seller_roll_id
             ) dmg ON sr.id = dmg.seller_roll_id
             WHERE sr.seller_id = ?
             ORDER BY sr.id ASC",
            [$excludeDamageId, $excludeDamageId, $sellerId]
        );

        Response::json([
            'success' => true,
            'rolls' => $rolls
        ]);
    }

    public function ajaxLookupDamageRoll(): void {
        $rawScan = trim((string)($_GET['code'] ?? ''));
        $scanCode = strtoupper(trim(explode('|', $rawScan, 2)[0]));

        if (!preg_match('/^([A-Z]{2}\d{4})(\d+)$/', $scanCode, $matches)) {
            Response::json(['success' => false, 'message' => 'QR code is not a recognized fabric roll label.'], 400);
            return;
        }

        $prefix = $matches[1];
        $sequence = (int)$matches[2];
        $rolls = Database::fetchAll(
            "SELECT sr.id, sr.seller_id, sr.roll_number, sr.color, sr.design_name, sr.quantity, sr.weight, sr.price,
                    sr.start_seq, sr.end_seq, sr.start_code, sr.end_code,
                    s.name as seller_name, s.company as seller_company
             FROM seller_rolls sr
             JOIN sellers s ON s.id = sr.seller_id
             WHERE sr.start_code LIKE ? AND sr.end_code LIKE ?
             ORDER BY sr.id ASC",
            [$prefix . '%', $prefix . '%']
        );

        $matchesForCode = array_values(array_filter($rolls, static function (array $roll) use ($prefix, $sequence): bool {
            return strtoupper(substr((string)$roll['start_code'], 0, 6)) === $prefix
                && strtoupper(substr((string)$roll['end_code'], 0, 6)) === $prefix
                && $sequence >= (int)$roll['start_seq']
                && $sequence <= (int)$roll['end_seq'];
        }));

        if (count($matchesForCode) !== 1) {
            Response::json([
                'success' => false,
                'message' => count($matchesForCode) > 1
                    ? 'This QR code matches more than one fabric roll. Select the roll manually.'
                    : 'No fabric roll was found for this QR code.',
            ], count($matchesForCode) > 1 ? 409 : 404);
            return;
        }

        $roll = $matchesForCode[0];
        $deletedLabel = Database::fetchOne(
            "SELECT id FROM seller_roll_deleted_labels WHERE seller_roll_id = ? AND `sequence` = ?",
            [(int)$roll['id'], $sequence]
        );
        if ($deletedLabel) {
            Response::json(['success' => false, 'message' => 'This QR label was deleted and cannot be used to report damage.'], 404);
            return;
        }

        $existingDamage = Database::fetchOne(
            "SELECT damage_number FROM roll_damages WHERE qr_code = ? LIMIT 1",
            [$scanCode]
        );
        if ($existingDamage) {
            Response::json([
                'success' => false,
                'message' => "QR label {$scanCode} is already recorded in damage report {$existingDamage['damage_number']}.",
            ], 409);
            return;
        }

        Response::json([
            'success' => true,
            'seller_id' => (int)$roll['seller_id'],
            'seller_name' => $roll['seller_name'],
            'roll_id' => (int)$roll['id'],
            'roll_number' => $roll['roll_number'],
            'color' => $roll['color'],
            'design_name' => $roll['design_name'],
            'quantity' => (int)$roll['quantity'],
            'weight' => (float)$roll['weight'],
            'price' => (float)$roll['price'],
            'code' => $scanCode,
        ]);
    }

    /**
     * Download or stream bill / invoice document for a supplier.
     */
    public function downloadBill(string $id): void {
        $sellerId = (int)$id;
        $seller = Database::fetchOne(
            "SELECT id, name, document_url FROM sellers WHERE id = ?",
            [$sellerId]
        );

        if (!$seller || empty($seller['document_url'])) {
            Session::flash('error', 'No invoice or bill document found for this supplier.');
            Response::back();
            return;
        }

        $documentUrl = $seller['document_url'];
        $cleanName = preg_replace('/[^A-Za-z0-9_-]/', '_', trim($seller['name'] ?? 'Supplier'));
        $ext = strtolower(pathinfo(parse_url($documentUrl, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'png';
        $downloadFilename = 'Supplier_Bill_' . $cleanName . '_' . $seller['id'] . '.' . $ext;

        // 1. Cloudinary hosted URL - redirect to attachment transformation
        if (str_contains($documentUrl, 'res.cloudinary.com')) {
            $attachmentUrl = downloadUrl($documentUrl, $downloadFilename);
            header('Location: ' . $attachmentUrl);
            exit;
        }

        // 2. Local file path
        if (!str_starts_with($documentUrl, 'http://') && !str_starts_with($documentUrl, 'https://')) {
            $cleanPath = ltrim(parse_url($documentUrl, PHP_URL_PATH) ?? '', '/');
            if (str_starts_with($cleanPath, 'public/')) {
                $cleanPath = substr($cleanPath, 7);
            }
            $localPath = BASE_PATH . '/public/' . $cleanPath;
            if (!file_exists($localPath)) {
                $localPath = BASE_PATH . '/' . $cleanPath;
            }

            if (file_exists($localPath) && is_file($localPath)) {
                $mimeType = mime_content_type($localPath) ?: 'application/octet-stream';
                header('Content-Description: File Transfer');
                header('Content-Type: ' . $mimeType);
                header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
                header('Content-Length: ' . filesize($localPath));
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                if (ob_get_level()) {
                    ob_end_clean();
                }
                flush();
                readfile($localPath);
                exit;
            }
        }

        // 3. Remote URL fallback via cURL streaming
        if (str_starts_with($documentUrl, 'http://') || str_starts_with($documentUrl, 'https://')) {
            $ch = curl_init($documentUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $fileData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpCode === 200 && $fileData !== false) {
                header('Content-Description: File Transfer');
                header('Content-Type: ' . ($contentType ?: 'application/octet-stream'));
                header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
                header('Content-Length: ' . strlen($fileData));
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                if (ob_get_level()) {
                    ob_end_clean();
                }
                flush();
                echo $fileData;
                exit;
            }
        }

        Response::redirect($documentUrl);
    }

    /**
     * Download or stream fabric roll photo.
     */
    public function downloadRollPhoto(string $id): void {
        $rollId = (int)$id;
        $roll = Database::fetchOne(
            "SELECT sr.id, sr.roll_number, sr.photo_url, s.name as seller_name 
             FROM seller_rolls sr 
             LEFT JOIN sellers s ON sr.seller_id = s.id 
             WHERE sr.id = ?",
            [$rollId]
        );

        if (!$roll || empty($roll['photo_url'])) {
            Session::flash('error', 'No photo found for this roll.');
            Response::back();
            return;
        }

        $photoUrl = $roll['photo_url'];
        $cleanRollNum = preg_replace('/[^A-Za-z0-9_-]/', '_', trim($roll['roll_number'] ?? 'Roll'));
        $ext = strtolower(pathinfo(parse_url($photoUrl, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
        $downloadFilename = 'Roll_Photo_' . $cleanRollNum . '_' . $roll['id'] . '.' . $ext;

        if (str_contains($photoUrl, 'res.cloudinary.com')) {
            $attachmentUrl = downloadUrl($photoUrl, $downloadFilename);
            header('Location: ' . $attachmentUrl);
            exit;
        }

        if (!str_starts_with($photoUrl, 'http://') && !str_starts_with($photoUrl, 'https://')) {
            $cleanPath = ltrim(parse_url($photoUrl, PHP_URL_PATH) ?? '', '/');
            if (str_starts_with($cleanPath, 'public/')) {
                $cleanPath = substr($cleanPath, 7);
            }
            $localPath = BASE_PATH . '/public/' . $cleanPath;
            if (!file_exists($localPath)) {
                $localPath = BASE_PATH . '/' . $cleanPath;
            }

            if (file_exists($localPath) && is_file($localPath)) {
                $mimeType = mime_content_type($localPath) ?: 'application/octet-stream';
                header('Content-Description: File Transfer');
                header('Content-Type: ' . $mimeType);
                header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
                header('Content-Length: ' . filesize($localPath));
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                if (ob_get_level()) {
                    ob_end_clean();
                }
                flush();
                readfile($localPath);
                exit;
            }
        }

        Response::redirect($photoUrl);
    }

    /**
     * Preview and print all fabric roll labels for a seller lot.
     */
    public function printSellerLabels(string $id): void {
        $sellerId = (int)$id;
        $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ?", [$sellerId]);
        if (!$seller) {
            Response::error(404, 'Material supplier not found.');
            return;
        }

        $rolls = Database::fetchAll("SELECT * FROM seller_rolls WHERE seller_id = ? ORDER BY id ASC", [$sellerId]);
        $stickers = $this->buildStickersForRolls($seller, $rolls);

        Response::viewOnly('production.sellers.labels', [
            'seller'     => $seller,
            'rolls'      => $rolls,
            'stickers'   => $stickers,
            'isLot'      => true,
            'pageTitle'  => 'Fabric Roll Labels — ' . ($seller['company'] ?: $seller['name']),
        ]);
    }

    /**
     * Preview and print labels for a single fabric roll.
     */
    public function printSellerRollLabel(string $id): void {
        $rollId = (int)$id;
        $roll = Database::fetchOne("SELECT * FROM seller_rolls WHERE id = ?", [$rollId]);
        if (!$roll) {
            Response::error(404, 'Fabric roll not found.');
            return;
        }

        $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ?", [(int)$roll['seller_id']]);
        $stickers = $this->buildStickersForRolls($seller, [$roll]);

        Response::viewOnly('production.sellers.labels', [
            'seller'     => $seller,
            'rolls'      => [$roll],
            'stickers'   => $stickers,
            'isLot'      => false,
            'singleRoll' => $roll,
            'pageTitle'  => 'Fabric Roll Label — ' . $roll['roll_number'],
        ]);
    }

    /**
     * AJAX endpoint to fetch fabric roll stickers JSON.
     */
    public function ajaxSellerRollLabels(string $id): void {
        $rollId = !empty($_GET['roll_id']) ? (int)$_GET['roll_id'] : 0;
        if ($rollId > 0) {
            $roll = Database::fetchOne("SELECT * FROM seller_rolls WHERE id = ?", [$rollId]);
            if (!$roll) {
                Response::json(['success' => false, 'message' => 'Fabric roll not found.'], 404);
                return;
            }
            $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ?", [(int)$roll['seller_id']]);
            $rolls = [$roll];
        } else {
            $sellerId = (int)$id;
            $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ?", [$sellerId]);
            if (!$seller) {
                Response::json(['success' => false, 'message' => 'Material supplier not found.'], 404);
                return;
            }
            $rolls = Database::fetchAll("SELECT * FROM seller_rolls WHERE seller_id = ? ORDER BY id ASC", [$sellerId]);
        }

        $stickers = $this->buildStickersForRolls($seller, $rolls);

        Response::json([
            'success'  => true,
            'seller'   => $seller,
            'rolls'    => $rolls,
            'stickers' => $stickers,
        ]);
    }

    /**
     * AJAX endpoint for live next sequence codes preview.
     */
    /**
     * Generate extra continuation label(s) for a seller roll.
     * Continues from the previous sequence code and updates quantity/end_code.
     */
    public function addExtraRollLabel(string $id): void {
        if (!CSRF::validateRequest()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                Response::json(['success' => false, 'message' => 'Invalid security token or session expired.'], 403);
                return;
            }
            Session::flash('error', 'Invalid security token or session expired.');
            Response::back();
            return;
        }

        $rollId = (int)$id;
        $roll = Database::fetchOne("SELECT * FROM seller_rolls WHERE id = ?", [$rollId]);
        if (!$roll) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                Response::json(['success' => false, 'message' => 'Fabric roll not found.'], 404);
                return;
            }
            Session::flash('error', 'Fabric roll not found.');
            Response::back();
            return;
        }

        $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ?", [(int)$roll['seller_id']]);
        if (!$seller) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                Response::json(['success' => false, 'message' => 'Material supplier not found.'], 404);
                return;
            }
            Session::flash('error', 'Material supplier not found.');
            Response::back();
            return;
        }

        $count = max(1, min(50, (int)($_POST['count'] ?? 1)));
        $currentQty = max(1, (int)($roll['quantity'] ?? 1));
        $currentEndSeq = (int)($roll['end_seq'] ?? 0);
        $prefix = !empty($roll['start_code']) ? substr($roll['start_code'], 0, 6) : ('FB' . date('my'));

        if ($currentEndSeq <= 0) {
            $seqInfo = ProductionService::getNextFabricRollSequence($currentQty, $roll['created_at'] ?? null);
            $currentEndSeq = (int)$seqInfo['end_seq'];
            $prefix = $seqInfo['prefix'];
        }

        $targetStartSeq = $currentEndSeq + 1;
        $conflict = Database::fetchOne(
            "SELECT id, roll_number FROM seller_rolls WHERE id != ? AND (
                (? BETWEEN start_seq AND end_seq) OR 
                start_code = ? OR end_code = ?
            ) LIMIT 1",
            [$rollId, $targetStartSeq, sprintf('%s%02d', $prefix, $targetStartSeq), sprintf('%s%02d', $prefix, $targetStartSeq)]
        );

        if ($conflict) {
            $globalSeq = ProductionService::getNextFabricRollSequence($count);
            $newStartSeq = (int)$globalSeq['start_seq'];
            $newEndSeq = (int)$globalSeq['end_seq'];
        } else {
            $newStartSeq = $targetStartSeq;
            $newEndSeq = $targetStartSeq + $count - 1;
        }

        $newQty = $currentQty + $count;
        $startCode = !empty($roll['start_code']) ? $roll['start_code'] : sprintf('%s%02d', $prefix, (int)($roll['start_seq'] ?? 1));
        $newEndCode = sprintf('%s%02d', $prefix, $newEndSeq);
        $newQrCode = ($newQty > 1) ? "{$startCode}-{$newEndCode}" : $startCode;

        Database::execute(
            "UPDATE seller_rolls SET 
                quantity = ?, 
                end_seq = ?, 
                end_code = ?, 
                qr_code = ? 
             WHERE id = ?",
            [$newQty, $newEndSeq, $newEndCode, $newQrCode, $rollId]
        );

        // Recalculate seller summary totals
        $allRolls = Database::fetchAll("SELECT * FROM seller_rolls WHERE seller_id = ?", [$seller['id']]);
        $computedRollCount = count($allRolls);
        $computedTotalWeight = array_sum(array_column($allRolls, 'weight'));
        $computedTotalPrice = array_sum(array_column($allRolls, 'price'));

        Database::execute(
            "UPDATE sellers SET 
                roll_count = ?, 
                roll_weight = ?, 
                roll_price = ? 
             WHERE id = ?",
            [$computedRollCount, $computedTotalWeight, $computedTotalPrice, $seller['id']]
        );

        $supplierName = mb_strtoupper(trim($seller['company'] ?: ($seller['name'] ?? 'GRM FABRIC')));
        $materialType = mb_strtoupper(trim($seller['material_type'] ?? 'STANDARD FABRIC'));
        $rollNum = $roll['roll_number'] ?: 'ROLL';
        $color = mb_strtoupper(trim($roll['color'] ?? ''));
        $design = mb_strtoupper(trim($roll['design_name'] ?? ''));
        $rollWeight = (float)($roll['weight'] ?? 0);
        $pieceWeight = $newQty > 0 ? round($rollWeight / $newQty, 2) : 0.0;
        $price = (float)($roll['price'] ?? 0);
        $photo = !empty($roll['photo_url']) ? $roll['photo_url'] : ($seller['roll_photo'] ?? null);

        $newStickers = [];
        for ($i = 0; $i < $count; $i++) {
            $seq = $newStartSeq + $i;
            $code = sprintf('%s%02d', $prefix, $seq);
            $payload = "{$code}|{$supplierName}|{$rollNum}|{$color}|{$design}|{$pieceWeight}KG";
            $pieceIndex = $currentQty + $i + 1;

            $newStickers[] = [
                'code'          => $code,
                'seq'           => $seq,
                'piece_index'   => $pieceIndex,
                'total_qty'     => $newQty,
                'roll_id'       => $rollId,
                'roll_number'   => $rollNum,
                'color'         => $color,
                'design_name'   => $design,
                'weight'        => $pieceWeight,
                'roll_weight'   => $rollWeight,
                'price'         => $price,
                'photo_url'     => $photo,
                'supplier_name' => $supplierName,
                'material_type' => $materialType,
                'qr_payload'    => $payload,
                'date'          => date('d M Y'),
            ];
        }

        AuditService::log('production', 'UPDATE', 'seller_rolls', $rollId, [
            'action'        => 'add_extra_labels',
            'roll_number'   => $rollNum,
            'added_count'   => $count,
            'new_quantity'  => $newQty,
            'new_end_code'  => $newEndCode
        ]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
            Response::json([
                'success'      => true,
                'message'      => "Generated {$count} extra label(s) for {$rollNum} (Code: {$newEndCode}).",
                'new_stickers' => $newStickers,
                'roll'         => [
                    'id'        => $rollId,
                    'quantity'  => $newQty,
                    'end_seq'   => $newEndSeq,
                    'end_code'  => $newEndCode,
                    'qr_code'   => $newQrCode,
                ]
            ]);
            return;
        }

        Session::flash('success', "Generated {$count} extra continuation label(s) for Roll {$rollNum} (ending at {$newEndCode}).");
        Response::back();
    }

    /**
     * Delete / remove extra label(s) from a seller roll.
     * Decreases quantity and adjusts end_seq/end_code accordingly.
     */
    public function deleteExtraRollLabel(string $id): void {
        if (!CSRF::validateRequest()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                Response::json(['success' => false, 'message' => 'Invalid security token or session expired.'], 403);
                return;
            }
            Session::flash('error', 'Invalid security token or session expired.');
            Response::back();
            return;
        }

        $rollId = (int)$id;
        $roll = Database::fetchOne("SELECT * FROM seller_rolls WHERE id = ?", [$rollId]);
        if (!$roll) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                Response::json(['success' => false, 'message' => 'Fabric roll not found.'], 404);
                return;
            }
            Session::flash('error', 'Fabric roll not found.');
            Response::back();
            return;
        }

        $seller = Database::fetchOne("SELECT * FROM sellers WHERE id = ?", [(int)$roll['seller_id']]);
        if (!$seller) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax'])) {
                Response::json(['success' => false, 'message' => 'Material supplier not found.'], 404);
                return;
            }
            Session::flash('error', 'Material supplier not found.');
            Response::back();
            return;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_POST['ajax']);
        $currentQty = max(1, (int)($roll['quantity'] ?? 1));
        $startSeq = (int)($roll['start_seq'] ?? 1);
        $endSeq = (int)($roll['end_seq'] ?? 0);
        if ($endSeq < $startSeq) {
            $endSeq = $startSeq + $currentQty - 1;
        }

        $requestedSequences = $_POST['sequences'] ?? [];
        if (!is_array($requestedSequences) || empty($requestedSequences)) {
            $deleteCount = max(1, (int)($_POST['count'] ?? 1));
            $existingDeleted = array_map(
                'intval',
                array_column(
                    Database::fetchAll("SELECT `sequence` FROM seller_roll_deleted_labels WHERE seller_roll_id = ?", [$rollId]),
                    'sequence'
                )
            );
            $deletedLookup = array_fill_keys($existingDeleted, true);
            $requestedSequences = [];
            for ($sequence = $endSeq; $sequence >= $startSeq && count($requestedSequences) < $deleteCount; $sequence--) {
                if (!isset($deletedLookup[$sequence])) {
                    $requestedSequences[] = $sequence;
                }
            }
        }

        $requestedSequences = array_values(array_unique(array_filter(
            array_map('intval', $requestedSequences),
            static fn(int $sequence): bool => $sequence > 0
        )));
        if (empty($requestedSequences)) {
            $message = 'No valid label sequence was selected for deletion.';
            if ($isAjax) {
                Response::json(['success' => false, 'message' => $message], 400);
                return;
            }
            Session::flash('error', $message);
            Response::back();
            return;
        }

        try {
            Database::beginTransaction();
            $lockedRoll = Database::fetchOne("SELECT quantity, start_seq, end_seq FROM seller_rolls WHERE id = ? FOR UPDATE", [$rollId]);
            if (!$lockedRoll) {
                Database::rollback();
                Response::json(['success' => false, 'message' => 'Fabric roll not found.'], 404);
                return;
            }
            $currentQty = max(1, (int)($lockedRoll['quantity'] ?? 1));
            $startSeq = (int)($lockedRoll['start_seq'] ?? 1);
            $endSeq = (int)($lockedRoll['end_seq'] ?? 0);
            if ($endSeq < $startSeq) {
                $endSeq = $startSeq + $currentQty - 1;
            }

            $existingDeleted = array_map(
                'intval',
                array_column(
                    Database::fetchAll("SELECT `sequence` FROM seller_roll_deleted_labels WHERE seller_roll_id = ?", [$rollId]),
                    'sequence'
                )
            );
            $deletedLookup = array_fill_keys($existingDeleted, true);
            foreach ($requestedSequences as $sequence) {
                if ($sequence < $startSeq || $sequence > $endSeq || isset($deletedLookup[$sequence])) {
                    Database::rollback();
                    Response::json(['success' => false, 'message' => 'A selected label is no longer available. Refresh the page and try again.'], 409);
                    return;
                }
            }

            if ($currentQty - count($requestedSequences) < 1) {
                Database::rollback();
                $message = 'Cannot delete the only label for this roll. Minimum 1 label is required.';
                if ($isAjax) {
                    Response::json(['success' => false, 'message' => $message], 400);
                    return;
                }
                Session::flash('error', $message);
                Response::back();
                return;
            }

            foreach ($requestedSequences as $sequence) {
                Database::execute(
                    "INSERT INTO seller_roll_deleted_labels (seller_roll_id, `sequence`) VALUES (?, ?)",
                    [$rollId, $sequence]
                );
            }

            $deleteCount = count($requestedSequences);
            $newQty = $currentQty - $deleteCount;
            Database::execute("UPDATE seller_rolls SET quantity = ? WHERE id = ?", [$newQty, $rollId]);
            Database::commit();
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }

        // Recalculate seller summary totals
        $allRolls = Database::fetchAll("SELECT * FROM seller_rolls WHERE seller_id = ?", [$seller['id']]);
        $computedRollCount = count($allRolls);
        $computedTotalWeight = array_sum(array_column($allRolls, 'weight'));
        $computedTotalPrice = array_sum(array_column($allRolls, 'price'));

        Database::execute(
            "UPDATE sellers SET 
                roll_count = ?, 
                roll_weight = ?, 
                roll_price = ? 
             WHERE id = ?",
            [$computedRollCount, $computedTotalWeight, $computedTotalPrice, $seller['id']]
        );

        AuditService::log('production', 'UPDATE', 'seller_rolls', $rollId, [
            'action'        => 'delete_extra_labels',
            'roll_number'   => $roll['roll_number'],
            'removed_count' => $deleteCount,
            'deleted_sequences' => $requestedSequences,
            'new_quantity'      => $newQty
        ]);

        if ($isAjax) {
            Response::json([
                'success'      => true,
                'message'      => "Removed {$deleteCount} label(s). Roll now has {$newQty} label(s).",
                'removed_count'=> $deleteCount,
                'new_quantity' => $newQty,
                'deleted_sequences' => $requestedSequences,
                'roll'         => [
                    'id'        => $rollId,
                    'quantity'  => $newQty,
                    'end_seq'   => $endSeq,
                    'end_code'  => $roll['end_code'],
                    'qr_code'   => $roll['qr_code'],
                ]
            ]);
            return;
        }

        Session::flash('success', "Removed {$deleteCount} label(s). Roll now has {$newQty} piece(s).");
        Response::back();
    }

    public function ajaxNextFabricRollCodes(): void {
        $qty = max(1, (int)($_GET['qty'] ?? 1));
        $seqData = ProductionService::getNextFabricRollSequence($qty);
        Response::json([
            'success' => true,
            'data'    => $seqData,
        ]);
    }

    /**
     * Helper to build sticker array for fabric rolls.
     */
    private function buildStickersForRolls(array $seller, array $rolls): array {
        $stickers = [];
        $supplierName = mb_strtoupper(trim($seller['company'] ?: ($seller['name'] ?? 'GRM FABRIC')));
        $materialType = mb_strtoupper(trim($seller['material_type'] ?? 'STANDARD FABRIC'));

        foreach ($rolls as &$r) {
            $qty = max(1, (int)($r['quantity'] ?? 1));
            
            // Auto-heal missing code if needed
            if (empty($r['start_code'])) {
                $seqInfo = ProductionService::getNextFabricRollSequence($qty, $r['created_at'] ?? null);
                Database::execute(
                    "UPDATE seller_rolls SET start_seq = ?, end_seq = ?, start_code = ?, end_code = ?, qr_code = ? WHERE id = ?",
                    [$seqInfo['start_seq'], $seqInfo['end_seq'], $seqInfo['start_code'], $seqInfo['end_code'], $seqInfo['qr_code'], $r['id']]
                );
                $r['start_seq'] = $seqInfo['start_seq'];
                $r['end_seq'] = $seqInfo['end_seq'];
                $r['start_code'] = $seqInfo['start_code'];
                $r['end_code'] = $seqInfo['end_code'];
                $r['qr_code'] = $seqInfo['qr_code'];
            }

            $startSeq = (int)($r['start_seq'] ?? 1);
            $endSeq = (int)($r['end_seq'] ?? 0);
            if ($endSeq < $startSeq) {
                $endSeq = $startSeq + $qty - 1;
            }
            $prefix = substr($r['start_code'], 0, 6);

            $rollNum = $r['roll_number'] ?: 'ROLL';
            $color = mb_strtoupper(trim($r['color'] ?? ''));
            $design = mb_strtoupper(trim($r['design_name'] ?? ''));
            $weight = (float)($r['weight'] ?? 0);
            $price = (float)($r['price'] ?? 0);
            $photo = !empty($r['photo_url']) ? $r['photo_url'] : ($seller['roll_photo'] ?? null);
            $deletedSequences = array_fill_keys(
                array_map(
                    'intval',
                    array_column(
                        Database::fetchAll("SELECT `sequence` FROM seller_roll_deleted_labels WHERE seller_roll_id = ?", [(int)$r['id']]),
                        'sequence'
                    )
                ),
                true
            );
            $activeSequences = [];
            for ($seq = $startSeq; $seq <= $endSeq; $seq++) {
                if (!isset($deletedSequences[$seq])) {
                    $activeSequences[] = $seq;
                }
            }
            $pieceWeight = count($activeSequences) > 0 ? round($weight / count($activeSequences), 2) : 0.0;

            foreach ($activeSequences as $i => $seq) {
                $code = sprintf('%s%02d', $prefix, $seq);
                $payload = "{$code}|{$supplierName}|{$rollNum}|{$color}|{$design}|{$pieceWeight}KG";

                $stickers[] = [
                    'code'          => $code,
                    'seq'           => $seq,
                    'piece_index'   => $i + 1,
                    'total_qty'     => count($activeSequences),
                    'roll_id'       => $r['id'] ?? 0,
                    'roll_number'   => $rollNum,
                    'color'         => $color,
                    'design_name'   => $design,
                    'weight'        => $pieceWeight,
                    'roll_weight'   => $weight,
                    'price'         => $price,
                    'photo_url'     => $photo,
                    'supplier_name' => $supplierName,
                    'material_type' => $materialType,
                    'qr_payload'    => $payload,
                    'date'          => !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : date('d M Y'),
                ];
            }
        }
        unset($r);

        return $stickers;
    }

    // =========================================================================
    // FABRIC & ROLL DAMAGES / DEFECT MANAGEMENT
    // =========================================================================

    public function damageList(): void {
        $search = trim($_GET['search'] ?? '');
        $sellerId = !empty($_GET['seller_id']) ? (int)$_GET['seller_id'] : null;
        $reason = trim($_GET['reason'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $resolutionType = trim($_GET['resolution_type'] ?? '');

        $sql = "SELECT rd.*, 
                       s.name as seller_name, 
                       s.company as seller_company,
                       sr.roll_number, 
                       sr.color as roll_color, 
                       sr.design_name as roll_design,
                       sr.weight as initial_roll_weight,
                       sr.price as initial_roll_price,
                       sr.photo_url as roll_photo_url,
                       u_rep.full_name as reported_by_name,
                       u_res.full_name as resolved_by_name,
                       repl_sr.roll_number as replacement_roll_code
                FROM roll_damages rd
                JOIN sellers s ON rd.seller_id = s.id
                JOIN seller_rolls sr ON rd.seller_roll_id = sr.id
                LEFT JOIN users u_rep ON rd.reported_by = u_rep.id
                LEFT JOIN users u_res ON rd.resolved_by = u_res.id
                LEFT JOIN seller_rolls repl_sr ON rd.replacement_roll_id = repl_sr.id
                WHERE 1=1";
        
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                rd.damage_number LIKE ?
                OR s.name LIKE ?
                OR s.company LIKE ?
                OR sr.roll_number LIKE ?
                OR sr.color LIKE ?
                OR sr.design_name LIKE ?
                OR rd.damage_reason LIKE ?
                OR rd.defect_notes LIKE ?
                OR rd.debit_note_number LIKE ?
                OR rd.replacement_roll_number LIKE ?
            )";
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s, $s, $s]);
        }

        if ($sellerId) {
            $sql .= " AND rd.seller_id = ?";
            $params[] = $sellerId;
        }

        if ($reason !== '') {
            $sql .= " AND rd.damage_reason = ?";
            $params[] = $reason;
        }

        if ($status !== '') {
            $sql .= " AND rd.status = ?";
            $params[] = $status;
        }

        if ($resolutionType !== '') {
            $sql .= " AND rd.resolution_type = ?";
            $params[] = $resolutionType;
        }

        $sql .= " ORDER BY rd.created_at DESC";
        $damages = Database::fetchAll($sql, $params);

        // Overall KPI stats
        $allDamages = Database::fetchAll(
            "SELECT rd.status, rd.resolution_type, rd.damaged_quantity, rd.damaged_weight, rd.loss_amount, rd.refund_amount
             FROM roll_damages rd"
        );

        $stats = [
            'total_damaged_records'  => count($allDamages),
            'total_damaged_weight'   => (float)array_sum(array_column($allDamages, 'damaged_weight')),
            'total_loss_amount'      => (float)array_sum(array_column($allDamages, 'loss_amount')),
            'total_refund_received'  => (float)array_sum(array_column($allDamages, 'refund_amount')),
            'count_logged'           => count(array_filter($allDamages, fn($d) => $d['status'] === 'LOGGED')),
            'count_return_initiated' => count(array_filter($allDamages, fn($d) => in_array($d['status'], ['RETURN_INITIATED', 'RETURNED_TO_SELLER']))),
            'count_refunded'         => count(array_filter($allDamages, fn($d) => $d['status'] === 'REFUND_RECEIVED')),
            'count_replacement'      => count(array_filter($allDamages, fn($d) => $d['status'] === 'REPLACEMENT_RECEIVED')),
            'count_closed'           => count(array_filter($allDamages, fn($d) => $d['status'] === 'CLOSED')),
        ];

        // Suppliers list for dropdown
        $sellers = Database::fetchAll("SELECT id, name, company FROM sellers WHERE seller_type = 'MATERIAL_SUPPLIER' ORDER BY name ASC");

        Response::view('production.damages.index', [
            'pageTitle'      => 'Fabric & Roll Damage Management',
            'activeModule'   => 'production',
            'activeMenu'     => 'prod-damages',
            'damages'        => $damages,
            'stats'          => $stats,
            'sellers'        => $sellers,
            'search'         => $search,
            'sellerId'       => $sellerId,
            'reason'         => $reason,
            'status'         => $status,
            'resolutionType' => $resolutionType,
            'breadcrumb'     => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Damages'],
            ],
        ]);
    }

    public function storeDamage(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $submittedQrCodes = $_POST['qr_codes'] ?? [];
        if (!is_array($submittedQrCodes)) {
            $submittedQrCodes = [];
        }
        if (empty($submittedQrCodes) && !empty($_POST['qr_code'])) {
            $submittedQrCodes = [$_POST['qr_code']];
        }
        $qrCodes = [];
        foreach ($submittedQrCodes as $submittedCode) {
            $code = strtoupper(trim(explode('|', trim((string)$submittedCode), 2)[0]));
            if ($code !== '' && !in_array($code, $qrCodes, true)) {
                $qrCodes[] = $code;
            }
        }
        $damageReason = trim($_POST['damage_reason'] ?? '');
        $defectNotes = trim($_POST['defect_notes'] ?? '');

        if (empty($qrCodes)) {
            Session::flash('error', 'Scan at least one valid fabric QR label before recording damage.');
            Response::back();
            return;
        }

        // Mutual Reason / Defect Notes validation:
        // Either select a reason OR provide notes with reason description
        if (empty($damageReason) && empty($defectNotes)) {
            Session::flash('error', 'Please select a Damage / Defect Reason or provide Defect Notes & Observations.');
            Response::back();
            return;
        }

        if ($damageReason === 'Other Fabric Damage' && empty($defectNotes)) {
            Session::flash('error', 'Please specify defect details in Defect Notes when "Other Fabric Damage" is selected.');
            Response::back();
            return;
        }

        if (empty($damageReason) && !empty($defectNotes)) {
            $damageReason = 'Other Fabric Damage';
        }

        $damageItems = [];
        $newDamageCountByRoll = [];
        foreach ($qrCodes as $qrCode) {
            if (!preg_match('/^([A-Z]{2}\d{4})(\d+)$/', $qrCode, $qrMatches)) {
                Session::flash('error', "QR code {$qrCode} is not a recognized fabric label.");
                Response::back();
                return;
            }

            $qrPrefix = $qrMatches[1];
            $qrSequence = (int)$qrMatches[2];
            $candidates = Database::fetchAll(
                "SELECT sr.*, s.name AS seller_name
                 FROM seller_rolls sr
                 JOIN sellers s ON s.id = sr.seller_id
                 WHERE sr.start_code LIKE ? AND sr.end_code LIKE ?",
                [$qrPrefix . '%', $qrPrefix . '%']
            );
            $matchingRolls = array_values(array_filter($candidates, static function (array $candidate) use ($qrPrefix, $qrSequence): bool {
                return strtoupper(substr((string)$candidate['start_code'], 0, 6)) === $qrPrefix
                    && strtoupper(substr((string)$candidate['end_code'], 0, 6)) === $qrPrefix
                    && $qrSequence >= (int)$candidate['start_seq']
                    && $qrSequence <= (int)$candidate['end_seq'];
            }));
            if (count($matchingRolls) !== 1) {
                Session::flash('error', count($matchingRolls) > 1
                    ? "QR code {$qrCode} matches more than one fabric roll."
                    : "No fabric roll was found for QR code {$qrCode}.");
                Response::back();
                return;
            }

            $roll = $matchingRolls[0];
            $sellerRollId = (int)$roll['id'];
            $deletedLabel = Database::fetchOne(
                "SELECT id FROM seller_roll_deleted_labels WHERE seller_roll_id = ? AND `sequence` = ?",
                [$sellerRollId, $qrSequence]
            );
            if ($deletedLabel) {
                Session::flash('error', "QR label {$qrCode} was deleted and cannot be used to report damage.");
                Response::back();
                return;
            }

            $existingQrDamage = Database::fetchOne(
                "SELECT damage_number FROM roll_damages WHERE qr_code = ? LIMIT 1",
                [$qrCode]
            );
            if ($existingQrDamage) {
                Session::flash('error', "QR label {$qrCode} is already recorded in damage report {$existingQrDamage['damage_number']}.");
                Response::back();
                return;
            }

            $rollQty = max(1, (int)($roll['quantity'] ?? 1));
            $rollWeight = (float)$roll['weight'];
            $rollPrice = (float)$roll['price'];
            $damagedWeight = round($rollWeight / $rollQty, 2);
            $ratePerKg = $rollWeight > 0 ? ($rollPrice / $rollWeight) : 0.0;
            $lossAmount = round($damagedWeight * $ratePerKg, 2);
            if ($lossAmount <= 0) {
                $lossAmount = round($rollPrice / $rollQty, 2);
            }

            $damageItems[] = [
                'qr_code' => $qrCode,
                'seller_id' => (int)$roll['seller_id'],
                'seller_roll_id' => $sellerRollId,
                'roll_number' => $roll['roll_number'],
                'damaged_weight' => $damagedWeight,
                'loss_amount' => $lossAmount,
            ];
            $newDamageCountByRoll[$sellerRollId] = ($newDamageCountByRoll[$sellerRollId] ?? 0) + 1;
        }

        foreach ($newDamageCountByRoll as $sellerRollId => $newCount) {
            $roll = Database::fetchOne(
                "SELECT sr.roll_number, sr.quantity - COALESCE(dmg.damaged_quantity, 0) AS available_quantity
                 FROM seller_rolls sr
                 LEFT JOIN (
                     SELECT seller_roll_id, SUM(damaged_quantity) AS damaged_quantity
                     FROM roll_damages
                     WHERE status NOT IN ('CLOSED', 'CANCELLED')
                     GROUP BY seller_roll_id
                 ) dmg ON dmg.seller_roll_id = sr.id
                 WHERE sr.id = ?",
                [$sellerRollId]
            );
            $availableQuantity = max(0, (int)($roll['available_quantity'] ?? 0));
            if (!$roll || $newCount > $availableQuantity) {
                $rollNumber = $roll['roll_number'] ?? (string)$sellerRollId;
                Session::flash('error', "The scanned labels for roll {$rollNumber} exceed its available damageable balance ({$availableQuantity} pcs).");
                Response::back();
                return;
            }
        }

        // Defect Photo / Evidence is required
        if (empty($_FILES['photo']['name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Defect Photo / Evidence is required.');
            Response::back();
            return;
        }

        // Handle photo upload (Cloudinary Cloud Storage)
        $photoUrl = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/damages', 'image');

        if (!$photoUrl) {
            Session::flash('error', 'Failed to upload Defect Photo. Please ensure Cloudinary is configured and a valid image is provided.');
            Response::back();
            return;
        }

        $userId = (int)(Session::get('user_id') ?? 1);
        $totalLossAmount = 0.0;

        try {
            Database::beginTransaction();
            $countToday = Database::fetchOne("SELECT COUNT(*) as cnt FROM roll_damages");
            $nextSeq = (int)($countToday['cnt'] ?? 0) + 1;
            foreach ($damageItems as $index => $item) {
                $damageNumber = 'DMG-ROL-' . date('Y') . '-' . sprintf("%04d", $nextSeq + $index);
                Database::execute(
                    "INSERT INTO roll_damages
                        (damage_number, seller_id, seller_roll_id, qr_code, damaged_quantity, damaged_weight, loss_amount, damage_reason, defect_notes, photo_url, status, resolution_type, reported_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, 'LOGGED', 'NONE', ?, NOW(), NOW())",
                    [
                        $damageNumber,
                        $item['seller_id'],
                        $item['seller_roll_id'],
                        $item['qr_code'],
                        $item['damaged_weight'],
                        $item['loss_amount'],
                        $damageReason,
                        $defectNotes ?: null,
                        $photoUrl,
                        $userId
                    ]
                );
                $damageId = (int)Database::lastInsertId();
                $totalLossAmount += (float)$item['loss_amount'];

                AuditService::log('production', 'create', 'roll_damages', $damageId, null, [
                    'damage_number' => $damageNumber,
                    'qr_code' => $item['qr_code'],
                    'seller_roll_id' => $item['seller_roll_id'],
                    'loss_amount' => $item['loss_amount'],
                    'reason' => $damageReason
                ]);
            }
            Database::commit();
            $redirectUrl = !empty($_POST['redirect_url']) ? $_POST['redirect_url'] : url('production/damages');
            Session::flash('success', count($damageItems) . ' scanned fabric piece(s) recorded as damage across ' . count($newDamageCountByRoll) . ' roll(s). Total loss valuation: ₹' . number_format($totalLossAmount, 2));
            Response::redirect($redirectUrl);
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollback();
            }
            Session::flash('error', 'Error logging roll damage: ' . $e->getMessage());
            Response::back();
        }
    }

    public function updateDamage(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $damageId = (int)$id;
        $existing = Database::fetchOne("SELECT * FROM roll_damages WHERE id = ?", [$damageId]);
        if (!$existing) {
            Session::flash('error', 'Damage record not found.');
            Response::back();
            return;
        }

        $sellerId = !empty($_POST['seller_id']) ? (int)$_POST['seller_id'] : (int)$existing['seller_id'];
        $sellerRollId = !empty($_POST['seller_roll_id']) ? (int)$_POST['seller_roll_id'] : (int)$existing['seller_roll_id'];
        $damageReason = trim($_POST['damage_reason'] ?? $existing['damage_reason']);
        $defectNotes = trim($_POST['defect_notes'] ?? '');
        $damagedQty = max(1, (int)($_POST['damaged_quantity'] ?? 1));

        if (!empty($existing['qr_code'])) {
            if ($sellerId !== (int)$existing['seller_id'] || $sellerRollId !== (int)$existing['seller_roll_id']) {
                Session::flash('error', 'A QR-linked damage record must remain assigned to its scanned supplier and roll.');
                Response::back();
                return;
            }
            $damagedQty = 1;
        }

        if (!$sellerId || !$sellerRollId) {
            Session::flash('error', 'Please select a Material Supplier and Defective Fabric Roll.');
            Response::back();
            return;
        }

        // Mutual Reason / Defect Notes validation:
        if (empty($damageReason) && empty($defectNotes)) {
            Session::flash('error', 'Please select a Damage / Defect Reason or provide Defect Notes & Observations.');
            Response::back();
            return;
        }

        if ($damageReason === 'Other Fabric Damage' && empty($defectNotes)) {
            Session::flash('error', 'Please specify defect details in Defect Notes when "Other Fabric Damage" is selected.');
            Response::back();
            return;
        }

        if (empty($damageReason) && !empty($defectNotes)) {
            $damageReason = 'Other Fabric Damage';
        }

        $roll = Database::fetchOne("SELECT * FROM seller_rolls WHERE id = ? AND seller_id = ?", [$sellerRollId, $sellerId]);
        if (!$roll) {
            Session::flash('error', 'Selected Fabric Roll not found for this supplier.');
            Response::back();
            return;
        }

        // Allocated rolls can still be damaged; only other active damage records reduce this limit.
        $existingDamages = Database::fetchOne(
            "SELECT COALESCE(SUM(damaged_quantity), 0) as dmg_qty, COALESCE(SUM(damaged_weight), 0.0) as dmg_weight 
             FROM roll_damages WHERE seller_roll_id = ? AND status NOT IN ('CLOSED', 'CANCELLED') AND id != ?",
            [$sellerRollId, $damageId]
        );
        $damagedExistingQty = (int)($existingDamages['dmg_qty'] ?? 0);
        $availQty = max(0, (int)$roll['quantity'] - $damagedExistingQty);

        if ($damagedQty > $availQty) {
            Session::flash('error', "Damaged Quantity ({$damagedQty} pcs) cannot exceed the available roll balance ({$availQty} pcs).");
            Response::back();
            return;
        }

        // Recalculate auto-calculated weight and financial loss valuation
        $rollWeight = (float)$roll['weight'];
        $rollPrice = (float)$roll['price'];
        $rollQty = max(1, (int)($roll['quantity'] ?? 1));

        $weightPerPc = $rollQty > 0 ? ($rollWeight / $rollQty) : 0.0;
        $damagedWeight = round($damagedQty * $weightPerPc, 2);

        $ratePerKg = $rollWeight > 0 ? ($rollPrice / $rollWeight) : 0.0;
        $lossAmount = round($damagedWeight * $ratePerKg, 2);
        if ($lossAmount <= 0 && $rollQty > 0) {
            $lossAmount = round(($rollPrice / $rollQty) * $damagedQty, 2);
        }

        // Handle photo upload (Cloudinary Cloud Storage)
        $photoUrl = $existing['photo_url'];
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/damages', 'image');
            if ($uploaded) {
                $photoUrl = $uploaded;
            }
        }

        if (empty($photoUrl)) {
            Session::flash('error', 'Defect Photo / Evidence is required.');
            Response::back();
            return;
        }

        try {
            Database::execute(
                "UPDATE roll_damages SET
                    seller_id = ?,
                    seller_roll_id = ?,
                    damaged_quantity = ?,
                    damaged_weight = ?,
                    loss_amount = ?,
                    damage_reason = ?,
                    defect_notes = ?,
                    photo_url = ?,
                    updated_at = NOW()
                 WHERE id = ?",
                [
                    $sellerId,
                    $sellerRollId,
                    $damagedQty,
                    $damagedWeight,
                    $lossAmount,
                    $damageReason,
                    $defectNotes ?: null,
                    $photoUrl,
                    $damageId
                ]
            );

            AuditService::log('production', 'update', 'roll_damages', $damageId, $existing, [
                'seller_id' => $sellerId,
                'seller_roll_id' => $sellerRollId,
                'damaged_weight' => $damagedWeight,
                'loss_amount' => $lossAmount,
                'reason' => $damageReason
            ]);

            Session::flash('success', "Damage record #{$existing['damage_number']} updated successfully.");
            Response::redirect(url('production/damages'));
        } catch (Exception $e) {
            Session::flash('error', 'Error updating damage record: ' . $e->getMessage());
            Response::back();
        }
    }

    public function resolveDamage(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $damageId = (int)$id;
        $damage = Database::fetchOne("SELECT * FROM roll_damages WHERE id = ?", [$damageId]);
        if (!$damage) {
            Session::flash('error', 'Damage record not found.');
            Response::back();
            return;
        }

        $status = trim($_POST['status'] ?? '');
        $resolutionType = trim($_POST['resolution_type'] ?? '');
        $refundAmount = max(0.0, (float)($_POST['refund_amount'] ?? 0.0));
        $debitNoteNumber = trim($_POST['debit_note_number'] ?? '');
        $replacementRollNumber = trim($_POST['replacement_roll_number'] ?? '');
        $resolutionNotes = trim($_POST['resolution_notes'] ?? '');

        if (empty($status)) {
            Session::flash('error', 'Update Damage Status is required. Please select a status.');
            Response::back();
            return;
        }

        if (empty($resolutionType) || $resolutionType === 'NONE') {
            Session::flash('error', 'Resolution Method is required. Please select a valid resolution method.');
            Response::back();
            return;
        }

        $userId = (int)(Session::get('user_id') ?? 1);

        try {
            // Optional: If replacement roll is received from supplier and user checked "Add to active rolls"
            $replacementRollId = null;
            if ($status === 'REPLACEMENT_RECEIVED' && !empty($_POST['create_replacement_roll'])) {
                $origRoll = Database::fetchOne("SELECT * FROM seller_rolls WHERE id = ?", [(int)$damage['seller_roll_id']]);
                if ($origRoll) {
                    Database::execute(
                        "INSERT INTO seller_rolls 
                            (seller_id, roll_number, color, design_name, design_count, quantity, weight, price, notes, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                        [
                            (int)$damage['seller_id'],
                            $replacementRollNumber ?: ($origRoll['roll_number'] . '-REPL'),
                            $origRoll['color'],
                            $origRoll['design_name'],
                            $origRoll['design_count'] ?? 1,
                            (int)$damage['damaged_quantity'],
                            (float)$damage['damaged_weight'],
                            (float)$damage['loss_amount'],
                            'Replacement roll received from supplier for damaged #' . $damage['damage_number'] . ' (' . ($origRoll['roll_number'] ?? '') . ')',
                        ]
                    );
                    $replacementRollId = (int)Database::lastInsertId();

                    // Recalculate seller summary totals
                    $allSellerRolls = Database::fetchAll("SELECT * FROM seller_rolls WHERE seller_id = ?", [(int)$damage['seller_id']]);
                    $computedRollCount = count($allSellerRolls);
                    $computedTotalWeight = array_sum(array_column($allSellerRolls, 'weight'));
                    $computedTotalPrice = array_sum(array_column($allSellerRolls, 'price'));
                    $uniqueColors = array_unique(array_filter(array_map('trim', array_column($allSellerRolls, 'color'))));
                    $uniqueDesigns = array_unique(array_filter(array_map('trim', array_column($allSellerRolls, 'design_name'))));

                    Database::execute(
                        "UPDATE sellers SET 
                            roll_count = ?, 
                            color_count = ?, 
                            design_count = ?, 
                            color_design_count = ?, 
                            roll_weight = ?, 
                            roll_price = ?, 
                            colors = ?
                         WHERE id = ?",
                        [
                            $computedRollCount,
                            count($uniqueColors),
                            count($uniqueDesigns),
                            count($uniqueColors) + count($uniqueDesigns),
                            $computedTotalWeight,
                            $computedTotalPrice,
                            !empty($uniqueColors) ? implode(', ', $uniqueColors) : '',
                            (int)$damage['seller_id']
                        ]
                    );
                }
            }

            Database::execute(
                "UPDATE roll_damages SET
                    status = ?,
                    resolution_type = ?,
                    refund_amount = ?,
                    debit_note_number = ?,
                    replacement_roll_number = ?,
                    replacement_roll_id = ?,
                    resolution_notes = ?,
                    resolved_by = ?,
                    resolved_at = NOW(),
                    updated_at = NOW()
                 WHERE id = ?",
                [
                    $status,
                    $resolutionType,
                    $refundAmount,
                    $debitNoteNumber ?: null,
                    $replacementRollNumber ?: null,
                    $replacementRollId ?: $damage['replacement_roll_id'],
                    $resolutionNotes ?: null,
                    $userId,
                    $damageId
                ]
            );

            AuditService::log('production', 'update', 'roll_damages', $damageId, $damage, [
                'status' => $status,
                'resolution_type' => $resolutionType,
                'refund_amount' => $refundAmount
            ]);

            Session::flash('success', "Damage record #{$damage['damage_number']} updated successfully. Status: {$status}");
            Response::redirect(url('production/damages'));
        } catch (Exception $e) {
            Session::flash('error', 'Error updating damage resolution: ' . $e->getMessage());
            Response::back();
        }
    }

    public function deleteDamage(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $damageId = (int)$id;
        $damage = Database::fetchOne("SELECT * FROM roll_damages WHERE id = ?", [$damageId]);
        if (!$damage) {
            Session::flash('error', 'Damage record not found.');
            Response::back();
            return;
        }

        try {
            Database::execute("DELETE FROM roll_damages WHERE id = ?", [$damageId]);
            AuditService::log('production', 'delete', 'roll_damages', $damageId, $damage, null);
            Session::flash('success', "Damage record #{$damage['damage_number']} deleted and roll inventory restored.");
            Response::redirect(url('production/damages'));
        } catch (Exception $e) {
            Session::flash('error', 'Error deleting damage record: ' . $e->getMessage());
            Response::back();
        }
    }

    // =========================================================================
    // CATALOG (In-House Manufactured Products)
    // =========================================================================

    public function catalogList(): void {
        $search = trim($_GET['search'] ?? '');
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $subcategoryId = !empty($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;

        $sql = "SELECT p.*, c.name as category_name, sc.name as subcategory_name, ssc.name as sub_subcategory_name, b.name as brand_name,
                       COALESCE(pv_summary.variant_count, 0) as variant_count,
                       COALESCE(pv_summary.min_prod_cost, 0) as min_prod_cost,
                       COALESCE(pv_summary.max_prod_cost, 0) as max_prod_cost,
                       COALESCE(pv_summary.min_selling_price, 0) as min_selling_price,
                       COALESCE(pv_summary.max_selling_price, 0) as max_selling_price,
                       COALESCE(pv_summary.min_mrp, 0) as min_mrp,
                       COALESCE(pv_summary.max_mrp, 0) as max_mrp
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN (
                    SELECT pv.product_id,
                           COUNT(pv.id) as variant_count,
                           MIN(COALESCE(pv.production_cost, pv.purchase_price, 0)) as min_prod_cost,
                           MAX(COALESCE(pv.production_cost, pv.purchase_price, 0)) as max_prod_cost,
                           MIN(pv.selling_price) as min_selling_price,
                           MAX(pv.selling_price) as max_selling_price,
                           MIN(pv.mrp) as min_mrp,
                           MAX(pv.mrp) as max_mrp
                    FROM product_variants pv
                    WHERE pv.is_active = 1
                    GROUP BY pv.product_id
                ) pv_summary ON pv_summary.product_id = p.id
                WHERE (p.source_type IN ('OWN_PRODUCTION', 'MANUFACTURED', 'IN_HOUSE') OR p.source_type IS NULL) 
                  AND p.is_active = 1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (
                p.name LIKE ? 
                OR p.code LIKE ? 
                OR p.product_code LIKE ?
                OR p.material LIKE ? 
                OR p.description LIKE ? 
                OR p.hsn_code LIKE ? 
                OR c.name LIKE ? 
                OR sc.name LIKE ? 
                OR ssc.name LIKE ? 
                OR b.name LIKE ?
                OR EXISTS (
                    SELECT 1 FROM product_variants pv2 
                    WHERE pv2.product_id = p.id 
                    AND (pv2.sku LIKE ? OR pv2.qr_code LIKE ?)
                )
            )";
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s, $s, $s, $s, $s]);
        }

        if ($categoryId) {
            $sql .= " AND (p.category_id = ? OR sc.category_id = ?)";
            $params[] = $categoryId;
            $params[] = $categoryId;
        }

        if ($subcategoryId) {
            $sql .= " AND p.subcategory_id = ?";
            $params[] = $subcategoryId;
        }

        $sql .= " ORDER BY p.created_at DESC";
        $products = Database::fetchAll($sql, $params);

        // Fetch categories and subcategories for filtering
        $categories = Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $subcategories = Database::fetchAll("SELECT * FROM subcategories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");

        Response::view('production.catalog.index', [
            'pageTitle'      => 'Production Catalog',
            'activeModule'   => 'production',
            'activeMenu'     => 'prod-catalog',
            'products'       => $products,
            'categories'     => $categories,
            'subcategories'  => $subcategories,
            'search'         => $search,
            'categoryId'     => $categoryId,
            'subcategoryId'  => $subcategoryId,
            'breadcrumb'     => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Catalog'],
            ],
        ]);
    }

    public function createCatalog(): void {
        $categories = Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $brands = Database::fetchAll("SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC");
        $sizes = Database::fetchAll("SELECT * FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        $colors = Database::fetchAll("SELECT * FROM colors WHERE is_active = 1 ORDER BY name ASC");

        // Generate auto style code suggestion
        $count = Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE source_type = 'OWN_PRODUCTION'")['c'] ?? 0;
        $suggestedCode = 'GRM-STY-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

        Response::view('production.catalog.create', [
            'pageTitle'     => 'Add Product Style | Production Catalog',
            'activeModule'  => 'production',
            'activeMenu'    => 'prod-catalog',
            'categories'    => $categories,
            'brands'        => $brands,
            'sizes'         => $sizes,
            'colors'        => $colors,
            'suggestedCode' => $suggestedCode,
            'breadcrumb'    => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Catalog', 'url' => url('production/catalog')],
                ['label' => 'Add Style Form'],
            ],
        ]);
    }

    public function storeCatalog(): void {
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $code = mb_strtoupper(trim($_POST['code'] ?? ''));
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null;
        $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
        $material = mb_strtoupper(trim($_POST['material'] ?? ''));
        $hsnCode = mb_strtoupper(trim($_POST['hsn_code'] ?? ''));
        $gstPercentage = max(0.0, (float)($_POST['gst_percentage'] ?? 0));
        $minStock = max(0, (int)($_POST['min_stock'] ?? 10));
        $maxStock = max(0, (int)($_POST['max_stock'] ?? 1000));
        $description = trim($_POST['description'] ?? '');

        // Validations
        if ($categoryId <= 0) {
            Session::flash('error', 'Please select a valid Category.');
            Response::redirect(url('production/catalog/create'));
            return;
        }

        if (empty($name)) {
            $catNames = [];
            if ($subSubcategoryId) {
                $ssc = Database::fetchOne("SELECT name FROM sub_subcategories WHERE id = ?", [$subSubcategoryId]);
                if ($ssc) $catNames[] = $ssc['name'];
            }
            if ($subcategoryId) {
                $sc = Database::fetchOne("SELECT name FROM subcategories WHERE id = ?", [$subcategoryId]);
                if ($sc) $catNames[] = $sc['name'];
            }
            $c = Database::fetchOne("SELECT name FROM categories WHERE id = ?", [$categoryId]);
            if ($c) $catNames[] = $c['name'];

            $name = !empty($catNames) ? implode(' - ', array_reverse($catNames)) : 'PRODUCT STYLE';
        }

        if (empty($code)) {
            $count = Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE source_type = 'OWN_PRODUCTION'")['c'] ?? 0;
            $code = 'GRM-STY-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
            $existing = Database::fetchOne("SELECT id FROM products WHERE code = ?", [$code]);
            if ($existing) {
                $code .= '-' . rand(10, 99);
            }
        }

        // Cloudinary Image Upload
        $imageUrl = null;
        $cloudinaryPublicId = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $cloudUrl = CloudinaryService::upload($_FILES['image'], 'grm_inventory/catalog', 'image');
            if ($cloudUrl) {
                $imageUrl = $cloudUrl;
                $cloudinaryPublicId = CloudinaryService::extractPublicId($cloudUrl) ?? ('catalog_' . time());
            }
        }

        $categoryCode = mb_strtoupper(trim($_POST['category_code'] ?? ''));
        $subcategoryCode = mb_strtoupper(trim($_POST['subcategory_code'] ?? ''));
        $subSubcategoryCode = mb_strtoupper(trim($_POST['sub_subcategory_code'] ?? ''));
        $qrCodePrefix = mb_strtoupper(trim($_POST['qr_code_prefix'] ?? ''));

        if (!empty($qrCodePrefix)) {
            $existingQr = Database::fetchOne(
                "SELECT id, name FROM products WHERE qr_code_prefix = ? LIMIT 1",
                [$qrCodePrefix]
            );
            if ($existingQr) {
                Session::flash('error', "The Whole Code for QR Code '{$qrCodePrefix}' already exists for Style '{$existingQr['name']}'. Please customize this code to make it unique.");
                Response::redirect(url('production/catalog/create'));
                return;
            }
        }

        Database::execute(
            "INSERT INTO products (name, code, category_id, subcategory_id, sub_subcategory_id, category_code, subcategory_code, sub_subcategory_code, qr_code_prefix, brand_id, source_type, material, description, hsn_code, gst_percentage, min_stock, max_stock, image_url, cloudinary_public_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'OWN_PRODUCTION', ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [
                $name,
                $code,
                $categoryId,
                $subcategoryId,
                $subSubcategoryId,
                $categoryCode,
                $subcategoryCode,
                $subSubcategoryCode,
                $qrCodePrefix,
                $brandId,
                $material,
                $description,
                $hsnCode,
                $gstPercentage,
                $minStock,
                $maxStock,
                $imageUrl,
                $cloudinaryPublicId
            ]
        );

        $productId = (int)Database::lastInsertId();

        // Process variants matrix or attributes
        $selectedSizes = !empty($_POST['sizes']) && is_array($_POST['sizes']) ? array_filter(array_map('intval', $_POST['sizes'])) : [];
        $selectedColors = !empty($_POST['colors']) && is_array($_POST['colors']) ? array_filter(array_map('intval', $_POST['colors'])) : [];

        $variantCount = 0;
        if (!empty($_POST['variants']) && is_array($_POST['variants'])) {
            foreach ($_POST['variants'] as $v) {
                $sizeId = !empty($v['size_id']) ? (int)$v['size_id'] : null;
                $colorId = !empty($v['color_id']) ? (int)$v['color_id'] : null;
                $sku = mb_strtoupper(trim($v['sku'] ?? ''));
                $prodCost = max(0.0, (float)($v['production_cost'] ?? 0));
                $mrp = max(0.0, (float)($v['mrp'] ?? 0));
                $sellingPrice = max(0.0, (float)($v['selling_price'] ?? 0));

                if (empty($sku)) {
                    $sku = $code;
                    if ($sizeId) {
                        $sCode = Database::fetchOne("SELECT code FROM sizes WHERE id = ?", [$sizeId])['code'] ?? $sizeId;
                        $sku .= '-' . $sCode;
                    }
                    if ($colorId) {
                        $cName = Database::fetchOne("SELECT name FROM colors WHERE id = ?", [$colorId])['name'] ?? $colorId;
                        $sku .= '-' . substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $cName)), 0, 4);
                    }
                }

                Database::execute(
                    "INSERT INTO product_variants (product_id, size_id, color_id, sku, mrp, purchase_price, production_cost, selling_price, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
                    [
                        $productId,
                        $sizeId,
                        $colorId,
                        $sku,
                        $mrp,
                        $prodCost,
                        $prodCost,
                        $sellingPrice
                    ]
                );
                $variantCount++;
            }
        } elseif (!empty($selectedSizes) || !empty($selectedColors)) {
            $sizesList = !empty($selectedSizes) ? $selectedSizes : [null];
            $colorsList = !empty($selectedColors) ? $selectedColors : [null];

            foreach ($sizesList as $sizeId) {
                $sCode = '';
                if ($sizeId) {
                    $sCode = Database::fetchOne("SELECT code FROM sizes WHERE id = ?", [$sizeId])['code'] ?? ('SZ' . $sizeId);
                }

                foreach ($colorsList as $colorId) {
                    $cCode = '';
                    if ($colorId) {
                        $cRow = Database::fetchOne("SELECT name FROM colors WHERE id = ?", [$colorId]);
                        $cCode = $cRow ? substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $cRow['name'])), 0, 4) : ('COL' . $colorId);
                    }

                    $skuParts = [$code];
                    if ($sCode) $skuParts[] = $sCode;
                    if ($cCode) $skuParts[] = $cCode;
                    $sku = implode('-', $skuParts);

                    Database::execute(
                        "INSERT INTO product_variants (product_id, size_id, color_id, sku, mrp, purchase_price, production_cost, selling_price, is_active)
                         VALUES (?, ?, ?, ?, 0.00, 0.00, 0.00, 0.00, 1)",
                        [
                            $productId,
                            $sizeId,
                            $colorId,
                            $sku
                        ]
                    );
                    $variantCount++;
                }
            }
        } else {
            // Default variant
            $defaultCost = max(0.0, (float)($_POST['default_production_cost'] ?? 0));
            $defaultMrp = max(0.0, (float)($_POST['default_mrp'] ?? 0));
            $defaultSelling = max(0.0, (float)($_POST['default_selling_price'] ?? 0));

            Database::execute(
                "INSERT INTO product_variants (product_id, sku, mrp, purchase_price, production_cost, selling_price, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)",
                [
                    $productId,
                    $code . '-STD',
                    $defaultMrp,
                    $defaultCost,
                    $defaultCost,
                    $defaultSelling
                ]
            );
            $variantCount = 1;
        }

        AuditService::log('production', 'create', 'products', (int)$productId, null, ['name' => $name, 'code' => $code, 'variants' => $variantCount]);

        Session::flash('success', "Product style '{$name}' with {$variantCount} variant(s) created successfully.");
        Response::redirect(url('production/catalog'));
    }

    public function viewCatalog(int $id): void {
        $product = Database::fetchOne(
            "SELECT p.*, c.name as category_name, sc.name as subcategory_name, ssc.name as sub_subcategory_name, b.name as brand_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.id = ?",
            [$id]
        );

        if (!$product) {
            Session::flash('error', 'Product style not found.');
            Response::redirect(url('production/catalog'));
            return;
        }

        $variants = Database::fetchAll(
            "SELECT pv.*, s.name as size_name, s.code as size_code, col.name as color_name, col.hex_code as color_hex
             FROM product_variants pv
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors col ON pv.color_id = col.id
             WHERE pv.product_id = ? AND pv.is_active = 1
             ORDER BY s.sort_order ASC, col.name ASC",
            [$id]
        );

        $programs = Database::fetchAll(
            "SELECT pp.*, u.name as unit_name
             FROM production_programs pp
             LEFT JOIN production_units u ON pp.production_unit_id = u.id
             WHERE pp.product_id = ?
             ORDER BY pp.created_at DESC LIMIT 5",
            [$id]
        );

        Response::view('production.catalog.view', [
            'pageTitle'    => $product['name'] . ' | Production Catalog',
            'activeModule' => 'production',
            'activeMenu'   => 'prod-catalog',
            'product'      => $product,
            'variants'     => $variants,
            'programs'     => $programs,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Catalog', 'url' => url('production/catalog')],
                ['label' => $product['code']],
            ],
        ]);
    }

    public function editCatalog(int $id): void {
        $product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) {
            Session::flash('error', 'Product style not found.');
            Response::redirect(url('production/catalog'));
            return;
        }

        $categories = Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        $subcategories = $product['category_id'] ? Database::fetchAll("SELECT * FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC", [$product['category_id']]) : [];
        $subSubcategories = $product['subcategory_id'] ? Database::fetchAll("SELECT * FROM sub_subcategories WHERE subcategory_id = ? AND is_active = 1 ORDER BY name ASC", [$product['subcategory_id']]) : [];
        $brands = Database::fetchAll("SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC");
        $sizes = Database::fetchAll("SELECT * FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        $colors = Database::fetchAll("SELECT * FROM colors WHERE is_active = 1 ORDER BY name ASC");

        $variants = Database::fetchAll(
            "SELECT pv.*, s.name as size_name, s.code as size_code, col.name as color_name, col.hex_code as color_hex
             FROM product_variants pv
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors col ON pv.color_id = col.id
             WHERE pv.product_id = ? AND pv.is_active = 1
             ORDER BY s.sort_order ASC, col.name ASC",
            [$id]
        );

        Response::view('production.catalog.edit', [
            'pageTitle'        => 'Edit ' . $product['name'] . ' | Catalog',
            'activeModule'     => 'production',
            'activeMenu'       => 'prod-catalog',
            'product'          => $product,
            'variants'         => $variants,
            'categories'       => $categories,
            'subcategories'    => $subcategories,
            'subSubcategories' => $subSubcategories,
            'brands'           => $brands,
            'sizes'            => $sizes,
            'colors'           => $colors,
            'breadcrumb'       => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Catalog', 'url' => url('production/catalog')],
                ['label' => 'Edit ' . $product['code']],
            ],
        ]);
    }

    public function updateCatalog(int $id): void {
        $product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) {
            Session::flash('error', 'Product style not found.');
            Response::redirect(url('production/catalog'));
            return;
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $code = mb_strtoupper(trim($_POST['code'] ?? ''));
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null;
        $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
        $material = mb_strtoupper(trim($_POST['material'] ?? ''));
        $hsnCode = mb_strtoupper(trim($_POST['hsn_code'] ?? ''));
        $gstPercentage = max(0.0, (float)($_POST['gst_percentage'] ?? 0));
        $minStock = max(0, (int)($_POST['min_stock'] ?? 10));
        $maxStock = max(0, (int)($_POST['max_stock'] ?? 1000));
        $description = trim($_POST['description'] ?? '');

        if ($categoryId <= 0) {
            Session::flash('error', 'Category is required.');
            Response::redirect(url('production/catalog/edit/' . $id));
            return;
        }

        if (empty($name)) {
            $catNames = [];
            if ($subSubcategoryId) {
                $ssc = Database::fetchOne("SELECT name FROM sub_subcategories WHERE id = ?", [$subSubcategoryId]);
                if ($ssc) $catNames[] = $ssc['name'];
            }
            if ($subcategoryId) {
                $sc = Database::fetchOne("SELECT name FROM subcategories WHERE id = ?", [$subcategoryId]);
                if ($sc) $catNames[] = $sc['name'];
            }
            $c = Database::fetchOne("SELECT name FROM categories WHERE id = ?", [$categoryId]);
            if ($c) $catNames[] = $c['name'];

            $name = !empty($catNames) ? implode(' - ', array_reverse($catNames)) : ($product['name'] ?? 'PRODUCT STYLE');
        }

        if (empty($code)) {
            $code = $product['code'];
        }

        $imageUrl = $product['image_url'];
        $cloudinaryPublicId = $product['cloudinary_public_id'];
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $cloudUrl = CloudinaryService::upload($_FILES['image'], 'grm_inventory/catalog', 'image');
            if ($cloudUrl) {
                $imageUrl = $cloudUrl;
                $cloudinaryPublicId = CloudinaryService::extractPublicId($cloudUrl) ?? ('catalog_' . time());
            }
        }

        $categoryCode = mb_strtoupper(trim($_POST['category_code'] ?? ''));
        $subcategoryCode = mb_strtoupper(trim($_POST['subcategory_code'] ?? ''));
        $subSubcategoryCode = mb_strtoupper(trim($_POST['sub_subcategory_code'] ?? ''));
        $qrCodePrefix = mb_strtoupper(trim($_POST['qr_code_prefix'] ?? ''));

        if (!empty($qrCodePrefix)) {
            $existingQr = Database::fetchOne(
                "SELECT id, name FROM products WHERE qr_code_prefix = ? AND id != ? LIMIT 1",
                [$qrCodePrefix, $id]
            );
            if ($existingQr) {
                Session::flash('error', "The Whole Code for QR Code '{$qrCodePrefix}' already exists for Style '{$existingQr['name']}'. Please customize this code to make it unique.");
                Response::redirect(url('production/catalog/edit/' . $id));
                return;
            }
        }

        Database::execute(
            "UPDATE products SET name = ?, code = ?, category_id = ?, subcategory_id = ?, sub_subcategory_id = ?, category_code = ?, subcategory_code = ?, sub_subcategory_code = ?, qr_code_prefix = ?, brand_id = ?, material = ?, description = ?, hsn_code = ?, gst_percentage = ?, min_stock = ?, max_stock = ?, image_url = ?, cloudinary_public_id = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $name,
                $code,
                $categoryId,
                $subcategoryId,
                $subSubcategoryId,
                $categoryCode,
                $subcategoryCode,
                $subSubcategoryCode,
                $qrCodePrefix,
                $brandId,
                $material,
                $description,
                $hsnCode,
                $gstPercentage,
                $minStock,
                $maxStock,
                $imageUrl,
                $cloudinaryPublicId,
                $id
            ]
        );

        // Sync variants from submitted variants or selected attributes
        $selectedSizes = !empty($_POST['sizes']) && is_array($_POST['sizes']) ? array_filter(array_map('intval', $_POST['sizes'])) : [];
        $selectedColors = !empty($_POST['colors']) && is_array($_POST['colors']) ? array_filter(array_map('intval', $_POST['colors'])) : [];

        if (!empty($_POST['variants']) && is_array($_POST['variants'])) {
            try {
                Database::execute("DELETE FROM product_variants WHERE product_id = ?", [$id]);
            } catch (Exception $e) {
                Database::execute("UPDATE product_variants SET is_active = 0 WHERE product_id = ?", [$id]);
            }

            foreach ($_POST['variants'] as $v) {
                $sizeId = !empty($v['size_id']) ? (int)$v['size_id'] : null;
                $colorId = !empty($v['color_id']) ? (int)$v['color_id'] : null;
                $sku = mb_strtoupper(trim($v['sku'] ?? ''));
                $prodCost = max(0.0, (float)($v['production_cost'] ?? 0));
                $mrp = max(0.0, (float)($v['mrp'] ?? 0));
                $sellingPrice = max(0.0, (float)($v['selling_price'] ?? 0));

                if (empty($sku)) {
                    $sku = $code . ($sizeId ? '-' . $sizeId : '') . ($colorId ? '-' . $colorId : '');
                }

                Database::execute(
                    "INSERT INTO product_variants (product_id, size_id, color_id, sku, mrp, purchase_price, production_cost, selling_price, is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                     ON DUPLICATE KEY UPDATE product_id=VALUES(product_id), size_id=VALUES(size_id), color_id=VALUES(color_id), mrp=VALUES(mrp), purchase_price=VALUES(purchase_price), production_cost=VALUES(production_cost), selling_price=VALUES(selling_price), is_active=1",
                    [
                        $id,
                        $sizeId,
                        $colorId,
                        $sku,
                        $mrp,
                        $prodCost,
                        $prodCost,
                        $sellingPrice
                    ]
                );
            }
        } elseif (!empty($selectedSizes) || !empty($selectedColors)) {
            try {
                Database::execute("DELETE FROM product_variants WHERE product_id = ?", [$id]);
            } catch (Exception $e) {
                Database::execute("UPDATE product_variants SET is_active = 0 WHERE product_id = ?", [$id]);
            }

            $sizesList = !empty($selectedSizes) ? $selectedSizes : [null];
            $colorsList = !empty($selectedColors) ? $selectedColors : [null];

            foreach ($sizesList as $sizeId) {
                $sCode = '';
                if ($sizeId) {
                    $sCode = Database::fetchOne("SELECT code FROM sizes WHERE id = ?", [$sizeId])['code'] ?? ('SZ' . $sizeId);
                }

                foreach ($colorsList as $colorId) {
                    $cCode = '';
                    if ($colorId) {
                        $cRow = Database::fetchOne("SELECT name FROM colors WHERE id = ?", [$colorId]);
                        $cCode = $cRow ? substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', $cRow['name'])), 0, 4) : ('COL' . $colorId);
                    }

                    $skuParts = [$code];
                    if ($sCode) $skuParts[] = $sCode;
                    if ($cCode) $skuParts[] = $cCode;
                    $sku = implode('-', $skuParts);

                    Database::execute(
                        "INSERT INTO product_variants (product_id, size_id, color_id, sku, mrp, purchase_price, production_cost, selling_price, is_active)
                         VALUES (?, ?, ?, ?, 0.00, 0.00, 0.00, 0.00, 1)
                         ON DUPLICATE KEY UPDATE product_id=VALUES(product_id), size_id=VALUES(size_id), color_id=VALUES(color_id), is_active=1",
                        [
                            $id,
                            $sizeId,
                            $colorId,
                            $sku
                        ]
                    );
                }
            }
        }

        AuditService::log('production', 'update', 'products', (int)$id, null, ['name' => $name, 'code' => $code]);
        Session::flash('success', "Product style '{$name}' updated successfully.");
        Response::redirect(url('production/catalog'));
    }

    public function deleteCatalog(int $id): void {
        $ts = time();
        Database::execute("UPDATE products SET is_active = 0, code = CONCAT(code, '_DEL_', ?) WHERE id = ?", [$ts, $id]);
        Database::execute("UPDATE product_variants SET is_active = 0, sku = CONCAT(sku, '_DEL_', ?), qr_code = CONCAT(qr_code, '_DEL_', ?) WHERE product_id = ?", [$ts, $ts, $id]);
        AuditService::log('production', 'delete', 'products', (int)$id, null, ['is_active' => 0]);
        Session::flash('success', 'Product style removed from catalog.');
        Response::redirect(url('production/catalog'));
    }

    // =========================================================================
    // AJAX CASCADING & QUICK-ADD ENDPOINTS
    // =========================================================================

    public function ajaxCheckQRCode(): void {
        try {
            $code = mb_strtoupper(trim($_GET['code'] ?? ''));
            $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;

            if (empty($code)) {
                Response::json(['exists' => false, 'code' => '']);
                return;
            }

            $query = "SELECT p.id, p.name, p.code, p.qr_code_prefix,
                             c.name as category_name, sc.name as subcategory_name, ssc.name as sub_subcategory_name
                      FROM products p
                      LEFT JOIN categories c ON c.id = p.category_id
                      LEFT JOIN subcategories sc ON sc.id = p.subcategory_id
                      LEFT JOIN sub_subcategories ssc ON ssc.id = p.sub_subcategory_id
                      WHERE p.qr_code_prefix = ?";
            $params = [$code];
            if ($excludeId > 0) {
                $query .= " AND p.id != ?";
                $params[] = $excludeId;
            }
            $query .= " LIMIT 1";

            $existing = Database::fetchOne($query, $params);

            if ($existing) {
                $hierarchy = array_filter([$existing['category_name'], $existing['subcategory_name'], $existing['sub_subcategory_name']]);
                $hierarchyText = !empty($hierarchy) ? implode(' > ', $hierarchy) : '';

                Response::json([
                    'exists' => true,
                    'code' => $code,
                    'message' => "The Whole Code for QR Code '{$code}' already exists for Style: '{$existing['name']}'" . ($hierarchyText ? " ({$hierarchyText})" : "") . ". Please customize this code to avoid duplicates.",
                    'product' => [
                        'id' => (int)$existing['id'],
                        'name' => $existing['name'],
                        'code' => $existing['code'],
                        'hierarchy' => $hierarchyText
                    ]
                ]);
                return;
            }

            Response::json(['exists' => false, 'code' => $code]);
        } catch (\Throwable $e) {
            Response::json(['exists' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function ajaxCategories(): void {
        try {
            $sql = "SELECT c.id, c.name, c.slug FROM categories c WHERE c.is_active = 1";
            if (!empty($_GET['catalog_only'])) {
                $sql .= " AND EXISTS (
                    SELECT 1 FROM products p
                    WHERE p.category_id = c.id
                      AND p.is_active = 1
                      AND (p.source_type IN ('OWN_PRODUCTION', 'MANUFACTURED', 'IN_HOUSE') OR p.source_type IS NULL)
                )";
            }
            $sql .= " ORDER BY c.sort_order ASC, c.name ASC";
            $categories = Database::fetchAll($sql);
            Response::json($categories);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function ajaxQuickAddCategory(): void {
        try {
            $name = mb_strtoupper(trim($_POST['name'] ?? ''));
            if (empty($name)) {
                Response::json(['success' => false, 'message' => 'Main Category name is required.'], 400);
                return;
            }
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $existing = Database::fetchOne("SELECT id, name, is_active FROM categories WHERE name = ? OR slug = ?", [$name, $slug]);
            if ($existing) {
                Database::execute("UPDATE categories SET is_active = 1, name = ?, updated_at = NOW() WHERE id = ?", [$name, $existing['id']]);
                Response::json(['success' => true, 'id' => (int)$existing['id'], 'name' => $name]);
                return;
            }

            $maxSort = Database::fetchOne("SELECT MAX(sort_order) as m FROM categories")['m'] ?? 0;
            
            $codeBase = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 3));
            if (empty($codeBase)) $codeBase = 'CAT';
            $code = $codeBase;
            $counter = 1;
            while (Database::fetchOne("SELECT id FROM categories WHERE code = ?", [$code])) {
                $code = $codeBase . $counter;
                $counter++;
            }

            Database::execute(
                "INSERT INTO categories (name, code, slug, sort_order, is_active) VALUES (?, ?, ?, ?, 1)",
                [$name, $code, $slug, ((int)$maxSort + 1)]
            );
            $id = (int)Database::lastInsertId();
            Response::json(['success' => true, 'id' => $id, 'name' => $name]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error creating category: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxUpdateCategory(int $id): void {
        try {
            $name = mb_strtoupper(trim($_POST['name'] ?? ''));
            if (empty($name)) {
                Response::json(['success' => false, 'message' => 'Main Category name is required.'], 400);
                return;
            }
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $existing = Database::fetchOne(
                "SELECT id, name FROM categories WHERE (name = ? OR slug = ?) AND id != ?",
                [$name, $slug, $id]
            );
            if ($existing) {
                Response::json(['success' => false, 'message' => "Category '{$existing['name']}' already exists."], 400);
                return;
            }

            Database::execute(
                "UPDATE categories SET name = ?, slug = ?, is_active = 1, updated_at = NOW() WHERE id = ?",
                [$name, $slug, $id]
            );
            Response::json(['success' => true, 'id' => $id, 'name' => $name]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error updating category: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxDeleteCategory(int $id): void {
        try {
            $count = Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE category_id = ? AND is_active = 1", [$id])['c'] ?? 0;
            if ($count > 0) {
                Response::json(['success' => false, 'message' => "Cannot delete this category because {$count} product style(s) are linked to it."], 400);
                return;
            }

            Database::execute("UPDATE categories SET is_active = 0 WHERE id = ?", [$id]);
            Database::execute("UPDATE subcategories SET is_active = 0 WHERE category_id = ?", [$id]);
            Response::json(['success' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error deleting category: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxSubcategories(int $categoryId): void {
        try {
            $sql = "SELECT sc.id, sc.name, sc.slug FROM subcategories sc
                    WHERE sc.category_id = ? AND sc.is_active = 1";
            if (!empty($_GET['catalog_only'])) {
                $sql .= " AND EXISTS (
                    SELECT 1 FROM products p
                    WHERE p.subcategory_id = sc.id
                      AND p.is_active = 1
                      AND (p.source_type IN ('OWN_PRODUCTION', 'MANUFACTURED', 'IN_HOUSE') OR p.source_type IS NULL)
                )";
            }
            $sql .= " ORDER BY sc.sort_order ASC, sc.name ASC";
            $subcats = Database::fetchAll(
                $sql,
                [$categoryId]
            );
            Response::json($subcats);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function ajaxQuickAddSubcategory(): void {
        try {
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $name = mb_strtoupper(trim($_POST['name'] ?? ''));
            if ($categoryId <= 0 || empty($name)) {
                Response::json(['success' => false, 'message' => 'Category and Subcategory name are required.'], 400);
                return;
            }
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $existing = Database::fetchOne("SELECT id, name, is_active FROM subcategories WHERE category_id = ? AND (name = ? OR slug = ?)", [$categoryId, $name, $slug]);
            if ($existing) {
                Database::execute("UPDATE subcategories SET is_active = 1, name = ?, updated_at = NOW() WHERE id = ?", [$name, $existing['id']]);
                Response::json(['success' => true, 'id' => (int)$existing['id'], 'name' => $name]);
                return;
            }

            $codeBase = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 3));
            if (empty($codeBase)) $codeBase = 'SUB';
            $code = $codeBase;
            $counter = 1;
            while (Database::fetchOne("SELECT id FROM subcategories WHERE code = ?", [$code])) {
                $code = $codeBase . $counter;
                $counter++;
            }

            Database::execute(
                "INSERT INTO subcategories (category_id, name, code, slug, is_active) VALUES (?, ?, ?, ?, 1)",
                [$categoryId, $name, $code, $slug]
            );
            $id = (int)Database::lastInsertId();
            Response::json(['success' => true, 'id' => $id, 'name' => $name]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error creating subcategory: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxUpdateSubcategory(int $id): void {
        try {
            $name = mb_strtoupper(trim($_POST['name'] ?? ''));
            if (empty($name)) {
                Response::json(['success' => false, 'message' => 'Subcategory name is required.'], 400);
                return;
            }
            $subcat = Database::fetchOne("SELECT category_id FROM subcategories WHERE id = ?", [$id]);
            if (!$subcat) {
                Response::json(['success' => false, 'message' => 'Subcategory not found.'], 404);
                return;
            }
            $categoryId = (int)$subcat['category_id'];
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $existing = Database::fetchOne(
                "SELECT id, name FROM subcategories WHERE category_id = ? AND (name = ? OR slug = ?) AND id != ?",
                [$categoryId, $name, $slug, $id]
            );
            if ($existing) {
                Response::json(['success' => false, 'message' => "Subcategory '{$existing['name']}' already exists in this category."], 400);
                return;
            }

            Database::execute(
                "UPDATE subcategories SET name = ?, slug = ?, is_active = 1, updated_at = NOW() WHERE id = ?",
                [$name, $slug, $id]
            );
            Response::json(['success' => true, 'id' => $id, 'name' => $name]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error updating subcategory: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxDeleteSubcategory(int $id): void {
        try {
            $count = Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE subcategory_id = ? AND is_active = 1", [$id])['c'] ?? 0;
            if ($count > 0) {
                Response::json(['success' => false, 'message' => "Cannot delete this subcategory because {$count} product style(s) are linked to it."], 400);
                return;
            }
            Database::execute("UPDATE subcategories SET is_active = 0 WHERE id = ?", [$id]);
            Database::execute("UPDATE sub_subcategories SET is_active = 0 WHERE subcategory_id = ?", [$id]);
            Response::json(['success' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error deleting subcategory: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxSubSubcategories(int $subcategoryId): void {
        try {
            $sql = "SELECT ssc.id, ssc.name, ssc.slug FROM sub_subcategories ssc
                    WHERE ssc.subcategory_id = ? AND ssc.is_active = 1";
            if (!empty($_GET['catalog_only'])) {
                $sql .= " AND EXISTS (
                    SELECT 1 FROM products p
                    WHERE p.sub_subcategory_id = ssc.id
                      AND p.is_active = 1
                      AND (p.source_type IN ('OWN_PRODUCTION', 'MANUFACTURED', 'IN_HOUSE') OR p.source_type IS NULL)
                )";
            }
            $sql .= " ORDER BY ssc.name ASC";
            $subSubcats = Database::fetchAll(
                $sql,
                [$subcategoryId]
            );
            Response::json($subSubcats);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function ajaxQuickAddSubSubcategory(): void {
        try {
            $subcategoryId = (int)($_POST['subcategory_id'] ?? 0);
            $name = mb_strtoupper(trim($_POST['name'] ?? ''));
            if ($subcategoryId <= 0 || empty($name)) {
                Response::json(['success' => false, 'message' => 'Subcategory and Sub-subcategory name are required.'], 400);
                return;
            }
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $existing = Database::fetchOne("SELECT id, name, is_active FROM sub_subcategories WHERE subcategory_id = ? AND (name = ? OR slug = ?)", [$subcategoryId, $name, $slug]);
            if ($existing) {
                Database::execute("UPDATE sub_subcategories SET is_active = 1, name = ?, updated_at = NOW() WHERE id = ?", [$name, $existing['id']]);
                Response::json(['success' => true, 'id' => (int)$existing['id'], 'name' => $name]);
                return;
            }

            $codeBase = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 3));
            if (empty($codeBase)) $codeBase = 'SSC';
            $code = $codeBase;
            $counter = 1;
            while (Database::fetchOne("SELECT id FROM sub_subcategories WHERE code = ?", [$code])) {
                $code = $codeBase . $counter;
                $counter++;
            }

            Database::execute(
                "INSERT INTO sub_subcategories (subcategory_id, name, code, slug, is_active) VALUES (?, ?, ?, ?, 1)",
                [$subcategoryId, $name, $code, $slug]
            );
            $id = (int)Database::lastInsertId();
            Response::json(['success' => true, 'id' => $id, 'name' => $name]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error creating sub-subcategory: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxUpdateSubSubcategory(int $id): void {
        try {
            $name = mb_strtoupper(trim($_POST['name'] ?? ''));
            if (empty($name)) {
                Response::json(['success' => false, 'message' => 'Sub-subcategory name is required.'], 400);
                return;
            }
            $subSubcat = Database::fetchOne("SELECT subcategory_id FROM sub_subcategories WHERE id = ?", [$id]);
            if (!$subSubcat) {
                Response::json(['success' => false, 'message' => 'Sub-subcategory not found.'], 404);
                return;
            }
            $subcategoryId = (int)$subSubcat['subcategory_id'];
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

            $existing = Database::fetchOne(
                "SELECT id, name FROM sub_subcategories WHERE subcategory_id = ? AND (name = ? OR slug = ?) AND id != ?",
                [$subcategoryId, $name, $slug, $id]
            );
            if ($existing) {
                Response::json(['success' => false, 'message' => "Sub-subcategory '{$existing['name']}' already exists."], 400);
                return;
            }

            Database::execute(
                "UPDATE sub_subcategories SET name = ?, slug = ?, is_active = 1, updated_at = NOW() WHERE id = ?",
                [$name, $slug, $id]
            );
            Response::json(['success' => true, 'id' => $id, 'name' => $name]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error updating sub-subcategory: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxDeleteSubSubcategory(int $id): void {
        try {
            $count = Database::fetchOne("SELECT COUNT(*) as c FROM products WHERE sub_subcategory_id = ? AND is_active = 1", [$id])['c'] ?? 0;
            if ($count > 0) {
                Response::json(['success' => false, 'message' => "Cannot delete this sub-subcategory because {$count} product style(s) are linked to it."], 400);
                return;
            }
            Database::execute("UPDATE sub_subcategories SET is_active = 0 WHERE id = ?", [$id]);
            Response::json(['success' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Error deleting sub-subcategory: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxQuickAddBrand(): void {
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        if (empty($name)) {
            Response::json(['success' => false, 'message' => 'Brand name is required.'], 400);
            return;
        }
        Database::execute("INSERT INTO brands (name, is_active) VALUES (?, 1)", [$name]);
        $id = (int)Database::lastInsertId();
        Response::json(['success' => true, 'id' => $id, 'name' => $name]);
    }

    public function ajaxQuickAddSize(): void {
        $code = mb_strtoupper(trim($_POST['code'] ?? ''));
        $name = mb_strtoupper(trim($_POST['name'] ?? $code));
        if (empty($code)) {
            Response::json(['success' => false, 'message' => 'Size code is required.'], 400);
            return;
        }
        Database::execute("INSERT INTO sizes (name, code, is_active) VALUES (?, ?, 1)", [$name, $code]);
        $id = (int)Database::lastInsertId();
        Response::json(['success' => true, 'id' => $id, 'code' => $code, 'name' => $name]);
    }

    public function ajaxDeleteSize(int $id): void {
        Database::execute("UPDATE sizes SET is_active = 0 WHERE id = ?", [$id]);
        Response::json(['success' => true, 'id' => $id]);
    }

    public function ajaxQuickAddColor(): void {
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $hex = trim($_POST['hex_code'] ?? '#000000');
        if (empty($name)) {
            Response::json(['success' => false, 'message' => 'Color name is required.'], 400);
            return;
        }
        // Check if color already exists (case-insensitive)
        $existing = Database::fetchOne("SELECT id, name, hex_code FROM colors WHERE name = ?", [$name]);
        if ($existing) {
            // Re-activate if inactive and return existing
            Database::execute("UPDATE colors SET is_active = 1, hex_code = ? WHERE id = ?", [$hex, $existing['id']]);
            Response::json(['success' => true, 'id' => (int)$existing['id'], 'name' => $name, 'hex_code' => $hex]);
            return;
        }
        Database::execute("INSERT INTO colors (name, hex_code, is_active) VALUES (?, ?, 1)", [$name, $hex]);
        $id = (int)Database::lastInsertId();
        Response::json(['success' => true, 'id' => $id, 'name' => $name, 'hex_code' => $hex]);
    }

    public function ajaxDeleteColor(int $id): void {
        Database::execute("UPDATE colors SET is_active = 0 WHERE id = ?", [$id]);
        Response::json(['success' => true, 'id' => $id]);
    }

    public function searchProducts(): void {
        $q = trim($_GET['q'] ?? '');
        $products = Database::fetchAll(
            "SELECT p.id, p.name, p.code, p.material, c.name as category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.is_active = 1 AND (p.name LIKE ? OR p.code LIKE ?)
             LIMIT 20",
            ['%' . $q . '%', '%' . $q . '%']
        );
        Response::json($products);
    }

    public function searchVariants(): void {
        $q = trim($_GET['q'] ?? '');
        $variants = Database::fetchAll(
            "SELECT pv.id, pv.sku, pv.production_cost, pv.selling_price, pv.mrp, p.name as product_name, s.code as size_code, col.name as color_name
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors col ON pv.color_id = col.id
             WHERE pv.is_active = 1 AND (pv.sku LIKE ? OR p.name LIKE ?)
             LIMIT 20",
            ['%' . $q . '%', '%' . $q . '%']
        );
        Response::json($variants);
    }

    public function checkStock(int $variantId): void {
        $balance = Database::fetchOne(
            "SELECT COALESCE(SUM(quantity), 0) as total_qty FROM stock_balances WHERE variant_id = ?",
            [$variantId]
        );
        Response::json(['variant_id' => $variantId, 'quantity' => (int)($balance['total_qty'] ?? 0)]);
    }

    // =========================================================================
    // PRODUCTION PROGRAMS
    // =========================================================================

    public function programList(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $unitFilter = trim($_GET['unit_id'] ?? ($_GET['production_unit_id'] ?? ''));
        if ($scopedUnitId !== null) {
            $unitFilter = (string)$scopedUnitId;
        }

        $filters = [
            'status'             => trim($_GET['status'] ?? ''),
            'production_unit_id' => $unitFilter,
            'priority'           => trim($_GET['priority'] ?? ''),
            'search'             => trim($_GET['search'] ?? ''),
        ];

        $programs = ProductionProgram::getAll($filters);
        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : Database::fetchAll("SELECT * FROM production_units WHERE is_active = 1 ORDER BY name ASC");

        $programRollsMap = [];
        foreach ($programs as $prog) {
            $programRollsMap[$prog['id']] = ProductionProgram::getRolls((int)$prog['id']);
        }

        Response::view('production.programs.index', [
            'pageTitle'        => 'Production Programs',
            'activeModule'     => 'production',
            'activeMenu'       => 'programs',
            'programs'         => $programs,
            'programRollsMap'  => $programRollsMap,
            'units'            => $units,
            'filters'          => $filters,
            'scopedUnitId'     => $scopedUnitId,
            'breadcrumb'       => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Programs'],
            ],
        ]);
    }

    public function createProgram(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : Database::fetchAll("SELECT * FROM production_units WHERE is_active = 1 ORDER BY name ASC");
        $categories = Database::fetchAll(
            "SELECT c.* FROM categories c
             WHERE c.is_active = 1
               AND EXISTS (
                   SELECT 1 FROM products p
                   WHERE p.category_id = c.id
                     AND p.is_active = 1
                     AND (p.source_type IN ('OWN_PRODUCTION', 'MANUFACTURED', 'IN_HOUSE') OR p.source_type IS NULL)
               )
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        $users = Database::fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");

        // Fetch raw material rolls from seller_rolls joined with sellers with live usage, damages, and remaining stock
        $rolls = Database::fetchAll(
            "SELECT sr.*, s.name as seller_name, s.company as seller_company, s.material_type,
                    COALESCE(u.used_quantity, 0) as used_quantity,
                    COALESCE(u.used_weight, 0) as used_weight,
                    COALESCE(u.used_price, 0) as used_price,
                    COALESCE(dmg.damaged_quantity, 0) as damaged_quantity,
                    COALESCE(dmg.damaged_weight, 0) as damaged_weight,
                    COALESCE(dmg.damaged_price, 0) as damaged_price,
                    GREATEST(0, sr.quantity - COALESCE(u.used_quantity, 0) - COALESCE(dmg.damaged_quantity, 0)) as available_quantity,
                    GREATEST(0.0, sr.weight - COALESCE(u.used_weight, 0) - COALESCE(dmg.damaged_weight, 0)) as available_weight,
                    GREATEST(0.0, sr.price - COALESCE(u.used_price, 0) - COALESCE(dmg.damaged_price, 0)) as available_price,
                    dmg.damage_reason,
                    dmg.damage_status
             FROM seller_rolls sr
             JOIN sellers s ON sr.seller_id = s.id
             LEFT JOIN (
                 SELECT ppr.seller_roll_id, 
                        SUM(ppr.roll_qty) as used_quantity,
                        SUM(ppr.roll_weight) as used_weight,
                        SUM(ppr.roll_price) as used_price
                 FROM production_program_rolls ppr
                 JOIN production_programs pp ON ppr.production_program_id = pp.id
                 WHERE pp.is_active = 1 AND pp.status != 'CANCELLED'
                 GROUP BY ppr.seller_roll_id
             ) u ON sr.id = u.seller_roll_id
             LEFT JOIN (
                 SELECT rd.seller_roll_id,
                        SUM(rd.damaged_quantity) as damaged_quantity,
                        SUM(rd.damaged_weight) as damaged_weight,
                        SUM(rd.loss_amount) as damaged_price,
                        MAX(rd.damage_reason) as damage_reason,
                        MAX(rd.status) as damage_status
                 FROM roll_damages rd
                 WHERE rd.status NOT IN ('CLOSED', 'CANCELLED')
                 GROUP BY rd.seller_roll_id
             ) dmg ON sr.id = dmg.seller_roll_id
             WHERE s.is_active = 1
             ORDER BY sr.id DESC"
        );

        // Auto-generate suggested program code PRG-YYYYMMDD-0001
        $count = Database::fetchOne("SELECT COUNT(*) as c FROM production_programs")['c'] ?? 0;
        $suggestedProgramNumber = 'PRG-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

        Response::view('production.programs.create', [
            'pageTitle'              => 'Create Production Program',
            'activeModule'           => 'production',
            'activeMenu'             => 'programs',
            'units'                  => $units,
            'categories'             => $categories,
            'rolls'                  => $rolls,
            'users'                  => $users,
            'scopedUnitId'           => $scopedUnitId,
            'suggestedProgramNumber' => $suggestedProgramNumber,
            'breadcrumb'             => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Programs', 'url' => url('production/programs')],
                ['label' => 'Create Program'],
            ],
        ]);
    }

    public function storeProgram(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId !== null) {
            $_POST['production_unit_id'] = $scopedUnitId;
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $programNumber = mb_strtoupper(trim($_POST['program_number'] ?? ''));
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : (!empty($_POST['target_date']) ? $_POST['target_date'] : date('Y-m-d', strtotime('+14 days')));
        $unitId = !empty($_POST['production_unit_id']) ? (int)$_POST['production_unit_id'] : $scopedUnitId;
        $priority = $_POST['priority'] ?? 'MEDIUM';
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'Program Name is required.');
            Response::back();
            return;
        }

        // Process multiple rolls from $_POST['rolls']
        $submittedRolls = !empty($_POST['rolls']) && is_array($_POST['rolls']) ? $_POST['rolls'] : [];
        $validRolls = [];
        $totalRollWeight = 0.0;
        $totalRollPrice = 0.0;
        $totalRollQty = 0;

        foreach ($submittedRolls as $r) {
            $rId = !empty($r['seller_roll_id']) ? (int)$r['seller_roll_id'] : 0;
            if ($rId <= 0) continue;

            $rQty = max(0, (int)($r['roll_qty'] ?? ($r['quantity'] ?? 0)));
            $rWeight = max(0.0, (float)($r['roll_weight'] ?? 0));
            $rPrice = max(0.0, (float)($r['roll_price'] ?? 0));
            $rCategoryId = !empty($r['category_id']) ? (int)$r['category_id'] : (!empty($_POST['category_id']) ? (int)$_POST['category_id'] : null);
            $rSubcatId = !empty($r['subcategory_id']) ? (int)$r['subcategory_id'] : (!empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null);
            $rSubSubcatId = !empty($r['sub_subcategory_id']) ? (int)$r['sub_subcategory_id'] : (!empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null);

            $sellerData = Database::fetchOne("SELECT seller_id, weight, price, color, design_name, quantity FROM seller_rolls WHERE id = ?", [$rId]);

            if ($sellerData) {
                if ($rQty <= 0 && !empty($sellerData['quantity'])) $rQty = (int)$sellerData['quantity'];
                if ($rWeight <= 0) $rWeight = (float)$sellerData['weight'];
                if ($rPrice <= 0) $rPrice = (float)$sellerData['price'];
                $validRolls[] = [
                    'seller_roll_id'     => $rId,
                    'seller_id'          => (int)$sellerData['seller_id'],
                    'category_id'        => $rCategoryId,
                    'subcategory_id'     => $rSubcatId,
                    'sub_subcategory_id' => $rSubSubcatId,
                    'roll_qty'           => $rQty,
                    'roll_weight'        => $rWeight,
                    'roll_price'         => $rPrice,
                    'roll_color'         => $sellerData['color'] ?? '',
                    'roll_design'        => $sellerData['design_name'] ?? '',
                ];
                $totalRollQty += $rQty;
                $totalRollWeight += $rWeight;
                $totalRollPrice += $rPrice;
            }
        }

        // Fallback to single roll selection if rolls array was empty
        if (empty($validRolls) && !empty($_POST['seller_roll_id'])) {
            $singleRollId = (int)$_POST['seller_roll_id'];
            $sellerData = Database::fetchOne("SELECT seller_id, weight, price, color, design_name, quantity FROM seller_rolls WHERE id = ?", [$singleRollId]);
            if ($sellerData) {
                $rQty = max(0, (int)($_POST['roll_qty'] ?? ($sellerData['quantity'] ?? 0)));
                $rWeight = max(0.0, (float)($_POST['roll_weight'] ?? $sellerData['weight']));
                $rPrice = max(0.0, (float)($_POST['roll_price'] ?? $sellerData['price']));
                $validRolls[] = [
                    'seller_roll_id'     => $singleRollId,
                    'seller_id'          => (int)$sellerData['seller_id'],
                    'category_id'        => $categoryId,
                    'subcategory_id'     => $subcategoryId,
                    'sub_subcategory_id' => $subSubcategoryId,
                    'roll_qty'           => $rQty,
                    'roll_weight'        => $rWeight,
                    'roll_price'         => $rPrice,
                    'roll_color'         => $sellerData['color'] ?? '',
                    'roll_design'        => $sellerData['design_name'] ?? '',
                ];
                $totalRollQty = $rQty;
                $totalRollWeight = $rWeight;
                $totalRollPrice = $rPrice;
            }
        }

        if (empty($validRolls)) {
            Session::flash('error', 'Please select at least one Raw Material Roll from sellers.');
            Response::back();
            return;
        }

        // Verify total requested roll quantity does not exceed available stock
        $rollUsageMap = [];
        foreach ($validRolls as $vr) {
            $rid = (int)$vr['seller_roll_id'];
            $rollUsageMap[$rid] = ($rollUsageMap[$rid] ?? 0) + (int)$vr['roll_qty'];
        }

        foreach ($rollUsageMap as $rid => $reqQty) {
            $sellerRoll = Database::fetchOne(
                "SELECT sr.roll_number, sr.quantity,
                        GREATEST(0, sr.quantity - COALESCE(u.used_quantity, 0) - COALESCE(dmg.damaged_quantity, 0)) as available_quantity
                 FROM seller_rolls sr
                 LEFT JOIN (
                     SELECT ppr.seller_roll_id, SUM(ppr.roll_qty) as used_quantity
                     FROM production_program_rolls ppr
                     JOIN production_programs pp ON ppr.production_program_id = pp.id
                     WHERE pp.is_active = 1 AND pp.status != 'CANCELLED'
                     GROUP BY ppr.seller_roll_id
                 ) u ON sr.id = u.seller_roll_id
                 LEFT JOIN (
                     SELECT rd.seller_roll_id, SUM(rd.damaged_quantity) as damaged_quantity
                     FROM roll_damages rd
                     WHERE rd.status NOT IN ('CLOSED', 'CANCELLED')
                     GROUP BY rd.seller_roll_id
                 ) dmg ON sr.id = dmg.seller_roll_id
                 WHERE sr.id = ?",
                [$rid]
            );
            if ($sellerRoll) {
                $maxStock = (int)($sellerRoll['available_quantity'] ?? $sellerRoll['quantity']);
                if ($reqQty > $maxStock) {
                    Session::flash('error', "Total requested quantity ({$reqQty} pcs) for Roll '{$sellerRoll['roll_number']}' exceeds available stock ({$maxStock} pcs after deducting damages and active programs). Please adjust quantities.");
                    Response::back();
                    return;
                }
            }
        }

        $primaryRoll = $validRolls[0];
        $sellerRollId = $primaryRoll['seller_roll_id'];
        $sellerId = $primaryRoll['seller_id'];

        if (!$categoryId && !empty($primaryRoll['category_id'])) {
            $categoryId = $primaryRoll['category_id'];
            $subcategoryId = $primaryRoll['subcategory_id'];
            $subSubcategoryId = $primaryRoll['sub_subcategory_id'];
        }

        // Optional: find or link product_id for backward compatibility
        $productId = null;
        if ($subSubcategoryId) {
            $p = Database::fetchOne("SELECT id FROM products WHERE sub_subcategory_id = ? AND is_active = 1 LIMIT 1", [$subSubcategoryId]);
            if ($p) $productId = (int)$p['id'];
        }
        if (!$productId && $subcategoryId) {
            $p = Database::fetchOne("SELECT id FROM products WHERE subcategory_id = ? AND is_active = 1 LIMIT 1", [$subcategoryId]);
            if ($p) $productId = (int)$p['id'];
        }
        if (!$productId && $categoryId) {
            $p = Database::fetchOne("SELECT id FROM products WHERE category_id = ? AND is_active = 1 LIMIT 1", [$categoryId]);
            if ($p) $productId = (int)$p['id'];
        }

        if (empty($programNumber)) {
            $count = Database::fetchOne("SELECT COUNT(*) as c FROM production_programs")['c'] ?? 0;
            $programNumber = 'PRG-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
        }

        try {
            $userId = Session::userId();
            $programId = ProductionProgram::create([
                'name'               => $name,
                'program_number'     => $programNumber,
                'production_unit_id' => $unitId,
                'product_id'         => $productId,
                'seller_roll_id'     => $sellerRollId,
                'seller_id'          => $sellerId,
                'roll_weight'        => $totalRollWeight,
                'roll_price'         => $totalRollPrice,
                'category_id'        => $categoryId,
                'subcategory_id'     => $subcategoryId,
                'sub_subcategory_id' => $subSubcategoryId,
                'planned_quantity'   => $totalRollQty,
                'completed_quantity' => 0,
                'start_date'         => $startDate,
                'end_date'           => $endDate,
                'target_date'        => $endDate,
                'priority'           => $priority,
                'assigned_to'        => $assignedTo,
                'status'             => 'APPROVED',
                'approved_by'        => $userId,
                'approved_at'        => date('Y-m-d H:i:s'),
                'notes'              => $notes,
                'created_by'         => $userId,
            ]);

            // Sync all assigned rolls
            ProductionProgram::syncRolls($programId, $validRolls);

            AuditService::log('production', 'create', 'production_programs', $programId, null, ['name' => $name, 'program_number' => $programNumber, 'roll_count' => count($validRolls)]);
            Session::flash('success', "Production Program '{$name}' created with " . count($validRolls) . " fabric roll(s).");
            Response::redirect(url('production/programs/view/' . $programId));
        } catch (Exception $e) {
            Session::flash('error', 'Error creating program: ' . $e->getMessage());
            Response::back();
        }
    }

    public function editProgram(string $id): void {
        $program = ProductionProgram::find((int)$id);
        if (!$program) {
            Response::error(404, 'Program not found.');
        }

        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : Database::fetchAll("SELECT * FROM production_units WHERE is_active = 1 ORDER BY name ASC");
        $categories = Database::fetchAll(
            "SELECT c.* FROM categories c
             WHERE c.is_active = 1
               AND EXISTS (
                   SELECT 1 FROM products p
                   WHERE p.category_id = c.id
                     AND p.is_active = 1
                     AND (p.source_type IN ('OWN_PRODUCTION', 'MANUFACTURED', 'IN_HOUSE') OR p.source_type IS NULL)
               )
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        $subcategories = $program['category_id'] ? Database::fetchAll("SELECT * FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC", [$program['category_id']]) : [];
        $subSubcategories = $program['subcategory_id'] ? Database::fetchAll("SELECT * FROM sub_subcategories WHERE subcategory_id = ? AND is_active = 1 ORDER BY name ASC", [$program['subcategory_id']]) : [];
        $users = Database::fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");
        $rolls = Database::fetchAll(
            "SELECT sr.*, s.name as seller_name, s.company as seller_company, s.material_type,
                    COALESCE(u.used_quantity, 0) as used_quantity,
                    COALESCE(u.used_weight, 0) as used_weight,
                    COALESCE(u.used_price, 0) as used_price,
                    COALESCE(dmg.damaged_quantity, 0) as damaged_quantity,
                    COALESCE(dmg.damaged_weight, 0) as damaged_weight,
                    COALESCE(dmg.damaged_price, 0) as damaged_price,
                    GREATEST(0, sr.quantity - COALESCE(u.used_quantity, 0) - COALESCE(dmg.damaged_quantity, 0)) as available_quantity,
                    GREATEST(0.0, sr.weight - COALESCE(u.used_weight, 0) - COALESCE(dmg.damaged_weight, 0)) as available_weight,
                    GREATEST(0.0, sr.price - COALESCE(u.used_price, 0) - COALESCE(dmg.damaged_price, 0)) as available_price,
                    dmg.damage_reason,
                    dmg.damage_status
             FROM seller_rolls sr
             JOIN sellers s ON sr.seller_id = s.id
             LEFT JOIN (
                 SELECT ppr.seller_roll_id, 
                        SUM(ppr.roll_qty) as used_quantity,
                        SUM(ppr.roll_weight) as used_weight,
                        SUM(ppr.roll_price) as used_price
                 FROM production_program_rolls ppr
                 JOIN production_programs pp ON ppr.production_program_id = pp.id
                 WHERE pp.is_active = 1 AND pp.status != 'CANCELLED' AND pp.id != ?
                 GROUP BY ppr.seller_roll_id
             ) u ON sr.id = u.seller_roll_id
             LEFT JOIN (
                 SELECT rd.seller_roll_id,
                        SUM(rd.damaged_quantity) as damaged_quantity,
                        SUM(rd.damaged_weight) as damaged_weight,
                        SUM(rd.loss_amount) as damaged_price,
                        MAX(rd.damage_reason) as damage_reason,
                        MAX(rd.status) as damage_status
                 FROM roll_damages rd
                 WHERE rd.status NOT IN ('CLOSED', 'CANCELLED')
                 GROUP BY rd.seller_roll_id
             ) dmg ON sr.id = dmg.seller_roll_id
             WHERE s.is_active = 1
             ORDER BY sr.id DESC",
            [(int)$id]
        );
        $programRolls = ProductionProgram::getRolls((int)$id);

        Response::view('production.programs.edit', [
            'pageTitle'        => 'Edit Program — ' . ($program['name'] ?: $program['program_number']),
            'activeModule'     => 'production',
            'activeMenu'       => 'programs',
            'program'          => $program,
            'programRolls'     => $programRolls,
            'units'            => $units,
            'categories'       => $categories,
            'subcategories'    => $subcategories,
            'subSubcategories' => $subSubcategories,
            'rolls'            => $rolls,
            'users'            => $users,
            'scopedUnitId'     => $scopedUnitId,
            'breadcrumb'       => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Programs', 'url' => url('production/programs')],
                ['label' => $program['program_number'], 'url' => url('production/programs/view/' . $id)],
                ['label' => 'Edit'],
            ],
        ]);
    }

    public function updateProgram(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        if ($scopedUnitId !== null) {
            $_POST['production_unit_id'] = $scopedUnitId;
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $programNumber = mb_strtoupper(trim($_POST['program_number'] ?? ''));
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $unitId = !empty($_POST['production_unit_id']) ? (int)$_POST['production_unit_id'] : $scopedUnitId;
        $priority = $_POST['priority'] ?? 'MEDIUM';
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'Program Name is required.');
            Response::back();
            return;
        }

        // Process multiple rolls from $_POST['rolls']
        $submittedRolls = !empty($_POST['rolls']) && is_array($_POST['rolls']) ? $_POST['rolls'] : [];
        $validRolls = [];
        $totalRollWeight = 0.0;
        $totalRollPrice = 0.0;
        $totalRollQty = 0;

        foreach ($submittedRolls as $r) {
            $rId = !empty($r['seller_roll_id']) ? (int)$r['seller_roll_id'] : 0;
            if ($rId <= 0) continue;

            $rQty = max(0, (int)($r['roll_qty'] ?? ($r['quantity'] ?? 0)));
            $rWeight = max(0.0, (float)($r['roll_weight'] ?? 0));
            $rPrice = max(0.0, (float)($r['roll_price'] ?? 0));
            $rCategoryId = !empty($r['category_id']) ? (int)$r['category_id'] : (!empty($_POST['category_id']) ? (int)$_POST['category_id'] : null);
            $rSubcatId = !empty($r['subcategory_id']) ? (int)$r['subcategory_id'] : (!empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null);
            $rSubSubcatId = !empty($r['sub_subcategory_id']) ? (int)$r['sub_subcategory_id'] : (!empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null);

            $sellerData = Database::fetchOne("SELECT seller_id, weight, price, color, design_name, quantity FROM seller_rolls WHERE id = ?", [$rId]);

            if ($sellerData) {
                if ($rQty <= 0 && !empty($sellerData['quantity'])) $rQty = (int)$sellerData['quantity'];
                if ($rWeight <= 0) $rWeight = (float)$sellerData['weight'];
                if ($rPrice <= 0) $rPrice = (float)$sellerData['price'];
                $validRolls[] = [
                    'seller_roll_id'     => $rId,
                    'seller_id'          => (int)$sellerData['seller_id'],
                    'category_id'        => $rCategoryId,
                    'subcategory_id'     => $rSubcatId,
                    'sub_subcategory_id' => $rSubSubcatId,
                    'roll_qty'           => $rQty,
                    'roll_weight'        => $rWeight,
                    'roll_price'         => $rPrice,
                    'roll_color'         => $sellerData['color'] ?? '',
                    'roll_design'        => $sellerData['design_name'] ?? '',
                ];
                $totalRollQty += $rQty;
                $totalRollWeight += $rWeight;
                $totalRollPrice += $rPrice;
            }
        }

        if (empty($validRolls) && !empty($_POST['seller_roll_id'])) {
            $singleRollId = (int)$_POST['seller_roll_id'];
            $sellerData = Database::fetchOne("SELECT seller_id, weight, price, color, design_name, quantity FROM seller_rolls WHERE id = ?", [$singleRollId]);
            if ($sellerData) {
                $rQty = max(0, (int)($_POST['roll_qty'] ?? ($sellerData['quantity'] ?? 0)));
                $rWeight = max(0.0, (float)($_POST['roll_weight'] ?? $sellerData['weight']));
                $rPrice = max(0.0, (float)($_POST['roll_price'] ?? $sellerData['price']));
                $validRolls[] = [
                    'seller_roll_id'     => $singleRollId,
                    'seller_id'          => (int)$sellerData['seller_id'],
                    'category_id'        => $categoryId,
                    'subcategory_id'     => $subcategoryId,
                    'sub_subcategory_id' => $subSubcategoryId,
                    'roll_qty'           => $rQty,
                    'roll_weight'        => $rWeight,
                    'roll_price'         => $rPrice,
                    'roll_color'         => $sellerData['color'] ?? '',
                    'roll_design'        => $sellerData['design_name'] ?? '',
                ];
                $totalRollQty = $rQty;
                $totalRollWeight = $rWeight;
                $totalRollPrice = $rPrice;
            }
        }

        if (empty($validRolls)) {
            Session::flash('error', 'Please select at least one Raw Material Roll.');
            Response::back();
            return;
        }

        // Verify total requested roll quantity does not exceed available stock
        $rollUsageMap = [];
        foreach ($validRolls as $vr) {
            $rid = (int)$vr['seller_roll_id'];
            $rollUsageMap[$rid] = ($rollUsageMap[$rid] ?? 0) + (int)$vr['roll_qty'];
        }

        foreach ($rollUsageMap as $rid => $reqQty) {
            $sellerRoll = Database::fetchOne(
                "SELECT sr.roll_number, sr.quantity,
                        GREATEST(0, sr.quantity - COALESCE(u.used_quantity, 0) - COALESCE(dmg.damaged_quantity, 0)) as available_quantity
                 FROM seller_rolls sr
                 LEFT JOIN (
                     SELECT ppr.seller_roll_id, SUM(ppr.roll_qty) as used_quantity
                     FROM production_program_rolls ppr
                     JOIN production_programs pp ON ppr.production_program_id = pp.id
                     WHERE pp.is_active = 1 AND pp.status != 'CANCELLED' AND pp.id != ?
                     GROUP BY ppr.seller_roll_id
                 ) u ON sr.id = u.seller_roll_id
                 LEFT JOIN (
                     SELECT rd.seller_roll_id, SUM(rd.damaged_quantity) as damaged_quantity
                     FROM roll_damages rd
                     WHERE rd.status NOT IN ('CLOSED', 'CANCELLED')
                     GROUP BY rd.seller_roll_id
                 ) dmg ON sr.id = dmg.seller_roll_id
                 WHERE sr.id = ?",
                [(int)$id, $rid]
            );
            if ($sellerRoll) {
                $maxStock = (int)($sellerRoll['available_quantity'] ?? $sellerRoll['quantity']);
                if ($reqQty > $maxStock) {
                    Session::flash('error', "Total requested quantity ({$reqQty} pcs) for Roll '{$sellerRoll['roll_number']}' exceeds available stock ({$maxStock} pcs after deducting damages and other active programs). Please adjust quantities.");
                    Response::back();
                    return;
                }
            }
        }

        $primaryRoll = $validRolls[0];
        $sellerRollId = $primaryRoll['seller_roll_id'];
        $sellerId = $primaryRoll['seller_id'];

        if (!$categoryId && !empty($primaryRoll['category_id'])) {
            $categoryId = $primaryRoll['category_id'];
            $subcategoryId = $primaryRoll['subcategory_id'];
            $subSubcategoryId = $primaryRoll['sub_subcategory_id'];
        }

        try {
            ProductionProgram::update((int)$id, [
                'name'               => $name,
                'program_number'     => $programNumber,
                'production_unit_id' => $unitId,
                'seller_roll_id'     => $sellerRollId,
                'seller_id'          => $sellerId,
                'roll_weight'        => $totalRollWeight,
                'roll_price'         => $totalRollPrice,
                'planned_quantity'   => $totalRollQty,
                'category_id'        => $categoryId,
                'subcategory_id'     => $subcategoryId,
                'sub_subcategory_id' => $subSubcategoryId,
                'start_date'         => $startDate,
                'end_date'           => $endDate,
                'target_date'        => $endDate,
                'priority'           => $priority,
                'assigned_to'        => $assignedTo,
                'notes'              => $notes,
            ]);

            // Sync rolls in production_program_rolls
            ProductionProgram::syncRolls((int)$id, $validRolls);

            AuditService::log('production', 'UPDATE', 'production_programs', (int)$id, null, [
                'Program Name'   => $name,
                'Program Number' => $programNumber,
                'Priority'       => $priority,
                'Rolls Count'    => count($validRolls),
                'Total Quantity' => $totalRollQty,
                'Total Weight'   => number_format($totalRollWeight, 2) . ' kg',
                'Total Price'    => '₹' . number_format($totalRollPrice, 2),
            ]);

            Session::flash('success', "Program '{$name}' updated successfully.");
            Response::redirect(url('production/programs/view/' . $id));
        } catch (Exception $e) {
            Session::flash('error', 'Error updating program: ' . $e->getMessage());
            Response::back();
        }
    }

    public function viewProgram(string $id): void {
        $timeline = ProductionService::getProgramTimeline((int)$id);
        if (!$timeline['program']) {
            Response::error(404, 'Program not found.');
        }

        $programRolls = ProductionProgram::getRolls((int)$id);

        Response::view('production.programs.view', array_merge($timeline, [
            'pageTitle'    => 'Program Details — ' . $timeline['program']['program_number'],
            'activeModule' => 'production',
            'activeMenu'   => 'programs',
            'programRolls' => $programRolls,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Programs', 'url' => url('production/programs')],
                ['label' => $timeline['program']['program_number']],
            ],
        ]));
    }

    public function approveProgram(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }
        try {
            $userId = Session::userId();
            ProductionService::approveProgram((int)$id, $userId);
            Session::flash('success', 'Production program approved successfully.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::back();
    }

    public function updateProgramStatus(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }
        $status = $_POST['status'] ?? '';
        $allowed = ['DRAFT', 'APPROVED', 'SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'HOLD', 'CANCELLED'];
        if (!in_array($status, $allowed, true)) {
            Session::flash('error', 'Invalid status.');
            Response::back();
        }

        ProductionProgram::updateStatus((int)$id, $status);
        Session::flash('success', "Program status changed to $status.");
        Response::back();
    }

    public function deleteProgram(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $program = ProductionProgram::find((int)$id);
        if (!$program) {
            Session::flash('error', 'Program not found.');
            Response::redirect(url('production/programs'));
            return;
        }

        ProductionProgram::delete((int)$id);
        AuditService::log('production', 'delete', 'production_programs', (int)$id, null, ['status' => 'CANCELLED', 'is_active' => 0]);

        $name = $program['name'] ?: $program['program_number'];
        Session::flash('success', "Production Program '{$name}' deleted successfully.");
        Response::redirect(url('production/programs'));
    }

    // =========================================================================
    // CUTTING
    // =========================================================================

    public function cuttingList(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $filters = [
            'status'             => trim($_GET['status'] ?? ''),
            'program_id'         => trim($_GET['program_id'] ?? ''),
            'production_unit_id' => $scopedUnitId ? (string)$scopedUnitId : trim($_GET['production_unit_id'] ?? ''),
            'search'             => trim($_GET['search'] ?? ''),
        ];

        $batches = CuttingBatch::getAll($filters);
        $cuttingRollsMap = [];
        foreach ($batches as $b) {
            $cuttingRollsMap[$b['id']] = CuttingBatch::getRolls((int)$b['id']);
        }

        Response::view('production.cutting.index', [
            'pageTitle'       => 'Cutting Batches',
            'activeModule'    => 'production',
            'activeMenu'      => 'cutting',
            'batches'         => $batches,
            'cuttingRollsMap' => $cuttingRollsMap,
            'filters'         => $filters,
            'scopedUnitId'    => $scopedUnitId,
            'breadcrumb'      => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Cutting'],
            ],
        ]);
    }

    public function ajaxProgramDetails(int $id): void {
        $program = ProductionProgram::find((int)$id);
        if (!$program) {
            Response::json(['success' => false, 'message' => 'Program not found.'], 404);
            return;
        }

        // Fetch assigned program rolls
        $assignedRolls = ProductionProgram::getRolls($id);
        $sellerRolls = !empty($assignedRolls) ? $assignedRolls : [];

        // Fetch primary roll details
        $roll = null;
        if (!empty($sellerRolls)) {
            $roll = $sellerRolls[0];
        } elseif (!empty($program['seller_roll_id'])) {
            $roll = Database::fetchOne(
                "SELECT sr.*, s.name as seller_name, s.company as seller_company, s.material_type
                 FROM seller_rolls sr
                 JOIN sellers s ON sr.seller_id = s.id
                 WHERE sr.id = ?",
                [$program['seller_roll_id']]
            );
        }

        // Fetch all rolls from the same seller or all active rolls for dropdown switching if none assigned
        if (empty($sellerRolls) && !empty($program['seller_id'])) {
            $sellerRolls = Database::fetchAll(
                "SELECT sr.*, s.name as seller_name, s.company as seller_company, s.material_type
                 FROM seller_rolls sr
                 JOIN sellers s ON sr.seller_id = s.id
                 WHERE sr.seller_id = ?
                 ORDER BY sr.id ASC",
                [$program['seller_id']]
            );
        }
        if (empty($sellerRolls)) {
            $sellerRolls = Database::fetchAll(
                "SELECT sr.*, s.name as seller_name, s.company as seller_company, s.material_type
                 FROM seller_rolls sr
                 JOIN sellers s ON sr.seller_id = s.id
                 WHERE s.is_active = 1
                 ORDER BY sr.id DESC"
            );
        }

        // Find linked product from program or catalog hierarchy
        $product = null;
        if (!empty($program['product_id'])) {
            $product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$program['product_id']]);
        }
        if (!$product && !empty($program['sub_subcategory_id'])) {
            $product = Database::fetchOne("SELECT * FROM products WHERE sub_subcategory_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1", [$program['sub_subcategory_id']]);
        }
        if (!$product && !empty($program['subcategory_id'])) {
            $product = Database::fetchOne("SELECT * FROM products WHERE subcategory_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1", [$program['subcategory_id']]);
        }
        if (!$product && !empty($program['category_id'])) {
            $product = Database::fetchOne("SELECT * FROM products WHERE category_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1", [$program['category_id']]);
        }

        // Enrich program with product category hierarchy if program fields were not explicitly set
        if ($product) {
            if (empty($program['category_id']) && !empty($product['category_id'])) {
                $program['category_id'] = $product['category_id'];
            }
            if (empty($program['subcategory_id']) && !empty($product['subcategory_id'])) {
                $program['subcategory_id'] = $product['subcategory_id'];
            }
            if (empty($program['sub_subcategory_id']) && !empty($product['sub_subcategory_id'])) {
                $program['sub_subcategory_id'] = $product['sub_subcategory_id'];
            }
            if (empty($program['category_name']) && !empty($program['category_id'])) {
                $c = Database::fetchOne("SELECT name FROM categories WHERE id = ?", [$program['category_id']]);
                if ($c) $program['category_name'] = $c['name'];
            }
            if (empty($program['subcategory_name']) && !empty($program['subcategory_id'])) {
                $sc = Database::fetchOne("SELECT name FROM subcategories WHERE id = ?", [$program['subcategory_id']]);
                if ($sc) $program['subcategory_name'] = $sc['name'];
            }
            if (empty($program['sub_subcategory_name']) && !empty($program['sub_subcategory_id'])) {
                $ssc = Database::fetchOne("SELECT name FROM sub_subcategories WHERE id = ?", [$program['sub_subcategory_id']]);
                if ($ssc) $program['sub_subcategory_name'] = $ssc['name'];
            }
        }

        // Build 3-tier catalog hierarchy string
        $hierarchyParts = array_filter([
            $program['category_name'] ?? '',
            $program['subcategory_name'] ?? '',
            $program['sub_subcategory_name'] ?? ''
        ]);
        $catalogHierarchy = !empty($hierarchyParts) ? implode(' › ', $hierarchyParts) : 'General Apparel';

        $parsedSizes = [];
        $parsedColors = [];

        if ($product) {
            $pvRows = Database::fetchAll(
                "SELECT pv.*, s.name as size_name, s.code as size_code, c.name as color_name, c.hex_code as color_hex
                 FROM product_variants pv
                 LEFT JOIN sizes s ON pv.size_id = s.id
                 LEFT JOIN colors c ON pv.color_id = c.id
                 WHERE pv.product_id = ? AND pv.is_active = 1",
                [$product['id']]
            );

            foreach ($pvRows as $pv) {
                $sName = $pv['size_name'] ?: ($pv['size_code'] ?: '');
                if (!empty($sName)) {
                    if (str_contains($sName, ',')) {
                        foreach (explode(',', $sName) as $sp) {
                            $sp = trim($sp);
                            if ($sp !== '' && !in_array($sp, $parsedSizes, true)) $parsedSizes[] = $sp;
                        }
                    } else {
                        if (!in_array($sName, $parsedSizes, true)) $parsedSizes[] = $sName;
                    }
                }

                $cName = $pv['color_name'] ?: '';
                if (!empty($cName)) {
                    if (str_contains($cName, ',')) {
                        foreach (explode(',', $cName) as $cp) {
                            $cp = trim($cp);
                            if ($cp !== '' && !in_array($cp, $parsedColors, true)) $parsedColors[] = $cp;
                        }
                    } else {
                        if (!in_array($cName, $parsedColors, true)) $parsedColors[] = $cName;
                    }
                }
            }
        }

        // Check active sizes and colors in database as fallback
        if (empty($parsedSizes)) {
            $rawSizes = Database::fetchAll("SELECT * FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
            foreach ($rawSizes as $s) {
                if (str_contains($s['name'], ',')) {
                    foreach (explode(',', $s['name']) as $sp) {
                        $sp = trim($sp);
                        if ($sp !== '' && !in_array($sp, $parsedSizes, true)) $parsedSizes[] = $sp;
                    }
                } else {
                    if (!in_array($s['name'], $parsedSizes, true)) $parsedSizes[] = $s['name'];
                }
            }
        }
        if (empty($parsedSizes)) {
            $parsedSizes = ['M', 'L', 'XL', 'XXL', '3XL'];
        }

        if (empty($parsedColors)) {
            $rawColors = Database::fetchAll("SELECT * FROM colors WHERE is_active = 1 ORDER BY name ASC");
            foreach ($rawColors as $c) {
                if (str_contains($c['name'], ',')) {
                    foreach (explode(',', $c['name']) as $cp) {
                        $cp = trim($cp);
                        if ($cp !== '' && !in_array($cp, $parsedColors, true)) $parsedColors[] = $cp;
                    }
                } else {
                    if (!in_array($c['name'], $parsedColors, true)) $parsedColors[] = $c['name'];
                }
            }
        }
        if (empty($parsedColors)) {
            $parsedColors = ['BLACK', 'BLUE', 'RED'];
        }

        $prgCode = $program['program_number'] ?: ($product ? $product['code'] : 'PRG');
        $variants = [];
        foreach ($parsedSizes as $sz) {
            foreach ($parsedColors as $col) {
                $variants[] = [
                    'id'              => null,
                    'sku'             => $prgCode . '-' . strtoupper(substr($sz, 0, 3)) . '-' . strtoupper(substr($col, 0, 3)),
                    'size_id'         => null,
                    'size_name'       => $sz,
                    'size_code'       => $sz,
                    'color_id'        => null,
                    'color_name'      => $col,
                    'color_hex'       => '#0d6efd',
                    'production_cost' => 0.00,
                ];
            }
        }

        $allRolls = Database::fetchAll(
            "SELECT sr.*, s.name as seller_name, s.company as seller_company, s.material_type
             FROM seller_rolls sr
             JOIN sellers s ON sr.seller_id = s.id
             WHERE s.is_active = 1
             ORDER BY sr.id DESC"
        );

        Response::json([
            'success'          => true,
            'program'          => $program,
            'product'          => $product,
            'roll'             => $roll,
            'assignedRolls'    => $assignedRolls,
            'sellerRolls'      => $sellerRolls,
            'allRolls'         => $allRolls,
            'catalogHierarchy' => $catalogHierarchy,
            'variants'         => $variants,
            'sizes'            => $parsedSizes,
            'colors'           => $parsedColors,
        ]);
    }

    public function ajaxCuttingDetails(int $id): void {
        $batch = CuttingBatch::find((int)$id);
        if (!$batch) {
            Response::json(['success' => false, 'message' => 'Cutting batch not found.'], 404);
            return;
        }

        $items = CuttingBatch::getItems((int)$id);
        $program = ProductionProgram::find((int)$batch['program_id']);

        // Load rolls from cutting batch or program
        $rolls = CuttingBatch::getRolls((int)$id);
        if (empty($rolls) && $program) {
            $rolls = ProductionProgram::getRolls((int)$program['id']);
        }

        // Default hierarchy if no rolls found
        $defaultHierarchyParts = array_filter([
            $batch['category_name'] ?? ($program['category_name'] ?? ''),
            $batch['subcategory_name'] ?? ($program['subcategory_name'] ?? ''),
            $batch['sub_subcategory_name'] ?? ($program['sub_subcategory_name'] ?? '')
        ]);
        $defaultCatalogHierarchy = !empty($defaultHierarchyParts) ? implode(' › ', $defaultHierarchyParts) : ($batch['product_name'] ?? 'General Apparel');

        // If cutting batch has no specific items, fallback to program variants
        if (empty($items) && $program) {
            $parsedSizes = ['M', 'L', 'XL', 'XXL'];
            $parsedColors = ['MAROON', 'NAVY BLUE', 'BLACK'];
            $prgCode = $program['program_number'] ?? 'PRG';
            foreach ($parsedSizes as $sz) {
                foreach ($parsedColors as $col) {
                    $items[] = [
                        'variant_id'   => null,
                        'size_name'    => $sz,
                        'color_name'   => $col,
                        'cut_qty'      => 0,
                        'planned_qty'  => 0,
                        'sku'          => $prgCode . '-' . substr($sz, 0, 3) . '-' . substr($col, 0, 3),
                    ];
                }
            }
        }

        $usedWeight = (float)($batch['used_weight'] > 0 ? $batch['used_weight'] : max(0.0, (float)($batch['roll_weight'] ?? 0) - (float)($batch['wastage_weight'] ?? 0)));
        $batch['used_weight'] = $usedWeight;

        $totalCutQty = 0;
        foreach ($items as $it) {
            $totalCutQty += (int)($it['cut_qty'] ?? 0);
        }
        if ($totalCutQty <= 0 && !empty($batch['cut_qty'])) {
            $totalCutQty = (int)$batch['cut_qty'];
        }

        $defaultUnitWeight = ($usedWeight > 0 && $totalCutQty > 0) ? round($usedWeight / $totalCutQty, 3) : 0.000;

        $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
            'name'               => $defaultCatalogHierarchy,
            'category_id'        => $batch['category_id'] ?? ($program['category_id'] ?? null),
            'subcategory_id'     => $batch['subcategory_id'] ?? ($program['subcategory_id'] ?? null),
            'sub_subcategory_id' => $batch['sub_subcategory_id'] ?? ($program['sub_subcategory_id'] ?? null),
        ], 'cut_qty');

        // Enhance items with prices and default unit weight derived from cutting used weight
        foreach ($items as &$item) {
            $itemCutQty = (int)($item['cut_qty'] ?? 0);
            if ($defaultUnitWeight > 0) {
                $item['weight_per_piece'] = $defaultUnitWeight;
                if ($itemCutQty > 0) {
                    $item['weight'] = round($itemCutQty * $defaultUnitWeight, 2);
                }
            }
            if (empty($item['base_price']) || (float)$item['base_price'] <= 0) {
                if (!empty($item['variant_mrp']) && (float)$item['variant_mrp'] > 0) {
                    $item['base_price'] = (float)$item['variant_mrp'];
                }
            }
            if (empty($item['discount_price']) || (float)$item['discount_price'] <= 0) {
                if (!empty($item['variant_selling_price']) && (float)$item['variant_selling_price'] > 0) {
                    $item['discount_price'] = (float)$item['variant_selling_price'];
                } elseif (!empty($item['base_price']) && (float)$item['base_price'] > 0) {
                    $item['discount_price'] = (float)$item['base_price'];
                }
            }
        }
        unset($item);

        Response::json([
            'success'          => true,
            'cuttingBatch'     => $batch,
            'program'          => $program,
            'catalogHierarchy' => $defaultCatalogHierarchy,
            'catalogs'         => array_values($distinctCatalogs),
            'items'            => $items,
        ]);
    }

    public function ajaxStitchingDetails(int $id): void {
        $batch = StitchingBatch::find((int)$id);
        if (!$batch) {
            Response::json(['success' => false, 'message' => 'Stitching batch not found.'], 404);
            return;
        }

        $items = StitchingBatch::getItems((int)$id);
        $program = ProductionProgram::find((int)$batch['program_id']);

        // Load rolls from cutting batch or program
        $rolls = !empty($batch['cutting_batch_id']) ? CuttingBatch::getRolls((int)$batch['cutting_batch_id']) : [];
        if (empty($rolls) && $program) {
            $rolls = ProductionProgram::getRolls((int)$program['id']);
        }

        $defaultHierarchyParts = array_filter([
            $batch['category_name'] ?? ($program['category_name'] ?? ''),
            $batch['subcategory_name'] ?? ($program['subcategory_name'] ?? ''),
            $batch['sub_subcategory_name'] ?? ($program['sub_subcategory_name'] ?? '')
        ]);
        $defaultCatalogHierarchy = !empty($defaultHierarchyParts) ? implode(' › ', $defaultHierarchyParts) : ($batch['product_name'] ?? 'General Apparel');

        $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
            'name'               => $defaultCatalogHierarchy,
            'category_id'        => $batch['category_id'] ?? ($program['category_id'] ?? null),
            'subcategory_id'     => $batch['subcategory_id'] ?? ($program['subcategory_id'] ?? null),
            'sub_subcategory_id' => $batch['sub_subcategory_id'] ?? ($program['sub_subcategory_id'] ?? null),
        ], 'stitched_qty');

        $catalogSequences = [];
        foreach ($distinctCatalogs as $cName => &$catData) {
            $lastSeq = LabelItem::getLastSequenceForCatalog(
                $catData['category_id'] ?? null,
                $catData['subcategory_id'] ?? null,
                $catData['sub_subcategory_id'] ?? null,
                $catData['name'] ?? null
            );
            $catData['last_sequence'] = $lastSeq;
            $catData['next_sequence'] = $lastSeq + 1;

            $cKey = ($catData['category_id'] ?? 0) . '_' . ($catData['subcategory_id'] ?? 0) . '_' . ($catData['sub_subcategory_id'] ?? 0);
            $seqInfo = [
                'last_sequence'      => $lastSeq,
                'next_sequence'      => $lastSeq + 1,
                'category_id'        => $catData['category_id'] ?? null,
                'subcategory_id'     => $catData['subcategory_id'] ?? null,
                'sub_subcategory_id' => $catData['sub_subcategory_id'] ?? null,
                'name'               => $catData['name'] ?? null,
            ];
            $catalogSequences[$cKey] = $seqInfo;
            if (!empty($catData['name'])) {
                $catalogSequences[$catData['name']] = $seqInfo;
            }
        }
        unset($catData);

        foreach ($items as &$it) {
            $cKey = ($it['category_id'] ?? 0) . '_' . ($it['subcategory_id'] ?? 0) . '_' . ($it['sub_subcategory_id'] ?? 0);
            $seqInfo = $catalogSequences[$cKey] ?? ($catalogSequences[$it['catalog_hierarchy'] ?? ''] ?? null);
            $it['last_sequence'] = $seqInfo ? $seqInfo['last_sequence'] : 0;
            $it['next_sequence'] = $seqInfo ? $seqInfo['next_sequence'] : 1;
        }
        unset($it);

        Response::json([
            'success'          => true,
            'stitchingBatch'   => $batch,
            'program'          => $program,
            'catalogHierarchy' => $defaultCatalogHierarchy,
            'catalogs'         => array_values($distinctCatalogs),
            'catalogSequences' => $catalogSequences,
            'items'            => $items,
        ]);
    }

    /**
     * Map variant items to distinct catalog hierarchies using roll queues and size cycles.
     */
    private function resolveItemsCatalogHierarchy(array &$items, array $rolls, array $defaultHierarchyData, string $qtyField = 'cut_qty'): array {
        $rollColorQueues = [];
        $distinctCatalogs = [];

        foreach ($rolls as $r) {
            $hParts = array_filter([
                $r['category_name'] ?? '',
                $r['subcategory_name'] ?? '',
                $r['sub_subcategory_name'] ?? ''
            ]);
            $hStr = !empty($hParts) ? implode(' › ', $hParts) : 'General Apparel';
            $rColor = strtoupper(trim($r['roll_color'] ?? ''));
            if ($rColor) {
                $rollColorQueues[$rColor][] = [
                    'category_id'        => $r['category_id'] ?? null,
                    'subcategory_id'     => $r['subcategory_id'] ?? null,
                    'sub_subcategory_id' => $r['sub_subcategory_id'] ?? null,
                    'catalog_hierarchy'  => $hStr,
                ];
            }
            if (!isset($distinctCatalogs[$hStr])) {
                $distinctCatalogs[$hStr] = [
                    'name'               => $hStr,
                    'category_id'        => $r['category_id'] ?? null,
                    'subcategory_id'     => $r['subcategory_id'] ?? null,
                    'sub_subcategory_id' => $r['sub_subcategory_id'] ?? null,
                    'item_count'         => 0,
                    'total_qty'          => 0,
                ];
            }
        }

        $defaultCatalogHierarchy = $defaultHierarchyData['name'] ?? 'General Apparel';
        if (empty($distinctCatalogs)) {
            $distinctCatalogs[$defaultCatalogHierarchy] = [
                'name'               => $defaultCatalogHierarchy,
                'category_id'        => $defaultHierarchyData['category_id'] ?? null,
                'subcategory_id'     => $defaultHierarchyData['subcategory_id'] ?? null,
                'sub_subcategory_id' => $defaultHierarchyData['sub_subcategory_id'] ?? null,
                'item_count'         => 0,
                'total_qty'          => 0,
            ];
        }

        $colorTracker = [];

        foreach ($items as &$item) {
            $itemHierarchy = '';
            $catId = !empty($item['category_id']) ? (int)$item['category_id'] : null;
            $subcatId = !empty($item['subcategory_id']) ? (int)$item['subcategory_id'] : null;
            $subSubcatId = !empty($item['sub_subcategory_id']) ? (int)$item['sub_subcategory_id'] : null;

            $itemParts = array_filter([
                $item['category_name'] ?? '',
                $item['subcategory_name'] ?? '',
                $item['sub_subcategory_name'] ?? ''
            ]);
            if (!empty($itemParts)) {
                $itemHierarchy = implode(' › ', $itemParts);
            }

            if (empty($itemHierarchy) && !empty($item['catalog_hierarchy'])) {
                $itemHierarchy = $item['catalog_hierarchy'];
            }

            $cName = strtoupper(trim(!empty($item['color_name']) ? $item['color_name'] : ($item['display_color'] ?? '')));
            $sName = strtoupper(trim(!empty($item['size_name']) ? $item['size_name'] : ($item['display_size'] ?? '')));

            if (empty($itemHierarchy) && $cName && !empty($rollColorQueues[$cName])) {
                if (!isset($colorTracker[$cName])) {
                    $colorTracker[$cName] = ['index' => 0, 'seen_sizes' => []];
                }
                if (in_array($sName, $colorTracker[$cName]['seen_sizes'], true)) {
                    if (isset($rollColorQueues[$cName][$colorTracker[$cName]['index'] + 1])) {
                        $colorTracker[$cName]['index']++;
                        $colorTracker[$cName]['seen_sizes'] = [];
                    }
                }
                $colorTracker[$cName]['seen_sizes'][] = $sName;
                $matchedRoll = $rollColorQueues[$cName][$colorTracker[$cName]['index']];

                $itemHierarchy = $matchedRoll['catalog_hierarchy'];
                if (!$catId) $catId = $matchedRoll['category_id'];
                if (!$subcatId) $subcatId = $matchedRoll['subcategory_id'];
                if (!$subSubcatId) $subSubcatId = $matchedRoll['sub_subcategory_id'];
            }

            if (empty($itemHierarchy)) {
                $itemHierarchy = $defaultCatalogHierarchy;
                if (!$catId) $catId = $defaultHierarchyData['category_id'] ?? null;
                if (!$subcatId) $subcatId = $defaultHierarchyData['subcategory_id'] ?? null;
                if (!$subSubcatId) $subSubcatId = $defaultHierarchyData['sub_subcategory_id'] ?? null;
            }

            $item['catalog_hierarchy'] = $itemHierarchy;
            $item['category_id'] = $catId;
            $item['subcategory_id'] = $subcatId;
            $item['sub_subcategory_id'] = $subSubcatId;

            if (!isset($distinctCatalogs[$itemHierarchy])) {
                $distinctCatalogs[$itemHierarchy] = [
                    'name'               => $itemHierarchy,
                    'category_id'        => $catId,
                    'subcategory_id'     => $subcatId,
                    'sub_subcategory_id' => $subSubcatId,
                    'item_count'         => 0,
                    'total_qty'          => 0,
                ];
            }
            $distinctCatalogs[$itemHierarchy]['item_count']++;
            $distinctCatalogs[$itemHierarchy]['total_qty'] += (int)($item[$qtyField] ?? ($item['cut_qty'] ?? ($item['stitched_qty'] ?? 0)));
        }
        unset($item);

        return $distinctCatalogs;
    }

    public function ajaxCatalogVariants(): void {
        $categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $subcategoryId = !empty($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;
        $subSubcategoryId = !empty($_GET['sub_subcategory_id']) ? (int)$_GET['sub_subcategory_id'] : null;

        // Resolve product matching hierarchy
        $product = null;
        if ($subSubcategoryId) {
            $product = Database::fetchOne("SELECT * FROM products WHERE sub_subcategory_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1", [$subSubcategoryId]);
        }
        if (!$product && $subcategoryId) {
            $product = Database::fetchOne("SELECT * FROM products WHERE subcategory_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1", [$subcategoryId]);
        }
        if (!$product && $categoryId) {
            $product = Database::fetchOne("SELECT * FROM products WHERE category_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1", [$categoryId]);
        }

        $parsedSizes = [];
        $parsedColors = [];

        if ($product) {
            $pvRows = Database::fetchAll(
                "SELECT pv.*, s.name as size_name, s.code as size_code, c.name as color_name, c.hex_code as color_hex
                 FROM product_variants pv
                 LEFT JOIN sizes s ON pv.size_id = s.id
                 LEFT JOIN colors c ON pv.color_id = c.id
                 WHERE pv.product_id = ? AND pv.is_active = 1",
                [$product['id']]
            );

            foreach ($pvRows as $pv) {
                $sName = $pv['size_name'] ?: ($pv['size_code'] ?: '');
                if (!empty($sName)) {
                    if (str_contains($sName, ',')) {
                        foreach (explode(',', $sName) as $sp) {
                            $sp = trim($sp);
                            if ($sp !== '' && !in_array($sp, $parsedSizes, true)) $parsedSizes[] = $sp;
                        }
                    } else {
                        if (!in_array($sName, $parsedSizes, true)) $parsedSizes[] = $sName;
                    }
                }

                $cName = $pv['color_name'] ?: '';
                if (!empty($cName)) {
                    if (str_contains($cName, ',')) {
                        foreach (explode(',', $cName) as $cp) {
                            $cp = trim($cp);
                            if ($cp !== '' && !in_array($cp, $parsedColors, true)) $parsedColors[] = $cp;
                        }
                    } else {
                        if (!in_array($cName, $parsedColors, true)) $parsedColors[] = $cName;
                    }
                }
            }
        }

        // Fallback to active sizes in DB if empty
        if (empty($parsedSizes)) {
            $rawSizes = Database::fetchAll("SELECT * FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
            foreach ($rawSizes as $s) {
                $rawStr = !empty($s['code']) ? $s['code'] : ($s['name'] ?? '');
                if (str_contains($rawStr, ',')) {
                    foreach (explode(',', $rawStr) as $sp) {
                        $sp = trim($sp);
                        if ($sp !== '' && !in_array($sp, $parsedSizes, true)) $parsedSizes[] = $sp;
                    }
                } else {
                    if ($rawStr !== '' && !in_array($rawStr, $parsedSizes, true)) $parsedSizes[] = $rawStr;
                }
            }
        }
        if (empty($parsedSizes)) {
            $parsedSizes = ['M', 'L', 'XL', 'XXL', '3XL'];
        }

        // Prioritize roll_color if provided from selected fabric roll
        $rollColor = trim($_GET['roll_color'] ?? '');
        if (!empty($rollColor) && $rollColor !== '-' && $rollColor !== 'Standard') {
            $parsedColors = [$rollColor];
        } elseif (empty($parsedColors)) {
            $rawColors = Database::fetchAll("SELECT * FROM colors WHERE is_active = 1 ORDER BY name ASC");
            foreach ($rawColors as $c) {
                if (str_contains($c['name'], ',')) {
                    foreach (explode(',', $c['name']) as $cp) {
                        $cp = trim($cp);
                        if ($cp !== '' && !in_array($cp, $parsedColors, true)) $parsedColors[] = $cp;
                    }
                } else {
                    if (!in_array($c['name'], $parsedColors, true)) $parsedColors[] = $c['name'];
                }
            }
        }
        if (empty($parsedColors)) {
            $parsedColors = ['STANDARD'];
        }

        $prodCode = $product ? ($product['code'] ?: 'CUT') : 'CUT';
        $variants = [];
        foreach ($parsedSizes as $sz) {
            foreach ($parsedColors as $col) {
                $variants[] = [
                    'id'              => null,
                    'sku'             => $prodCode . '-' . strtoupper(substr($sz, 0, 3)) . '-' . strtoupper(substr($col, 0, 3)),
                    'size_name'       => $sz,
                    'color_name'      => $col,
                    'planned_quantity'=> 0,
                ];
            }
        }

        Response::json([
            'success'  => true,
            'product'  => $product,
            'sizes'    => $parsedSizes,
            'colors'   => $parsedColors,
            'variants' => $variants,
        ]);
    }

    public function createCutting(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $programId = $_GET['program_id'] ?? null;
        
        $progUnitClause = $scopedUnitId ? " AND pp.production_unit_id = $scopedUnitId" : "";
        $programs = Database::fetchAll(
            "SELECT pp.*, 
                    COALESCE(pp.name, p.name) as product_name, 
                    pu.name as unit_name,
                    sr.roll_number, sr.color as roll_color, sr.design_name as roll_design,
                    c.name as category_name, sc.name as subcategory_name, ssc.name as sub_subcategory_name
             FROM production_programs pp
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             LEFT JOIN seller_rolls sr ON pp.seller_roll_id = sr.id
             LEFT JOIN categories c ON pp.category_id = c.id
             LEFT JOIN subcategories sc ON pp.subcategory_id = sc.id
             LEFT JOIN sub_subcategories ssc ON pp.sub_subcategory_id = ssc.id
             WHERE (pp.is_active IS NULL OR pp.is_active = 1)$progUnitClause
               AND pp.id NOT IN (SELECT program_id FROM cutting_batches WHERE program_id IS NOT NULL)
             ORDER BY pp.created_at DESC"
        );

        $selectedProgram = null;
        $selectedProgramRolls = [];
        if ($programId) {
            $alreadyCut = Database::fetchOne("SELECT id, batch_number FROM cutting_batches WHERE program_id = ?", [(int)$programId]);
            if ($alreadyCut) {
                Session::flash('error', "Production Program has already been cut in Cutting Batch {$alreadyCut['batch_number']}. Duplicate cutting is not allowed.");
                Response::redirect(url('production/cutting'));
                return;
            }
            $selectedProgram = ProductionProgram::find((int)$programId);
            if ($selectedProgram) {
                $selectedProgramRolls = ProductionProgram::getRolls((int)$programId);
            }
        }

        $categories = Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
        $users = Database::fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");
        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : Database::fetchAll("SELECT * FROM production_units WHERE is_active = 1 ORDER BY name ASC");
        $rolls = Database::fetchAll(
            "SELECT sr.*, s.name as seller_name, s.company as seller_company, s.material_type
             FROM seller_rolls sr
             JOIN sellers s ON sr.seller_id = s.id
             WHERE s.is_active = 1
             ORDER BY sr.id DESC"
        );

        $count = Database::fetchOne("SELECT COUNT(*) as c FROM cutting_batches")['c'] ?? 0;
        $suggestedBatchNumber = 'CUT-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

        Response::view('production.cutting.create', [
            'pageTitle'            => 'New Cutting Batch',
            'activeModule'         => 'production',
            'activeMenu'           => 'cutting',
            'programs'             => $programs,
            'selectedProgram'      => $selectedProgram,
            'selectedProgramRolls' => $selectedProgramRolls,
            'categories'           => $categories,
            'users'                => $users,
            'units'                => $units,
            'rolls'                => $rolls,
            'scopedUnitId'         => $scopedUnitId,
            'suggestedBatchNumber' => $suggestedBatchNumber,
            'breadcrumb'           => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Cutting', 'url' => url('production/cutting')],
                ['label' => 'New Cutting Batch'],
            ],
        ]);
    }

    public function storeCutting(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $programId = (int)($_POST['program_id'] ?? 0);
        $batchNumber = mb_strtoupper(trim($_POST['batch_number'] ?? ''));
        $sellerRollId = !empty($_POST['seller_roll_id']) ? (int)$_POST['seller_roll_id'] : null;
        $rollWeight = max(0.0, (float)($_POST['roll_weight'] ?? 0));
        $rollPrice = max(0.0, (float)($_POST['roll_price'] ?? 0));
        $wastageWeight = max(0.0, (float)($_POST['wastage_weight'] ?? 0));
        $operatorId = !empty($_POST['operator_id']) ? (int)$_POST['operator_id'] : null;
        $unitId = !empty($_POST['production_unit_id']) ? (int)$_POST['production_unit_id'] : null;
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null;
        $status = $_POST['status'] ?? 'IN_PROGRESS';
        if (empty($name)) {
            Session::flash('error', 'Cutting Name is required. Please provide a valid batch name.');
            Response::back();
            return;
        }

        if (empty($programId)) {
            Session::flash('error', 'Please select a Production Program.');
            Response::back();
            return;
        }

        // Prevent duplicate cutting of the same program
        $existingCutting = Database::fetchOne(
            "SELECT id, batch_number, name FROM cutting_batches WHERE program_id = ?",
            [$programId]
        );
        if ($existingCutting) {
            $progInfo = ProductionProgram::find($programId);
            $progNum = $progInfo['program_number'] ?? ('#' . $programId);
            Session::flash('error', "Validation Error: Production Program ({$progNum}) has already been assigned to Cutting Batch {$existingCutting['batch_number']}. Duplicate cutting of the same program is not allowed.");
            Response::back();
            return;
        }

        // Process multiple rolls from $_POST['rolls']
        $submittedRolls = !empty($_POST['rolls']) && is_array($_POST['rolls']) ? $_POST['rolls'] : [];
        $validRolls = [];
        $calcTotalRollWeight = 0.0;
        $calcTotalRollPrice = 0.0;

        foreach ($submittedRolls as $r) {
            $rId = !empty($r['seller_roll_id']) ? (int)$r['seller_roll_id'] : 0;
            if ($rId <= 0) continue;

            $rWeight = max(0.0, (float)($r['roll_weight'] ?? 0));
            $rPrice = max(0.0, (float)($r['roll_price'] ?? 0));
            $rWastageWeight = max(0.0, (float)($r['wastage_weight'] ?? 0));
            $rRate = $rWeight > 0 ? ($rPrice / $rWeight) : 0;
            $rWastageCost = max(0.0, (float)($r['wastage_cost'] ?? round($rWastageWeight * $rRate, 2)));

            $rCategoryId = !empty($r['category_id']) ? (int)$r['category_id'] : (!empty($_POST['category_id']) ? (int)$_POST['category_id'] : null);
            $rSubcatId = !empty($r['subcategory_id']) ? (int)$r['subcategory_id'] : (!empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null);
            $rSubSubcatId = !empty($r['sub_subcategory_id']) ? (int)$r['sub_subcategory_id'] : (!empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null);

            $sellerData = Database::fetchOne("SELECT seller_id, weight, price, color, design_name FROM seller_rolls WHERE id = ?", [$rId]);

            if ($sellerData) {
                if ($rWeight <= 0) $rWeight = (float)$sellerData['weight'];
                if ($rPrice <= 0) $rPrice = (float)$sellerData['price'];
                $validRolls[] = [
                    'seller_roll_id'     => $rId,
                    'seller_id'          => (int)$sellerData['seller_id'],
                    'roll_weight'        => $rWeight,
                    'roll_price'         => $rPrice,
                    'wastage_weight'     => $rWastageWeight,
                    'wastage_cost'       => $rWastageCost,
                    'roll_color'         => $sellerData['color'] ?? '',
                    'roll_design'        => $sellerData['design_name'] ?? '',
                    'category_id'        => $rCategoryId,
                    'subcategory_id'     => $rSubcatId,
                    'sub_subcategory_id' => $rSubSubcatId,
                ];
                $calcTotalRollWeight += $rWeight;
                $calcTotalRollPrice += $rPrice;
            }
        }

        // Fallback to single roll if rolls array wasn't passed
        if (empty($validRolls) && !empty($_POST['seller_roll_id'])) {
            $singleRollId = (int)$_POST['seller_roll_id'];
            $sellerData = Database::fetchOne("SELECT seller_id, weight, price, color, design_name FROM seller_rolls WHERE id = ?", [$singleRollId]);
            if ($sellerData) {
                $rWeight = max(0.0, (float)($_POST['roll_weight'] ?? $sellerData['weight']));
                $rPrice = max(0.0, (float)($_POST['roll_price'] ?? $sellerData['price']));
                $rWastageWeight = max(0.0, (float)($_POST['wastage_weight'] ?? 0));
                $rRate = $rWeight > 0 ? ($rPrice / $rWeight) : 0;
                $rWastageCost = max(0.0, (float)($_POST['wastage_cost'] ?? round($rWastageWeight * $rRate, 2)));

                $validRolls[] = [
                    'seller_roll_id'     => $singleRollId,
                    'seller_id'          => (int)$sellerData['seller_id'],
                    'roll_weight'        => $rWeight,
                    'roll_price'         => $rPrice,
                    'wastage_weight'     => $rWastageWeight,
                    'wastage_cost'       => $rWastageCost,
                    'roll_color'         => $sellerData['color'] ?? '',
                    'roll_design'        => $sellerData['design_name'] ?? '',
                    'category_id'        => $categoryId,
                    'subcategory_id'     => $subcategoryId,
                    'sub_subcategory_id' => $subSubcategoryId,
                ];
                $calcTotalRollWeight = $rWeight;
                $calcTotalRollPrice = $rPrice;
            }
        }

        if ($calcTotalRollWeight > 0) $rollWeight = $calcTotalRollWeight;
        if ($calcTotalRollPrice > 0) $rollPrice = $calcTotalRollPrice;
        if (!empty($validRolls)) {
            $sellerRollId = $validRolls[0]['seller_roll_id'];
            if (!$categoryId) $categoryId = $validRolls[0]['category_id'] ?? null;
            if (!$subcategoryId) $subcategoryId = $validRolls[0]['subcategory_id'] ?? null;
            if (!$subSubcategoryId) $subSubcategoryId = $validRolls[0]['sub_subcategory_id'] ?? null;

            $sumRollWastageWeight = array_sum(array_column($validRolls, 'wastage_weight'));
            $sumRollWastageCost = array_sum(array_column($validRolls, 'wastage_cost'));
            if ($sumRollWastageWeight > 0) {
                $wastageWeight = $sumRollWastageWeight;
            }
            if ($sumRollWastageCost > 0) {
                $wastageCost = $sumRollWastageCost;
            }
        }

        $usedWeight = max(0.0, $rollWeight - $wastageWeight);

        if (empty($name)) {
            $program = ProductionProgram::find($programId);
            $name = 'CUTTING - ' . ($program['name'] ?: $program['program_number']);
        }

        if (empty($batchNumber)) {
            $count = Database::fetchOne("SELECT COUNT(*) as c FROM cutting_batches")['c'] ?? 0;
            $batchNumber = 'CUT-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
        }

        $items = [];
        $totalCut = 0;
        $totalPlanned = 0;
        $totalRejected = 0;
        $totalWastage = 0;

        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $vals) {
                $cutQty = (int)($vals['cut_qty'] ?? 0);
                $plannedQty = (int)($vals['planned_qty'] ?? $cutQty);
                $rejQty = (int)($vals['rejected_qty'] ?? 0);
                $wQty = (int)($vals['wastage_qty'] ?? 0);
                $sizeName = trim($vals['size_name'] ?? '');
                $colorName = trim($vals['color_name'] ?? '');
                $variantId = !empty($vals['variant_id']) ? (int)$vals['variant_id'] : null;

                $itemCatId = !empty($vals['category_id']) ? (int)$vals['category_id'] : $categoryId;
                $itemSubcatId = !empty($vals['subcategory_id']) ? (int)$vals['subcategory_id'] : $subcategoryId;
                $itemSubSubcatId = !empty($vals['sub_subcategory_id']) ? (int)$vals['sub_subcategory_id'] : $subSubcategoryId;
                $itemCatalogHierarchy = !empty($vals['catalog_hierarchy']) ? trim($vals['catalog_hierarchy']) : null;

                if (!empty($sizeName) || !empty($colorName) || $cutQty > 0 || $plannedQty > 0 || $rejQty > 0 || $wQty > 0) {
                    $items[] = [
                        'variant_id'         => $variantId,
                        'category_id'        => $itemCatId,
                        'subcategory_id'     => $itemSubcatId,
                        'sub_subcategory_id' => $itemSubSubcatId,
                        'catalog_hierarchy'  => $itemCatalogHierarchy,
                        'size_name'          => $sizeName,
                        'color_name'         => $colorName,
                        'planned_qty'        => $plannedQty,
                        'cut_qty'            => $cutQty,
                        'rejected_qty'       => $rejQty,
                        'wastage_qty'        => $wQty,
                    ];
                    $totalCut += $cutQty;
                    $totalPlanned += $plannedQty;
                    $totalRejected += $rejQty;
                    $totalWastage += $wQty;
                }
            }
        }

        $ratePerKg = $rollWeight > 0 ? ($rollPrice / $rollWeight) : 0;
        if (empty($wastageCost) || $wastageCost <= 0) {
            $wastageCost = max(0.0, (float)($_POST['wastage_cost'] ?? round($wastageWeight * $ratePerKg, 2)));
        }
        $costPerPiece = ($rollPrice > 0 && $totalCut > 0) ? round($rollPrice / $totalCut, 2) : 0.00;

        try {
            $userId = Session::userId();
            $batchId = ProductionService::createCuttingBatch([
                'name'               => $name,
                'program_id'         => $programId,
                'batch_number'       => $batchNumber,
                'seller_roll_id'     => $sellerRollId,
                'roll_weight'        => $rollWeight,
                'roll_price'         => $rollPrice,
                'wastage_weight'     => $wastageWeight,
                'wastage_cost'       => $wastageCost,
                'used_weight'        => $usedWeight,
                'cost_per_piece'     => $costPerPiece,
                'category_id'        => $categoryId,
                'subcategory_id'     => $subcategoryId,
                'sub_subcategory_id' => $subSubcategoryId,
                'operator_id'        => $operatorId,
                'production_unit_id' => $unitId,
                'material_issued'    => "Roll Weight: {$rollWeight} kg | Wastage: {$wastageWeight} kg (₹{$wastageCost}) | Used: {$usedWeight} kg",
                'planned_qty'        => $totalPlanned,
                'cut_qty'            => $totalCut,
                'rejected_qty'       => $totalRejected,
                'wastage_qty'        => $totalWastage,
                'status'             => $status,
                'notes'              => $_POST['notes'] ?? '',
            ], $items, $userId);

            // Sync rolls in cutting_batch_rolls
            CuttingBatch::syncRolls($batchId, $validRolls);

            Session::flash('success', "Cutting Batch '{$name}' created successfully with {$totalCut} pcs.");
            Response::redirect(url('production/cutting'));
        } catch (Exception $e) {
            Session::flash('error', 'Error creating cutting batch: ' . $e->getMessage());
            Response::back();
        }
    }

    public function editCutting(string $id): void {
        $batch = CuttingBatch::find((int)$id);
        if (!$batch) {
            Response::error(404, 'Cutting batch not found.');
        }

        $batchRolls = CuttingBatch::getRolls((int)$id);
        $rolls = Database::fetchAll(
            "SELECT sr.*, s.name as seller_name, s.company as seller_company, s.material_type
             FROM seller_rolls sr
             JOIN sellers s ON sr.seller_id = s.id
             WHERE s.is_active = 1
             ORDER BY sr.id DESC"
        );

        $categories = Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
        $items = CuttingBatch::getItems((int)$id);
        $users = Database::fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");
        $units = function_exists('getScopedProductionUnits') ? getScopedProductionUnits() : Database::fetchAll("SELECT * FROM production_units WHERE is_active = 1 ORDER BY name ASC");

        Response::view('production.cutting.edit', [
            'pageTitle'    => 'Edit Cutting Batch — ' . ($batch['name'] ?: $batch['batch_number']),
            'activeModule' => 'production',
            'activeMenu'   => 'cutting',
            'batch'        => $batch,
            'batchRolls'   => $batchRolls,
            'rolls'        => $rolls,
            'categories'   => $categories,
            'items'        => $items,
            'users'        => $users,
            'units'        => $units,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Cutting', 'url' => url('production/cutting')],
                ['label' => $batch['batch_number']],
                ['label' => 'Edit'],
            ],
        ]);
    }

    public function updateCutting(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $rollWeight = max(0.0, (float)($_POST['roll_weight'] ?? 0));
        $rollPrice = max(0.0, (float)($_POST['roll_price'] ?? 0));
        $wastageWeight = max(0.0, (float)($_POST['wastage_weight'] ?? 0));
        $operatorId = !empty($_POST['operator_id']) ? (int)$_POST['operator_id'] : null;
        $unitId = !empty($_POST['production_unit_id']) ? (int)$_POST['production_unit_id'] : null;
        $status = $_POST['status'] ?? 'IN_PROGRESS';
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'Cutting Name is required. Please provide a valid batch name.');
            Response::back();
            return;
        }

        // Process multiple rolls from $_POST['rolls']
        $submittedRolls = !empty($_POST['rolls']) && is_array($_POST['rolls']) ? $_POST['rolls'] : [];
        $validRolls = [];
        $calcTotalRollWeight = 0.0;
        $calcTotalRollPrice = 0.0;
        $sellerRollId = null;

        foreach ($submittedRolls as $r) {
            $rId = !empty($r['seller_roll_id']) ? (int)$r['seller_roll_id'] : 0;
            if ($rId <= 0) continue;

            $rWeight = max(0.0, (float)($r['roll_weight'] ?? 0));
            $rPrice = max(0.0, (float)($r['roll_price'] ?? 0));
            $rWastageWeight = max(0.0, (float)($r['wastage_weight'] ?? 0));
            $rRate = $rWeight > 0 ? ($rPrice / $rWeight) : 0;
            $rWastageCost = max(0.0, (float)($r['wastage_cost'] ?? round($rWastageWeight * $rRate, 2)));

            $rCategoryId = !empty($r['category_id']) ? (int)$r['category_id'] : (!empty($_POST['category_id']) ? (int)$_POST['category_id'] : null);
            $rSubcatId = !empty($r['subcategory_id']) ? (int)$r['subcategory_id'] : (!empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null);
            $rSubSubcatId = !empty($r['sub_subcategory_id']) ? (int)$r['sub_subcategory_id'] : (!empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null);

            $sellerData = Database::fetchOne("SELECT seller_id, weight, price, color, design_name FROM seller_rolls WHERE id = ?", [$rId]);

            if ($sellerData) {
                if ($rWeight <= 0) $rWeight = (float)$sellerData['weight'];
                if ($rPrice <= 0) $rPrice = (float)$sellerData['price'];
                $validRolls[] = [
                    'seller_roll_id'     => $rId,
                    'seller_id'          => (int)$sellerData['seller_id'],
                    'roll_weight'        => $rWeight,
                    'roll_price'         => $rPrice,
                    'wastage_weight'     => $rWastageWeight,
                    'wastage_cost'       => $rWastageCost,
                    'roll_color'         => $sellerData['color'] ?? '',
                    'roll_design'        => $sellerData['design_name'] ?? '',
                    'category_id'        => $rCategoryId,
                    'subcategory_id'     => $rSubcatId,
                    'sub_subcategory_id' => $rSubSubcatId,
                ];
                $calcTotalRollWeight += $rWeight;
                $calcTotalRollPrice += $rPrice;
            }
        }

        if (empty($validRolls) && !empty($_POST['seller_roll_id'])) {
            $singleRollId = (int)$_POST['seller_roll_id'];
            $sellerData = Database::fetchOne("SELECT seller_id, weight, price, color, design_name FROM seller_rolls WHERE id = ?", [$singleRollId]);
            if ($sellerData) {
                $rWeight = max(0.0, (float)($_POST['roll_weight'] ?? $sellerData['weight']));
                $rPrice = max(0.0, (float)($_POST['roll_price'] ?? $sellerData['price']));
                $rWastageWeight = max(0.0, (float)($_POST['wastage_weight'] ?? 0));
                $rRate = $rWeight > 0 ? ($rPrice / $rWeight) : 0;
                $rWastageCost = max(0.0, (float)($r['wastage_cost'] ?? round($rWastageWeight * $rRate, 2)));

                $validRolls[] = [
                    'seller_roll_id'     => $singleRollId,
                    'seller_id'          => (int)$sellerData['seller_id'],
                    'roll_weight'        => $rWeight,
                    'roll_price'         => $rPrice,
                    'wastage_weight'     => $rWastageWeight,
                    'wastage_cost'       => $rWastageCost,
                    'roll_color'         => $sellerData['color'] ?? '',
                    'roll_design'        => $sellerData['design_name'] ?? '',
                    'category_id'        => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                    'subcategory_id'     => !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null,
                    'sub_subcategory_id' => !empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null,
                ];
                $calcTotalRollWeight = $rWeight;
                $calcTotalRollPrice = $rPrice;
            }
        }

        if ($calcTotalRollWeight > 0) $rollWeight = $calcTotalRollWeight;
        if ($calcTotalRollPrice > 0) $rollPrice = $calcTotalRollPrice;
        if (!empty($validRolls)) {
            $sellerRollId = $validRolls[0]['seller_roll_id'];

            $sumRollWastageWeight = array_sum(array_column($validRolls, 'wastage_weight'));
            $sumRollWastageCost = array_sum(array_column($validRolls, 'wastage_cost'));
            if ($sumRollWastageWeight > 0) {
                $wastageWeight = $sumRollWastageWeight;
            }
            if ($sumRollWastageCost > 0) {
                $wastageCost = $sumRollWastageCost;
            }
        }

        $usedWeight = max(0.0, $rollWeight - $wastageWeight);

        $items = [];
        $totalCut = 0;
        $totalPlanned = 0;
        $totalRejected = 0;
        $totalWastage = 0;

        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $vals) {
                $cutQty = (int)($vals['cut_qty'] ?? 0);
                $plannedQty = (int)($vals['planned_qty'] ?? $cutQty);
                $rejQty = (int)($vals['rejected_qty'] ?? 0);
                $wQty = (int)($vals['wastage_qty'] ?? 0);
                $sizeName = trim($vals['size_name'] ?? '');
                $colorName = trim($vals['color_name'] ?? '');
                $variantId = !empty($vals['variant_id']) ? (int)$vals['variant_id'] : null;

                $itemCatId = !empty($vals['category_id']) ? (int)$vals['category_id'] : null;
                $itemSubcatId = !empty($vals['subcategory_id']) ? (int)$vals['subcategory_id'] : null;
                $itemSubSubcatId = !empty($vals['sub_subcategory_id']) ? (int)$vals['sub_subcategory_id'] : null;
                $itemCatalogHierarchy = !empty($vals['catalog_hierarchy']) ? trim($vals['catalog_hierarchy']) : null;

                if (!empty($sizeName) || !empty($colorName) || $cutQty > 0 || $plannedQty > 0 || $rejQty > 0 || $wQty > 0) {
                    $items[] = [
                        'variant_id'         => $variantId,
                        'category_id'        => $itemCatId,
                        'subcategory_id'     => $itemSubcatId,
                        'sub_subcategory_id' => $itemSubSubcatId,
                        'catalog_hierarchy'  => $itemCatalogHierarchy,
                        'size_name'          => $sizeName,
                        'color_name'         => $colorName,
                        'planned_qty'        => $plannedQty,
                        'cut_qty'            => $cutQty,
                        'rejected_qty'       => $rejQty,
                        'wastage_qty'        => $wQty,
                    ];
                    $totalCut += $cutQty;
                    $totalPlanned += $plannedQty;
                    $totalRejected += $rejQty;
                    $totalWastage += $wQty;
                }
            }
        }

        $ratePerKg = $rollWeight > 0 ? ($rollPrice / $rollWeight) : 0;
        $wastageCost = max(0.0, (float)($_POST['wastage_cost'] ?? round($wastageWeight * $ratePerKg, 2)));
        $costPerPiece = ($rollPrice > 0 && $totalCut > 0) ? round($rollPrice / $totalCut, 2) : 0.00;

        try {
            $updateData = [
                'name'               => $name,
                'roll_weight'        => $rollWeight,
                'roll_price'         => $rollPrice,
                'wastage_weight'     => $wastageWeight,
                'wastage_cost'       => $wastageCost,
                'used_weight'        => $usedWeight,
                'cost_per_piece'     => $costPerPiece,
                'operator_id'        => $operatorId,
                'production_unit_id' => $unitId,
                'planned_qty'        => $totalPlanned,
                'cut_qty'            => $totalCut,
                'rejected_qty'       => $totalRejected,
                'wastage_qty'        => $totalWastage,
                'status'             => $status,
                'notes'              => $notes,
            ];
            if ($sellerRollId) {
                $updateData['seller_roll_id'] = $sellerRollId;
            }

            ProductionService::updateCuttingBatch((int)$id, $updateData, $items);

            // Sync rolls in cutting_batch_rolls
            CuttingBatch::syncRolls((int)$id, $validRolls);

            Session::flash('success', 'Cutting batch updated successfully.');
            Response::redirect(url('production/cutting'));
        } catch (Exception $e) {
            Session::flash('error', 'Error updating cutting batch: ' . $e->getMessage());
            Response::back();
        }
    }

    public function deleteCutting(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $batch = CuttingBatch::find((int)$id);
        if (!$batch) {
            Session::flash('error', 'Cutting batch not found.');
            Response::redirect(url('production/cutting'));
            return;
        }

        CuttingBatch::delete((int)$id);
        AuditService::log('production', 'delete', 'cutting_batches', (int)$id, null, ['status' => 'CANCELLED', 'is_active' => 0]);

        $name = $batch['name'] ?: $batch['batch_number'];
        Session::flash('success', "Cutting Batch '{$name}' deleted successfully.");
        Response::redirect(url('production/cutting'));
    }

    // =========================================================================
    // STITCHING
    // =========================================================================

    public function stitchingList(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $filters = [
            'status'             => $_GET['status'] ?? '',
            'program_id'         => $_GET['program_id'] ?? '',
            'production_unit_id' => $scopedUnitId ? (string)$scopedUnitId : ($_GET['production_unit_id'] ?? ''),
            'search'             => $_GET['search'] ?? '',
        ];

        $batches = StitchingBatch::getAll($filters);

        Response::view('production.stitching.index', [
            'pageTitle'    => 'Stitching Batches',
            'activeModule' => 'production',
            'activeMenu'   => 'stitching',
            'batches'      => $batches,
            'filters'      => $filters,
            'scopedUnitId' => $scopedUnitId,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Stitching'],
            ],
        ]);
    }

    public function createStitching(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $programId = $_GET['program_id'] ?? null;
        $cuttingBatchId = $_GET['cutting_batch_id'] ?? null;

        $progUnitClause = $scopedUnitId ? " AND pp.production_unit_id = $scopedUnitId" : "";
        $programs = Database::fetchAll(
            "SELECT pp.*, p.name as product_name, p.code as product_code, pu.name as unit_name
             FROM production_programs pp
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             WHERE pp.is_active = 1$progUnitClause
               AND pp.id NOT IN (SELECT program_id FROM stitching_batches WHERE program_id IS NOT NULL AND (is_active = 1 OR is_active IS NULL) AND status != 'CANCELLED')
             ORDER BY pp.id DESC"
        );

        $cuttingBatches = Database::fetchAll(
            "SELECT cb.*, pp.program_number, pp.name as program_name, p.name as product_name
             FROM cutting_batches cb
             LEFT JOIN production_programs pp ON cb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             WHERE (cb.is_active = 1 OR cb.is_active IS NULL)$progUnitClause
               AND cb.id NOT IN (SELECT cutting_batch_id FROM stitching_batches WHERE cutting_batch_id IS NOT NULL AND (is_active = 1 OR is_active IS NULL) AND status != 'CANCELLED')
             ORDER BY cb.id DESC"
        );

        $categories = Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
        $users = Database::fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");

        $selectedProgram = null;
        $selectedCutting = null;
        $items = [];

        if ($cuttingBatchId) {
            $alreadyStitched = Database::fetchOne(
                "SELECT id, batch_number FROM stitching_batches WHERE cutting_batch_id = ? AND (is_active = 1 OR is_active IS NULL) AND status != 'CANCELLED'",
                [(int)$cuttingBatchId]
            );
            if ($alreadyStitched) {
                Session::flash('error', "Cutting Batch has already been assigned to Stitching Batch {$alreadyStitched['batch_number']}. Duplicate stitching is not allowed.");
                Response::redirect(url('production/stitching'));
                return;
            }
            $selectedCutting = CuttingBatch::find((int)$cuttingBatchId);
            if ($selectedCutting) {
                $programId = $selectedCutting['program_id'];
                $items = CuttingBatch::getItems((int)$cuttingBatchId);
            }
        }

        if ($programId && !$cuttingBatchId) {
            $alreadyStitchedProg = Database::fetchOne(
                "SELECT id, batch_number FROM stitching_batches WHERE program_id = ? AND cutting_batch_id IS NULL AND (is_active = 1 OR is_active IS NULL) AND status != 'CANCELLED'",
                [(int)$programId]
            );
            if ($alreadyStitchedProg) {
                Session::flash('error', "Production Program has already been assigned to Stitching Batch {$alreadyStitchedProg['batch_number']}. Duplicate stitching is not allowed.");
                Response::redirect(url('production/stitching'));
                return;
            }
            $selectedProgram = ProductionProgram::find((int)$programId);
        } elseif ($programId) {
            $selectedProgram = ProductionProgram::find((int)$programId);
        }

        $count = Database::fetchOne("SELECT COUNT(*) as c FROM stitching_batches")['c'] ?? 0;
        $suggestedBatchNumber = 'STCH-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

        Response::view('production.stitching.create', [
            'pageTitle'            => 'New Stitching Batch',
            'activeModule'         => 'production',
            'activeMenu'           => 'stitching',
            'programs'             => $programs,
            'cuttingBatches'       => $cuttingBatches,
            'selectedProgram'      => $selectedProgram,
            'selectedCutting'      => $selectedCutting,
            'categories'           => $categories,
            'suggestedBatchNumber' => $suggestedBatchNumber,
            'items'                => $items,
            'users'                => $users,
            'breadcrumb'           => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Stitching', 'url' => url('production/stitching')],
                ['label' => 'New Stitching Batch'],
            ],
        ]);
    }

    public function storeStitching(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $programId = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $cuttingBatchId = !empty($_POST['cutting_batch_id']) ? (int)$_POST['cutting_batch_id'] : null;

        if (!$programId && $cuttingBatchId) {
            $cb = CuttingBatch::find($cuttingBatchId);
            if ($cb) {
                $programId = (int)$cb['program_id'];
            }
        }

        if (!$programId) {
            Session::flash('error', 'Please select a Production Program.');
            Response::back();
        }

        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $batchNumber = trim($_POST['batch_number'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null;
        $workerId = !empty($_POST['worker_id']) ? (int)$_POST['worker_id'] : null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = $_POST['status'] ?? 'IN_PROGRESS';
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'Stitching Name is required. Please enter a valid batch name.');
            Response::back();
            return;
        }

        if (empty($cuttingBatchId)) {
            Session::flash('error', 'Select Cutting Name is required. Please choose a source Cutting Batch.');
            Response::back();
            return;
        }

        // Prevent duplicate stitching of the same cutting batch
        if (!empty($cuttingBatchId)) {
            $existingStitching = Database::fetchOne(
                "SELECT id, batch_number, name FROM stitching_batches WHERE cutting_batch_id = ? AND (is_active = 1 OR is_active IS NULL) AND status != 'CANCELLED'",
                [$cuttingBatchId]
            );
            if ($existingStitching) {
                $cb = CuttingBatch::find($cuttingBatchId);
                $cbNum = $cb['batch_number'] ?? ('#' . $cuttingBatchId);
                Session::flash('error', "Validation Error: Cutting Batch ({$cbNum}) has already been assigned to Stitching Batch {$existingStitching['batch_number']}. Duplicate stitching of the same cutting batch is not allowed.");
                Response::back();
                return;
            }
        }

        if (empty($cuttingBatchId) && !empty($programId)) {
            $existingStitchingProg = Database::fetchOne(
                "SELECT id, batch_number, name FROM stitching_batches WHERE program_id = ? AND cutting_batch_id IS NULL AND (is_active = 1 OR is_active IS NULL) AND status != 'CANCELLED'",
                [$programId]
            );
            if ($existingStitchingProg) {
                $prog = ProductionProgram::find($programId);
                $progNum = $prog['program_number'] ?? ('#' . $programId);
                Session::flash('error', "Validation Error: Production Program ({$progNum}) has already been assigned to Stitching Batch {$existingStitchingProg['batch_number']}. Duplicate stitching is not allowed.");
                Response::back();
                return;
            }
        }

        if (empty($batchNumber)) {
            $count = Database::fetchOne("SELECT COUNT(*) as c FROM stitching_batches")['c'] ?? 0;
            $batchNumber = 'STCH-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
        }

        $items = [];
        $totalStitched = 0;
        $totalWeight = 0.0;

        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $vals) {
                $stitchedQty = (int)($vals['stitched_qty'] ?? ($vals['qty'] ?? 0));
                $receivedQty = (int)($vals['received_qty'] ?? $stitchedQty);
                $weight = max(0.0, (float)($vals['weight'] ?? 0));
                $unitWeight = isset($vals['weight_per_piece']) ? max(0.0, (float)$vals['weight_per_piece']) : ($stitchedQty > 0 ? round($weight / $stitchedQty, 3) : 0.000);
                if ($weight <= 0 && $stitchedQty > 0 && $unitWeight > 0) {
                    $weight = round($stitchedQty * $unitWeight, 2);
                }
                $rejQty = (int)($vals['rejected_qty'] ?? 0);
                $rewQty = (int)($vals['rework_qty'] ?? 0);
                $basePrice = max(0.0, (float)($vals['base_price'] ?? 0));
                $discountPrice = max(0.0, (float)($vals['discount_price'] ?? 0));
                $sizeName = trim($vals['size_name'] ?? '');
                $colorName = trim($vals['color_name'] ?? '');
                $variantId = !empty($vals['variant_id']) ? (int)$vals['variant_id'] : null;

                $itemCatId = !empty($vals['category_id']) ? (int)$vals['category_id'] : $categoryId;
                $itemSubcatId = !empty($vals['subcategory_id']) ? (int)$vals['subcategory_id'] : $subcategoryId;
                $itemSubSubcatId = !empty($vals['sub_subcategory_id']) ? (int)$vals['sub_subcategory_id'] : $subSubcategoryId;
                $itemCatalogHierarchy = !empty($vals['catalog_hierarchy']) ? trim($vals['catalog_hierarchy']) : null;

                if (!empty($sizeName) || !empty($colorName) || $stitchedQty > 0 || $weight > 0 || $receivedQty > 0) {
                    $items[] = [
                        'variant_id'         => $variantId,
                        'category_id'        => $itemCatId,
                        'subcategory_id'     => $itemSubcatId,
                        'sub_subcategory_id' => $itemSubSubcatId,
                        'catalog_hierarchy'  => $itemCatalogHierarchy,
                        'size_name'          => $sizeName,
                        'color_name'         => $colorName,
                        'received_qty'       => $receivedQty,
                        'stitched_qty'       => $stitchedQty,
                        'weight_per_piece'   => $unitWeight,
                        'weight'             => $weight,
                        'base_price'         => $basePrice,
                        'discount_price'     => $discountPrice,
                        'rejected_qty'       => $rejQty,
                        'rework_qty'         => $rewQty,
                    ];
                    $totalStitched += $stitchedQty;
                    $totalWeight += $weight;
                }
            }
        }

        if (empty($items)) {
            Session::flash('error', 'Please fill all required variant items.');
            Response::back();
            return;
        }

        foreach ($items as $idx => $item) {
            $rowNum = $idx + 1;
            $vName = trim(($item['size_name'] ?: '') . ' / ' . ($item['color_name'] ?: ''));
            if ($item['stitched_qty'] <= 0 || $item['weight_per_piece'] <= 0 || $item['weight'] <= 0 || $item['base_price'] <= 0 || $item['discount_price'] <= 0) {
                Session::flash('error', "Row #{$rowNum} ({$vName}) has missing or 0 values. Qty, Weight, Base Price, and Discount Price must all be greater than 0.");
                Response::back();
                return;
            }
            if ($item['discount_price'] >= $item['base_price']) {
                Session::flash('error', "Row #{$rowNum} ({$vName}): Discount Price must be less than Base Price.");
                Response::back();
                return;
            }
        }

        // Validate Stitching Weight against Source Cutting Batch Usable Weight
        if ($cuttingBatchId) {
            $cb = CuttingBatch::find($cuttingBatchId);
            if ($cb) {
                $cuttingUsedWeight = (float)($cb['used_weight'] > 0 ? $cb['used_weight'] : max(0.0, (float)($cb['roll_weight'] ?? 0) - (float)($cb['wastage_weight'] ?? 0)));
                if ($cuttingUsedWeight > 0 && $totalWeight > round($cuttingUsedWeight + 0.01, 2)) {
                    Session::flash('error', "Validation Error: Total Stitching Weight (" . number_format($totalWeight, 2) . " kg) exceeds Cutting Batch Usable Weight (" . number_format($cuttingUsedWeight, 2) . " kg). The stitching weight must match the cutting weight.");
                    Response::back();
                    return;
                }
            }
        }

        try {
            $userId = Session::userId();
            $batchId = ProductionService::createStitchingBatch([
                'name'               => $name,
                'batch_number'       => $batchNumber,
                'program_id'         => $programId,
                'cutting_batch_id'   => $cuttingBatchId,
                'category_id'        => $categoryId,
                'subcategory_id'     => $subcategoryId,
                'sub_subcategory_id' => $subSubcategoryId,
                'worker_id'          => $workerId,
                'start_date'         => $startDate,
                'end_date'           => $endDate,
                'received_qty'       => $totalStitched,
                'stitched_qty'       => $totalStitched,
                'total_weight'       => $totalWeight,
                'status'             => $status,
                'notes'              => $notes,
            ], $items, $userId);

            Session::flash('success', "Stitching Batch '{$name}' created successfully with {$totalStitched} pcs (" . number_format($totalWeight, 2) . " kg).");
            Response::redirect(url('production/stitching'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    public function editStitching(string $id): void {
        $batch = StitchingBatch::find((int)$id);
        if (!$batch) {
            Response::error(404, 'Stitching batch not found.');
        }

        $programs = Database::fetchAll(
            "SELECT pp.*, p.name as product_name, p.code as product_code, pu.name as unit_name
             FROM production_programs pp
             LEFT JOIN products p ON pp.product_id = p.id
             LEFT JOIN production_units pu ON pp.production_unit_id = pu.id
             WHERE pp.is_active = 1
             ORDER BY pp.id DESC"
        );

        $cuttingBatches = Database::fetchAll(
            "SELECT cb.*, pp.program_number, pp.name as program_name, p.name as product_name
             FROM cutting_batches cb
             LEFT JOIN production_programs pp ON cb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             WHERE (cb.is_active = 1 OR cb.is_active IS NULL)
             ORDER BY cb.id DESC"
        );

        $categories = Database::fetchAll("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
        $items = StitchingBatch::getItems((int)$id);

        $sourceCutting = !empty($batch['cutting_batch_id']) ? CuttingBatch::find((int)$batch['cutting_batch_id']) : null;
        $cuttingUsableWeight = 0.0;
        if ($sourceCutting) {
            $cuttingUsableWeight = (float)($sourceCutting['used_weight'] > 0 ? $sourceCutting['used_weight'] : max(0.0, (float)($sourceCutting['roll_weight'] ?? 0) - (float)($sourceCutting['wastage_weight'] ?? 0)));
        }

        // Sync received_qty / cut_qty from linked source cutting batch items
        if (!empty($batch['cutting_batch_id'])) {
            $cuttingItems = CuttingBatch::getItems((int)$batch['cutting_batch_id']);
            $cutMap = [];
            foreach ($cuttingItems as $ci) {
                $sKey = strtoupper(trim($ci['size_name'] ?? ''));
                $cKey = strtoupper(trim($ci['color_name'] ?? ''));
                $cutMap[$sKey . '_' . $cKey] = (int)($ci['cut_qty'] ?? 0);
            }
            foreach ($items as &$it) {
                $sName = strtoupper(trim(!empty($it['size_name']) ? $it['size_name'] : ($it['display_size'] ?? '')));
                $cName = strtoupper(trim(!empty($it['color_name']) ? $it['color_name'] : ($it['display_color'] ?? '')));
                $key = $sName . '_' . $cName;
                if (isset($cutMap[$key])) {
                    $it['received_qty'] = $cutMap[$key];
                    $it['cut_qty'] = $cutMap[$key];
                }
            }
            unset($it);
        }

        // Load rolls for catalog hierarchy mapping
        $rolls = !empty($batch['cutting_batch_id']) ? CuttingBatch::getRolls((int)$batch['cutting_batch_id']) : [];
        if (empty($rolls) && !empty($batch['program_id'])) {
            $rolls = ProductionProgram::getRolls((int)$batch['program_id']);
        }

        $defaultHierarchyParts = array_filter([
            $batch['category_name'] ?? '',
            $batch['subcategory_name'] ?? '',
            $batch['sub_subcategory_name'] ?? ''
        ]);
        $defaultCatalogHierarchy = !empty($defaultHierarchyParts) ? implode(' › ', $defaultHierarchyParts) : 'General Apparel';

        $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
            'name'               => $defaultCatalogHierarchy,
            'category_id'        => $batch['category_id'] ?? null,
            'subcategory_id'     => $batch['subcategory_id'] ?? null,
            'sub_subcategory_id' => $batch['sub_subcategory_id'] ?? null,
        ], 'stitched_qty');

        $users = Database::fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");

        Response::view('production.stitching.edit', [
            'pageTitle'           => 'Edit Stitching Batch — ' . ($batch['name'] ?: $batch['batch_number']),
            'activeModule'        => 'production',
            'activeMenu'          => 'stitching',
            'batch'               => $batch,
            'sourceCutting'       => $sourceCutting,
            'cuttingUsableWeight' => $cuttingUsableWeight,
            'programs'            => $programs,
            'cuttingBatches'      => $cuttingBatches,
            'categories'          => $categories,
            'distinctCatalogs'    => array_values($distinctCatalogs),
            'items'               => $items,
            'users'               => $users,
            'breadcrumb'          => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Stitching', 'url' => url('production/stitching')],
                ['label' => $batch['batch_number'] ?: ('Batch #' . $batch['id'])],
                ['label' => 'Edit'],
            ],
        ]);
    }

    public function updateStitching(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $programId = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $cuttingBatchId = !empty($_POST['cutting_batch_id']) ? (int)$_POST['cutting_batch_id'] : null;
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_subcategory_id']) ? (int)$_POST['sub_subcategory_id'] : null;
        $workerId = !empty($_POST['worker_id']) ? (int)$_POST['worker_id'] : null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $status = $_POST['status'] ?? 'IN_PROGRESS';
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'Stitching Name is required. Please enter a valid batch name.');
            Response::back();
            return;
        }

        $items = [];
        $totalStitched = 0;
        $totalWeight = 0.0;

        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $vals) {
                $stitchedQty = (int)($vals['stitched_qty'] ?? ($vals['qty'] ?? 0));
                $receivedQty = (int)($vals['received_qty'] ?? $stitchedQty);
                $weight = max(0.0, (float)($vals['weight'] ?? 0));
                $unitWeight = isset($vals['weight_per_piece']) ? max(0.0, (float)$vals['weight_per_piece']) : ($stitchedQty > 0 ? round($weight / $stitchedQty, 3) : 0.000);
                if ($weight <= 0 && $stitchedQty > 0 && $unitWeight > 0) {
                    $weight = round($stitchedQty * $unitWeight, 2);
                }
                $rejQty = (int)($vals['rejected_qty'] ?? 0);
                $rewQty = (int)($vals['rework_qty'] ?? 0);
                $basePrice = max(0.0, (float)($vals['base_price'] ?? 0));
                $discountPrice = max(0.0, (float)($vals['discount_price'] ?? 0));
                $sizeName = trim($vals['size_name'] ?? '');
                $colorName = trim($vals['color_name'] ?? '');
                $variantId = !empty($vals['variant_id']) ? (int)$vals['variant_id'] : null;

                $itemCatId = !empty($vals['category_id']) ? (int)$vals['category_id'] : $categoryId;
                $itemSubcatId = !empty($vals['subcategory_id']) ? (int)$vals['subcategory_id'] : $subcategoryId;
                $itemSubSubcatId = !empty($vals['sub_subcategory_id']) ? (int)$vals['sub_subcategory_id'] : $subSubcategoryId;
                $itemCatalogHierarchy = !empty($vals['catalog_hierarchy']) ? trim($vals['catalog_hierarchy']) : null;

                if (!empty($sizeName) || !empty($colorName) || $stitchedQty > 0 || $weight > 0 || $receivedQty > 0) {
                    $items[] = [
                        'variant_id'         => $variantId,
                        'category_id'        => $itemCatId,
                        'subcategory_id'     => $itemSubcatId,
                        'sub_subcategory_id' => $itemSubSubcatId,
                        'catalog_hierarchy'  => $itemCatalogHierarchy,
                        'size_name'          => $sizeName,
                        'color_name'         => $colorName,
                        'received_qty'       => $receivedQty,
                        'stitched_qty'       => $stitchedQty,
                        'weight_per_piece'   => $unitWeight,
                        'weight'             => $weight,
                        'base_price'         => $basePrice,
                        'discount_price'     => $discountPrice,
                        'rejected_qty'       => $rejQty,
                        'rework_qty'         => $rewQty,
                    ];
                    $totalStitched += $stitchedQty;
                    $totalWeight += $weight;
                }
            }
        }

        if (empty($items)) {
            Session::flash('error', 'Please fill all required variant items.');
            Response::back();
            return;
        }

        foreach ($items as $idx => $item) {
            $rowNum = $idx + 1;
            $vName = trim(($item['size_name'] ?: '') . ' / ' . ($item['color_name'] ?: ''));
            if ($item['stitched_qty'] <= 0 || $item['weight_per_piece'] <= 0 || $item['weight'] <= 0 || $item['base_price'] <= 0 || $item['discount_price'] <= 0) {
                Session::flash('error', "Row #{$rowNum} ({$vName}) has missing or 0 values. Qty, Weight, Base Price, and Discount Price must all be greater than 0.");
                Response::back();
                return;
            }
            if ($item['discount_price'] >= $item['base_price']) {
                Session::flash('error', "Row #{$rowNum} ({$vName}): Discount Price must be less than Base Price.");
                Response::back();
                return;
            }
        }

        // Validate Stitching Weight against Source Cutting Batch Usable Weight
        $existingBatch = StitchingBatch::find((int)$id);
        $targetCuttingId = $cuttingBatchId ?: ($existingBatch['cutting_batch_id'] ?? null);
        if ($targetCuttingId) {
            $cb = CuttingBatch::find((int)$targetCuttingId);
            if ($cb) {
                $cuttingUsedWeight = (float)($cb['used_weight'] > 0 ? $cb['used_weight'] : max(0.0, (float)($cb['roll_weight'] ?? 0) - (float)($cb['wastage_weight'] ?? 0)));
                if ($cuttingUsedWeight > 0 && $totalWeight > round($cuttingUsedWeight + 0.01, 2)) {
                    Session::flash('error', "Validation Error: Total Stitching Weight (" . number_format($totalWeight, 2) . " kg) exceeds Cutting Batch Usable Weight (" . number_format($cuttingUsedWeight, 2) . " kg). The stitching weight must match the cutting weight.");
                    Response::back();
                    return;
                }
            }
        }

        try {
            $batchData = [
                'worker_id'          => $workerId,
                'start_date'         => $startDate,
                'end_date'           => $endDate,
                'status'             => $status,
                'notes'              => $notes,
                'received_qty'       => $totalStitched,
                'stitched_qty'       => $totalStitched,
                'total_weight'       => $totalWeight,
            ];
            if (!empty($name)) $batchData['name'] = $name;
            if ($programId) $batchData['program_id'] = $programId;
            if ($cuttingBatchId) $batchData['cutting_batch_id'] = $cuttingBatchId;
            if ($categoryId) $batchData['category_id'] = $categoryId;
            if ($subcategoryId) $batchData['subcategory_id'] = $subcategoryId;
            if ($subSubcategoryId) $batchData['sub_subcategory_id'] = $subSubcategoryId;

            ProductionService::updateStitchingBatch((int)$id, $batchData, $items);

            Session::flash('success', "Stitching Batch updated successfully with {$totalStitched} pcs (" . number_format($totalWeight, 2) . " kg).");
            Response::redirect(url('production/stitching'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    public function deleteStitching(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $batchId = (int)$id;
        $batch = StitchingBatch::find($batchId);
        if (!$batch) {
            Session::flash('error', 'Stitching batch not found.');
            Response::redirect(url('production/stitching'));
            return;
        }

        try {
            StitchingBatch::update($batchId, ['is_active' => 0]);
            AuditService::log('production', 'delete', 'stitching_batch', $batchId, $batch, ['is_active' => 0]);
            Session::flash('success', "Stitching Batch '{$batch['name']}' deleted successfully.");
        } catch (Exception $e) {
            Session::flash('error', 'Error deleting stitching batch: ' . $e->getMessage());
        }

        Response::redirect(url('production/stitching'));
    }

    // =========================================================================
    // LABELS
    // =========================================================================

    public function labelList(): void {
        $filters = [
            'status'     => $_GET['status'] ?? '',
            'program_id' => $_GET['program_id'] ?? '',
            'search'     => $_GET['search'] ?? '',
        ];

        $batches = LabelBatch::getAll($filters);

        Response::view('production.labels.index', [
            'pageTitle'    => 'Label Batches',
            'activeModule' => 'production',
            'activeMenu'   => 'labels',
            'batches'      => $batches,
            'filters'      => $filters,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Labels'],
            ],
        ]);
    }

    public function createLabel(): void {
        $programId = $_GET['program_id'] ?? null;
        $stitchingBatchId = $_GET['stitching_batch_id'] ?? null;

        $stitchingBatches = Database::fetchAll(
            "SELECT sb.*, pp.program_number, pp.name as program_name, p.name as product_name
             FROM stitching_batches sb
             LEFT JOIN production_programs pp ON sb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             WHERE (sb.is_active = 1 OR sb.is_active IS NULL)
               AND sb.id NOT IN (SELECT stitching_batch_id FROM label_batches WHERE stitching_batch_id IS NOT NULL)
               AND (sb.program_id IS NULL OR sb.program_id NOT IN (SELECT program_id FROM label_batches WHERE program_id IS NOT NULL))
             ORDER BY sb.id DESC"
        );

        $programs = Database::fetchAll(
            "SELECT pp.*, COALESCE(pp.name, p.name) as product_name 
             FROM production_programs pp
             LEFT JOIN products p ON pp.product_id = p.id
             WHERE (pp.is_active = 1 OR pp.is_active IS NULL)
               AND pp.id NOT IN (SELECT program_id FROM label_batches WHERE program_id IS NOT NULL)
             ORDER BY pp.created_at DESC"
        );

        $selectedStitching = null;
        $selectedProgram = null;
        $items = [];
        $rolls = [];
        $distinctCatalogs = [];
        $catalogHierarchy = 'General Apparel';

        if ($stitchingBatchId) {
            $alreadyLabeled = Database::fetchOne(
                "SELECT id, batch_number FROM label_batches WHERE stitching_batch_id = ?",
                [(int)$stitchingBatchId]
            );
            if ($alreadyLabeled) {
                Session::flash('error', "Stitching Batch has already been assigned to Label Batch {$alreadyLabeled['batch_number']}. Duplicate label generation is not allowed.");
                Response::redirect(url('production/labels'));
                return;
            }
            $selectedStitching = StitchingBatch::find((int)$stitchingBatchId);
            if ($selectedStitching) {
                $programId = $selectedStitching['program_id'];
                $items = StitchingBatch::getItems((int)$stitchingBatchId);
                $rolls = StitchingBatch::getRolls((int)$stitchingBatchId);

                $defaultHierarchyParts = array_filter([
                    $selectedStitching['category_name'] ?? '',
                    $selectedStitching['subcategory_name'] ?? '',
                    $selectedStitching['sub_subcategory_name'] ?? ''
                ]);
                $catalogHierarchy = !empty($defaultHierarchyParts) ? implode(' › ', $defaultHierarchyParts) : ($selectedStitching['product_name'] ?? 'General Apparel');

                $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
                    'name'               => $catalogHierarchy,
                    'category_id'        => $selectedStitching['category_id'] ?? null,
                    'subcategory_id'     => $selectedStitching['subcategory_id'] ?? null,
                    'sub_subcategory_id' => $selectedStitching['sub_subcategory_id'] ?? null,
                ], 'stitched_qty');
            }
        }

        if ($programId && !$selectedProgram) {
            $alreadyLabeledProg = Database::fetchOne(
                "SELECT id, batch_number FROM label_batches WHERE program_id = ?",
                [(int)$programId]
            );
            if ($alreadyLabeledProg) {
                Session::flash('error', "Production Program has already been assigned to Label Batch {$alreadyLabeledProg['batch_number']}. Duplicate label generation is not allowed.");
                Response::redirect(url('production/labels'));
                return;
            }
            $selectedProgram = ProductionProgram::find((int)$programId);
            if (empty($items)) {
                $items = ProductionProgram::getItems((int)$programId);
                $rolls = ProductionProgram::getRolls((int)$programId);
                $defaultHierarchyParts = array_filter([
                    $selectedProgram['category_name'] ?? '',
                    $selectedProgram['subcategory_name'] ?? '',
                    $selectedProgram['sub_subcategory_name'] ?? ''
                ]);
                $catalogHierarchy = !empty($defaultHierarchyParts) ? implode(' › ', $defaultHierarchyParts) : ($selectedProgram['product_name'] ?? 'General Apparel');
                $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
                    'name'               => $catalogHierarchy,
                    'category_id'        => $selectedProgram['category_id'] ?? null,
                    'subcategory_id'     => $selectedProgram['subcategory_id'] ?? null,
                    'sub_subcategory_id' => $selectedProgram['sub_subcategory_id'] ?? null,
                ], 'planned_qty');
            }
        }

        $catalogSequences = [];
        foreach ($distinctCatalogs as $cName => &$catData) {
            $lastSeq = LabelItem::getLastSequenceForCatalog(
                $catData['category_id'] ?? null,
                $catData['subcategory_id'] ?? null,
                $catData['sub_subcategory_id'] ?? null,
                $catData['name'] ?? null
            );
            $catData['last_sequence'] = $lastSeq;
            $catData['next_sequence'] = $lastSeq + 1;

            $cKey = ($catData['category_id'] ?? 0) . '_' . ($catData['subcategory_id'] ?? 0) . '_' . ($catData['sub_subcategory_id'] ?? 0);
            $seqInfo = [
                'last_sequence'      => $lastSeq,
                'next_sequence'      => $lastSeq + 1,
                'category_id'        => $catData['category_id'] ?? null,
                'subcategory_id'     => $catData['subcategory_id'] ?? null,
                'sub_subcategory_id' => $catData['sub_subcategory_id'] ?? null,
                'name'               => $catData['name'] ?? null,
            ];
            $catalogSequences[$cKey] = $seqInfo;
            if (!empty($catData['name'])) {
                $catalogSequences[$catData['name']] = $seqInfo;
            }
        }
        unset($catData);

        foreach ($items as &$it) {
            $cKey = ($it['category_id'] ?? 0) . '_' . ($it['subcategory_id'] ?? 0) . '_' . ($it['sub_subcategory_id'] ?? 0);
            $seqInfo = $catalogSequences[$cKey] ?? ($catalogSequences[$it['catalog_hierarchy'] ?? ''] ?? null);
            $it['last_sequence'] = $seqInfo ? $seqInfo['last_sequence'] : 0;
            $it['next_sequence'] = $seqInfo ? $seqInfo['next_sequence'] : 1;
        }
        unset($it);

        $count = Database::fetchOne("SELECT COUNT(*) as c FROM label_batches")['c'] ?? 0;
        $suggestedBatchNumber = 'LBL-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);

        Response::view('production.labels.create', [
            'pageTitle'            => 'New Label Batch & QR Generator',
            'activeModule'         => 'production',
            'activeMenu'           => 'labels',
            'stitchingBatches'     => $stitchingBatches,
            'programs'             => $programs,
            'selectedStitching'    => $selectedStitching,
            'selectedProgram'      => $selectedProgram,
            'suggestedBatchNumber' => $suggestedBatchNumber,
            'items'                => $items,
            'distinctCatalogs'     => array_values($distinctCatalogs),
            'catalogSequences'     => $catalogSequences,
            'catalogHierarchy'     => $catalogHierarchy,
            'breadcrumb'           => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Labels', 'url' => url('production/labels')],
                ['label' => 'New Label Batch'],
            ],
        ]);
    }

    public function storeLabel(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $stitchingBatchId = !empty($_POST['stitching_batch_id']) ? (int)$_POST['stitching_batch_id'] : null;
        $programId = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $batchNumber = trim($_POST['batch_number'] ?? '');
        $status = $_POST['status'] ?? 'IN_PROGRESS';

        if (empty($stitchingBatchId)) {
            Session::flash('error', 'Select Stitching Name is required. Please choose a Stitching Batch.');
            Response::back();
            return;
        }

        if (!$programId && $stitchingBatchId) {
            $sb = StitchingBatch::find($stitchingBatchId);
            if ($sb) {
                $programId = (int)$sb['program_id'];
            }
        }

        if (!$programId) {
            Session::flash('error', 'Please select a valid Stitching Batch linked to a Production Program.');
            Response::back();
            return;
        }

        // Prevent duplicate label batch generation
        if (!empty($stitchingBatchId)) {
            $existingLabel = Database::fetchOne(
                "SELECT id, batch_number FROM label_batches WHERE stitching_batch_id = ?",
                [$stitchingBatchId]
            );
            if ($existingLabel) {
                $sb = StitchingBatch::find($stitchingBatchId);
                $sbNum = $sb['batch_number'] ?? ('#' . $stitchingBatchId);
                Session::flash('error', "Validation Error: Stitching Batch ({$sbNum}) has already been assigned to Label Batch {$existingLabel['batch_number']}. Duplicate label generation is not allowed.");
                Response::back();
                return;
            }
        }

        if (!empty($programId)) {
            $existingLabelProg = Database::fetchOne(
                "SELECT id, batch_number FROM label_batches WHERE program_id = ?",
                [$programId]
            );
            if ($existingLabelProg) {
                $prog = ProductionProgram::find($programId);
                $progNum = $prog['program_number'] ?? ('#' . $programId);
                Session::flash('error', "Validation Error: Production Program ({$progNum}) has already been assigned to Label Batch {$existingLabelProg['batch_number']}. Duplicate label generation is not allowed.");
                Response::back();
                return;
            }
        }

        if (empty($batchNumber)) {
            $count = Database::fetchOne("SELECT COUNT(*) as c FROM label_batches")['c'] ?? 0;
            $batchNumber = 'LBL-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
        }

        $items = [];
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $vals) {
                $variantId = !empty($vals['variant_id']) ? (int)$vals['variant_id'] : null;
                $categoryId = !empty($vals['category_id']) ? (int)$vals['category_id'] : null;
                $subcategoryId = !empty($vals['subcategory_id']) ? (int)$vals['subcategory_id'] : null;
                $subSubcategoryId = !empty($vals['sub_subcategory_id']) ? (int)$vals['sub_subcategory_id'] : null;
                $catHierarchy = !empty($vals['catalog_hierarchy']) ? trim($vals['catalog_hierarchy']) : null;
                $sizeName = !empty($vals['size_name']) ? trim($vals['size_name']) : null;
                $colorName = !empty($vals['color_name']) ? trim($vals['color_name']) : null;
                $basePrice = isset($vals['base_price']) ? (float)$vals['base_price'] : 0.00;
                $discountPrice = isset($vals['discount_price']) ? (float)$vals['discount_price'] : 0.00;
                $reqQty = (int)($vals['required_qty'] ?? 0);
                $prtQty = (int)($vals['printed_qty'] ?? $reqQty);
                $usdQty = (int)($vals['used_qty'] ?? $prtQty);
                $dmgQty = (int)($vals['damaged_qty'] ?? 0);
                $startSeq = !empty($vals['start_seq']) ? (int)$vals['start_seq'] : null;
                $endSeq = !empty($vals['end_seq']) ? (int)$vals['end_seq'] : null;
                $startCode = !empty($vals['start_code']) ? trim($vals['start_code']) : null;
                $endCode = !empty($vals['end_code']) ? trim($vals['end_code']) : null;

                if ($variantId || !empty($sizeName) || $reqQty > 0 || $prtQty > 0) {
                    $items[] = [
                        'variant_id'         => $variantId,
                        'category_id'        => $categoryId,
                        'subcategory_id'     => $subcategoryId,
                        'sub_subcategory_id' => $subSubcategoryId,
                        'catalog_hierarchy'  => $catHierarchy,
                        'size_name'          => $sizeName,
                        'color_name'         => $colorName,
                        'base_price'         => $basePrice,
                        'discount_price'     => $discountPrice,
                        'required_qty'       => $reqQty,
                        'printed_qty'        => $prtQty,
                        'used_qty'           => $usdQty,
                        'damaged_qty'        => $dmgQty,
                        'start_seq'          => $startSeq,
                        'end_seq'            => $endSeq,
                        'start_code'         => $startCode,
                        'end_code'           => $endCode,
                    ];
                }
            }
        }

        if (empty($items)) {
            Session::flash('error', 'Please select a Stitching Name / Batch with valid product variants before saving.');
            Response::back();
            return;
        }

        try {
            $userId = Session::userId();
            $batchId = ProductionService::createLabelBatch([
                'program_id'         => $programId,
                'stitching_batch_id' => $stitchingBatchId,
                'batch_number'       => $batchNumber,
                'status'             => $status,
            ], $items, $userId);

            Session::flash('success', "Label batch '{$batchNumber}' created successfully with QR code tag generator.");
            Response::redirect(url('production/labels'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    public function editLabel(string $id): void {
        $batch = LabelBatch::find((int)$id);
        if (!$batch) {
            Response::error(404, 'Label batch not found.');
        }

        $items = LabelBatch::getItems((int)$id);
        $stitchingBatches = StitchingBatch::getAll();
        $rolls = !empty($batch['stitching_batch_id']) ? StitchingBatch::getRolls((int)$batch['stitching_batch_id']) : (!empty($batch['program_id']) ? ProductionProgram::getRolls((int)$batch['program_id']) : []);

        $hierarchyParts = array_filter([
            $batch['category_name'] ?? '',
            $batch['subcategory_name'] ?? '',
            $batch['sub_subcategory_name'] ?? ''
        ]);
        $catalogHierarchy = !empty($hierarchyParts) ? implode(' › ', $hierarchyParts) : ($batch['product_name'] ?? 'General Apparel');

        $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
            'name'               => $catalogHierarchy,
            'category_id'        => $batch['category_id'] ?? null,
            'subcategory_id'     => $batch['subcategory_id'] ?? null,
            'sub_subcategory_id' => $batch['sub_subcategory_id'] ?? null,
        ], 'required_qty');

        $catalogSequences = [];
        foreach ($distinctCatalogs as $cName => &$catData) {
            $lastSeq = LabelItem::getLastSequenceForCatalog(
                $catData['category_id'] ?? null,
                $catData['subcategory_id'] ?? null,
                $catData['sub_subcategory_id'] ?? null,
                $catData['name'] ?? null,
                (int)$id
            );
            $catData['last_sequence'] = $lastSeq;
            $catData['next_sequence'] = $lastSeq + 1;

            $cKey = ($catData['category_id'] ?? 0) . '_' . ($catData['subcategory_id'] ?? 0) . '_' . ($catData['sub_subcategory_id'] ?? 0);
            $seqInfo = [
                'last_sequence'      => $lastSeq,
                'next_sequence'      => $lastSeq + 1,
                'category_id'        => $catData['category_id'] ?? null,
                'subcategory_id'     => $catData['subcategory_id'] ?? null,
                'sub_subcategory_id' => $catData['sub_subcategory_id'] ?? null,
                'name'               => $catData['name'] ?? null,
            ];
            $catalogSequences[$cKey] = $seqInfo;
            if (!empty($catData['name'])) {
                $catalogSequences[$catData['name']] = $seqInfo;
            }
        }
        unset($catData);

        Response::view('production.labels.edit', [
            'pageTitle'        => 'Edit Label Batch — ' . $batch['batch_number'],
            'activeModule'     => 'production',
            'activeMenu'       => 'labels',
            'batch'            => $batch,
            'items'            => $items,
            'stitchingBatches' => $stitchingBatches,
            'catalogHierarchy' => $catalogHierarchy,
            'distinctCatalogs' => array_values($distinctCatalogs),
            'catalogSequences' => $catalogSequences,
            'breadcrumb'       => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Labels', 'url' => url('production/labels')],
                ['label' => $batch['batch_number']],
            ],
        ]);
    }

    public function updateLabel(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }
        $items = [];
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $vals) {
                $items[] = [
                    'variant_id'         => !empty($vals['variant_id']) ? (int)$vals['variant_id'] : null,
                    'category_id'        => !empty($vals['category_id']) ? (int)$vals['category_id'] : null,
                    'subcategory_id'     => !empty($vals['subcategory_id']) ? (int)$vals['subcategory_id'] : null,
                    'sub_subcategory_id' => !empty($vals['sub_subcategory_id']) ? (int)$vals['sub_subcategory_id'] : null,
                    'catalog_hierarchy'  => !empty($vals['catalog_hierarchy']) ? trim($vals['catalog_hierarchy']) : null,
                    'size_name'          => !empty($vals['size_name']) ? trim($vals['size_name']) : null,
                    'color_name'         => !empty($vals['color_name']) ? trim($vals['color_name']) : null,
                    'base_price'         => (float)($vals['base_price'] ?? 0.00),
                    'discount_price'     => (float)($vals['discount_price'] ?? 0.00),
                    'required_qty'       => (int)($vals['required_qty'] ?? 0),
                    'printed_qty'        => (int)($vals['printed_qty'] ?? 0),
                    'used_qty'           => (int)($vals['used_qty'] ?? 0),
                    'damaged_qty'        => (int)($vals['damaged_qty'] ?? 0),
                    'start_seq'          => !empty($vals['start_seq']) ? (int)$vals['start_seq'] : null,
                    'end_seq'            => !empty($vals['end_seq']) ? (int)$vals['end_seq'] : null,
                    'start_code'         => !empty($vals['start_code']) ? trim($vals['start_code']) : null,
                    'end_code'           => !empty($vals['end_code']) ? trim($vals['end_code']) : null,
                ];
            }
        }

        try {
            ProductionService::updateLabelBatch((int)$id, [
                'status' => $_POST['status'] ?? 'IN_PROGRESS',
            ], $items);

            Session::flash('success', 'Label batch updated.');
            Response::redirect(url('production/labels'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    public function deleteLabel(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $batchId = (int)$id;
        $batch = LabelBatch::find($batchId);
        if (!$batch) {
            Session::flash('error', 'Label batch not found.');
            Response::back();
            return;
        }

        try {
            ProductionService::deleteLabelBatch($batchId);
            Session::flash('success', 'Label Batch ' . ($batch['batch_number'] ?? ('#' . $batchId)) . ' deleted successfully.');
            Response::redirect(url('production/labels'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }




    // =========================================================================
    // PRODUCTION BATCHES & PACKING
    // =========================================================================

    public function batchList(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $warehouseId = trim($_GET['warehouse_id'] ?? $_GET['destination_warehouse_id'] ?? '');
        $filters = [
            'status'                   => trim($_GET['status'] ?? ''),
            'program_id'               => trim($_GET['program_id'] ?? ''),
            'production_unit_id'       => $scopedUnitId ? (string)$scopedUnitId : trim($_GET['production_unit_id'] ?? ''),
            'destination_warehouse_id' => $warehouseId,
            'warehouse_id'             => $warehouseId,
            'search'                   => trim($_GET['search'] ?? ''),
        ];

        $batches = ProductionBatch::getAll($filters);
        $warehouses = Database::fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name ASC");
        if (empty($warehouses)) {
            $warehouses = Database::fetchAll("SELECT * FROM warehouses ORDER BY name ASC");
        }

        Response::view('production.batches.index', [
            'pageTitle'    => 'Finished Production Batches',
            'activeModule' => 'production',
            'activeMenu'   => 'batches',
            'batches'      => $batches,
            'warehouses'   => $warehouses,
            'filters'      => $filters,
            'scopedUnitId' => $scopedUnitId,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Finished Batches'],
            ],
        ]);
    }

    public function createBatch(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $qcBatchId = $_GET['qc_batch_id'] ?? null;
        $unitClause = $scopedUnitId ? " AND pp.production_unit_id = $scopedUnitId" : "";
        
        $rawQcBatches = Database::fetchAll(
            "SELECT qb.*, pp.program_number, COALESCE(p.name, pp.name, 'General Apparel') as product_name,
                    COALESCE((SELECT SUM(passed_qty) FROM qc_items WHERE qc_batch_id = qb.id), qb.passed_qty, 0) as total_qc_passed,
                    COALESCE((
                        SELECT SUM(pbi.quantity)
                        FROM production_batch_items pbi
                        JOIN production_batches pb ON pbi.production_batch_id = pb.id
                        WHERE pb.qc_batch_id = qb.id AND pb.status != 'CANCELLED'
                    ), 0) as already_packed_qty
             FROM qc_batches qb
             JOIN production_programs pp ON qb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             WHERE (qb.passed_qty > 0 OR qb.status IN ('PASSED', 'PARTIAL', 'COMPLETED', 'REWORK'))$unitClause
             ORDER BY qb.created_at DESC"
        );

        $qcBatches = [];
        foreach ($rawQcBatches as $qb) {
            $passed = (int)($qb['total_qc_passed'] ?: $qb['passed_qty']);
            $packed = (int)$qb['already_packed_qty'];
            $remaining = max(0, $passed - $packed);
            
            $qb['total_qc_passed'] = $passed;
            $qb['already_packed_qty'] = $packed;
            $qb['remaining_qty'] = $remaining;

            if ($remaining > 0 || ($qcBatchId && (int)$qcBatchId === (int)$qb['id'])) {
                $qcBatches[] = $qb;
            }
        }

        $selectedQC = null;
        $items = [];
        if ($qcBatchId) {
            $selectedQC = QCBatch::find((int)$qcBatchId);
            if ($selectedQC) {
                $totalQCPassed = (int)(Database::fetchOne(
                    "SELECT COALESCE(SUM(passed_qty), 0) as total FROM qc_items WHERE qc_batch_id = ?",
                    [(int)$qcBatchId]
                )['total'] ?? 0);
                if ($totalQCPassed <= 0) {
                    $totalQCPassed = (int)($selectedQC['passed_qty'] ?? 0);
                }

                $alreadyPackedTotal = (int)(Database::fetchOne(
                    "SELECT COALESCE(SUM(pbi.quantity), 0) as total
                     FROM production_batch_items pbi
                     JOIN production_batches pb ON pbi.production_batch_id = pb.id
                     WHERE pb.qc_batch_id = ? AND pb.status != 'CANCELLED'",
                    [(int)$qcBatchId]
                )['total'] ?? 0);

                $selectedQC['total_qc_passed'] = $totalQCPassed;
                $selectedQC['already_packed_qty'] = $alreadyPackedTotal;
                $selectedQC['remaining_qty'] = max(0, $totalQCPassed - $alreadyPackedTotal);

                // Fetch already packed quantities mapped per variant
                $variantPackedMap = [];
                $packedRows = Database::fetchAll(
                    "SELECT pbi.variant_id, COALESCE(SUM(pbi.quantity), 0) as packed_qty
                     FROM production_batch_items pbi
                     JOIN production_batches pb ON pbi.production_batch_id = pb.id
                     WHERE pb.qc_batch_id = ? AND pb.status != 'CANCELLED'
                     GROUP BY pbi.variant_id",
                    [(int)$qcBatchId]
                );
                foreach ($packedRows as $pr) {
                    $variantPackedMap[(int)$pr['variant_id']] = (int)$pr['packed_qty'];
                }

                $items = QCBatch::getItems((int)$qcBatchId);
                $productId = ProductionService::findOrCreateProductForProgram((int)$selectedQC['program_id']);
                if ($productId > 0) {
                    foreach ($items as &$it) {
                        if (empty($it['variant_id'])) {
                            $vId = ProductionService::findOrCreateVariant(
                                $productId,
                                $it['size_name'] ?? $it['display_size'] ?? '',
                                $it['color_name'] ?? $it['display_color'] ?? '',
                                $it['sku'] ?? ''
                            );
                            if ($vId > 0) {
                                $it['variant_id'] = $vId;
                                Database::execute("UPDATE qc_items SET variant_id = ? WHERE id = ?", [$vId, $it['id']]);
                                if (!empty($it['base_price']) || !empty($it['discount_price'])) {
                                    Database::execute("UPDATE product_variants SET mrp = ?, selling_price = ? WHERE id = ?", [
                                        (float)($it['base_price'] ?? 0),
                                        (float)($it['discount_price'] ?? 0),
                                        $vId
                                    ]);
                                }
                            }
                        }
                        $vId = (int)($it['variant_id'] ?? 0);
                        $vPacked = $variantPackedMap[$vId] ?? 0;
                        $it['already_packed'] = $vPacked;
                        $it['remaining_qty'] = max(0, (int)$it['passed_qty'] - $vPacked);
                    }
                    unset($it);
                } else {
                    foreach ($items as &$it) {
                        $vId = (int)($it['variant_id'] ?? 0);
                        $vPacked = $variantPackedMap[$vId] ?? 0;
                        $it['already_packed'] = $vPacked;
                        $it['remaining_qty'] = max(0, (int)$it['passed_qty'] - $vPacked);
                    }
                    unset($it);
                }

                $availablePieceTags = ProductionService::getAvailableQCPieceTags((int)$qcBatchId);
                foreach ($items as &$it) {
                    $it['available_piece_tags'] = $availablePieceTags[(int)$it['id']] ?? [];
                }
                unset($it);

                $existingBatches = Database::fetchAll(
                    "SELECT pb.*, w.name as warehouse_name, w.code as warehouse_code
                     FROM production_batches pb 
                     LEFT JOIN warehouses w ON pb.destination_warehouse_id = w.id 
                     WHERE pb.qc_batch_id = ? AND pb.status != 'CANCELLED' 
                     ORDER BY pb.id DESC",
                    [(int)$qcBatchId]
                );

                // Ensure selectedQC is in $qcBatches list if not already present
                $inList = false;
                foreach ($qcBatches as $qb) {
                    if ((int)$qb['id'] === (int)$selectedQC['id']) {
                        $inList = true;
                        break;
                    }
                }
                if (!$inList) {
                    $qcBatches[] = array_merge($selectedQC, [
                        'total_qc_passed'   => $totalQCPassed,
                        'already_packed_qty' => $alreadyPackedTotal,
                        'remaining_qty'      => max(0, $totalQCPassed - $alreadyPackedTotal),
                    ]);
                }
            }
        }

        $warehouses = Database::fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name ASC");

        Response::view('production.batches.create', [
            'pageTitle'       => 'Pack Production Batch',
            'activeModule'    => 'production',
            'activeMenu'      => 'batches',
            'qcBatches'       => $qcBatches,
            'selectedQC'      => $selectedQC,
            'existingBatches' => $existingBatches ?? [],
            'items'           => $items,
            'warehouses'      => $warehouses,
            'breadcrumb'      => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Batches', 'url' => url('production/batches')],
                ['label' => 'Pack Batch'],
            ],
        ]);
    }

    public function storeBatch(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }
        $validator = Validator::make($_POST, [
            'destination_warehouse_id' => 'required|numeric',
            'qc_batch_id'              => 'required|numeric',
            'box_count'                => 'required|numeric',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::back();
            return;
        }

        $qcBatchId = (int)$_POST['qc_batch_id'];
        $qcBatch = QCBatch::find($qcBatchId);
        if (!$qcBatch) {
            Session::flash('error', 'Selected QC Inspection Batch does not exist.');
            Response::back();
            return;
        }

        $programId = (int)($qcBatch['program_id'] ?? ($_POST['program_id'] ?? 0));
        $productId = ProductionService::findOrCreateProductForProgram($programId);

        $items = [];
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $key => $vals) {
                $qty = (int)($vals['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $variantId = 0;
                if (!empty($vals['variant_id']) && is_numeric($vals['variant_id'])) {
                    $variantId = (int)$vals['variant_id'];
                } elseif (is_numeric($key) && (int)$key > 0 && empty($vals['size_name'])) {
                    $variantId = (int)$key;
                }

                // Verify variant exists in product_variants
                if ($variantId > 0) {
                    $exists = Database::fetchOne("SELECT id FROM product_variants WHERE id = ?", [$variantId]);
                    if (!$exists) {
                        $variantId = 0;
                    }
                }

                // Fallback find or create variant
                if ($variantId <= 0 && $productId > 0) {
                    $sizeName = $vals['size_name'] ?? '';
                    $colorName = $vals['color_name'] ?? '';
                    $sku = $vals['sku'] ?? '';
                    $variantId = ProductionService::findOrCreateVariant($productId, $sizeName, $colorName, $sku);

                    if (!empty($vals['qc_item_id']) && $variantId > 0) {
                        Database::execute("UPDATE qc_items SET variant_id = ? WHERE id = ?", [$variantId, (int)$vals['qc_item_id']]);
                    }
                }

                if ($variantId > 0) {
                    $items[] = [
                        'variant_id' => $variantId,
                        'quantity'   => $qty,
                        'qc_item_id' => (int)($vals['qc_item_id'] ?? 0),
                        'piece_tags' => isset($vals['piece_tags']) && is_array($vals['piece_tags']) ? $vals['piece_tags'] : [],
                    ];
                    
                    if (!empty($vals['qc_item_id'])) {
                        $qcItem = Database::fetchOne("SELECT base_price, discount_price FROM qc_items WHERE id = ?", [(int)$vals['qc_item_id']]);
                        if ($qcItem && ($qcItem['base_price'] > 0 || $qcItem['discount_price'] > 0)) {
                            Database::execute("UPDATE product_variants SET mrp = ?, selling_price = ? WHERE id = ?", [
                                (float)$qcItem['base_price'],
                                (float)$qcItem['discount_price'],
                                $variantId
                            ]);
                        }
                    }
                }
            }
        }

        if (empty($items)) {
            Session::flash('error', 'Please pack at least 1 variant item with quantity greater than 0.');
            Response::back();
            return;
        }

        try {
            $user = Session::get('user');
            $userId = !empty($user['id']) ? (int)$user['id'] : (int)(Database::fetchOne("SELECT id FROM users LIMIT 1")['id'] ?? 2);
            $batchId = ProductionService::createProductionBatch([
                'program_id'               => $programId,
                'qc_batch_id'              => !empty($_POST['qc_batch_id']) ? (int)$_POST['qc_batch_id'] : null,
                'destination_warehouse_id' => (int)$_POST['destination_warehouse_id'],
                'box_count'                => (int)($_POST['box_count'] ?? 1),
                'status'                   => 'PREPARING',
            ], $items, $userId);

            Session::flash('success', 'Production batch packed and created successfully.');
            Response::redirect(url('production/batches'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    public function approveBatch(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }
        try {
            $user = Session::get('user');
            $userId = !empty($user['id']) ? (int)$user['id'] : (int)(Database::fetchOne("SELECT id FROM users LIMIT 1")['id'] ?? 2);
            ProductionService::approveProductionBatch((int)$id, $userId);
            Session::flash('success', 'Production batch approved for outward dispatch.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::back();
    }

    public function printBatchQRLabel(string $id): void {
        $batchId = (int)$id;
        $batch = ProductionBatch::find($batchId);
        if (!$batch) {
            Response::error(404, 'Production batch not found.');
            return;
        }
        $items = ProductionBatch::getItems($batchId);
        Response::viewOnly('warehouse.inward.label', [
            'batch' => $batch,
            'items' => $items,
        ]);
    }

    // =========================================================================
    // OUTWARD DISPATCH
    // =========================================================================

    public function outwardList(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $search = $_GET['search'] ?? '';
        $outwards = ProductionOutward::getAll([
            'search'             => $search,
            'production_unit_id' => $scopedUnitId,
        ]);

        $unitClause = $scopedUnitId ? " AND pp.production_unit_id = $scopedUnitId" : "";
        $approvedBatches = Database::fetchAll(
            "SELECT pb.*, pp.program_number, COALESCE(p.name, pp.name, 'General Apparel') as product_name, w.name as warehouse_name
             FROM production_batches pb
             JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             JOIN warehouses w ON pb.destination_warehouse_id = w.id
             WHERE pb.status = 'APPROVED'
               AND pb.id NOT IN (SELECT production_batch_id FROM production_outwards WHERE production_batch_id IS NOT NULL)$unitClause
             ORDER BY pb.created_at DESC"
        );

        Response::view('production.outward.index', [
            'pageTitle'       => 'Production Outward & Dispatch',
            'activeModule'    => 'production',
            'activeMenu'      => 'outward',
            'outwards'        => $outwards,
            'approvedBatches' => $approvedBatches,
            'search'          => $search,
            'scopedUnitId'    => $scopedUnitId,
            'breadcrumb'      => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Outward Dispatch'],
            ],
        ]);
    }

    public function storeOutward(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }
        $validator = Validator::make($_POST, [
            'production_batch_id' => 'required|numeric',
            'vehicle_number'      => 'required',
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Response::back();
            return;
        }

        $batchId = (int)$_POST['production_batch_id'];
        $existingOutward = Database::fetchOne(
            "SELECT id, outward_number FROM production_outwards WHERE production_batch_id = ?",
            [$batchId]
        );
        if ($existingOutward) {
            $pbInfo = ProductionBatch::find($batchId);
            $pbNum = $pbInfo['batch_number'] ?? ('#' . $batchId);
            Session::flash('error', "Validation Error: Finished Batch ({$pbNum}) has already been outward dispatched under Outward #{$existingOutward['outward_number']}. Duplicate outward dispatch is not allowed.");
            Response::back();
            return;
        }

        try {
            $userId = Session::userId();
            $outwardId = ProductionService::createOutward([
                'production_batch_id' => $batchId,
                'dispatch_date'       => !empty($_POST['dispatch_date']) ? $_POST['dispatch_date'] : date('Y-m-d H:i:s'),
                'vehicle_number'      => trim($_POST['vehicle_number']),
                'notes'               => trim($_POST['notes'] ?? ''),
            ], $userId);

            Session::flash('success', 'Production batch dispatched to warehouse successfully.');
            Response::redirect(url('production/outward'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    public function dispatchOutward(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }
        try {
            $userId = Session::userId();
            ProductionService::dispatchOutward((int)$id, $userId);
            Session::flash('success', 'Outward dispatched.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::back();
    }

    public function batchDetailsAjax(string $id): void {
        $batchId = (int)$id;
        $batch = ProductionBatch::find($batchId);
        if (!$batch) {
            Response::json(['success' => false, 'message' => 'Production batch not found.'], 404);
            return;
        }
        $items = ProductionBatch::getItems($batchId);
        $outward = ProductionOutward::findByBatchId($batchId);

        Response::json([
            'success' => true,
            'batch'   => $batch,
            'items'   => $items,
            'outward' => $outward,
        ]);
    }

    public function outwardDetailsAjax(string $id): void {
        $outwardId = (int)$id;
        $outward = ProductionOutward::find($outwardId);
        if (!$outward) {
            Response::json(['success' => false, 'message' => 'Outward record not found.'], 404);
            return;
        }
        $batch = !empty($outward['production_batch_id']) ? ProductionBatch::find((int)$outward['production_batch_id']) : null;
        $items = !empty($outward['production_batch_id']) ? ProductionBatch::getItems((int)$outward['production_batch_id']) : [];

        Response::json([
            'success' => true,
            'outward' => $outward,
            'batch'   => $batch,
            'items'   => $items,
        ]);
    }

    // =========================================================================
    // REWORK FROM WAREHOUSE MODULE
    // =========================================================================

    public function warehouseReworkList(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $warehouseId = !empty($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : null;
        $defectType = trim($_GET['defect_type'] ?? '');

        $filters = array_filter([
            'search'             => $search,
            'status'             => $status,
            'warehouse_id'       => $warehouseId,
            'defect_type'        => $defectType,
            'production_unit_id' => $scopedUnitId,
        ]);

        $reworks = ProductionWarehouseRework::getAll($filters);
        $stats = ProductionWarehouseRework::getStats(['production_unit_id' => $scopedUnitId]);

        $warehouses = Database::fetchAll("SELECT id, name, code FROM warehouses ORDER BY name ASC");
        
        $unitClause = $scopedUnitId ? " AND pp.production_unit_id = $scopedUnitId" : "";
        $dispatchedBatches = Database::fetchAll(
            "SELECT pb.id, pb.batch_number, pb.total_qty, pb.destination_warehouse_id as warehouse_id,
                    w.name as warehouse_name, COALESCE(p.name, pp.name, 'General Garment') as product_name,
                    p.id as product_id, po.id as outward_id, po.outward_number, po.dispatch_date
             FROM production_batches pb
             JOIN production_programs pp ON pb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             JOIN warehouses w ON pb.destination_warehouse_id = w.id
             LEFT JOIN production_outwards po ON po.production_batch_id = pb.id
             WHERE pb.status IN ('DISPATCHED', 'RECEIVED', 'APPROVED') $unitClause
             ORDER BY pb.id DESC"
        );

        $productionUnits = Database::fetchAll("SELECT id, name, code FROM production_units WHERE is_active = 1 ORDER BY name ASC");

        $allVariants = Database::fetchAll(
            "SELECT pv.id, pv.sku, p.name as product_name, s.name as size_name, c.name as color_name
             FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE pv.is_active = 1
             ORDER BY p.name ASC, pv.sku ASC LIMIT 500"
        );

        Response::view('production.rework.index', [
            'pageTitle'         => 'Rework from Warehouse',
            'activeModule'      => 'production',
            'activeMenu'        => 'warehouse-rework',
            'reworks'           => $reworks,
            'stats'             => $stats,
            'warehouses'        => $warehouses,
            'dispatchedBatches' => $dispatchedBatches,
            'productionUnits'   => $productionUnits,
            'allVariants'       => $allVariants,
            'filters'           => [
                'search'       => $search,
                'status'       => $status,
                'warehouse_id' => $warehouseId,
                'defect_type'  => $defectType,
            ],
            'scopedUnitId'      => $scopedUnitId,
            'breadcrumb'        => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Rework from Warehouse'],
            ],
        ]);
    }

    public function lookupBatchVariantsAjax(string $batchId): void {
        $bId = (int)$batchId;
        $items = Database::fetchAll(
            "SELECT pbi.variant_id, pbi.quantity, pv.sku, p.name as product_name, p.id as product_id,
                    s.name as size_name, c.name as color_name
             FROM production_batch_items pbi
             JOIN product_variants pv ON pbi.variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             LEFT JOIN sizes s ON pv.size_id = s.id
             LEFT JOIN colors c ON pv.color_id = c.id
             WHERE pbi.production_batch_id = ?",
            [$bId]
        );

        if (empty($items)) {
            // Fallback from program items
            $batch = Database::fetchOne("SELECT program_id FROM production_batches WHERE id = ?", [$bId]);
            if ($batch && !empty($batch['program_id'])) {
                $items = Database::fetchAll(
                    "SELECT ppi.variant_id, ppi.planned_quantity as quantity, pv.sku, p.name as product_name, p.id as product_id,
                            s.name as size_name, c.name as color_name
                     FROM production_program_items ppi
                     JOIN product_variants pv ON ppi.variant_id = pv.id
                     JOIN products p ON pv.product_id = p.id
                     LEFT JOIN sizes s ON pv.size_id = s.id
                     LEFT JOIN colors c ON pv.color_id = c.id
                     WHERE ppi.program_id = ?",
                    [(int)$batch['program_id']]
                );
            }
        }

        Response::json([
            'success'  => true,
            'variants' => $items,
        ]);
    }

    public function storeWarehouseRework(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $warehouseId = (int)($_POST['warehouse_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $damageReason = trim($_POST['damage_reason'] ?? '');
        $defectType = trim($_POST['defect_type'] ?? 'STITCHING_ERROR');

        if ($warehouseId <= 0 || $variantId <= 0 || empty($damageReason)) {
            Session::flash('error', 'Please select a warehouse, product variant, and provide the damage reason.');
            Response::back();
        }

        $batchId = !empty($_POST['production_batch_id']) ? (int)$_POST['production_batch_id'] : null;
        $outwardId = !empty($_POST['production_outward_id']) ? (int)$_POST['production_outward_id'] : null;

        if ($batchId && !$outwardId) {
            $po = Database::fetchOne("SELECT id FROM production_outwards WHERE production_batch_id = ?", [$batchId]);
            if ($po) $outwardId = (int)$po['id'];
        }

        $photoUrl = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts, true)) {
                if (class_exists('CloudinaryService')) {
                    $photoUrl = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/production/rework', 'image');
                }
            }
        }

        try {
            $userId = Session::userId() ?? 1;
            $reworkId = ProductionWarehouseRework::create([
                'warehouse_id'          => $warehouseId,
                'production_batch_id'   => $batchId,
                'production_outward_id' => $outwardId,
                'variant_id'            => $variantId,
                'quantity'              => $quantity,
                'defect_type'           => $defectType,
                'damage_reason'         => $damageReason,
                'defect_notes'          => trim($_POST['defect_notes'] ?? ''),
                'challan_number'        => trim($_POST['challan_number'] ?? ''),
                'received_date'         => !empty($_POST['received_date']) ? $_POST['received_date'] : date('Y-m-d'),
                'received_by'           => $userId,
                'assigned_to'           => trim($_POST['assigned_to'] ?? ''),
                'production_unit_id'    => !empty($_POST['production_unit_id']) ? (int)$_POST['production_unit_id'] : null,
                'photo_url'             => $photoUrl,
                'status'                => !empty($_POST['start_immediately']) && hasPermission('warehouse_rework', 'edit') ? 'IN_REWORK' : 'RECEIVED',
                'created_by'            => $userId,
            ]);

            Session::flash('success', "Damaged goods return successfully logged for rework (Ref: #{$reworkId}).");
        } catch (Exception $e) {
            Session::flash('error', 'Error logging rework: ' . $e->getMessage());
        }

        Response::redirect(url('production/warehouse-rework'));
    }

    public function updateWarehouseReworkStatus(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
        }

        $reworkId = (int)$id;
        $rework = ProductionWarehouseRework::find($reworkId);
        if (!$rework) {
            Session::flash('error', 'Rework record not found.');
            Response::back();
        }

        $newStatus = trim($_POST['status'] ?? '');
        $assignedTo = trim($_POST['assigned_to'] ?? ($rework['assigned_to'] ?? ''));
        $unitId = !empty($_POST['production_unit_id']) ? (int)$_POST['production_unit_id'] : ($rework['production_unit_id'] ?? null);
        $notes = trim($_POST['repair_notes'] ?? ($rework['repair_notes'] ?? ''));

        $updateData = [
            'status'             => $newStatus,
            'assigned_to'        => $assignedTo,
            'production_unit_id' => $unitId,
            'repair_notes'       => $notes,
        ];

        ProductionWarehouseRework::update($reworkId, $updateData);
        Session::flash('success', "Rework #{$rework['rework_number']} updated to {$newStatus}.");
        Response::redirect(url('production/warehouse-rework'));
    }

    public function completeWarehouseRework(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
        }

        $reworkId = (int)$id;
        $rework = ProductionWarehouseRework::find($reworkId);
        if (!$rework) {
            Session::flash('error', 'Rework record not found.');
            Response::back();
        }

        $totalQty = (int)$rework['quantity'];
        $repairedQty = max(0, (int)($_POST['repaired_qty'] ?? 0));
        $scrappedQty = max(0, (int)($_POST['scrapped_qty'] ?? 0));
        $repairNotes = trim($_POST['repair_notes'] ?? '');

        if (($repairedQty + $scrappedQty) <= 0) {
            Session::flash('error', 'Please enter a valid repaired or scrapped quantity.');
            Response::back();
        }

        if (($repairedQty + $scrappedQty) > $totalQty) {
            Session::flash('error', "Total repaired ({$repairedQty}) and scrapped ({$scrappedQty}) cannot exceed returned quantity ({$totalQty}).");
            Response::back();
        }

        $finalStatus = ($repairedQty > 0) ? 'REPAIRED' : 'SCRAPPED';

        ProductionWarehouseRework::update($reworkId, [
            'repaired_qty' => $repairedQty,
            'scrapped_qty' => $scrappedQty,
            'status'       => $finalStatus,
            'repair_notes' => $repairNotes,
            'repaired_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::flash('success', "Rework #{$rework['rework_number']} completed: {$repairedQty} pcs repaired successfully, {$scrappedQty} pcs scrapped.");
        Response::redirect(url('production/warehouse-rework'));
    }

    public function redispatchWarehouseRework(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
        }

        $reworkId = (int)$id;
        $rework = ProductionWarehouseRework::find($reworkId);
        if (!$rework) {
            Session::flash('error', 'Rework record not found.');
            Response::back();
        }

        $vehicle = trim($_POST['redispatched_vehicle'] ?? 'Direct Courier / Handover');
        $notes = trim($_POST['redispatched_notes'] ?? '');

        ProductionWarehouseRework::update($reworkId, [
            'status'               => 'RE_DISPATCHED',
            'redispatched_date'    => date('Y-m-d H:i:s'),
            'redispatched_vehicle' => $vehicle,
            'redispatched_notes'   => $notes,
        ]);

        Session::flash('success', "Repaired items for Rework #{$rework['rework_number']} re-dispatched to {$rework['warehouse_name']}.");
        Response::redirect(url('production/warehouse-rework'));
    }

    public function viewWarehouseReworkAjax(string $id): void {
        $rework = ProductionWarehouseRework::find((int)$id);
        if (!$rework) {
            Response::json(['success' => false, 'message' => 'Rework record not found.'], 404);
            return;
        }

        Response::json([
            'success' => true,
            'data'    => $rework,
        ]);
    }

    public function deleteWarehouseRework(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::back();
        }

        ProductionWarehouseRework::delete((int)$id);
        Session::flash('success', 'Rework record deleted successfully.');
        Response::redirect(url('production/warehouse-rework'));
    }

    // =========================================================================
    // QUALITY CONTROL (QC) INSPECTION MODULE
    // =========================================================================

    public function qcList(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $batches = QCBatch::getAll([
            'status'             => $status,
            'search'             => $search,
            'production_unit_id' => $scopedUnitId,
        ]);

        Response::view('production.qc.index', [
            'pageTitle'    => 'Quality Control (QC) Inspections',
            'activeModule' => 'production',
            'activeMenu'   => 'qc',
            'batches'      => $batches,
            'filters'      => ['status' => $status, 'search' => $search],
            'scopedUnitId' => $scopedUnitId,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'QC Inspections'],
            ],
        ]);
    }

    public function createQC(): void {
        $scopedUnitId = function_exists('currentProductionUnitId') ? currentProductionUnitId() : null;
        $stitchingBatchId = isset($_GET['stitching_batch_id']) ? (int)$_GET['stitching_batch_id'] : null;
        $labelBatchId = isset($_GET['label_batch_id']) ? (int)$_GET['label_batch_id'] : null;

        $unitClause = $scopedUnitId ? " AND pp.production_unit_id = $scopedUnitId" : "";
        $stitchingBatches = Database::fetchAll(
            "SELECT sb.*, pp.program_number, COALESCE(p.name, pp.name, 'General Apparel') as product_name 
             FROM stitching_batches sb
             JOIN production_programs pp ON sb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             WHERE 1=1$unitClause
               AND sb.id NOT IN (SELECT stitching_batch_id FROM qc_batches WHERE stitching_batch_id IS NOT NULL)
               AND (sb.program_id IS NULL OR sb.program_id NOT IN (SELECT program_id FROM qc_batches WHERE program_id IS NOT NULL))
             ORDER BY sb.created_at DESC"
        );

        $labelBatches = Database::fetchAll(
            "SELECT lb.*, pp.program_number, COALESCE(p.name, pp.name, 'General Apparel') as product_name
             FROM label_batches lb
             JOIN production_programs pp ON lb.program_id = pp.id
             LEFT JOIN products p ON pp.product_id = p.id
             WHERE 1=1$unitClause
               AND lb.id NOT IN (SELECT label_batch_id FROM qc_batches WHERE label_batch_id IS NOT NULL)
               AND (lb.program_id IS NULL OR lb.program_id NOT IN (SELECT program_id FROM qc_batches WHERE program_id IS NOT NULL))
             ORDER BY lb.created_at DESC"
        );

        $users = Database::fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");

        $selectedStitching = null;
        $selectedLabel = null;
        $items = [];
        $distinctCatalogs = [];
        $catalogHierarchy = 'General Apparel';
        $rolls = [];

        if ($stitchingBatchId) {
            $alreadyQC = Database::fetchOne(
                "SELECT id FROM qc_batches WHERE stitching_batch_id = ?",
                [$stitchingBatchId]
            );
            if ($alreadyQC) {
                Session::flash('error', "Stitching Batch has already undergone QC Inspection (QC #{$alreadyQC['id']}). Duplicate QC inspection is not allowed.");
                Response::redirect(url('production/qc'));
                return;
            }
            $selectedStitching = StitchingBatch::find($stitchingBatchId);
            if ($selectedStitching) {
                $stitchingItems = StitchingBatch::getItems($stitchingBatchId);
                $rolls = StitchingBatch::getRolls($stitchingBatchId);

                $linkedLabelBatch = Database::fetchOne(
                    "SELECT id FROM label_batches WHERE stitching_batch_id = ? ORDER BY id DESC LIMIT 1",
                    [$stitchingBatchId]
                );
                $labelItemsMap = [];
                if ($linkedLabelBatch) {
                    $lItems = LabelBatch::getItems((int)$linkedLabelBatch['id']);
                    foreach ($lItems as $li) {
                        $k = strtolower(trim($li['size_name'] ?? '')) . '_' . strtolower(trim($li['color_name'] ?? ''));
                        $labelItemsMap[$k] = $li;
                    }
                }

                foreach ($stitchingItems as $si) {
                    $k = strtolower(trim($si['display_size'] ?? $si['size_name'] ?? '')) . '_' . strtolower(trim($si['display_color'] ?? $si['color_name'] ?? ''));
                    $matchedLabel = $labelItemsMap[$k] ?? null;

                    $items[] = [
                        'variant_id'         => !empty($si['variant_id']) ? $si['variant_id'] : null,
                        'category_id'        => !empty($si['category_id']) ? (int)$si['category_id'] : null,
                        'subcategory_id'     => !empty($si['subcategory_id']) ? (int)$si['subcategory_id'] : null,
                        'sub_subcategory_id' => !empty($si['sub_subcategory_id']) ? (int)$si['sub_subcategory_id'] : null,
                        'catalog_hierarchy'  => $si['catalog_hierarchy'] ?? null,
                        'category_name'      => $si['category_name'] ?? null,
                        'subcategory_name'   => $si['subcategory_name'] ?? null,
                        'sub_subcategory_name'=> $si['sub_subcategory_name'] ?? null,
                        'size_name'          => $si['display_size'] ?? ($si['size_name'] ?? 'Standard'),
                        'color_name'         => $si['display_color'] ?? ($si['color_name'] ?? 'Assorted'),
                        'base_price'         => (float)($si['base_price'] ?? 1000.00),
                        'discount_price'     => (float)($si['discount_price'] ?? 500.00),
                        'stitched_qty'       => (int)($si['stitched_qty'] ?? 0),
                        'sku'                => $si['sku'] ?? (($si['size_name'] ?? '') . '-' . ($si['color_name'] ?? '')),
                        'start_seq'          => $matchedLabel['start_seq'] ?? null,
                        'end_seq'            => $matchedLabel['end_seq'] ?? null,
                        'start_code'         => $matchedLabel['start_code'] ?? null,
                        'end_code'           => $matchedLabel['end_code'] ?? null,
                    ];
                }
                $hierarchyParts = array_filter([
                    $selectedStitching['category_name'] ?? '',
                    $selectedStitching['subcategory_name'] ?? '',
                    $selectedStitching['sub_subcategory_name'] ?? ''
                ]);
                $catalogHierarchy = !empty($hierarchyParts) ? implode(' › ', $hierarchyParts) : ($selectedStitching['product_name'] ?? 'General Apparel');

                $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
                    'name'               => $catalogHierarchy,
                    'category_id'        => $selectedStitching['category_id'] ?? null,
                    'subcategory_id'     => $selectedStitching['subcategory_id'] ?? null,
                    'sub_subcategory_id' => $selectedStitching['sub_subcategory_id'] ?? null,
                ], 'stitched_qty');
            }
        } elseif ($labelBatchId) {
            $alreadyQC = Database::fetchOne(
                "SELECT id FROM qc_batches WHERE label_batch_id = ?",
                [$labelBatchId]
            );
            if ($alreadyQC) {
                Session::flash('error', "Label Batch has already undergone QC Inspection (QC #{$alreadyQC['id']}). Duplicate QC inspection is not allowed.");
                Response::redirect(url('production/qc'));
                return;
            }
            $selectedLabel = LabelBatch::find($labelBatchId);
            if ($selectedLabel) {
                $labelItems = LabelBatch::getItems($labelBatchId);
                $rolls = !empty($selectedLabel['stitching_batch_id']) ? StitchingBatch::getRolls((int)$selectedLabel['stitching_batch_id']) : (!empty($selectedLabel['program_id']) ? ProductionProgram::getRolls((int)$selectedLabel['program_id']) : []);
                foreach ($labelItems as $li) {
                    $items[] = [
                        'variant_id'         => !empty($li['variant_id']) ? $li['variant_id'] : null,
                        'category_id'        => !empty($li['category_id']) ? (int)$li['category_id'] : null,
                        'subcategory_id'     => !empty($li['subcategory_id']) ? (int)$li['subcategory_id'] : null,
                        'sub_subcategory_id' => !empty($li['sub_subcategory_id']) ? (int)$li['sub_subcategory_id'] : null,
                        'catalog_hierarchy'  => $li['catalog_hierarchy'] ?? null,
                        'category_name'      => $li['category_name'] ?? null,
                        'subcategory_name'   => $li['subcategory_name'] ?? null,
                        'sub_subcategory_name'=> $li['sub_subcategory_name'] ?? null,
                        'size_name'          => $li['display_size'] ?? ($li['size_name'] ?? 'Standard'),
                        'color_name'         => $li['display_color'] ?? ($li['color_name'] ?? 'Assorted'),
                        'base_price'         => (float)($li['base_price'] ?? 1000.00),
                        'discount_price'     => (float)($li['discount_price'] ?? 500.00),
                        'stitched_qty'       => (int)($li['required_qty'] ?? 0),
                        'sku'                => $li['sku'] ?? (($li['size_name'] ?? '') . '-' . ($li['color_name'] ?? '')),
                        'start_seq'          => $li['start_seq'] ?? null,
                        'end_seq'            => $li['end_seq'] ?? null,
                        'start_code'         => $li['start_code'] ?? null,
                        'end_code'           => $li['end_code'] ?? null,
                    ];
                }
                $hierarchyParts = array_filter([
                    $selectedLabel['category_name'] ?? '',
                    $selectedLabel['subcategory_name'] ?? '',
                    $selectedLabel['sub_subcategory_name'] ?? ''
                ]);
                $catalogHierarchy = !empty($hierarchyParts) ? implode(' › ', $hierarchyParts) : ($selectedLabel['product_name'] ?? 'General Apparel');

                $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
                    'name'               => $catalogHierarchy,
                    'category_id'        => $selectedLabel['category_id'] ?? null,
                    'subcategory_id'     => $selectedLabel['subcategory_id'] ?? null,
                    'sub_subcategory_id' => $selectedLabel['sub_subcategory_id'] ?? null,
                ], 'stitched_qty');
            }
        }

        Response::view('production.qc.create', [
            'pageTitle'         => 'Initialize QC Inspection Batch',
            'activeModule'      => 'production',
            'activeMenu'        => 'qc',
            'stitchingBatches'  => $stitchingBatches,
            'labelBatches'      => $labelBatches,
            'selectedStitching' => $selectedStitching,
            'selectedLabel'     => $selectedLabel,
            'users'             => $users,
            'items'             => $items,
            'catalogHierarchy'  => $catalogHierarchy,
            'distinctCatalogs'  => array_values($distinctCatalogs),
            'breadcrumb'        => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'QC Inspections', 'url' => url('production/qc')],
                ['label' => 'New QC Inspection Batch'],
            ],
        ]);
    }

    public function storeQC(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $stitchingBatchId = !empty($_POST['stitching_batch_id']) ? (int)$_POST['stitching_batch_id'] : null;
        $labelBatchId = !empty($_POST['label_batch_id']) ? (int)$_POST['label_batch_id'] : null;
        $inspectorId = !empty($_POST['inspector_id']) ? (int)$_POST['inspector_id'] : null;
        if (!$inspectorId) {
            $user = Session::get('user');
            $inspectorId = !empty($user['id']) ? (int)$user['id'] : 1;
        }
        $notes = trim($_POST['notes'] ?? '');

        $programId = null;

        if ($stitchingBatchId) {
            $sb = StitchingBatch::find($stitchingBatchId);
            if ($sb) $programId = $sb['program_id'];
        } elseif ($labelBatchId) {
            $lb = LabelBatch::find($labelBatchId);
            if ($lb) $programId = $lb['program_id'];
        }

        if (!$programId) {
            Session::flash('error', 'Please select a valid Stitching or Label Batch.');
            Response::back();
        }

        // Prevent duplicate QC inspection
        if (!empty($stitchingBatchId)) {
            $existingQC = Database::fetchOne(
                "SELECT id FROM qc_batches WHERE stitching_batch_id = ?",
                [$stitchingBatchId]
            );
            if ($existingQC) {
                $sb = StitchingBatch::find($stitchingBatchId);
                $sbNum = $sb['batch_number'] ?? ('#' . $stitchingBatchId);
                Session::flash('error', "Validation Error: Stitching Batch ({$sbNum}) has already undergone QC Inspection (QC #{$existingQC['id']}). Duplicate QC inspection is not allowed.");
                Response::back();
                return;
            }
        }

        if (!empty($labelBatchId)) {
            $existingQC = Database::fetchOne(
                "SELECT id FROM qc_batches WHERE label_batch_id = ?",
                [$labelBatchId]
            );
            if ($existingQC) {
                $lb = LabelBatch::find($labelBatchId);
                $lbNum = $lb['batch_number'] ?? ('#' . $labelBatchId);
                Session::flash('error', "Validation Error: Label Batch ({$lbNum}) has already undergone QC Inspection (QC #{$existingQC['id']}). Duplicate QC inspection is not allowed.");
                Response::back();
                return;
            }
        }

        if (!empty($programId)) {
            $existingQCProg = Database::fetchOne(
                "SELECT id FROM qc_batches WHERE program_id = ?",
                [$programId]
            );
            if ($existingQCProg) {
                $prog = ProductionProgram::find($programId);
                $progNum = $prog['program_number'] ?? ('#' . $programId);
                Session::flash('error', "Validation Error: Production Program ({$progNum}) has already undergone QC Inspection (QC #{$existingQCProg['id']}). Duplicate QC inspection is not allowed.");
                Response::back();
                return;
            }
        }

        try {
            $postedItems = $_POST['items'] ?? [];
            if (empty($postedItems)) {
                Session::flash('error', 'No items provided for QC inspection.');
                Response::back();
            }

            $totalQty = 0;
            foreach ($postedItems as $it) {
                $totalQty += (int)($it['inspected_qty'] ?? $it['stitched_qty'] ?? 0);
            }

            $qcBatchId = QCBatch::create([
                'program_id'         => $programId,
                'stitching_batch_id' => $stitchingBatchId,
                'label_batch_id'     => $labelBatchId,
                'inspector_id'       => $inspectorId,
                'total_qty'          => $totalQty,
                'passed_qty'         => 0,
                'rejected_qty'       => 0,
                'rework_qty'         => 0,
                'status'             => 'PENDING',
                'notes'              => $notes,
            ]);

            foreach ($postedItems as $idx => $it) {
                $inspected = (int)($it['inspected_qty'] ?? $it['stitched_qty'] ?? 0);
                $passed = isset($it['passed_qty']) ? (int)$it['passed_qty'] : 0;
                $rejected = (int)($it['rejected_qty'] ?? 0);
                $rework = (int)($it['rework_qty'] ?? 0);

                QCItem::create([
                    'qc_batch_id'        => $qcBatchId,
                    'variant_id'         => !empty($it['variant_id']) ? (int)$it['variant_id'] : null,
                    'category_id'        => !empty($it['category_id']) ? (int)$it['category_id'] : null,
                    'subcategory_id'     => !empty($it['subcategory_id']) ? (int)$it['subcategory_id'] : null,
                    'sub_subcategory_id' => !empty($it['sub_subcategory_id']) ? (int)$it['sub_subcategory_id'] : null,
                    'catalog_hierarchy'  => !empty($it['catalog_hierarchy']) ? trim($it['catalog_hierarchy']) : null,
                    'size_name'          => $it['size_name'] ?? 'Standard',
                    'color_name'         => $it['color_name'] ?? 'Assorted',
                    'tag_code'           => !empty($it['start_code']) ? $it['start_code'] : ($it['tag_code'] ?? null),
                    'base_price'         => (float)($it['base_price'] ?? 1000.00),
                    'discount_price'     => (float)($it['discount_price'] ?? 500.00),
                    'inspected_qty'      => $inspected,
                    'passed_qty'         => $passed,
                    'rejected_qty'       => $rejected,
                    'rework_qty'         => $rework,
                    'defect_reason'      => $it['defect_reason'] ?? null,
                    'defect_notes'       => $it['defect_notes'] ?? null,
                ]);
            }

            QCBatch::recalculateTotals($qcBatchId);

            Session::flash('success', 'QC Inspection batch created successfully.');
            Response::redirect(url('production/qc/inspect/' . $qcBatchId));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    public function deleteQC(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $batchId = (int)$id;
        $batch = QCBatch::find($batchId);
        if (!$batch) {
            Session::flash('error', 'QC Inspection batch not found.');
            Response::back();
            return;
        }

        try {
            QCBatch::delete($batchId);
            Session::flash('success', 'QC Inspection Batch #' . $batchId . ' deleted successfully.');
            Response::redirect(url('production/qc'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    public function inspectQC(string $id): void {
        $batchId = (int)$id;
        $batch = QCBatch::find($batchId);

        if (!$batch) {
            Response::error(404, 'QC Inspection Batch not found.');
            return;
        }

        $items = QCBatch::getItems($batchId);
        $lockedPieces = $this->getQCLockedPieceStates($batch, $items);
        $users = Database::fetchAll("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name ASC");

        $rolls = !empty($batch['stitching_batch_id']) ? StitchingBatch::getRolls((int)$batch['stitching_batch_id']) : (!empty($batch['program_id']) ? ProductionProgram::getRolls((int)$batch['program_id']) : []);

        $hierarchyParts = array_filter([
            $batch['category_name'] ?? '',
            $batch['subcategory_name'] ?? '',
            $batch['sub_subcategory_name'] ?? ''
        ]);
        $catalogHierarchy = !empty($hierarchyParts) ? implode(' › ', $hierarchyParts) : ($batch['product_name'] ?? 'General Apparel');

        $distinctCatalogs = $this->resolveItemsCatalogHierarchy($items, $rolls, [
            'name'               => $catalogHierarchy,
            'category_id'        => $batch['program_category_id'] ?? null,
            'subcategory_id'     => $batch['program_subcategory_id'] ?? null,
            'sub_subcategory_id' => $batch['program_sub_subcategory_id'] ?? null,
        ], 'inspected_qty');

        Response::view('production.qc.inspect', [
            'pageTitle'        => 'QC Inspection Workstation — Batch #' . $batchId,
            'activeModule'     => 'production',
            'activeMenu'       => 'qc',
            'batch'            => $batch,
            'items'            => $items,
            'lockedPieces'     => $lockedPieces,
            'users'            => $users,
            'catalogHierarchy' => $catalogHierarchy,
            'distinctCatalogs' => array_values($distinctCatalogs),
            'breadcrumb'       => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'QC Inspections', 'url' => url('production/qc')],
                ['label' => 'Inspect Batch #' . $batchId],
            ],
        ]);
    }

    private function getQCLockedPieceStates(array $batch, array $items): array {
        $outwardRow = Database::fetchOne(
            "SELECT COALESCE(SUM(COALESCE(NULLIF(po.total_quantity, 0), NULLIF(po.quantity, 0), pb.total_qty, 0)), 0) as total_outward
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             WHERE pb.qc_batch_id = ? OR (pb.qc_batch_id IS NULL AND pb.program_id = ? AND pb.program_id > 0)",
            [(int)$batch['id'], (int)($batch['program_id'] ?? 0)]
        );
        $outwardQty = (int)($outwardRow['total_outward'] ?? 0);
        if ($outwardQty <= 0) {
            return [];
        }

        $savedPiecesMap = [];
        $decodedPieces = json_decode((string)($batch['pieces_data'] ?? ''), true);
        if (!is_array($decodedPieces)) {
            $decodedPieces = json_decode(html_entity_decode((string)($batch['pieces_data'] ?? ''), ENT_QUOTES, 'UTF-8'), true);
        }
        if (!is_array($decodedPieces)) {
            $decodedPieces = json_decode(stripslashes((string)($batch['pieces_data'] ?? '')), true);
        }
        if (is_array($decodedPieces)) {
            foreach ($decodedPieces as $piece) {
                if (is_array($piece) && isset($piece['index'])) {
                    $savedPiecesMap[(int)$piece['index']] = $piece;
                }
            }
        }

        $lockedPieces = [];
        $pieceIndex = 1;
        $approvedIndex = 0;
        foreach ($items as $item) {
            $inspectedQty = max(1, (int)$item['inspected_qty']);
            $passedQty = (int)($item['passed_qty'] ?? 0);
            $rejectedQty = (int)($item['rejected_qty'] ?? 0);
            $reworkQty = (int)($item['rework_qty'] ?? 0);

            for ($pieceNumber = 1; $pieceNumber <= $inspectedQty; $pieceNumber++, $pieceIndex++) {
                $savedPiece = $savedPiecesMap[$pieceIndex] ?? null;
                if ($savedPiece !== null) {
                    $status = strtoupper((string)($savedPiece['status'] ?? 'PENDING'));
                } elseif ($pieceNumber <= $passedQty) {
                    $status = 'APPROVED';
                } elseif ($pieceNumber <= $passedQty + $rejectedQty) {
                    $status = 'REJECTED';
                } elseif ($pieceNumber <= $passedQty + $rejectedQty + $reworkQty) {
                    $status = 'REWORK';
                } else {
                    $status = 'PENDING';
                }

                if ($status !== 'APPROVED') {
                    continue;
                }

                $approvedIndex++;
                if ($approvedIndex > $outwardQty) {
                    continue;
                }
                $tagCode = trim((string)($savedPiece['tag_code'] ?? ''));
                $lockKey = $tagCode !== '' ? $tagCode : $pieceIndex;
                $lockedPieces[$lockKey] = [
                    'status' => 'APPROVED',
                    'is_reinspected' => !empty($savedPiece['is_reinspected']),
                    'defect_reason' => $savedPiece['defect_reason'] ?? '',
                    'defect_notes' => $savedPiece['defect_notes'] ?? '',
                    'outwarded' => $approvedIndex <= $outwardQty,
                    'item_id' => (int)$item['id'],
                    'tag_code' => $tagCode,
                    'index' => $pieceIndex,
                ];
            }
        }

        return $lockedPieces;
    }

    private function protectLockedQCPieces(array $piecesData, array $lockedPieces): array {
        if (empty($lockedPieces)) {
            return $piecesData;
        }

        $piecesByIndex = [];
        foreach ($piecesData as $piece) {
            if (is_array($piece) && isset($piece['index'])) {
                $piecesByIndex[(int)$piece['index']] = $piece;
            }
        }

        foreach ($lockedPieces as $key => $lockedPiece) {
            if (empty($lockedPiece['outwarded'])) {
                continue;
            }
            $index = null;
            if ($lockedPiece['tag_code'] !== '') {
                foreach ($piecesByIndex as $pieceIndex => $candidate) {
                    if (trim((string)($candidate['tag_code'] ?? '')) === $lockedPiece['tag_code']) {
                        $index = $pieceIndex;
                        break;
                    }
                }
                if ($index === null) {
                    continue;
                }
            } else {
                $index = (int)($lockedPiece['index'] ?? $key);
            }

            $piece = $piecesByIndex[$index] ?? [
                'index' => $index,
                'item_id' => $lockedPiece['item_id'],
                'tag_code' => $lockedPiece['tag_code'],
            ];
            $piece['index'] = $index;
            $piece['item_id'] = $lockedPiece['item_id'];
            if ($lockedPiece['tag_code'] !== '') {
                $piece['tag_code'] = $lockedPiece['tag_code'];
            }
            $piece['status'] = $lockedPiece['status'];
            $piece['is_reinspected'] = $lockedPiece['is_reinspected'];
            $piece['defect_reason'] = $lockedPiece['defect_reason'];
            $piece['defect_notes'] = $lockedPiece['defect_notes'];
            $piecesByIndex[$index] = $piece;
        }

        ksort($piecesByIndex);
        return array_values($piecesByIndex);
    }

    private function saveQCItemTotalsFromPieces(array $items, array $piecesData): void {
        $totals = [];
        foreach ($items as $item) {
            $totals[(int)$item['id']] = [
                'inspected_qty' => 0,
                'passed_qty' => 0,
                'rejected_qty' => 0,
                'rework_qty' => 0,
                'defect_reason' => null,
                'defect_notes' => null,
            ];
        }

        foreach ($piecesData as $piece) {
            if (!is_array($piece)) {
                continue;
            }
            $itemId = (int)($piece['item_id'] ?? 0);
            if (!isset($totals[$itemId])) {
                continue;
            }
            $itemTotals = &$totals[$itemId];
            $itemTotals['inspected_qty']++;
            $status = strtoupper((string)($piece['status'] ?? 'PENDING'));
            if ($status === 'APPROVED' || $status === 'PASSED') {
                $itemTotals['passed_qty']++;
            } elseif ($status === 'REJECTED') {
                $itemTotals['rejected_qty']++;
            } elseif ($status === 'REWORK') {
                $itemTotals['rework_qty']++;
            }
            if (empty($itemTotals['defect_reason']) && !empty($piece['defect_reason'])) {
                $itemTotals['defect_reason'] = trim((string)$piece['defect_reason']);
            }
            if (empty($itemTotals['defect_notes']) && !empty($piece['defect_notes'])) {
                $itemTotals['defect_notes'] = trim((string)$piece['defect_notes']);
            }
            unset($itemTotals);
        }

        foreach ($totals as $itemId => $itemTotals) {
            QCItem::update($itemId, $itemTotals);
        }
    }

    public function updateQC(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
        }

        $batchId = (int)$id;
        $batch = QCBatch::find($batchId);

        if (!$batch) {
            Response::error(404, 'QC Inspection Batch not found.');
            return;
        }

        try {
            $inspectorId = !empty($_POST['inspector_id']) ? (int)$_POST['inspector_id'] : null;
            if (!$inspectorId) {
                $user = Session::get('user');
                $inspectorId = !empty($user['id']) ? (int)$user['id'] : (!empty($batch['inspector_id']) ? (int)$batch['inspector_id'] : 1);
            }
            $notes = trim($_POST['notes'] ?? '');
            
            $items = QCBatch::getItems($batchId);
            $lockedPieces = $this->getQCLockedPieceStates($batch, $items);
            $rawPieces = $_POST['pieces_data'] ?? null;
            $piecesData = null;
            $decodedPieces = null;
            if (!empty($rawPieces)) {
                $decoded = json_decode($rawPieces, true);
                if (!is_array($decoded)) {
                    $decoded = json_decode(html_entity_decode($rawPieces, ENT_QUOTES, 'UTF-8'), true);
                }
                if (!is_array($decoded)) {
                    $decoded = json_decode(stripslashes($rawPieces), true);
                }
                if (is_array($decoded)) {
                    $decodedPieces = $this->protectLockedQCPieces($decoded, $lockedPieces);
                    $piecesData = json_encode($decodedPieces, JSON_UNESCAPED_UNICODE);
                }
            }

            // Also check $_POST['items'] fallback if provided by aggregatePieceCounts
            $postedItems = $_POST['items'] ?? [];
            if (!empty($postedItems) && is_array($postedItems)) {
                $validItemIds = array_column($items, 'id');
                foreach ($postedItems as $pItemId => $pItemVals) {
                    if (in_array((int)$pItemId, $validItemIds)) {
                        QCItem::update((int)$pItemId, [
                            'inspected_qty' => (int)($pItemVals['inspected_qty'] ?? 0),
                            'passed_qty'    => (int)($pItemVals['passed_qty'] ?? 0),
                            'rejected_qty'  => (int)($pItemVals['rejected_qty'] ?? 0),
                            'rework_qty'    => (int)($pItemVals['rework_qty'] ?? 0),
                            'defect_reason' => !empty($pItemVals['defect_reason']) ? trim($pItemVals['defect_reason']) : null,
                            'defect_notes'  => !empty($pItemVals['defect_notes']) ? trim($pItemVals['defect_notes']) : null,
                        ]);
                    }
                }
            }

            $updateData = [
                'inspector_id' => $inspectorId,
                'notes'        => $notes,
            ];
            if ($piecesData !== null) {
                $updateData['pieces_data'] = $piecesData;
            }
            QCBatch::update($batchId, $updateData);

            if ($decodedPieces !== null) {
                $this->saveQCItemTotalsFromPieces($items, $decodedPieces);
            }

            $result = QCBatch::recalculateTotals($batchId);

            Session::flash('success', 'QC inspection results saved successfully. Status: ' . $result['status']);
            Response::redirect(url('production/qc'));
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::back();
        }
    }

    /**
     * AJAX auto-save endpoint for QC inspection workstation.
     */
    public function saveQCAjax(string $id): void {
        $batchId = (int)$id;
        $batch = QCBatch::find($batchId);

        if (!$batch) {
            Response::json(['success' => false, 'message' => 'QC Inspection Batch not found.'], 404);
            return;
        }

        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            if (!is_array($data)) {
                $data = $_POST;
            }

            $items = QCBatch::getItems($batchId);
            $lockedPieces = $this->getQCLockedPieceStates($batch, $items);
            $rawPieces = $data['pieces_data'] ?? null;
            $piecesData = null;
            $decodedPieces = null;
            if (!empty($rawPieces)) {
                if (is_array($rawPieces)) {
                    $decodedPieces = $rawPieces;
                } else {
                    $decoded = json_decode($rawPieces, true);
                    if (!is_array($decoded)) {
                        $decoded = json_decode(html_entity_decode($rawPieces, ENT_QUOTES, 'UTF-8'), true);
                    }
                    if (!is_array($decoded)) {
                        $decoded = json_decode(stripslashes($rawPieces), true);
                    }
                    if (is_array($decoded)) {
                        $decodedPieces = $decoded;
                    }
                }
            }

            if ($decodedPieces !== null) {
                $decodedPieces = $this->protectLockedQCPieces($decodedPieces, $lockedPieces);
                $piecesData = json_encode($decodedPieces, JSON_UNESCAPED_UNICODE);
            }

            if ($piecesData !== null) {
                QCBatch::update($batchId, [
                    'pieces_data' => $piecesData,
                ]);
                $this->saveQCItemTotalsFromPieces($items, $decodedPieces);
            }

            $result = QCBatch::recalculateTotals($batchId);

            Response::json([
                'success' => true,
                'message' => 'QC inspection auto-saved.',
                'totals'  => $result,
            ]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRODUCTION INVENTORY (POST-QC PASSED PRODUCTS)
    // =========================================================================

    /**
     * Production Inventory — Dynamically lists all finished products passed through QC inspection.
     */
    /**
     * Production Inventory — Dynamically lists all finished products passed through QC inspection,
     * deducting outward dispatches and showing real-time remaining stock & outward history.
     */
    public function productionInventory(): void {
        $search = trim($_GET['search'] ?? '');
        $categoryFilter = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;

        $sql = "SELECT qi.*, 
                       qb.program_id, 
                       qb.inspector_id,
                       qb.created_at as batch_created_at,
                       pp.program_number, 
                       pp.name as program_name,
                       COALESCE(p.id, pp.product_id) as product_id,
                       COALESCE(p.name, pp.name) as product_name, 
                       p.code as product_sku,
                       p.image_url as product_image,
                       COALESCE(c_p.name, c_pp.name) as category_name,
                       COALESCE(p.category_id, pp.category_id) as category_id,
                       u.full_name as inspector_name
                FROM qc_items qi
                JOIN qc_batches qb ON qi.qc_batch_id = qb.id
                JOIN production_programs pp ON qb.program_id = pp.id
                LEFT JOIN products p ON pp.product_id = p.id
                LEFT JOIN categories c_p ON p.category_id = c_p.id
                LEFT JOIN categories c_pp ON pp.category_id = c_pp.id
                LEFT JOIN users u ON qb.inspector_id = u.id
                WHERE (qi.passed_qty > 0 OR qi.rework_qty > 0 OR qi.rejected_qty > 0)";
        
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (
                p.name LIKE ? 
                OR p.code LIKE ? 
                OR pp.name LIKE ? 
                OR pp.program_number LIKE ? 
                OR qi.size_name LIKE ? 
                OR qi.color_name LIKE ? 
                OR qi.tag_code LIKE ? 
                OR c_p.name LIKE ? 
                OR c_pp.name LIKE ? 
                OR CAST(qb.id AS CHAR) LIKE ?
            )";
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s, $s, $s]);
        }

        if ($categoryFilter) {
            $sql .= " AND (p.category_id = ? OR pp.category_id = ?)";
            $params[] = $categoryFilter;
            $params[] = $categoryFilter;
        }

        $sql .= " ORDER BY qb.created_at DESC, qi.id DESC";

        $items = Database::fetchAll($sql, $params);

        // Fetch all outward dispatches to link with batches/programs
        $allOutwards = Database::fetchAll(
            "SELECT po.id as outward_id,
                    po.outward_number,
                    po.dispatch_date,
                    po.status as outward_status,
                    COALESCE(po.total_quantity, po.quantity, pb.total_qty, 0) as outward_qty,
                    COALESCE(po.total_boxes, pb.box_count, 0) as total_boxes,
                    pb.id as production_batch_id,
                    pb.batch_number,
                    pb.status as batch_status,
                    pb.qc_batch_id,
                    pb.program_id,
                    w.name as warehouse_name,
                    w.code as warehouse_code,
                    po.vehicle_number,
                    po.driver_name,
                    po.transporter_name,
                    po.lr_number
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             LEFT JOIN warehouses w ON (po.warehouse_id = w.id OR pb.destination_warehouse_id = w.id)
             ORDER BY po.created_at DESC"
        );

        // Map outwards by qc_batch_id and program_id
        $outwardsByQC = [];
        $outwardsByProgram = [];
        foreach ($allOutwards as $ow) {
            if (!empty($ow['qc_batch_id'])) {
                $outwardsByQC[(int)$ow['qc_batch_id']][] = $ow;
            }
            if (!empty($ow['program_id'])) {
                $outwardsByProgram[(int)$ow['program_id']][] = $ow;
            }
        }

        // Group items by QC batch
        $batches = [];
        foreach ($items as $it) {
            $batchId = $it['qc_batch_id'];
            if (!isset($batches[$batchId])) {
                $batches[$batchId] = [
                    'qc_batch_id'         => $batchId,
                    'program_id'          => $it['program_id'],
                    'program_number'      => $it['program_number'],
                    'product_name'        => $it['product_name'] ?? $it['program_name'] ?? 'Finished Product',
                    'category_name'       => $it['category_name'] ?? '',
                    'category_id'         => $it['category_id'] ?? null,
                    'inspector_name'      => $it['inspector_name'],
                    'batch_created_at'    => $it['batch_created_at'],
                    'total_passed_pieces' => 0,
                    'total_rework_pieces' => 0,
                    'total_rejected_pieces' => 0,
                    'total_pieces'        => 0,
                    'total_value'         => 0.0,
                    'variants'            => [],
                ];
            }
            $passedQty = (int)$it['passed_qty'];
            $reworkQty = (int)($it['rework_qty'] ?? 0);
            $rejectedQty = (int)($it['rejected_qty'] ?? 0);
            $price = (float)(($it['discount_price'] ?? 0) > 0 ? $it['discount_price'] : ($it['base_price'] ?? 0));
            $batches[$batchId]['total_passed_pieces'] += $passedQty;
            $batches[$batchId]['total_rework_pieces'] += $reworkQty;
            $batches[$batchId]['total_rejected_pieces'] += $rejectedQty;
            $batches[$batchId]['total_pieces']        += $passedQty;
            $batches[$batchId]['total_value']         += ($passedQty * $price);
            if ($passedQty > 0) {
                $batches[$batchId]['variants'][] = [
                    'size_name'  => $it['size_name'],
                    'color_name' => $it['color_name'],
                    'passed_qty' => $passedQty,
                ];
            }
        }

        // Calculate Outward Deductions, Remaining In-Stock & Outward Status for each batch
        foreach ($batches as $bId => &$batch) {
            $progId = (int)($batch['program_id'] ?? 0);
            
            // Collect matching outward records
            $matchingOutwards = [];
            $seenOutwardIds = [];
            if (!empty($outwardsByQC[$bId])) {
                foreach ($outwardsByQC[$bId] as $ow) {
                    if (!isset($seenOutwardIds[$ow['outward_id']])) {
                        $matchingOutwards[] = $ow;
                        $seenOutwardIds[$ow['outward_id']] = true;
                    }
                }
            }
            if (!empty($outwardsByProgram[$progId])) {
                foreach ($outwardsByProgram[$progId] as $ow) {
                    if (!isset($seenOutwardIds[$ow['outward_id']])) {
                        $matchingOutwards[] = $ow;
                        $seenOutwardIds[$ow['outward_id']] = true;
                    }
                }
            }

            $totalOutwardQty = 0;
            foreach ($matchingOutwards as $ow) {
                $totalOutwardQty += (int)$ow['outward_qty'];
            }

            $passedPcs = (int)$batch['total_passed_pieces'];
            $outwardPcs = min($passedPcs, $totalOutwardQty);
            $remainingPcs = max(0, $passedPcs - $outwardPcs);
            
            $unitAvgPrice = $passedPcs > 0 ? ($batch['total_value'] / $passedPcs) : 0;
            $remainingVal = max(0.0, $remainingPcs * $unitAvgPrice);

            $batch['total_outward_pieces'] = $outwardPcs;
            $batch['remaining_pieces']       = $remainingPcs;
            $batch['remaining_value']        = $remainingVal;
            $batch['total_passed_value']     = $batch['total_value'];
            $batch['outward_history']        = $passedPcs > 0 ? $matchingOutwards : [];
            $batch['outward_count']          = count($batch['outward_history']);

            if ($passedPcs === 0) {
                $batch['stock_status'] = 'NO_PASSED_STOCK';
            } elseif ($remainingPcs === 0) {
                $batch['stock_status'] = 'FULLY_OUTWARDED';
            } elseif ($outwardPcs > 0 && $remainingPcs > 0) {
                $batch['stock_status'] = 'PARTIALLY_OUTWARDED';
            } else {
                $batch['stock_status'] = 'IN_STOCK';
            }
        }
        unset($batch);

        // Overall stats reflecting remaining inventory & outward history
        $stats = [
            'total_remaining_pieces'    => array_sum(array_column($batches, 'remaining_pieces')),
            'total_remaining_valuation' => array_sum(array_column($batches, 'remaining_value')),
            'total_outwarded_pieces'    => array_sum(array_column($batches, 'total_outward_pieces')),
            'total_passed_pieces'       => array_sum(array_column($batches, 'total_passed_pieces')),
            'total_rework_pieces'       => array_sum(array_column($batches, 'total_rework_pieces')),
            'total_rejected_pieces'     => array_sum(array_column($batches, 'total_rejected_pieces')),
            'total_passed_valuation'    => array_sum(array_column($batches, 'total_passed_value')),
            'unique_batches_count'      => count($batches),
            'in_stock_batches_count'    => count(array_filter($batches, fn($b) => ($b['remaining_pieces'] ?? 0) > 0)),
            'unique_products_count'     => count(array_unique(array_filter(array_column($items, 'product_id')))),
        ];

        // Fetch categories for filter dropdown
        $categories = Database::fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
        if (empty($categories)) {
            $categories = Database::fetchAll("SELECT id, name FROM categories ORDER BY name ASC");
        }

        Response::view('production.inventory.index', [
            'pageTitle'      => 'Production Inventory',
            'activeModule'   => 'production',
            'activeMenu'     => 'prod-inventory',
            'batches'        => array_values($batches),
            'stats'          => $stats,
            'categories'     => $categories,
            'search'         => $search,
            'categoryFilter' => $categoryFilter,
            'breadcrumb'     => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'QC Inspections', 'url' => url('production/qc')],
                ['label' => 'Inventory'],
            ],
        ]);
    }

    /**
     * Show detailed piece-by-piece inventory for a single QC batch,
     * with real-time remaining stock calculation and full outward dispatch history.
     */
    public function productionInventoryBatch(int $id): void {
        $sql = "SELECT qi.*, 
                       qb.program_id, 
                       qb.inspector_id,
                       qb.created_at as batch_created_at,
                       qb.pieces_data,
                       pp.program_number, 
                       p.id as product_id,
                       p.name as product_name, 
                       p.code as product_sku,
                       p.image_url as product_image,
                       COALESCE(c_qi.name, c.name, c_pp.name) as category_name,
                       COALESCE(sc_qi.name, sc.name, sc_pp.name) as subcategory_name,
                       COALESCE(ssc_qi.name, ssc.name, ssc_pp.name) as sub_subcategory_name,
                       c_qi.code as qi_cat_code,
                       sc_qi.code as qi_sub_code,
                       ssc_qi.code as qi_subsub_code,
                       li.start_seq as label_start_seq,
                       li.end_seq as label_end_seq,
                       li.start_code as label_start_code,
                       li.end_code as label_end_code,
                       u.full_name as inspector_name
                FROM qc_items qi
                JOIN qc_batches qb ON qi.qc_batch_id = qb.id
                JOIN production_programs pp ON qb.program_id = pp.id
                LEFT JOIN products p ON pp.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN categories c_pp ON pp.category_id = c_pp.id
                LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
                LEFT JOIN subcategories sc_pp ON pp.subcategory_id = sc_pp.id
                LEFT JOIN sub_subcategories ssc ON p.sub_subcategory_id = ssc.id
                LEFT JOIN sub_subcategories ssc_pp ON pp.sub_subcategory_id = ssc_pp.id
                LEFT JOIN categories c_qi ON qi.category_id = c_qi.id
                LEFT JOIN subcategories sc_qi ON qi.subcategory_id = sc_qi.id
                LEFT JOIN sub_subcategories ssc_qi ON qi.sub_subcategory_id = ssc_qi.id
                LEFT JOIN users u ON qb.inspector_id = u.id
                LEFT JOIN label_items li ON (
                    (qb.label_batch_id IS NOT NULL AND li.label_batch_id = qb.label_batch_id AND (
                        (qi.variant_id IS NOT NULL AND li.variant_id = qi.variant_id)
                        OR (LOWER(TRIM(li.size_name)) = LOWER(TRIM(qi.size_name)) AND LOWER(TRIM(li.color_name)) = LOWER(TRIM(qi.color_name)))
                    ))
                    OR (qb.label_batch_id IS NULL AND qb.stitching_batch_id IS NOT NULL AND li.label_batch_id = (
                        SELECT lb_sub.id FROM label_batches lb_sub WHERE lb_sub.stitching_batch_id = qb.stitching_batch_id ORDER BY lb_sub.id DESC LIMIT 1
                    ) AND (
                        (qi.variant_id IS NOT NULL AND li.variant_id = qi.variant_id)
                        OR (LOWER(TRIM(li.size_name)) = LOWER(TRIM(qi.size_name)) AND LOWER(TRIM(li.color_name)) = LOWER(TRIM(qi.color_name)))
                    ))
                )
                WHERE qi.qc_batch_id = ?
                  AND (qi.passed_qty > 0 OR qi.rework_qty > 0 OR qi.rejected_qty > 0)
                ORDER BY qi.id ASC";

        $qcItems = Database::fetchAll($sql, [$id]);

        if (empty($qcItems)) {
            Response::redirect(url('production/inventory'), 'error', 'Batch not found or has no recorded QC outcome.');
            return;
        }

        $first = $qcItems[0];
        $qcOutcomeItems = array_values(array_filter($qcItems, static function (array $item): bool {
            return (int)($item['rework_qty'] ?? 0) > 0 || (int)($item['rejected_qty'] ?? 0) > 0;
        }));
        $totalReworkPieces = array_sum(array_map(static fn(array $item): int => (int)($item['rework_qty'] ?? 0), $qcItems));
        $totalRejectedPieces = array_sum(array_map(static fn(array $item): int => (int)($item['rejected_qty'] ?? 0), $qcItems));
        $items = array_values(array_filter($qcItems, static function (array $item): bool {
            return (int)($item['passed_qty'] ?? 0) > 0;
        }));
        $programId = (int)($first['program_id'] ?? 0);

        // Fetch outward dispatch history for this QC batch / program
        $outwards = empty($items) ? [] : Database::fetchAll(
            "SELECT po.*,
                    pb.batch_number,
                    pb.box_count as batch_box_count,
                    pb.status as batch_status,
                    COALESCE(po.total_quantity, po.quantity, pb.total_qty, 0) as outward_qty,
                    COALESCE(po.total_boxes, pb.box_count, 0) as total_boxes,
                    w.name as warehouse_name,
                    w.code as warehouse_code,
                    u.full_name as dispatched_by_name,
                    pu.name as unit_name
             FROM production_outwards po
             JOIN production_batches pb ON po.production_batch_id = pb.id
             LEFT JOIN warehouses w ON (po.warehouse_id = w.id OR pb.destination_warehouse_id = w.id)
             LEFT JOIN production_units pu ON po.production_unit_id = pu.id
             LEFT JOIN users u ON po.created_by = u.id
             WHERE pb.qc_batch_id = ? OR (pb.program_id = ? AND pb.program_id > 0)
             ORDER BY po.created_at DESC",
            [$id, $programId]
        );

        $totalOutwardPieces = 0;
        foreach ($outwards as $ow) {
            $totalOutwardPieces += (int)$ow['outward_qty'];
        }

        $getPieceCatalogAbbr = function($it, $first) {
            if (!empty($it['label_start_code']) && preg_match('/^([A-Za-z]+)/', $it['label_start_code'], $m)) {
                return strtoupper($m[1]);
            }
            if (!empty($it['qr_code_prefix']) && strlen(trim($it['qr_code_prefix'])) <= 5) {
                return strtoupper(trim($it['qr_code_prefix']));
            }
            $c1 = trim($it['category_name'] ?? '');
            $c2 = trim($it['subcategory_name'] ?? '');
            $c3 = trim($it['sub_subcategory_name'] ?? '');
            $abbr = '';
            if (!empty($c1)) $abbr .= strtoupper(mb_substr($c1, 0, 1));
            if (!empty($c2)) $abbr .= strtoupper(mb_substr($c2, 0, 1));
            if (!empty($c3)) $abbr .= strtoupper(mb_substr($c3, 0, 1));
            if (strlen($abbr) === 3) return $abbr;

            $hierarchy = $it['catalog_hierarchy'] ?? '';
            if (!empty($hierarchy)) {
                $parts = array_filter(preg_split('/›|>|\||\-/', $hierarchy));
                if (!empty($parts)) {
                    $abbr = '';
                    foreach ($parts as $part) {
                        $words = preg_split('/\s+/', trim($part));
                        if (!empty($words[0])) $abbr .= strtoupper(mb_substr($words[0], 0, 1));
                    }
                    if (!empty($abbr)) return substr($abbr, 0, 3);
                }
            }
            return 'BSH';
        };

        // Decode saved pieces data if available
        $savedPiecesMap = [];
        if (!empty($first['pieces_data'])) {
            $raw = $first['pieces_data'];
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                $decoded = json_decode(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'), true);
            }
            if (!is_array($decoded)) {
                $decoded = json_decode(stripslashes($raw), true);
            }
            if (is_array($decoded)) {
                foreach ($decoded as $dp) {
                    if (!empty($dp['item_id'])) {
                        $savedPiecesMap[(int)$dp['item_id']][] = $dp;
                    }
                }
            }
        }

        // Build piece-by-piece records
        $pieceItems = [];
        $globalPieceIndex = 1;
        $batchPieceSeq = 1;
        $totalPieces = 0;
        $totalValue  = 0.0;

        foreach ($items as $it) {
            $passedQty = (int)$it['passed_qty'];
            $price = (float)($it['discount_price'] > 0 ? $it['discount_price'] : $it['base_price']);
            $totalPieces += $passedQty;
            $totalValue  += ($passedQty * $price);

            $batchDate = new DateTime($it['batch_created_at'] ?? 'now');
            $monthStr  = $batchDate->format('m');
            $yearStr   = $batchDate->format('y');
            $catalogPrefix = $getPieceCatalogAbbr($it, $first);
            $startSeq = !empty($it['label_start_seq']) ? (int)$it['label_start_seq'] : null;

            $codePrefixAndDate = null;
            if (!empty($it['label_start_code']) && preg_match('/^([A-Za-z]+\d{4})/', $it['label_start_code'], $m)) {
                $codePrefixAndDate = strtoupper($m[1]);
            }

            $itemSavedPieces = $savedPiecesMap[(int)$it['id']] ?? [];
            $passedSavedPieces = array_values(array_filter($itemSavedPieces, function($p) {
                return ($p['status'] ?? '') === 'APPROVED';
            }));

            $hParts = array_filter([
                $it['category_name'] ?? '',
                $it['subcategory_name'] ?? '',
                $it['sub_subcategory_name'] ?? ''
            ]);
            $catHierarchy = !empty($hParts) ? implode(' > ', $hParts) : ($it['category_name'] ?? 'General');

            for ($p = 1; $p <= $passedQty; $p++) {
                if (isset($passedSavedPieces[$p - 1]['tag_code']) && !empty($passedSavedPieces[$p - 1]['tag_code'])) {
                    $pieceTagCode = $passedSavedPieces[$p - 1]['tag_code'];
                } elseif ($codePrefixAndDate && $startSeq !== null) {
                    $seqVal = $startSeq + ($p - 1);
                    $pieceTagCode = $codePrefixAndDate . sprintf("%02d", $seqVal);
                } elseif ($startSeq !== null) {
                    $seqVal = $startSeq + ($p - 1);
                    $pieceTagCode = $catalogPrefix . $monthStr . $yearStr . sprintf("%02d", $seqVal);
                } else {
                    $pieceTagCode = $catalogPrefix . $monthStr . $yearStr . sprintf("%02d", $batchPieceSeq);
                }
                $batchPieceSeq++;

                $isDispatched = ($globalPieceIndex <= $totalOutwardPieces);
                $pieceOutwardInfo = null;
                if ($isDispatched && !empty($outwards)) {
                    $pieceOutwardInfo = $outwards[0]['outward_number'] ?? 'OUTWARDED';
                }

                $pieceItems[] = [
                    'piece_no'        => $globalPieceIndex++,
                    'piece_seq'       => $p,
                    'passed_in_group' => $passedQty,
                    'qc_item_id'      => $it['id'],
                    'qc_batch_id'     => $it['qc_batch_id'],
                    'product_id'      => $it['product_id'],
                    'product_name'    => $it['product_name'],
                    'product_sku'     => $it['product_sku'] ?? '',
                    'sku'             => $it['product_sku'] ?? '',
                    'product_image'   => $it['product_image'] ?? null,
                    'category_name'   => !empty($it['catalog_hierarchy']) ? $it['catalog_hierarchy'] : $catHierarchy,
                    'size_name'       => $it['size_name'] ?? 'Std',
                    'color_name'      => $it['color_name'] ?? 'Std',
                    'tag_code'        => $pieceTagCode,
                    'base_price'      => (float)($it['base_price'] ?? 0),
                    'discount_price'  => (float)($it['discount_price'] ?? 0),
                    'program_id'      => $it['program_id'] ?? null,
                    'program_number'  => $it['program_number'] ?? 'N/A',
                    'inspector_name'  => $it['inspector_name'] ?? 'Inspector',
                    'batch_created_at'=> $it['batch_created_at'] ?? null,
                    'is_dispatched'   => $isDispatched,
                    'outward_info'    => $pieceOutwardInfo,
                ];
            }
        }

        $effectiveOutwardPieces = min($totalPieces, $totalOutwardPieces);
        $remainingPieces = max(0, $totalPieces - $effectiveOutwardPieces);
        $avgPiecePrice = $totalPieces > 0 ? ($totalValue / $totalPieces) : 0;
        $remainingValue = max(0.0, $remainingPieces * $avgPiecePrice);

        Response::view('production.inventory.batch', [
            'pageTitle'           => 'QC Batch #' . $id . ' — Inventory Detail',
            'activeModule'        => 'production',
            'activeMenu'          => 'prod-inventory',
            'qcBatchId'           => $id,
            'batchInfo'           => $first,
            'pieceItems'          => $pieceItems,
            'rawItems'            => $items,
            'qcOutcomeItems'      => $qcOutcomeItems,
            'totalReworkPieces'   => $totalReworkPieces,
            'totalRejectedPieces' => $totalRejectedPieces,
            'totalPieces'         => $totalPieces,
            'totalValue'          => $totalValue,
            'totalOutwardPieces'  => $effectiveOutwardPieces,
            'remainingPieces'     => $remainingPieces,
            'remainingValue'      => $remainingValue,
            'outwards'            => $outwards,
            'breadcrumb'          => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Inventory', 'url' => url('production/inventory')],
                ['label' => 'QC Batch #' . $id],
            ],
        ]);
    }

    // =========================================================================
    // PRODUCTION REPORTS & ANALYTICS
    // =========================================================================

    public function reports(): void {
        // Summary KPIs
        $totalPrograms = Database::fetchOne("SELECT COUNT(*) as cnt, COALESCE(SUM(planned_quantity),0) as planned, COALESCE(SUM(completed_quantity),0) as completed FROM production_programs");
        $totalCutting = Database::fetchOne("SELECT COALESCE(SUM(cut_qty),0) as cut, COALESCE(SUM(rejected_qty),0) as rejected, COALESCE(SUM(wastage_qty),0) as wastage FROM cutting_batches");
        $totalQC = Database::fetchOne("SELECT COALESCE(SUM(inspected_qty),0) as inspected, COALESCE(SUM(passed_qty),0) as passed, COALESCE(SUM(rejected_qty),0) as rejected, COALESCE(SUM(rework_qty),0) as rework FROM qc_items");
        
        // Efficiency by production unit
        $unitStats = Database::fetchAll(
            "SELECT pu.name as unit_name,
                    COUNT(pp.id) as program_count,
                    COALESCE(SUM(pp.planned_quantity), 0) as total_planned,
                    COALESCE(SUM(pp.completed_quantity), 0) as total_completed
             FROM production_units pu
             LEFT JOIN production_programs pp ON pu.id = pp.production_unit_id
             WHERE pu.is_active = 1
             GROUP BY pu.id, pu.name"
        );

        // Stage breakdown
        $stageCounts = Database::fetchOne(
            "SELECT 
                (SELECT COUNT(*) FROM production_programs WHERE status = 'DRAFT') as draft_programs,
                (SELECT COUNT(*) FROM production_programs WHERE status IN ('APPROVED', 'SCHEDULED')) as scheduled_programs,
                (SELECT COUNT(*) FROM cutting_batches WHERE status IN ('PENDING', 'IN_PROGRESS')) as active_cutting,
                (SELECT COUNT(*) FROM stitching_batches WHERE status IN ('PENDING', 'IN_PROGRESS')) as active_stitching,
                (SELECT COUNT(*) FROM label_batches WHERE status IN ('PENDING', 'IN_PROGRESS')) as active_labels,
                (SELECT COUNT(*) FROM qc_batches WHERE status IN ('PENDING', 'PARTIAL', 'REWORK')) as active_qc,
                (SELECT COUNT(*) FROM production_batches WHERE status = 'APPROVED') as ready_batches"
        );

        Response::view('production.reports.index', [
            'pageTitle'     => 'Production Analytics & Reports',
            'activeModule'  => 'production',
            'activeMenu'    => 'reports',
            'totalPrograms' => $totalPrograms,
            'totalCutting'  => $totalCutting,
            'totalQC'       => $totalQC,
            'unitStats'     => $unitStats,
            'stageCounts'   => $stageCounts,
            'breadcrumb'    => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Analytics & Reports'],
            ],
        ]);
    }

    // =========================================================================
    // FABRIC & CUTTING WASTAGE SELLING MANAGEMENT
    // =========================================================================

    public function wastageList(): void {
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = trim($_GET['payment_status'] ?? '');
        $batchFilter = trim($_GET['cutting_batch_id'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $sql = "SELECT ws.*, 
                       cb.batch_number as cutting_batch_number,
                       cb.name as cutting_batch_name,
                       cb.wastage_weight as batch_total_wastage,
                       cb.wastage_cost as batch_wastage_cost,
                       pp.program_number,
                       pp.name as program_name,
                       u.full_name as created_by_name
                FROM wastage_sales ws
                LEFT JOIN cutting_batches cb ON ws.cutting_batch_id = cb.id
                LEFT JOIN production_programs pp ON ws.program_id = pp.id
                LEFT JOIN users u ON ws.created_by = u.id
                WHERE 1=1";
        
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                ws.sale_number LIKE ?
                OR ws.buyer_name LIKE ?
                OR ws.buyer_company LIKE ?
                OR ws.buyer_phone LIKE ?
                OR ws.invoice_number LIKE ?
                OR ws.payment_reference LIKE ?
                OR cb.batch_number LIKE ?
                OR pp.program_number LIKE ?
            )";
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s]);
        }

        if ($type !== '') {
            $sql .= " AND ws.wastage_type = ?";
            $params[] = $type;
        }

        if ($status !== '') {
            $sql .= " AND ws.payment_status = ?";
            $params[] = $status;
        }

        if ($batchFilter !== '') {
            $sql .= " AND ws.cutting_batch_id = ?";
            $params[] = (int)$batchFilter;
        }

        if ($dateFrom !== '') {
            $sql .= " AND ws.sale_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND ws.sale_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY ws.sale_date DESC, ws.id DESC";
        $sales = Database::fetchAll($sql, $params);

        // Overall summary stats
        $allSales = Database::fetchAll("SELECT weight, total_amount, paid_amount, payment_status, wastage_type FROM wastage_sales");
        $totalWeight = (float)array_sum(array_column($allSales, 'weight'));
        $totalAmount = (float)array_sum(array_column($allSales, 'total_amount'));
        $totalPaid = (float)array_sum(array_column($allSales, 'paid_amount'));
        $totalPending = max(0.0, $totalAmount - $totalPaid);
        $avgRate = $totalWeight > 0 ? ($totalAmount / $totalWeight) : 0.0;

        $stats = [
            'total_records'   => count($allSales),
            'total_weight'    => $totalWeight,
            'total_amount'    => $totalAmount,
            'total_paid'      => $totalPaid,
            'total_pending'   => $totalPending,
            'avg_rate_per_kg' => $avgRate,
            'count_paid'      => count(array_filter($allSales, fn($s) => $s['payment_status'] === 'PAID')),
            'count_partial'   => count(array_filter($allSales, fn($s) => $s['payment_status'] === 'PARTIAL')),
            'count_pending'   => count(array_filter($allSales, fn($s) => $s['payment_status'] === 'PENDING')),
        ];

        // Fetch cutting module batches with wastage details, program info, and remaining balance
        $cuttingBatches = Database::fetchAll("
            SELECT cb.id, cb.batch_number, cb.name, cb.program_id, cb.seller_roll_id,
                   cb.roll_weight, cb.roll_price, cb.wastage_weight, cb.wastage_cost, cb.used_weight,
                   pp.program_number, pp.name as program_name,
                   s.name as seller_name,
                   sr.color, sr.design_name,
                   COALESCE(sold.sold_weight, 0.0) as sold_weight,
                   GREATEST(0.0, cb.wastage_weight - COALESCE(sold.sold_weight, 0.0)) as available_wastage_weight
            FROM cutting_batches cb
            LEFT JOIN production_programs pp ON cb.program_id = pp.id
            LEFT JOIN seller_rolls sr ON cb.seller_roll_id = sr.id
            LEFT JOIN sellers s ON sr.seller_id = s.id
            LEFT JOIN (
                SELECT cutting_batch_id, SUM(weight) as sold_weight
                FROM wastage_sales
                WHERE cutting_batch_id IS NOT NULL
                GROUP BY cutting_batch_id
            ) sold ON cb.id = sold.cutting_batch_id
            ORDER BY cb.id DESC
        ");

        $programs = Database::fetchAll("SELECT id, program_number, name FROM production_programs ORDER BY id DESC LIMIT 50");

        Response::view('production.wastage.index', [
            'pageTitle'      => 'Fabric & Cutting Wastage Selling',
            'activeModule'   => 'production',
            'activeMenu'     => 'wastage-selling',
            'sales'          => $sales,
            'stats'          => $stats,
            'cuttingBatches' => $cuttingBatches,
            'programs'       => $programs,
            'search'         => $search,
            'type'           => $type,
            'paymentStatus'  => $status,
            'cuttingBatchId' => $batchFilter,
            'dateFrom'       => $dateFrom,
            'dateTo'         => $dateTo,
            'breadcrumb'     => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Wastage Selling'],
            ],
        ]);
    }

    public function storeWastage(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $cuttingBatchId = !empty($_POST['cutting_batch_id']) ? (int)$_POST['cutting_batch_id'] : null;
        if (!$cuttingBatchId || $cuttingBatchId <= 0) {
            Session::flash('error', 'Select Cutting Module Wastage / Scrap Batch is required.');
            Response::back();
            return;
        }

        $buyerName = trim($_POST['buyer_name'] ?? '');
        if (empty($buyerName)) {
            Session::flash('error', 'Scrap Buyer / Vendor Name is required.');
            Response::back();
            return;
        }

        $buyerPhone = trim($_POST['buyer_phone'] ?? '');
        if (empty($buyerPhone)) {
            Session::flash('error', 'Buyer Phone / Mobile is required.');
            Response::back();
            return;
        }
        if (!preg_match('/^[0-9]{10}$/', $buyerPhone)) {
            Session::flash('error', 'Buyer Phone / Mobile must be exactly 10 digits.');
            Response::back();
            return;
        }

        $buyerCompany = trim($_POST['buyer_company'] ?? '');
        if (empty($buyerCompany)) {
            Session::flash('error', 'Buyer Company / Business Name is required.');
            Response::back();
            return;
        }

        $wastageType = trim($_POST['wastage_type'] ?? '');
        if (empty($wastageType)) {
            Session::flash('error', 'Wastage Category is required.');
            Response::back();
            return;
        }

        $weight = (float)($_POST['weight'] ?? 0.0);
        $ratePerKg = (float)($_POST['rate_per_kg'] ?? 0.0);
        $totalAmountInput = (float)($_POST['total_amount'] ?? 0.0);

        if ($weight <= 0) {
            Session::flash('error', 'Wastage Weight must be greater than 0.');
            Response::back();
            return;
        }

        // Validate cutting batch available wastage balance (excluding current sale)
        $cb = Database::fetchOne("SELECT id, batch_number, name, program_id, wastage_weight FROM cutting_batches WHERE id = ?", [$cuttingBatchId]);
        if (!$cb) {
            Session::flash('error', 'Selected cutting batch not found.');
            Response::back();
            return;
        }

        $soldRow = Database::fetchOne(
            "SELECT COALESCE(SUM(weight), 0.0) as sold_weight FROM wastage_sales WHERE cutting_batch_id = ? AND id != ?",
            [$cuttingBatchId, $saleId]
        );
        $alreadySold = (float)($soldRow['sold_weight'] ?? 0.0);
        $totalWastage = (float)($cb['wastage_weight'] ?? 0.0);
        $availableWastage = max(0.0, $totalWastage - $alreadySold);

        if ($availableWastage <= 0.001) {
            Session::flash('error', "Cutting Batch [{$cb['batch_number']}] wastage is ALREADY FULLY SOLD by other transactions. Cannot update sale.");
            Response::back();
            return;
        }

        if ($weight > $availableWastage + 0.001) {
            Session::flash('error', "Entered weight ({$weight} kg) exceeds available wastage balance ({$availableWastage} kg) for Batch [{$cb['batch_number']}].");
            Response::back();
            return;
        }

        // Validate cutting batch available wastage balance
        $cb = Database::fetchOne("SELECT id, batch_number, name, program_id, wastage_weight FROM cutting_batches WHERE id = ?", [$cuttingBatchId]);
        if (!$cb) {
            Session::flash('error', 'Selected cutting batch not found.');
            Response::back();
            return;
        }

        $soldRow = Database::fetchOne(
            "SELECT COALESCE(SUM(weight), 0.0) as sold_weight FROM wastage_sales WHERE cutting_batch_id = ?",
            [$cuttingBatchId]
        );
        $alreadySold = (float)($soldRow['sold_weight'] ?? 0.0);
        $totalWastage = (float)($cb['wastage_weight'] ?? 0.0);
        $availableWastage = max(0.0, $totalWastage - $alreadySold);

        if ($availableWastage <= 0.001) {
            Session::flash('error', "Cutting Batch [{$cb['batch_number']}] wastage is ALREADY FULLY SOLD ({$alreadySold} kg sold of {$totalWastage} kg total). Cannot record another sale.");
            Response::back();
            return;
        }

        if ($weight > $availableWastage + 0.001) {
            Session::flash('error', "Entered weight ({$weight} kg) exceeds available wastage balance ({$availableWastage} kg) for Batch [{$cb['batch_number']}].");
            Response::back();
            return;
        }

        if ($ratePerKg <= 0 && $totalAmountInput <= 0) {
            Session::flash('error', 'Selling Price & Quantity (Manual Entry) is required. Please enter a valid Selling Rate (₹/kg) or Total Sale Price.');
            Response::back();
            return;
        }

        if ($totalAmountInput <= 0 && $ratePerKg > 0) {
            $totalAmount = round($weight * $ratePerKg, 2);
        } elseif ($ratePerKg <= 0 && $totalAmountInput > 0) {
            $ratePerKg = round($totalAmountInput / $weight, 2);
            $totalAmount = round($totalAmountInput, 2);
        } else {
            $totalAmount = round($totalAmountInput, 2);
            $ratePerKg = round($ratePerKg, 2);
        }

        if (!isset($_POST['paid_amount']) || trim((string)$_POST['paid_amount']) === '') {
            Session::flash('error', 'Amount Received (₹) is required (enter 0 if unpaid).');
            Response::back();
            return;
        }
        $paidAmount = (float)$_POST['paid_amount'];
        if ($paidAmount < 0) {
            Session::flash('error', 'Amount Received cannot be negative.');
            Response::back();
            return;
        }
        if ($paidAmount > $totalAmount) {
            Session::flash('error', 'Amount Received cannot exceed Total Sale Price (₹' . number_format($totalAmount, 2) . ').');
            Response::back();
            return;
        }

        $paymentStatus = trim($_POST['payment_status'] ?? '');
        if (empty($paymentStatus) || !in_array($paymentStatus, ['PAID', 'PARTIAL', 'PENDING'], true)) {
            $paymentStatus = ($paidAmount >= $totalAmount && $totalAmount > 0) ? 'PAID' : (($paidAmount > 0) ? 'PARTIAL' : 'PENDING');
        }

        $paymentMethod = trim($_POST['payment_method'] ?? '');
        if (empty($paymentMethod) || !in_array($paymentMethod, ['CASH', 'UPI', 'BANK_TRANSFER', 'CHEQUE', 'CREDIT'], true)) {
            $paymentMethod = 'CASH';
        }

        $saleDate = trim($_POST['sale_date'] ?? '');
        if (empty($saleDate)) {
            Session::flash('error', 'Sale Date is required.');
            Response::back();
            return;
        }

        $paymentReference = !empty($_POST['payment_reference']) ? trim($_POST['payment_reference']) : null;
        $invoiceNumber = !empty($_POST['invoice_number']) ? trim($_POST['invoice_number']) : null;
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;

        $programId = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        if ($cuttingBatchId && !$programId) {
            $cb = Database::fetchOne("SELECT program_id FROM cutting_batches WHERE id = ?", [$cuttingBatchId]);
            if ($cb && !empty($cb['program_id'])) {
                $programId = (int)$cb['program_id'];
            }
        }

        // Handle photo / bill upload (Cloudinary Cloud Storage)
        $photoUrl = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoUrl = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/wastage', 'image');
        }

        // Generate sequential sale number e.g. WST-2026-0001
        $count = Database::fetchOne("SELECT COUNT(*) as cnt FROM wastage_sales");
        $nextSeq = (int)($count['cnt'] ?? 0) + 1;
        $saleNumber = 'WST-' . date('Y') . '-' . sprintf("%04d", $nextSeq);

        $userId = (int)(Session::get('user_id') ?? 1);

        try {
            Database::execute(
                "INSERT INTO wastage_sales 
                    (sale_number, buyer_name, buyer_phone, buyer_company, cutting_batch_id, program_id, wastage_type, weight, rate_per_kg, total_amount, paid_amount, payment_status, payment_method, payment_reference, invoice_number, sale_date, photo_url, notes, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $saleNumber,
                    $buyerName,
                    $buyerPhone,
                    $buyerCompany,
                    $cuttingBatchId,
                    $programId,
                    $wastageType,
                    $weight,
                    $ratePerKg,
                    $totalAmount,
                    $paidAmount,
                    $paymentStatus,
                    $paymentMethod,
                    $paymentReference ?: null,
                    $invoiceNumber ?: null,
                    $saleDate,
                    $photoUrl,
                    $notes ?: null,
                    $userId
                ]
            );
            $saleId = (int)Database::lastInsertId();

            AuditService::log('production', 'create', 'wastage_sales', $saleId, null, [
                'sale_number'  => $saleNumber,
                'buyer_name'   => $buyerName,
                'weight'       => $weight,
                'total_amount' => $totalAmount
            ]);

            Session::flash('success', "Wastage sale {$saleNumber} recorded successfully for ₹" . number_format($totalAmount, 2));
            Response::redirect(url('production/wastage'));
        } catch (Exception $e) {
            Session::flash('error', 'Error recording wastage sale: ' . $e->getMessage());
            Response::back();
        }
    }

    public function updateWastage(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $saleId = (int)$id;
        $existing = Database::fetchOne("SELECT * FROM wastage_sales WHERE id = ?", [$saleId]);
        if (!$existing) {
            Session::flash('error', 'Wastage sale record not found.');
            Response::back();
            return;
        }

        $cuttingBatchId = !empty($_POST['cutting_batch_id']) ? (int)$_POST['cutting_batch_id'] : null;
        if (!$cuttingBatchId || $cuttingBatchId <= 0) {
            Session::flash('error', 'Select Cutting Module Wastage / Scrap Batch is required.');
            Response::back();
            return;
        }

        $buyerName = trim($_POST['buyer_name'] ?? '');
        if (empty($buyerName)) {
            Session::flash('error', 'Scrap Buyer / Vendor Name is required.');
            Response::back();
            return;
        }

        $buyerPhone = trim($_POST['buyer_phone'] ?? '');
        if (empty($buyerPhone)) {
            Session::flash('error', 'Buyer Phone / Mobile is required.');
            Response::back();
            return;
        }
        if (!preg_match('/^[0-9]{10}$/', $buyerPhone)) {
            Session::flash('error', 'Buyer Phone / Mobile must be exactly 10 digits.');
            Response::back();
            return;
        }

        $buyerCompany = trim($_POST['buyer_company'] ?? '');
        if (empty($buyerCompany)) {
            Session::flash('error', 'Buyer Company / Business Name is required.');
            Response::back();
            return;
        }

        $wastageType = trim($_POST['wastage_type'] ?? '');
        if (empty($wastageType)) {
            Session::flash('error', 'Wastage Category is required.');
            Response::back();
            return;
        }

        $weight = (float)($_POST['weight'] ?? 0.0);
        $ratePerKg = (float)($_POST['rate_per_kg'] ?? 0.0);
        $totalAmountInput = (float)($_POST['total_amount'] ?? 0.0);

        if ($weight <= 0) {
            Session::flash('error', 'Wastage Weight must be greater than 0.');
            Response::back();
            return;
        }

        // Validate cutting batch available wastage balance (excluding current sale)
        $cb = Database::fetchOne("SELECT id, batch_number, name, program_id, wastage_weight FROM cutting_batches WHERE id = ?", [$cuttingBatchId]);
        if (!$cb) {
            Session::flash('error', 'Selected cutting batch not found.');
            Response::back();
            return;
        }

        $soldRow = Database::fetchOne(
            "SELECT COALESCE(SUM(weight), 0.0) as sold_weight FROM wastage_sales WHERE cutting_batch_id = ? AND id != ?",
            [$cuttingBatchId, $saleId]
        );
        $alreadySold = (float)($soldRow['sold_weight'] ?? 0.0);
        $totalWastage = (float)($cb['wastage_weight'] ?? 0.0);
        $availableWastage = max(0.0, $totalWastage - $alreadySold);

        if ($availableWastage <= 0.001) {
            Session::flash('error', "Cutting Batch [{$cb['batch_number']}] wastage is ALREADY FULLY SOLD by other transactions. Cannot update sale.");
            Response::back();
            return;
        }

        if ($weight > $availableWastage + 0.001) {
            Session::flash('error', "Entered weight ({$weight} kg) exceeds available wastage balance ({$availableWastage} kg) for Batch [{$cb['batch_number']}].");
            Response::back();
            return;
        }

        // Validate cutting batch available wastage balance
        $cb = Database::fetchOne("SELECT id, batch_number, name, program_id, wastage_weight FROM cutting_batches WHERE id = ?", [$cuttingBatchId]);
        if (!$cb) {
            Session::flash('error', 'Selected cutting batch not found.');
            Response::back();
            return;
        }

        $soldRow = Database::fetchOne(
            "SELECT COALESCE(SUM(weight), 0.0) as sold_weight FROM wastage_sales WHERE cutting_batch_id = ?",
            [$cuttingBatchId]
        );
        $alreadySold = (float)($soldRow['sold_weight'] ?? 0.0);
        $totalWastage = (float)($cb['wastage_weight'] ?? 0.0);
        $availableWastage = max(0.0, $totalWastage - $alreadySold);

        if ($availableWastage <= 0.001) {
            Session::flash('error', "Cutting Batch [{$cb['batch_number']}] wastage is ALREADY FULLY SOLD ({$alreadySold} kg sold of {$totalWastage} kg total). Cannot record another sale.");
            Response::back();
            return;
        }

        if ($weight > $availableWastage + 0.001) {
            Session::flash('error', "Entered weight ({$weight} kg) exceeds available wastage balance ({$availableWastage} kg) for Batch [{$cb['batch_number']}].");
            Response::back();
            return;
        }

        if ($ratePerKg <= 0 && $totalAmountInput <= 0) {
            Session::flash('error', 'Selling Price & Quantity (Manual Entry) is required. Please enter a valid Selling Rate (₹/kg) or Total Sale Price.');
            Response::back();
            return;
        }

        if ($totalAmountInput <= 0 && $ratePerKg > 0) {
            $totalAmount = round($weight * $ratePerKg, 2);
        } elseif ($ratePerKg <= 0 && $totalAmountInput > 0) {
            $ratePerKg = round($totalAmountInput / $weight, 2);
            $totalAmount = round($totalAmountInput, 2);
        } else {
            $totalAmount = round($totalAmountInput, 2);
            $ratePerKg = round($ratePerKg, 2);
        }

        if (!isset($_POST['paid_amount']) || trim((string)$_POST['paid_amount']) === '') {
            Session::flash('error', 'Amount Received (₹) is required (enter 0 if unpaid).');
            Response::back();
            return;
        }
        $paidAmount = (float)$_POST['paid_amount'];
        if ($paidAmount < 0) {
            Session::flash('error', 'Amount Received cannot be negative.');
            Response::back();
            return;
        }
        if ($paidAmount > $totalAmount) {
            Session::flash('error', 'Amount Received cannot exceed Total Sale Price (₹' . number_format($totalAmount, 2) . ').');
            Response::back();
            return;
        }

        $paymentStatus = trim($_POST['payment_status'] ?? '');
        if (empty($paymentStatus) || !in_array($paymentStatus, ['PAID', 'PARTIAL', 'PENDING'], true)) {
            $paymentStatus = ($paidAmount >= $totalAmount && $totalAmount > 0) ? 'PAID' : (($paidAmount > 0) ? 'PARTIAL' : 'PENDING');
        }

        $paymentMethod = trim($_POST['payment_method'] ?? '');
        if (empty($paymentMethod) || !in_array($paymentMethod, ['CASH', 'UPI', 'BANK_TRANSFER', 'CHEQUE', 'CREDIT'], true)) {
            $paymentMethod = $existing['payment_method'] ?? 'CASH';
        }

        $saleDate = trim($_POST['sale_date'] ?? '');
        if (empty($saleDate)) {
            Session::flash('error', 'Sale Date is required.');
            Response::back();
            return;
        }

        $paymentReference = !empty($_POST['payment_reference']) ? trim($_POST['payment_reference']) : ($existing['payment_reference'] ?? null);
        $invoiceNumber = !empty($_POST['invoice_number']) ? trim($_POST['invoice_number']) : ($existing['invoice_number'] ?? null);
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : ($existing['notes'] ?? null);

        $programId = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        if ($cuttingBatchId && !$programId) {
            $cb = Database::fetchOne("SELECT program_id FROM cutting_batches WHERE id = ?", [$cuttingBatchId]);
            if ($cb && !empty($cb['program_id'])) {
                $programId = (int)$cb['program_id'];
            }
        }

        $photoUrl = $existing['photo_url'];
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/wastage', 'image');
            if ($uploaded) {
                $photoUrl = $uploaded;
            }
        }

        try {
            Database::execute(
                "UPDATE wastage_sales SET
                    buyer_name = ?,
                    buyer_phone = ?,
                    buyer_company = ?,
                    cutting_batch_id = ?,
                    program_id = ?,
                    wastage_type = ?,
                    weight = ?,
                    rate_per_kg = ?,
                    total_amount = ?,
                    paid_amount = ?,
                    payment_status = ?,
                    payment_method = ?,
                    payment_reference = ?,
                    invoice_number = ?,
                    sale_date = ?,
                    photo_url = ?,
                    notes = ?,
                    updated_at = NOW()
                 WHERE id = ?",
                [
                    $buyerName,
                    $buyerPhone,
                    $buyerCompany,
                    $cuttingBatchId,
                    $programId,
                    $wastageType,
                    $weight,
                    $ratePerKg,
                    $totalAmount,
                    $paidAmount,
                    $paymentStatus,
                    $paymentMethod,
                    $paymentReference ?: null,
                    $invoiceNumber ?: null,
                    $saleDate,
                    $photoUrl,
                    $notes ?: null,
                    $saleId
                ]
            );

            AuditService::log('production', 'update', 'wastage_sales', $saleId, $existing, [
                'buyer_name'   => $buyerName,
                'weight'       => $weight,
                'total_amount' => $totalAmount,
                'paid_amount'  => $paidAmount
            ]);

            Session::flash('success', "Wastage sale #{$existing['sale_number']} updated successfully.");
            Response::redirect(url('production/wastage'));
        } catch (Exception $e) {
            Session::flash('error', 'Error updating wastage sale: ' . $e->getMessage());
            Response::back();
        }
    }

    public function deleteWastage(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $saleId = (int)$id;
        $existing = Database::fetchOne("SELECT * FROM wastage_sales WHERE id = ?", [$saleId]);
        if (!$existing) {
            Session::flash('error', 'Wastage sale record not found.');
            Response::back();
            return;
        }

        try {
            Database::execute("DELETE FROM wastage_sales WHERE id = ?", [$saleId]);
            AuditService::log('production', 'delete', 'wastage_sales', $saleId, $existing, null);
            Session::flash('success', "Wastage sale #{$existing['sale_number']} deleted successfully.");
            Response::redirect(url('production/wastage'));
        } catch (Exception $e) {
            Session::flash('error', 'Error deleting wastage sale: ' . $e->getMessage());
            Response::back();
        }
    }

    public function wastageReceipt(string $id): void {
        $saleId = (int)$id;
        $sale = Database::fetchOne(
            "SELECT ws.*, 
                    cb.batch_number as cutting_batch_number,
                    cb.name as cutting_batch_name,
                    pp.program_number,
                    pp.name as program_name,
                    u.full_name as created_by_name
             FROM wastage_sales ws
             LEFT JOIN cutting_batches cb ON ws.cutting_batch_id = cb.id
             LEFT JOIN production_programs pp ON ws.program_id = pp.id
             LEFT JOIN users u ON ws.created_by = u.id
             WHERE ws.id = ?",
            [$saleId]
        );

        if (!$sale) {
            Response::error(404, 'Wastage sale record not found.');
            return;
        }

        Response::json([
            'success' => true,
            'sale'    => $sale
        ]);
    }

    // =========================================================================
    // STITCHING WASTAGE SELLING MANAGEMENT
    // =========================================================================

    public function stitchingWastageList(): void {
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = trim($_GET['payment_status'] ?? '');
        $batchFilter = trim($_GET['stitching_batch_id'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $sql = "SELECT sws.*, 
                       sb.batch_number as stitching_batch_number,
                       sb.name as stitching_batch_name,
                       pp.program_number,
                       pp.name as program_name,
                       u.full_name as created_by_name
                FROM stitching_wastage_sales sws
                LEFT JOIN stitching_batches sb ON sws.stitching_batch_id = sb.id
                LEFT JOIN production_programs pp ON sws.program_id = pp.id
                LEFT JOIN users u ON sws.created_by = u.id
                WHERE 1=1";
        
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                sws.sale_number LIKE ?
                OR sws.buyer_name LIKE ?
                OR sws.buyer_company LIKE ?
                OR sws.buyer_phone LIKE ?
                OR sws.invoice_number LIKE ?
                OR sws.payment_reference LIKE ?
                OR sb.batch_number LIKE ?
                OR pp.program_number LIKE ?
            )";
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s, $s]);
        }

        if ($type !== '') {
            $sql .= " AND sws.waste_type = ?";
            $params[] = $type;
        }

        if ($status !== '') {
            $sql .= " AND sws.payment_status = ?";
            $params[] = $status;
        }

        if ($batchFilter !== '') {
            $sql .= " AND sws.stitching_batch_id = ?";
            $params[] = (int)$batchFilter;
        }

        if ($dateFrom !== '') {
            $sql .= " AND sws.sale_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND sws.sale_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY sws.sale_date DESC, sws.id DESC";
        $sales = Database::fetchAll($sql, $params);

        // Overall summary stats
        $allSales = Database::fetchAll("SELECT quantity, unit_type, total_amount, paid_amount, payment_status, waste_type FROM stitching_wastage_sales");
        $totalQty = (float)array_sum(array_column($allSales, 'quantity'));
        $totalAmount = (float)array_sum(array_column($allSales, 'total_amount'));
        $totalPaid = (float)array_sum(array_column($allSales, 'paid_amount'));
        $totalPending = max(0.0, $totalAmount - $totalPaid);
        $avgRate = $totalQty > 0 ? ($totalAmount / $totalQty) : 0.0;

        $stats = [
            'total_records'    => count($allSales),
            'total_quantity'   => $totalQty,
            'total_amount'     => $totalAmount,
            'total_paid'       => $totalPaid,
            'total_pending'    => $totalPending,
            'avg_rate_per_unit'=> $avgRate,
            'count_paid'       => count(array_filter($allSales, fn($s) => $s['payment_status'] === 'PAID')),
            'count_partial'    => count(array_filter($allSales, fn($s) => $s['payment_status'] === 'PARTIAL')),
            'count_pending'    => count(array_filter($allSales, fn($s) => $s['payment_status'] === 'PENDING')),
        ];

        // Fetch stitching module batches with program and production quantities
        $stitchingBatches = Database::fetchAll("
            SELECT sb.id, sb.batch_number, sb.name, sb.program_id, sb.cutting_batch_id,
                   sb.received_qty, sb.stitched_qty, sb.rejected_qty, sb.rework_qty, sb.total_weight, sb.status,
                   pp.program_number, pp.name as program_name,
                   cb.batch_number as cutting_batch_number,
                   COALESCE(sold.sold_quantity, 0.0) as sold_quantity,
                   COALESCE(sold.sales_count, 0) as sales_count,
                   sold.latest_sale_number,
                   sold.latest_unit_type
            FROM stitching_batches sb
            LEFT JOIN production_programs pp ON sb.program_id = pp.id
            LEFT JOIN cutting_batches cb ON sb.cutting_batch_id = cb.id
            LEFT JOIN (
                SELECT stitching_batch_id, 
                       SUM(quantity) as sold_quantity, 
                       COUNT(*) as sales_count,
                       MAX(sale_number) as latest_sale_number,
                       MAX(unit_type) as latest_unit_type
                FROM stitching_wastage_sales
                WHERE stitching_batch_id IS NOT NULL
                GROUP BY stitching_batch_id
            ) sold ON sb.id = sold.stitching_batch_id
            ORDER BY sb.id DESC
        ");

        $programs = Database::fetchAll("SELECT id, program_number, name FROM production_programs ORDER BY id DESC LIMIT 50");

        Response::view('production.stitching_wastage.index', [
            'pageTitle'        => 'Stitching Scrap & Wastage Selling',
            'activeModule'     => 'production',
            'activeMenu'       => 'stitching-wastage',
            'sales'            => $sales,
            'stats'            => $stats,
            'stitchingBatches' => $stitchingBatches,
            'programs'         => $programs,
            'search'           => $search,
            'type'             => $type,
            'paymentStatus'    => $status,
            'stitchingBatchId' => $batchFilter,
            'dateFrom'         => $dateFrom,
            'dateTo'           => $dateTo,
            'breadcrumb'       => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Stitching Wastage'],
            ],
        ]);
    }

    public function storeStitchingWastage(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $stitchingBatchId = !empty($_POST['stitching_batch_id']) ? (int)$_POST['stitching_batch_id'] : null;
        $buyerName = trim($_POST['buyer_name'] ?? '');
        $buyerPhone = trim($_POST['buyer_phone'] ?? '');
        $buyerCompany = trim($_POST['buyer_company'] ?? '');
        $wasteType = trim($_POST['waste_type'] ?? '');
        $unitType = trim($_POST['unit_type'] ?? 'KG');
        $quantity = (float)($_POST['quantity'] ?? 0.0);
        $ratePerUnit = (float)($_POST['rate_per_unit'] ?? 0.0);
        $saleDate = trim($_POST['sale_date'] ?? '');
        $paymentStatus = trim($_POST['payment_status'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? '');
        $paymentReference = trim($_POST['payment_reference'] ?? '');
        $invoiceNumber = trim($_POST['invoice_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $programId = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;

        // 1. Select Stitching Module Batch / Program is required
        if (empty($stitchingBatchId)) {
            Session::flash('error', 'Select Stitching Module Batch / Program is required. Please choose a Stitching Batch.');
            Response::back();
            return;
        }

        // Validation: Prevent duplicate selling of already sold stitching batch scrap
        $alreadySold = Database::fetchOne(
            "SELECT id, sale_number, quantity, unit_type, sale_date 
             FROM stitching_wastage_sales 
             WHERE stitching_batch_id = ?",
            [$stitchingBatchId]
        );
        if ($alreadySold) {
            $sb = Database::fetchOne("SELECT batch_number, name FROM stitching_batches WHERE id = ?", [$stitchingBatchId]);
            $sbNum = $sb['batch_number'] ?? ('#' . $stitchingBatchId);
            Session::flash('error', "Validation Error: Stitching Batch [{$sbNum}] wastage has already been sold under Sale #{$alreadySold['sale_number']} ({$alreadySold['quantity']} {$alreadySold['unit_type']} on {$alreadySold['sale_date']}). Duplicate sale of the same stitching batch wastage is not allowed.");
            Response::back();
            return;
        }

        // 2. Scrap Buyer / Vendor Name is required
        if (empty($buyerName)) {
            Session::flash('error', 'Scrap Buyer / Vendor Name is required.');
            Response::back();
            return;
        }

        // 3. Buyer Phone / Mobile is required and 10 digits only
        if (empty($buyerPhone) || !preg_match('/^[0-9]{10}$/', $buyerPhone)) {
            Session::flash('error', 'Buyer Phone / Mobile is required and must be a valid 10-digit number.');
            Response::back();
            return;
        }

        // 4. Buyer Company / Business Name is required
        if (empty($buyerCompany)) {
            Session::flash('error', 'Buyer Company / Business Name is required.');
            Response::back();
            return;
        }

        // 5. Stitching Waste Category is required
        if (empty($wasteType)) {
            Session::flash('error', 'Stitching Waste Category is required.');
            Response::back();
            return;
        }

        // 6. Selling Price & Quantity (Manual Entry) is required
        if ($quantity <= 0 || $ratePerUnit <= 0) {
            Session::flash('error', 'Selling Price & Quantity (Manual Entry) is required. Quantity and Rate must be greater than 0.');
            Response::back();
            return;
        }

        // 7. Amount Received (₹) is required
        if (!isset($_POST['paid_amount']) || $_POST['paid_amount'] === '' || (float)$_POST['paid_amount'] < 0) {
            Session::flash('error', 'Amount Received (₹) is required.');
            Response::back();
            return;
        }

        // 8. Sale Date is required
        if (empty($saleDate)) {
            Session::flash('error', 'Sale Date is required.');
            Response::back();
            return;
        }

        if ($stitchingBatchId && !$programId) {
            $sb = Database::fetchOne("SELECT program_id FROM stitching_batches WHERE id = ?", [$stitchingBatchId]);
            if ($sb && !empty($sb['program_id'])) {
                $programId = (int)$sb['program_id'];
            }
        }

        $totalAmount = round($quantity * $ratePerUnit, 2);
        if (!empty($_POST['total_amount']) && (float)$_POST['total_amount'] > 0) {
            $totalAmount = round((float)$_POST['total_amount'], 2);
        }
        $paidAmount = max(0.0, (float)($_POST['paid_amount'] ?? 0.0));
        if ($paidAmount > $totalAmount) {
            $paidAmount = $totalAmount;
        }

        if (empty($paymentStatus) || !in_array($paymentStatus, ['PAID', 'PARTIAL', 'PENDING'], true)) {
            $paymentStatus = ($paidAmount >= $totalAmount && $totalAmount > 0) ? 'PAID' : (($paidAmount > 0) ? 'PARTIAL' : 'PENDING');
        }
        if (empty($paymentMethod) || !in_array($paymentMethod, ['CASH', 'UPI', 'BANK_TRANSFER', 'CHEQUE', 'CREDIT'], true)) {
            $paymentMethod = 'CASH';
        }
        $paymentReference = !empty($_POST['payment_reference']) ? trim($_POST['payment_reference']) : null;
        $invoiceNumber = !empty($_POST['invoice_number']) ? trim($_POST['invoice_number']) : null;
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;

        // Handle photo upload (Cloudinary Cloud Storage)
        $photoUrl = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoUrl = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/stitching_wastage', 'image');
        }

        // Generate sequential sale number e.g. STW-2026-0001
        $count = Database::fetchOne("SELECT COUNT(*) as cnt FROM stitching_wastage_sales");
        $nextSeq = (int)($count['cnt'] ?? 0) + 1;
        $saleNumber = 'STW-' . date('Y') . '-' . sprintf("%04d", $nextSeq);

        $userId = (int)(Session::get('user_id') ?? 1);

        try {
            Database::execute(
                "INSERT INTO stitching_wastage_sales 
                    (sale_number, buyer_name, buyer_phone, buyer_company, stitching_batch_id, program_id, waste_type, unit_type, quantity, rate_per_unit, total_amount, paid_amount, payment_status, payment_method, payment_reference, invoice_number, sale_date, photo_url, notes, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $saleNumber,
                    $buyerName,
                    $buyerPhone ?: null,
                    $buyerCompany ?: null,
                    $stitchingBatchId,
                    $programId,
                    $wasteType,
                    $unitType,
                    $quantity,
                    $ratePerUnit,
                    $totalAmount,
                    $paidAmount,
                    $paymentStatus,
                    $paymentMethod,
                    $paymentReference ?: null,
                    $invoiceNumber ?: null,
                    $saleDate,
                    $photoUrl,
                    $notes ?: null,
                    $userId
                ]
            );
            $saleId = (int)Database::lastInsertId();

            AuditService::log('production', 'create', 'stitching_wastage_sales', $saleId, null, [
                'sale_number'  => $saleNumber,
                'buyer_name'   => $buyerName,
                'quantity'     => $quantity,
                'total_amount' => $totalAmount
            ]);

            Session::flash('success', "Stitching wastage sale {$saleNumber} recorded successfully for ₹" . number_format($totalAmount, 2));
            Response::redirect(url('production/stitching-wastage'));
        } catch (Exception $e) {
            Session::flash('error', 'Error recording stitching wastage sale: ' . $e->getMessage());
            Response::back();
        }
    }

    public function updateStitchingWastage(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $saleId = (int)$id;
        $existing = Database::fetchOne("SELECT * FROM stitching_wastage_sales WHERE id = ?", [$saleId]);
        if (!$existing) {
            Session::flash('error', 'Stitching wastage sale record not found.');
            Response::back();
            return;
        }

        $stitchingBatchId = !empty($_POST['stitching_batch_id']) ? (int)$_POST['stitching_batch_id'] : (int)($existing['stitching_batch_id'] ?? 0);
        $buyerName = trim($_POST['buyer_name'] ?? $existing['buyer_name']);
        $buyerPhone = trim($_POST['buyer_phone'] ?? '');
        $buyerCompany = trim($_POST['buyer_company'] ?? '');
        $wasteType = trim($_POST['waste_type'] ?? $existing['waste_type']);
        $unitType = trim($_POST['unit_type'] ?? $existing['unit_type']);
        $quantity = (float)($_POST['quantity'] ?? $existing['quantity']);
        $ratePerUnit = (float)($_POST['rate_per_unit'] ?? $existing['rate_per_unit']);
        $saleDate = trim($_POST['sale_date'] ?? $existing['sale_date']);
        $paymentStatus = trim($_POST['payment_status'] ?? $existing['payment_status']);
        $paymentMethod = trim($_POST['payment_method'] ?? $existing['payment_method']);
        $paymentReference = trim($_POST['payment_reference'] ?? '');
        $invoiceNumber = trim($_POST['invoice_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $programId = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;

        // 1. Select Stitching Module Batch / Program is required
        if (empty($stitchingBatchId)) {
            Session::flash('error', 'Select Stitching Module Batch / Program is required. Please choose a Stitching Batch.');
            Response::back();
            return;
        }

        // Validation: Prevent updating to another stitching batch whose scrap has already been sold
        $alreadySold = Database::fetchOne(
            "SELECT id, sale_number, quantity, unit_type, sale_date 
             FROM stitching_wastage_sales 
             WHERE stitching_batch_id = ? AND id != ?",
            [$stitchingBatchId, $saleId]
        );
        if ($alreadySold) {
            $sb = Database::fetchOne("SELECT batch_number, name FROM stitching_batches WHERE id = ?", [$stitchingBatchId]);
            $sbNum = $sb['batch_number'] ?? ('#' . $stitchingBatchId);
            Session::flash('error', "Validation Error: Stitching Batch [{$sbNum}] wastage has already been sold under Sale #{$alreadySold['sale_number']} ({$alreadySold['quantity']} {$alreadySold['unit_type']} on {$alreadySold['sale_date']}). Duplicate sale of the same stitching batch wastage is not allowed.");
            Response::back();
            return;
        }

        // 2. Scrap Buyer / Vendor Name is required
        if (empty($buyerName)) {
            Session::flash('error', 'Scrap Buyer / Vendor Name is required.');
            Response::back();
            return;
        }

        // 3. Buyer Phone / Mobile is required and 10 digits only
        if (empty($buyerPhone) || !preg_match('/^[0-9]{10}$/', $buyerPhone)) {
            Session::flash('error', 'Buyer Phone / Mobile is required and must be a valid 10-digit number.');
            Response::back();
            return;
        }

        // 4. Buyer Company / Business Name is required
        if (empty($buyerCompany)) {
            Session::flash('error', 'Buyer Company / Business Name is required.');
            Response::back();
            return;
        }

        // 5. Stitching Waste Category is required
        if (empty($wasteType)) {
            Session::flash('error', 'Stitching Waste Category is required.');
            Response::back();
            return;
        }

        // 6. Selling Price & Quantity (Manual Entry) is required
        if ($quantity <= 0 || $ratePerUnit <= 0) {
            Session::flash('error', 'Selling Price & Quantity (Manual Entry) is required. Quantity and Rate must be greater than 0.');
            Response::back();
            return;
        }

        // 7. Amount Received (₹) is required
        if (!isset($_POST['paid_amount']) || $_POST['paid_amount'] === '' || (float)$_POST['paid_amount'] < 0) {
            Session::flash('error', 'Amount Received (₹) is required.');
            Response::back();
            return;
        }

        // 8. Sale Date is required
        if (empty($saleDate)) {
            Session::flash('error', 'Sale Date is required.');
            Response::back();
            return;
        }

        if ($stitchingBatchId && !$programId) {
            $sb = Database::fetchOne("SELECT program_id FROM stitching_batches WHERE id = ?", [$stitchingBatchId]);
            if ($sb && !empty($sb['program_id'])) {
                $programId = (int)$sb['program_id'];
            }
        }

        $totalAmount = round($quantity * $ratePerUnit, 2);
        if (!empty($_POST['total_amount']) && (float)$_POST['total_amount'] > 0) {
            $totalAmount = round((float)$_POST['total_amount'], 2);
        }
        $paidAmount = max(0.0, (float)($_POST['paid_amount'] ?? 0.0));
        if ($paidAmount > $totalAmount) {
            $paidAmount = $totalAmount;
        }

        if (empty($paymentStatus) || !in_array($paymentStatus, ['PAID', 'PARTIAL', 'PENDING'], true)) {
            $paymentStatus = ($paidAmount >= $totalAmount && $totalAmount > 0) ? 'PAID' : (($paidAmount > 0) ? 'PARTIAL' : 'PENDING');
        }
        if (empty($paymentMethod) || !in_array($paymentMethod, ['CASH', 'UPI', 'BANK_TRANSFER', 'CHEQUE', 'CREDIT'], true)) {
            $paymentMethod = $existing['payment_method'] ?? 'CASH';
        }
        $paymentReference = !empty($_POST['payment_reference']) ? trim($_POST['payment_reference']) : ($existing['payment_reference'] ?? null);
        $invoiceNumber = !empty($_POST['invoice_number']) ? trim($_POST['invoice_number']) : ($existing['invoice_number'] ?? null);
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : ($existing['notes'] ?? null);

        $photoUrl = $existing['photo_url'];
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploaded = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/stitching_wastage', 'image');
            if ($uploaded) {
                $photoUrl = $uploaded;
            }
        }

        try {
            Database::execute(
                "UPDATE stitching_wastage_sales SET
                    buyer_name = ?,
                    buyer_phone = ?,
                    buyer_company = ?,
                    stitching_batch_id = ?,
                    program_id = ?,
                    waste_type = ?,
                    unit_type = ?,
                    quantity = ?,
                    rate_per_unit = ?,
                    total_amount = ?,
                    paid_amount = ?,
                    payment_status = ?,
                    payment_method = ?,
                    payment_reference = ?,
                    invoice_number = ?,
                    sale_date = ?,
                    photo_url = ?,
                    notes = ?,
                    updated_at = NOW()
                 WHERE id = ?",
                [
                    $buyerName,
                    $buyerPhone ?: null,
                    $buyerCompany ?: null,
                    $stitchingBatchId,
                    $programId,
                    $wasteType,
                    $unitType,
                    $quantity,
                    $ratePerUnit,
                    $totalAmount,
                    $paidAmount,
                    $paymentStatus,
                    $paymentMethod,
                    $paymentReference ?: null,
                    $invoiceNumber ?: null,
                    $saleDate,
                    $photoUrl,
                    $notes ?: null,
                    $saleId
                ]
            );

            AuditService::log('production', 'update', 'stitching_wastage_sales', $saleId, $existing, [
                'buyer_name'   => $buyerName,
                'quantity'     => $quantity,
                'total_amount' => $totalAmount,
                'paid_amount'  => $paidAmount
            ]);

            Session::flash('success', "Stitching wastage sale #{$existing['sale_number']} updated successfully.");
            Response::redirect(url('production/stitching-wastage'));
        } catch (Exception $e) {
            Session::flash('error', 'Error updating stitching wastage sale: ' . $e->getMessage());
            Response::back();
        }
    }

    public function deleteStitchingWastage(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $saleId = (int)$id;
        $existing = Database::fetchOne("SELECT * FROM stitching_wastage_sales WHERE id = ?", [$saleId]);
        if (!$existing) {
            Session::flash('error', 'Stitching wastage sale record not found.');
            Response::back();
            return;
        }

        try {
            Database::execute("DELETE FROM stitching_wastage_sales WHERE id = ?", [$saleId]);
            AuditService::log('production', 'delete', 'stitching_wastage_sales', $saleId, $existing, null);
            Session::flash('success', "Stitching wastage sale #{$existing['sale_number']} deleted successfully.");
            Response::redirect(url('production/stitching-wastage'));
        } catch (Exception $e) {
            Session::flash('error', 'Error deleting stitching wastage sale: ' . $e->getMessage());
            Response::back();
        }
    }

    public function stitchingWastageReceipt(string $id): void {
        $saleId = (int)$id;
        $sale = Database::fetchOne(
            "SELECT sws.*, 
                    sb.batch_number as stitching_batch_number,
                    sb.name as stitching_batch_name,
                    pp.program_number,
                    pp.name as program_name,
                    u.full_name as created_by_name
             FROM stitching_wastage_sales sws
             LEFT JOIN stitching_batches sb ON sws.stitching_batch_id = sb.id
             LEFT JOIN production_programs pp ON sws.program_id = pp.id
             LEFT JOIN users u ON sws.created_by = u.id
             WHERE sws.id = ?",
            [$saleId]
        );

        if (!$sale) {
            Response::error(404, 'Stitching wastage sale record not found.');
            return;
        }

        Response::json([
            'success' => true,
            'sale'    => $sale
        ]);
    }

    // =========================================================================
    // PRODUCTION EXPENSES & ASSETS MANAGEMENT (Unit 1 & Unit 2)
    // =========================================================================

    public function expensesList(): void {
        $search = trim($_GET['search'] ?? '');
        $unitFilter = trim($_GET['production_unit'] ?? '');
        $categoryFilter = trim($_GET['category'] ?? '');
        $statusFilter = trim($_GET['payment_status'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $sql = "SELECT pe.*,
                       pp.program_number,
                       pp.name as program_name,
                       u.full_name as created_by_name
                FROM production_expenses pe
                LEFT JOIN production_programs pp ON pe.program_id = pp.id
                LEFT JOIN users u ON pe.created_by = u.id
                WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                pe.expense_number LIKE ?
                OR pe.item_name LIKE ?
                OR pe.vendor_name LIKE ?
                OR pe.vendor_company LIKE ?
                OR pe.invoice_number LIKE ?
                OR pe.asset_tag_or_serial LIKE ?
                OR pe.notes LIKE ?
            )";
            $s = '%' . $search . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s, $s, $s]);
        }

        if ($unitFilter !== '' && $unitFilter !== 'ALL') {
            $sql .= " AND pe.production_unit = ?";
            $params[] = $unitFilter;
        }

        if ($categoryFilter !== '' && $categoryFilter !== 'ALL') {
            $sql .= " AND pe.category = ?";
            $params[] = $categoryFilter;
        }

        if ($statusFilter !== '' && $statusFilter !== 'ALL') {
            $sql .= " AND pe.payment_status = ?";
            $params[] = $statusFilter;
        }

        if ($dateFrom !== '') {
            $sql .= " AND pe.expense_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $sql .= " AND pe.expense_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY pe.expense_date DESC, pe.id DESC";
        $expenses = Database::fetchAll($sql, $params);

        $productionUnitOptions = [];
        foreach (ProductionUnit::getAll() as $unit) {
            $code = trim((string)($unit['code'] ?? ''));
            if ($code !== '') {
                $productionUnitOptions[$code] = trim((string)$unit['name']);
            }
        }

        $savedProductionUnits = Database::fetchAll(
            "SELECT DISTINCT production_unit
             FROM production_expenses
             WHERE production_unit IS NOT NULL AND TRIM(production_unit) != ''
             ORDER BY production_unit ASC"
        );
        $legacyUnitLabels = [
            'PRODUCTION_1' => 'Production Unit 1',
            'PRODUCTION_2' => 'Production Unit 2',
            'BOTH_UNITS'   => 'Both Units / Common',
            'GENERAL'      => 'General Facility',
        ];
        foreach ($savedProductionUnits as $savedUnit) {
            $value = trim((string)$savedUnit['production_unit']);
            if ($value !== '' && !isset($productionUnitOptions[$value])) {
                $productionUnitOptions[$value] = $legacyUnitLabels[$value] ?? ucwords(strtolower(str_replace(['_', '-'], ' ', $value)));
            }
        }

        $savedExpenseCategories = Database::fetchAll(
            "SELECT category AS value
             FROM production_expenses
             WHERE category IS NOT NULL AND TRIM(category) != ''
             UNION
             SELECT category AS value
             FROM production_expense_items
             WHERE category IS NOT NULL AND TRIM(category) != ''
             ORDER BY value ASC"
        );
        $expenseCategoryOptions = [];
        $knownCategoryLabels = [
            'MACHINERY_ASSET'     => 'Machinery & Equipment',
            'SEWING_MATERIALS'    => 'Sewing Materials & Trims',
            'MAINTENANCE_REPAIR'  => 'Maintenance & Repairs',
            'OPERATIONAL_UTILITY' => 'Operations & Utilities',
            'OTHER'               => 'Other Expenses',
            'GENERAL'             => 'General',
        ];
        foreach ($savedExpenseCategories as $savedCategory) {
            $value = trim((string)$savedCategory['value']);
            if ($value !== '') {
                $expenseCategoryOptions[$value] = $knownCategoryLabels[$value] ?? ucwords(strtolower(str_replace(['_', '-'], ' ', $value)));
            }
        }

        // Overall stats
        $allExpenses = Database::fetchAll("SELECT production_unit, category, total_cost, paid_amount, payment_status FROM production_expenses");
        $totalCost = (float)array_sum(array_column($allExpenses, 'total_cost'));
        $totalPaid = (float)array_sum(array_column($allExpenses, 'paid_amount'));
        $totalPending = max(0.0, $totalCost - $totalPaid);

        $unit1Cost = (float)array_sum(array_column(array_filter($allExpenses, fn($e) => $e['production_unit'] === 'PRODUCTION_1'), 'total_cost'));
        $unit2Cost = (float)array_sum(array_column(array_filter($allExpenses, fn($e) => $e['production_unit'] === 'PRODUCTION_2'), 'total_cost'));
        $bothUnitsCost = (float)array_sum(array_column(array_filter($allExpenses, fn($e) => in_array($e['production_unit'], ['BOTH_UNITS', 'GENERAL'])), 'total_cost'));

        $machineryCost = (float)array_sum(array_column(array_filter($allExpenses, fn($e) => $e['category'] === 'MACHINERY_ASSET'), 'total_cost'));
        $sewingMaterialsCost = (float)array_sum(array_column(array_filter($allExpenses, fn($e) => $e['category'] === 'SEWING_MATERIALS'), 'total_cost'));
        $maintenanceCost = (float)array_sum(array_column(array_filter($allExpenses, fn($e) => $e['category'] === 'MAINTENANCE_REPAIR'), 'total_cost'));
        $operationsCost = (float)array_sum(array_column(array_filter($allExpenses, fn($e) => $e['category'] === 'OPERATIONAL_UTILITY'), 'total_cost'));

        $stats = [
            'total_records'          => count($allExpenses),
            'total_cost'             => $totalCost,
            'total_paid'             => $totalPaid,
            'total_pending'          => $totalPending,
            'unit1_cost'             => $unit1Cost,
            'unit2_cost'             => $unit2Cost,
            'both_units_cost'        => $bothUnitsCost,
            'machinery_cost'         => $machineryCost,
            'sewing_materials_cost'  => $sewingMaterialsCost,
            'maintenance_cost'       => $maintenanceCost,
            'operations_cost'        => $operationsCost,
            'count_paid'             => count(array_filter($allExpenses, fn($e) => $e['payment_status'] === 'PAID')),
            'count_pending'          => count(array_filter($allExpenses, fn($e) => $e['payment_status'] === 'PENDING')),
            'count_partial'          => count(array_filter($allExpenses, fn($e) => $e['payment_status'] === 'PARTIAL')),
        ];

        $programs = Database::fetchAll("SELECT id, program_number, name FROM production_programs ORDER BY id DESC LIMIT 50");
        $expenseItems = Database::fetchAll("SELECT id, name, category, unit_type, default_rate FROM production_expense_items ORDER BY name ASC");

        Response::view('production.expenses.index', [
            'pageTitle'      => 'Production Expenses & Assets Management',
            'activeModule'   => 'production',
            'activeMenu'     => 'production-expenses',
            'expenses'       => $expenses,
            'stats'          => $stats,
            'programs'       => $programs,
            'expenseItems'   => $expenseItems,
            'productionUnitOptions' => $productionUnitOptions,
            'expenseCategoryOptions' => $expenseCategoryOptions,
            'search'         => $search,
            'productionUnit' => $unitFilter,
            'category'       => $categoryFilter,
            'paymentStatus'  => $statusFilter,
            'dateFrom'       => $dateFrom,
            'dateTo'         => $dateTo,
            'breadcrumb'     => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => 'Expenses & Assets'],
            ],
        ]);
    }

    public function storeExpense(): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $itemName = trim($_POST['item_name'] ?? '');
        $quantity = max(0.01, (float)($_POST['quantity'] ?? 1.0));
        $unitCost = max(0.0, (float)($_POST['unit_cost'] ?? 0.0));
        $totalCost = max(0.0, (float)($_POST['total_cost'] ?? round($quantity * $unitCost, 2)));
        if ($totalCost <= 0.0 && $quantity > 0 && $unitCost > 0) {
            $totalCost = round($quantity * $unitCost, 2);
        }

        if (empty($itemName)) {
            Session::flash('error', 'Expense Type is required.');
            Response::back();
            return;
        }

        // Auto-register item into production_expense_items
        try {
            $existingItem = Database::fetchOne("SELECT id FROM production_expense_items WHERE name = ?", [$itemName]);
            if (!$existingItem) {
                Database::execute(
                    "INSERT INTO production_expense_items (name, category, unit_type, default_rate) VALUES (?, 'GENERAL', 'PCS', ?)",
                    [$itemName, $unitCost]
                );
            }
        } catch (\Throwable $e) {
            // Ignore duplicate
        }

        $productionUnit = trim($_POST['production_unit'] ?? 'PRODUCTION_1');
        $category = 'GENERAL';
        $unitType = 'PCS';
        $vendorName = 'Direct';
        $paidAmount = $totalCost;
        $paymentStatus = 'PAID';
        $paymentMethod = 'CASH';
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));

        // Generate sequential expense number e.g. EXP-2026-0001
        $count = Database::fetchOne("SELECT COUNT(*) as cnt FROM production_expenses");
        $nextSeq = (int)($count['cnt'] ?? 0) + 1;
        $expenseNumber = 'EXP-' . date('Y') . '-' . sprintf("%04d", $nextSeq);

        $userId = (int)(Session::get('user_id') ?? 1);

        // Handle photo / receipt / bill image upload
        $photoUrl = null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts, true)) {
                if (class_exists('CloudinaryService')) {
                    $photoUrl = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/production/expenses', 'auto');
                }
                if (!$photoUrl) {
                    $uploadDir = BASE_PATH . '/public/uploads/production_expenses/';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0777, true);
                    }
                    $filename = 'exp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                        $photoUrl = 'uploads/production_expenses/' . $filename;
                    }
                }
            }
        }

        try {
            Database::execute(
                "INSERT INTO production_expenses
                    (expense_number, production_unit, category, item_name,
                     quantity, unit_type, unit_cost, total_cost, paid_amount, payment_status,
                     payment_method, vendor_name, expense_date, photo_url, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $expenseNumber,
                    $productionUnit,
                    $category,
                    $itemName,
                    $quantity,
                    $unitType,
                    $unitCost,
                    $totalCost,
                    $paidAmount,
                    $paymentStatus,
                    $paymentMethod,
                    $vendorName,
                    $expenseDate,
                    $photoUrl,
                    $userId
                ]
            );

            $expenseId = (int)Database::lastInsertId();

            Session::flash('success', "Expense {$expenseNumber} ({$itemName} - Qty: {$quantity}, Price: ₹" . number_format($totalCost, 2) . ") recorded successfully.");
            Response::redirect(url('production/expenses'));
        } catch (\Exception $e) {
            Session::flash('error', 'Error recording production expense: ' . $e->getMessage());
            Response::back();
        }
    }

    public function updateExpense(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $expenseId = (int)$id;
        $existing = Database::fetchOne("SELECT * FROM production_expenses WHERE id = ?", [$expenseId]);
        if (!$existing) {
            Session::flash('error', 'Expense record not found.');
            Response::back();
            return;
        }

        $itemName = trim($_POST['item_name'] ?? $existing['item_name']);
        $quantity = max(0.01, (float)($_POST['quantity'] ?? $existing['quantity']));
        $unitCost = max(0.0, (float)($_POST['unit_cost'] ?? $existing['unit_cost']));
        $totalCost = max(0.0, (float)($_POST['total_cost'] ?? round($quantity * $unitCost, 2)));
        if ($totalCost <= 0.0 && $quantity > 0 && $unitCost > 0) {
            $totalCost = round($quantity * $unitCost, 2);
        }

        if (empty($itemName)) {
            Session::flash('error', 'Expense Type is required.');
            Response::back();
            return;
        }

        // Handle photo / receipt / bill image upload
        $photoUrl = $existing['photo_url'] ?? null;
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts, true)) {
                $newPhoto = null;
                if (class_exists('CloudinaryService')) {
                    $newPhoto = CloudinaryService::upload($_FILES['photo'], 'grm_inventory/production/expenses', 'auto');
                }
                if (!$newPhoto) {
                    $uploadDir = BASE_PATH . '/public/uploads/production_expenses/';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0777, true);
                    }
                    $filename = 'exp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                        $newPhoto = 'uploads/production_expenses/' . $filename;
                    }
                }
                if ($newPhoto) {
                    $photoUrl = $newPhoto;
                }
            }
        }

        try {
            Database::execute(
                "UPDATE production_expenses SET
                    item_name = ?,
                    quantity = ?,
                    unit_cost = ?,
                    total_cost = ?,
                    paid_amount = ?,
                    photo_url = ?,
                    updated_at = NOW()
                 WHERE id = ?",
                [
                    $itemName,
                    $quantity,
                    $unitCost,
                    $totalCost,
                    $totalCost,
                    $photoUrl,
                    $expenseId
                ]
            );

            Session::flash('success', "Production expense #{$existing['expense_number']} updated successfully.");
            Response::redirect(url('production/expenses'));
        } catch (\Exception $e) {
            Session::flash('error', 'Error updating expense record: ' . $e->getMessage());
            Response::back();
        }
    }

    public function deleteExpense(string $id): void {
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token. Please try again.');
            Response::back();
            return;
        }

        $expenseId = (int)$id;
        $existing = Database::fetchOne("SELECT * FROM production_expenses WHERE id = ?", [$expenseId]);
        if (!$existing) {
            Session::flash('error', 'Expense record not found.');
            Response::back();
            return;
        }

        try {
            Database::execute("DELETE FROM production_expenses WHERE id = ?", [$expenseId]);
            AuditService::log('production', 'delete', 'production_expenses', $expenseId, $existing, null);
            Session::flash('success', "Production expense #{$existing['expense_number']} deleted successfully.");
            Response::redirect(url('production/expenses'));
        } catch (Exception $e) {
            Session::flash('error', 'Error deleting expense record: ' . $e->getMessage());
            Response::back();
        }
    }

    public function expenseReceipt(string $id): void {
        $expenseId = (int)$id;
        $expense = Database::fetchOne(
            "SELECT pe.*,
                    pp.program_number,
                    pp.name as program_name,
                    u.full_name as created_by_name
             FROM production_expenses pe
             LEFT JOIN production_programs pp ON pe.program_id = pp.id
             LEFT JOIN users u ON pe.created_by = u.id
             WHERE pe.id = ?",
            [$expenseId]
        );

        if (!$expense) {
            Response::error(404, 'Expense record not found.');
            return;
        }

        Response::json([
            'success' => true,
            'expense' => $expense
        ]);
    }

    public function ajaxQuickAddExpenseItem(): void {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'GENERAL');
        $unitType = trim($_POST['unit_type'] ?? 'PCS');
        $defaultRate = max(0.0, (float)($_POST['default_rate'] ?? 0.0));

        if (empty($name)) {
            Response::json(['success' => false, 'message' => 'Expense type name is required.'], 400);
            return;
        }

        $existing = Database::fetchOne("SELECT id, name, category, unit_type, default_rate FROM production_expense_items WHERE name = ?", [$name]);
        if ($existing) {
            Response::json([
                'success' => true,
                'message' => 'Expense type already exists.',
                'item' => $existing,
                'is_existing' => true
            ]);
            return;
        }

        Database::execute(
            "INSERT INTO production_expense_items (name, category, unit_type, default_rate) VALUES (?, ?, ?, ?)",
            [$name, $category, $unitType, $defaultRate]
        );
        $id = (int)Database::lastInsertId();

        Response::json([
            'success' => true,
            'message' => 'Expense type created successfully.',
            'item' => [
                'id' => $id,
                'name' => $name,
                'category' => $category,
                'unit_type' => $unitType,
                'default_rate' => $defaultRate
            ],
            'is_existing' => false
        ]);
    }

    public function ajaxGetExpenseItems(): void {
        $items = Database::fetchAll("SELECT id, name, category, unit_type, default_rate FROM production_expense_items ORDER BY name ASC");
        Response::json(['success' => true, 'items' => $items]);
    }

    public function viewDamage(string $id): void {
        $damage = Database::fetchOne(
            "SELECT rd.*, s.name AS seller_name, s.company AS seller_company,
                    sr.roll_number, sr.color AS roll_color, sr.design_name AS roll_design,
                    u_rep.full_name AS reported_by_name, u_res.full_name AS resolved_by_name,
                    repl_sr.roll_number AS replacement_roll_code
             FROM roll_damages rd
             JOIN sellers s ON rd.seller_id = s.id
             JOIN seller_rolls sr ON rd.seller_roll_id = sr.id
             LEFT JOIN users u_rep ON rd.reported_by = u_rep.id
             LEFT JOIN users u_res ON rd.resolved_by = u_res.id
             LEFT JOIN seller_rolls repl_sr ON rd.replacement_roll_id = repl_sr.id
             WHERE rd.id = ?",
            [(int)$id]
        );
        if (!$damage) {
            Response::error(404, 'Damage record not found.');
            return;
        }

        $this->renderProductionRecord(
            'Damage Record ' . ($damage['damage_number'] ?? ('#' . $damage['id'])),
            'damages',
            'Damages',
            url('production/damages'),
            [
                'Supplier'          => $damage['seller_name'] ?? '—',
                'Company'           => $damage['seller_company'] ?? '—',
                'Roll Number'       => $damage['roll_number'] ?? '—',
                'Color'             => $damage['roll_color'] ?? '—',
                'Design'            => $damage['roll_design'] ?? '—',
                'Reason'            => $damage['damage_reason'] ?? '—',
                'Notes'             => $damage['defect_notes'] ?? '—',
                'Status'            => $damage['status'] ?? '—',
                'Resolution'        => $damage['resolution_type'] ?? '—',
                'Debit Note'        => $damage['debit_note_number'] ?? '—',
                'Replacement Roll'  => $damage['replacement_roll_code'] ?? ($damage['replacement_roll_number'] ?? '—'),
                'Reported By'       => $damage['reported_by_name'] ?? '—',
                'Resolved By'       => $damage['resolved_by_name'] ?? '—',
                'Created At'        => $damage['created_at'] ?? '—',
            ],
            [],
            [],
            'Damage Details'
        );
    }

    public function viewCuttingBatch(string $id): void {
        $batch = CuttingBatch::find((int)$id);
        if (!$batch) {
            Response::error(404, 'Cutting batch not found.');
            return;
        }

        $items = CuttingBatch::getItems((int)$id);
        $this->renderProductionRecord(
            'Cutting Batch ' . ($batch['batch_number'] ?? ('#' . $batch['id'])),
            'cutting',
            'Cutting',
            url('production/cutting'),
            [
                'Name'            => $batch['name'] ?? '—',
                'Batch Number'    => $batch['batch_number'] ?? '—',
                'Program'         => $batch['program_number'] ?? ($batch['program_name'] ?? '—'),
                'Product'         => $batch['product_name'] ?? '—',
                'Production Unit' => $batch['unit_name'] ?? '—',
                'Operator'        => $batch['operator_name'] ?? '—',
                'Status'          => $batch['status'] ?? '—',
                'Planned Quantity'=> number_format((int)($batch['planned_qty'] ?? 0)),
                'Cut Quantity'    => number_format((int)($batch['cut_qty'] ?? 0)),
                'Rejected Quantity'=> number_format((int)($batch['rejected_qty'] ?? 0)),
                'Wastage Quantity' => number_format((float)($batch['wastage_qty'] ?? 0), 2),
                'Created At'      => $batch['created_at'] ?? '—',
            ],
            $items,
            [
                'Catalog'   => 'catalog_hierarchy',
                'Size'      => 'size_name',
                'Color'     => 'color_name',
                'Planned'   => 'planned_qty',
                'Cut'       => 'cut_qty',
                'Rejected'  => 'rejected_qty',
                'Wastage'   => 'wastage_qty',
            ],
            'Cut Items'
        );
    }

    public function viewStitchingBatch(string $id): void {
        $batch = StitchingBatch::find((int)$id);
        if (!$batch) {
            Response::error(404, 'Stitching batch not found.');
            return;
        }

        $items = StitchingBatch::getItems((int)$id);
        $this->renderProductionRecord(
            'Stitching Batch ' . ($batch['batch_number'] ?? ('#' . $batch['id'])),
            'stitching',
            'Stitching',
            url('production/stitching'),
            [
                'Name'             => $batch['name'] ?? '—',
                'Batch Number'     => $batch['batch_number'] ?? '—',
                'Program'          => $batch['program_number'] ?? ($batch['program_name'] ?? '—'),
                'Cutting Batch'    => $batch['cutting_batch_number'] ?? ($batch['cutting_name'] ?? '—'),
                'Product'          => $batch['product_name'] ?? '—',
                'Worker'           => $batch['worker_name'] ?? '—',
                'Status'           => $batch['status'] ?? '—',
                'Received Quantity'=> number_format((int)($batch['received_qty'] ?? 0)),
                'Stitched Quantity'=> number_format((int)($batch['stitched_qty'] ?? 0)),
                'Rejected Quantity'=> number_format((int)($batch['rejected_qty'] ?? 0)),
                'Rework Quantity'  => number_format((int)($batch['rework_qty'] ?? 0)),
                'Total Weight'     => number_format((float)($batch['total_weight'] ?? 0), 2) . ' kg',
                'Created At'       => $batch['created_at'] ?? '—',
            ],
            $items,
            [
                'Catalog' => 'catalog_hierarchy',
                'Size'    => 'display_size',
                'Color'   => 'display_color',
                'Received'=> 'received_qty',
                'Stitched'=> 'stitched_qty',
                'Rejected'=> 'rejected_qty',
                'Rework'  => 'rework_qty',
                'Weight'  => 'weight',
            ],
            'Stitching Items'
        );
    }

    public function viewLabelBatch(string $id): void {
        $batch = LabelBatch::find((int)$id);
        if (!$batch) {
            Response::error(404, 'Label batch not found.');
            return;
        }

        $items = LabelBatch::getItems((int)$id);
        $this->renderProductionRecord(
            'Label Batch ' . ($batch['batch_number'] ?? ('#' . $batch['id'])),
            'labels',
            'Labels',
            url('production/labels'),
            [
                'Batch Number'      => $batch['batch_number'] ?? '—',
                'Program'           => $batch['program_number'] ?? ($batch['program_name'] ?? '—'),
                'Stitching Batch'   => $batch['stitching_batch_number'] ?? ($batch['stitching_name'] ?? '—'),
                'Product'           => $batch['product_name'] ?? '—',
                'Required Labels'   => number_format((int)($batch['required_qty'] ?? 0)),
                'Printed'           => number_format((int)($batch['printed_qty'] ?? 0)),
                'Used / Applied'    => number_format((int)($batch['used_qty'] ?? 0)),
                'Damaged'           => number_format((int)($batch['damaged_qty'] ?? 0)),
                'Status'            => $batch['status'] ?? '—',
                'Created At'        => $batch['created_at'] ?? '—',
            ],
            $items,
            [
                'Catalog' => 'category_name',
                'Size'    => 'display_size',
                'Color'   => 'display_color',
                'SKU'     => 'sku',
                'Required'=> 'required_qty',
                'Printed' => 'printed_qty',
                'Used'    => 'used_qty',
                'Damaged' => 'damaged_qty',
            ],
            'Label Items'
        );
    }

    public function viewQCBatch(string $id): void {
        $batch = QCBatch::find((int)$id);
        if (!$batch) {
            Response::error(404, 'QC batch not found.');
            return;
        }

        $items = QCBatch::getItems((int)$id);
        $this->renderProductionRecord(
            'QC Batch #' . $batch['id'],
            'qc',
            'Quality Control',
            url('production/qc'),
            [
                'Program'         => $batch['program_number'] ?? ($batch['program_name'] ?? '—'),
                'Product'         => $batch['product_name'] ?? '—',
                'Inspector'       => $batch['inspector_name'] ?? '—',
                'Status'          => $batch['status'] ?? '—',
                'Total Quantity'  => number_format((int)($batch['total_qty'] ?? 0)),
                'Passed'          => number_format((int)($batch['passed_qty'] ?? 0)),
                'Rejected'        => number_format((int)($batch['rejected_qty'] ?? 0)),
                'Rework'          => number_format((int)($batch['rework_qty'] ?? 0)),
                'Created At'      => $batch['created_at'] ?? '—',
            ],
            $items,
            [
                'Catalog'   => 'catalog_hierarchy',
                'Size'      => 'display_size',
                'Color'     => 'display_color',
                'Inspected' => 'inspected_qty',
                'Passed'    => 'passed_qty',
                'Rejected'  => 'rejected_qty',
                'Rework'    => 'rework_qty',
            ],
            'Inspection Items'
        );
    }

    private function renderProductionRecord(
        string $recordTitle,
        string $activeMenu,
        string $sectionTitle,
        string $backUrl,
        array $fields,
        array $items,
        array $itemColumns,
        string $itemsTitle
    ): void {
        Response::view('production.record_view', [
            'pageTitle'    => $recordTitle,
            'activeModule' => 'production',
            'activeMenu'   => $activeMenu,
            'breadcrumb'   => [
                ['label' => 'Production', 'url' => url('production/dashboard')],
                ['label' => $sectionTitle, 'url' => $backUrl],
                ['label' => 'Details'],
            ],
            'recordTitle'  => $recordTitle,
            'backUrl'      => $backUrl,
            'fields'       => $fields,
            'items'        => $items,
            'itemColumns'  => $itemColumns,
            'itemsTitle'   => $itemsTitle,
        ]);
    }
}
