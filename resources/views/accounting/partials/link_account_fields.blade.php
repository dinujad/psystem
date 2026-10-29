@php
    $show_link_accounts = auth()->check() && (
        auth()->user()->can('superadmin')
        || (new \App\Utils\ModuleUtil())->hasThePermissionInSubscription(session('user.business_id'), 'accounting_module')
    );
@endphp
@if($show_link_accounts)
<div class="col-sm-12">
    <div class="well well-sm" style="margin-top:8px;">
        <strong>Link account</strong>
        <p class="help-block" style="margin-bottom:8px;">Choose the chart of account for this entry. Leave both blank to use the accounts already linked in Accounting settings. Deposit to is debited. Payment account is credited.</p>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('accounting_deposit_to', __('accounting::lang.deposit_to').':') !!}
                    {!! Form::select('accounting_deposit_to', [], null, ['class' => 'form-control accounts-dropdown', 'placeholder' => __('accounting::lang.deposit_to'), 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('accounting_payment_account', __('accounting::lang.payment_account').':') !!}
                    {!! Form::select('accounting_payment_account', [], null, ['class' => 'form-control accounts-dropdown', 'placeholder' => __('accounting::lang.payment_account'), 'style' => 'width:100%']) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endif
