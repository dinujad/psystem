@extends('layouts.guest')
@section('title', $title)

@section('css')
<style>
.pw-inv-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 12px;
}
.pw-inv-actions .btn,
.pw-inv-actions .tw-dw-btn {
    margin: 0 !important;
    white-space: nowrap;
}
.pw-inv-frame {
    border: 1px solid #ccc;
    background: #fff;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.pw-inv-inner {
    max-width: 210mm;
    margin: 0 auto;
    min-width: 280px;
}
@media (max-width: 767px) {
    .container { width: 100% !important; padding-left: 10px !important; padding-right: 10px !important; }
    .pw-inv-actions {
        justify-content: stretch;
        flex-direction: column;
        align-items: stretch;
    }
    .pw-inv-actions .btn,
    .pw-inv-actions .tw-dw-btn,
    .pw-inv-actions a.btn {
        width: 100%;
        display: block;
        text-align: center;
        box-sizing: border-box;
        padding: 12px 14px !important;
        font-size: 15px !important;
    }
    .pw-inv-actions .alert {
        width: 100%;
        display: block !important;
        margin: 0 !important;
        box-sizing: border-box;
    }
    .pw-inv-frame {
        border-left: none;
        border-right: none;
        margin-left: -10px;
        margin-right: -10px;
        width: calc(100% + 20px);
    }
    .pw-inv-inner {
        max-width: 100%;
        transform-origin: top left;
    }
    /* Scale invoice content for narrow screens */
    #invoice_content {
        width: 210mm;
        max-width: none;
        transform: scale(calc((100vw - 20px) / 210mm));
        transform-origin: top left;
    }
    #invoice_content::after {
        content: '';
        display: block;
        /* spacer so scaled height doesn't overlap next content — approx */
        height: 0;
    }
}
@media print {
    .pw-inv-actions { display: none !important; }
    .pw-inv-frame { border: none !important; overflow: visible !important; }
    #invoice_content { transform: none !important; width: 210mm !important; }
}
</style>
@endsection

@section('content')

<div class="container">
    <div class="spacer"></div>
    <div class="row">
        <div class="col-xs-12 col-md-12">
            <div class="pw-inv-actions no-print">
            @if(!empty($onepay_pay_url))
                <a href="{{ $onepay_pay_url }}" class="btn btn-success">
                    <i class="fas fa-credit-card"></i> Pay with Card
                </a>
            @endif
            @if(!empty($payzy_pay_url))
                <a href="{{ $payzy_pay_url }}" class="btn btn-warning">
                    <i class="fas fa-calendar-alt"></i> Payzy 4 Installment
                </a>
            @endif
            @if(empty($onepay_pay_url) && empty($payzy_pay_url) && !empty($payment_link))
                <a href="{{$payment_link}}" class="btn btn-info"><i class="fas fa-money-check-alt"></i> Pay Now
                </a>
            @endif
            @if(session('status.msg') || (is_array(session('status')) && !empty(session('status')['msg'])))
                @php $st = session('status'); $stMsg = is_array($st) ? ($st['msg'] ?? '') : session('status.msg'); $stOk = is_array($st) ? !empty($st['success']) : session('status.success'); @endphp
                <div class="alert {{ !empty($stOk) ? 'alert-success' : 'alert-warning' }} text-left" style="padding:8px 12px;margin:0;">
                    {{ $stMsg }}
                </div>
            @endif
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm" id="print_invoice"
                 aria-label="Print"><i class="fas fa-print"></i> @lang( 'messages.print' )
            </button>
            @if(!empty($download_url) && config('constants.enable_download_pdf'))
                <a href="{{ $download_url }}" class="tw-dw-btn tw-dw-btn-accent tw-text-white tw-dw-btn-sm" download>
                    <i class="fas fa-download"></i> @lang('lang_v1.download_pdf')
                </a>
            @endif
            @auth
                <a href="{{action([\App\Http\Controllers\SellController::class, 'index'])}}" class="tw-dw-btn tw-dw-btn-success tw-text-white tw-dw-btn-sm" ><i class="fas fa-backward"></i>
                </a>
            @endauth
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-md-8 col-md-offset-2 col-sm-12 pw-inv-frame">
            <div class="spacer"></div>
            <div id="invoice_content" class="pw-inv-inner">
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
    function fitInvoiceToMobile() {
        var el = document.getElementById('invoice_content');
        if (!el || window.innerWidth > 767) {
            if (el) {
                el.style.transform = '';
                el.style.height = '';
                if (el.parentElement) el.parentElement.style.height = '';
            }
            return;
        }
        var pageW = 210 * 3.7795275591; // mm to px approx at 96dpi
        var avail = Math.max(280, window.innerWidth - 20);
        var scale = Math.min(1, avail / pageW);
        el.style.transformOrigin = 'top left';
        el.style.transform = 'scale(' + scale + ')';
        var h = el.scrollHeight * scale;
        el.style.height = (el.scrollHeight) + 'px';
        if (el.parentElement) {
            el.parentElement.style.height = Math.ceil(h + 24) + 'px';
            el.parentElement.style.overflow = 'hidden';
        }
    }

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
        fitInvoiceToMobile();
        $(window).on('resize orientationchange', fitInvoiceToMobile);
        $(document).on('click', '#print_invoice', function(){
            printInvoiceWithColors();
        });
    });
</script>
@endsection
