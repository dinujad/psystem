{{-- Terms & Notes for Proforma / Invoice PDFs (Printworks + Safety Sign) --}}
@php
	$defaultQuotationTerms = $defaultQuotationTerms ?? config('constants.default_quotation_terms');
	$existingTerms = (! empty($transaction) && ! empty($transaction->quotation_terms))
		? $transaction->quotation_terms
		: $defaultQuotationTerms;
	$termsValue = old('quotation_terms', $existingTerms);
	$existingNotes = (! empty($transaction)) ? ($transaction->additional_notes ?? null) : null;
	$notesValue = old('sale_note', $saleNoteValue ?? $existingNotes);
@endphp
@component('components.widget', ['class' => 'box-solid', 'title' => 'Terms & Notes (shown on PDF)'])
	<div class="row">
		<div class="col-md-12">
			<div class="form-group">
				{!! Form::label('quotation_terms', 'Terms & Conditions') !!}
				<p class="help-block" style="margin-top:0;">Quotation / Proforma / Invoice PDF එකේ Terms එකට යනවා.</p>
				{!! Form::textarea('quotation_terms', $termsValue, [
					'class' => 'form-control',
					'rows' => 5,
					'placeholder' => $defaultQuotationTerms,
				]) !!}
			</div>
		</div>
		<div class="col-md-12">
			<div class="form-group">
				{!! Form::label('sale_note', 'Additional Notes') !!}
				{!! Form::textarea('sale_note', $notesValue, [
					'class' => 'form-control',
					'rows' => 3,
					'placeholder' => 'Optional notes on PDF',
				]) !!}
			</div>
		</div>
	</div>
@endcomponent
