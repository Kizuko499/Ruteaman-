@extends($user->role->admin()?'admin.layout':'layouts.app')
@section('content')
@php($adminAccount=$user->role->admin())
<div class="account-page {{ $adminAccount?'admin-account-page':'' }}"><header class="account-page-head"><a class="back" href="{{ $adminAccount?route('admin.index'):route('map') }}">{{ $adminAccount?__('← Kembali ke ringkasan konsol'):__('← Kembali ke peta') }}</a><p class="eyebrow">{{ $adminAccount?__('Konsol · Akun administrator'):__('RuteAman · Akun') }}</p><h1>@yield('account-heading')</h1><p>@yield('account-description')</p></header>
<nav class="account-page-nav" aria-label="{{ __('Halaman akun') }}">@foreach(['profile'=>__('Profil'),'settings'=>__('Pengaturan'),'notifications'=>__('Notifikasi'),'security'=>__('Keamanan'),'privacy'=>__('Privasi')] as $key=>$name)<a href="{{ route(($adminAccount?'admin.account.':'account.').$key) }}" @if(request()->routeIs('account.'.$key,'admin.account.'.$key)) aria-current="page" @endif>{{ $name }}</a>@endforeach</nav>
@if(!$adminAccount)
@if(session('success'))<p class="account-banner" role="status">{{ __(session('success')) }}</p>@endif
@if($errors->any())<p class="error" role="alert">{{ __($errors->first()) }}</p>@endif
@endif
@yield('account-content')
</div>
@if($adminAccount)<div class="account-feedback" id="account-feedback" role="status" aria-live="polite" hidden></div>@endif
@endsection
