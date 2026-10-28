@php($g=$galleryGroups[$galleryKey])
<div class="ra-project__preview" data-home-gallery="{{ $galleryKey }}" data-gallery-mode="{{ $g['mode'] }}" data-gallery-version="{{ $g['version'] }}" data-gallery-feed="{{ route('home-gallery.feed') }}" data-gallery-fallback="{{ asset('images/'.config('home_gallery.groups.'.$galleryKey.'.fallback.0')) }}" data-gallery-orientation="{{ $g['orientation'] }}">
 @foreach($g['frames'] as $frame)<button class="ra-project__shot" type="button" data-photo-token="{{ $frame['token'] }}" aria-label="{{ $frame['alt'] }}" aria-pressed="false"><img src="{{ $frame['url'] }}" width="900" height="675" alt="{{ $frame['alt'] }}" loading="lazy" decoding="async"></button>@endforeach
 <div class="home-gallery-controls"><button type="button" class="home-gallery-pause" data-gallery-pause aria-pressed="false" @if($g['mode']!=='rotate') hidden @endif>{{ __('Jeda pergantian') }}</button></div>
</div>
