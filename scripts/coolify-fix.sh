#!/bin/bash
# One-shot fix for HTTP 500 after Coolify deploy (stale config cache).
set -e
cd "$(dirname "$0")/.."

echo "==> Removing stale bootstrap caches..."
rm -f bootstrap/cache/config.php bootstrap/cache/routes*.php

echo "==> Clearing Laravel caches..."
php artisan optimize:clear

echo "==> Checking APP_KEY..."
if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY is not set in Coolify Environment Variables."
  echo "Run: php artisan key:generate --show"
  exit 1
fi

echo "==> Checking database..."
php artisan migrate:status

echo "Done. Refresh https://erp.printworks.lk/login"
