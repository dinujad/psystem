@php
    $footerPath = public_path('images/footer (1).png');
@endphp
@if(file_exists($footerPath))
<div style="width:100%; margin:0; padding:0; text-align:center;">
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($footerPath)) }}" width="194mm" style="display:block; margin:0 auto;" alt="Footer">
</div>
@endif
