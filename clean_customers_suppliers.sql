-- =========================================================================
-- PharmaCare POS - Clean Customers, Suppliers & Transactions (InnoDB Safe)
-- =========================================================================
-- Uses DELETE FROM instead of TRUNCATE to satisfy InnoDB foreign key checks.
-- PRESERVES:
--   ✔ All Items / Medications (`products`)
--   ✔ All Stock Batches (`product_batches`)
--   ✔ Therapeutic Categories (`categories`)
--   ✔ Users and Roles (`users`, `roles`)
-- =========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Wipe customer transactions (POS bills, prescriptions, settlements)
DELETE FROM `prescriptions`;
DELETE FROM `sale_items`;
DELETE FROM `sales`;
DELETE FROM `customer_settlements`;
DELETE FROM `customer_extra_payments`;

-- 2. Wipe customers
DELETE FROM `customers`;

-- 3. Wipe supplier transactions (GRN receipts and payments)
DELETE FROM `supplier_payments`;
DELETE FROM `grn_items`;
DELETE FROM `grns`;

-- 4. Wipe suppliers
DELETE FROM `suppliers`;

-- 5. Disassociate supplier IDs from product batches (keeps your stock & items 100% intact)
UPDATE `product_batches` SET `supplier_id` = NULL;

-- 6. Reset ID counters to start from 1 for new clean transactions
ALTER TABLE `prescriptions` AUTO_INCREMENT = 1;
ALTER TABLE `sale_items` AUTO_INCREMENT = 1;
ALTER TABLE `sales` AUTO_INCREMENT = 1;
ALTER TABLE `customer_settlements` AUTO_INCREMENT = 1;
ALTER TABLE `customer_extra_payments` AUTO_INCREMENT = 1;
ALTER TABLE `customers` AUTO_INCREMENT = 1;
ALTER TABLE `supplier_payments` AUTO_INCREMENT = 1;
ALTER TABLE `grn_items` AUTO_INCREMENT = 1;
ALTER TABLE `grns` AUTO_INCREMENT = 1;
ALTER TABLE `suppliers` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- 7. Verification: Confirm customers/suppliers are 0 and items are preserved
SELECT 
    (SELECT COUNT(*) FROM `customers`) AS remaining_customers,
    (SELECT COUNT(*) FROM `suppliers`) AS remaining_suppliers,
    (SELECT COUNT(*) FROM `sales`) AS remaining_sales,
    (SELECT COUNT(*) FROM `grns`) AS remaining_grns,
    (SELECT COUNT(*) FROM `products`) AS preserved_catalog_items,
    (SELECT COUNT(*) FROM `product_batches`) AS preserved_stock_batches;
