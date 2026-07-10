#!/bin/bash
set -e
cd /var/www/html

bash scripts/ensure-upload-dirs.sh
bash scripts/ensure-env-file.sh 2>/dev/null || true
php artisan config:clear 2>/dev/null || true

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
