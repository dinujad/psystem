@php
    $documentBrand = strtolower(trim((string) ($document_brand ?? 'printworks')));
    if (! in_array($documentBrand, ['printworks', 'safetysign'], true)) {
        $documentBrand = 'printworks';
    }
    $isSafetySign = $documentBrand === 'safetysign';

    if ($isSafetySign) {
        $footerCandidates = [
            public_path('images/safety sign footer.png'),
            public_path('images/safetysign_footer.png'),
            public_path('images/safetysignfooter.png'),
        ];
        $footerPath = null;
        foreach ($footerCandidates as $candidate) {
            if (file_exists($candidate)) {
                $footerPath = $candidate;
                break;
            }
        }
    } else {
        $footerPath = public_path('images/footer.png');
        if (! file_exists($footerPath)) {
            $footerPath = public_path('images/footer (1).png');
        }
        if ($footerPath && ! file_exists($footerPath)) {
            $footerPath = null;
        }
    }

    $docLabel = strtolower($document_title ?? 'invoice');
    $isQuote = str_contains(strtoupper((string) ($document_title ?? '')), 'QUOT');
    $brandAccent = $isSafetySign ? '#F9A810' : '#E31E24';
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
</div>
@if($footerPath && file_exists($footerPath))
<div style="width:210mm; margin:0; padding:0; text-align:center;">
    <img src="{{ $footerPath }}" style="width:210mm; height:auto; display:block; margin:0; padding:0; border:0;" alt="Footer">
</div>
@endif
