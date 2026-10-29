CREATE TABLE IF NOT EXISTS source_account_cutoffs (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  source_email VARCHAR(190) NOT NULL UNIQUE,
  last_ticket_date DATE NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
