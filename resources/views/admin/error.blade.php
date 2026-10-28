@extends('admin.layout')
@section('title',__('Tindakan belum dapat dilanjutkan'))
@section('content')
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('PERMINTAAN DITOLAK') }}</p><h1>{{ $code }}</h1><p>{{ $message }}</p></div><div class="admin-actions">@if($code==='REAUTH_REQUIRED')<a class="admin-primary" href="{{ route('admin.reauth') }}">{{ __('Konfirmasi kata sandi') }}</a>@endif<a class="admin-secondary" href="{{ auth()->user()?->role->admin()?route('admin.index'):route('home') }}">{{ __('Kembali') }}</a></div>
@endsection
