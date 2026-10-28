@push('styles')
<link rel="stylesheet" href="{{ asset('css/home-area-summary-v1.css') }}?v=summary-ui-1">
@endpush
<style>
/* CSS ringkasan area beranda (ditanam langsung; rujukan berkas lewat tumpukan gaya tidak tersaji di server) */
/* Scoped home summary: preserve data attributes, routes and theme elsewhere. */
.featured-columns{align-items:start}
.featured-summary.ra-area-summary{padding:0;border:1px solid #c9ded7;border-radius:16px;background:#fffef9;color:#12383a;box-shadow:0 4px 18px rgba(18,56,58,.045);min-width:0;overflow:hidden;text-align:start}
.ra-area-summary>summary{display:flex;align-items:center;gap:12px;list-style:none;margin:0;padding:24px;font-size:21px;line-height:1.35;font-weight:700;cursor:pointer;min-height:80px}
.ra-area-summary>summary::-webkit-details-marker{display:none}
.ra-area-summary>summary>span:nth-child(2){flex:1;min-width:0;overflow-wrap:anywhere}
.ra-area-summary__mark{display:grid;place-items:center;width:40px;height:40px;flex:none;border:1px solid #c7dcd6;border-radius:10px;background:#edf6f2;color:#1f6e70}
.ra-area-summary__mark svg{width:24px;height:24px}
.ra-area-summary__chevron{width:9px;height:9px;flex:none;margin-inline:2px 4px;border-right:2px solid #527775;border-bottom:2px solid #527775;transform:rotate(45deg)}
.ra-area-summary[open] .ra-area-summary__chevron{transform:rotate(225deg)}
.ra-area-summary:not([open])>summary{margin:0}
.ra-area-summary__body{display:grid;gap:20px;padding:0 24px 24px;min-width:0}
.ra-area-summary__form{display:grid!important;gap:8px!important;margin:0!important;min-width:0}
.ra-area-summary__form label{font-size:14px;line-height:1.5;font-weight:700;margin:0;display:block}
.ra-area-summary__picker{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;min-width:0}
.ra-area-summary__picker select{width:100%;max-width:100%;min-width:0;min-height:52px;height:52px;line-height:1.5;padding:12px 32px 12px 12px;background:#fff;border:1px solid #6c928b;border-radius:10px;font:inherit;font-size:16px;color:#12383a;text-overflow:ellipsis}
.ra-area-summary__submit{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:52px;height:52px;line-height:1.5;padding:12px 16px;border:1px solid #1f6e70;border-radius:10px;background:#1f6e70;color:#fff;font:inherit;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap}
.ra-area-summary__submit svg{width:18px;height:18px;flex:none}
.ra-area-summary__submit:hover{background:#18595b;border-color:#18595b}
.ra-area-summary__catalog{display:grid;grid-template-columns:24px minmax(0,1fr) 18px;align-items:center;gap:12px;min-height:52px;padding:12px 14px;background:#f6f8f3;border:1px solid #d5e1d9;border-radius:10px;color:#1b5e60;text-decoration:none;font-size:14px;font-weight:600;line-height:1.5;overflow-wrap:anywhere}
.ra-area-summary__catalog svg{display:block;width:24px;height:24px}
.ra-area-summary__catalog .ra-area-summary__arrow{width:18px;height:18px}
.ra-area-summary__catalog:hover{background:#edf4ed;border-color:#9dbfb1}
.ra-area-summary__stats{display:grid;gap:12px;padding:20px;background:#edf6f2;border:1px solid #d0e3da;border-radius:12px;min-width:0}
.ra-area-summary .featured-total{display:flex;align-items:center;gap:16px;margin:0;min-width:0;line-height:1.5}
.ra-area-summary .featured-total strong{font-size:56px;line-height:1;font-weight:700;letter-spacing:-.04em;min-width:0;flex:none;white-space:nowrap;overflow-wrap:normal;font-variant-numeric:tabular-nums;color:#123f40}
.ra-area-summary .featured-total span{max-width:220px;min-width:0;flex:1;font-size:16px;line-height:1.5;color:#244d4d;overflow-wrap:anywhere}
.ra-area-summary .condition-chips{display:flex;flex-wrap:wrap;gap:8px;margin:0;min-width:0}
.ra-area-summary .condition-chips:empty{display:none}
.ra-area-summary .condition-chips a{display:inline-flex;align-items:center;min-height:44px;margin:0;padding:8px 12px;border:1px solid #94b5a9;border-radius:8px;background:#fffef9;color:#1b5e60;text-decoration:none;font-size:14px;line-height:1.5;overflow-wrap:anywhere;max-width:100%}
.ra-area-summary .condition-chips a:hover{background:#f9fbf6;border-color:#1f6e70;text-decoration:underline}
.ra-area-summary .condition-age{display:flex;align-items:center;gap:6px;margin:0;color:#496b67;font-size:14px;line-height:1.5;overflow-wrap:anywhere}
.ra-area-summary .condition-age svg{width:16px;height:16px;flex:none}
.ra-area-summary__help{padding-top:20px;border-top:1px solid #dce6df;min-width:0}
.ra-area-summary__help h3{font-size:16px;line-height:1.5;font-weight:700;margin:0 0 8px;overflow-wrap:anywhere}
.ra-area-summary__help p{font-size:14px;line-height:1.7;color:#496b67;margin:0 0 8px;overflow-wrap:anywhere}
.ra-area-summary__help a{display:inline-flex;align-items:center;min-height:44px;color:#1b5e60;font-size:14px;font-weight:700;line-height:1.5;text-decoration:none;overflow-wrap:anywhere}
.ra-area-summary__help a:hover{text-decoration:underline}
.ra-area-summary :is(summary,select,button,a):focus-visible{outline:3px solid #237c80;outline-offset:3px}
.ra-area-summary>summary:focus-visible{outline-offset:-4px;border-radius:14px}
html[dir=rtl] .ra-area-summary__picker select{padding:12px 12px 12px 32px}
html[dir=rtl] .ra-area-summary__arrow,html[dir=rtl] .ra-area-summary__submit svg{transform:scaleX(-1)}
@media(min-width:640px) and (max-width:1000px){.ra-area-summary__picker{grid-template-columns:1fr}.ra-area-summary__submit{width:100%}}
@media(max-width:639px){.ra-area-summary>summary{padding:20px;font-size:20px;gap:10px}.ra-area-summary__body{padding:0 20px 20px;gap:16px}.ra-area-summary__picker{grid-template-columns:1fr}.ra-area-summary__submit{width:100%;font-size:16px}.ra-area-summary__stats{padding:16px}.ra-area-summary .featured-total strong{font-size:52px}.ra-area-summary .featured-total span{font-size:16px}.ra-area-summary__help{padding-top:16px}}
@media(prefers-reduced-motion:reduce){.ra-area-summary *{transition:none!important;animation:none!important}}
.ra-area-summary__search{position:relative;min-width:0}
.ra-area-summary__search input[type="text"]{width:100%;max-width:100%;min-width:0;min-height:52px;height:52px;padding:12px;font:inherit;font-size:16px;color:#12383a;background:#fff;border:1px solid #6c928b;border-radius:10px}
.ra-area-summary__search input[type="text"]::placeholder{color:#6d8a86}
.ra-area-summary__results{position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:30;margin:0;padding:6px;list-style:none;background:#fffef9;border:1px solid #b9d2c9;border-radius:12px;box-shadow:0 18px 40px rgba(18,56,58,.18);max-height:320px;overflow:auto}
.ra-area-summary__results[hidden]{display:none}
.ra-area-summary__result-btn{display:grid;gap:2px;width:100%;padding:10px 12px;text-align:left;background:none;border:0;border-radius:8px;font:inherit;color:#12383a;cursor:pointer}
.ra-area-summary__result-btn:hover,.ra-area-summary__result-btn:focus-visible{background:#edf6f2}
.ra-area-summary__result-btn span{font-size:15px;font-weight:600;line-height:1.35;overflow-wrap:anywhere}
.ra-area-summary__result-btn small{font-size:12.5px;color:#527775;line-height:1.4}
/* Polesan seksi kondisi terbaru: satu wadah 1120px, kartu menyatu, kontrol di dalam kartu, tanpa panah. */
.featured-section .section-head{max-width:1120px;margin-inline:auto}
.featured-columns{max-width:1120px;gap:20px;align-items:stretch}
.featured-section .condition-disclaimer{max-width:1120px;margin-inline:auto}
.featured-shell{display:flex;flex-direction:column;min-width:0;background:rgba(255,255,255,.88);border:1px solid #e3ece7;border-radius:20px;box-shadow:0 12px 32px rgba(18,56,58,.08);padding:18px}
.featured-shell .condition-card--featured{background:transparent;border:0;box-shadow:none;padding:0;border-radius:0;overflow:visible}
.featured-shell .condition-photo{border-radius:14px}
.featured-shell .condition-card-body{padding:16px 4px 0}
.condition-badge{position:absolute;top:12px;left:12px;z-index:2;background:rgba(255,255,255,.88);border-radius:999px;padding:5px 11px;font-size:11.5px;font-weight:700;color:#1f6e70}
.featured-shell .condition-status{border-radius:999px;padding:5px 11px}
.featured-shell .condition-cta{display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#359fa0,#1f6e70);border-radius:999px;padding:10px 18px;margin-top:18px;font-size:13.5px;box-shadow:0 6px 16px rgba(31,110,112,.25)}
.featured-controls{border-top:1px solid #e3ece7;padding-top:14px}
.featured-nav{display:flex;gap:8px;flex-wrap:wrap}
.featured-section .featured-controls button{background:#fff;border:1px solid #e3ece7;border-radius:999px;padding:8px 14px;min-height:40px;font-size:13px;color:#12383a}
.featured-section .featured-controls button:hover{border-color:#c9dcd4;background:#f7fbf9}
.featured-section .featured-controls button:disabled{opacity:.55}
.featured-poswrap{display:flex;align-items:center;gap:10px;margin-left:auto}
.featured-segments{display:flex;gap:4px;align-items:center;max-width:190px;overflow:hidden}
.featured-section .featured-segments span{width:14px;max-width:16px;min-width:5px;height:3.5px;border-radius:99px;background:#dce8e2;flex:1 1 8px;margin-left:0}
.featured-section .featured-segments span.on{background:#359fa0;flex-grow:1.7}
.featured-summary.ra-area-summary{border-radius:20px;border-color:#e3ece7;background:rgba(255,255,255,.88);box-shadow:0 12px 32px rgba(18,56,58,.08);display:flex;flex-direction:column}
.ra-area-summary__body{flex:1}
.ra-area-summary__help{margin-top:auto}
.ra-area-summary__picker{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #dfe9e4;border-radius:999px;padding:5px 5px 5px 16px}
.ra-area-summary__picker:focus-within{border-color:#359fa0;box-shadow:0 0 0 3px rgba(53,159,160,.18)}
.ra-area-summary__search{flex:1}
.ra-area-summary__search input[type="text"]{border:0;background:transparent;min-height:0;height:auto;padding:0}
.ra-area-summary__results{top:calc(100% + 12px)}
.ra-area-summary__submit{border:0;border-radius:999px;background:linear-gradient(135deg,#359fa0,#1f6e70);min-height:0;height:auto;padding:9px 16px;font-size:13px}
.ra-area-summary__submit:hover{background:linear-gradient(135deg,#2b8a8c,#18595b)}
.ra-area-summary__catalog{grid-template-columns:24px minmax(0,1fr);border-radius:12px;background:#f4faf8}
.ra-area-summary__stats{background:transparent;border:0;border-radius:0;padding:0}
.ra-stats-line{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.ra-area-summary .featured-total strong{font-size:44px}
.ra-area-summary .condition-age{margin-left:auto}
.ra-area-summary .condition-chips a{border-radius:999px;background:#e8f5f1;border-color:transparent;padding:6px 12px;min-height:0;font-size:12.5px;font-weight:700}
.ra-area-summary__list-title{font-size:14px;font-weight:800}
.ra-area-summary .area-hazard{flex-direction:row;align-items:flex-start;gap:11px;background:#fff;border-color:#e3ece7;border-radius:13px;padding:11px 13px}
.area-hazard__content{display:grid;gap:2px;min-width:0}
.area-hazard__dot{width:9px;height:9px;border-radius:50%;background:#b7bdb9;margin-top:5px;flex:none}
.area-hazard__dot--new{background:#e3a008}
.area-hazard__dot--confirmed{background:#359fa0}
.area-hazard__dot--verified{background:#2e9e5b}
.area-hazard__dot--disputed{background:#d95f43}
.area-hazard__dot--resolved{background:#58a08c}
@media(max-width:639px){.featured-shell{padding:15px}}
</style>
<section class="featured-section holo-zone" data-holo="true" data-featured data-endpoint="{{ route('conditions.featured') }}" data-school="{{ $school?->public_slug ?? $school?->id }}" data-timezone="{{ $school?->timezone??'Asia/Jakarta' }}" data-interval="{{ $featured['rotate_in_seconds'] }}" data-slug="{{ $featured['hazard']['public_slug']??'' }}">
 <div class="section-head"><div><p class="eyebrow">{{ __('Semua hazard') }}</p><h2>{{ __('Kondisi terbaru dari semua institusi') }}</h2></div><a class="conditions-all" href="{{ route('conditions.index',['school'=>$school?->public_slug??$school?->id]) }}" aria-label="{{ __('Lihat semua kondisi terbaru') }}">{{ __('Lihat semua') }}</a></div>
 <div class="featured-columns">
  <div class="featured-shell"><div data-featured-card aria-live="off">@if($featured['hazard'])<x-condition-card :item="$featured['hazard']" featured/>@else<div class="condition-empty"><h3>{{ __('Belum ada laporan yang masuk.') }}</h3><p>{{ __('Ini bukan jaminan kondisi aman.') }}</p><a class="btn btn-dark" href="{{ route('map',['school'=>$school?->id]) }}">{{ __('Buka peta') }}</a><a class="text-link" href="{{ auth()->check()?route('hazards.create'):route('guest-reports.create',['school'=>$school?->id]) }}">{{ __('Lapor kondisi') }}</a></div>@endif</div>
   <div class="featured-controls"><div class="featured-nav"><button type="button" data-prev>{{ __('Sebelumnya') }}</button><button type="button" data-next>{{ __('Berikutnya') }}</button></div><div class="featured-poswrap"><div class="featured-segments" data-segments aria-hidden="true">@for($seg = 1; $seg <= (int) ($featured['total_active'] ?? 0); $seg++)<span class="{{ $seg === (int) ($featured['position'] ?? 0) ? 'on' : '' }}"></span>@endfor</div><span data-position>{{ $featured['position'] }} {{ __('dari') }} {{ $featured['total_active'] }}</span></div></div><p class="sr-only" data-featured-announcement aria-live="polite"></p>
  </div>
  <details class="featured-summary ra-area-summary" open>
   <summary><span class="ra-area-summary__mark" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span><span>{{ __('Ringkasan area institusi') }}</span><span class="ra-area-summary__chevron" aria-hidden="true"></span></summary>
   <div class="ra-area-summary__body">
    <form action="{{ route('home') }}" method="get" class="ra-area-summary__form" data-area-form data-area-endpoint="{{ route('conditions.area') }}"><label for="featured-school">{{ __('Institusi · wilayah pemantauan') }}</label><div class="ra-area-summary__picker"><div class="ra-area-summary__search" data-area-search data-search-endpoint="{{ route('schools.search') }}"><input type="text" id="featured-school" data-area-search-input value="{{ $school?->name }}" placeholder="{{ __('Ketik nama institusi…') }}" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="featured-school-results" aria-autocomplete="list"><input type="hidden" name="school" data-area-search-value value="{{ $school?->public_slug }}"><ul class="ra-area-summary__results" id="featured-school-results" data-area-search-results role="listbox" hidden></ul></div><button type="submit" class="ra-area-summary__submit">{{ __('Tampilkan area') }}</button></div></form>
    <a class="ra-area-summary__catalog" href="{{ route('campus-catalog.index',['country'=>'ID']) }}"><span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m2 8 10-5 10 5-10 5L2 8Z"/><path d="M6 11v7c4 3 8 3 12 0v-7M22 9v9"/></svg></span><span>{{ __('Cari daftar kampus Indonesia / dunia') }}</span></a>
    <div class="ra-area-summary__stats">
     <div class="ra-stats-line">
      <p class="featured-total"><strong data-total>{{ $summary['total'] }}</strong><span>{{ __('kondisi aktif dalam wilayah institusi') }}</span></p>
      <p class="condition-age"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/></svg><span>{{ __('Diperbarui') }} <time data-generated datetime="{{ $featured['generated_at'] }}">{{ now($school?->timezone??'Asia/Jakarta')->format('H.i T') }}</time></span></p>
     </div>
     <div class="condition-chips" data-category-counts>@foreach($summary['categories'] as $code=>$category)<a href="{{ route('conditions.index',['kategori'=>$code,'school'=>$school?->public_slug??$school?->id]) }}">{{ $category['label'] }} · {{ $category['count'] }}</a>@endforeach</div>
    </div>
    <div class="ra-area-summary__hazards">
     <h3 class="ra-area-summary__list-title">{{ __('Hazard di area ini') }}</h3>
     <ul class="area-hazard-list" data-area-list>
      @forelse($area['hazards'] ?? [] as $hz)
      <li><a class="area-hazard" href="{{ $hz['detail_url'] }}"><span class="area-hazard__dot area-hazard__dot--{{ $hz['status'] ?? 'new' }}" aria-hidden="true"></span><span class="area-hazard__content"><span class="area-hazard__main"><strong>{{ $hz['type_label'] }}</strong>@if($hz['location_label'])<span class="area-hazard__loc">{{ $hz['location_label'] }}</span>@endif</span><span class="area-hazard__meta">{{ $hz['status_label'] }} · {{ $hz['age_text'] }} · {{ $hz['confirmations_count'] }} {{ __('konfirmasi') }}@if($hz['is_demo']) <span class="data-demo data-demo--icon" role="img" aria-label="{{ __('Data demo') }}" title="{{ __('Data demo — bukan laporan nyata') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2v6L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 8V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/></svg></span>@endif</span></span></a></li>
      @empty
      <li class="area-hazard-empty">{{ __('Belum ada hazard aktif di area ini.') }}</li>
      @endforelse
     </ul>
     @if($summary['total'] > count($area['hazards'] ?? []))
     <a class="area-hazard-all" data-area-all href="{{ route('conditions.index', ['school' => $school?->public_slug ?? $school?->id]) }}">{{ __('Lihat semua kondisi di area ini') }}</a>
     @endif
    </div>
    <div class="ra-area-summary__help"><h3>{{ __('Baca status, bukan hanya foto') }}</h3><p>{{ __('Konfirmasi komunitas berbeda dari verifikasi pengelola institusi. Perhatikan umur data; kondisi di lapangan dapat berubah.') }}</p><a href="{{ route('help') }}">{{ __('Cara membaca status') }}</a></div>
   </div>
  </details>
 </div><p class="condition-disclaimer">{{ __('Informasi komunitas, bukan jaminan kondisi jalan. Tidak ada laporan berarti belum ada data.') }}</p>
</section>
