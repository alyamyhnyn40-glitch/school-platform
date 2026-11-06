#!/usr/bin/env bash
# يولّد database/schema.sql من ملفات migrations بالترتيب
cd "$(dirname "$0")/.." && { echo "-- schema.sql: تجميع مرتب لكل migrations (يُولَّد بـ bin/build-schema.sh). للتثبيت الأنظف استخدم معالج /install"; cat database/migrations/*.sql; } > database/schema.sql
