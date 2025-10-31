-- 20241017_update_products_imei.sql
-- Converte il campo barcode in IMEI e aggiunge l'IMEI alle righe di vendita.

ALTER TABLE products
  DROP INDEX IF EXISTS idx_products_barcode,
  CHANGE COLUMN barcode imei VARCHAR(100) NULL,
  ADD UNIQUE KEY idx_products_imei (imei);

ALTER TABLE sale_items
  ADD COLUMN IF NOT EXISTS product_imei VARCHAR(100) NULL AFTER product_id;
