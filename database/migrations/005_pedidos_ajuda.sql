-- Pedidos de ajuda (antes num Google Form em gracachurchpalhoca.org/queroajuda)
CREATE TABLE help_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  message TEXT NOT NULL,
  extra TEXT NULL,
  lang CHAR(2) NOT NULL DEFAULT 'pt',
  consent TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('novo','contato','concluido') NOT NULL DEFAULT 'novo',
  notes TEXT NULL,
  handled_by INT UNSIGNED NULL,
  ip_hash CHAR(64) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status, created_at),
  INDEX idx_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
