@extends('emails.layout')
@section('content')
<p>{{ __('Status laporanmu berubah.') }}</p>
<p><strong>{{ $typeLabel }}</strong><br>{{ $statusLabel }}</p>
@if($note)<p style="background:#f4f7f7;border-radius:10px;padding:12px;">{{ $note }}</p>@endif
<p><a href="{{ $hazardUrl }}" style="display:inline-block;background:#1F6E70;color:#ffffff;padding:11px 18px;border-radius:10px;text-decoration:none;">{{ __('Lihat laporan') }}</a></p>
@endsection
