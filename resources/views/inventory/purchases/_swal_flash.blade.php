{{-- Nice SweetAlert popups for purchase success / validation errors --}}
@php
    $swalErrors = isset($errors) && $errors->any() ? $errors->all() : [];
    $legacySuccess = session('success');
@endphp
@if(!empty($swalErrors) || (!empty($legacySuccess) && !session('swal_popup')))
<script>
(function () {
    function showRmpSwal() {
        if (typeof swal !== 'function') {
            return;
        }
        @if(!empty($swalErrors))
            swal({
                title: 'Error',
                text: {!! json_encode(implode("\n", $swalErrors)) !!},
                icon: 'error',
                button: 'OK'
            });
        @elseif(!empty($legacySuccess))
            swal({
                title: 'Success',
                text: {!! json_encode($legacySuccess) !!},
                icon: 'success',
                button: 'OK'
            });
        @endif
    }
    if (window.jQuery) {
        $(document).ready(showRmpSwal);
    } else {
        document.addEventListener('DOMContentLoaded', showRmpSwal);
    }
})();
</script>
@endif
