{{-- Document brand: Printworks (red) vs Safety Sign (black) --}}
@php
	$currentBrand = old(
		'document_brand',
		(! empty($transaction) ? ($transaction->document_brand ?? 'printworks') : 'printworks')
	);
	if (! in_array($currentBrand, ['printworks', 'safetysign'], true)) {
		$currentBrand = 'printworks';
	}
	$showBrandPicker = (! empty($status) && in_array($status, ['quotation', 'proforma'], true))
		|| ! empty($invoice_mode)
		|| (! empty($transaction) && (
			(int) ($transaction->is_quotation ?? 0) === 1
			|| ($transaction->sub_status ?? '') === 'proforma'
			|| ($transaction->status ?? '') === 'final'
		));
@endphp
@if($showBrandPicker)
<style>
.doc-brand-bar {
	display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
	margin: 0 0 16px; padding: 12px 14px;
	background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px;
}
.doc-brand-label {
	font-size: 12px; font-weight: 800; color: #111827;
	text-transform: uppercase; letter-spacing: .04em; margin-right: 4px;
}
.doc-brand-opt {
	display: inline-flex; align-items: center; gap: 8px;
	border: 1.5px solid #e5e7eb; background: #fff; color: #374151;
	border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 700;
	cursor: pointer; margin: 0;
}
.doc-brand-opt input { margin: 0; }
.doc-brand-opt.active-pw {
	border-color: #E31E24; background: #fff5f5; color: #991b1b;
}
.doc-brand-opt.active-ss {
	border-color: #111827; background: #f3f4f6; color: #111827;
}
.doc-brand-hint { font-size: 11px; color: #6b7280; margin-left: auto; }
</style>
<div class="col-sm-12">
	<div class="doc-brand-bar" id="docBrandBar">
		<span class="doc-brand-label">Quotation / Document for</span>
		<label class="doc-brand-opt {{ $currentBrand === 'printworks' ? 'active-pw' : '' }}">
			<input type="radio" name="document_brand" value="printworks" {{ $currentBrand === 'printworks' ? 'checked' : '' }}>
			Printworks
		</label>
		<label class="doc-brand-opt {{ $currentBrand === 'safetysign' ? 'active-ss' : '' }}">
			<input type="radio" name="document_brand" value="safetysign" {{ $currentBrand === 'safetysign' ? 'checked' : '' }}>
			Safety Sign
		</label>
		<span class="doc-brand-hint">PDF theme එක quotation → proforma → invoice දක්වාම යනවා.</span>
	</div>
</div>
<script>
(function () {
	function syncBrandUi() {
		$('#docBrandBar .doc-brand-opt').each(function () {
			var $l = $(this);
			var v = $l.find('input').val();
			$l.removeClass('active-pw active-ss');
			if ($l.find('input').is(':checked')) {
				$l.addClass(v === 'safetysign' ? 'active-ss' : 'active-pw');
			}
		});
	}
	$(document).on('change', '#docBrandBar input[name="document_brand"]', syncBrandUi);
})();
</script>
@endif
