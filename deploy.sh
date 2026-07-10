#!/bin/bash
# PrintWorks — run after every deploy (Coolify: set as "Post Deployment Command")
# Example: bash deploy.sh

set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

echo "==> Ensuring upload folders & storage link..."
bash scripts/ensure-upload-dirs.sh

echo "==> Clearing caches..."
php artisan optimize:clear

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding Walk-In Customer (safe to re-run)..."
php artisan db:seed --class=WalkInCustomerSeeder --force

echo "==> Rebuilding caches..."
php artisan config:cache
# routes/web.php has Closure-based routes, which cannot be serialized by
# route:cache — skip it (harmless, just no route-cache perf boost) so this
# script doesn't abort (set -e) before view:cache runs.
php artisan route:cache || echo "==> route:cache skipped (closure routes present)"
php artisan view:cache

echo ""
echo "Deploy complete!"
echo "Ensure Coolify persistent volumes are mounted for:"
echo "  - public/uploads"
echo "  - storage/app/public"
