@extends('emails.layout')
@section('content')
<p>{{ __('Halo') }} {{ $recipientName }},</p>
<p>{{ __('Alamat email ini didaftarkan sebagai penerima notifikasi RuteAman oleh administrator. Klik tombol berikut untuk memverifikasi alamat ini. Tautan berlaku 72 jam dan hanya dapat dipakai untuk alamat ini.') }}</p>
<p><a href="{{ $verifyUrl }}" style="display:inline-block;background:#1F6E70;color:#ffffff;padding:11px 18px;border-radius:10px;text-decoration:none;">{{ __('Verifikasi alamat email') }}</a></p>
<p style="font-size:13px;color:#49696b;">{{ __('Selama belum terverifikasi, alamat ini tidak menerima notifikasi maupun ringkasan apa pun. Abaikan email ini bila kamu tidak merasa didaftarkan; alamatmu tidak akan menerima kiriman lanjutan.') }}</p>
@endsection
