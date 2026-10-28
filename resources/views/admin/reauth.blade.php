@extends('admin.layout')
@section('title',__('Konfirmasi kata sandi'))
@section('content')
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('KONFIRMASI TINDAKAN') }}</p><h1>{{ __('Konfirmasi kata sandi') }}</h1><p>{{ __('Konfirmasi berlaku selama 10 menit. Kembali ke formulir dan kirim tindakan setelah konfirmasi.') }}</p></div><form class="admin-panel admin-narrow" method="post" action="{{ route('admin.reauth') }}">@csrf<label>{{ __('Kata sandi') }}<input type="password" name="password" required autocomplete="current-password"></label><button class="admin-primary">{{ __('Konfirmasi') }}</button></form>
@endsection
