-- Initial database migration for TheNewsLog.org
-- Run once on a new database. Requires MySQL 8.0+.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS feeds (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    site_url VARCHAR(512) NOT NULL,
    feed_url VARCHAR(512) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_checked_at DATETIME NULL,
    fail_count INT UNSIGNED NOT NULL DEFAULT 0,
    http_etag VARCHAR(255) NULL,
    last_modified VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_feeds_feed_url (feed_url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feed_id INT UNSIGNED NOT NULL,
    title VARCHAR(512) NOT NULL,
    url VARCHAR(1024) NOT NULL,
    url_hash CHAR(40) NOT NULL,
    summary_raw MEDIUMTEXT NULL,
    author VARCHAR(255) NULL,
    source_name VARCHAR(255) NULL,
    og_image VARCHAR(1024) NULL,
    published_at DATETIME NULL,
    status ENUM('new','discarded','curated') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_feed FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE CASCADE,
    UNIQUE KEY uk_items_urlhash (url_hash),
    KEY idx_items_feed (feed_id),
    KEY idx_items_published (published_at),
    KEY idx_items_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS editions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    edition_date DATE NOT NULL,
    slug VARCHAR(255) NOT NULL,
    title VARCHAR(255) NULL,
    intro TEXT NULL,
    status ENUM('draft','scheduled','published') NOT NULL DEFAULT 'draft',
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    scheduled_for DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_editions_date (edition_date),
    UNIQUE KEY uk_editions_slug (slug),
    KEY idx_editions_status (status),
    KEY idx_editions_is_published (is_published, edition_date),
    KEY idx_editions_schedule (status, scheduled_for),
    `date` DATE GENERATED ALWAYS AS (edition_date) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curated_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id BIGINT UNSIGNED NULL,
    external_url VARCHAR(1024) NULL,
    title VARCHAR(255) NOT NULL,
    title_custom VARCHAR(280) NULL,
    blurb VARCHAR(500) NOT NULL,
    note VARCHAR(280) NULL,
    source_name VARCHAR(255) NULL,
    source_url VARCHAR(500) NULL,
    tags_csv VARCHAR(255) NULL,
    edition_date DATE NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    pinned TINYINT(1) GENERATED ALWAYS AS (is_pinned) STORED,
    position INT NULL,
    curator_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    CONSTRAINT fk_curated_links_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL,
    KEY idx_curated_pub (is_published, created_at),
    KEY idx_curated_edition (edition_date, is_published),
    FULLTEXT KEY ft_curated_tags (tags_csv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS edition_curated_link (
    edition_id INT UNSIGNED NOT NULL,
    curated_link_id BIGINT UNSIGNED NOT NULL,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (edition_id, curated_link_id),
    CONSTRAINT fk_ecl_edition FOREIGN KEY (edition_id) REFERENCES editions(id) ON DELETE CASCADE,
    CONSTRAINT fk_ecl_curated_link FOREIGN KEY (curated_link_id) REFERENCES curated_links(id) ON DELETE CASCADE,
    KEY idx_ecl_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tags_name (name),
    UNIQUE KEY uk_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curated_link_tag (
    curated_link_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (curated_link_id, tag_id),
    CONSTRAINT fk_clt_curated_link FOREIGN KEY (curated_link_id) REFERENCES curated_links(id) ON DELETE CASCADE,
    CONSTRAINT fk_clt_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscribers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(320) NOT NULL,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_subscribers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
