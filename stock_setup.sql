-- =============================================================================
-- Stock Tracking System Setup Script
-- Run this against your database ONCE to prepare the schema and seed data.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Fix stock_movement table: add AUTO_INCREMENT to id and add movement_date
-- -----------------------------------------------------------------------------
ALTER TABLE `stock_movement`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `stock_movement`
    ADD COLUMN `movement_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
    AFTER `unit_qty`;

-- -----------------------------------------------------------------------------
-- 2. Register the five new pages
--    pagetype: 3 = Editor page (admin maintenance), 2 = Roster/operational page
--    submenu:  pick a submenu group number (using 4 for a new Stock group)
--    unrestricted: 0 = requires login
-- -----------------------------------------------------------------------------
INSERT INTO `page` (`id`, `pagenumber`, `name`, `usepagenum`, `pagetype`, `unrestricted`, `submenu`, `menuid`, `menutext`, `maxcolumns`, `autoextendtasks`) VALUES
(50, 401, 'Stock Categories', 0, 3, 0, 4, 'stockcategoryoption', 'Stock Categories', 0, 0),
(51, 402, 'Stock Items',      0, 3, 0, 4, 'stockoption',         'Stock Items',      0, 0),
(52, 403, 'Stocktake',        0, 3, 0, 4, 'stocktakeoption',     'Stocktake',        0, 0),
(53, 404, 'Deliveries',       0, 3, 0, 4, 'deliveryoption',      'Deliveries',       0, 0),
(54, 405, 'Stock Usage',      0, 3, 0, 4, 'stockoutoption',      'Stock Usage',      0, 0);

-- -----------------------------------------------------------------------------
-- 3. Add page_action entries (VIEW + INSERT/UPDATE/DELETE where appropriate)
--    action ids: 1=INSERT, 2=UPDATE, 4=DELETE, 18=VIEW
-- -----------------------------------------------------------------------------
-- Stock Categories (page id=50)
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(50, 18), (50, 1), (50, 2), (50, 4);

-- Stock Items (page id=51)
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(51, 18), (51, 1), (51, 2), (51, 4);

-- Stocktake (page id=52) — VIEW + INSERT only
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(52, 18), (52, 1);

-- Deliveries (page id=53) — VIEW + INSERT only
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(53, 18), (53, 1);

-- Stock Usage (page id=54) — VIEW + INSERT only
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(54, 18), (54, 1);

-- -----------------------------------------------------------------------------
-- 4. Add menu items for the Stock group
--    Menu structure: parent heading "Stock" with five child items
--    Choose menucode values that don't conflict with existing ones.
--    Existing menus use: 01, 02, 03, 04, 05, 06, 07, 08, 09
--    Using 10 for the new Stock group.
-- -----------------------------------------------------------------------------
INSERT INTO `menuitem` (`id`, `menucode`, `page_number`, `text`, `inactive`, `menu_number`, `is_public`) VALUES
(40, '10',   '0',   'Stock',            0, 0, 0),
(41, '10_1', '401', 'Stock Categories', 0, 0, 0),
(42, '10_2', '402', 'Stock Items',      0, 0, 0),
(43, '10_3', '403', 'Stocktake',        0, 0, 0),
(44, '10_4', '404', 'Deliveries',       0, 0, 0),
(45, '10_5', '405', 'Stock Usage',      0, 0, 0),
(46, '10_6', '406', 'Stock Levels',    0, 0, 0);

-- Stock Level Report (page id=56) — VIEW only
INSERT INTO `page` (`id`, `pagenumber`, `name`, `usepagenum`, `pagetype`, `unrestricted`, `submenu`, `menuid`, `menutext`, `maxcolumns`, `autoextendtasks`) VALUES
(56, 406, 'Stock Level Report', 0, 3, 0, 4, 'stockleveloption', 'Stock Levels', 0, 0);

INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(56, 1);

-- Damaged Stock (page id=57) — VIEW + INSERT only
INSERT INTO `page` (`id`, `pagenumber`, `name`, `usepagenum`, `pagetype`, `unrestricted`, `submenu`, `menuid`, `menutext`, `maxcolumns`, `autoextendtasks`) VALUES
(57, 407, 'Damaged Stock', 0, 3, 0, 4, 'damagedstockoption', 'Damaged Stock', 0, 0);

INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(57, 1);

INSERT INTO `menuitem` (`menucode`, `page_number`, `text`, `inactive`, `menu_number`, `is_public`) VALUES
('10_7', '407', 'Damaged Stock', 0, 0, 0);

-- Also add 'damaged' to the movement_type ENUM if not already present
ALTER TABLE `stock_movement`
    MODIFY `movement_type` ENUM('stocktake_adjustment','delivery','stockout','damaged','reversal') NOT NULL;

-- Stock Usage Report (page id=58) — VIEW + INSERT only
INSERT INTO `page` (`id`, `pagenumber`, `name`, `usepagenum`, `pagetype`, `unrestricted`, `submenu`, `menuid`, `menutext`, `maxcolumns`, `autoextendtasks`) VALUES
(58, 408, 'Stock Usage Report', 0, 3, 0, 4, 'stockusagereportoption', 'Usage Report', 0, 0);

INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(58, 1);

INSERT INTO `menuitem` (`menucode`, `page_number`, `text`, `inactive`, `menu_number`, `is_public`) VALUES
('10_8', '408', 'Usage Report', 0, 0, 0);

-- =============================================================================
-- After running this script:
--   1. Log in as Admin — the Stock menu should now appear.
--   2. Go to Set Up > Roles to grant non-admin users access to the stock pages.
--   3. Use Stock > Stock Categories to add categories first,
--      then Stock > Stock Items to add items.
-- =============================================================================
