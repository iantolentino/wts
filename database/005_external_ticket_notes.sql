CREATE TABLE IF NOT EXISTS external_ticket_notes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_key VARCHAR(80) NOT NULL,
  source_ticket_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_external_ticket_notes (source_key, source_ticket_id, id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
