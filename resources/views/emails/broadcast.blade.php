@extends('emails.layout')
@section('content')
{!! nl2br(e($bodyText)) !!}
<p style="margin-top:24px;color:#5b6b66;font-size:13px">{{ __('Email informasi dari admin RuteAman, dikirim oleh') }} {{ $senderName }}. {{ __('Tidak perlu membalas email ini.') }}</p>
@endsection
