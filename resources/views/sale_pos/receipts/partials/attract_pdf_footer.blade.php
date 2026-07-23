@php
    $documentBrand = strtolower(trim((string) ($document_brand ?? 'printworks')));
    if (! in_array($documentBrand, ['printworks', 'safetysign'], true)) {
        $documentBrand = 'printworks';
    }
    $isSafetySign = $documentBrand === 'safetysign';

    $footerPath = public_path('images/footer.png');
    if (! file_exists($footerPath)) {
        $footerPath = public_path('images/footer (1).png');
    }
    if ($isSafetySign) {
        $footerPath = null;
    }

    $docLabel = strtolower($document_title ?? 'invoice');
    $isQuote = str_contains(strtoupper((string) ($document_title ?? '')), 'QUOT');
    $brandAccent = $isSafetySign ? '#111111' : '#E31E24';
    $tagline = $isQuote
        ? ($isSafetySign
            ? '“Clear signs. Strong brands. Safer spaces.”'
            : 'Committed to excellence with every project.')
        : ($isSafetySign
            ? '“Thank you for choosing Safety Sign.”'
            : '“Every print tells a story. Thank you for making us part of yours.”');
    $sysNote = $isQuote
        ? 'System generated Quotation. No signature required.'
        : 'System-generated '.$docLabel.'. No signature required.';
@endphp
<div style="width:210mm; margin:0; padding:0 12mm 2mm 12mm; box-sizing:border-box; text-align:left; font-size:9px; color:#666;">
    <div style="font-weight:700; color:#111; margin:0 0 3px 0;">{{ $sysNote }}</div>
    <div style="color:{{ $brandAccent }}; font-style:italic; margin:0 0 6px 0;">{{ $tagline }}</div>
    @if($isSafetySign)
    <div style="color:#111; font-weight:700; margin:0 0 2px 0;">Safety Sign.lk — Signage &amp; Advertising Solutions</div>
    <div style="margin:0 0 4px 0;">Web: www.safetysign.lk</div>
    @endif
</div>
@if($footerPath && file_exists($footerPath))
<div style="width:210mm; margin:0; padding:0; text-align:center;">
    <img src="{{ $footerPath }}" style="width:210mm; height:auto; display:block; margin:0; padding:0; border:0;" alt="Footer">
</div>
@endif
