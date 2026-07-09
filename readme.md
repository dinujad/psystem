# PrintWorks ERP

Business management system for print shops and production houses.

## Stack

- Laravel 9 + MySQL
- Bootstrap admin UI
- WhatsApp service (Node.js) — `whatsapp-service/`

## Deploy (Coolify)

Requires **PHP 8.2+** in `composer.json` (Nixpacks no longer supports PHP 8.0).

Post-deployment command:

```bash
bash deploy.sh
```

### File storage — local VPS disk (default)

Mount persistent volumes:

- `public/uploads`
- `storage/app/public`

### File storage — Cloudflare R2 (recommended for production)

Set in Coolify environment:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-r2-access-key
AWS_SECRET_ACCESS_KEY=your-r2-secret
AWS_DEFAULT_REGION=auto
AWS_BUCKET=erp
AWS_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=https://pub-YOUR_ID.r2.dev
```

Enable **public access** on the R2 bucket (or use a custom domain) so `AWS_URL` serves images/PDFs.

When using R2, VPS upload volumes are optional — files go to the cloud bucket.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build   # if needed
```
