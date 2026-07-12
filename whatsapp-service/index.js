require('dotenv').config();

const express = require('express');
const fs = require('fs');
const path = require('path');
const QRCode = require('qrcode');
const pino = require('pino');
const {
  default: makeWASocket,
  useMultiFileAuthState,
  DisconnectReason,
  fetchLatestBaileysVersion,
  isJidGroup,
  isJidBroadcast,
  isJidNewsletter,
  jidNormalizedUser,
  Browsers,
  downloadMediaMessage,
  makeCacheableSignalKeyStore,
} = require('@whiskeysockets/baileys');

const PORT = process.env.PORT || 3000;
const API_KEY = process.env.API_KEY;
const LARAVEL_WEBHOOK_URL = process.env.LARAVEL_WEBHOOK_URL;
const LARAVEL_CONTACT_WEBHOOK_URL = process.env.LARAVEL_CONTACT_WEBHOOK_URL
  || (LARAVEL_WEBHOOK_URL ? LARAVEL_WEBHOOK_URL.replace(/\/incoming\/?$/, '/contacts') : null);
const LARAVEL_LID_MERGE_URL = process.env.LARAVEL_LID_MERGE_URL
  || (LARAVEL_WEBHOOK_URL ? LARAVEL_WEBHOOK_URL.replace(/\/incoming\/?$/, '/lid-merge') : null);
const LARAVEL_CONNECTED_WEBHOOK_URL = process.env.LARAVEL_CONNECTED_WEBHOOK_URL
  || (LARAVEL_WEBHOOK_URL ? LARAVEL_WEBHOOK_URL.replace(/\/incoming\/?$/, '/connected') : null);
const AUTH_DIR = path.join(__dirname, 'auth_session');
const LID_MAP_FILE = path.join(__dirname, 'lid_map.json');
const CONTACT_STORE_FILE = path.join(__dirname, 'contact_store.json');

const logger = pino({ level: 'info' });

let sock = null;
let currentQr = null;

// Prevent duplicate webhook delivery for the same WhatsApp message id
const processedMessageIds = new Set();

// Manual contacts store — accumulates LID → realJid across reconnects
const contactStore = {}; // realJid → { lid, name }
let connectionStatus = 'disconnected';
let isStarting = false;

// In-memory store for recently sent messages (needed for Baileys retry/re-send on decrypt failure)
const msgStore = {};
const MSG_STORE_MAX = 500; // keep last 500 messages

// Phone number / phone-JID → LID JID (required for iPhone recipients)
const msisdnToLid = {};

function storeSentMsg(key, msg) {
  msgStore[key] = msg;
  if (typeof key === 'string' && key.includes(':')) {
    const id = key.split(':').pop();
    if (id) msgStore[id] = msg;
  }
  const keys = Object.keys(msgStore);
  if (keys.length > MSG_STORE_MAX) {
    delete msgStore[keys[0]];
  }
}

function cacheMsisdnLid(phoneJid, lidJid) {
  if (!phoneJid || !lidJid) return;
  const lidKey = lidJid.endsWith('@lid') ? lidJid : `${lidJid}@lid`;
  const digits = String(phoneJid).replace(/\D/g, '').slice(-11);
  msisdnToLid[digits] = lidKey;
  msisdnToLid[phoneJid] = lidKey;
  if (phoneJid.endsWith('@s.whatsapp.net')) {
    msisdnToLid[phoneJid.replace('@s.whatsapp.net', '')] = lidKey;
  }
}

// ── LID → real phone-JID mapping (persisted to disk) ─────────────────────────
let lidToRealJid = {};

function loadLidMap() {
  try {
    if (fs.existsSync(LID_MAP_FILE)) {
      // Strip BOM if present (PowerShell UTF-8 can add it)
      const raw = fs.readFileSync(LID_MAP_FILE, 'utf8').replace(/^\uFEFF/, '');
      lidToRealJid = JSON.parse(raw);
      console.log(`[WhatsApp] LID map loaded: ${Object.keys(lidToRealJid).length} entries`);
      for (const [lid, realJid] of Object.entries(lidToRealJid)) {
        cacheMsisdnLid(realJid, lid);
      }
    } else {
      console.log('[WhatsApp] No lid_map.json found — starting fresh');
    }
  } catch (e) {
    console.log(`[WhatsApp] LID map load error: ${e.message} — starting fresh`);
    lidToRealJid = {};
  }
}

function saveLidMap() {
  try {
    fs.writeFileSync(LID_MAP_FILE, JSON.stringify(lidToRealJid, null, 2));
  } catch (e) {}
}

function loadContactStore() {
  try {
    if (fs.existsSync(CONTACT_STORE_FILE)) {
      const raw = fs.readFileSync(CONTACT_STORE_FILE, 'utf8').replace(/^\uFEFF/, '');
      const loaded = JSON.parse(raw);
      if (loaded && typeof loaded === 'object') {
        Object.assign(contactStore, loaded);
        console.log(`[WhatsApp] Contact store loaded: ${Object.keys(contactStore).length} entries`);
      }
    }
  } catch (e) {
    console.log(`[WhatsApp] Contact store load error: ${e.message}`);
  }
}

function saveContactStore() {
  try {
    fs.writeFileSync(CONTACT_STORE_FILE, JSON.stringify(contactStore, null, 2));
  } catch (e) {}
}

async function forwardLidMerge(lidJid, realJid) {
  if (!LARAVEL_LID_MERGE_URL) return;
  const lid = normalizeJidToNumber(lidJid);
  const real = normalizeJidToNumber(realJid);
  if (!lid || !real || lid === real) return;
  if (!/^\d{7,15}$/.test(real)) return;
  try {
    await fetch(LARAVEL_LID_MERGE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'x-api-key': API_KEY },
      body: JSON.stringify({ lid, real }),
    });
  } catch (e) {
    logState(`LID merge webhook error: ${e.message}`);
  }
}

function isLikelyLidNumber(num) {
  if (!num) return false;
  const s = String(num).replace(/\D/g, '');
  if (lidToRealJid[`${s}@lid`]) return true;
  if (s.length >= 13) return true;
  if (s.length > 15) return true;
  return false;
}

