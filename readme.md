# PrintWorks ERP

Business management system for print shops and production houses.

## Stack

- Laravel 9 + MySQL
- Bootstrap admin UI
- WhatsApp service (Node.js) — `whatsapp-service/`

## Deploy (Coolify)

Post-deployment command:

```bash
bash deploy.sh
```

Mount persistent volumes:

- `public/uploads`
- `storage/app/public`

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build   # if needed
```
