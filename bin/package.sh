#!/usr/bin/env bash
# bin/package.sh X.Y  → school-platform-vX.Y.zip (مع vendor بلا dev، بلا .env والسجلات والرفع)
set -e
V="${1:?الإصدار مثل 0.1}"; ROOT="$(cd "$(dirname "$0")/.." && pwd)"; STG="$(mktemp -d)"; NAME=school-platform
rsync -a --exclude='.env' --exclude='/storage/logs/*' --exclude='/storage/uploads/*' --exclude='/storage/cache/*' --exclude='/storage/backups/*' \
  --exclude='/storage/installed.lock' --exclude='.phpunit.cache' --exclude='*.zip' --exclude='/vendor' --exclude='/tests' --exclude='.git' "$ROOT/" "$STG/$NAME/"
mkdir -p "$STG/$NAME/storage/"{logs,uploads,cache,backups}; touch "$STG/$NAME/storage/"{logs,uploads,cache,backups}/.gitkeep
( cd "$STG/$NAME" && composer install --no-dev --optimize-autoloader --no-interaction -q )
( cd "$STG" && zip -qr "$ROOT/$NAME-v$V.zip" "$NAME" )
rm -rf "$STG"; ls -la "$ROOT/$NAME-v$V.zip"