function resolveLidFromMap(num) {
  if (!num) return null;
  const s = String(num).replace(/\D/g, '');
  const mapped = lidToRealJid[`${s}@lid`];
  if (!mapped) return null;
  const real = normalizeJidToNumber(mapped);
  return /^\d{7,15}$/.test(real) ? real : null;
}

function storeLidMapping(lid, realJid) {
  if (!lid || !realJid) return;
  const key = lid.endsWith('@lid') ? lid : lid + '@lid';
  if (lidToRealJid[key] !== realJid) {
    lidToRealJid[key] = realJid;
    saveLidMap();
    forwardLidMerge(key, realJid).catch(() => {});
  }
  cacheMsisdnLid(realJid, key);
}
// ─────────────────────────────────────────────────────────────────────────────

// Queue for messages arriving before contacts are synced (LID not yet resolved)
const pendingMessages = [];

// Background queue for WhatsApp Web history sync (can be thousands of messages)
const historySyncQueue = [];
let historySyncRunning = false;
let historySyncTotal = 0;
let historySyncProcessed = 0;

const profileSyncQueue = [];
let profileSyncRunning = false;
const profileSyncedPhones = new Set();
const pendingContactSync = new Map();
let contactSyncTimer = null;

function logState(message) {
  logger.info(message);
  console.log(`[WhatsApp] ${message}`);
}

function authMiddleware(req, res, next) {
  const key = req.headers['x-api-key'];
  if (!API_KEY) return res.status(500).json({ error: 'API_KEY not configured.' });
  if (!key || key !== API_KEY) return res.status(401).json({ error: 'Unauthorized' });
  return next();
}

function clearAuthSession() {
  if (fs.existsSync(AUTH_DIR)) {
    fs.rmSync(AUTH_DIR, { recursive: true, force: true });
    logState('Auth session cleared.');
  }
}

async function forwardContactsBatch(contacts) {
  if (!LARAVEL_CONTACT_WEBHOOK_URL || !contacts?.length) return;
  const chunkSize = 40;
  for (let i = 0; i < contacts.length; i += chunkSize) {
    const chunk = contacts.slice(i, i + chunkSize);
    try {
      const response = await fetch(LARAVEL_CONTACT_WEBHOOK_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'x-api-key': API_KEY },
        body: JSON.stringify({ contacts: chunk }),
      });
      if (!response.ok) {
        const body = await response.text();
        logState(`Contact webhook failed (${response.status}): ${body}`);
      }
    } catch (error) {
      logState(`Contact webhook error: ${error.message}`);
    }
  }
}

function resolvePhoneFromContact(c) {
  if (!c) return null;
  if (c.id && (c.id.endsWith('@s.whatsapp.net') || c.id.endsWith('@c.us'))) {
    const num = normalizeJidToNumber(c.id);
    return /^\d{7,15}$/.test(num) ? num : null;
  }
  if (c.id && c.id.endsWith('@lid')) {
    const mapped = lidToRealJid[c.id];
    if (mapped) {
      const num = normalizeJidToNumber(mapped);
      return /^\d{7,15}$/.test(num) ? num : null;
    }
  }
  if (c.phoneNumber) {
    const num = String(c.phoneNumber).replace(/\D/g, '');
    if (/^\d{7,15}$/.test(num)) return num;
  }
  return null;
}

function contactDisplayName(c) {
  return c.notify || c.name || c.verifiedName || c.pushname || null;
}

function queueContactSync(phone, waName) {
  if (!phone || !waName) return;
  const clean = String(waName).trim();
  if (!clean) return;
  pendingContactSync.set(phone, clean);
}

async function flushContactSyncQueue() {
  if (!pendingContactSync.size) return;
  const batch = [...pendingContactSync.entries()].map(([phone, wa_name]) => ({ phone, wa_name }));
  pendingContactSync.clear();
  await forwardContactsBatch(batch);
  for (const row of batch) {
    enqueueProfilePictureSync(row.phone);
  }
}

function enqueueProfilePictureSync(phone) {
  if (!phone || profileSyncedPhones.has(phone)) return;
  if (!profileSyncQueue.includes(phone)) {
    profileSyncQueue.push(phone);
  }
  drainProfileSyncQueue().catch(() => {});
}

async function drainProfileSyncQueue() {
  if (profileSyncRunning || !sock || connectionStatus !== 'connected') return;
  profileSyncRunning = true;
  while (profileSyncQueue.length > 0) {
    const phone = profileSyncQueue.shift();
    if (!phone || profileSyncedPhones.has(phone)) continue;
    try {
      const jid = `${phone}@s.whatsapp.net`;
      const url = await sock.profilePictureUrl(jid, 'image');
      if (url) {
        const resp = await fetch(url);
        if (resp.ok) {
          const buf = Buffer.from(await resp.arrayBuffer());
          await forwardContactsBatch([{
            phone,
            profile_picture: buf.toString('base64'),
          }]);
        }
      }
      profileSyncedPhones.add(phone);
    } catch (e) {
      // no profile picture or fetch failed — mark tried to avoid loops
      profileSyncedPhones.add(phone);
    }
    await new Promise((r) => setTimeout(r, 350));
  }
  profileSyncRunning = false;
}

async function syncAllDeviceContacts() {
  if (!sock || connectionStatus !== 'connected') {
    return { success: false, message: 'Not connected' };
  }

  const seen = new Set();

  for (const [realJid, meta] of Object.entries(contactStore)) {
    if (!meta?.name) continue;
    const phone = normalizeJidToNumber(realJid);
    if (/^\d{7,15}$/.test(phone) && !seen.has(phone)) {
      seen.add(phone);
      queueContactSync(phone, meta.name);
    }
  }

  if (sock.store?.contacts) {
    for (const [jid, c] of Object.entries(sock.store.contacts)) {
      const phone = resolvePhoneFromContact({ id: jid, ...c });
      const waName = contactDisplayName(c);
      if (phone && waName && !seen.has(phone)) {
        seen.add(phone);
        queueContactSync(phone, waName);
      }
    }
  }

  await flushContactSyncQueue();

  return { success: true, synced: seen.size };
}

