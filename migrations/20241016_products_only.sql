-- 20241016_products_only.sql
-- Script focalizzato sulla sola creazione della tabella prodotti e relativo vincolo.

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  sku VARCHAR(100) NULL,
  imei VARCHAR(100) NULL,
  category VARCHAR(100) NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 22.00,
  notes TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY idx_products_sku (sku),
  UNIQUE KEY idx_products_imei (imei)
);

ALTER TABLE sale_items
  ADD COLUMN product_id INT NULL AFTER iccid_id,
  ADD COLUMN product_imei VARCHAR(100) NULL AFTER product_id,
  ADD COLUMN tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER price,
  ADD COLUMN tax_amount DECIMAL(10,4) NOT NULL DEFAULT 0.0000 AFTER tax_rate,
  ADD CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id)
    REFERENCES products(id)
    ON DELETE SET NULL;
