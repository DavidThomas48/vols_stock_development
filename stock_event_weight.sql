ALTER TABLE stock_event
    ADD COLUMN IF NOT EXISTS total_weight INT NULL;
