<link href="{{ asset('css/tailwind/app.css') }}" rel="stylesheet">
<style>
    /* ── PrintWorks ERP Login ───────────────────────────── */
    html, body.pw-auth-body {
        height: 100%;
        margin: 0;
        padding: 0;
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .pw-auth-body {
        background: #0c0a1a;
        color: #e5e7eb;
        min-height: 100vh;
    }

    .pw-login-page {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }

    .pw-login-topbar {
        position: absolute;
        top: 16px;
        right: 20px;
        z-index: 20;
    }

    .pw-login-shell {
        flex: 1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 100vh;
    }

    @media (max-width: 960px) {
        .pw-login-shell { grid-template-columns: 1fr; }
        .pw-login-hero { display: none; }
    }

    /* Hero panel */
    .pw-login-hero {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 48px 40px;
        background: linear-gradient(145deg, #1e1b4b 0%, #312e81 40%, #4c1d95 100%);
        overflow: hidden;
    }

    .pw-hero-glow {
        position: absolute;
        width: 500px;
        height: 500px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(124, 92, 252, 0.35) 0%, transparent 70%);
        top: -120px;
        right: -100px;
        pointer-events: none;
    }

    .pw-login-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 24px 24px;
        pointer-events: none;
    }

    .pw-hero-inner {
        position: relative;
        z-index: 1;
        max-width: 420px;
    }

    .pw-brand-lockup {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .pw-brand-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(124,92,252,0.3), rgba(167,139,250,0.15));
        border: 1px solid rgba(167,139,250,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #c4b5fd;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }

    .pw-brand-icon svg { width: 32px; height: 32px; }

    .pw-brand-text {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
    }

    .pw-brand-name {
        font-size: 36px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #fff;
    }

    .pw-brand-erp {
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.28em;
        text-transform: uppercase;
        color: #a78bfa;
        margin-top: 2px;
    }

    .pw-hero-tagline {
        font-size: 16px;
        line-height: 1.6;
        color: rgba(255,255,255,0.75);
        margin: 0 0 32px;
    }

    .pw-hero-features {
        list-style: none;
        padding: 0;
        margin: 0 0 36px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .pw-hero-features li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 14px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        backdrop-filter: blur(8px);
    }

    .pw-feat-icon {
        width: 36px;
        height: 36px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: rgba(124, 92, 252, 0.15);
        border: 1px solid rgba(167, 139, 250, 0.25);
        color: #c4b5fd;
    }

    .pw-feat-icon svg {
        width: 18px;
        height: 18px;
    }

    .pw-hero-features strong {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 2px;
    }

    .pw-hero-features span {
        font-size: 12px;
        color: rgba(255,255,255,0.55);
    }

    .pw-hero-footer {
        font-size: 12px;
        color: rgba(255,255,255,0.4);
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin: 0;
    }

    /* Form panel */
    .pw-login-main {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 24px;
        background: #0c0a1a;
        position: relative;
    }

    .pw-login-main::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 1px;
        height: 100%;
        background: linear-gradient(180deg, transparent, rgba(124,92,252,0.3), transparent);
    }

    @media (max-width: 960px) {
        .pw-login-main::before { display: none; }
    }

    .pw-login-card {
        width: 100%;
        max-width: 400px;
    }

    .pw-mobile-brand {
        display: none;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 24px;
        justify-content: center;
    }

    @media (max-width: 960px) {
        .pw-mobile-brand { display: flex; }
    }

    .pw-m-brand-name {
        font-size: 26px;
        font-weight: 800;
        color: #fff;
    }

    .pw-m-brand-erp {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.2em;
        color: #a78bfa;
        background: rgba(124,92,252,0.15);
        border: 1px solid rgba(124,92,252,0.3);
        padding: 3px 8px;
        border-radius: 6px;
    }

    .pw-login-title {
        font-size: 26px;
        font-weight: 800;
        color: #fff;
        margin: 0 0 6px;
        letter-spacing: -0.02em;
    }

    .pw-login-sub {
        font-size: 14px;
        color: #9ca3af;
        margin: 0 0 28px;
    }

    .pw-login-sub strong { color: #c4b5fd; font-weight: 600; }

    .pw-field { margin-bottom: 18px; }

    .pw-field label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #d1d5db;
        margin-bottom: 7px;
    }

    .pw-label-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 7px;
    }

    .pw-label-row label { margin-bottom: 0; }

    .pw-forgot {
        font-size: 12px;
        color: #a78bfa;
        text-decoration: none;
        font-weight: 500;
    }

    .pw-forgot:hover { color: #c4b5fd; text-decoration: underline; }

    .pw-input-wrap {
        position: relative;
    }

    .pw-input-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: #6b7280;
        pointer-events: none;
    }

    .pw-input {
        width: 100%;
        height: 48px;
        padding: 0 16px 0 42px;
        background: #16122a;
        border: 1.5px solid #2d2640;
        border-radius: 12px;
        color: #f9fafb;
        font-size: 14px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .pw-input-pass { padding-right: 44px; }

    .pw-input:focus {
        border-color: #7c5cfc;
        box-shadow: 0 0 0 3px rgba(124, 92, 252, 0.2);
    }

    .pw-input::placeholder { color: #4b5563; }

    .pw-show-pass {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
    }

    .pw-show-pass:hover { color: #d1d5db; }

    .pw-remember {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        font-size: 13px;
        color: #9ca3af;
        user-select: none;
        margin-top: 4px;
    }

    .pw-remember input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #7c5cfc;
        cursor: pointer;
    }

    .pw-submit {
        width: 100%;
        height: 50px;
        margin-top: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: linear-gradient(135deg, #7c5cfc 0%, #6d28d9 100%);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(124, 92, 252, 0.4);
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .pw-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 28px rgba(124, 92, 252, 0.5);
    }

    .pw-submit:active { transform: translateY(0); }

    .pw-error {
        display: block;
        color: #f87171;
        font-size: 12px;
        margin-top: 5px;
    }

    .pw-recaptcha { margin-top: 14px; }

    .pw-register {
        text-align: center;
        margin-top: 20px;
        font-size: 13px;
        color: #6b7280;
    }

    .pw-register a {
        color: #a78bfa;
        font-weight: 600;
        text-decoration: none;
        margin-left: 4px;
    }

    .pw-register a:hover { text-decoration: underline; }

    .pw-copy {
        text-align: center;
        margin-top: 32px;
        font-size: 11px;
        color: #374151;
    }

    /* Demo panel */
    .pw-demo-panel {
        width: 100%;
        max-width: 400px;
        margin-bottom: 20px;
    }

    /* Language dropdown on dark bg */
    .pw-login-topbar .dropdown-menu {
        background: #1a1628 !important;
        border: 1px solid #2d2640 !important;
    }

    .pw-login-topbar .btn,
    .pw-login-topbar a {
        color: #d1d5db !important;
    }

    /* Legacy auth pages */
    .dropdown-menu {
        background-color: #1a1a1a !important;
        border: 1px solid #333 !important;
    }
    .dropdown-menu > li > a {
        color: #ddd !important;
    }
    .dropdown-menu > li > a:hover {
        background-color: #333 !important;
        color: #fff !important;
    }

    body { min-height: 100vh; background: transparent; margin: 0; padding: 0; }
    h1 { color: #fff; }
</style>

<style type="text/css">
    .patt-wrap { z-index: 10; }
    .patt-circ.hovered { background-color: #cde2f2; border: none; }
    .patt-circ.hovered .patt-dots { display: none; }
    .patt-circ.dir {
        background-image: url("http://pos.test/img/pattern-directionicon-arrow.png");
        background-position: center;
        background-repeat: no-repeat;
    }
    .patt-circ.e { transform: rotate(0deg); }
    .patt-circ.s-e { transform: rotate(45deg); }
    .patt-circ.s { transform: rotate(90deg); }
    .patt-circ.s-w { transform: rotate(135deg); }
    .patt-circ.w { transform: rotate(180deg); }
    .patt-circ.n-w { transform: rotate(225deg); }
    .patt-circ.n { transform: rotate(270deg); }
    .patt-circ.n-e { transform: rotate(315deg); }
</style>
