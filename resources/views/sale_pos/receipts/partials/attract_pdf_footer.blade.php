@php
    $footerPath = public_path('images/footer.png');
    if (! file_exists($footerPath)) {
        $footerPath = public_path('images/footer (1).png');
    }
    $signPath = public_path('images/sign.png');
    $signB64 = file_exists($signPath) ? base64_encode(file_get_contents($signPath)) : null;
    $docLabel = strtolower($document_title ?? 'invoice');
@endphp
{{-- Signature sits directly above brand footer on every page bottom --}}
<div style="width:100%; margin:0; padding:0 12mm 3mm 12mm; box-sizing:border-box;">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="vertical-align:bottom; text-align:left; font-size:10px; font-style:italic; color:#9ca3af; padding:0 0 6px 0;">
                *This is a system generated {{ $docLabel }}.
            </td>
            <td style="vertical-align:bottom; text-align:right; font-size:12px; color:#111; line-height:1.35; padding:0 0 2px 0;">
                @if(! empty($signB64))
                    <img src="data:image/png;base64,{{ $signB64 }}" style="height:58px; width:auto; display:block; margin:0 0 4px auto;" alt="Signature">
                @endif
                <div style="font-size:12px; color:#222; margin:0;">Yours faithfully,</div>
                <div style="font-size:13px; font-weight:700; color:#111; margin:0;">Sandaruwan Dharampriya</div>
                <div style="font-size:11px; color:#444; margin:0;">Director, PrintWorks</div>
            </td>
        </tr>
    </table>
</div>
@if(file_exists($footerPath))
<div style="width:100%; margin:0; padding:0; text-align:center; line-height:0; font-size:0;">
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($footerPath)) }}" style="width:210mm; display:block; margin:0;" alt="Footer">
</div>
@endif