async function forwardToLaravel(payload) {
  if (!LARAVEL_WEBHOOK_URL) return;
  try {
    const response = await fetch(LARAVEL_WEBHOOK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'x-api-key': API_KEY },
      body: JSON.stringify(payload),
    });
    if (!response.ok) {
      const body = await response.text();
      logState(`Webhook failed (${response.status}): ${body}`);
    }
  } catch (error) {
    logState(`Webhook error: ${error.message}`);
  }
}

function getLinkedPhone() {
  if (!sock?.user?.id) return null;
  const num = normalizeJidToNumber(sock.user.id);
  return /^\d{8,15}$/.test(num) ? num : null;
}

async function notifyLaravelConnected() {
  const phone = getLinkedPhone();
  if (!phone || !LARAVEL_CONNECTED_WEBHOOK_URL) return;
  try {
    const response = await fetch(LARAVEL_CONNECTED_WEBHOOK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'x-api-key': API_KEY },
      body: JSON.stringify({ phone }),
    });
    if (!response.ok) {
      const body = await response.text();
      logState(`Connected webhook failed (${response.status}): ${body}`);
    } else {
      logState(`Connected webhook sent for +${phone}`);
    }
  } catch (error) {
    logState(`Connected webhook error: ${error.message}`);
  }
}

function extractTextMessage(message) {
  if (!message) return null;
  return (
    message.conversation ||
    message.extendedTextMessage?.text ||
    message.imageMessage?.caption ||
    message.videoMessage?.caption ||
    message.documentMessage?.caption ||
    null
  );
}

/**
 * Detect media type from message object.
 * Returns { mediaType, mimetype, filename } or null if text-only.
 */
function detectMedia(message) {
  if (!message) return null;
  if (message.imageMessage) {
    return {
      mediaType: 'image',
      mimetype: message.imageMessage.mimetype || 'image/jpeg',
      filename: 'image.jpg',
      msgType: 'imageMessage',
    };
  }
  if (message.documentMessage) {
    return {
      mediaType: 'document',
      mimetype: message.documentMessage.mimetype || 'application/octet-stream',
      filename: message.documentMessage.fileName || 'file',
      msgType: 'documentMessage',
    };
  }
  if (message.videoMessage) {
    return {
      mediaType: 'video',
      mimetype: message.videoMessage.mimetype || 'video/mp4',
      filename: 'video.mp4',
      msgType: 'videoMessage',
    };
  }
  if (message.audioMessage) {
    return {
      mediaType: 'audio',
      mimetype: message.audioMessage.mimetype || 'audio/ogg',
      filename: 'audio.ogg',
      msgType: 'audioMessage',
    };
  }
  return null;
}

async function downloadMediaAsBase64(msg) {
  try {
    const buffer = await downloadMediaMessage(
      msg,
      'buffer',
      {},
      { logger: pino({ level: 'silent' }), reuploadRequest: sock?.updateMediaMessage }
    );
    return buffer ? buffer.toString('base64') : null;
  } catch (e) {
    logState(`Media download error: ${e.message}`);
    return null;
  }
}

function normalizeJidToNumber(jid) {
  if (!jid) return '';
  return jid.split('@')[0].split(':')[0];
}

/**
 * Skip JIDs that are definitely not personal 1-on-1 senders.
 */
function shouldSkipJid(jid) {
  if (!jid) return true;
  if (isJidGroup(jid)) return true;
  if (isJidBroadcast(jid)) return true;
  if (isJidNewsletter(jid)) return true;
  if (jid === 'status@broadcast') return true;
  return false;
}

/**
 * Try every available source to extract the sender's real phone number.
 *
 *  1. participant (set by Baileys for both group and new-protocol DM)
 *  2. remoteJid if it is a @s.whatsapp.net or @c.us JID
 *  3. lidToRealJid map (populated from contacts events, persisted to disk)
 *  4. sock.contacts internal store
 */
function resolveSenderPhone(msg) {
  const remoteJid  = msg.key.remoteJid || '';
  const participant = msg.key.participant || '';

  // 0. senderPn / participantPn — WhatsApp sends the REAL phone number JID
  //    directly on the message key even when the chat is addressed by LID.
  //    This is exactly how WhatsApp Web resolves the number. Highest priority.
  const pnFields = [msg.key.senderPn, msg.key.participantPn];
  for (const pn of pnFields) {
    if (pn) {
      const num = normalizeJidToNumber(pn);
      if (/^\d{7,15}$/.test(num)) {
        // Cache LID → PN so future messages (and sends) resolve instantly
        const lid = msg.key.senderLid || msg.key.participantLid ||
          (remoteJid.endsWith('@lid') ? remoteJid : null);
        if (lid) storeLidMapping(lid, pn.endsWith('@s.whatsapp.net') ? pn : `${num}@s.whatsapp.net`);
        logState(`Resolved real number via senderPn: ${remoteJid} → ${num}`);
        return num;
      }
    }
  }

  // 1. participant — Baileys sometimes sets this for DMs in new protocol
  if (participant) {
    const resolved = resolveOneJid(participant);
    if (resolved) return resolved;
  }

  // 2. remoteJid is already a phone JID
  if (remoteJid.endsWith('@s.whatsapp.net') || remoteJid.endsWith('@c.us')) {
    const num = normalizeJidToNumber(remoteJid);
    if (/^\d{7,15}$/.test(num)) return num;
  }

  // 3. remoteJid is LID — look up in persisted map
  if (remoteJid.endsWith('@lid')) {
    const mapped = lidToRealJid[remoteJid];
    if (mapped) {
      const num = normalizeJidToNumber(mapped);
      if (/^\d{7,15}$/.test(num)) return num;
    }

    // 4. contactStore — manually accumulated from contacts.upsert events
    for (const [realJid, contact] of Object.entries(contactStore)) {
      if (contact && contact.lid) {
        const lidKey = contact.lid.endsWith('@lid') ? contact.lid : contact.lid + '@lid';
        if (lidKey === remoteJid) {
          const num = normalizeJidToNumber(realJid);
          if (/^\d{7,15}$/.test(num)) {
            storeLidMapping(remoteJid, realJid);
            logState(`Resolved LID via contactStore: ${remoteJid} → ${num}`);
            return num;
          }
        }
      }
    }

    // 5. sock.contacts internal store (Baileys keeps this internally)
    if (sock && sock.contacts) {
      for (const [realJid, contact] of Object.entries(sock.contacts)) {
        if (
          contact &&
          (contact.lid === remoteJid ||
           contact.lid === normalizeJidToNumber(remoteJid) ||
           contact.lidJid === remoteJid)
        ) {
          const num = normalizeJidToNumber(realJid);
          if (/^\d{7,15}$/.test(num)) {
            storeLidMapping(remoteJid, realJid);
            return num;
          }
        }
      }
    }
  }

  return null;
}

