/**
 * Extra WhatsApp account (Safety Sign, etc.) — QR link + send.
 * PrintWorks keeps using the main session in index.js.
 */
const fs = require('fs');
const path = require('path');
const QRCode = require('qrcode');
const pino = require('pino');
const {
  default: makeWASocket,
  useMultiFileAuthState,
  DisconnectReason,
  fetchLatestBaileysVersion,
  Browsers,
  makeCacheableSignalKeyStore,
} = require('@whiskeysockets/baileys');

function createAccountSession({ id, authDir, apiKey, laravelWebhookUrl, laravelConnectedWebhookUrl, log }) {
  const state = {
    sock: null,
    currentQr: null,
    intentionalLogout: false,
    reconnectTimer: null,
    connectionStatus: 'disconnected',
    isStarting: false,
  };

  function logState(message) {
    const line = `[${id}] ${message}`;
    if (typeof log === 'function') log(line);
    else console.log(`[WhatsApp] ${line}`);
  }

  function clearAuthSession() {
    try {
      if (fs.existsSync(authDir)) {
        fs.rmSync(authDir, { recursive: true, force: true });
        logState('Auth session cleared.');
      }
    } catch (e) {
      logState(`Auth clear warning: ${e.message}`);
      try {
        if (fs.existsSync(authDir)) {
          for (const name of fs.readdirSync(authDir)) {
            try { fs.unlinkSync(path.join(authDir, name)); } catch (_) {}
          }
        }
      } catch (_) {}
    }
  }

  function scheduleReconnect(delayMs, reason) {
    if (state.intentionalLogout) {
      logState(`Skip reconnect (${reason}) — logout in progress.`);
      return;
    }
    if (state.reconnectTimer) clearTimeout(state.reconnectTimer);
    state.reconnectTimer = setTimeout(() => {
      state.reconnectTimer = null;
      start().catch(() => {});
    }, delayMs);
  }

  function getLinkedPhone() {
    if (!state.sock?.user?.id) return null;
    const num = String(state.sock.user.id).split(':')[0].replace(/\D/g, '');
    return /^\d{8,15}$/.test(num) ? num : null;
  }

  async function qrToBase64Png(qrString) {
    return QRCode.toDataURL(qrString, { margin: 1, width: 320 }).then((dataUrl) => {
      const parts = String(dataUrl).split(',');
      return parts[1] || '';
    });
  }

  async function forwardToLaravel(payload) {
    if (!laravelWebhookUrl) return;
    try {
      payload.account = id;
      await fetch(laravelWebhookUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'x-api-key': apiKey },
        body: JSON.stringify(payload),
      });
    } catch (e) {
      logState(`Webhook error: ${e.message}`);
    }
  }

  async function notifyLaravelConnected() {
    const phone = getLinkedPhone();
    if (!phone || !laravelConnectedWebhookUrl) return;
    try {
      const response = await fetch(laravelConnectedWebhookUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'x-api-key': apiKey },
        body: JSON.stringify({ phone, account: id }),
      });
      if (!response.ok) {
        const body = await response.text();
        logState(`Connected webhook failed (${response.status}): ${body}`);
      } else {
        logState(`Connected webhook sent for +${phone}`);
      }
    } catch (e) {
      logState(`Connected webhook error: ${e.message}`);
    }
  }

  async function start() {
    if (state.isStarting) return;
    if (state.sock && state.connectionStatus === 'connected') return;

    state.isStarting = true;
    state.connectionStatus = 'connecting';
    state.currentQr = null;
    logState('Connecting...');

    if (state.sock) {
      try { state.sock.end(undefined); } catch (e) {}
      state.sock = null;
    }

    try {
      if (!fs.existsSync(authDir)) {
        fs.mkdirSync(authDir, { recursive: true });
      }

      const { state: authState, saveCreds } = await useMultiFileAuthState(authDir);
      const { version } = await fetchLatestBaileysVersion();

      const sock = makeWASocket({
        version,
        auth: {
          creds: authState.creds,
          keys: makeCacheableSignalKeyStore(authState.keys, pino({ level: 'silent' })),
        },
        printQRInTerminal: false,
        logger: pino({ level: 'silent' }),
        browser: Browsers.ubuntu('Chrome'),
        syncFullHistory: false,
      });

      state.sock = sock;
      sock.ev.on('creds.update', saveCreds);

      sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
          state.currentQr = qr;
          state.connectionStatus = 'waiting_for_scan';
          logState('QR ready — scan with this brand phone.');
        }

        if (connection === 'open') {
          state.currentQr = null;
          state.connectionStatus = 'connected';
          state.isStarting = false;
          logState(`Connected as +${getLinkedPhone() || '?'}`);
          notifyLaravelConnected().catch(() => {});
        }

        if (connection === 'close') {
          state.connectionStatus = 'disconnected';
          state.isStarting = false;
          state.currentQr = null;
          const code = lastDisconnect?.error?.output?.statusCode;
          const loggedOut = code === DisconnectReason.loggedOut;
          logState(`Disconnected (code ${code || '?'}).`);
          if (state.intentionalLogout) return;
          if (loggedOut) {
            clearAuthSession();
            scheduleReconnect(2000, 'logged-out');
          } else {
            scheduleReconnect(code === DisconnectReason.restartRequired ? 1000 : 3000, 'close');
          }
        }
      });

      sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify' && type !== 'append') return;
        for (const msg of messages || []) {
          try {
            if (!msg?.message || msg.key?.fromMe) continue;
            const remoteJid = msg.key?.remoteJid || '';
            if (remoteJid.endsWith('@g.us') || remoteJid === 'status@broadcast') continue;
            const from = String(remoteJid).split('@')[0].split(':')[0].replace(/\D/g, '');
            if (!from) continue;
            let text = '';
            const m = msg.message;
            if (m.conversation) text = m.conversation;
            else if (m.extendedTextMessage?.text) text = m.extendedTextMessage.text;
            else if (m.imageMessage?.caption) text = m.imageMessage.caption;
            else if (m.documentMessage?.caption) text = m.documentMessage.caption;
            await forwardToLaravel({
              from,
              message: text || '',
              timestamp: msg.messageTimestamp
                ? new Date(Number(msg.messageTimestamp) * 1000).toISOString()
                : new Date().toISOString(),
              message_id: msg.key?.id || null,
              direction: 'in',
              is_history: false,
            });
          } catch (e) {
            logState(`Incoming handle error: ${e.message}`);
          }
        }
      });

      state.isStarting = false;
    } catch (e) {
      state.isStarting = false;
      state.connectionStatus = 'disconnected';
      logState(`Start failed: ${e.message}`);
      scheduleReconnect(4000, 'start-failed');
    }
  }

  async function getQrPayload() {
    if (state.connectionStatus === 'connected') {
      return { status: 'connected', phone: getLinkedPhone(), account: id };
    }
    if (state.currentQr) {
      const qr = await qrToBase64Png(state.currentQr);
      return { status: 'waiting_for_scan', qr, account: id };
    }
    if (!state.isStarting && state.connectionStatus !== 'connecting') {
      scheduleReconnect(100, 'qr-request');
    }
    return { status: 'waiting', account: id };
  }

  function getStatusPayload() {
    return {
      status: state.connectionStatus,
      phone: getLinkedPhone(),
      account: id,
    };
  }

  async function sendMessage({ number, message, media_type, media_base64, media_mimetype, media_filename }) {
    if (state.connectionStatus !== 'connected' || !state.sock) {
      const err = new Error('WhatsApp is not connected.');
      err.status = 503;
      throw err;
    }

    const normalizedNumber = String(number).replace(/\D/g, '');
    if (!/^\d{8,15}$/.test(normalizedNumber)) {
      const err = new Error('number must be in international format without + or spaces (8–15 digits).');
      err.status = 422;
      throw err;
    }

    let waPayload;
    if (media_type && media_base64) {
      const buf = Buffer.from(media_base64, 'base64');
      if (media_type === 'image') {
        waPayload = { image: buf, mimetype: media_mimetype || 'image/jpeg', caption: String(message || '') };
      } else if (media_type === 'document') {
        waPayload = {
          document: buf,
          mimetype: media_mimetype || 'application/octet-stream',
          fileName: media_filename || 'file',
          caption: String(message || ''),
        };
      } else if (media_type === 'video') {
        waPayload = { video: buf, mimetype: media_mimetype || 'video/mp4', caption: String(message || '') };
      } else {
        waPayload = { text: String(message || '') };
      }
    } else {
      waPayload = { text: String(message || '') };
    }

    const jid = `${normalizedNumber}@s.whatsapp.net`;
    const result = await state.sock.sendMessage(jid, waPayload);
    return { success: true, message_id: result?.key?.id || null, account: id };
  }

  async function logout() {
    state.intentionalLogout = true;
    if (state.reconnectTimer) {
      clearTimeout(state.reconnectTimer);
      state.reconnectTimer = null;
    }
    const oldSock = state.sock;
    state.sock = null;
    state.currentQr = null;
    state.connectionStatus = 'disconnected';
    state.isStarting = false;

    if (oldSock) {
      try { oldSock.ev.removeAllListeners(); } catch (e) {}
      try { await oldSock.logout(); } catch (e) {}
      try { oldSock.end(undefined); } catch (e) {}
    }

    await new Promise((r) => setTimeout(r, 800));
    clearAuthSession();

    setTimeout(() => {
      state.intentionalLogout = false;
      start().catch((e) => logState(`Post-logout start failed: ${e.message}`));
    }, 2000);

    return { success: true, message: 'Logged out. A fresh QR will be generated.', account: id };
  }

  return {
    id,
    start,
    getQrPayload,
    getStatusPayload,
    sendMessage,
    logout,
    getLinkedPhone,
    get connectionStatus() {
      return state.connectionStatus;
    },
  };
}

module.exports = { createAccountSession };
