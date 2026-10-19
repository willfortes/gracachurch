CREATE TABLE settings (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Programação: cultos, ministérios e eventos especiais
CREATE TABLE events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(120) NOT NULL,
  summary VARCHAR(300) NOT NULL DEFAULT '',
  description TEXT NULL,
  category ENUM('culto','ministerio','evento') NOT NULL DEFAULT 'culto',
  recurrence ENUM('weekly','monthly','once') NOT NULL DEFAULT 'weekly',
  weekday TINYINT NULL,
  nth TINYINT NULL,
  date DATE NULL,
  start_time TIME NOT NULL,
  duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 120,
  location_name VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  lat DECIMAL(9,6) NULL,
  lng DECIMAL(9,6) NULL,
  notify TINYINT(1) NOT NULL DEFAULT 1,
  reminder_min SMALLINT UNSIGNED NOT NULL DEFAULT 120,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  published TINYINT(1) NOT NULL DEFAULT 1,
  sort INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grupos de convívio (encontros nas casas)
CREATE TABLE cell_groups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  neighborhood VARCHAR(80) NOT NULL DEFAULT '',
  city VARCHAR(80) NOT NULL DEFAULT 'Palhoça',
  address VARCHAR(255) NULL,
  show_address TINYINT(1) NOT NULL DEFAULT 0,
  lat DECIMAL(9,6) NULL,
  lng DECIMAL(9,6) NULL,
  recurrence ENUM('weekly','monthly') NOT NULL DEFAULT 'weekly',
  weekday TINYINT NOT NULL DEFAULT 4,
  nth TINYINT NULL,
  start_time TIME NOT NULL DEFAULT '20:00:00',
  duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 120,
  audience VARCHAR(80) NOT NULL DEFAULT 'Todos',
  leader_name VARCHAR(120) NULL,
  leader_phone VARCHAR(30) NULL,
  description TEXT NULL,
  notify TINYINT(1) NOT NULL DEFAULT 1,
  reminder_min SMALLINT UNSIGNED NOT NULL DEFAULT 120,
  published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faqs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(200) NOT NULL,
  answer TEXT NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  published TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Navegadores inscritos para receber avisos. topics: ["cultos","eventos","grupo:3"]
CREATE TABLE push_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  endpoint_hash CHAR(64) NOT NULL UNIQUE,
  endpoint TEXT NOT NULL,
  p256dh VARCHAR(255) NOT NULL,
  auth VARCHAR(64) NOT NULL,
  topics JSON NOT NULL,
  platform VARCHAR(40) NOT NULL DEFAULT '',
  failures TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_sent_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada aviso (manual ou lembrete automático). ref_key evita lembrete duplicado.
CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  body VARCHAR(300) NOT NULL,
  url VARCHAR(255) NOT NULL DEFAULT '/',
  audience VARCHAR(40) NOT NULL DEFAULT 'all',
  source ENUM('manual','auto') NOT NULL DEFAULT 'manual',
  ref_key VARCHAR(120) NULL UNIQUE,
  ttl INT UNSIGNED NOT NULL DEFAULT 86400,
  status ENUM('queued','sending','done') NOT NULL DEFAULT 'queued',
  total INT UNSIGNED NOT NULL DEFAULT 0,
  sent INT UNSIGNED NOT NULL DEFAULT 0,
  failed INT UNSIGNED NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila de entrega: uma linha por aviso x navegador
CREATE TABLE push_outbox (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notification_id INT UNSIGNED NOT NULL,
  subscription_id INT UNSIGNED NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  next_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_delivery (notification_id, subscription_id),
  INDEX idx_next (next_attempt_at),
  FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
  FOREIGN KEY (subscription_id) REFERENCES push_subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
