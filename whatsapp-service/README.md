# PrintWorks WhatsApp Microservice

Standalone Node.js service that connects to WhatsApp Web via Baileys and exposes a small REST API for the Laravel app.

**Do not run this inside the Laravel/PHP Coolify container** — that image has no `npm`. Deploy it as a **separate** Coolify application.

---

## Coolify deploy (separate app)

### 1. Create application
1. Coolify → your project (`erp` / `production`) → **+ New** → **Application**
2. Connect the **same GitHub repo** (`dinujad/psystem`)
3. Name it e.g. `whatsapp` / `printworks-whatsapp`

### 2. Build / General settings

| Setting | Value |
|---------|--------|
| **Base Directory** | `whatsapp-service` |
| **Build Pack** | Dockerfile |
| **Dockerfile Location** | `Dockerfile` (inside base dir) |
| **Ports Exposes** | `3000` |
| **Public Port** | optional — prefer **not** exposing publicly; Laravel should use the **internal** URL |

### 3. Environment variables

```env
PORT=3000
API_KEY=printworks-wa-secret-2026
LARAVEL_WEBHOOK_URL=https://erp.printworks.lk/whatsapp/webhook/incoming
```

Use the **same** `API_KEY` as Laravel `WHATSAPP_API_KEY`.

### 4. Persistent storage (keep QR session)
Add a volume so you do not re-scan QR after every deploy:

| Source (Coolify volume) | Destination |
|-------------------------|-------------|
| (new volume) | `/app/auth_session` |

Optional backup path if used: `/app/auth_session_backup`

### 5. Deploy
Click **Deploy**. Logs should show:
- `Service listening on port 3000`
- then `Connected.` **or** `waiting_for_scan`

First time: open ERP → **WhatsApp → Link** → scan QR.

### 6. Point Laravel ERP at this service
In the **Laravel** Coolify app environment:

```env
WHATSAPP_SERVICE_URL=http://<whatsapp-container-or-service-name>:3000
WHATSAPP_API_KEY=printworks-wa-secret-2026
```

How to get the internal URL in Coolify:
- Open the WhatsApp app → **Links** / network / service name
- Or use Coolify’s internal hostname for that service (same Docker network as ERP)
- Example shapes: `http://whatsapp:3000` or `http://<coolify-service-uuid>:3000`

Redeploy / restart the **ERP** app after saving those env vars (or clear config cache).

### 7. Quick health check
From ERP container terminal (optional):

```bash
wget -qO- --header="x-api-key: YOUR_API_KEY" http://<whatsapp-host>:3000/status
```

Expect JSON like `{ "status": "connected" }`.

---

## Local setup

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
| POST | `/send` | Body: `{ "number": "947XXXXXXXX", "message": "Hello" }` (+ optional PDF media fields) |
| POST | `/logout` | Clears session and generates a fresh QR |

## Session persistence

Auth state is stored in `./auth_session/`. After the first QR scan, the service reconnects automatically without a new scan unless you call `POST /logout`.

## Architecture

```
Laravel (UI, DB)  ←—— HTTP ——→  Node service (Baileys / WhatsApp Web)
         ↑
         └── webhook POST /whatsapp/webhook/incoming (incoming messages)
```

Keep this service on the private Docker network when possible — do not expose it publicly unless you must.
