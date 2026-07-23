{{-- Quotation multi-option toolbar (OPTION 01, OPTION 02, +) --}}
@php
	$isQuotationForm = (!empty($status) && $status === 'quotation')
		|| (!empty($transaction) && (int) ($transaction->is_quotation ?? 0) === 1);
	$initialOptionCount = 1;
	if (!empty($sell_details)) {
		$initialOptionCount = max(1, (int) collect($sell_details)->max(fn ($l) => (int) ($l->option_group ?? 1)));
	}
@endphp
@if($isQuotationForm)
<style>
.quote-opt-bar {
	display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
	margin: 0 0 14px; padding: 12px 14px;
	background: #fff5f5; border: 1px solid #fecaca; border-radius: 10px;
}
.quote-opt-label { font-size: 12px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: .04em; margin-right: 4px; }
.quote-opt-tab {
	border: 1.5px solid #e5e7eb; background: #fff; color: #374151;
	border-radius: 8px; padding: 7px 14px; font-size: 12px; font-weight: 800;
	cursor: pointer;
}
.quote-opt-tab.active {
	background: #E31E24; border-color: #E31E24; color: #fff;
}
.quote-opt-add {
	border: 1.5px dashed #E31E24; background: #fff; color: #E31E24;
	border-radius: 8px; padding: 7px 14px; font-size: 12px; font-weight: 800;
	cursor: pointer;
}
.quote-opt-add:hover { background: #fff1f2; }
.quote-opt-hint { font-size: 11px; color: #6b7280; margin-left: auto; }
.quote-opt-subtotal {
	font-size: 12px; font-weight: 700; color: #111827;
	background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
	padding: 6px 12px;
}
</style>
<div class="quote-opt-bar" id="quoteOptBar">
	<span class="quote-opt-label">Options</span>
	<div id="quoteOptTabs" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
	<button type="button" class="quote-opt-add" id="quoteOptAddBtn">+ Add Option</button>
	<span class="quote-opt-subtotal">This option total: <span id="quoteOptSubtotal">0</span></span>
	<span class="quote-opt-hint">Products you add go into the selected option. Each option has its own Grand Total on the PDF.</span>
</div>
<input type="hidden" id="quote_active_option" value="1">
<input type="hidden" id="quote_option_count" value="{{ $initialOptionCount }}">
@endif
