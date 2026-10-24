CREATE TABLE IF NOT EXISTS live_location_shares (
  session_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  accuracy_m DECIMAL(10,2) NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (session_key),
  KEY idx_live_location_user (user_id),
  KEY idx_live_location_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
