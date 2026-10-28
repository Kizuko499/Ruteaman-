@extends('emails.layout')
@section('content')
<p>{{ __('Halo') }} {{ $recipientName }},</p>
<p>{{ __('Berikut ringkasan aktivitas RuteAman untuk periode') }} <strong>{{ $periodLabel }}</strong>:</p>
@foreach($sections as $section)
<p style="margin:18px 0 6px;font-weight:bold;color:#1F6E70;">{{ $section['title'] }}</p>
<ul style="margin:0;padding-left:18px;">
@foreach($section['lines'] as $line)
<li style="margin:3px 0;">{{ $line }}</li>
@endforeach
</ul>
@endforeach
<p style="margin-top:20px;"><a href="{{ $homeUrl }}" style="display:inline-block;background:#1F6E70;color:#ffffff;padding:11px 18px;border-radius:10px;text-decoration:none;">{{ __('Buka RuteAman') }}</a></p>
<p style="font-size:13px;color:#49696b;">{{ __('Ringkasan ini hanya berisi angka dan informasi yang memang menjadi cakupanmu. Pengaturan penerima dikelola oleh administrator RuteAman.') }}</p>
@endsection
