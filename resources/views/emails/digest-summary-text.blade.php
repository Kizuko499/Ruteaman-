{{ __('Halo') }} {{ $recipientName }},

{{ __('Ringkasan aktivitas RuteAman untuk periode') }} {{ $periodLabel }}:

@foreach($sections as $section)
{{ $section['title'] }}
@foreach($section['lines'] as $line)
- {{ $line }}
@endforeach

@endforeach
{{ __('Buka RuteAman:') }} {{ $homeUrl }}
