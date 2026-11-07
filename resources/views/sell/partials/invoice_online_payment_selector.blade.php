@php
    $selectedOnlinePay = old(
        'invoice_online_payment',
        (!empty($transaction) ? ($transaction->invoice_online_payment ?? null) : null) ?: ((!empty($invoice_mode)) ? 'card' : 'bank')
    );
@endphp
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('invoice_online_payment', 'Invoice payment method') !!}
        <p class="help-block" style="margin:0 0 6px;font-size:12px;">
            Customer invoice link එකේ පෙන්වන payment option එක. Bank transfer නම් Pay buttons නෑ.
        </p>
        {!! Form::select('invoice_online_payment', [
            'card' => 'Pay with Card (OnePay)',
            'payzy' => 'Payzy 4 Installment',
            'bank' => 'Bank transfer (no online pay buttons)',
        ], $selectedOnlinePay, ['class' => 'form-control select2', 'id' => 'invoice_online_payment', 'style' => 'width:100%;']) !!}
    </div>
</div>
