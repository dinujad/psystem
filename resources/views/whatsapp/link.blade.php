@extends('layouts.app')
@section('title', 'WhatsApp')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        WhatsApp
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">Link PrintWorks and Safety Sign phones separately</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        @foreach ([
            'printworks' => 'PrintWorks',
            'safetysign' => 'Safety Sign',
        ] as $accountId => $accountLabel)
            <div class="col-md-6">
                <div class="box box-primary" data-wa-account="{{ $accountId }}">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $accountLabel }} WhatsApp</h3>
                        <div class="box-tools pull-right">
                            <span class="label label-default wa-status-badge">Checking...</span>
                        </div>
                    </div>
                    <div class="box-body text-center">
                        <p class="text-muted" style="margin-bottom:12px;">
                            Quotation / invoice from <strong>{{ $accountLabel }}</strong> goes out from this phone.
                        </p>

                        <div class="wa-connected-panel" style="display:none;">
                            <p class="text-success" style="font-size: 18px; margin: 20px 0;">
                                ✅ {{ $accountLabel }} Linked
                            </p>
                            <p class="text-muted wa-linked-phone"></p>
                            <button type="button" class="btn btn-warning wa-logout-btn" style="margin-top: 15px;">
                                Unlink &amp; Scan New QR
                            </button>
                        </div>

                        <div class="wa-qr-panel">
                            <p class="text-muted">Open WhatsApp on the <strong>{{ $accountLabel }}</strong> phone → <strong>Linked Devices</strong> → <strong>Link a Device</strong>, then scan this QR.</p>
                            <div class="wa-qr-container" style="min-height: 300px; display:flex; align-items:center; justify-content:center;">
                                <p class="text-muted wa-qr-loading">Loading QR code...</p>
                                <img class="wa-qr-image" src="" alt="{{ $accountLabel }} WhatsApp QR" style="display:none; max-width:280px; border:1px solid #ddd; border-radius:8px; padding:8px;">
                            </div>
                            <p class="text-danger wa-qr-error" style="display:none; margin-top:15px;"></p>
                        </div>
                    </div>
                </div>

                <div class="box box-default wa-send-box" style="display:none;">
                    <div class="box-header with-border">
                        <h3 class="box-title">Send test ({{ $accountLabel }})</h3>
                    </div>
                    <div class="box-body">
                        <form class="wa-send-form">
                            @csrf
                            <div class="form-group">
                                <label>Phone Number (international, no +)</label>
                                <input type="text" class="form-control wa-number" placeholder="94771234567" required>
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea class="form-control wa-message" rows="2" placeholder="Hello from {{ $accountLabel }}" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success wa-send-btn">Send Message</button>
                            <p class="wa-send-result" style="margin-top:10px; display:none;"></p>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
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

    function setBadge(box, status) {
        const badge = box.querySelector('.wa-status-badge');
        const map = {
            connected: ['label-success', 'Connected'],
            waiting_for_scan: ['label-warning', 'Waiting for Scan'],
            waiting: ['label-warning', 'Generating QR...'],
            disconnected: ['label-danger', 'Disconnected'],
        };
        const [cls, text] = map[status] || ['label-default', status || 'Unknown'];
        badge.className = 'label wa-status-badge ' + cls;
        badge.textContent = text;
    }

    function showConnected(box, phone) {
        box.querySelector('.wa-connected-panel').style.display = 'block';
        box.querySelector('.wa-qr-panel').style.display = 'none';
        const phoneEl = box.querySelector('.wa-linked-phone');
        phoneEl.textContent = phone ? ('Linked number: +' + phone) : 'Your WhatsApp account is connected.';
        const sendBox = box.parentElement.querySelector('.wa-send-box');
        if (sendBox) sendBox.style.display = 'block';
        setBadge(box, 'connected');
    }

    function showQrWaiting(box) {
        box.querySelector('.wa-connected-panel').style.display = 'none';
        box.querySelector('.wa-qr-panel').style.display = 'block';
        const sendBox = box.parentElement.querySelector('.wa-send-box');
        if (sendBox) sendBox.style.display = 'none';
    }

    async function fetchStatus(account) {
        const res = await fetch(statusUrl + '?account=' + encodeURIComponent(account), {
            headers: { 'Accept': 'application/json' }
        });
        return res.json();
    }

    async function fetchQr(account) {
        const res = await fetch(qrUrl + '?account=' + encodeURIComponent(account), {
            headers: { 'Accept': 'application/json' }
        });
        return res.json();
    }

    async function pollAccount(box) {
        const account = box.getAttribute('data-wa-account');
        const qrLoading = box.querySelector('.wa-qr-loading');
        const qrImage = box.querySelector('.wa-qr-image');
        const qrError = box.querySelector('.wa-qr-error');

        try {
            const statusData = await fetchStatus(account);
            const status = statusData.status || 'disconnected';
            setBadge(box, status);

            if (status === 'connected') {
                showConnected(box, statusData.phone || null);
                return;
            }

            showQrWaiting(box);
            qrError.style.display = 'none';

            const data = await fetchQr(account);
            if (data.status === 'connected') {
                showConnected(box, data.phone || null);
                return;
            }

            if (data.status === 'waiting') {
                qrLoading.style.display = 'block';
                qrLoading.textContent = 'Generating QR code...';
                qrImage.style.display = 'none';
                setBadge(box, 'waiting');
                return;
            }

            if (data.qr) {
                qrLoading.style.display = 'none';
                qrImage.src = 'data:image/png;base64,' + data.qr;
                qrImage.style.display = 'inline-block';
                setBadge(box, 'waiting_for_scan');
                return;
            }

            qrLoading.style.display = 'block';
            qrLoading.textContent = 'Waiting for QR code...';
        } catch (e) {
            qrError.textContent = 'Could not reach WhatsApp service. Restart the Node WhatsApp service after deploy.';
            qrError.style.display = 'block';
            setBadge(box, 'disconnected');
            showQrWaiting(box);
        }
    }

    document.querySelectorAll('[data-wa-account]').forEach(function (box) {
        const account = box.getAttribute('data-wa-account');

        box.querySelector('.wa-logout-btn').addEventListener('click', async function () {
            if (!confirm('Unlink this WhatsApp account and show a new QR?')) return;
            const btn = this;
            btn.disabled = true;
            try {
                await fetch(logoutUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ account: account }),
                });
            } catch (e) {}
            btn.disabled = false;
            showQrWaiting(box);
            pollAccount(box);
        });

        const sendForm = box.parentElement.querySelector('.wa-send-form');
        if (sendForm) {
            sendForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const resultEl = box.parentElement.querySelector('.wa-send-result');
                const btn = box.parentElement.querySelector('.wa-send-btn');
                resultEl.style.display = 'none';
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
                            account: account,
                            number: box.parentElement.querySelector('.wa-number').value.trim(),
                            message: box.parentElement.querySelector('.wa-message').value.trim(),
                        }),
                    });
                    const data = await res.json();
                    resultEl.style.display = 'block';
                    resultEl.className = data.success ? 'text-success wa-send-result' : 'text-danger wa-send-result';
                    resultEl.textContent = data.success ? 'Message sent successfully.' : (data.message || data.error || 'Failed to send message.');
                } catch (err) {
                    resultEl.style.display = 'block';
                    resultEl.className = 'text-danger wa-send-result';
                    resultEl.textContent = 'Failed to send message.';
                } finally {
                    btn.disabled = false;
                }
            });
        }

        pollAccount(box);
        setInterval(function () { pollAccount(box); }, 3000);
    });
})();
</script>
@endsection
