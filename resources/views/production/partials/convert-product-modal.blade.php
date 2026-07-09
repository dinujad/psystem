{{-- Convert completed job → product modal (shared) --}}
<div class="cvt-ov" id="cvtModal">
    <div class="cvt-modal">
        <div class="cvt-head">
            <h3>Convert to Product</h3>
            <p id="cvtJobLabel">—</p>
        </div>
        <div class="cvt-body" id="cvtBody">
            <div class="cvt-tabs">
                <button type="button" class="cvt-tab active" data-mode="existing">Existing Product</button>
                <button type="button" class="cvt-tab" data-mode="new">New Product</button>
            </div>
            <div class="cvt-field">
                <span class="cvt-label">Location *</span>
                <select id="cvtLocation"></select>
            </div>
            <div class="cvt-field">
                <span class="cvt-label">Quantity to add *</span>
                <input type="number" id="cvtQty" min="0.01" step="0.01" value="1">
            </div>
            <div class="cvt-panel active" id="cvtPanelExisting">
                <div class="cvt-field">
                    <span class="cvt-label">Search product</span>
                    <input type="text" id="cvtSearch" placeholder="Type product name or SKU…" autocomplete="off">
                    <div class="cvt-search-results" id="cvtSearchResults"></div>
                    <div class="cvt-selected" id="cvtSelected"></div>
                    <input type="hidden" id="cvtProductId">
                    <input type="hidden" id="cvtVariationId">
                </div>
            </div>
            <div class="cvt-panel" id="cvtPanelNew">
                <div class="cvt-field">
                    <span class="cvt-label">Product name *</span>
                    <input type="text" id="cvtName" placeholder="Product name">
                </div>
                <div class="cvt-field">
                    <span class="cvt-label">Unit *</span>
                    <select id="cvtUnit"></select>
                </div>
                <div class="cvt-grid">
                    <div class="cvt-field">
                        <span class="cvt-label">Purchase price *</span>
                        <input type="number" id="cvtPurchase" min="0" step="0.01" placeholder="0.00">
                        <div class="cvt-hint" id="cvtMaterialHint"></div>
                    </div>
                    <div class="cvt-field">
                        <span class="cvt-label">Selling price *</span>
                        <input type="number" id="cvtSelling" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="cvt-field">
                    <span class="cvt-label">Profit % (optional)</span>
                    <input type="number" id="cvtProfit" min="0" step="0.01" placeholder="Auto-calculated">
                </div>
            </div>
        </div>
        <div class="cvt-foot">
            <button type="button" class="cvt-btn cancel" id="cvtCancel">Cancel</button>
            <button type="button" class="cvt-btn save" id="cvtSave">Save Product</button>
        </div>
    </div>
</div>

{{-- Success popup after convert --}}
<div class="cvt-ov" id="cvtSuccessModal">
    <div class="cvt-modal cvt-success-modal">
        <div class="cvt-success-body">
            <div class="cvt-success-icon" aria-hidden="true">
                <svg viewBox="0 0 52 52" width="52" height="52">
                    <circle class="cvt-success-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="cvt-success-check" fill="none" d="M14 27l8 8 16-16"/>
                </svg>
            </div>
            <h3 class="cvt-success-title">Success!</h3>
            <p class="cvt-success-msg" id="cvtSuccessMsg">—</p>
        </div>
        <div class="cvt-foot">
            <button type="button" class="cvt-btn save" id="cvtSuccessClose" autofocus>Done</button>
        </div>
    </div>
</div>
