-- المرحلة 7: صور في الأسئلة وخيارات الاختيار من متعدد (ملفات في files بسياق question)
ALTER TABLE question_bank ADD COLUMN image_file_id BIGINT UNSIGNED NULL, ADD COLUMN option_images JSON NULL;
