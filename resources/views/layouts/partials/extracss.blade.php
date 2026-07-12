
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <style>
    body {
      transition: opacity ease-in 0.2s;
    }
    body[unresolved] {
      opacity: 0;
      display: block;
      overflow: hidden;
      position: relative;
    }
  </style>

  <!-- Black + Gold modern theme overrides (visual only) -->
  <style>
    body.skin-blue-light-blackgold {
      background: #0b0f14 !important;
    }

    body.skin-blue-light-blackgold #scrollable-container {
      background: #0b0f14 !important;
      color: #e5e7eb !important;
    }

    body.skin-blue-light-blackgold .side-bar {
      background: #0b0f14 !important;
      border-right-color: rgba(212, 175, 55, 0.25) !important;
    }

    body.skin-blue-light-blackgold .side-bar-heading {
      color: #d4af37 !important;
    }

    body.skin-blue-light-blackgold .tw-bg-white {
      background: #111827 !important;
    }

    body.skin-blue-light-blackgold table.dataTable thead th {
      background: #0b0f14 !important;
      color: #f9fafb !important;
      border-bottom-color: rgba(212, 175, 55, 0.25) !important;
    }

    body.skin-blue-light-blackgold table.dataTable tbody td {
      color: #e5e7eb !important;
    }

    /* Header bar */
    body.skin-blue-light-blackgold main .tw-sticky.no-print.tw-top-0 {
      background: linear-gradient(90deg, #0b0f14 0%, #111827 100%) !important;
      border-bottom-color: rgba(212, 175, 55, 0.35) !important;
    }

    body.skin-blue-light-blackgold main .tw-sticky.no-print.tw-top-0 * {
      color: #f9fafb !important;
    }

    /* Replace blue dashboard gradient with dark one */
    body.skin-blue-light-blackgold .tw-bg-gradient-to-r {
      background-image: linear-gradient(90deg, #0b0f14 0%, #111827 100%) !important;
    }

    /* Common text classes */
    body.skin-blue-light-blackgold .tw-text-gray-900 {
      color: #e5e7eb !important;
    }

    body.skin-blue-light-blackgold .tw-text-gray-600 {
      color: #e5e7eb !important;
    }
  </style>

  <!-- Admin “pro” dashboard theme: dark sidebar + purple accent + light canvas (content unchanged) -->
  <style>
    :root {
      --admin-pro-bg: #f8f9fa;
      --admin-pro-primary: #211f26;
      --admin-pro-primary-light: #2d2a33;
      --admin-pro-primary-dark: #1a1820;
      --admin-pro-sidebar: #211f26;
      --admin-pro-accent: #7c7394;
      --admin-pro-accent-hover: #6b6278;
      --admin-pro-accent-soft: rgba(33, 31, 38, 0.08);
      --admin-pro-card-shadow: 0 4px 24px rgba(28, 28, 33, 0.06);
      --admin-pro-hero-gradient: linear-gradient(135deg, #2d2a33 0%, #211f26 48%, #1a1820 100%);
      --admin-pro-hero-shadow: 0 6px 20px rgba(33, 31, 38, 0.22);
    }

    body.theme-admin-pro {
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: var(--admin-pro-bg) !important;
    }

    body.theme-admin-pro main.tw-flex.tw-flex-col {
      background: var(--admin-pro-bg) !important;
    }

    body.theme-admin-pro #scrollable-container {
      background: var(--admin-pro-bg) !important;
    }

    /* Sidebar shell */
    body.theme-admin-pro .side-bar.admin-pro-sidebar {
      background: var(--admin-pro-sidebar) !important;
      border-right-color: rgba(255, 255, 255, 0.06) !important;
      box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
    }

    /*
     * Sidebar was only as tall as its menu, leaving a white gap below.
     * Stretch the flex wrapper + sidebar to the full viewport so the dark
     * surface fills all the way down.
     */
    body.theme-admin-pro .thetop {
      min-height: 100vh;
      align-items: stretch;
    }

    body.theme-admin-pro .side-bar.admin-pro-sidebar {
      min-height: 100vh;
      height: auto !important;
    }

    body.theme-admin-pro .admin-pro-sidebar-brand {
      background: var(--admin-pro-sidebar) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
      border-right-color: transparent !important;
    }

    body.theme-admin-pro .admin-pro-logo-mark {
      background: linear-gradient(135deg, #3d3947 0%, var(--admin-pro-primary) 100%);
      box-shadow: 0 2px 8px rgba(33, 31, 38, 0.35);
    }

    body.theme-admin-pro .side-bar-heading {
      color: #fff !important;
    }

    body.theme-admin-pro .side-bar #side-bar {
      border-right-color: transparent !important;
      background: transparent !important;
    }

    body.theme-admin-pro .side-bar #side-bar > .tw-px-3.tw-py-2.tw-text-xs {
      color: rgba(255, 255, 255, 0.38) !important;
    }

    /* Nav links */
    body.theme-admin-pro .side-bar #side-bar a.drop_down,
    body.theme-admin-pro .side-bar #side-bar a[href]:not(.drop_down) {
      color: rgba(255, 255, 255, 0.72) !important;
    }

    /*
     * Sidebar hovers: menu links use Tailwind hover:tw-bg-gray-100 + theme forces light text.
     * That yields white-on-white. Force a dark-surface hover + light text (beats utility order).
     */
    body.theme-admin-pro aside.side-bar #side-bar a.drop_down:hover,
    body.theme-admin-pro aside.side-bar #side-bar a.drop_down:focus,
    body.theme-admin-pro aside.side-bar #side-bar a.drop_down:focus-visible,
    body.theme-admin-pro aside.side-bar #side-bar a[href]:not(.drop_down):hover,
    body.theme-admin-pro aside.side-bar #side-bar a[href]:not(.drop_down):focus,
    body.theme-admin-pro aside.side-bar #side-bar a[href]:not(.drop_down):focus-visible {
      background-color: rgba(255, 255, 255, 0.12) !important;
      background-image: none !important;
      color: #fff !important;
    }

    body.theme-admin-pro aside.side-bar #side-bar a:hover span,
    body.theme-admin-pro aside.side-bar #side-bar a:focus span,
    body.theme-admin-pro aside.side-bar #side-bar a:hover i,
    body.theme-admin-pro aside.side-bar #side-bar a:focus i {
      color: #fff !important;
    }

    body.theme-admin-pro aside.side-bar #side-bar a:hover .svg,
    body.theme-admin-pro aside.side-bar #side-bar a:focus .svg {
      color: rgba(255, 255, 255, 0.9) !important;
      stroke: currentColor !important;
    }

    body.theme-admin-pro .side-bar #side-bar a.tw-bg-gray-200,
    body.theme-admin-pro .side-bar #side-bar a.tw-bg-gray-200:hover {
      background: #3d3947 !important;
      color: #fff !important;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
    }

    body.theme-admin-pro .side-bar #side-bar a .svg {
      color: rgba(255, 255, 255, 0.45) !important;
    }

    body.theme-admin-pro .side-bar #side-bar a.tw-bg-gray-200 .svg {
      color: #fff !important;
    }

    body.theme-admin-pro .side-bar #side-bar > div.tw-bg-gray-200 {
      background: transparent !important;
    }

    body.theme-admin-pro .side-bar #side-bar > div.tw-bg-gray-200 > a.drop_down {
      background: var(--admin-pro-accent-soft) !important;
      color: #fff !important;
    }

    body.theme-admin-pro .side-bar .chiled .tw-bg-gray-200 {
      background: transparent !important;
    }

    body.theme-admin-pro .side-bar .chiled .tw-absolute.tw-bg-gray-200 {
      background: rgba(255, 255, 255, 0.12) !important;
    }

    body.theme-admin-pro .side-bar .chiled a {
      color: rgba(255, 255, 255, 0.55) !important;
    }

    body.theme-admin-pro .side-bar .chiled a:hover {
      color: #fff !important;
    }

    body.theme-admin-pro .side-bar .chiled a.tw-text-primary-700 {
      color: #c4bfd4 !important;
    }

    body.theme-admin-pro .side-bar i {
      opacity: 0.9;
    }

    /* Top header: same purple as dashboard welcome banner */
    body.theme-admin-pro .admin-pro-header {
      background: var(--admin-pro-hero-gradient) !important;
      background-image: var(--admin-pro-hero-gradient) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
      box-shadow: var(--admin-pro-hero-shadow);
    }

    body.theme-admin-pro .admin-pro-header .tw-text-white,
    body.theme-admin-pro .admin-pro-header summary,
    body.theme-admin-pro .admin-pro-header a:not(.btn-danger),
    body.theme-admin-pro .admin-pro-header button {
      color: rgba(255, 255, 255, 0.9) !important;
    }

    body.theme-admin-pro .admin-pro-header svg {
      color: inherit !important;
      stroke: currentColor !important;
    }

    body.theme-admin-pro .admin-pro-header .tw-ring-white\/10,
    body.theme-admin-pro .admin-pro-header .hover\:tw-ring-white\/10 {
      --tw-ring-color: rgba(255, 255, 255, 0.12) !important;
    }

    /* Unified glass pill buttons */
    body.theme-admin-pro .admin-pro-header button,
    body.theme-admin-pro .admin-pro-header summary,
    body.theme-admin-pro .admin-pro-header a.load_notifications,
    body.theme-admin-pro .admin-pro-header a.dropdown-toggle,
    body.theme-admin-pro .admin-pro-header a.tw-inline-flex:not(.btn-danger),
    body.theme-admin-pro .admin-pro-header [class*="tw-bg-"][class*="800"],
    body.theme-admin-pro .admin-pro-header [class*="hover\:tw-bg-"][class*="700"] {
      background-color: rgba(255, 255, 255, 0.08) !important;
      background-image: none !important;
      border: 1px solid rgba(255, 255, 255, 0.1) !important;
      box-shadow: none !important;
      color: rgba(255, 255, 255, 0.9) !important;
    }

    body.theme-admin-pro .admin-pro-header button:hover,
    body.theme-admin-pro .admin-pro-header summary:hover,
    body.theme-admin-pro .admin-pro-header a.load_notifications:hover,
    body.theme-admin-pro .admin-pro-header a.dropdown-toggle:hover,
    body.theme-admin-pro .admin-pro-header a.tw-inline-flex:not(.btn-danger):hover,
    body.theme-admin-pro .admin-pro-header [class*="tw-bg-"][class*="800"]:hover,
    body.theme-admin-pro .admin-pro-header a[class*="hover\:tw-bg-"]:hover {
      background-color: rgba(255, 255, 255, 0.22) !important;
      border-color: rgba(255, 255, 255, 0.35) !important;
      color: #fff !important;
    }

    /* POS — white CTA on dark header */
    body.theme-admin-pro .admin-pro-header a[href*="pos/create"] {
      background: rgba(255, 255, 255, 0.95) !important;
      border-color: transparent !important;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12) !important;
      color: var(--admin-pro-primary) !important;
    }

    body.theme-admin-pro .admin-pro-header a[href*="pos/create"]:hover {
      background: #fff !important;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15) !important;
      color: var(--admin-pro-primary-light) !important;
    }

    body.theme-admin-pro .notifications-menu .notifications_count {
      background: var(--admin-pro-accent) !important;
      color: #fff !important;
      border-radius: 9999px;
      font-weight: 600;
      font-size: 11px;
      min-width: 1.25rem;
      border: 2px solid rgba(255, 255, 255, 0.35);
    }

    /* Cards: slightly softer elevation site-wide in theme */
    body.theme-admin-pro .tw-rounded-xl.tw-bg-white.tw-shadow-sm {
      border-radius: 14px !important;
      box-shadow: var(--admin-pro-card-shadow) !important;
    }

    body.theme-admin-pro table.dataTable thead th {
      background: #f3f4f6 !important;
    }

    body.theme-admin-pro .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--admin-pro-accent) !important;
      border-color: var(--admin-pro-accent) !important;
      color: #fff !important;
    }

    /* Align dashboard chart header accents with purple theme */
    body.theme-admin-pro #scrollable-container .tw-text-sky-500 {
      color: var(--admin-pro-accent) !important;
    }

    body.theme-admin-pro #scrollable-container .tw-bg-sky-100 {
      background: var(--admin-pro-accent-soft) !important;
    }

    body.theme-admin-pro.skin-blue-light-blackgold .side-bar.admin-pro-sidebar {
      background: var(--admin-pro-sidebar) !important;
    }

    body.theme-admin-pro.skin-blue-light-blackgold #scrollable-container {
      background: var(--admin-pro-bg) !important;
      color: #111827 !important;
    }
  </style>
  <style type="text/css">
    .swal-icon--error {
      border-color: #f27474;
      -webkit-animation: animateErrorIcon 0.5s;
      animation: animateErrorIcon 0.5s;
    }
    .swal-icon--error__x-mark {
      position: relative;
      display: block;
      -webkit-animation: animateXMark 0.5s;
      animation: animateXMark 0.5s;
    }
    .swal-icon--error__line {
      position: absolute;
      height: 5px;
      width: 47px;
      background-color: #f27474;
      display: block;
      top: 37px;
      border-radius: 2px;
    }
    .swal-icon--error__line--left {
      -webkit-transform: rotate(45deg);
      transform: rotate(45deg);
      left: 17px;
    }
    .swal-icon--error__line--right {
      -webkit-transform: rotate(-45deg);
      transform: rotate(-45deg);
      right: 16px;
    }
    @-webkit-keyframes animateErrorIcon {
      0% {
        -webkit-transform: rotateX(100deg);
        transform: rotateX(100deg);
        opacity: 0;
      }
      to {
        -webkit-transform: rotateX(0deg);
        transform: rotateX(0deg);
        opacity: 1;
      }
    }
    @keyframes animateErrorIcon {
      0% {
        -webkit-transform: rotateX(100deg);
        transform: rotateX(100deg);
        opacity: 0;
      }
      to {
        -webkit-transform: rotateX(0deg);
        transform: rotateX(0deg);
        opacity: 1;
      }
    }
    @-webkit-keyframes animateXMark {
      0% {
        -webkit-transform: scale(0.4);
        transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
      }
      50% {
        -webkit-transform: scale(0.4);
        transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
      }
      80% {
        -webkit-transform: scale(1.15);
        transform: scale(1.15);
        margin-top: -6px;
      }
      to {
        -webkit-transform: scale(1);
        transform: scale(1);
        margin-top: 0;
        opacity: 1;
      }
    }
    @keyframes animateXMark {
      0% {
        -webkit-transform: scale(0.4);
        transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
      }
      50% {
        -webkit-transform: scale(0.4);
        transform: scale(0.4);
        margin-top: 26px;
        opacity: 0;
      }
      80% {
        -webkit-transform: scale(1.15);
        transform: scale(1.15);
        margin-top: -6px;
      }
      to {
        -webkit-transform: scale(1);
        transform: scale(1);
        margin-top: 0;
        opacity: 1;
      }
    }
    .swal-icon--warning {
      border-color: #f8bb86;
      -webkit-animation: pulseWarning 0.75s infinite alternate;
      animation: pulseWarning 0.75s infinite alternate;
    }
    .swal-icon--warning__body {
      width: 5px;
      height: 47px;
      top: 10px;
      border-radius: 2px;
      margin-left: -2px;
    }
    .swal-icon--warning__body,
    .swal-icon--warning__dot {
      position: absolute;
      left: 50%;
      background-color: #f8bb86;
    }
    .swal-icon--warning__dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      margin-left: -4px;
      bottom: -11px;
    }
    @-webkit-keyframes pulseWarning {
      0% {
        border-color: #f8d486;
      }
      to {
        border-color: #f8bb86;
      }
    }
    @keyframes pulseWarning {
      0% {
        border-color: #f8d486;
      }
      to {
        border-color: #f8bb86;
      }
    }
    .swal-icon--success {
      border-color: #a5dc86;
    }
    .swal-icon--success:after,
    .swal-icon--success:before {
      content: "";
      border-radius: 50%;
      position: absolute;
      width: 60px;
      height: 120px;
      background: #fff;
      -webkit-transform: rotate(45deg);
      transform: rotate(45deg);
    }
    .swal-icon--success:before {
      border-radius: 120px 0 0 120px;
      top: -7px;
      left: -33px;
      -webkit-transform: rotate(-45deg);
      transform: rotate(-45deg);
      -webkit-transform-origin: 60px 60px;
      transform-origin: 60px 60px;
    }
    .swal-icon--success:after {
      border-radius: 0 120px 120px 0;
      top: -11px;
      left: 30px;
      -webkit-transform: rotate(-45deg);
      transform: rotate(-45deg);
      -webkit-transform-origin: 0 60px;
      transform-origin: 0 60px;
      -webkit-animation: rotatePlaceholder 4.25s ease-in;
      animation: rotatePlaceholder 4.25s ease-in;
    }
    .swal-icon--success__ring {
      width: 80px;
      height: 80px;
      border: 4px solid hsla(98, 55%, 69%, 0.2);
      border-radius: 50%;
      box-sizing: content-box;
      position: absolute;
      left: -4px;
      top: -4px;
      z-index: 2;
    }
    .swal-icon--success__hide-corners {
      width: 5px;
      height: 90px;
      background-color: #fff;
      padding: 1px;
      position: absolute;
      left: 28px;
      top: 8px;
      z-index: 1;
      -webkit-transform: rotate(-45deg);
      transform: rotate(-45deg);
    }
    .swal-icon--success__line {
      height: 5px;
      background-color: #a5dc86;
      display: block;
      border-radius: 2px;
      position: absolute;
      z-index: 2;
    }
    .swal-icon--success__line--tip {
      width: 25px;
      left: 14px;
      top: 46px;
      -webkit-transform: rotate(45deg);
      transform: rotate(45deg);
      -webkit-animation: animateSuccessTip 0.75s;
      animation: animateSuccessTip 0.75s;
    }
    .swal-icon--success__line--long {
      width: 47px;
      right: 8px;
      top: 38px;
      -webkit-transform: rotate(-45deg);
      transform: rotate(-45deg);
      -webkit-animation: animateSuccessLong 0.75s;
      animation: animateSuccessLong 0.75s;
    }
    @-webkit-keyframes rotatePlaceholder {
      0% {
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
      }
      5% {
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
      }
      12% {
        -webkit-transform: rotate(-405deg);
        transform: rotate(-405deg);
      }
      to {
        -webkit-transform: rotate(-405deg);
        transform: rotate(-405deg);
      }
    }
    @keyframes rotatePlaceholder {
      0% {
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
      }
      5% {
        -webkit-transform: rotate(-45deg);
        transform: rotate(-45deg);
      }
      12% {
        -webkit-transform: rotate(-405deg);
        transform: rotate(-405deg);
      }
      to {
        -webkit-transform: rotate(-405deg);
        transform: rotate(-405deg);
      }
    }
    @-webkit-keyframes animateSuccessTip {
      0% {
        width: 0;
        left: 1px;
        top: 19px;
      }
      54% {
        width: 0;
        left: 1px;
        top: 19px;
      }
      70% {
        width: 50px;
        left: -8px;
        top: 37px;
      }
      84% {
        width: 17px;
        left: 21px;
        top: 48px;
      }
      to {
        width: 25px;
        left: 14px;
        top: 45px;
      }
    }
    @keyframes animateSuccessTip {
      0% {
        width: 0;
        left: 1px;
        top: 19px;
      }
      54% {
        width: 0;
        left: 1px;
        top: 19px;
      }
      70% {
        width: 50px;
        left: -8px;
        top: 37px;
      }
      84% {
        width: 17px;
        left: 21px;
        top: 48px;
      }
      to {
        width: 25px;
        left: 14px;
        top: 45px;
      }
    }
    @-webkit-keyframes animateSuccessLong {
      0% {
        width: 0;
        right: 46px;
        top: 54px;
      }
      65% {
        width: 0;
        right: 46px;
        top: 54px;
      }
      84% {
        width: 55px;
        right: 0;
        top: 35px;
      }
      to {
        width: 47px;
        right: 8px;
        top: 38px;
      }
    }
    @keyframes animateSuccessLong {
      0% {
        width: 0;
        right: 46px;
        top: 54px;
      }
      65% {
        width: 0;
        right: 46px;
        top: 54px;
      }
      84% {
        width: 55px;
        right: 0;
        top: 35px;
      }
      to {
        width: 47px;
        right: 8px;
        top: 38px;
      }
    }
    .swal-icon--info {
      border-color: #c9dae1;
    }
    .swal-icon--info:before {
      width: 5px;
      height: 29px;
      bottom: 17px;
      border-radius: 2px;
      margin-left: -2px;
    }
    .swal-icon--info:after,
    .swal-icon--info:before {
      content: "";
      position: absolute;
      left: 50%;
      background-color: #c9dae1;
    }
    .swal-icon--info:after {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      margin-left: -3px;
      top: 19px;
    }
    .swal-icon {
      width: 80px;
      height: 80px;
      border-width: 4px;
      border-style: solid;
      border-radius: 50%;
      padding: 0;
      position: relative;
      box-sizing: content-box;
      margin: 20px auto;
    }
    .swal-icon:first-child {
      margin-top: 32px;
    }
    .swal-icon--custom {
      width: auto;
      height: auto;
      max-width: 100%;
      border: none;
      border-radius: 0;
    }
    .swal-icon img {
      max-width: 100%;
      max-height: 100%;
    }
    .swal-title {
      color: rgba(0, 0, 0, 0.65);
      font-weight: 600;
      text-transform: none;
      position: relative;
      display: block;
      padding: 13px 16px;
      font-size: 27px;
      line-height: normal;
      text-align: center;
      margin-bottom: 0;
    }
    .swal-title:first-child {
      margin-top: 26px;
    }
    .swal-title:not(:first-child) {
      padding-bottom: 0;
    }
    .swal-title:not(:last-child) {
      margin-bottom: 13px;
    }
    .swal-text {
      font-size: 16px;
      position: relative;
      float: none;
      line-height: normal;
      vertical-align: top;
      text-align: left;
      display: inline-block;
      margin: 0;
      padding: 0 10px;
      font-weight: 400;
      color: rgba(0, 0, 0, 0.64);
      max-width: calc(100% - 20px);
      overflow-wrap: break-word;
      box-sizing: border-box;
    }
    .swal-text:first-child {
      margin-top: 45px;
    }
    .swal-text:last-child {
      margin-bottom: 45px;
    }
    .swal-footer {
      text-align: right;
      padding-top: 13px;
      margin-top: 13px;
      padding: 13px 16px;
      border-radius: inherit;
      border-top-left-radius: 0;
      border-top-right-radius: 0;
    }
    .swal-button-container {
      margin: 5px;
      display: inline-block;
      position: relative;
    }
    .swal-button {
      background-color: #7cd1f9;
      color: #fff;
      border: none;
      box-shadow: none;
      border-radius: 5px;
      font-weight: 600;
      font-size: 14px;
      padding: 10px 24px;
      margin: 0;
      cursor: pointer;
    }
    .swal-button[not:disabled]:hover {
      background-color: #78cbf2;
    }
    .swal-button:active {
      background-color: #70bce0;
    }
    .swal-button:focus {
      outline: none;
      box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(43, 114, 165, 0.29);
    }
    .swal-button[disabled] {
      opacity: 0.5;
      cursor: default;
    }
    .swal-button::-moz-focus-inner {
      border: 0;
    }
    .swal-button--cancel {
      color: #555;
      background-color: #efefef;
    }
    .swal-button--cancel[not:disabled]:hover {
      background-color: #e8e8e8;
    }
    .swal-button--cancel:active {
      background-color: #d7d7d7;
    }
    .swal-button--cancel:focus {
      box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(116, 136, 150, 0.29);
    }
    .swal-button--danger {
      background-color: #e64942;
    }
    .swal-button--danger[not:disabled]:hover {
      background-color: #df4740;
    }
    .swal-button--danger:active {
      background-color: #cf423b;
    }
    .swal-button--danger:focus {
      box-shadow: 0 0 0 1px #fff, 0 0 0 3px rgba(165, 43, 43, 0.29);
    }
    .swal-content {
      padding: 0 20px;
      margin-top: 20px;
      font-size: medium;
    }
    .swal-content:last-child {
      margin-bottom: 20px;
    }
    .swal-content__input,
    .swal-content__textarea {
      -webkit-appearance: none;
      background-color: #fff;
      border: none;
      font-size: 14px;
      display: block;
      box-sizing: border-box;
      width: 100%;
      border: 1px solid rgba(0, 0, 0, 0.14);
      padding: 10px 13px;
      border-radius: 2px;
      transition: border-color 0.2s;
    }
    .swal-content__input:focus,
    .swal-content__textarea:focus {
      outline: none;
      border-color: #6db8ff;
    }
    .swal-content__textarea {
      resize: vertical;
    }
    .swal-button--loading {
      color: transparent;
    }
    .swal-button--loading ~ .swal-button__loader {
      opacity: 1;
    }
    .swal-button__loader {
      position: absolute;
      height: auto;
      width: 43px;
      z-index: 2;
      left: 50%;
      top: 50%;
      -webkit-transform: translateX(-50%) translateY(-50%);
      transform: translateX(-50%) translateY(-50%);
      text-align: center;
      pointer-events: none;
      opacity: 0;
    }
    .swal-button__loader div {
      display: inline-block;
      float: none;
      vertical-align: baseline;
      width: 9px;
      height: 9px;
      padding: 0;
      border: none;
      margin: 2px;
      opacity: 0.4;
      border-radius: 7px;
      background-color: hsla(0, 0%, 100%, 0.9);
      transition: background 0.2s;
      -webkit-animation: swal-loading-anim 1s infinite;
      animation: swal-loading-anim 1s infinite;
    }
    .swal-button__loader div:nth-child(3n + 2) {
      -webkit-animation-delay: 0.15s;
      animation-delay: 0.15s;
    }
    .swal-button__loader div:nth-child(3n + 3) {
      -webkit-animation-delay: 0.3s;
      animation-delay: 0.3s;
    }
    @-webkit-keyframes swal-loading-anim {
      0% {
        opacity: 0.4;
      }
      20% {
        opacity: 0.4;
      }
      50% {
        opacity: 1;
      }
      to {
        opacity: 0.4;
      }
    }
    @keyframes swal-loading-anim {
      0% {
        opacity: 0.4;
      }
      20% {
        opacity: 0.4;
      }
      50% {
        opacity: 1;
      }
      to {
        opacity: 0.4;
      }
    }
    .swal-overlay {
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 0;
      overflow-y: auto;
      background-color: rgba(0, 0, 0, 0.4);
      z-index: 10000;
      pointer-events: none;
      opacity: 0;
      transition: opacity 0.3s;
    }
    .swal-overlay:before {
      content: " ";
      display: inline-block;
      vertical-align: middle;
      height: 100%;
    }
    .swal-overlay--show-modal {
      opacity: 1;
      pointer-events: auto;
    }
    .swal-overlay--show-modal .swal-modal {
      opacity: 1;
      pointer-events: auto;
      box-sizing: border-box;
      -webkit-animation: showSweetAlert 0.3s;
      animation: showSweetAlert 0.3s;
      will-change: transform;
    }
    .swal-modal {
      width: 478px;
      opacity: 0;
      pointer-events: none;
      background-color: #fff;
      text-align: center;
      border-radius: 5px;
      position: static;
      margin: 20px auto;
      display: inline-block;
      vertical-align: middle;
      -webkit-transform: scale(1);
      transform: scale(1);
      -webkit-transform-origin: 50% 50%;
      transform-origin: 50% 50%;
      z-index: 10001;
      transition: opacity 0.2s, -webkit-transform 0.3s;
      transition: transform 0.3s, opacity 0.2s;
      transition: transform 0.3s, opacity 0.2s, -webkit-transform 0.3s;
    }
    @media (max-width: 500px) {
      .swal-modal {
        width: calc(100% - 20px);
      }
    }
    @-webkit-keyframes showSweetAlert {
      0% {
        -webkit-transform: scale(1);
        transform: scale(1);
      }
      1% {
        -webkit-transform: scale(0.5);
        transform: scale(0.5);
      }
      45% {
        -webkit-transform: scale(1.05);
        transform: scale(1.05);
      }
      80% {
        -webkit-transform: scale(0.95);
        transform: scale(0.95);
      }
      to {
        -webkit-transform: scale(1);
        transform: scale(1);
      }
    }
    @keyframes showSweetAlert {
      0% {
        -webkit-transform: scale(1);
        transform: scale(1);
      }
      1% {
        -webkit-transform: scale(0.5);
        transform: scale(0.5);
      }
      45% {
        -webkit-transform: scale(1.05);
        transform: scale(1.05);
      }
      80% {
        -webkit-transform: scale(0.95);
        transform: scale(0.95);
      }
      to {
        -webkit-transform: scale(1);
        transform: scale(1);
      }
    }
  </style>
  <style>
    .action-link[data-v-1552a5b6] {
      cursor: pointer;
    }
  </style>
  <style>
    .action-link[data-v-397d14ca] {
      cursor: pointer;
    }
  </style>
  <style>
    .action-link[data-v-49962cc0] {
      cursor: pointer;
    }
  </style>

  <!-- Modern DataTables styling (visual only) -->
  <style>
    table.dataTable {
      border-collapse: separate !important;
      border-spacing: 0 !important;
      width: 100% !important;
      /* overflow:hidden removed - it clips action dropdowns */
    }
    /* Border-radius applied to wrapper instead of table to avoid clipping dropdowns */
    .dataTables_wrapper {
      border-radius: 14px;
      overflow: visible !important;
    }

    table.dataTable thead th {
      background: #f3f4f6 !important;
      color: #111827 !important;
      font-weight: 600 !important;
      border-bottom: 1px solid #e5e7eb !important;
      padding: 12px 10px !important;
      white-space: nowrap !important;
    }

    table.dataTable tbody td {
      color: #111827 !important;
      padding: 12px 10px !important;
      vertical-align: middle !important;
    }

    table.dataTable tbody tr:hover td {
      background: #fafafa !important;
    }

    /* Pagination */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      border-radius: 10px !important;
      border: 1px solid #e5e7eb !important;
      padding: 6px 10px !important;
      margin-left: 6px !important;
      background: #ffffff !important;
      color: #111827 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: #2563eb !important;
      border-color: #2563eb !important;
      color: #ffffff !important;
    }

    /* Search input */
    .dataTables_wrapper .dataTables_filter input {
      border-radius: 12px !important;
      border: 1px solid #e5e7eb !important;
      padding: 8px 12px !important;
    }

    /* Sell floating dropdown rendered to body */
    .sell-floating-dropdown {
      min-width: 240px !important;
      max-height: 60vh;
      overflow-y: auto;
      overflow-x: hidden;
    }
    .table-floating-dropdown {
      min-width: 240px !important;
      max-height: 60vh;
      overflow-y: auto;
      overflow-x: hidden;
    }

    /*
     * Force visible native checkboxes (iCheck skins break under Tailwind/Admin Pro)
     */
    input[type="checkbox"].input-icheck,
    input[type="radio"].input-icheck {
      position: static !important;
      opacity: 1 !important;
      visibility: visible !important;
      display: inline-block !important;
      width: 18px !important;
      height: 18px !important;
      min-width: 18px !important;
      min-height: 18px !important;
      margin: 0 8px 0 0 !important;
      vertical-align: middle !important;
      -webkit-appearance: checkbox !important;
      -moz-appearance: checkbox !important;
      appearance: auto !important;
      accent-color: #3c8dbc !important;
      cursor: pointer !important;
      flex-shrink: 0 !important;
      pointer-events: auto !important;
      z-index: 2 !important;
    }
    .checkbox label,
    .radio label {
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      padding-left: 0 !important;
      cursor: pointer !important;
      min-height: 22px !important;
    }
    .icheckbox_square-blue,
    .iradio_square-blue {
      display: inline-flex !important;
      align-items: center !important;
      width: auto !important;
      height: auto !important;
      min-width: 0 !important;
      min-height: 0 !important;
      margin: 0 8px 0 0 !important;
      padding: 0 !important;
      background: none !important;
      border: none !important;
      opacity: 1 !important;
      visibility: visible !important;
      vertical-align: middle !important;
    }
    .icheckbox_square-blue > input,
    .iradio_square-blue > input {
      position: static !important;
      opacity: 1 !important;
      width: 18px !important;
      height: 18px !important;
      margin: 0 !important;
      -webkit-appearance: checkbox !important;
      appearance: auto !important;
    }
    .icheckbox_square-blue > ins,
    .iradio_square-blue > ins {
      display: none !important;
    }
  </style>