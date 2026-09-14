-- Health Connect: rusthartslag, totaal verbrande calorieën en lichaamsvet.
-- Re-running is safe on MySQL 8+ and MariaDB 10.6+.
SET @ritme_schema = DATABASE();

SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='resting_hr')=0,'ALTER TABLE health_connect_daily ADD COLUMN resting_hr SMALLINT UNSIGNED NULL AFTER sleep_minutes','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;
SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='total_calories_kcal')=0,'ALTER TABLE health_connect_daily ADD COLUMN total_calories_kcal DECIMAL(10,2) NULL AFTER resting_hr','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;
SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='body_fat_percentage')=0,'ALTER TABLE health_connect_daily ADD COLUMN body_fat_percentage DECIMAL(5,2) NULL AFTER total_calories_kcal','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;

SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='resting_hr_sources')=0,'ALTER TABLE health_connect_daily ADD COLUMN resting_hr_sources TEXT NULL AFTER sleep_minutes_sources','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;
SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='total_calories_kcal_sources')=0,'ALTER TABLE health_connect_daily ADD COLUMN total_calories_kcal_sources TEXT NULL AFTER resting_hr_sources','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;
SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='body_fat_percentage_sources')=0,'ALTER TABLE health_connect_daily ADD COLUMN body_fat_percentage_sources TEXT NULL AFTER total_calories_kcal_sources','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;

SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='resting_hr_synced_at')=0,'ALTER TABLE health_connect_daily ADD COLUMN resting_hr_synced_at DATETIME NULL AFTER sleep_minutes_synced_at','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;
SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='total_calories_kcal_synced_at')=0,'ALTER TABLE health_connect_daily ADD COLUMN total_calories_kcal_synced_at DATETIME NULL AFTER resting_hr_synced_at','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;
SET @ritme_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@ritme_schema AND TABLE_NAME='health_connect_daily' AND COLUMN_NAME='body_fat_percentage_synced_at')=0,'ALTER TABLE health_connect_daily ADD COLUMN body_fat_percentage_synced_at DATETIME NULL AFTER total_calories_kcal_synced_at','SELECT 1'); PREPARE ritme_stmt FROM @ritme_sql; EXECUTE ritme_stmt; DEALLOCATE PREPARE ritme_stmt;

CREATE OR REPLACE VIEW effective_daily_metrics AS
 SELECT dates.date, COALESCE(h.weight,d.weight) AS weight, COALESCE(h.steps,d.steps) AS steps,
 COALESCE(h.sleep_minutes,d.sleep_minutes) AS sleep_minutes,d.sleep_quality,COALESCE(h.resting_hr,d.resting_hr) AS resting_hr,
 d.blood_pressure_sys,d.blood_pressure_dia,d.navel_cm,d.hip_cm,
 h.sleep_minutes AS hc_sleep_minutes,h.total_calories_kcal,h.body_fat_percentage,
 CASE WHEN h.weight IS NOT NULL THEN 'Health Connect' WHEN d.weight IS NOT NULL THEN 'handmatig' ELSE NULL END AS weight_source,
 CASE WHEN h.steps IS NOT NULL THEN 'Health Connect' WHEN d.steps IS NOT NULL THEN 'handmatig' ELSE NULL END AS steps_source
 FROM (SELECT date FROM daily_metrics UNION SELECT date FROM health_connect_daily) dates
 LEFT JOIN daily_metrics d ON d.date=dates.date LEFT JOIN health_connect_daily h ON h.date=dates.date;
