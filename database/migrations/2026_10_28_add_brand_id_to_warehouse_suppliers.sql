ALTER TABLE `warehouse_suppliers`
    ADD COLUMN IF NOT EXISTS `brand_id` INT UNSIGNED DEFAULT NULL AFTER `company`,
    ADD INDEX `idx_warehouse_suppliers_brand` (`brand_id`);
