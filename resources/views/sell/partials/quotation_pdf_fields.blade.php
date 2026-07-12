@php
    use App\Utils\QuotationAdditionalTermsUtil;

    $additionalTermsValues = QuotationAdditionalTermsUtil::mergeWithSaved($savedAdditionalTermsJson ?? null);
    $additionalSections = QuotationAdditionalTermsUtil::sectionDefinitions();
    $defaultQuotationTerms = $defaultQuotationTerms ?? config('constants.default_quotation_terms');
@endphp

<style>
    .pw-quote-tabs { border-bottom: 2px solid #E31E24; margin-bottom: 16px; }
    .pw-quote-tabs > li > a {
        color: #555;
        font-weight: 600;
        border-radius: 4px 4px 0 0;
    }
    .pw-quote-tabs > li.active > a,
    .pw-quote-tabs > li.active > a:hover,
    .pw-quote-tabs > li.active > a:focus {
        color: #E31E24 !important;
        border-color: #E31E24 #E31E24 transparent !important;
        border-bottom-color: #fff !important;
        font-weight: 700;
    }
    .pw-additional-section {
        border: 1px solid #E8E8E8;
        border-left: 4px solid #E31E24;
        border-radius: 4px;
        padding: 12px 14px;
        margin-bottom: 14px;
        background: #fafafa;
    }
    .pw-additional-section-title {
        color: #E31E24;
        font-weight: 800;
        font-size: 13px;
        letter-spacing: 0.3px;
        margin: 0 0 8px 0;
        text-transform: uppercase;
    }
    .pw-additional-section .help-block {
        margin-top: 6px;
        color: #888;
        font-size: 12px;
    }
    .pw-additional-section textarea {
        background: #fff;
        border-color: #ddd;
    }
    .pw-additional-section textarea:focus {
        border-color: #E31E24;
        box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 6px rgba(227, 30, 36, 0.25);
    }
    .pw-custom-badge {
        display: inline-block;
        background: #E8E8E8;
        color: #666;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 6px;
        vertical-align: middle;
        text-transform: uppercase;
    }
</style>

@component('components.widget', ['class' => 'box-solid', 'title' => 'Quotation PDF details'])
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('prepared_by_preview', 'Prepared by') !!}
                {!! Form::text('prepared_by_preview', auth()->user()->user_full_name ?? trim(auth()->user()->surname.' '.auth()->user()->first_name.' '.auth()->user()->last_name), [
                    'class' => 'form-control',
                    'readonly',
                ]) !!}
                <p class="help-block" style="margin-top:4px;">System user account full name automatically used on PDF.</p>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs pw-quote-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#pw-quote-tab-terms" aria-controls="pw-quote-tab-terms" role="tab" data-toggle="tab">
                Terms &amp; Conditions
            </a>
        </li>
        <li role="presentation">
            <a href="#pw-quote-tab-additional" aria-controls="pw-quote-tab-additional" role="tab" data-toggle="tab">
                Additional Terms &amp; Conditions
            </a>
        </li>
        <li role="presentation">
            <a href="#pw-quote-tab-notes" aria-controls="pw-quote-tab-notes" role="tab" data-toggle="tab">
                Additional Notes
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="pw-quote-tab-terms">
            <div class="form-group">
                {!! Form::label('quotation_terms', 'Terms & Conditions (shown on PDF)') !!}
                <p class="help-block" style="margin-top:0;">Default save වෙලා තියෙනවා. Click කරලා edit කරන්න පුළුවන්.</p>
                {!! Form::textarea('quotation_terms', old('quotation_terms', $defaultQuotationTerms), [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => $defaultQuotationTerms,
                ]) !!}
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="pw-quote-tab-additional">
            <p class="help-block" style="margin-top:0;margin-bottom:14px;">
                PDF එකේ red heading + bullet style එකට යනවා. Default text save වෙලා තියෙනවා — click කරලා edit කරන්න.
                Custom sections empty නම් PDF එකේ show නොවේ.
            </p>
            @foreach($additionalSections as $key => $section)
                @php
                    $fieldValue = old('quotation_additional_terms.'.$key, $additionalTermsValues[$key] ?? '');
                    $isCustom = in_array($key, ['delivery', 'installation', 'additional_accessories'], true);
                @endphp
                <div class="pw-additional-section">
                    <div class="pw-additional-section-title">
                        {{ $section['title'] }}
                        @if($isCustom)
                            <span class="pw-custom-badge">Custom</span>
                        @endif
                    </div>
                    {!! Form::textarea(
                        'quotation_additional_terms['.$key.']',
                        $fieldValue,
                        [
                            'class' => 'form-control pw-additional-term-field',
                            'rows' => $section['rows'],
                            'placeholder' => $section['placeholder'] ?: 'Enter text for PDF...',
                            'data-section-key' => $key,
                        ]
                    ) !!}
                    @if($isCustom)
                        <p class="help-block">Custom text එක type කර save කරන්න. Empty නම් PDF එකේ show නොවේ.</p>
                    @else
                        <p class="help-block">Default policy text — edit කර quotation එකට match කරන්න.</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div role="tabpanel" class="tab-pane" id="pw-quote-tab-notes">
            <div class="form-group">
                {!! Form::label('sale_note_quote', 'Additional Notes (shown on PDF)') !!}
                <p class="help-block" style="margin-top:0;">Quotation PDF Additional Notes කොටසට යනවා. Line එකක් එක bullet point එකක්.</p>
                {!! Form::textarea('sale_note', old('sale_note', $saleNoteValue ?? ''), [
                    'class' => 'form-control',
                    'rows' => 6,
                    'id' => 'sale_note_quote',
                    'placeholder' => "Additional note line 1\nAdditional note line 2",
                ]) !!}
            </div>
        </div>
    </div>
@endcomponent
