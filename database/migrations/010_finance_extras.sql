-- المرحلة 3: الرسوم — أعمدة وجداول إضافية
ALTER TABLE fee_structures
  ADD COLUMN installments_count TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER currency,
  ADD COLUMN first_due_date DATE NULL AFTER installments_count,
  ADD COLUMN interval_months TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER first_due_date;
ALTER TABLE tax_settings ADD COLUMN prices_include_tax TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE student_fees
  ADD COLUMN tax_percent DECIMAL(5,2) NULL, ADD COLUMN tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0, ADD COLUMN payable DECIMAL(14,2) NOT NULL DEFAULT 0,
  ADD COLUMN status ENUM('active','cancelled') NOT NULL DEFAULT 'active', ADD COLUMN cancel_reason VARCHAR(255) NULL, ADD COLUMN discount_reason VARCHAR(255) NULL;
ALTER TABLE payments
  ADD COLUMN fee_currency CHAR(3) NULL, ADD COLUMN fee_rate_to_base DECIMAL(18,6) NULL, ADD COLUMN applied_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  ADD INDEX idx_pay_fee (school_id, student_fee_id);
CREATE TABLE payment_allocations (
  payment_id BIGINT UNSIGNED NOT NULL, installment_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (payment_id, installment_id), FOREIGN KEY (payment_id) REFERENCES payments(id), FOREIGN KEY (installment_id) REFERENCES installments(id) ON DELETE CASCADE
) ENGINE=InnoDB;
