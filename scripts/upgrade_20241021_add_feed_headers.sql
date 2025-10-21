-- Upgrade script: add feed header metadata columns for conditional fetching

ALTER TABLE feeds
    ADD COLUMN IF NOT EXISTS http_etag VARCHAR(255) NULL AFTER fail_count,
    ADD COLUMN IF NOT EXISTS last_modified VARCHAR(255) NULL AFTER http_etag;
