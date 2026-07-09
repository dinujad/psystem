<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — PrintWorks ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('layouts.partials.css')
    @include('layouts.partials.extracss_auth')
    @if(config('constants.enable_recaptcha'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
</head>
<body class="pw-auth-body">
    @if (session('status') && session('status.success') !== null)
        <input type="hidden" id="status_span" data-status="{{ session('status.success') }}"
            data-msg="{{ session('status.msg') }}">
    @endif

    @yield('content')

    @include('layouts.partials.javascripts')
    <script src="{{ asset('js/login.js?v=' . $asset_v) }}"></script>
    @yield('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2_register').select2();
        });
    </script>
</body>
</html>
