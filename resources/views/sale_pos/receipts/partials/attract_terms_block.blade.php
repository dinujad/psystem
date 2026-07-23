{{-- Terms & Notes: follows content (not page-bottom). Same for Printworks + Safety Sign. --}}
<div class="terms-follow">
  <div class="terms-title">Terms &amp; Conditions</div>
  <ul class="terms-list">
    @forelse($quotationTermLines as $term)
      <li>{{ $term }}</li>
    @empty
      <li>—</li>
    @endforelse
  </ul>
  <div class="terms-title" style="margin-top:12px;">Additional Notes</div>
  <ul class="terms-list">
    @forelse($additionalNoteLines as $note)
      <li>{{ $note }}</li>
    @empty
      <li>—</li>
    @endforelse
  </ul>
</div>
