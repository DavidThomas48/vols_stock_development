-- =============================================================================
-- Stock Event Module Setup Script
-- Run this against your database ONCE after stock_setup.sql has been applied.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. New role: Perform Stocktake
-- -----------------------------------------------------------------------------
INSERT INTO `role` (`name`, `cellname`, `rosterindex`) VALUES
('Perform Stocktake', 'StockOp', 0);

-- -----------------------------------------------------------------------------
-- 2. New table: location
-- -----------------------------------------------------------------------------
CREATE TABLE `location` (
  `id`                  INT         NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(45) NOT NULL,
  `uncontrolled_issues` TINYINT(1)  NOT NULL DEFAULT 0 COMMENT 'This means the location''s issues are not tracked so are determined by a stocktake',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- 3. New table: stock_supplier
-- -----------------------------------------------------------------------------
CREATE TABLE `stock_supplier` (
  `id`   INT         NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- 4. New table: stock_supplier_category
--    Links suppliers to the stock categories they supply.
-- -----------------------------------------------------------------------------
CREATE TABLE `stock_supplier_category` (
  `id`                INT NOT NULL AUTO_INCREMENT,
  `stock_supplier_id` INT NOT NULL,
  `stock_category_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ssc_supplier_idx` (`stock_supplier_id`),
  KEY `fk_ssc_category_idx` (`stock_category_id`),
  CONSTRAINT `fk_ssc_supplier`  FOREIGN KEY (`stock_supplier_id`) REFERENCES `stock_supplier`  (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ssc_category`  FOREIGN KEY (`stock_category_id`) REFERENCES `stock_category`  (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- 5. New table: stock_client
-- -----------------------------------------------------------------------------
CREATE TABLE `stock_client` (
  `id`   INT         NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed: default client
INSERT INTO `stock_client` (`name`) VALUES ('FoodBank');

-- -----------------------------------------------------------------------------
-- 6. New table: stock_event
-- -----------------------------------------------------------------------------
CREATE TABLE `stock_event` (
  `id`               INT      NOT NULL AUTO_INCREMENT,
  `location1_id`     INT      NOT NULL,
  `location2_id`     INT      NULL     DEFAULT NULL,
  `supplier_id`      INT      NULL     DEFAULT NULL,
  `stock_client_id`  INT      NULL     DEFAULT NULL,
  `event`            ENUM('delivery','transfer','adjustment','issue','stocktake') NOT NULL,
  `status`           ENUM('in progress','closed','cancelled') NOT NULL DEFAULT 'in progress',
  `date_created`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_closed`      DATETIME NULL     DEFAULT NULL,
  `date_cancelled`   DATETIME NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_se_location1_idx`    (`location1_id`),
  KEY `fk_se_location2_idx`    (`location2_id`),
  KEY `fk_se_supplier_idx`     (`supplier_id`),
  KEY `fk_se_stock_client_idx` (`stock_client_id`),
  CONSTRAINT `fk_se_location1`    FOREIGN KEY (`location1_id`)    REFERENCES `location`      (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_se_location2`    FOREIGN KEY (`location2_id`)    REFERENCES `location`      (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_se_supplier`     FOREIGN KEY (`supplier_id`)     REFERENCES `stock_supplier`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_se_stock_client` FOREIGN KEY (`stock_client_id`) REFERENCES `stock_client` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- 7. Alter stock_movement:
--    a) Drop movement_type — event semantics are read from stock_event.event.
--    b) Add stock_qoh, stock_event_id, location_id (nullable, backward-compatible).
-- -----------------------------------------------------------------------------
ALTER TABLE `stock_movement`
    DROP COLUMN `movement_type`;

ALTER TABLE `stock_movement`
    ADD COLUMN `stock_qoh`      INT NULL DEFAULT NULL AFTER `unit_qty`,
    ADD COLUMN `stock_event_id` INT NULL DEFAULT NULL AFTER `stock_qoh`,
    ADD COLUMN `location_id`    INT NULL DEFAULT NULL AFTER `stock_event_id`;

ALTER TABLE `stock_movement`
    ADD CONSTRAINT `fk_sm_stock_event` FOREIGN KEY (`stock_event_id`) REFERENCES `stock_event` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_sm_location`    FOREIGN KEY (`location_id`)    REFERENCES `location`    (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- -----------------------------------------------------------------------------
-- 8. New pages (continuing from existing stock pages 401–408)
--    pagetype 3 = Editor page
-- -----------------------------------------------------------------------------
INSERT INTO `page` (`id`, `pagenumber`, `name`, `usepagenum`, `pagetype`, `unrestricted`, `submenu`, `menuid`, `menutext`, `maxcolumns`, `autoextendtasks`) VALUES
(59, 409, 'Locations',         0, 3, 0, 4, 'locationoption',         'Locations',         0, 0),
(60, 410, 'Stock Suppliers',   0, 3, 0, 4, 'stocksupplieroption',    'Stock Suppliers',   0, 0),
(61, 411, 'Stocktake Event',   0, 3, 0, 4, 'stocktakeeventoption',   'Stocktake',         0, 0),
(62, 412, 'Delivery Event',    0, 3, 0, 4, 'deliveryeventoption',    'Delivery',          0, 0),
(63, 413, 'Transfer Event',    0, 3, 0, 4, 'transfereventoption',    'Transfer',          0, 0),
(64, 414, 'Adjustment Event',  0, 3, 0, 4, 'adjustmenteventoption',  'Adjustment',        0, 0);

-- -----------------------------------------------------------------------------
-- 9. page_action entries
--    action ids: 1=INSERT, 2=UPDATE, 4=DELETE, 18=VIEW
-- -----------------------------------------------------------------------------
-- Locations editor (page id=59) — full CRUD
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(59, 18), (59, 1), (59, 2), (59, 4);

-- Stock Suppliers editor (page id=60) — full CRUD
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(60, 18), (60, 1), (60, 2), (60, 4);

-- Stocktake Event (page id=61) — VIEW + INSERT + UPDATE (no delete)
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(61, 18), (61, 1), (61, 2);

-- Delivery Event (page id=62) — VIEW + INSERT + UPDATE
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(62, 18), (62, 1), (62, 2);

-- Transfer Event (page id=63) — VIEW + INSERT + UPDATE
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(63, 18), (63, 1), (63, 2);

-- Adjustment Event (page id=64) — VIEW + INSERT + UPDATE
INSERT INTO `page_action` (`page_id`, `action_id`) VALUES
(64, 18), (64, 1), (64, 2);

-- -----------------------------------------------------------------------------
-- 10. Menu items (continuing from existing Stock group, menucode 06_x)
--     Existing: 06_1 to 06_8
-- -----------------------------------------------------------------------------
INSERT INTO `menuitem` (`menucode`, `page_number`, `text`, `inactive`, `menu_number`, `is_public`) VALUES
('06_9',  '409', 'Locations',       0, 0, 0),
('06_10', '410', 'Stock Suppliers', 0, 0, 0),
('06_11', '411', 'Stocktake',       0, 0, 0),
('06_12', '412', 'Delivery',        0, 0, 0),
('06_13', '413', 'Transfer',        0, 0, 0),
('06_14', '414', 'Adjustment',      0, 0, 0);

-- =============================================================================
-- After running this script:
--   1. Log in as Admin — the new menu items should appear in the Stock group.
--   2. Go to Set Up > Roles to grant the "Perform Stocktake" role access to
--      the new stock event pages.
--   3. Use Stock > Locations to add at least one location before using any
--      stock event pages.
--   4. Use Stock > Stock Suppliers to add suppliers before using Delivery.
-- =============================================================================
