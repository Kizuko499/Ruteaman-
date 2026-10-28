@extends('admin.layout')
@section('title',__('Undangan dibuat'))
@section('content')
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('AKUN SIAP DIUNDANG') }}</p><h1>{{ __('Undangan untuk') }} {{ $target->display_name }}</h1><p>{{ $target->role->label() }} · {{ $target->school?->name??'Platform' }}</p></div><section class="admin-panel admin-narrow"><p class="admin-notice">{{ __('Tautan ini hanya ditampilkan sekali. Jangan membagikannya di ruang publik.') }}</p><label>{{ __('Tautan setel sandi') }}<input type="text" readonly value="{{ $url }}" id="admin-once-link" autocomplete="off"></label><div class="admin-actions"><button class="admin-primary" type="button" data-copy-invite>{{ __('Salin tautan') }}</button><a class="admin-secondary" href="{{ route('admin.admins') }}">{{ __('Ke daftar akun') }}</a></div><p role="status" data-copy-status></p><p>{{ $mail_sent?__('Email diserahkan ke server email kontak.'):__('Email belum dikirim. Konfigurasikan SMTP; mailer log/array sengaja tidak menulis token ke log. Bagikan tautan secara privat.') }}</p><small>{{ __('Berlaku') }} {{ config('ruteaman_admin.invite_hours') }} {{ __('jam dan hanya bisa digunakan sekali. Membuat tautan baru membatalkan tautan sebelumnya.') }}</small></section>
@endsection
