-- =============================================================================
-- Supplier Category Setup Script
-- Run this ONCE against your database after stock_event_setup.sql has been applied.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Rename the existing supplier-stock-category junction table.
--    The PHP class is renamed to StockSupplierCatLinkTable accordingly.
-- -----------------------------------------------------------------------------
RENAME TABLE `stock_supplier_category` TO `stock_supplier_cat_link`;

-- -----------------------------------------------------------------------------
-- 2. New table: stock_supplier_category
--    Simple lookup: a category that a supplier belongs to (e.g. "Grocery", "Bakery").
-- -----------------------------------------------------------------------------
CREATE TABLE `stock_supplier_category` (
  `id`   INT         NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- 3. Add supplier_category_id FK to stock_supplier (nullable — optional).
-- -----------------------------------------------------------------------------
ALTER TABLE `stock_supplier`
  ADD COLUMN `supplier_category_id` INT NULL DEFAULT NULL AFTER `name`,
  ADD CONSTRAINT `fk_ss_supplier_category`
    FOREIGN KEY (`supplier_category_id`)
    REFERENCES `stock_supplier_category` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- -----------------------------------------------------------------------------
-- 4. New page: Supplier Categories (page 416)
-- -----------------------------------------------------------------------------
INSERT INTO `page` (`pagenumber`, `name`, `usepagenum`, `pagetype`, `unrestricted`, `submenu`, `menuid`, `menutext`, `maxcolumns`, `autoextendtasks`) VALUES
(416, 'Supplier Categories', 0, 3, 0, 4, 'suppliercategoryoption', 'Supplier Categories', 0, 0);

-- Full CRUD for the new page
INSERT INTO `page_action` (`page_id`, `action_id`)
SELECT id, 18 FROM `page` WHERE `pagenumber` = 416
UNION ALL
SELECT id,  1 FROM `page` WHERE `pagenumber` = 416
UNION ALL
SELECT id,  2 FROM `page` WHERE `pagenumber` = 416
UNION ALL
SELECT id,  4 FROM `page` WHERE `pagenumber` = 416;

-- -----------------------------------------------------------------------------
-- 5. Menu item for the new page
-- -----------------------------------------------------------------------------
INSERT INTO `menuitem` (`menucode`, `page_number`, `text`, `inactive`, `menu_number`, `is_public`) VALUES
('06_16', '416', 'Supplier Categories', 0, 0, 0);

-- =============================================================================
-- After running this script:
--   1. Refresh the app — "Supplier Categories" should appear in the Stock menu.
--   2. Add supplier categories before editing suppliers (so the dropdown
--      has options to select from).
-- =============================================================================
