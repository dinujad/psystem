{{-- Description column format on PDF: product+note vs note only --}}
@php
	$currentDescFormat = old(
		'description_format',
		(! empty($transaction) ? ($transaction->description_format ?? 'product_and_note') : 'product_and_note')
	);
	if (! in_array($currentDescFormat, ['product_and_note', 'note_only'], true)) {
		$currentDescFormat = 'product_and_note';
	}
	$showDescFormatPicker = (! empty($status) && in_array($status, ['quotation', 'proforma'], true))
		|| ! empty($invoice_mode)
		|| (! empty($transaction) && (
			(int) ($transaction->is_quotation ?? 0) === 1
			|| ($transaction->sub_status ?? '') === 'proforma'
			|| ($transaction->status ?? '') === 'final'
		));
@endphp
@if($showDescFormatPicker)
<style>
.desc-format-bar {
	display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
	margin: 0 0 16px; padding: 12px 14px;
	background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px;
}
.desc-format-label {
	font-size: 12px; font-weight: 800; color: #111827;
	text-transform: uppercase; letter-spacing: .04em; margin-right: 4px;
}
.desc-format-select {
	min-width: 280px; max-width: 100%;
	height: 36px; border: 1.5px solid #e5e7eb; border-radius: 8px;
	padding: 6px 10px; font-size: 13px; font-weight: 600; color: #374151;
	background: #fff;
}
.desc-format-hint { font-size: 11px; color: #6b7280; margin-left: auto; }
</style>
<div class="col-sm-12">
	<div class="desc-format-bar" id="descFormatBar">
		<span class="desc-format-label">Description format</span>
		<select name="description_format" id="description_format" class="desc-format-select">
			<option value="product_and_note" {{ $currentDescFormat === 'product_and_note' ? 'selected' : '' }}>
				Product name + description
			</option>
			<option value="note_only" {{ $currentDescFormat === 'note_only' ? 'selected' : '' }}>
				Description only (no product name)
			</option>
		</select>
		<span class="desc-format-hint">PDF Description column — quotation → proforma → invoice දක්වාම යනවා.</span>
	</div>
</div>
@endif
