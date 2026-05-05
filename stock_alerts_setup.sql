-- =============================================================================
-- Stock Alerts Setup Script
-- Run this ONCE against your database.
-- =============================================================================

ALTER TABLE `user`
  ADD COLUMN `receives_stock_alerts` TINYINT(1) NOT NULL DEFAULT 0;
