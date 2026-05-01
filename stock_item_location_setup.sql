-- =============================================================================
-- Stock Item Location Setup Script
-- Run once to create the stock_item_location relation table.
-- Requires: stock and stock_location tables already exist.
-- =============================================================================

CREATE TABLE `stock_item_location` (
  `id`                INT NOT NULL AUTO_INCREMENT,
  `stock_id`          INT NOT NULL,
  `stock_location_id` INT NOT NULL,
  `target_qty`        INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sil_stock_loc`    (`stock_id`, `stock_location_id`),
  KEY `fk_sil_stock_idx`           (`stock_id`),
  KEY `fk_sil_location_idx`        (`stock_location_id`),
  CONSTRAINT `fk_sil_stock`    FOREIGN KEY (`stock_id`)          REFERENCES `stock`          (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sil_location` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_location` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
