{{-- Shared: Sales Channel + Document format filters --}}
@php
	$sources = $sources ?? ['Web' => 'Web', 'Direct' => 'Direct'];
	$document_brands = $document_brands ?? [
		'printworks' => 'Printworks',
		'safetysign' => 'Safety Sign',
	];
@endphp
<div class="col-md-3">
	<div class="form-group">
		{!! Form::label('sell_list_filter_source', 'Sales Channel:') !!}
		{!! Form::select('sell_list_filter_source', $sources, null, [
			'class' => 'form-control select2',
			'style' => 'width:100%',
			'placeholder' => __('lang_v1.all'),
		]) !!}
	</div>
</div>
<div class="col-md-3">
	<div class="form-group">
		{!! Form::label('sell_list_filter_document_brand', 'Document format:') !!}
		{!! Form::select('sell_list_filter_document_brand', $document_brands, null, [
			'class' => 'form-control select2',
			'style' => 'width:100%',
			'placeholder' => __('lang_v1.all'),
		]) !!}
	</div>
</div>
