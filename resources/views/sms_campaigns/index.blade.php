@extends('layouts.app')
@section('title', __('lang_v1.send_sms'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.send_sms')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\SmsCampaignController::class, 'send']), 'method' => 'post']) !!}
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('send_to_all', 1, false, ['id' => 'send_to_all']) !!}
                            Send to all saved customers
                        </label>
                    </div>
                </div>
            </div>

            <div class="row" id="customer_select_wrap">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('contact_ids', __('lang_v1.customers') . ':') !!}
                        {!! Form::select('contact_ids[]', $customers->pluck('name', 'id'), old('contact_ids'), ['class' => 'form-control select2', 'multiple', 'id' => 'contact_ids', 'style' => 'width: 100%;']) !!}
                        <p class="help-block">Select one or more customers for manual SMS.</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('sms_body', __('lang_v1.sms_body') . ':*') !!}
                        {!! Form::textarea('sms_body', old('sms_body'), ['class' => 'form-control', 'rows' => 5, 'required', 'placeholder' => 'Type your message']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">
                @lang('messages.send')
            </button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#send_to_all').on('change', function() {
            if ($(this).is(':checked')) {
                $('#customer_select_wrap').hide();
                $('#contact_ids').prop('disabled', true);
            } else {
                $('#customer_select_wrap').show();
                $('#contact_ids').prop('disabled', false);
            }
        }).trigger('change');
    });
</script>
@endsection
