@extends('layouts.auth2')
@section('title', __('lang_v1.login'))
@inject('request', 'Illuminate\Http\Request')
@section('content')
    @php
        $username = old('username');
        $password = null;
        if (config('app.env') == 'demo') {
            $username = 'admin';
            $password = '123456';

            $demo_types = [
                'all_in_one' => 'admin',
                'super_market' => 'admin',
                'pharmacy' => 'admin-pharmacy',
                'electronics' => 'admin-electronics',
                'services' => 'admin-services',
                'restaurant' => 'admin-restaurant',
                'superadmin' => 'superadmin',
                'woocommerce' => 'woocommerce_user',
                'essentials' => 'admin-essentials',
                'manufacturing' => 'manufacturer-demo',
            ];

            if (!empty($_GET['demo_type']) && array_key_exists($_GET['demo_type'], $demo_types)) {
                $username = $demo_types[$_GET['demo_type']];
            }
        }
    @endphp

    <div class="pw-login-page">
        {{-- Language --}}
        <div class="pw-login-topbar">
            @include('layouts.partials.language_btn')
        </div>

        <div class="pw-login-shell">
            {{-- Brand panel --}}
            <aside class="pw-login-hero" aria-hidden="false">
                <div class="pw-hero-inner">
                    <div class="pw-brand-lockup">
                        <div class="pw-brand-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9V2h12v7"/>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                                <path d="M6 14h12v8H6z"/>
                            </svg>
                        </div>
                        <div class="pw-brand-text">
                            <span class="pw-brand-name">PrintWorks</span>
                            <span class="pw-brand-erp">ERP</span>
                        </div>
                    </div>

                    <p class="pw-hero-tagline">Complete business management for print shops &amp; production houses.</p>

                    <ul class="pw-hero-features">
                        <li>
                            <span class="pw-feat-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5h-2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-12a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
                            </span>
                            <div><strong>Production &amp; Jobs</strong><span>Track orders from quote to delivery</span></div>
                        </li>
                        <li>
                            <span class="pw-feat-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/><path d="M3 3h2l2 12h12l2-8H7"/></svg>
                            </span>
                            <div><strong>POS &amp; Sales</strong><span>Invoices, payments &amp; customers</span></div>
                        </li>
                        <li>
                            <span class="pw-feat-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-9"/><path d="M8 22v-5"/><path d="M16 22v-5"/><path d="M3 9l9-6 9 6v2H3z"/></svg>
                            </span>
                            <div><strong>Inventory</strong><span>Stock, materials &amp; suppliers</span></div>
                        </li>
                        <li>
                            <span class="pw-feat-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <div><strong>Team Tasks</strong><span>Weekly plans &amp; employee to-dos</span></div>
                        </li>
                    </ul>

                    <p class="pw-hero-footer">Secure · Fast · Built for your business</p>
                </div>
                <div class="pw-hero-glow" aria-hidden="true"></div>
            </aside>

            {{-- Form panel --}}
            <main class="pw-login-main">
                @if (config('app.env') == 'demo')
                <div class="pw-demo-panel">
                    @component('components.widget', [
                        'class' => 'box-primary',
                        'header' => '<h4 class="text-center" style="color:#fff;">Demo Shops <small style="color:#aaa;"><i> <br/>Demos are for example purpose only.<br/><b>Click button to login that business</b></small></h4>',
                    ])
                        <a href="?demo_type=all_in_one" class="btn btn-app bg-olive demo-login" data-toggle="tooltip"
                            title="Showcases all feature available in the application."
                            data-admin="{{ $demo_types['all_in_one'] }}"> <i class="fas fa-star"></i> All In One</a>
                        <a href="?demo_type=pharmacy" class="btn bg-maroon btn-app demo-login" data-toggle="tooltip"
                            title="Shops with products having expiry dates." data-admin="{{ $demo_types['pharmacy'] }}"><i class="fas fa-medkit"></i>Pharmacy</a>
                        <a href="?demo_type=services" class="btn bg-orange btn-app demo-login" data-toggle="tooltip"
                            title="For all service providers." data-admin="{{ $demo_types['services'] }}"><i class="fas fa-wrench"></i>Multi-Service</a>
                        <a href="?demo_type=electronics" class="btn bg-purple btn-app demo-login" data-toggle="tooltip"
                            title="Products having IMEI or Serial number." data-admin="{{ $demo_types['electronics'] }}"><i class="fas fa-laptop"></i>Electronics</a>
                        <a href="?demo_type=super_market" class="btn bg-navy btn-app demo-login" data-toggle="tooltip"
                            title="Super market shops." data-admin="{{ $demo_types['super_market'] }}"><i class="fas fa-shopping-cart"></i> Super Market</a>
                        <a href="?demo_type=restaurant" class="btn bg-red btn-app demo-login" data-toggle="tooltip"
                            title="Restaurants and similar shops." data-admin="{{ $demo_types['restaurant'] }}"><i class="fas fa-utensils"></i> Restaurant</a>
                    @endcomponent
                </div>
                @endif

                <div class="pw-login-card">
                    <div class="pw-mobile-brand">
                        <span class="pw-m-brand-name">PrintWorks</span>
                        <span class="pw-m-brand-erp">ERP</span>
                    </div>

                    <h1 class="pw-login-title">@lang('lang_v1.welcome_back')</h1>
                    <p class="pw-login-sub">@lang('lang_v1.login_to_your') <strong>PrintWorks ERP</strong> account</p>

                    <form method="POST" action="{{ route('login') }}" id="login-form" class="pw-login-form">
                        {{ csrf_field() }}

                        <div class="pw-field">
                            <label for="username">@lang('lang_v1.username')</label>
                            <div class="pw-input-wrap">
                                <svg class="pw-input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
                                <input class="pw-input" name="username" id="username" type="text" required autofocus
                                    placeholder="Username or email" value="{{ $username }}">
                            </div>
                            @if ($errors->has('username'))
                                <span class="pw-error"><strong>{{ $errors->first('username') }}</strong></span>
                            @endif
                        </div>

                        <div class="pw-field">
                            <div class="pw-label-row">
                                <label for="password">@lang('lang_v1.password')</label>
                                @if (config('app.env') != 'demo')
                                    <a href="{{ route('password.request') }}" class="pw-forgot" tabindex="-1">@lang('lang_v1.forgot_your_password')</a>
                                @endif
                            </div>
                            <div class="pw-input-wrap">
                                <svg class="pw-input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11v-4a4 4 0 0 1 8 0v4"/></svg>
                                <input class="pw-input pw-input-pass" id="password" type="password" name="password"
                                    value="{{ $password }}" required placeholder="••••••••">
                                <button type="button" id="show_hide_icon" class="pw-show-pass" aria-label="Show password">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/></svg>
                                </button>
                            </div>
                            @if ($errors->has('password'))
                                <span class="pw-error"><strong>{{ $errors->first('password') }}</strong></span>
                            @endif
                        </div>

                        <label class="pw-remember">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>@lang('lang_v1.remember_me')</span>
                        </label>

                        @if(config('constants.enable_recaptcha'))
                        <div class="pw-recaptcha">
                            <div class="g-recaptcha" data-sitekey="{{ config('constants.google_recaptcha_key') }}"></div>
                            @if ($errors->has('g-recaptcha-response'))
                                <span class="pw-error">{{ $errors->first('g-recaptcha-response') }}</span>
                            @endif
                        </div>
                        @endif

                        <button type="submit" class="pw-submit">
                            @lang('lang_v1.login')
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M13 18l6-6-6-6"/></svg>
                        </button>
                    </form>

                    <p class="pw-copy">Developed By E media Solution Pvt Ltd</p>
                </div>
            </main>
        </div>
    </div>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#show_hide_icon').off('click');
            $('.change_lang').click(function() {
                window.location = "{{ route('login') }}?lang=" + $(this).attr('value');
            });
            $('a.demo-login').click(function(e) {
                e.preventDefault();
                $('#username').val($(this).data('admin'));
                $('#password').val("{{ $password }}");
                $('form#login-form').submit();
            });

            $('#show_hide_icon').on('click', function(e) {
                e.preventDefault();
                const passwordInput = $('#password');
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828"/><path d="M16.681 16.673a8.717 8.717 0 0 1-4.681 1.327c-3.6 0-6.6-2-9-6c1.272-2.12 2.712-3.678 4.32-4.674m2.86-1.146a9.055 9.055 0 0 1 1.82-.18c3.6 0 6.6 2 9 6c-.666 1.11-1.379 2.067-2.138 2.87"/><path d="M3 3l18 18"/></svg>');
                } else {
                    passwordInput.attr('type', 'password');
                    $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6"/></svg>');
                }
            });
        });
    </script>
@endsection
