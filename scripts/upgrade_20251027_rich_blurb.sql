-- Upgrade to support rich blurbs
-- 1) Expand curated_links.blurb to TEXT for longer summaries
-- 2) Add curated_links.blurb_html (MEDIUMTEXT) for sanitized rich content

ALTER TABLE curated_links MODIFY blurb TEXT NOT NULL;
ALTER TABLE curated_links ADD COLUMN IF NOT EXISTS blurb_html MEDIUMTEXT NULL AFTER blurb;

