@extends('emails.layout')
@section('content')
<p>{{ __('Ada komentar baru pada laporan yang kamu ikuti.') }}</p>
<p><strong>{{ $typeLabel }}</strong><br>{{ $commenterName }}:</p>
<p style="background:#f4f7f7;border-radius:10px;padding:12px;">{{ $excerpt }}</p>
<p><a href="{{ $hazardUrl }}" style="display:inline-block;background:#1F6E70;color:#ffffff;padding:11px 18px;border-radius:10px;text-decoration:none;">{{ __('Buka laporan') }}</a></p>
@endsection
