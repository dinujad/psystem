{{-- Sales Channel: Web | Direct (saved as transactions.source) --}}
@php
	$salesChannelOptions = [
		'Web' => 'Web',
		'Direct' => 'Direct',
	];
	$currentSalesChannel = old(
		'source',
		! empty($transaction) ? ($transaction->source ?: 'Web') : 'Web'
	);
	if (! array_key_exists($currentSalesChannel, $salesChannelOptions)) {
		// Keep legacy custom values selectable so edit doesn't wipe them
		$salesChannelOptions[$currentSalesChannel] = $currentSalesChannel;
	}
	$showSalesChannel = (! empty($status) && in_array($status, ['quotation', 'proforma'], true))
		|| ! empty($invoice_mode)
		|| (! empty($transaction) && (
			(int) ($transaction->is_quotation ?? 0) === 1
			|| ($transaction->sub_status ?? '') === 'proforma'
			|| ($transaction->status ?? '') === 'final'
		));
@endphp
@if($showSalesChannel)
<style>
.sales-channel-bar {
	display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
	margin: 0 0 16px; padding: 12px 14px;
	background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px;
}
.sales-channel-label {
	font-size: 12px; font-weight: 800; color: #111827;
	text-transform: uppercase; letter-spacing: .04em; margin-right: 4px;
}
.sales-channel-select {
	min-width: 180px; max-width: 100%;
	height: 36px; border: 1.5px solid #e5e7eb; border-radius: 8px;
	padding: 6px 10px; font-size: 13px; font-weight: 600; color: #374151;
	background: #fff;
}
.sales-channel-hint { font-size: 11px; color: #6b7280; margin-left: auto; }
</style>
<div class="col-sm-12">
	<div class="sales-channel-bar" id="salesChannelBar">
		<span class="sales-channel-label">Sales Channel *</span>
		<select name="source" id="source" class="sales-channel-select" required>
			@foreach($salesChannelOptions as $value => $label)
				<option value="{{ $value }}" {{ (string) $currentSalesChannel === (string) $value ? 'selected' : '' }}>
					{{ $label }}
				</option>
			@endforeach
		</select>
		<span class="sales-channel-hint">PDF එකේ Sales Channel — Web / Direct</span>
	</div>
</div>
@endif
