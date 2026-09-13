-- Eigen productcatalogus en voedingsdagboek. Opnieuw uitvoeren is veilig.
CREATE TABLE IF NOT EXISTS food_products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 barcode VARCHAR(32) NULL,
 name VARCHAR(160) NOT NULL,
 brand VARCHAR(120) NULL,
 calories_per_100g DECIMAL(8,2) NOT NULL,
 protein_per_100g DECIMAL(8,2) NOT NULL,
 carbohydrates_per_100g DECIMAL(8,2) NOT NULL,
 fat_per_100g DECIMAL(8,2) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY product_barcode (barcode), KEY product_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS food_diary_entries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 date DATE NOT NULL,
 meal ENUM('breakfast','lunch','dinner','other') NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 amount_g DECIMAL(8,2) NOT NULL,
 product_name VARCHAR(160) NOT NULL,
 brand VARCHAR(120) NULL,
 calories_kcal DECIMAL(8,2) NOT NULL,
 protein_g DECIMAL(8,2) NOT NULL,
 carbohydrates_g DECIMAL(8,2) NOT NULL,
 fat_g DECIMAL(8,2) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(product_id) REFERENCES food_products(id), KEY diary_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
