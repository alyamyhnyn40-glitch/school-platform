-- المرحلة 7: المساعد الذكي — عدّاد استخدام يومي لكل مستخدم (لا تُخزَّن نصوص المحادثات)
CREATE TABLE assistant_usage (
  user_id BIGINT UNSIGNED NOT NULL, day DATE NOT NULL, n INT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (user_id, day)
) ENGINE=InnoDB;
