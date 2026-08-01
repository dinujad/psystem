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

    $footerHeightMm = 36;
    if ($footerPath && file_exists($footerPath) && ($footerSize = @getimagesize($footerPath)) && $footerSize[0] > 0) {
        $footerHeightMm = min(60, max(24, round(210 * $footerSize[1] / $footerSize[0], 2)));
    }
@endphp
{{-- Sys-note and tagline live inline under "Prepared by" so mPDF matches the browser preview --}}
@if($footerPath && file_exists($footerPath))
<div style="width:210mm; margin:0; padding:0; text-align:center;">
    <img src="{{ $footerPath }}" style="width:210mm; height:{{ $footerHeightMm }}mm; display:block; margin:0; padding:0; border:0;" alt="Footer">
</div>
@endif
