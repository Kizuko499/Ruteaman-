@props(['item','featured'=>false,'heading'=>'h3'])
<article class="condition-card {{ $featured?'condition-card--featured':'' }}">
 <a href="{{ $item['detail_url'] }}" aria-label="Lihat kondisi: {{ $item['title'] }}">
  <div class="condition-photo"><span class="category-symbol"><x-condition-symbol :type="$item['type']" :visual="$item['type_visual'] ?? null"/></span>@if($featured)<span class="condition-badge">{{ $item['type_label'] }}</span>@endif
  @if($item['photo_url'])<picture><source type="image/webp" srcset="{{ $item['photo_url'] }}"><img src="{{ $item['photo_fallback_url']??$item['photo_url'] }}" width="640" height="400" alt="{{ $item['alt'] }}" loading="{{ $featured?'eager':'lazy' }}" @if($featured) fetchpriority="high" @endif></picture>@endif</div>
  <div class="condition-card-body">@if($item['is_demo'])<span class="data-demo data-demo--icon" role="img" aria-label="{{ __('Data demo') }}" title="{{ __('Data demo — bukan laporan nyata') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2v6L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 8V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/></svg></span>@endif
   <p class="condition-kind">{{ $item['type_label'] }}</p><{{ $heading }}>{{ $item['title'] }}</{{ $heading }}><x-status-chip :label="$item['status_label']"/>
   <p class="condition-age">{{ $item['age_text'] }} · {{ $item['confirmations_count'] }} {{ __('konfirmasi') }}</p>
   @if($featured)<span class="condition-cta">{{ __('Lihat kondisi') }}</span>@endif
  </div>
 </a>
</article>
