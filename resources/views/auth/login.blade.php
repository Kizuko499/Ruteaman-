@extends('layouts.app')
@section('title',__('Masuk'))
@section('content')
@php($loginPhoto=app(\App\Services\SitePresentationService::class)->loginPhoto())
<div class="auth-page">
<style>
/* Polesan modern minimalis halaman masuk — hanya tampilan */
.auth-page .auth-card{background:transparent;border:0;border-radius:0;box-shadow:none;max-width:520px;padding:48px 24px;gap:13px;align-self:center;margin-inline:auto}
.auth-page .auth-card h1{font-family:Poppins,Inter,sans-serif;font-size:2rem;font-weight:700;letter-spacing:-.01em;color:#14343A;margin:16px 0 0}
.auth-page .auth-card>p{color:#5F7476;font-size:.93rem;margin:0;text-align:left}
.auth-page .auth-card label{font-size:.84rem;font-weight:600;color:#34565A}
.auth-page .auth-card input:not([type=checkbox]){width:100%;box-sizing:border-box;height:52px;border:1px solid #DFE9E4;border-radius:14px;padding:0 14px;font-size:.95rem;font-family:Inter,sans-serif;color:#14343A;background:#fff;margin-top:7px;transition:border-color .15s,box-shadow .15s}
.auth-page .auth-card input:not([type=checkbox]):focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.auth-page .auth-card .check{display:flex;align-items:center;gap:9px;font-size:.9rem;font-weight:500;color:#49696B}
.auth-page .auth-card .check input{width:19px;height:19px;margin:0;accent-color:#1F6E70;flex:none}
.auth-page .auth-card .btn-dark{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;border-radius:15px;color:#fff;font-family:Poppins,Inter,sans-serif;font-weight:600;font-size:1rem;padding:15px;width:100%;box-shadow:0 12px 24px rgba(31,110,112,.22);cursor:pointer;margin-top:4px}
.auth-page .auth-card>a:not(.brand){display:block;text-align:right;font-size:.93rem;font-weight:600;color:var(--teal-dark,#1F6E70);text-decoration:none;margin-top:-2px}
.auth-page .auth-card p{font-size:.93rem;color:#5F7476;margin:0}
.auth-page .auth-card p a{color:var(--teal-dark,#1F6E70);font-weight:600;text-decoration:none}
.auth-page .auth-card p:nth-last-child(2){text-align:center}
.auth-page .auth-card p:last-child{border-top:1px solid #EEF4F0;padding-top:14px;text-align:center}
.auth-page .auth-card .error{background:#FDECEA;color:#8C3A1B;border-radius:12px;padding:12px 14px;font-size:.9rem}
.auth-page .auth-card .notice{background:#EFF7F3;color:#1F6E70;border-radius:12px;padding:12px 14px;font-size:.9rem}
.auth-page .auth-card .cf-turnstile{margin:2px 0}
/* Halaman login memakai kerangka aplikasi penuh: navbar bawah tampil
   (bisa disembunyikan pengguna) dan ruang lega bawah dipulihkan. */
@media (max-width:640px){
 body.account-layout #main:has(>.auth-page){padding-bottom:calc(var(--mobile-nav-clearance) + env(safe-area-inset-bottom))}
}
.auth-page{min-height:100vh;min-height:100dvh}
@media(max-width:900px){
 /* HP: tetap versi kartu seperti sebelumnya (satu kolom hanya untuk desktop) */
 .auth-page .auth-card{background:#fff;border:1px solid #E3EFE9;border-radius:22px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 6px 14px rgba(18,56,58,.06),0 24px 48px rgba(18,56,58,.12);margin:20px 14px;padding:28px 20px;max-width:none;width:auto}
 .auth-page .auth-card h1{font-size:1.65rem}
 .auth-page .auth-card>a:not(.brand){text-align:right;font-size:.88rem;font-weight:600;color:var(--teal-dark,#1F6E70)}
 .auth-page .auth-card p{font-size:.9rem}
 .auth-page .auth-card p:nth-last-child(2){text-align:center}
 .auth-page .auth-card p:last-child{border-top:1px solid #EEF4F0;padding-top:14px;text-align:center}
 .auth-page .auth-card input:not([type=checkbox]){font-size:16px}
}
@media (max-width:640px){
 .auth-page{min-height:calc(100dvh - var(--mobile-nav-clearance) - env(safe-area-inset-bottom))}
}
</style>
<div class="auth-photo"><img src="{{ $loginPhoto['url'] }}" alt="{{ $loginPhoto['alt'] }}"><div><p class="eyebrow">{{ __('Privasi sejak awal') }}</p><h2>{{ __('Masuk untuk konfirmasi dan riwayat akun.') }}</h2><p>{{ __('Peta dan formulir laporan tamu tersedia tanpa akun.') }}</p></div></div><form class="auth-card" data-auth-submit method="post" action="{{ route('login') }}">@csrf<a class="brand" href="{{ route('home') }}"><span class="brand-mark">R</span><b>Rute<span>Aman</span></b></a>@if(request()->boolean('session_expired'))<div class="notice" role="status">{{ __('Sesi berakhir. Masuk kembali untuk melanjutkan.') }}</div>@endif<h1>{{ __('Selamat datang') }}</h1><p>{{ __('Gunakan akun sekolahmu.') }}</p>@if($errors->any())<div class="error">{{ __($errors->first()) }}</div>@endif<label>{{ __('Email') }}<input type="email" name="email" value="{{ old('email') }}" required autofocus></label><label>{{ __('Kata sandi') }}<input type="password" name="password" required></label><label class="check"><input type="checkbox" name="remember"> {{ __('Ingat saya') }}</label>@include('partials.turnstile')
<button class="btn btn-dark">{{ __('Masuk') }}</button><a href="{{ route('password.request') }}">{{ __('Lupa kata sandi?') }}</a><p><a href="{{ route('guest-reports.create') }}">{{ __('Lapor tanpa login') }}</a></p><p>{{ __('Belum punya akun?') }} <a href="{{ route('register') }}">{{ __('Daftar') }}</a></p></form></div>
@endsection