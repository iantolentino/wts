-- Apply after schema.sql, to the NEW Wheettle database only.
ALTER TABLE staff_directory DROP INDEX full_name,
  ADD employee_code VARCHAR(40) NULL UNIQUE,
  ADD position VARCHAR(150) NOT NULL DEFAULT '',
  ADD shift_schedule VARCHAR(190) NOT NULL DEFAULT '',
  ADD department_id BIGINT UNSIGNED NULL,
  ADD tl_id BIGINT UNSIGNED NULL,
  ADD start_date DATE NULL,
  ADD exit_date DATE NULL,
  ADD employment_status ENUM('new','active','exited') NOT NULL DEFAULT 'new',
  ADD notes TEXT NULL,
  ADD version INT UNSIGNED NOT NULL DEFAULT 1,
  ADD KEY idx_staff_filter(employment_status,department_id,tl_id,start_date),
  ADD FOREIGN KEY(department_id) REFERENCES departments(id),
  ADD FOREIGN KEY(tl_id) REFERENCES users(id);

CREATE TABLE staff_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  staff_id BIGINT UNSIGNED NOT NULL, actor_id BIGINT UNSIGNED NOT NULL,
  event_type ENUM('created','updated','note') NOT NULL,
  effective_date DATE NOT NULL, notes TEXT NOT NULL,
  before_data JSON NULL, after_data JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_staff_history_date(staff_id,effective_date,id),
  KEY idx_history_effective(effective_date,event_type),
  FOREIGN KEY(staff_id) REFERENCES staff_directory(id),
  FOREIGN KEY(actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_photos (
  staff_id BIGINT UNSIGNED PRIMARY KEY, image_data MEDIUMBLOB NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(staff_id) REFERENCES staff_directory(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE tickets ADD staff_id BIGINT UNSIGNED NULL, ADD version INT UNSIGNED NOT NULL DEFAULT 1,
  ADD FOREIGN KEY(staff_id) REFERENCES staff_directory(id), ADD KEY idx_tickets_staff(staff_id,created_at);

CREATE TABLE login_attempts (
  bucket CHAR(64) PRIMARY KEY, failures INT UNSIGNED NOT NULL DEFAULT 0,
  last_attempt DATETIME NOT NULL, KEY idx_login_attempt_time(last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permissions(permission_key,label,description) VALUES
('view_staff','View staff','View employee profiles and history.'),
('manage_staff','Manage staff','Manually create and update employee records and photos.'),
('view_reports','View reports','View staff and ticket reports.'),
('export_staff','Export staff','Export employee profiles and staff history.');

-- Wheettle grants replace the inherited role grants in this fresh installation.
DELETE FROM role_permissions;
INSERT INTO role_permissions(role_id,permission_id,granted)
SELECT r.id,p.id,1 FROM roles r CROSS JOIN permissions p WHERE r.slug='super-admin';
INSERT INTO role_permissions(role_id,permission_id,granted)
SELECT r.id,p.id,1 FROM roles r CROSS JOIN permissions p WHERE r.slug='team-leader'
AND p.permission_key IN ('view_all_tickets','create_tickets','edit_tickets','close_tickets','comment_tickets','assign_tickets','view_staff','manage_staff','view_reports','export_staff','export_tickets');
INSERT INTO role_permissions(role_id,permission_id,granted)
SELECT r.id,p.id,1 FROM roles r CROSS JOIN permissions p WHERE r.slug IN ('management','department-head')
AND p.permission_key IN ('view_all_tickets','view_staff','view_reports','export_staff','export_tickets');
INSERT INTO role_permissions(role_id,permission_id,granted)
SELECT r.id,p.id,1 FROM roles r CROSS JOIN permissions p WHERE r.slug='client-viewer'
AND p.permission_key IN ('view_all_tickets');
