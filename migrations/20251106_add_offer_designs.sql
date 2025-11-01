-- 20251106_add_offer_designs.sql
-- Definisce l'archivio dei layout brochure progettati con l'editor drag and drop.

CREATE TABLE IF NOT EXISTS offer_designs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  public_id CHAR(36) NOT NULL,
  user_id INT NULL,
  name VARCHAR(150) NOT NULL,
  description VARCHAR(255) NULL,
  format VARCHAR(10) NOT NULL DEFAULT 'A4',
  orientation ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait',
  theme VARCHAR(50) NULL,
  html LONGTEXT NOT NULL,
  css LONGTEXT NULL,
  design_json LONGTEXT NULL,
  meta_json LONGTEXT NULL,
  last_used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_offer_design_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT uniq_offer_design_public_id UNIQUE (public_id),
  INDEX idx_offer_design_user (user_id, updated_at),
  INDEX idx_offer_design_used (last_used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
