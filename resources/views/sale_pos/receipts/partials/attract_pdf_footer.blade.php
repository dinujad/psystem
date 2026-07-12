@php
    $footerPath = public_path('images/footer.png');
    if (! file_exists($footerPath)) {
        $footerPath = public_path('images/footer (1).png');
    }
    $docLabel = strtolower($document_title ?? 'invoice');
    $isQuote = str_contains(strtoupper((string) ($document_title ?? '')), 'QUOT');
    $brandRed = '#E31E24';
    $tagline = $isQuote
        ? 'Committed to excellence with every project.'
        : '“Every print tells a story. Thank you for making us part of yours.”';
    $sysNote = $isQuote
        ? 'System generated Quotation. No signature required.'
        : 'System-generated '.$docLabel.'. No signature required.';
@endphp
<div style="width:210mm; margin:0; padding:0 12mm 2mm 12mm; box-sizing:border-box; text-align:left; font-size:9px; color:#666;">
    <div style="font-weight:700; color:#111; margin:0 0 3px 0;">{{ $sysNote }}</div>
    <div style="color:{{ $brandRed }}; font-style:italic; margin:0 0 6px 0;">{{ $tagline }}</div>
</div>
@if(file_exists($footerPath))
<div style="width:210mm; margin:0; padding:0; text-align:center;">
    <img src="{{ $footerPath }}" style="width:210mm; height:auto; display:block; margin:0; padding:0; border:0;" alt="Footer">
</div>
@endif
