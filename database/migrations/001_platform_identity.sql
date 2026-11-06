-- المنصة والهوية
CREATE TABLE schools (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  name_en VARCHAR(200) NULL,
  city VARCHAR(100) NULL, governorate VARCHAR(100) NULL, address VARCHAR(255) NULL,
  phone VARCHAR(30) NULL, email VARCHAR(190) NULL, logo_file_id BIGINT UNSIGNED NULL,
  gender_policy ENUM('mixed','boys','girls') NOT NULL DEFAULT 'mixed',
  base_currency CHAR(3) NOT NULL DEFAULT 'YER',
  timezone VARCHAR(50) NOT NULL DEFAULT 'Asia/Aden',
  status ENUM('active','suspended','archived') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE subscription_plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL, max_students INT UNSIGNED NULL, max_storage_mb INT UNSIGNED NULL,
  modules JSON NOT NULL, price DECIMAL(14,2) NOT NULL DEFAULT 0, currency CHAR(3) NOT NULL DEFAULT 'USD',
  billing_period ENUM('monthly','yearly') NOT NULL DEFAULT 'yearly', is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE school_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id INT UNSIGNED NOT NULL, plan_id INT UNSIGNED NOT NULL,
  starts_on DATE NOT NULL, ends_on DATE NULL, status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  amount DECIMAL(14,2) NOT NULL DEFAULT 0, notes VARCHAR(255) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
  INDEX (school_id, status)
) ENGINE=InnoDB;

CREATE TABLE school_modules (
  school_id INT UNSIGNED NOT NULL, module VARCHAR(50) NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (school_id, module), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

-- المستخدمون: school_id NULL = مشغّل المنصة
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id INT UNSIGNED NULL,
  user_type ENUM('platform','staff','student','guardian') NOT NULL,
  full_name VARCHAR(200) NOT NULL,
  email VARCHAR(190) NULL, phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  locale VARCHAR(5) NOT NULL DEFAULT 'ar', theme ENUM('light','dark','auto') NOT NULL DEFAULT 'auto',
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL, last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NULL, deleted_at DATETIME NULL,
  UNIQUE KEY uq_email (email), UNIQUE KEY uq_phone (phone),
  INDEX (school_id, user_type), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

CREATE TABLE platform_admins (
  user_id BIGINT UNSIGNED PRIMARY KEY, is_owner TINYINT(1) NOT NULL DEFAULT 0, FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(80) NOT NULL UNIQUE,
  module VARCHAR(50) NOT NULL, label VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_id INT UNSIGNED NULL, code VARCHAR(50) NOT NULL, name VARCHAR(120) NOT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0, scope ENUM('platform','school') NOT NULL DEFAULT 'school',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_role (school_id, code), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id INT UNSIGNED NOT NULL, permission_id INT UNSIGNED NOT NULL, PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE, FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id BIGINT UNSIGNED NOT NULL, role_id INT UNSIGNED NOT NULL, PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identifier VARCHAR(190) NOT NULL, ip VARCHAR(45) NOT NULL,
  success TINYINT(1) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (identifier, created_at), INDEX (ip, created_at)
) ENGINE=InnoDB;

CREATE TABLE otp_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, phone VARCHAR(30) NOT NULL, code_hash VARCHAR(255) NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (phone, created_at)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (token_hash), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rate_limits (
  rkey VARCHAR(190) PRIMARY KEY, hits INT UNSIGNED NOT NULL DEFAULT 0, window_start DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE support_access_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_user_id BIGINT UNSIGNED NOT NULL, school_id INT UNSIGNED NOT NULL,
  reason VARCHAR(255) NOT NULL, started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, ended_at DATETIME NULL,
  FOREIGN KEY (admin_user_id) REFERENCES users(id), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
