@extends('layouts.app')
@section('title',__('Kata sandi baru'))
@section('content')
<form class="auth-card standalone" method="post" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><h1>{{ __('Kata sandi baru') }}</h1><label>{{ __('Email') }}<input type="email" name="email" value="{{ $email }}" required></label><label>{{ __('Kata sandi') }}<input type="password" name="password" required></label><label>{{ __('Ulangi kata sandi') }}<input type="password" name="password_confirmation" required></label><button class="btn btn-dark">{{ __('Simpan') }}</button></form>
@endsection