@extends('layouts.app')
@section('title', 'WhatsApp')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        WhatsApp
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">Link your WhatsApp account via QR code</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">WhatsApp Connection</h3>
                    <div class="box-tools pull-right">
                        <span id="wa-status-badge" class="label label-default">Checking...</span>
                    </div>
                </div>
                <div class="box-body text-center">
                    <div id="wa-connected-panel" style="display:none;">
                        <p class="text-success" style="font-size: 18px; margin: 30px 0;">
                            ✅ WhatsApp Linked
                        </p>
                        <p class="text-muted">Your WhatsApp account is connected and ready to send/receive messages.</p>
                        <button type="button" class="btn btn-warning" id="wa-logout-btn" style="margin-top: 15px;">
                            Unlink &amp; Scan New QR
                        </button>
                    </div>

                    <div id="wa-qr-panel">
                        <p class="text-muted">Open WhatsApp on your phone → <strong>Linked Devices</strong> → <strong>Link a Device</strong>, then scan this QR code.</p>
                        <div id="wa-qr-container" style="min-height: 340px; display:flex; align-items:center; justify-content:center;">
                            <p id="wa-qr-loading" class="text-muted">Loading QR code...</p>
                            <img id="wa-qr-image" src="" alt="WhatsApp QR Code" style="display:none; max-width:320px; border:1px solid #ddd; border-radius:8px; padding:8px;">
                        </div>
                        <p id="wa-qr-error" class="text-danger" style="display:none; margin-top:15px;"></p>
                    </div>
                </div>
            </div>

            <div class="box box-default" id="wa-send-box" style="display:none;">
                <div class="box-header with-border">
                    <h3 class="box-title">Send Test Message</h3>
                </div>
                <div class="box-body">
                    <form id="wa-send-form">
                        @csrf
                        <div class="form-group">
                            <label for="wa-number">Phone Number (international, no +)</label>
                            <input type="text" class="form-control" id="wa-number" name="number" placeholder="94771234567" required>
                        </div>
                        <div class="form-group">
                            <label for="wa-message">Message</label>
                            <textarea class="form-control" id="wa-message" name="message" rows="3" placeholder="Hello from PrintWorks" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success" id="wa-send-btn">Send Message</button>
                        <p id="wa-send-result" style="margin-top:10px; display:none;"></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const qrUrl = "{{ url('/whatsapp/qr') }}";
    const statusUrl = "{{ url('/whatsapp/status') }}";
    const sendUrl = "{{ url('/whatsapp/send') }}";
    const logoutUrl = "{{ url('/whatsapp/logout') }}";

    const statusBadge = document.getElementById('wa-status-badge');
    const connectedPanel = document.getElementById('wa-connected-panel');
    const qrPanel = document.getElementById('wa-qr-panel');
    const qrImage = document.getElementById('wa-qr-image');
    const qrLoading = document.getElementById('wa-qr-loading');
    const qrError = document.getElementById('wa-qr-error');
    const sendBox = document.getElementById('wa-send-box');
    const sendForm = document.getElementById('wa-send-form');
    const sendResult = document.getElementById('wa-send-result');
    const logoutBtn = document.getElementById('wa-logout-btn');

    let pollTimer = null;

    function setBadge(status) {
        const map = {
            connected: ['label-success', 'Connected'],
            waiting_for_scan: ['label-warning', 'Waiting for Scan'],
            waiting: ['label-warning', 'Generating QR...'],
            disconnected: ['label-danger', 'Disconnected'],
        };

        const [cls, text] = map[status] || ['label-default', status || 'Unknown'];
        statusBadge.className = 'label ' + cls;
        statusBadge.textContent = text;
    }

    function showConnected() {
        connectedPanel.style.display = 'block';
        qrPanel.style.display = 'none';
        sendBox.style.display = 'block';
        setBadge('connected');
    }

    function showQrWaiting() {
        connectedPanel.style.display = 'none';
        qrPanel.style.display = 'block';
        sendBox.style.display = 'none';
    }

    async function fetchStatus() {
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            setBadge(data.status || 'disconnected');
            return data.status;
        } catch (e) {
            setBadge('disconnected');
            return 'disconnected';
        }
    }

    async function fetchQr() {
        qrError.style.display = 'none';

        try {
            const res = await fetch(qrUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (data.message && !data.status) {
                qrLoading.textContent = data.message;
                qrImage.style.display = 'none';
                setBadge('disconnected');
                return;
            }

            if (data.status === 'connected') {
                showConnected();
                return;
            }

            showQrWaiting();

            if (data.status === 'waiting') {
                qrLoading.style.display = 'block';
                qrLoading.textContent = 'Generating QR code...';
                qrImage.style.display = 'none';
                setBadge('waiting');
                return;
            }

            if (data.qr) {
                qrLoading.style.display = 'none';
                qrImage.src = 'data:image/png;base64,' + data.qr;
                qrImage.style.display = 'inline-block';
                setBadge('waiting_for_scan');
                return;
            }

            qrLoading.textContent = 'Waiting for QR code...';
        } catch (e) {
            qrError.textContent = 'Could not reach WhatsApp service. Is the Node service running?';
            qrError.style.display = 'block';
            setBadge('disconnected');
        }
    }

    async function poll() {
        const status = await fetchStatus();

        if (status === 'connected') {
            showConnected();
            return;
        }

        // Was connected but dropped — show QR panel again.
        showQrWaiting();
        await fetchQr();
    }

    function startPolling() {
        poll();
        pollTimer = setInterval(poll, 3000);
    }

    sendForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        sendResult.style.display = 'none';

        const btn = document.getElementById('wa-send-btn');
        btn.disabled = true;

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    number: document.getElementById('wa-number').value.trim(),
                    message: document.getElementById('wa-message').value.trim(),
                }),
            });

            const data = await res.json();
            sendResult.style.display = 'block';
            sendResult.className = data.success ? 'text-success' : 'text-danger';
            sendResult.textContent = data.success ? 'Message sent successfully.' : (data.message || 'Failed to send message.');
        } catch (err) {
            sendResult.style.display = 'block';
            sendResult.className = 'text-danger';
            sendResult.textContent = 'Failed to send message.';
        } finally {
            btn.disabled = false;
        }
    });

    logoutBtn.addEventListener('click', async function () {
        if (!confirm('Unlink WhatsApp and generate a new QR code?')) {
            return;
        }

        logoutBtn.disabled = true;

        try {
            await fetch(logoutUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
        } catch (e) {
            // ignore — polling will recover
        }

        showQrWaiting();
        logoutBtn.disabled = false;
        startPolling();
    });

    startPolling();
})();
</script>
@endsection
