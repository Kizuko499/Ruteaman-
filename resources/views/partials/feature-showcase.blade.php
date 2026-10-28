@php($galleryGroups=app(\App\Services\HomeGalleryService::class)->feed()['groups'])
<section class="ra-showcase liquid-root" data-liquid-root data-showcase data-theme="light" data-holo="true" aria-labelledby="showcase-title">
 <header class="ra-showcase__header">
  <div><p class="eyebrow">{{ __('Jelajahi fitur') }}</p><h2 id="showcase-title">{{ __('Satu alur untuk memahami kondisi rute') }}</h2></div>
   </header>

 <div class="ra-showcase-grid">
  <article class="ra-project ra-project--full liquid-glass" data-glass-variant="card">
   @include('partials.home-gallery-slots',['galleryKey'=>'routes'])
   <div class="ra-project__content">
    <p class="ra-step"><span>01</span><i aria-hidden="true"></i></p>
    <h3>{{ __('Bandingkan alternatif rute') }}</h3>
    <p>{{ __('Pilih titik awal, sekolah, dan moda. Graph routing menampilkan beberapa jalur, durasi, jarak, serta Beban Hazard tanpa memberi klaim jaminan kondisi jalan.') }}</p>
    <ul class="ra-tags" aria-label="{{ __('Fitur perbandingan rute') }}"><li>{{ __('Peta interaktif') }}</li><li>{{ __('Graph routing') }}</li><li>{{ __('Beban Hazard') }}</li><li>{{ __('Petunjuk arah') }}</li></ul>
   </div>
  </article>

  <article class="ra-project liquid-glass" data-glass-variant="card">
   @include('partials.home-gallery-slots',['galleryKey'=>'reporting'])
   <div class="ra-project__content"><p class="ra-step"><span>02</span><i aria-hidden="true"></i></p><h3>{{ __('Lapor dalam empat langkah') }}</h3><p>{{ __('Tandai lokasi, pilih kategori, tambahkan foto opsional, lalu periksa dan kirim.') }}</p><ul class="ra-tags" aria-label="{{ __('Fitur pelaporan') }}"><li>{{ __('Lokasi sekali pakai') }}</li><li>{{ __('Foto privat') }}</li><li>{{ __('Mobile-first') }}</li></ul></div>
  </article>

  <article class="ra-project liquid-glass" data-glass-variant="card">
   @include('partials.home-gallery-slots',['galleryKey'=>'followup'])
   <div class="ra-project__content"><p class="ra-step"><span>03</span><i aria-hidden="true"></i></p><h3>{{ __('Konfirmasi dan tindak lanjut') }}</h3><p>{{ __('Komunitas mengonfirmasi kondisi, Admin Sekolah memeriksa bukti, dan sekolah membuat paket eskalasi tanpa identitas pelapor.') }}</p><ul class="ra-tags" aria-label="{{ __('Fitur verifikasi') }}"><li>{{ __('Satu ketuk') }}</li><li>{{ __('Antrean prioritas') }}</li><li>{{ __('Privasi pelapor') }}</li></ul></div>
  </article>
 </div>
</section>