function resolveOneJid(jid) {
  if (!jid) return null;
  if (jid.endsWith('@s.whatsapp.net') || jid.endsWith('@c.us')) {
    const num = normalizeJidToNumber(jid);
    return /^\d{7,15}$/.test(num) ? num : null;
  }
  if (jid.endsWith('@lid')) {
    const mapped = lidToRealJid[jid];
    if (mapped) {
      const num = normalizeJidToNumber(mapped);
      return /^\d{7,15}$/.test(num) ? num : null;
    }
  }
  return null;
}

function resolveChatPhone(msg, fromMe = false) {
  if (fromMe) {
    const remoteJid = msg.key.remoteJid || '';
    if (remoteJid.endsWith('@s.whatsapp.net') || remoteJid.endsWith('@c.us')) {
      const num = normalizeJidToNumber(remoteJid);
      return /^\d{7,15}$/.test(num) ? num : null;
    }
    if (remoteJid.endsWith('@lid')) {
      const mapped = lidToRealJid[remoteJid];
      if (mapped) {
        const num = normalizeJidToNumber(mapped);
        if (/^\d{7,15}$/.test(num)) return num;
      }
      // Do not return raw LID for outgoing — wait for mapping
      return null;
    }
    return null;
  }
  return resolveSenderPhone(msg);
}

function enqueueHistoryMessages(messages) {
  if (!messages || !messages.length) return;
  historySyncTotal += messages.length;
  for (const msg of messages) {
    historySyncQueue.push(msg);
  }
  drainHistorySyncQueue().catch(() => {});
}

async function drainHistorySyncQueue() {
  if (historySyncRunning) return;
  historySyncRunning = true;
  logState(`History sync queue started (${historySyncQueue.length} pending)`);

  while (historySyncQueue.length > 0) {
    const msg = historySyncQueue.shift();
    try {
      await processMessage(msg, { isHistory: true });
      historySyncProcessed++;
    } catch (e) {
      logState(`History sync message error: ${e.message}`);
    }
    // Throttle so Laravel/webhook is not overwhelmed
    if (historySyncProcessed % 25 === 0) {
      await new Promise((r) => setTimeout(r, 100));
    }
  }

  historySyncRunning = false;
  logState(`History sync queue idle (processed ${historySyncProcessed}/${historySyncTotal})`);
}

async function processMessage(msg, options = {}) {
  const { isHistory = false } = options;
  if (shouldSkipJid(msg.key.remoteJid)) return;

  const fromMe = !!msg.key.fromMe;
  const waMsgId = msg.key.id || null;
  if (waMsgId && processedMessageIds.has(waMsgId)) return;

  const text = extractTextMessage(msg.message);
  const media = detectMedia(msg.message);

  // Must have text or media
  if (!text && !media) return;

  let from = resolveChatPhone(msg, fromMe);

  // Resolve known LID → real phone before storing
  if (from && isLikelyLidNumber(from)) {
    const real = resolveLidFromMap(from);
    if (real) {
      logState(`Resolved LID via map before forward: ${from} → ${real}`);
      from = real;
    }
  }

  if (!from) {
    if (fromMe) return;
    // WhatsApp gave us only a LID (sender not in contacts). Forward IMMEDIATELY
    // using the LID number so the message shows up instantly — just like
    // WhatsApp Web — instead of waiting/dropping. Resolve real number in bg.
    const rawJid = msg.key.remoteJid || '';
    from = normalizeJidToNumber(rawJid);
    logState(`Unresolved JID, forwarding under LID immediately: ${rawJid} → ${from}`);
    if (rawJid.endsWith('@lid') && sock) {
      tryResolveLidAsync(rawJid).catch(() => {});
    }
    if (!from || !/^\d{6,20}$/.test(from)) {
      // Truly nothing usable — queue as last resort
      logState(`No usable identifier, queuing: ${rawJid}`);
      pendingMessages.push({ msg, attempts: 0, queuedAt: Date.now() });
      return;
    }
  }

  const timestamp = msg.messageTimestamp
    ? new Date(Number(msg.messageTimestamp) * 1000).toISOString()
    : new Date().toISOString();

  const payload = {
    from,
    message: text || '',
    timestamp,
    message_id: msg.key.id || null,
    direction: fromMe ? 'out' : 'in',
    is_history: isHistory,
  };

  // Download media and attach as base64 (skip heavy downloads during bulk history sync)
  if (media && !isHistory) {
    logState(`Incoming media (${media.mediaType}) from +${from}`);
    const base64 = await downloadMediaAsBase64(msg);
    if (base64) {
      payload.media_type     = media.mediaType;
      payload.media_mimetype = media.mimetype;
      payload.media_filename = media.filename;
      payload.media_base64   = base64;
    }
    // If caption is empty and there's media, use a placeholder
    if (!payload.message) payload.message = `[${media.mediaType}]`;
  } else if (media && isHistory) {
    payload.message = payload.message || `[${media.mediaType}]`;
  } else if (!fromMe) {
    logState(`Incoming from +${from}: "${(text || '').substring(0, 60)}"`);
  }

  await forwardToLaravel(payload);
  if (waMsgId) processedMessageIds.add(waMsgId);
}

/**
 * Attempt to resolve a LID by scanning numbers stored in lid_map.json or
 * by asking the Laravel app for known phone numbers via a resolve endpoint.
 * This is best-effort; failures are silently ignored.
 */
