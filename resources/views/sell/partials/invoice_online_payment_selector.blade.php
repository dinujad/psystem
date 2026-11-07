@php
    // Default = offline payment (bank). Edit කළාම saved value එක පේනවා.
    $selectedOnlinePay = old(
        'invoice_online_payment',
        (!empty($transaction) && !empty($transaction->invoice_online_payment))
            ? $transaction->invoice_online_payment
            : 'bank'
    );
@endphp
<div class="col-sm-4">
    <div class="form-group">
        {!! Form::label('invoice_online_payment', 'Invoice payment method') !!}
        <p class="help-block" style="margin:0 0 6px;font-size:12px;">
            Customer invoice link + PDF එකට යනවා. Offline නම් Pay buttons නෑ. Payzy නම් PDF එකේ tax + 4 installments පේනවා.
        </p>
        {!! Form::select('invoice_online_payment', [
            'bank' => 'Offline payment (bank / cash / cheque)',
            'card' => 'Pay with Card (OnePay)',
            'payzy' => 'Payzy 4 Installment',
        ], $selectedOnlinePay, ['class' => 'form-control select2', 'id' => 'invoice_online_payment', 'style' => 'width:100%;']) !!}
    </div>
</div>
