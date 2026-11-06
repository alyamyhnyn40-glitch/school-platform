-- المالية
CREATE TABLE currencies (
  code CHAR(3) PRIMARY KEY, name VARCHAR(60) NOT NULL, symbol VARCHAR(10) NOT NULL, decimals TINYINT UNSIGNED NOT NULL DEFAULT 2
) ENGINE=InnoDB;
CREATE TABLE exchange_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, currency CHAR(3) NOT NULL, rate_to_base DECIMAL(18,6) NOT NULL,
  effective_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, created_by BIGINT UNSIGNED NULL, INDEX (school_id, currency, effective_from),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (currency) REFERENCES currencies(code)
) ENGINE=InnoDB;
CREATE TABLE tax_settings (
  school_id INT UNSIGNED PRIMARY KEY, tax_enabled TINYINT(1) NOT NULL DEFAULT 0, rate_percent DECIMAL(5,2) NULL, tax_label VARCHAR(60) NULL,
  tax_number VARCHAR(60) NULL, exempt_note VARCHAR(255) NULL, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE payment_methods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, kind ENUM('cash','bank','wallet','exchange') NOT NULL, name VARCHAR(100) NOT NULL,
  account_info VARCHAR(255) NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE fee_structures (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, academic_year_id INT UNSIGNED NOT NULL, grade_level_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL, currency CHAR(3) NOT NULL, FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
) ENGINE=InnoDB;
CREATE TABLE fee_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, structure_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL,
  amount DECIMAL(14,2) NOT NULL, installments_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (structure_id) REFERENCES fee_structures(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE discounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, kind ENUM('percent','fixed') NOT NULL,
  value DECIMAL(14,2) NOT NULL, reason ENUM('sibling','scholarship','staff_child','other') NOT NULL DEFAULT 'other', FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE student_fees (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL, structure_id INT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL, gross DECIMAL(14,2) NOT NULL, discount_total DECIMAL(14,2) NOT NULL DEFAULT 0, net DECIMAL(14,2) NOT NULL, discount_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_sf (enrollment_id, structure_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (enrollment_id) REFERENCES student_enrollments(id), FOREIGN KEY (structure_id) REFERENCES fee_structures(id)
) ENGINE=InnoDB;
CREATE TABLE installments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, student_fee_id BIGINT UNSIGNED NOT NULL, seq TINYINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL, amount DECIMAL(14,2) NOT NULL, paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0, status ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending',
  INDEX (school_id, due_date, status), FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (student_fee_id) REFERENCES student_fees(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, number VARCHAR(30) NOT NULL, guardian_id BIGINT UNSIGNED NULL,
  student_fee_id BIGINT UNSIGNED NULL, currency CHAR(3) NOT NULL, rate_to_base DECIMAL(18,6) NOT NULL, subtotal DECIMAL(14,2) NOT NULL,
  tax_percent DECIMAL(5,2) NULL, tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0, total DECIMAL(14,2) NOT NULL,
  status ENUM('issued','partial','paid','cancelled') NOT NULL DEFAULT 'issued', issued_on DATE NOT NULL, created_by BIGINT UNSIGNED NULL,
  cancelled_reason VARCHAR(255) NULL, UNIQUE KEY uq_inv (school_id, number), FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE invoice_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, invoice_id BIGINT UNSIGNED NOT NULL, description VARCHAR(200) NOT NULL, amount DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, invoice_id BIGINT UNSIGNED NULL, installment_id BIGINT UNSIGNED NULL,
  student_fee_id BIGINT UNSIGNED NULL, method_id INT UNSIGNED NULL, currency CHAR(3) NOT NULL, amount DECIMAL(14,2) NOT NULL, rate_to_base DECIMAL(18,6) NOT NULL,
  reference_no VARCHAR(100) NULL, paid_on DATE NOT NULL, received_by BIGINT UNSIGNED NULL, voided_at DATETIME NULL, void_reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB;
CREATE TABLE receipts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, school_id INT UNSIGNED NOT NULL, payment_id BIGINT UNSIGNED NOT NULL, number VARCHAR(30) NOT NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_rcpt (school_id, number), UNIQUE KEY uq_rcpt_pay (payment_id),
  FOREIGN KEY (school_id) REFERENCES schools(id), FOREIGN KEY (payment_id) REFERENCES payments(id)
) ENGINE=InnoDB;