async function tryResolveLidAsync(lidJid) {
  if (!sock || connectionStatus !== 'connected') return;

  // Try all reverse entries in our current map — find ones that point to a real JID
  // and use those phones to query WhatsApp (in case the map was partially built)
  const knownPhones = Object.values(lidToRealJid)
    .map(jid => normalizeJidToNumber(jid))
    .filter(n => /^\d{7,15}$/.test(n));

  // Add any numbers we know about from previous sends
  if (!knownPhones.length) return;

  try {
    const waInfo = await sock.onWhatsApp(...knownPhones.slice(0, 50));
    let updated = false;
    for (const entry of (waInfo || [])) {
      if (entry.lid && entry.jid) {
        storeLidMapping(entry.lid, entry.jid);
        if (entry.lid === lidJid || entry.lid.split('@')[0] === lidJid.split('@')[0]) {
          updated = true;
        }
      }
    }
    if (updated) {
      logState(`Resolved LID ${lidJid} via onWhatsApp scan — flushing pending queue`);
      await flushPendingMessages();
    }
  } catch (e) {}
}

async function handleIncomingMessages(messages, options = {}) {
  for (const msg of messages) {
    await processMessage(msg, options);
  }
}

/** Retry queued messages that couldn't be resolved at arrival time */
async function flushPendingMessages() {
  const now = Date.now();
  const keep = [];

    for (const item of pendingMessages) {
      item.attempts++;
      let from = resolveSenderPhone(item.msg);

      if (!from && now - item.queuedAt < 30000 && item.attempts < 6) {
        // Keep retrying for up to 30 seconds
        keep.push(item);
        continue;
      }

      // After retries — use LID number as fallback so we never lose a message
      if (!from) {
        const rawJid = item.msg.key.remoteJid || '';
        from = normalizeJidToNumber(rawJid);
        logState(`Using LID as fallback phone for unresolvable JID: ${rawJid} → ${from}`);
      }

      if (from) {
        const waMsgId = item.msg.key.id || null;
        if (waMsgId && processedMessageIds.has(waMsgId)) continue;

        const text = extractTextMessage(item.msg.message);
        const media = detectMedia(item.msg.message);
        const timestamp = item.msg.messageTimestamp
          ? new Date(Number(item.msg.messageTimestamp) * 1000).toISOString()
          : new Date().toISOString();

        const payload = { from, message: text || '', timestamp, message_id: item.msg.key.id || null };

        if (media) {
          const base64 = await downloadMediaAsBase64(item.msg);
          if (base64) {
            payload.media_type     = media.mediaType;
            payload.media_mimetype = media.mimetype;
            payload.media_filename = media.filename;
            payload.media_base64   = base64;
          }
          if (!payload.message) payload.message = `[${media.mediaType}]`;
        }

        logState(`Resolved pending msg from +${from}: "${(text || '').substring(0, 60)}"`);
        await forwardToLaravel(payload);
        if (waMsgId) processedMessageIds.add(waMsgId);
      }
    }

  pendingMessages.length = 0;
  pendingMessages.push(...keep);
}

function processContactsArray(contacts) {
  let mapped = 0;
  for (const c of contacts) {
    const waName = contactDisplayName(c);
    const phone = resolvePhoneFromContact(c);

    // c.id = real phone JID,  c.lid = LID JID
    if (c.id && c.lid) {
      const lidKey = c.lid.endsWith('@lid') ? c.lid : c.lid + '@lid';
      lidToRealJid[lidKey] = c.id;
      cacheMsisdnLid(c.id, lidKey);
      contactStore[c.id] = { lid: lidKey, name: waName || c.notify || c.name || null };
      mapped++;
    }
    if (c.id && (c.id.endsWith('@s.whatsapp.net') || c.id.endsWith('@c.us'))) {
      contactStore[c.id] = { lid: null, name: waName || c.notify || c.name || null };
    }
    if (c.id && c.id.endsWith('@lid') && c.phone) {
      lidToRealJid[c.id] = c.phone + '@s.whatsapp.net';
      mapped++;
    }
    if (c.id && c.id.endsWith('@lid') && c.phoneNumber) {
      const ph = String(c.phoneNumber).replace(/\D/g, '');
      if (ph) {
        lidToRealJid[c.id] = ph + '@s.whatsapp.net';
        mapped++;
      }
    }

    if (phone && waName) {
      queueContactSync(phone, waName);
    }
  }
  if (mapped > 0) {
    saveLidMap();
    logState(`LID map updated: +${mapped} entries, total ${Object.keys(lidToRealJid).length}`);
    flushPendingMessages().catch(() => {});
  }
  if (pendingContactSync.size > 0) {
    clearTimeout(contactSyncTimer);
    contactSyncTimer = setTimeout(() => flushContactSyncQueue().catch(() => {}), 1500);
  }
  if (mapped > 0 || contacts.length > 0) {
    saveContactStore();
  }
}

