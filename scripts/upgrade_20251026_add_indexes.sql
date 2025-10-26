-- Add helpful indexes for query performance
-- Run once on production (MySQL 8.0+). If IF NOT EXISTS is unsupported on
-- your version, you can ignore errors for duplicate indexes or create them
-- conditionally via information_schema.

-- Curated links published_at is used frequently for ordering
CREATE INDEX IF NOT EXISTS idx_curated_links_published_at ON curated_links (published_at);

-- Inbox ordering and housekeeping often use items.created_at
CREATE INDEX IF NOT EXISTS idx_items_created_at ON items (created_at);

