@extends('layouts.app')
@section('title',__('Semua kondisi terbaru'))
@section('metadata')<meta name="robots" content="noindex,nofollow"><meta name="description" content="Daftar kondisi jalan terbaru di area sekolah, berdasarkan laporan komunitas.">@endsection
@section('content')
<style data-ruteaman-conditions="v1">
/* Daftar kondisi: kepala ramping, filter jadi kartu dengan pil tersegmentasi
   (termasuk opsi Semua kondisi), kartu kondisi lebih ringkas dan tenang. */
.conditions-page h1{font-size:28px;font-weight:600;letter-spacing:-.015em;margin:10px 0 6px}
.conditions-page .eyebrow{font-size:11px;letter-spacing:.14em}
.condition-subtitle{font-size:13.5px;color:#5F7A76;margin:0}
.condition-back{font-size:14px;font-weight:600}
.condition-breadcrumb{font-size:12.5px;color:#7A918B}
.condition-filters{background:#fff;border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:16px;display:grid;gap:14px;margin:18px 0 26px}
.condition-filters>label{font-size:13px;font-weight:600;color:#14343A;display:grid;gap:7px}
.condition-filters select{height:50px;border:1.5px solid #DFE9E4;border-radius:14px;padding:0 14px;font-size:14.5px;font-weight:500;color:#14343A;background:#fff;width:100%;box-sizing:border-box}
.condition-filters select:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.condition-filters fieldset{border:0;padding:0;margin:0;min-width:0;width:auto;display:flex;flex-direction:row;flex-wrap:wrap;gap:8px;align-items:center}
.condition-filters legend{font-size:13px;font-weight:600;color:#14343A;margin-bottom:8px;padding:0;width:100%}
.condition-filters fieldset label{position:relative;display:inline-flex;align-items:center;padding:9px 14px;margin:0 6px 6px 0;border:1.5px solid #DFE9E4;border-radius:999px;font-size:13.5px;font-weight:600;color:#49696B;background:#fff;cursor:pointer}
.condition-filters fieldset label:has(input:checked){background:#1F6E70;border-color:#1F6E70;color:#fff}
.condition-filters fieldset input{position:absolute;opacity:0;pointer-events:none}
.condition-filters .btn{width:100%;min-height:46px;margin:0;border-radius:14px;padding:0 24px;background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;font-size:14.5px;font-weight:600}
@media(min-width:900px){
 .condition-filters{grid-template-columns:minmax(220px,340px) 1fr auto;align-items:end}
 .condition-filters .btn{width:auto}
 .condition-filters fieldset label{margin-bottom:0}
}
.condition-section{margin-top:30px}
.condition-section-head{border-bottom:1px solid #E3EFE9;padding-bottom:10px;margin-bottom:16px}
.condition-section-head h2{font-size:19px;font-weight:600;letter-spacing:-.01em}
.condition-section-head p{font-size:13px;color:#5F7A76}
.condition-grid{gap:16px}
.condition-card{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);margin-top:0;overflow:hidden}
.condition-photo{aspect-ratio:16/8.2;border-radius:0}
.condition-photo .category-symbol svg{width:52px;height:52px}
.condition-card-body{padding:14px 16px 16px}
.condition-kind{font-size:12px;color:#7A918B;margin:0 0 4px}
.condition-card-body h3{font-size:18px;font-weight:600;letter-spacing:-.01em;margin:0 0 8px}
.condition-age{font-size:12.5px;color:#7A918B}
.conditions-load{text-align:center}
.condition-disclaimer{font-size:12.5px;color:#7A918B;text-align:center}
.condition-empty{background:#fff;border:1px dashed #C9DCD2;border-radius:18px;padding:28px 20px;text-align:center}
.condition-filters,.condition-card{box-shadow:0 1px 2px rgba(18,56,58,.06),0 6px 16px rgba(18,56,58,.07),0 22px 44px rgba(18,56,58,.11)}
.condition-card{transition:transform .22s ease,box-shadow .22s ease}
@media(hover:hover){.condition-card:hover{transform:translateY(-2px);box-shadow:0 2px 4px rgba(18,56,58,.06),0 10px 22px rgba(18,56,58,.09),0 30px 56px rgba(18,56,58,.14)}}
.condition-filters .btn{box-shadow:0 8px 18px rgba(31,110,112,.26)}
.condition-filters fieldset label{transition:background .18s ease,color .18s ease,border-color .18s ease,box-shadow .18s ease}
.condition-filters fieldset label:has(input:checked){box-shadow:0 4px 10px rgba(31,110,112,.25)}
.condition-count{display:inline-flex;align-items:center;min-width:26px;height:22px;padding:0 8px;border-radius:999px;background:#EAF4EE;color:#1F6E70;font-size:12px;font-weight:700}
.conditions-load .btn{box-shadow:0 1px 2px rgba(18,56,58,.06),0 10px 24px rgba(18,56,58,.08)}
</style>
<div class="conditions-page" data-condition-list data-endpoint="{{ route('conditions.api') }}" data-cursor="{{ $feed['next_cursor']??'' }}" data-total="{{ $feed['total_count'] }}">
 <a class="condition-back" href="{{ route('home') }}" data-back data-fallback-href="{{ route('home') }}">{{ __('← Kembali') }}</a>
 <nav aria-label="{{ __('Breadcrumb') }}" class="condition-breadcrumb"><a href="{{ route('home') }}">{{ __('Beranda') }}</a><span aria-hidden="true">›</span><span>{{ __('Semua kondisi') }}</span></nav>
 <p class="eyebrow">{{ $school?->name??__('Area sekolah') }}</p><h1>{{ __('Semua kondisi terbaru') }}</h1>
 <p class="condition-subtitle">{{ $feed['total_count'] }} {{ __('kondisi · diurutkan dari yang terbaru · diperbarui') }} <time datetime="{{ $feed['generated_at'] }}">{{ \Carbon\Carbon::parse($feed['generated_at'])->timezone($school?->timezone??'Asia/Jakarta')->format('H.i T') }}</time></p>
 <form class="condition-filters" method="get"><input type="hidden" name="school" value="{{ $school?->public_slug??$school?->id }}"><label>{{ __('Kategori') }}<select name="kategori"><option value="">{{ __('Semua kategori') }}</option>@foreach($types as $type)<option value="{{ $type->code }}" @selected(($filters['kategori']??'')===$type->code)>{{ __($type->label) }}</option>@endforeach</select></label><fieldset><legend>{{ __('Tampilkan') }}</legend><label><input type="radio" name="tampil" value="aktif" @checked(($filters['tampil']??'aktif')==='aktif')> {{ __('Sedang terjadi') }}</label><label><input type="radio" name="tampil" value="surut" @checked(($filters['tampil']??'aktif')==='surut')> {{ __('Sudah surut') }} {{ config('ruteaman.resolved_hours') }} {{ __('jam terakhir') }}</label><label><input type="radio" name="tampil" value="semua" @checked(($filters['tampil']??'aktif')==='semua')> {{ __('Semua kondisi') }}</label></fieldset><button class="btn btn-dark">{{ __('Terapkan') }}</button></form>
 @php
  $unverified = collect($feed['items'])->filter(fn($i) => in_array($i['status'] ?? '', ['new','confirmed','disputed']))->values();
  $verified = collect($feed['items'])->filter(fn($i) => ($i['status'] ?? '') === 'verified')->values();
  $others = collect($feed['items'])->filter(fn($i) => !in_array($i['status'] ?? '', ['new','confirmed','disputed','verified']))->values();
 @endphp
 @if($unverified->count())
 <section class="condition-section" aria-labelledby="cond-unverified">
  <div class="condition-section-head"><h2 id="cond-unverified">{{ __('Perlu verifikasi') }}</h2><span class="condition-count">{{ $unverified->count() }}</span><p>{{ __('Laporan komunitas yang menunggu pemeriksaan pengelola.') }}</p></div>
  <ol class="condition-grid">@foreach($unverified as $item)<li><x-condition-card :item="$item" heading="h3"/></li>@endforeach</ol>
 </section>
 @endif
 @if($verified->count())
 <section class="condition-section" aria-labelledby="cond-verified">
  <div class="condition-section-head"><h2 id="cond-verified">{{ __('Terverifikasi') }}</h2><span class="condition-count">{{ $verified->count() }}</span><p>{{ __('Sudah diperiksa dan dikonfirmasi pengelola.') }}</p></div>
  <ol class="condition-grid">@foreach($verified as $item)<li><x-condition-card :item="$item" heading="h3"/></li>@endforeach</ol>
 </section>
 @endif
 @if($others->count())
 <section class="condition-section" aria-labelledby="cond-others">
  <div class="condition-section-head"><h2 id="cond-others">{{ __('Lainnya') }}</h2><span class="condition-count">{{ $others->count() }}</span></div>
  <ol class="condition-grid">@foreach($others as $item)<li><x-condition-card :item="$item" heading="h3"/></li>@endforeach</ol>
 </section>
 @endif
 @if(!$feed['items'])<div class="condition-empty"><h2>{{ __('Belum ada kondisi untuk filter ini.') }}</h2><p>{{ __('Tidak ada laporan bukan jaminan kondisi aman.') }}</p><a class="btn btn-dark" href="{{ route('map') }}">{{ __('Lihat peta') }}</a></div>@endif
 <div class="conditions-load"><button class="btn btn-soft" type="button" data-load-more @if(!$feed['next_cursor']) hidden @endif>{{ __('Muat lebih banyak') }}</button><p data-list-status role="status" aria-live="polite">{{ $feed['next_cursor']?'':__('Tidak ada kondisi lain.') }}</p></div>
 <p class="condition-disclaimer">{{ __('Informasi komunitas. Tetap perhatikan keadaan sebenarnya; RuteAman bukan layanan darurat.') }}</p>
</div>
@endsection
