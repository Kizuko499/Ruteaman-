@extends('layouts.app')
@section('title',$item['title'])
@section('metadata')
 <link rel="canonical" href="{{ route('conditions.show',$hazard) }}"><meta name="robots" content="{{ config('ruteaman.public_hazard_indexable')&&$hazard->status->value!=='new'?'index,follow':'noindex,nofollow' }}"><meta name="description" content="{{ $item['title'] }} · {{ $item['status_label'] }} · {{ $item['age_text'] }}. Informasi komunitas, bukan jaminan kondisi jalan.">
 <meta property="og:type" content="article"><meta property="og:title" content="{{ $item['title'] }}"><meta property="og:description" content="{{ $item['status_label'] }} · {{ $item['age_text'] }}"><meta property="og:url" content="{{ route('conditions.show',$hazard) }}">@if($hazard->status->value!=='new')<meta property="og:image" content="{{ route('conditions.og',$hazard) }}">@endif
@endsection
@section('content')
@php($tz=$school?->timezone??'Asia/Jakarta')
@php($canConfirm=($hazard->publication_control??'normal')==='normal'&&!$hazard->requires_moderation&&auth()->check()&&auth()->user()->active&&auth()->user()->school_id&&app(\App\Services\PermissionService::class)->feature(auth()->user()->school,'confirmation',true)&&\App\Models\Hazard::whereKey($hazard->id)->inSchool(auth()->user()->school_id)->exists()&&!$hazard->reports()->where('reporter_id',auth()->id())->exists())
@php($canDeleteComments=auth()->check()&&app(\App\Services\PermissionService::class)->can(auth()->user(),'comments.delete'))
<div class="conditions-page condition-article" data-condition-detail data-generated="{{ now()->toIso8601String() }}" data-refresh="{{ url('/api/conditions/'.$hazard->public_slug) }}" data-lat="{{ $hazard->latitude }}" data-lng="{{ $hazard->longitude }}">
 <div class="condition-sticky" hidden><a href="{{ route('conditions.index') }}" data-back data-fallback-href="{{ route('conditions.index') }}" aria-label="{{ __('Kembali ke daftar kondisi') }}"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg></a><strong>{{ $item['title'] }}</strong><button type="button" data-share><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4"/><path d="m15.4 6.5-6.8 4"/></svg>{{ __('Bagikan') }}</button></div>
 <a class="condition-back" href="{{ route('conditions.index') }}" data-back data-fallback-href="{{ route('conditions.index') }}">{{ __('← Kembali') }}</a>
 <nav class="condition-breadcrumb" aria-label="{{ __('Breadcrumb') }}"><a href="{{ route('home') }}">{{ __('Beranda') }}</a><span aria-hidden="true">›</span><a href="{{ route('conditions.index') }}">{{ __('Semua kondisi') }}</a><span aria-hidden="true">›</span><span>{{ $item['type_label'] }}</span></nav>
 <p data-offline class="condition-banner" hidden>{{ __('Offline. Halaman terakhir yang sedang terbuka tetap dapat dibaca. Data belum diperbarui.') }}</p>
 @if(($hazard->publication_control??'normal')!=='normal')<p class="condition-banner">{{ $hazard->status_label }}{{ __('. Halaman ini hanya untuk pengelola yang berwenang.') }}</p>@elseif($hazard->status->value==='resolved')<p class="condition-banner">{{ __('Kondisi ini sudah surut ·') }} {{ $hazard->resolved_at?->timezone($tz)->locale(\App\Services\LanguagePreferences::carbonLocale())->translatedFormat('d F Y, H.i T') }}{{ __('. Konfirmasi ditutup.') }}</p>@elseif($expired)<p class="condition-banner">{{ __('Data sudah kedaluwarsa. Keadaan sekarang belum diketahui. Konfirmasi “Masih ada” akan meminta peninjauan ulang.') }}</p>@elseif($hazard->requires_moderation)<p class="condition-banner">{{ __('Laporan menunggu pemeriksaan. Halaman ini hanya dapat dibuka pengelola yang berwenang.') }}</p>@elseif($hazard->status->value==='new')<p class="condition-banner">{{ $hazard->published_at?__('Laporan langsung dipublikasikan, belum terverifikasi. Informasi dapat berubah dan perlu konfirmasi independen.'):__('Menunggu konfirmasi. Hanya anggota area sekolah yang dapat membuka laporan ini.') }}</p>@endif
 <header class="condition-heading"><p class="eyebrow">{{ $item['type_label'] }}</p><h1>{{ $item['title'] }}</h1>@if($item['is_demo'])<span class="data-demo data-demo--icon" role="img" aria-label="{{ __('Data demo') }}" title="{{ __('Simulasi demo — bukan audit lapangan') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2v6L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 8V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/></svg></span>@endif
  <p>{{ __('RuteAman · Dilaporkan') }} <time datetime="{{ $hazard->first_reported_at->toIso8601String() }}">{{ $hazard->first_reported_at->timezone($tz)->locale(\App\Services\LanguagePreferences::carbonLocale())->translatedFormat('d F Y, H.i T') }}</time></p>
  <p>{{ __('Terakhir dikonfirmasi:') }} @if($hazard->last_confirmed_at)<time datetime="{{ $hazard->last_confirmed_at->toIso8601String() }}">{{ $hazard->last_confirmed_at->locale(\App\Services\LanguagePreferences::carbonLocale())->diffForHumans() }}</time>@else {{ __('belum ada konfirmasi') }} @endif</p>
  <div class="condition-actions"><x-status-chip :label="$item['status_label']"/>
   @if($hazard->status->value==='resolved')<button disabled>{{ __('Masih ada') }}</button><button disabled>{{ __('Sudah surut') }}</button>@elseif($canConfirm)<form method="post" action="{{ route('hazards.confirm',$hazard) }}">@csrf<button name="vote" value="still_there">{{ __('◎ Masih ada') }}</button><button name="vote" value="gone" @disabled($expired)>{{ __('○ Sudah surut') }}</button></form>@elseif(!auth()->check())<a href="{{ route('login') }}">{{ __('Masuk untuk mengonfirmasi') }}</a>@else<span class="condition-age">{{ __('Konfirmasi hanya oleh anggota area yang bukan pelapor.') }}</span>@endif
   <button type="button" data-share>{{ __('Bagikan') }}</button><button type="button" data-copy>{{ __('Salin tautan') }}</button>
  </div><p class="condition-age">@if($hazard->status->value==='verified'){{ __('Diverifikasi oleh verifikator') }} {{ $school?->name??'sekolah' }} · @endif{{ $input['reports'] }} {{ __('laporan independen ·') }} <span data-count>{{ $input['confirmations'] }}</span> {{ __('konfirmasi') }}</p>
 </header><p data-detail-status class="condition-age" role="status" aria-live="polite"></p>
 @if($errors->any())<p class="condition-banner" role="alert">{{ __($errors->first()) }}</p>@endif
 <div class="condition-article-columns"><article>
  <figure class="condition-hero"><div class="condition-photo"><span class="category-symbol"><x-condition-symbol :type="$item['type']" :visual="$item['type_visual'] ?? null"/></span>@if($photos)<picture><source type="image/webp" srcset="{{ $photos[0]['thumb'] }} 640w, {{ $photos[0]['url'] }} 1280w" sizes="(min-width:1024px) 720px, 95vw"><img src="{{ $photos[0]['fallback'] }}" srcset="{{ $photos[0]['fallback_thumb'] }} 640w, {{ $photos[0]['fallback'] }} 1280w" sizes="(min-width:1024px) 720px, 95vw" width="1280" height="720" alt="{{ $photos[0]['alt'] }}" fetchpriority="high"></picture><button type="button" data-photo-index="0">{{ __('Lihat foto') }}</button>@endif</div><figcaption>{{ !$photos?__('Belum ada foto untuk kondisi ini.'):($item['is_demo']?__('Foto ilustrasi data demo; bukan bukti audit lokasi.'):__('Foto laporan komunitas. Tidak menampilkan identitas pelapor.')) }}</figcaption></figure>
  @if(count($photos)>1)<div class="condition-thumbnails">@foreach($photos as $i=>$photo)<button type="button" data-photo-index="{{ $i }}" aria-label="Lihat foto {{ $i+1 }}"><picture><source type="image/webp" srcset="{{ $photo['thumb'] }}"><img src="{{ $photo['fallback_thumb'] }}" alt="" width="96" height="64" loading="lazy"></picture></button>@endforeach</div>@endif
  <section><h2>{{ __('Ringkasan') }}</h2><p>{{ $item['type_label'] }} {{ __('dilaporkan') }}{{ $hazard->location_label?__(' di ').$hazard->location_label:'' }} {{ __('pada') }} {{ $hazard->first_reported_at->timezone($tz)->locale(\App\Services\LanguagePreferences::carbonLocale())->translatedFormat('d F Y, H.i T') }}{{ __('. Status saat ini:') }} {{ strtolower($item['status_label']) }}{{ __('. Tersedia') }} {{ $input['reports'] }} {{ __('laporan independen dan') }} {{ $input['confirmations'] }} {{ __('konfirmasi masih ada.') }}</p><p>{{ __('Informasi ini berasal dari komunitas. Kondisi dapat berubah dan bukan jaminan kondisi jalan.') }}</p></section>
  @if($relatedItems)<aside class="condition-read-also"><strong>{{ __('Baca juga') }}</strong><a href="{{ $relatedItems[0]['detail_url'] }}">{{ $relatedItems[0]['title'] }} →</a></aside>@endif
  <section><h2>{{ __('Rincian kondisi') }}</h2><x-fact-table :facts="[__('Lokasi')=>$hazard->location_label?:__('Label lokasi belum tersedia'),__('Jenis')=>$item['type_label'],__('Keparahan')=>match((int)$hazard->severity){1=>__('Ringan'),2=>__('Sedang'),default=>__('Berat')},__('Status')=>$item['status_label'],__('Dilaporkan')=>$hazard->first_reported_at->timezone($tz)->locale(\App\Services\LanguagePreferences::carbonLocale())->translatedFormat('d F Y, H.i T'),__('Konfirmasi terakhir')=>$hazard->last_confirmed_at?->timezone($tz)->locale(\App\Services\LanguagePreferences::carbonLocale())->translatedFormat('d F Y, H.i T')??__('Belum ada'),'Umur data'=>$item['age_text'],'Laporan independen'=>$input['reports'],'Konfirmasi masih ada'=>$input['confirmations'],'TTL jenis'=>$input['ttl_minutes'].' menit','Batas aktif'=>($input['ttl_minutes']*3).' menit tanpa kabar baru']"/></section>
  <section class="condition-evidence"><h2>{{ __('Kenapa tingkatnya begini?') }}</h2><p>{{ __('Beban titik ini:') }} {{ $load['value']??'—' }} · {{ $load['label'] }}{{ __('. Angka rute menggabungkan semua kondisi aktif di sepanjang rute.') }}</p><p>{{ __('Keyakinan bukti') }} {{ round($input['confidence']*100) }}{{ __('% · kesegaran') }} {{ round($input['freshness']*100) }}{{ __('%. Laporan dan konfirmasi independen, foto, serta verifikasi sekolah memengaruhi perhitungan.') }}</p><p>{{ $input['weather_label'] }}{{ __('. Heuristik, bukan prediksi atau jaminan.') }}</p></section>
  <section><h2>{{ __('Linimasa') }}</h2><x-timeline :events="$timeline" :timezone="$tz"/></section>
  <section><h2>{{ __('Lokasi kondisi') }}</h2><p>{{ $hazard->location_label?:__('Lokasi ditandai pada peta.') }}</p><div id="condition-mini-map" class="condition-mini-map" aria-label="{{ __('Peta lokasi kondisi') }}"></div><a class="condition-back" href="{{ route('map',['hazard'=>$hazard->public_slug,'school'=>$school?->id]) }}">{{ __('Buka titik ini di peta →') }}</a><p class="condition-age">{{ __('Koordinat kondisi:') }} {{ number_format($hazard->latitude,6) }}, {{ number_format($hazard->longitude,6) }}{{ __('. Peta tidak meminta lokasi perangkat.') }}</p></section>
 </article><aside class="condition-sidebar">
  @if(count($relatedItems)>1)<section><h2>{{ __('Kondisi lain di dekatnya') }}</h2>@foreach(array_slice($relatedItems,1) as $relatedItem)<x-condition-card :item="$relatedItem"/>@endforeach</section>@endif
  <section><p class="eyebrow">{{ __('Cara membaca') }}</p><h2>{{ __('Perhatikan status dan umur data') }}</h2><p>{{ __('Dikonfirmasi komunitas: bukti dari pengguna independen.') }}</p><p>{{ __('Terverifikasi: diperiksa petugas sekolah.') }}</p><p>{{ __('Perlu ditinjau: bukti belum selaras.') }}</p><p>{{ __('Data lama tidak memastikan keadaan sekarang. Tidak ada laporan bukan jaminan kondisi aman.') }}</p><a class="text-link" href="{{ route('help') }}">{{ __('Bantuan dan status →') }}</a></section>
  @if(!auth()->check()||(auth()->user()->school&&app(\App\Services\PermissionService::class)->feature(auth()->user()->school,'reporting',true)))<section class="condition-report-cta"><h2>{{ __('Melihat kondisi lain?') }}</h2><p>{{ __('Berhenti dahulu sebelum melapor. Hindari identitas siswa, wajah, dan plat nomor.') }}</p><a class="btn btn-dark" href="{{ auth()->check()?route('hazards.create'):route('guest-reports.create') }}">{{ __('＋ Lapor kondisi') }}</a></section>@endif
 </aside></div>
 @if($photos)<dialog class="condition-lightbox" aria-labelledby="lightbox-title"><h2 id="lightbox-title">{{ __('Foto kondisi') }}</h2><button type="button" data-photo-close aria-label="{{ __('Tutup foto') }}">{{ __('Tutup ×') }}</button><picture><source type="image/webp" data-lightbox-webp><img alt="" width="1280" height="720"></picture><div><button type="button" data-photo-prev>{{ __('← Sebelumnya') }}</button><span data-photo-position aria-live="polite"></span><button type="button" data-photo-next>{{ __('Berikutnya →') }}</button></div></dialog><script type="application/json" id="condition-photos">{!! json_encode($photos,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!}</script>@endif
</div>

  <section id="komentar"><h2>{{ __('Komentar') }} ({{ $comments->count() }})</h2>
  @if(session('success'))<p class="notice" role="status">{{ session('success') }}</p>@endif
  <div class="comment-list">
  @forelse($comments as $comment)
  <article class="comment-card">
    <div class="comment-avatar" aria-hidden="true">{{ strtoupper(substr($comment->authorName(), 0, 1)) }}</div>
    <div class="comment-body">
      <p class="comment-head"><strong>{{ $comment->authorName() }}</strong>@if(!$comment->user_id)<span class="comment-chip">Guest</span>@endif <small>{{ $comment->created_at->timezone($tz)->translatedFormat('d M Y, H.i') }}</small></p>
      <p class="comment-text">{{ $comment->body }}</p>
      @if($canDeleteComments)
      <form method="post" action="{{ route('conditions.comments.destroy', [$hazard, $comment]) }}" class="comment-delete" onsubmit="return confirm('{{ __('Hapus komentar ini? Tindakan ini tidak dapat dibatalkan.') }}')">@csrf @method('DELETE')<button type="submit" class="comment-delete-btn">{{ __('Hapus komentar') }}</button></form>
      @endif
    </div>
  </article>
  @empty
  <p class="comment-empty">{{ __('Belum ada komentar. Jadilah yang pertama memberi kabar kondisi di titik ini.') }}</p>
  @endforelse
  </div>
  <form method="post" action="{{ route('conditions.comments.store', $hazard) }}" class="comment-form">
    @csrf
    <div class="comment-form-head"><div class="comment-avatar" aria-hidden="true">{{ auth()->check() ? strtoupper(substr(auth()->user()->display_name, 0, 1)) : 'G' }}</div><label for="comment-body">{{ auth()->check() ? __('Komentar sebagai :name', ['name' => auth()->user()->display_name]) : __('Komentar sebagai Guest') }}</label></div>
    <textarea id="comment-body" name="body" rows="3" maxlength="500" required placeholder="{{ __('Tulis kabar kondisi, mis. sudah surut / masih tergenang…') }}">{{ old('body') }}</textarea>
    @error('body')<p class="error" role="alert">{{ $message }}</p>@enderror
    <div class="comment-form-foot"><small>{{ __('Maksimal 500 karakter. Jaga bahasa dan jangan menulis identitas pribadi.') }}</small><button class="btn btn-dark" type="submit">{{ __('Kirim komentar') }}</button></div>
  </form>
  </section>

<style>
/* Komentar kondisi — gaya literal tersemat (tumpukan gaya layout tidak tersaji di server) */
#komentar{margin-top:36px;max-width:820px;scroll-margin-top:96px}
#komentar>h2{font-size:24px;font-weight:700;line-height:1.3;margin:0 0 4px}
.comment-list{display:grid;gap:10px;margin:14px 0}
.comment-card{display:flex;gap:12px;background:#fff;border:1px solid var(--line,#e5dcb8);border-radius:16px;padding:14px 16px;box-shadow:0 2px 8px #12383a0d}
.comment-avatar{flex-shrink:0;width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--teal,#359fa0),var(--teal-dark,#1f6e70));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px}
.comment-body{min-width:0;flex:1}
.comment-head{margin:0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:15px}
.comment-head small{color:var(--muted,#5b6f70);font-size:12.5px}
.comment-chip{background:var(--mint,#dff3e4);color:var(--teal-dark,#1f6e70);font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px}
.comment-text{margin:5px 0 0;overflow-wrap:anywhere;line-height:1.6;color:#24484b}
.comment-empty{color:var(--muted,#5b6f70);line-height:1.6;margin:14px 0 0;background:#fffefa;border:1.5px dashed #d8cfa8;border-radius:16px;padding:20px 18px;text-align:center}
.comment-form{background:#fff;border:1px solid var(--line,#e5dcb8);border-radius:16px;padding:18px;margin-top:14px;display:grid;gap:12px;box-shadow:0 2px 8px #12383a0d}
.comment-form label{font-weight:800;font-size:15px}
.comment-form-head{display:flex;align-items:center;gap:10px}
.comment-form textarea{width:100%;box-sizing:border-box;min-height:96px;border:1px solid #b9cfc9;border-radius:12px;padding:12px 14px;font:inherit;line-height:1.55;resize:vertical;background:#fffefa;color:#12383a}
.comment-form textarea::placeholder{color:#7d9291}
.comment-form textarea:focus{outline:none;border-color:var(--teal-dark,#1f6e70);box-shadow:0 0 0 3px #359fa033}
.comment-form .error{margin:0;color:#923907;font-size:14px}
.comment-form-foot{display:flex;align-items:center;justify-content:space-between;gap:12px}
.comment-form-foot small{color:var(--muted,#5b6f70);line-height:1.5;flex:1}
.comment-form-foot .btn{flex-shrink:0}
@media(max-width:640px){.comment-form{padding:16px}.comment-form-foot{flex-direction:column;align-items:stretch}.comment-form-foot .btn{width:100%}}
#komentar{max-width:1200px;margin:0 auto;padding:10px 24px 56px;scroll-margin-top:96px}
.comment-delete{margin-top:6px}
.comment-delete-btn{background:none;border:0;padding:2px 0;color:#b3261e;font:inherit;font-size:13.5px;font-weight:700;cursor:pointer;text-decoration:underline}
@media(max-width:640px){#komentar{padding:10px 20px 40px}}
</style>
<style>
/* Polesan modern minimalis halaman detail kondisi — hanya tampilan */
.conditions-page.condition-article{max-width:1160px;margin:0 auto;padding:26px 22px 10px}
.conditions-page .condition-back{display:inline-flex;align-items:center;gap:6px;font-size:.88rem;font-weight:600;color:var(--teal-dark,#1F6E70);text-decoration:none;padding:8px 14px 8px 11px;background:rgba(255,255,255,.65);border:1px solid #E1EAE5;border-radius:999px;box-shadow:0 2px 8px rgba(18,56,58,.06)}
.conditions-page .condition-breadcrumb{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:16px;font-size:.86rem;color:#7D9291}
.conditions-page .condition-breadcrumb a{color:var(--teal-dark,#1F6E70);font-weight:600;text-decoration:none}
.conditions-page .condition-breadcrumb span{color:#49696B}
.conditions-page .condition-banner{background:#FFF8E1;border:1px solid rgba(193,164,100,.3);border-radius:14px;padding:12px 16px;font-size:.92rem;line-height:1.55;color:#5C5540;margin:18px 0 0;box-shadow:0 6px 16px rgba(193,164,100,.10)}
.conditions-page .condition-heading{margin-top:22px}
.conditions-page .condition-heading .eyebrow{font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--teal,#359FA0);margin:0}
.conditions-page .condition-heading h1{font-family:Poppins,Inter,sans-serif;font-size:2rem;font-weight:700;letter-spacing:-.01em;color:#14343A;margin:8px 0 10px}
.conditions-page .condition-heading>p{color:#5F7476;font-size:.93rem;line-height:1.55;margin:3px 0}
.conditions-page .condition-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:16px}
.conditions-page .condition-actions .condition-status{display:inline-flex;align-items:center;gap:7px;background:#EFF7F3;border:1px solid #DCE9E3;border-radius:999px;padding:9px 15px;font-size:.88rem;font-weight:700;color:var(--teal-dark,#1F6E70);box-shadow:0 2px 8px rgba(18,56,58,.06)}
.conditions-page .condition-actions .condition-status svg{width:15px;height:15px}
.conditions-page .condition-actions>a,.conditions-page .condition-actions>button,.conditions-page .condition-actions form button{display:inline-flex;align-items:center;gap:7px;background:#fff;border:1px solid #DFE9E4;border-radius:999px;padding:10px 18px;font-family:Inter,sans-serif;font-size:.9rem;font-weight:600;color:#14343A;text-decoration:none;cursor:pointer;box-shadow:0 2px 6px rgba(18,56,58,.07),0 10px 20px rgba(18,56,58,.05);transition:transform .15s,box-shadow .15s,background .15s}
.conditions-page .condition-actions>a:hover,.conditions-page .condition-actions>button:hover,.conditions-page .condition-actions form button:hover{background:#F4F8F6;transform:translateY(-1px);box-shadow:0 4px 10px rgba(18,56,58,.08),0 14px 26px rgba(18,56,58,.07)}
.conditions-page .condition-actions form{display:inline-flex;gap:10px}
.conditions-page .condition-actions form button[name=vote][value=still_there]{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border-color:transparent;color:#fff}
.conditions-page .condition-age{color:#7D9291;font-size:.86rem}
.conditions-page .condition-article-columns{gap:30px;margin-top:24px}
.conditions-page .condition-hero{margin:0}
.conditions-page .condition-photo{border:1px solid #E3EFE9;border-radius:22px;overflow:hidden;box-shadow:0 2px 4px rgba(18,56,58,.05),0 20px 42px rgba(18,56,58,.11)}
.conditions-page .condition-photo .category-symbol svg{width:76px;height:76px}
.conditions-page .condition-hero figcaption{color:#7D9291;font-size:.86rem;margin-top:10px}
.conditions-page .condition-thumbnails{gap:10px;margin-top:12px}
.conditions-page .condition-thumbnails button{border:1px solid #E3EFE9;border-radius:12px;overflow:hidden;padding:0;background:#fff;cursor:pointer}
.conditions-page .condition-article-columns article>section{background:#fff;border:1px solid #E3EFE9;border-radius:20px;padding:22px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 6px 14px rgba(18,56,58,.06),0 20px 40px rgba(18,56,58,.09);margin-top:18px}
.conditions-page .condition-article-columns article>section h2{font-family:Poppins,Inter,sans-serif;font-size:1.15rem;font-weight:600;color:#14343A;margin:0 0 10px}
.conditions-page .condition-article-columns article>section p{font-size:.95rem;line-height:1.65;color:#3E5A5C}
.conditions-page .condition-article-columns article>section.condition-evidence{background:#F7FAF8}
.conditions-page .condition-read-also{display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:#F4F8F6;border:1px solid #E3EFE9;border-radius:14px;padding:12px 16px;margin-top:18px;font-size:.92rem}
.conditions-page .condition-read-also a{color:var(--teal-dark,#1F6E70);font-weight:600;text-decoration:none}
.conditions-page .condition-facts{margin:0}
.conditions-page .condition-facts>div{display:grid;grid-template-columns:200px 1fr;gap:14px;padding:10px 0;border-bottom:1px solid #EEF4F0}
.conditions-page .condition-facts>div:last-child{border-bottom:0;padding-bottom:2px}
.conditions-page .condition-facts dt{font-weight:600;color:#34565A;font-size:.92rem}
.conditions-page .condition-facts dd{margin:0;color:#22393C;font-size:.95rem}
.conditions-page .condition-timeline{margin:6px 0 0}
.conditions-page .condition-mini-map{height:300px;border:1px solid #E3EFE9;border-radius:18px;overflow:hidden;margin:12px 0}
.conditions-page .condition-sidebar>section{background:#fff;border:1px solid #E3EFE9;border-radius:20px;padding:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 6px 14px rgba(18,56,58,.06),0 20px 40px rgba(18,56,58,.09);margin-bottom:18px}
.conditions-page .condition-sidebar>section h2{font-family:Poppins,Inter,sans-serif;font-size:1.12rem;font-weight:600;color:#14343A;margin:0 0 10px}
.conditions-page .condition-sidebar>section p{font-size:.92rem;line-height:1.6;color:#49696B}
.conditions-page .condition-sidebar .eyebrow{font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--teal,#359FA0)}
.conditions-page .condition-sidebar>section.condition-report-cta{background:#FFF7E6;border-color:#F0DFC0}
.conditions-page .condition-sticky{border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 16px 38px rgba(18,56,58,.18)}
.conditions-page .condition-sticky button[data-share]{display:inline-flex;align-items:center;gap:7px;background:#fff;border:1px solid #DFE9E4;border-radius:999px;padding:8px 15px;font-family:Inter,sans-serif;font-size:.88rem;font-weight:600;color:#14343A;cursor:pointer}
#komentar>h2{font-family:Poppins,Inter,sans-serif;font-size:1.3rem;font-weight:600;color:#14343A}
#komentar .comment-card{border-color:#E3EFE9;border-radius:18px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 12px 26px rgba(18,56,58,.08)}
#komentar .comment-empty{background:#FAFCFB;border-color:#DCE9E3;border-radius:18px}
#komentar .comment-form{border-color:#E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 6px 14px rgba(18,56,58,.06),0 20px 40px rgba(18,56,58,.09)}
#komentar .comment-form label{font-family:Poppins,Inter,sans-serif;font-weight:600;font-size:.95rem}
#komentar .comment-form textarea{border-color:#DFE9E4;border-radius:14px;background:#fff}
#komentar .comment-form textarea:focus{border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
@media(max-width:760px){
 .conditions-page.condition-article{padding:18px 14px 6px}
 .conditions-page .condition-heading h1{font-size:1.6rem}
 .conditions-page .condition-article-columns article>section{padding:18px 16px;border-radius:18px}
 .conditions-page .condition-facts>div{grid-template-columns:1fr;gap:2px}
 .conditions-page .condition-mini-map{height:240px}
}
</style>
@endsection
