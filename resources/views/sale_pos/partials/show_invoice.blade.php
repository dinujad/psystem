@extends('layouts.guest')
@section('title', $title)
@section('content')

<div class="container">
    <div class="spacer"></div>
    <div class="row">
        <div class="col-md-12 text-right mb-12" >
            @if(!empty($payment_link))
                <a href="{{$payment_link}}" class="btn btn-info no-print" style="margin-right: 20px;"><i class="fas fa-money-check-alt" title="@lang('lang_v1.pay')"></i> @lang('lang_v1.pay')
                </a>
            @endif
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print tw-dw-btn-sm" id="print_invoice" 
                 aria-label="Print"><i class="fas fa-print"></i> @lang( 'messages.print' )
            </button>
            @auth
                <a href="{{action([\App\Http\Controllers\SellController::class, 'index'])}}" class="tw-dw-btn tw-dw-btn-success tw-text-white no-print tw-dw-btn-sm" ><i class="fas fa-backward"></i>
                </a>
            @endauth
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-sm-12" style="border: 1px solid #ccc; background:#fff;">
            <div class="spacer"></div>
            <div id="invoice_content" style="max-width:210mm; margin:0 auto;">
                {!! $receipt['html_content'] !!}
            </div>
            <div class="spacer"></div>
        </div>
    </div>
    <div class="spacer"></div>
</div>
@stop
@section('javascript')
<script type="text/javascript">
    function printInvoiceWithColors() {
        $('#invoice_content').printThis({
            importCSS: true,
            importStyle: true,
            printContainer: true,
            removeInline: false,
            printDelay: 500,
            header: '<style type="text/css">' +
                '@page{size:A4;margin:10mm 0 0 0;}' +
                'html,body{margin:0;padding:0;background:#fff!important;width:210mm;}' +
                'html,body,*,*::before,*::after,table,th,td,div,span{' +
                '-webkit-print-color-adjust:exact!important;' +
                'print-color-adjust:exact!important;' +
                'color-adjust:exact!important;}' +
                '.sheet{min-height:277mm!important;box-sizing:border-box!important;padding:0 12mm 40mm 12mm!important;position:relative!important;width:210mm!important;}' +
                '.page-footer,.footer-bar{position:fixed!important;left:0!important;right:0!important;bottom:0!important;width:210mm!important;max-width:100%!important;margin:0!important;padding:0!important;}' +
                '.page-footer img{width:210mm!important;max-width:100%!important;height:auto!important;display:block!important;}' +
                '</style>'
        });
    }

    $(document).ready(function(){
        $(document).on('click', '#print_invoice', function(){
            printInvoiceWithColors();
        });
    });
    @if(!empty(request()->input('print_on_load')))
        $(window).on('load', function(){
            printInvoiceWithColors();
        });
    @endif
</script>
@endsection