@extends('emails.layout')
@section('content')
<p>{{ __('Ada laporan kondisi jalan baru yang menunggu verifikasi di sekolahmu.') }}</p>
<p><strong>{{ $typeLabel }}</strong><br>{{ $schoolName }}@if($locationLabel)<br>{{ $locationLabel }}@endif</p>
<p><a href="{{ $queueUrl }}" style="display:inline-block;background:#1F6E70;color:#ffffff;padding:11px 18px;border-radius:10px;text-decoration:none;">{{ __('Buka antrean verifikasi') }}</a></p>
<p style="font-size:13px;color:#49696b;">{{ __('Kamu menerima email ini karena akunmu berwenang memverifikasi laporan di sekolah tersebut. Buka antrean setelah masuk ke akunmu.') }}</p>
@endsection
