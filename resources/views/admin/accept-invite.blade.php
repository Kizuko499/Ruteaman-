@extends('admin.layout')
@section('title',__('Setel kata sandi'))
@section('content')
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('UNDANGAN RUTEAMAN') }}</p><h1>{{ __('Setel kata sandi admin') }}</h1></div>@if($valid)<form method="post" action="{{ route('admin.invite.accept',['token'=>$token]) }}" class="admin-panel admin-narrow">@csrf<input type="hidden" name="email" value="{{ $email }}"><p>{{ __('Alias login:') }} <strong>{{ $email }}</strong></p><label>{{ __('Kata sandi baru') }}<input type="password" name="password" required minlength="12" maxlength="128" autocomplete="new-password"></label><label>{{ __('Ulangi kata sandi') }}<input type="password" name="password_confirmation" required minlength="12" maxlength="128" autocomplete="new-password"></label><p>{{ __('Minimal 12 karakter. Gunakan kata sandi unik.') }}</p><button class="admin-primary">{{ __('Setel kata sandi dan masuk') }}</button></form>@else<section class="admin-panel"><h2>{{ __('Tautan tidak berlaku') }}</h2><p>{{ __('Tautan kedaluwarsa atau sudah digunakan. Minta pembuat atau atasan menerbitkan undangan baru.') }}</p><a class="admin-secondary" href="{{ route('login') }}">{{ __('Kembali ke masuk') }}</a></section>@endif
@endsection
