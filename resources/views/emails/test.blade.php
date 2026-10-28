@extends('emails.layout')
@section('content')
<p>{{ __('Ini email uji dari panel admin RuteAman.') }}</p>
<p>{{ __('Dikirim oleh') }}: {{ $senderName }}<br>{{ __('Waktu') }}: {{ $sentAt }}</p>
<p>{{ __('Jika kamu menerima email ini, konfigurasi pengiriman RuteAman bekerja. Tidak perlu membalas email ini.') }}</p>
@endsection
