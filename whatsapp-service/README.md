# PrintWorks WhatsApp Microservice

Standalone Node.js service that connects to WhatsApp Web via Baileys and exposes a small REST API for the Laravel app.

## Setup

```bash
cd whatsapp-service
cp .env.example .env
# Edit .env — set API_KEY and LARAVEL_WEBHOOK_URL
npm install
npm start
```

Use the **same** `API_KEY` value in Laravel's `.env` as `WHATSAPP_API_KEY`.

## Environment

| Variable | Description |
|----------|-------------|
| `PORT` | HTTP port (default `3000`) |
| `API_KEY` | Shared secret — sent as `x-api-key` header |
| `LARAVEL_WEBHOOK_URL` | Full URL for incoming message webhook |

## API Endpoints

All endpoints require header: `x-api-key: <API_KEY>`

| Method | Path | Description |
|--------|------|-------------|
| GET | `/qr` | Current QR as base64 PNG, or `{ status: "connected" }` / `{ status: "waiting" }` |
| GET | `/status` | `{ status: "connected" \| "disconnected" \| "waiting_for_scan" }` |
| POST | `/send` | Body: `{ "number": "947XXXXXXXX", "message": "Hello" }` |
| POST | `/logout` | Clears session and generates a fresh QR |

## PM2 (production)

```bash
npm install -g pm2
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

## Session persistence

Auth state is stored in `./auth_session/`. After the first QR scan, the service reconnects automatically without a new scan unless you call `POST /logout`.

## Architecture

```
Laravel (UI, DB)  ←—— HTTP ——→  Node service (Baileys / WhatsApp Web)
         ↑
         └── webhook POST /whatsapp/webhook/incoming (incoming messages)
```

Keep this service on localhost — do not expose it publicly on your VPS.
