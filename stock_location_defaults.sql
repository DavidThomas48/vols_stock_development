-- Add default-location flags to stock_location.
-- Run once. Safe to re-run (IF NOT EXISTS guard per column).

ALTER TABLE stock_location
    ADD COLUMN IF NOT EXISTS is_delivery_default      TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS is_transfer_from_default TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS is_transfer_to_default   TINYINT(1) NOT NULL DEFAULT 0;

-- Set the defaults by name (adjust if your location names differ).
UPDATE stock_location SET is_delivery_default      = 1 WHERE name = 'Storeroom';
UPDATE stock_location SET is_transfer_from_default = 1 WHERE name = 'Storeroom';
UPDATE stock_location SET is_transfer_to_default   = 1 WHERE name = 'Undercroft';