async function startWhatsApp() {
  if (isStarting) return;
  // Already have a live socket — do not create another (causes 440 loop).
  if (sock && connectionStatus === 'connected') return;

  isStarting = true;
  connectionStatus = 'connecting';
  currentQr = null;
  logState('Connecting...');

  // Tear down any stale socket before opening a new one.
  if (sock) {
    try { sock.end(undefined); } catch (e) {}
    sock = null;
  }

  try {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
      version,
      auth: {
        creds: state.creds,
        keys: makeCacheableSignalKeyStore(state.keys, pino({ level: 'silent' })),
      },
      printQRInTerminal: false,
      logger: pino({ level: 'silent' }),
      browser: Browsers.ubuntu('Chrome'),
      syncFullHistory: true,
      // Upload fresh pre-keys before each send — reduces decrypt failures
      patchMessageBeforeSending: async (msg) => {
        try {
          if (sock?.uploadPreKeysToServerIfRequired) {
            await sock.uploadPreKeysToServerIfRequired();
          }
        } catch (e) { /* non-fatal */ }
        return msg;
      },
      // Lets Baileys re-send when recipient WhatsApp requests retry after decrypt failure
      getMessage: async (key) => {
        if (!key?.id) return undefined;
        const storeKey = `${key.remoteJid}:${key.id}`;
        if (msgStore[storeKey]) return msgStore[storeKey];
        if (msgStore[key.id]) return msgStore[key.id];
        return undefined;
      },
    });

    sock.ev.on('creds.update', saveCreds);

    // WhatsApp emits this when a LID contact shares their phone number
    sock.ev.on('chats.phoneNumberShare', ({ lid, jid }) => {
      if (lid && jid) {
        storeLidMapping(lid, jid);
        logState(`phoneNumberShare: ${lid} → ${jid}`);
        flushPendingMessages().catch(() => {});
      }
    });

    // ── Contact sync — builds LID → real phone mapping ──────────────────────
    sock.ev.on('contacts.upsert', (contacts) => {
      logState(`contacts.upsert: ${contacts.length} contacts`);
      // Log first 5 to understand structure
      contacts.slice(0, 5).forEach(c => logState(`CONTACT_UPSERT: ${JSON.stringify(c)}`));
      processContactsArray(contacts);
    });

    sock.ev.on('contacts.update', (updates) => {
      logState(`contacts.update: ${updates.length} updates`);
      // Log full data so we can see if real phone is here
      for (const c of updates) {
        logState(`CONTACT_UPDATE: ${JSON.stringify(c)}`);
        // Try every possible field for phone JID
        const realJid = c.phoneNumber || c.phone || null;
        if (realJid && c.id && c.id.endsWith('@lid')) {
          storeLidMapping(c.id, typeof realJid === 'string' && realJid.includes('@')
            ? realJid : `${String(realJid).replace(/\D/g,'')}@s.whatsapp.net`);
        }
      }
      processContactsArray(updates);
    });

    function syncNamesFromChats(chats) {
      if (!Array.isArray(chats) || !chats.length) return;
      let queued = 0;
      for (const chat of chats) {
        const phone = resolvePhoneFromContact({ id: chat.id });
        const name = chat.name || chat.subject || null;
        if (phone && name && !String(chat.id).endsWith('@g.us')) {
          queueContactSync(phone, name);
          const jid = `${phone}@s.whatsapp.net`;
          contactStore[jid] = { ...(contactStore[jid] || {}), name: String(name).trim() };
          queued++;
        }
      }
      if (queued > 0) {
        saveContactStore();
        clearTimeout(contactSyncTimer);
        contactSyncTimer = setTimeout(() => flushContactSyncQueue().catch(() => {}), 1500);
        logState(`Queued ${queued} chat display names for contact sync`);
      }
    }

    sock.ev.on('chats.upsert', (chats) => {
      logState(`chats.upsert: ${chats.length} chats`);
      syncNamesFromChats(chats);
    });

    sock.ev.on('chats.update', (updates) => {
      syncNamesFromChats(updates);
    });

    // messaging-history.set fires on initial chat sync (chats + messages + contacts)
    sock.ev.on('messaging-history.set', ({ contacts, messages, chats, progress, isLatest }) => {
      if (contacts && contacts.length) {
        logState(`messaging-history.set: ${contacts.length} contacts`);
        processContactsArray(contacts);
      }
      if (chats && chats.length) {
        logState(`messaging-history.set: ${chats.length} chats`);
        syncNamesFromChats(chats);
      }
      if (messages && messages.length) {
        logState(`messaging-history.set: ${messages.length} messages (progress ${progress ?? '?'})`);
        enqueueHistoryMessages(messages);
      }
      if (isLatest) {
        logState('WhatsApp history sync complete.');
        syncAllDeviceContacts().catch(() => {});
      }
    });
    // ────────────────────────────────────────────────────────────────────────

    sock.ev.on('connection.update', async (update) => {
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        currentQr = qr;
        connectionStatus = 'waiting_for_scan';
        logState('QR code generated — waiting for scan.');
      }

      if (connection === 'open') {
        currentQr = null;
        connectionStatus = 'connected';
        isStarting = false;
        logState('Connected.');

        // Tell Laravel which number is linked — clears stale inbox if account changed
        await notifyLaravelConnected();

        // Upload pre-keys so recipients can decrypt our messages.
        // Stale/exhausted pre-keys are the #1 cause of "Waiting for this message".
        try {
          if (sock && typeof sock.uploadPreKeysToServerIfRequired === 'function') {
            await sock.uploadPreKeysToServerIfRequired();
            logState('Pre-keys uploaded successfully.');
          }
        } catch (e) {
          logState(`Pre-key upload warning (non-fatal): ${e.message}`);
        }

        // Pull latest contacts/chat names from linked device
        try {
          if (sock?.resyncAppState) {
            await sock.resyncAppState(['regular', 'regular_high'], false);
            logState('App state resync requested (contacts/chats).');
          }
        } catch (e) {
          logState(`App state resync warning (non-fatal): ${e.message}`);
        }

        // Retry pending messages after contacts may sync
        setTimeout(() => flushPendingMessages().catch(() => {}), 5000);
        setTimeout(() => syncAllDeviceContacts().catch(() => {}), 6000);
      }

      if (connection === 'close') {
        currentQr = null;
        connectionStatus = 'disconnected';

        const statusCode = lastDisconnect?.error?.output?.statusCode;
        const loggedOut = statusCode === DisconnectReason.loggedOut;

        if (loggedOut) {
          logState('Logged out — clearing session.');
          clearAuthSession();
          sock = null;
          isStarting = false;
          setTimeout(() => startWhatsApp(), 2000);
          return;
        }

        logState(`Disconnected (code: ${statusCode ?? 'unknown'}) — reconnecting...`);
        try { sock?.end(undefined); } catch (e) {}
        sock = null;
        isStarting = false;
        // 440 = another client replaced us — wait longer before retry.
        const delay = statusCode === 440 ? 8000 : 3000;
        setTimeout(() => startWhatsApp(), delay);
      }
    });

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
      // Cache ALL messages (sent or received) for getMessage retry support
      for (const msg of messages) {
        if (msg.key?.id && msg.message) {
          const storeKey = `${msg.key.remoteJid}:${msg.key.id}`;
          storeSentMsg(storeKey, msg.message);
        }
      }
      if (type === 'notify') {
        await handleIncomingMessages(messages, { isHistory: false });
      } else if (type === 'append') {
        // Historical messages synced from phone / WhatsApp servers
        enqueueHistoryMessages(messages);
      }
    });

  } catch (error) {
    connectionStatus = 'disconnected';
    logState(`Startup error: ${error.message}`);
    sock = null;
    isStarting = false;
    setTimeout(() => startWhatsApp(), 5000);
  }
}

// Retry pending messages every 10 seconds
setInterval(() => {
  if (pendingMessages.length > 0) flushPendingMessages().catch(() => {});
}, 10000);

