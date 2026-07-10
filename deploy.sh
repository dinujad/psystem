#!/bin/bash
# PrintWorks — run after every deploy (Coolify: set as "Post Deployment Command")
# Example: bash deploy.sh

set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo "==> Ensuring upload folders & storage link..."
bash scripts/ensure-upload-dirs.sh

echo "==> Ensuring .env file (Coolify)..."
bash scripts/ensure-env-file.sh

echo "==> Clearing caches..."
rm -f bootstrap/cache/config.php bootstrap/cache/routes*.php
php artisan optimize:clear

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding Walk-In Customer (safe to re-run)..."
php artisan db:seed --class=WalkInCustomerSeeder --force

# Do NOT run config:cache / route:cache / view:cache on Coolify.
# Env vars are injected at runtime; cached config causes 500 errors
# (e.g. "Target class [view] does not exist") when env changes or is incomplete.

echo ""
echo "Deploy complete!"
echo "Ensure Coolify persistent volumes are mounted for:"
echo "  - public/uploads"
echo "  - storage/app/public"
