-- Adds scheduled_for column and supporting index for editions scheduling.
ALTER TABLE editions
    ADD COLUMN scheduled_for DATETIME NULL AFTER published_at,
    ADD KEY idx_editions_schedule (status, scheduled_for);
