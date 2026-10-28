<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Services\LanguagePreferences::direction() }}" data-theme="light">
<head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
 <meta name="theme-color" content="#1F6E70"><link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml"><title>@yield('title','RuteAman') {{ __('— Papan kondisi rute sekolah') }}</title>
 @hasSection('metadata') @yield('metadata') @else <meta name="description" content="Lihat dan laporkan kondisi rute sekolah berdasarkan informasi komunitas."> @endif
 <script>window.ruteamanAppearance=@json(auth()->user()?->appearance ?? null);try{const value=window.ruteamanAppearance||localStorage.getItem('ruteaman.appearance')||'system';document.documentElement.dataset.appearance=value;document.documentElement.dataset.theme='light';}catch{document.documentElement.dataset.theme='light';}</script>
 @include('partials.language-head')
 <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/inter-latin-400.woff2') }}" crossorigin>
 <link rel="preload" as="font" type="font/woff2" href="{{ asset('fonts/poppins-latin-700.woff2') }}" crossorigin>
 <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
@vite(['resources/css/app.css','resources/js/app.js'])
 <style data-ruteaman-dock="v1">
 /* Layar penuh di HP: zona cadangan 88px di bawah area konten dihapus di semua
    halaman — konten mengalir sampai tepi bawah layar dan navbar bawah melayang
    di atasnya. Ruang lega dari navbar dipindah ke ujung gulir area konten.
    Bayangan navbar ditegaskan agar terangkat dari konten di belakangnya. */
 @media (max-width:640px){
 body{padding-bottom:0}
 body.account-layout #main{height:100dvh;padding-bottom:calc(var(--mobile-nav-clearance) + env(safe-area-inset-bottom))}
 body.account-layout #main:has(> .map-page),body.account-layout #main:has(> .graph-map-page),body.account-layout #main:has(> .auth-page){padding-bottom:0}
 .map-page,.graph-map-page{min-height:100svh}
 .mobile-tabs{box-shadow:0 1px 2px rgba(18,56,58,.08),0 8px 16px rgba(18,56,58,.12),0 24px 44px rgba(18,56,58,.22)}
 }
 </style>
 @stack('styles')
</head>
<body class="{{ request()->routeIs('home')?'publication-home':'account-layout' }}">
 <a class="skip" href="#main">{{ __('Lewati ke konten') }}</a>
 @if(session('success'))<div class="toast" role="status">{{ __(session('success')) }}</div>@endif
 <main id="main">@yield('content')</main>
 @include('partials.mobile-nav')
 <x-account-menu/>
 
 <script type="application/json" id="condition-type-data">{!! json_encode(app(\App\Services\ConditionIconService::class)->catalog(), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
 @stack('scripts')
</body>
</html>