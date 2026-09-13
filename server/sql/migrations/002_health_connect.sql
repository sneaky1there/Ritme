-- Apply after install.sql to an existing v1 database. Re-running is safe.
CREATE TABLE IF NOT EXISTS mobile_guard (id TINYINT PRIMARY KEY) ENGINE=InnoDB;
INSERT IGNORE INTO mobile_guard (id) VALUES (1);
CREATE TABLE IF NOT EXISTS mobile_pairings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL, used_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS mobile_devices (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 name VARCHAR(100) NOT NULL, active BOOLEAN NOT NULL DEFAULT TRUE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 last_sync_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS health_connect_daily (
 date DATE NOT NULL PRIMARY KEY, device_id BIGINT UNSIGNED NOT NULL,
 steps INT UNSIGNED NULL, weight DECIMAL(6,2) NULL, sleep_minutes SMALLINT UNSIGNED NULL,
 steps_sources TEXT NULL, weight_sources TEXT NULL, sleep_minutes_sources TEXT NULL,
 steps_synced_at DATETIME NULL, weight_synced_at DATETIME NULL, sleep_minutes_synced_at DATETIME NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(device_id) REFERENCES mobile_devices(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Manual values always take precedence; original manual sleep retains its time-in-bed meaning.
CREATE OR REPLACE VIEW effective_daily_metrics AS
 SELECT dates.date, COALESCE(d.weight,h.weight) AS weight, COALESCE(d.steps,h.steps) AS steps,
 d.sleep_minutes,d.sleep_quality,d.resting_hr,d.blood_pressure_sys,d.blood_pressure_dia,
 d.navel_cm,d.hip_cm,
 h.sleep_minutes AS hc_sleep_minutes,
 CASE WHEN d.weight IS NOT NULL THEN 'handmatig' WHEN h.weight IS NOT NULL THEN 'Health Connect' ELSE NULL END AS weight_source,
 CASE WHEN d.steps IS NOT NULL THEN 'handmatig' WHEN h.steps IS NOT NULL THEN 'Health Connect' ELSE NULL END AS steps_source
 FROM (SELECT date FROM daily_metrics UNION SELECT date FROM health_connect_daily) dates
 LEFT JOIN daily_metrics d ON d.date=dates.date LEFT JOIN health_connect_daily h ON h.date=dates.date;