async function qrToBase64Png(qrString) {
  const dataUrl = await QRCode.toDataURL(qrString, {
    errorCorrectionLevel: 'M', margin: 2, width: 320,
  });
  return dataUrl.replace(/^data:image\/png;base64,/, '');
}

const app = express();
app.use(express.json({ limit: '64mb' }));
app.use(express.urlencoded({ limit: '64mb', extended: true }));

app.get('/health', (req, res) => {
  res.json({
    ok: true,
    status: connectionStatus,
    phone: getLinkedPhone(),
    lid_map_size: Object.keys(lidToRealJid).length,
    pending_messages: pendingMessages.length,
    history_sync_queue: historySyncQueue.length,
    history_sync_running: historySyncRunning,
    history_sync_processed: historySyncProcessed,
    history_sync_total: historySyncTotal,
  });
});

app.get('/qr', authMiddleware, async (req, res) => {
  if (connectionStatus === 'connected') {
    return res.json({ status: 'connected', phone: getLinkedPhone() });
  }
  if (!currentQr) return res.json({ status: 'waiting' });
  try {
    const qr = await qrToBase64Png(currentQr);
    return res.json({ status: 'waiting_for_scan', qr });
  } catch (error) {
    return res.status(500).json({ error: 'Failed to generate QR image.' });
  }
});

app.get('/status', authMiddleware, (req, res) => {
  res.json({ status: connectionStatus, phone: getLinkedPhone() });
});

app.post('/send', authMiddleware, async (req, res) => {
  const { number, message, media_type, media_base64, media_mimetype, media_filename } = req.body || {};

  // Require either a text message or media attachment
  if (!number || (!message && !media_base64)) {
    return res.status(422).json({ error: 'number and either message or media_base64 are required.' });
  }

  const normalizedNumber = String(number).replace(/\D/g, '');
  if (!/^\d{8,15}$/.test(normalizedNumber)) {
    return res.status(422).json({
      error: 'number must be in international format without + or spaces (8–15 digits).',
    });
  }

  if (connectionStatus !== 'connected' || !sock) {
    return res.status(503).json({ error: 'WhatsApp is not connected.' });
  }

  try {
    const { media_type, media_base64, media_mimetype, media_filename } = req.body || {};

    let waPayload;

    if (media_type && media_base64) {
      const buf = Buffer.from(media_base64, 'base64');
      if (media_type === 'image') {
        waPayload = {
          image: buf,
          mimetype: media_mimetype || 'image/jpeg',
          caption: String(message || ''),
        };
      } else if (media_type === 'document') {
        waPayload = {
          document: buf,
          mimetype: media_mimetype || 'application/octet-stream',
          fileName: media_filename || 'file',
          caption: String(message || ''),
        };
      } else if (media_type === 'video') {
        waPayload = {
          video: buf,
          mimetype: media_mimetype || 'video/mp4',
          caption: String(message || ''),
        };
      } else {
        waPayload = { text: String(message || '') };
      }
    } else {
      waPayload = { text: String(message) };
    }

    const result = await deliverMessage(normalizedNumber, waPayload);

    return res.json({ success: true, message_id: result?.key?.id || null });
  } catch (error) {
    logState(`Send failed: ${error.message}`);
    return res.status(500).json({ error: 'Failed to send message.', detail: error.message });
  }
});

/**
 * Resolve a batch of LIDs or phone numbers.
 * POST /resolve-lids  body: { phones: ['947...', ...], lids: ['269...', ...] }
 * Returns { mappings: { 'LID@lid': '94xxx@s.whatsapp.net', ... } }
 */
