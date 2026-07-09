<script>
(function () {
    if (window.__cvtInitV3) return;
    window.__cvtInitV3 = true;

    const CVT_CSRF = '{{ csrf_token() }}';
    const CVT_BASE = '{{ url('production') }}';
    const CVT_SEARCH = '{{ route('production.products.search') }}';
    let cvtJobId = null;
    let cvtMode = 'existing';
    let cvtSearchTimer = null;

    const cvtModal = document.getElementById('cvtModal');
    const cvtSuccessModal = document.getElementById('cvtSuccessModal');
    if (cvtModal && cvtModal.parentElement !== document.body) document.body.appendChild(cvtModal);
    if (cvtSuccessModal && cvtSuccessModal.parentElement !== document.body) document.body.appendChild(cvtSuccessModal);

    function showCvtSuccess(message, onDone) {
        const msgEl = document.getElementById('cvtSuccessMsg');
        const closeBtn = document.getElementById('cvtSuccessClose');
        if (!cvtSuccessModal || !msgEl) return;

        msgEl.textContent = message || 'Operation completed successfully.';
        if (closeBtn) {
            closeBtn.onclick = () => {
                cvtSuccessModal.classList.remove('show');
                if (typeof onDone === 'function') onDone();
            };
        }
        cvtSuccessModal.onclick = (e) => {
            if (e.target === cvtSuccessModal) {
                cvtSuccessModal.classList.remove('show');
                if (typeof onDone === 'function') onDone();
            }
        };
        cvtSuccessModal.onkeydown = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                closeBtn?.click();
            }
        };
        cvtSuccessModal.classList.add('show');
        setTimeout(() => closeBtn?.focus(), 50);
    }

    function showCvtError(message) {
        if (typeof swal === 'function') {
            swal({ title: 'Error', text: message || 'Something went wrong.', icon: 'error' });
        } else {
            alert(message || 'Something went wrong.');
        }
    }

    window.openConvertModal = function (jobId) {
        cvtJobId = jobId;
        fetch(`${CVT_BASE}/${jobId}/convert-product`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CVT_CSRF }
        })
        .then(r => r.json())
        .then(d => {
            if (!d.success) { showCvtError(d.message || 'Failed.'); return; }
            if (d.converted) {
                showCvtSuccess('This job was already converted to "' + (d.product_name || 'product') + '".');
                return;
            }
            document.getElementById('cvtJobLabel').textContent = d.job.job_number + ' — ' + d.job.title;
            document.getElementById('cvtLocation').innerHTML = d.locations.map(l => `<option value="${l.id}">${l.name}</option>`).join('');
            document.getElementById('cvtUnit').innerHTML = d.units.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
            document.getElementById('cvtQty').value = d.default_qty || 1;
            document.getElementById('cvtName').value = d.default_name || '';
            const purchase = parseFloat(d.suggested_purchase_price) || 0;
            const selling = parseFloat(d.suggested_selling_price) || 0;
            document.getElementById('cvtPurchase').value = purchase > 0 ? purchase.toFixed(2) : '';
            document.getElementById('cvtSelling').value = selling > 0 ? selling.toFixed(2) : (purchase > 0 ? purchase.toFixed(2) : '');
            document.getElementById('cvtProfit').value = '';
            const hint = document.getElementById('cvtMaterialHint');
            if (hint) {
                if (d.material_count > 0 && purchase > 0) {
                    hint.innerHTML = 'Auto-filled from <strong>' + d.material_count + ' raw material(s)</strong> used in this job — total Rs ' + purchase.toFixed(2);
                } else if (d.material_count > 0) {
                    hint.textContent = d.material_count + ' raw material(s) recorded for this job (no unit prices set).';
                } else {
                    hint.textContent = 'No raw materials recorded for this job — enter purchase price manually.';
                }
            }
            document.getElementById('cvtSearch').value = '';
            document.getElementById('cvtProductId').value = '';
            document.getElementById('cvtVariationId').value = '';
            document.getElementById('cvtSelected').style.display = 'none';
            document.getElementById('cvtSearchResults').style.display = 'none';
            setCvtMode('existing');
            cvtModal.classList.add('show');
        })
        .catch(() => showCvtError('Could not load conversion form.'));
    };

    function setCvtMode(mode) {
        cvtMode = mode;
        document.querySelectorAll('.cvt-tab').forEach(t => t.classList.toggle('active', t.dataset.mode === mode));
        document.getElementById('cvtPanelExisting').classList.toggle('active', mode === 'existing');
        document.getElementById('cvtPanelNew').classList.toggle('active', mode === 'new');
    }

    document.querySelectorAll('.cvt-tab').forEach(tab => {
        tab.addEventListener('click', () => setCvtMode(tab.dataset.mode));
    });

    document.getElementById('cvtCancel')?.addEventListener('click', () => cvtModal.classList.remove('show'));
    cvtModal?.addEventListener('click', e => { if (e.target === cvtModal) cvtModal.classList.remove('show'); });

    document.getElementById('cvtSearch')?.addEventListener('input', function () {
        clearTimeout(cvtSearchTimer);
        const q = this.value.trim();
        if (q.length < 1) {
            document.getElementById('cvtSearchResults').style.display = 'none';
            return;
        }
        cvtSearchTimer = setTimeout(() => {
            const loc = document.getElementById('cvtLocation').value;
            fetch(`${CVT_SEARCH}?q=${encodeURIComponent(q)}&location_id=${loc}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                const box = document.getElementById('cvtSearchResults');
                if (!d.products || !d.products.length) {
                    box.innerHTML = '<div style="padding:12px;color:#9ca3af;font-size:12px;">No products found.</div>';
                } else {
                    box.innerHTML = d.products.map(p => `
                        <div class="cvt-search-item" data-pid="${p.product_id}" data-vid="${p.variation_id}" data-name="${p.name}">
                            <strong>${p.name}</strong>
                            <span style="color:#9ca3af;font-size:11px;"> · SKU: ${p.sku || '—'} · Stock: ${parseFloat(p.qty_available).toFixed(2)}</span>
                        </div>`).join('');
                    box.querySelectorAll('.cvt-search-item').forEach(item => {
                        item.addEventListener('click', () => {
                            document.getElementById('cvtProductId').value = item.dataset.pid;
                            document.getElementById('cvtVariationId').value = item.dataset.vid;
                            const sel = document.getElementById('cvtSelected');
                            sel.textContent = '✓ Selected: ' + item.dataset.name;
                            sel.style.display = 'block';
                            box.style.display = 'none';
                        });
                    });
                }
                box.style.display = 'block';
            });
        }, 300);
    });

    document.getElementById('cvtSave')?.addEventListener('click', function (e) {
        e.preventDefault();
        const btn = this;
        const payload = {
            mode: cvtMode,
            location_id: parseInt(document.getElementById('cvtLocation').value, 10),
            quantity: parseFloat(document.getElementById('cvtQty').value),
        };
        if (cvtMode === 'existing') {
            payload.product_id = parseInt(document.getElementById('cvtProductId').value, 10);
            payload.variation_id = parseInt(document.getElementById('cvtVariationId').value, 10);
            if (!payload.product_id) { alert('Select an existing product.'); return; }
        } else {
            payload.name = document.getElementById('cvtName').value.trim();
            payload.unit_id = parseInt(document.getElementById('cvtUnit').value, 10);
            payload.purchase_price = parseFloat(document.getElementById('cvtPurchase').value);
            payload.selling_price = parseFloat(document.getElementById('cvtSelling').value);
            const profit = document.getElementById('cvtProfit').value;
            if (profit) payload.profit_percent = parseFloat(profit);
            if (!payload.name) { alert('Product name is required.'); return; }
            if (isNaN(payload.purchase_price)) { alert('Purchase price is required.'); return; }
            if (isNaN(payload.selling_price)) { alert('Selling price is required.'); return; }
        }
        if (!payload.quantity || payload.quantity <= 0) { alert('Enter a valid quantity.'); return; }

        btn.disabled = true;
        fetch(`${CVT_BASE}/${cvtJobId}/convert-product`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CVT_CSRF },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            btn.disabled = false;
            if (!d.success) { showCvtError(d.message || 'Failed.'); return; }
            cvtModal.classList.remove('show');
            showCvtSuccess(d.message, () => location.reload());
        })
        .catch(() => { btn.disabled = false; showCvtError('Request failed.'); });
    });
})();
</script>
