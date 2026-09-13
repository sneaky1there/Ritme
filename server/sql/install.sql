-- MySQL 8+ / MariaDB 10.6+, database selected before import. All dates use configured timezone.
CREATE TABLE IF NOT EXISTS daily_metrics (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 date DATE NOT NULL UNIQUE,
 weight DECIMAL(6,2) NULL, steps INT UNSIGNED NULL,
 sleep_minutes SMALLINT UNSIGNED NULL, sleep_quality TINYINT UNSIGNED NULL,
 resting_hr SMALLINT UNSIGNED NULL, blood_pressure_sys SMALLINT UNSIGNED NULL,
 blood_pressure_dia SMALLINT UNSIGNED NULL, navel_cm DECIMAL(6,2) NULL, hip_cm DECIMAL(6,2) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS weekly_checkins (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, week_start DATE NOT NULL UNIQUE,
 libido TINYINT UNSIGNED NULL, bowel_movement TEXT NULL, cravings TINYINT UNSIGNED NULL,
 stress TINYINT UNSIGNED NULL, libido_details TEXT NULL, stress_details TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS workouts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, source VARCHAR(32) NOT NULL,
 external_id VARCHAR(100) NOT NULL, date DATE NOT NULL, started_at DATETIME NOT NULL,
 workout_name VARCHAR(255) NOT NULL, duration_minutes DECIMAL(10,2) NULL,
 total_volume DECIMAL(16,2) NULL, raw_json LONGTEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY source_external (source, external_id), KEY workout_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS workout_sets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, workout_id BIGINT UNSIGNED NOT NULL,
 exercise_external_id VARCHAR(100) NOT NULL, exercise_name VARCHAR(255) NOT NULL,
 exercise_index INT UNSIGNED NOT NULL, set_index INT UNSIGNED NOT NULL, set_type VARCHAR(32) NOT NULL,
 weight_kg DECIMAL(10,3) NULL, reps INT UNSIGNED NULL, distance DECIMAL(12,3) NULL,
 duration_seconds DECIMAL(12,3) NULL, rpe DECIMAL(4,1) NULL,
 FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE,
 KEY exercise_lookup (exercise_external_id), UNIQUE KEY set_identity (workout_id, exercise_index, set_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS coach_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 description VARCHAR(100) NOT NULL, active BOOLEAN NOT NULL DEFAULT TRUE,
 can_view_overview BOOLEAN NOT NULL DEFAULT TRUE, can_view_training BOOLEAN NOT NULL DEFAULT FALSE,
 can_view_nutrition BOOLEAN NOT NULL DEFAULT FALSE,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, last_used_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS nutrition_daily (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 date DATE NOT NULL UNIQUE,
 calories_kcal DECIMAL(8,2) NULL, protein_g DECIMAL(8,2) NULL,
 carbohydrates_g DECIMAL(8,2) NULL, fat_g DECIMAL(8,2) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS food_products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, barcode VARCHAR(32) NULL,
 name VARCHAR(160) NOT NULL, brand VARCHAR(120) NULL,
 calories_per_100g DECIMAL(8,2) NOT NULL, protein_per_100g DECIMAL(8,2) NOT NULL,
 carbohydrates_per_100g DECIMAL(8,2) NOT NULL, fat_per_100g DECIMAL(8,2) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY product_barcode (barcode), KEY product_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS food_diary_entries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, date DATE NOT NULL,
 meal ENUM('breakfast','lunch','dinner','other') NOT NULL, product_id BIGINT UNSIGNED NOT NULL,
 amount_g DECIMAL(8,2) NOT NULL, product_name VARCHAR(160) NOT NULL, brand VARCHAR(120) NULL,
 calories_kcal DECIMAL(8,2) NOT NULL, protein_g DECIMAL(8,2) NOT NULL,
 carbohydrates_g DECIMAL(8,2) NOT NULL, fat_g DECIMAL(8,2) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(product_id) REFERENCES food_products(id), KEY diary_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS login_attempts (
 bucket CHAR(64) PRIMARY KEY, attempts INT UNSIGNED NOT NULL DEFAULT 0, window_start BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS sync_state (
 source VARCHAR(32) PRIMARY KEY, last_success_at DATETIME NULL, imported_count INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Health Connect extension (v1.1)
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