app.post('/resolve-lids', authMiddleware, async (req, res) => {
  const { phones = [] } = req.body || {};
  if (!sock || connectionStatus !== 'connected') {
    return res.status(503).json({ error: 'Not connected.' });
  }
  const results = {};
  try {
    const cleaned = phones.map(p => String(p).replace(/\D/g, '')).filter(p => /^\d{7,15}$/.test(p));
    if (cleaned.length) {
      const waInfo = await sock.onWhatsApp(...cleaned);
      for (const entry of (waInfo || [])) {
        if (entry.lid) {
          storeLidMapping(entry.lid, entry.jid);
          results[entry.lid] = entry.jid;
        }
      }
    }
    return res.json({ mappings: results, lid_map_size: Object.keys(lidToRealJid).length });
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
});

// Track sent status message IDs for delete API
const sentStatusMessages = {};

function getStatusJidList() {
  const jids = new Set();

  for (const realJid of Object.keys(contactStore)) {
    if (realJid.endsWith('@s.whatsapp.net')) {
      jids.add(realJid);
    }
  }

  if (sock && sock.contacts) {
    for (const jid of Object.keys(sock.contacts)) {
      if (jid.endsWith('@s.whatsapp.net')) {
        jids.add(jid);
      }
    }
  }

  for (const realJid of Object.values(lidToRealJid)) {
    if (typeof realJid === 'string' && realJid.endsWith('@s.whatsapp.net')) {
      jids.add(realJid);
    }
  }

  return [...jids];
}

function ensureConnected(res) {
  if (connectionStatus !== 'connected' || !sock) {
    res.status(503).json({ error: 'WhatsApp is not connected.' });
    return false;
  }
  return true;
}

function normalizePhoneNumber(number) {
  const normalizedNumber = String(number || '').replace(/\D/g, '');
  if (!/^\d{8,15}$/.test(normalizedNumber)) {
    return null;
  }
  return normalizedNumber;
}

async function resolveSendJid(normalizedNumber) {
  const phoneJid = `${normalizedNumber}@s.whatsapp.net`;

  if (msisdnToLid[normalizedNumber]) {
    return msisdnToLid[normalizedNumber];
  }
  if (msisdnToLid[phoneJid]) {
    return msisdnToLid[phoneJid];
  }

  // Reverse lookup from persisted LID map
  for (const [lid, realJid] of Object.entries(lidToRealJid)) {
    const realDigits = String(realJid).replace(/\D/g, '');
    if (realDigits.endsWith(normalizedNumber) || realDigits === normalizedNumber) {
      cacheMsisdnLid(phoneJid, lid);
      return lid.endsWith('@lid') ? lid : `${lid}@lid`;
    }
  }

  if (!sock || connectionStatus !== 'connected') {
    return phoneJid;
  }

  try {
    const waInfo = await sock.onWhatsApp(normalizedNumber);
    const entry = waInfo && waInfo[0];
    if (!entry) return phoneJid;

    const resolvedPhoneJid = entry.jid || phoneJid;

    if (entry.lid) {
      const lidJid = String(entry.lid).includes('@') ? entry.lid : `${entry.lid}@lid`;
      storeLidMapping(lidJid, resolvedPhoneJid);
      cacheMsisdnLid(resolvedPhoneJid, lidJid);
      logState(`Send JID resolved via LID: ${normalizedNumber} → ${lidJid}`);
      return lidJid;
    }

    return resolvedPhoneJid;
  } catch (e) {
    logState(`resolveSendJid warning: ${e.message}`);
    return phoneJid;
  }
}

async function deliverMessage(normalizedNumber, waPayload) {
  const targetJid = await resolveSendJid(normalizedNumber);
  const result = await sock.sendMessage(targetJid, waPayload);

  if (result?.key?.id && result.message) {
    const remoteJid = result.key.remoteJid || targetJid;
    storeSentMsg(`${remoteJid}:${result.key.id}`, result.message);
    storeSentMsg(`${normalizedNumber}@s.whatsapp.net:${result.key.id}`, result.message);
  }

  return result;
}

async function mapRecipientLid(normalizedNumber) {
  await resolveSendJid(normalizedNumber);
}

app.post('/send-poll', authMiddleware, async (req, res) => {
  const { number, question, options, selectable_count } = req.body || {};

  if (!number || !question || !options) {
    return res.status(422).json({ error: 'number, question and options are required.' });
  }

  const normalizedNumber = normalizePhoneNumber(number);
  if (!normalizedNumber) {
    return res.status(422).json({ error: 'number must be in international format without + or spaces (8–15 digits).' });
  }

  if (!ensureConnected(res)) return;

  const optionList = Array.isArray(options)
    ? options.map((o) => String(o).trim()).filter(Boolean)
    : String(options).split(',').map((o) => o.trim()).filter(Boolean);

  if (optionList.length < 2) {
    return res.status(422).json({ error: 'At least two poll options are required.' });
  }
  if (optionList.length > 12) {
    return res.status(422).json({ error: 'Maximum 12 poll options allowed.' });
  }

  try {
    const waPayload = {
      poll: {
        name: String(question),
        values: optionList,
        selectableCount: Math.max(1, parseInt(selectable_count, 10) || 1),
      },
    };

    const result = await deliverMessage(normalizedNumber, waPayload);

    return res.json({ success: true, message_id: result?.key?.id || null });
  } catch (error) {
    logState(`Send poll failed: ${error.message}`);
    return res.status(500).json({ error: 'Failed to send poll.', detail: error.message });
  }
});

app.post('/status/send-text', authMiddleware, async (req, res) => {
  const { text, background_color, font } = req.body || {};

  if (!text || !String(text).trim()) {
    return res.status(422).json({ error: 'text is required.' });
  }

  if (!ensureConnected(res)) return;

  const statusJidList = getStatusJidList();
  if (!statusJidList.length) {
    return res.status(422).json({
      error: 'No WhatsApp contacts available for status. Open WhatsApp inbox and sync contacts first.',
    });
  }

  try {
    const options = {
      statusJidList,
      broadcast: true,
    };
    if (background_color) options.backgroundColor = String(background_color);
    if (font) options.font = parseInt(font, 10) || font;

    const result = await sock.sendMessage('status@broadcast', { text: String(text) }, options);
    const statusId = result?.key?.id || null;

    if (statusId && result?.key) {
      sentStatusMessages[statusId] = result.key;
    }

    return res.json({
      success: true,
      message_id: statusId,
      status_id: statusId,
      recipients: statusJidList.length,
    });
  } catch (error) {
    logState(`Send status failed: ${error.message}`);
    return res.status(500).json({ error: 'Failed to send status.', detail: error.message });
  }
});

app.post('/status/delete', authMiddleware, async (req, res) => {
  const { status_id } = req.body || {};

  if (!status_id) {
    return res.status(422).json({ error: 'status_id is required.' });
  }

  if (!ensureConnected(res)) return;

  try {
    const storedKey = sentStatusMessages[status_id] || {
      remoteJid: 'status@broadcast',
      fromMe: true,
      id: String(status_id),
    };

    await sock.sendMessage('status@broadcast', { delete: storedKey });
    delete sentStatusMessages[status_id];

    return res.json({ success: true, message: 'Status deleted successfully.' });
  } catch (error) {
    logState(`Delete status failed: ${error.message}`);
    return res.status(500).json({ error: 'Failed to delete status.', detail: error.message });
  }
});

app.post('/sync-contacts', authMiddleware, async (req, res) => {
  try {
    const result = await syncAllDeviceContacts();
    return res.json(result);
  } catch (error) {
    return res.status(500).json({ success: false, error: error.message });
  }
});

app.post('/logout', authMiddleware, async (req, res) => {
  try {
    if (sock) {
      try { await sock.logout(); } catch (e) {}
      sock = null;
    }
    clearAuthSession();
    currentQr = null;
    connectionStatus = 'disconnected';
    isStarting = false;
    setTimeout(() => startWhatsApp(), 1000);
    return res.json({ success: true, message: 'Logged out. A fresh QR will be generated.' });
  } catch (error) {
    return res.status(500).json({ error: 'Logout failed.', detail: error.message });
  }
});

// Load persisted LID map and contact names before starting
loadLidMap();
loadContactStore();

const server = app.listen(PORT, () => {
  logState(`Service listening on port ${PORT}`);
  startWhatsApp();
});

// If the port is already taken, another instance is already running. Exit
// immediately WITHOUT connecting to WhatsApp — two clients on the same auth
// session cause the "Connection Replaced" (code 440) disconnect/reconnect loop.
server.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    logState(`Port ${PORT} already in use — another instance is running. Exiting to avoid the 440 connection-replaced loop.`);
    process.exit(1);
  }
  logState(`Server error: ${err.message}`);
  process.exit(1);
});
