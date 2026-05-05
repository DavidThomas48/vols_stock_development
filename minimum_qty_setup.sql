-- =============================================================================
-- Minimum Quantity Setup Script
-- Run this ONCE against your database to add minimum_qty support.
-- =============================================================================

ALTER TABLE `stock_item_location`
  ADD COLUMN `minimum_qty` INT NULL DEFAULT NULL;
