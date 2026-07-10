#!/bin/bash
# Creates upload/storage folders required on fresh deploy (Coolify, VPS, etc.)
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

DIRS=(
    "public/uploads/temp"
    "public/uploads/img"
    "public/uploads/documents"
    "public/uploads/media"
    "public/uploads/business_logos"
    "public/uploads/invoice_logos"
    "public/uploads/carousel_images"
    "public/uploads/cms"
    "public/uploads/UltimatePOS"
    "storage/app/public/production"
    "storage/app/public/whatsapp"
    "storage/app/public/whatsapp/avatars"
    "storage/framework/cache"
    "storage/framework/sessions"
    "storage/framework/views"
    "storage/logs"
    "bootstrap/cache"
)

echo "==> Ensuring upload & storage directories..."
for dir in "${DIRS[@]}"; do
    mkdir -p "$dir"
    touch "$dir/.gitkeep" 2>/dev/null || true
done

# Laravel public/storage symlink (production files, WhatsApp media)
if [ ! -L "public/storage" ] && [ ! -d "public/storage" ]; then
    php artisan storage:link 2>/dev/null || ln -sf ../storage/app/public public/storage
fi

# PHP-FPM runs as www-data in Nixpacks/Coolify — writable dirs required for
# bootstrap/cache (package discovery) and public/uploads (file uploads).
if id www-data >/dev/null 2>&1; then
    chown -R www-data:www-data storage bootstrap/cache public/uploads 2>/dev/null || true
fi
chmod -R ug+rwX storage bootstrap/cache public/uploads 2>/dev/null || true
# Installer + Laravel need full storage tree writable by www-data (PHP-FPM).
chmod -R 777 storage bootstrap/cache public/uploads 2>/dev/null || true

echo "==> Upload directories ready."
