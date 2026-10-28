@extends('layouts.app')
@section('title',__('Lupa kata sandi'))
@section('content')
<form class="auth-card standalone" method="post" action="{{ route('password.email') }}">@csrf<a class="brand" href="{{ route('home') }}"><span class="brand-mark">R</span><b>Rute<span>Aman</span></b></a><h1>{{ __('Atur ulang kata sandi') }}</h1><p>{{ __('Masukkan email akun sekolahmu.') }}</p>@if(session('status'))<div class="notice">{{ __(session('status')) }}</div>@endif<label>{{ __('Email') }}<input type="email" name="email" required autofocus></label><button class="btn btn-dark">{{ __('Kirim tautan reset') }}</button></form>
@endsection