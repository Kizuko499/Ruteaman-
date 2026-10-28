@extends('layouts.app')
@section('title',__('Beranda'))
@section('content')
{{-- home-mobile-flow:start --}}
<style data-ruteaman-home-mobile="v1">
/* Mobile-only natural flow: hero copy and route form must never overlap. */
@media (max-width: 900px) {
  .landing-wrap > .reference-hero {
    display: flex !important;
    flex-direction: column !important;
    position: relative;
    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;
    box-sizing: border-box;
    padding: 16px 16px 20px !important;
    margin: 0 !important;
    border-radius: 0 !important;
    overflow: visible;
    isolation: isolate;
  }
  .landing-wrap > .reference-hero > img,
  .landing-wrap > .reference-hero > .hero-shade {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    border-radius: 0 !important;
    pointer-events: none;
  }
  .landing-wrap > .reference-hero > img { object-fit: cover; object-position: center; z-index: 0; }
  .landing-wrap > .reference-hero > .hero-shade {
    z-index: 1;
    background: linear-gradient(100deg, rgba(10,43,45,.82), rgba(10,43,45,.64));
  }
  .reference-hero .floating-nav {
    position: relative !important;
    inset: auto !important;
    transform: none !important;
    width: 100% !important;
    max-width: none !important;
    box-sizing: border-box;
    min-height: 64px;
    margin: 0 0 16px !important;
    padding: 10px 84px 10px 16px !important;
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    gap: 12px;
    z-index: 4;
  }
  .reference-hero .floating-nav .nav-links { display: none !important; }
  .reference-hero .floating-nav .brand,
  .reference-hero .floating-nav .nav-actions { min-width: 0; }
  .reference-hero .hero-copy {
    position: relative !important;
    inset: auto !important;
    transform: none !important;
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    z-index: 2;
    min-width: 0;
    color: #fff;
  }
  .reference-hero .hero-copy .hero-chip {
    display: inline-block;
    max-width: 100%;
    box-sizing: border-box;
    white-space: normal;
    font-size: 13px;
    line-height: 1.5;
    padding: 8px 12px;
  }
  .reference-hero .hero-copy h1 {
    max-width: 100% !important;
    margin: 0 !important;
    font-size: clamp(32px, 9.8vw, 42px) !important;
    line-height: 1.12 !important;
    letter-spacing: -.025em;
    overflow-wrap: anywhere;
    text-wrap: balance;
  }
  .reference-hero .hero-copy p {
    max-width: 640px;
    margin: 0 !important;
    font-size: 16px !important;
    line-height: 1.6 !important;
    text-wrap: pretty;
  }
  .reference-hero .hero-copy .ra-liquid-cta {
    width: min(100%, 280px) !important;
    max-width: 100% !important;
    min-height: 54px;
    box-sizing: border-box;
    flex: 0 0 auto;
    margin: 4px 0 0 !important;
  }
  .reference-hero .floating-search {
    position: relative !important;
    inset: auto !important;
    transform: none !important;
    width: 100% !important;
    max-width: none !important;
    min-height: 0 !important;
    height: auto !important;
    margin: 16px 0 0 !important;
    padding: 16px !important;
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) !important;
    gap: 12px !important;
    box-sizing: border-box;
    border: 1px solid rgba(255,255,255,.8) !important;
    border-radius: 18px !important;
    background: linear-gradient(120deg, rgba(255,250,235,.96), rgba(255,255,255,.94)) !important;
    overflow: visible;
    z-index: 3;
  }
  .reference-hero .floating-search > canvas { opacity: .08 !important; pointer-events: none; }
  .reference-hero .floating-search > i,
  .reference-hero .floating-search .search-icon { display: none !important; }
  .reference-hero .floating-search label {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr);
    gap: 8px;
    min-width: 0;
  }
  .reference-hero .floating-search label span { font-size: 14px; line-height: 1.4; font-weight: 700; }
  .reference-hero .floating-search input {
    width: 100% !important;
    min-width: 0 !important;
    min-height: 48px !important;
    padding: 10px 12px !important;
    box-sizing: border-box;
    font-size: 16px !important;
    line-height: 1.4;
    color: #123e40;
    background: #fff !important;
    border: 1px solid #6d9291 !important;
    border-radius: 10px;
  }
  .reference-hero .floating-search > .btn { width: 100%; min-height: 48px; margin: 0; box-sizing: border-box; }
  .reference-hero .floating-search .landing-school-error { font-size: 14px; line-height: 1.5; margin: 0; }
  .reference-hero :focus-visible { outline: 3px solid #ffb478; outline-offset: 3px; }
  /* Footer ditumpuk: brand di atas, teks di bawah — sebelumnya berdempetan tanpa jarak */
  .landing-wrap > footer {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 14px !important;
    padding: 32px 20px !important;
  }
  .landing-wrap > footer p { margin: 0; max-width: 56ch; }
}
@media (max-width: 640px) {
  .reference-hero .floating-nav { padding-right: 16px !important; }
}
.floating-search label.hero-search{position:relative}
.hero-search__results{position:absolute;left:0;right:0;top:100%;z-index:60;margin:6px 0 0;padding:6px;list-style:none;background:#fffef9;border:1px solid #b9d2c9;border-radius:12px;box-shadow:0 18px 40px rgba(18,56,58,.22);max-height:320px;overflow:auto;text-align:left}
.hero-search__results[hidden]{display:none}
.hero-search__result-btn{display:grid;gap:2px;width:100%;padding:10px 12px;text-align:left;background:none;border:0;border-radius:8px;font:inherit;color:#12383a;cursor:pointer}
.hero-search__result-btn:hover,.hero-search__result-btn:focus-visible{background:#edf6f2}
.hero-search__result-btn span{font-size:15px;font-weight:600;line-height:1.35;overflow-wrap:anywhere}
.hero-search__result-btn small{font-size:12.5px;font-weight:400;color:#527775;line-height:1.4}
.hero-search__results{margin-top:10px;background:#fff;border-color:#E3EFE9;border-radius:18px;box-shadow:0 24px 48px rgba(18,56,58,.20)}
.hero-search__result-btn{border-radius:12px}
.hero-search__result-btn:hover,.hero-search__result-btn:focus-visible{background:#EAF4EE}
.hero-search__result-btn span{font-size:14.5px}
.hero-search__result-btn small{font-size:12px;color:#5F7A76}
/* Jangkar lapisan holo seksi unggulan: tanpa posisi relatif, lapisan ::before-nya
   lari ke awal halaman dan tampil sebagai pita pucat di area navbar bawah. */
.featured-section{position:relative}
/* Beranda layar penuh di HP: hilangkan zona cadangan 88px di bawah area konten
   (penyebab pita krem polos di belakang navbar bawah). Konten kini mengalir
   sampai tepi bawah layar dan navbar melayang di atasnya; ujung halaman diberi
   ruang lega di dalam footer agar tidak tertutup navbar saat digulir habis. */
@media (max-width: 640px) {
  body.publication-home{padding-bottom:0}
  body.publication-home #main{height:100dvh}
  .landing-wrap > footer{padding-bottom:calc(var(--mobile-nav-clearance) + env(safe-area-inset-bottom) + 16px) !important}
}
</style>
{{-- home-mobile-flow:end --}}

<div class="landing-wrap">
 <section class="reference-hero liquid-root holo-zone" data-liquid-root data-holo="true">
  @include('partials.nav')
  <img src="{{ asset('images/siswa-berangkat-ke-sekolah.webp') }}" width="1600" height="1067" alt="{{ __('Siswa berjalan menuju sekolah di tepi jalan') }}">
  <div class="hero-shade"></div>
  <div class="hero-copy">
   <span class="hero-chip"><i class="hero-chip__dot" aria-hidden="true"></i>{{ __('Papan kondisi rute institusi') }}</span>
   <h1>{{ __('Cek kondisi jalan sebelum berangkat.') }}</h1>
   <p>{{ __('Lihat laporan kondisi rute menuju sekolah atau kampus — dilaporkan warga, diverifikasi petugas, dan tampil langsung di peta.') }}</p>
   <x-liquid-cta :href="route('map')" label="Lihat peta dan bandingkan rute">{{ __('Lihat peta') }}</x-liquid-cta>
   @if(!empty($stats))<p class="hero-stats"><span><strong>{{ number_format($stats['schools'], 0, ',', '.') }}</strong> {{ __('sekolah') }}</span><i aria-hidden="true"></i><span><strong>{{ number_format($stats['campuses'], 0, ',', '.') }}</strong> {{ __('kampus terdaftar') }}</span></p>@endif
  </div>
  <style data-ruteaman-herosearch="v2">
  /* Panel saran hero melayang DI ATAS tombol Bandingkan (label jadi konteks
     tumpuk ber-z-index) dan teks hasil netral — tidak lagi mewarisi tipografi
     label (huruf besar berjarak lebar dari aturan label span). */
  .floating-search label.hero-search{position:relative;z-index:70}
  .floating-search label.hero-search:focus-within{z-index:80}
  .reference-hero .floating-search .hero-search__result-btn span{font-size:14.5px;font-weight:600;letter-spacing:0;text-transform:none;color:#12383A}
  .reference-hero .floating-search .hero-search__result-btn small{font-size:12px;font-weight:400;letter-spacing:0;text-transform:none;color:#5F7A76}
  /* Tombol x pembersih di dalam kolom: ketuk untuk mengosongkan kolom dan
     mulai pencarian baru; ruang kanan input disisakan agar teks tidak tertutup. */
  .floating-search label.hero-search .fs-tx{position:relative}
  .reference-hero .floating-search label.hero-search input{padding-right:46px!important}
  .hero-search__clear{position:absolute;right:10px;bottom:9px;width:30px;height:30px;border:0;border-radius:999px;background:#EAF4EE;color:#1F6E70;display:grid;place-items:center;cursor:pointer;padding:0;z-index:5}
  .hero-search__clear[hidden]{display:none}
  .hero-search__clear svg{width:14px;height:14px}
  .hero-search__clear:hover{background:#DDEEE6}
  </style>
  <form class="floating-search liquid-glass" data-glass-variant="search" action="{{ route('map') }}" method="get" data-search-endpoint="{{ route('schools.search') }}">
   <label class="hero-search fs-field"><span class="fs-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-6.8-5.6-6.8-10.4a6.8 6.8 0 0 1 13.6 0C18.8 15.4 12 21 12 21Z"/><circle cx="12" cy="10.6" r="2.3"/></svg></span><span class="fs-tx"><span>{{ __('Dari mana?') }}</span><input name="from" placeholder="{{ __('Tulis lokasi awal') }}" autocomplete="off" data-hero-origin-input role="combobox" aria-expanded="false" aria-controls="hero-origin-results" aria-autocomplete="list"><button type="button" class="hero-search__clear" data-hero-origin-clear aria-label="{{ __('Hapus pencarian') }}" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></span>
    <ul class="hero-search__results" id="hero-origin-results" data-hero-origin-results role="listbox" hidden></ul>
   </label>
   <input type="hidden" name="from_lat" data-hero-origin-lat>
   <input type="hidden" name="from_lng" data-hero-origin-lng>
   <i></i>
   <label class="hero-search fs-field"><span class="fs-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"/><path d="M5 18h14"/><path d="M7.5 18v-6.5"/><path d="M12 18v-6.5"/><path d="M16.5 18v-6.5"/><path d="M4 8.2 12 3l8 5.2"/><path d="M4.5 11.5h15"/></svg></span><span class="fs-tx"><span>{{ __('Ke institusi') }}</span><input name="to" value="{{ old('to', '') }}" placeholder="{{ __('Ketik nama institusi…') }}" autocomplete="off" maxlength="255" required data-hero-school-input role="combobox" aria-expanded="false" aria-controls="hero-school-results" aria-autocomplete="list"><button type="button" class="hero-search__clear" data-hero-school-clear aria-label="{{ __('Hapus pencarian') }}" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></span>
    <input type="hidden" name="school" data-hero-school-value>
    <ul class="hero-search__results" id="hero-school-results" data-hero-school-results role="listbox" hidden></ul>
    @error('to')<small class="landing-school-error" role="alert">{{ $message }}</small>@enderror
   </label>
   <button class="search-icon" aria-label="{{ __('Cari rute') }}">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 5 5"></path></svg>
   </button>
   <button class="btn btn-dark">{{ __('Bandingkan') }}</button>
  </form>
 </section>

<style data-ruteaman-welcome-photo="v1">
/* Bingkai foto sambutan: foto mengisi bingkai penuh (desain mockup welcome v1).
   Aturan lama membiarkan padding kaca 16px + tinggi foto tetap 290px sehingga
   foto terpotong di dalam bingkai rasio 16/10.5 pada layar <=900px. */
@media (max-width:900px){
 .welcome-photo.liquid-glass{padding:0}
 .welcome-photo.liquid-glass img{height:100%;min-height:0;border-radius:0}
}
</style>
 <section class="welcome-block liquid-root holo-zone" data-liquid-root data-holo="true">
  <div class="welcome-photo liquid-glass" data-glass-variant="media">
   <img src="{{ asset('images/banjir-genangan.webp') }}" width="900" height="675" alt="{{ __('Contoh data demo genangan di jalan') }}">
  </div>
  <div class="welcome-copy liquid-glass" data-glass-variant="content">
   <p class="eyebrow">{{ __('Informasi seperlunya') }}</p><h2>{{ __('Satu tempat untuk melihat kondisi rute sekolah') }}</h2>
   <p>{{ __('Setiap hazard menampilkan umur data, jumlah laporan, dan status konfirmasi. Tidak ada laporan berarti belum ada data—bukan jaminan kondisi jalan.') }}</p>
   <div class="welcome-actions"><a class="btn btn-dark" href="{{ route('map') }}">{{ __('Buka peta') }}</a><a class="text-link" href="#cara-kerja">{{ __('Pelajari cara kerja') }}</a></div>
  </div>
 </section>

 @include('pages.home-featured')
 @include('partials.feature-showcase')

 <section class="how-block liquid-root holo-zone" data-liquid-root data-holo="true" id="cara-kerja">
  <p class="eyebrow">{{ __('Cara kerja') }}</p><h2>{{ __('Informasi komunitas yang dapat ditindaklanjuti') }}</h2>
  <div class="steps"><article class="liquid-glass" data-glass-variant="card"><b>01</b><span class="step-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" /> <circle cx="12" cy="10" r="3" /></svg></span><h3>{{ __('Lapor') }}</h3><p>{{ __('Tandai lokasi dan pilih jenis hazard dalam maksimal empat langkah.') }}</p></article><article class="liquid-glass" data-glass-variant="card"><b>02</b><span class="step-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21.801 10A10 10 0 1 1 17 3.335" /> <path d="m9 11 3 3L22 4" /></svg></span><h3>{{ __('Konfirmasi') }}</h3><p>{{ __('Pengguna lain memberi kabar “Masih ada” atau “Sudah surut”.') }}</p></article><article class="liquid-glass" data-glass-variant="card"><b>03</b><span class="step-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z" /> <path d="m21.854 2.147-10.94 10.939" /></svg></span><h3>{{ __('Teruskan') }}</h3><p>{{ __('Sekolah membuat paket eskalasi tanpa identitas pelapor.') }}</p></article></div>
 </section>
 <section class="faq-block liquid-root holo-zone" data-liquid-root data-holo="true" aria-labelledby="faq-title">
  <p class="eyebrow">{{ __('Pertanyaan umum') }}</p><h2 id="faq-title">{{ __('Cara membaca informasi RuteAman') }}</h2>
  <div class="faq-list">
   <details class="liquid-glass" data-glass-variant="card"><summary>{{ __('Apakah rute dengan Beban Hazard rendah pasti bebas hambatan?') }}</summary><p>{{ __('Tidak. Angka hanya merangkum laporan aktif yang tersedia. Kondisi jalan dapat berubah dan belum semua hazard dilaporkan.') }}</p></details>
   <details class="liquid-glass" data-glass-variant="card"><summary>{{ __('Apakah lokasi saya terus dilacak?') }}</summary><p>{{ __('Tidak. Lokasi diminta satu kali untuk mengisi titik awal dan tidak disimpan sebagai riwayat perjalanan.') }}</p></details>
   <details class="liquid-glass" data-glass-variant="card"><summary>{{ __('Mengapa pilihan rute bisa berubah?') }}</summary><p>{{ __('Routing engine menghitung ulang graph jalan, durasi, dan hazard terbaru setiap kali titik awal, sekolah, atau moda diganti.') }}</p></details>
  </div>
 </section>
 <footer><a class="brand" href="{{ route('home') }}"><span class="brand-mark">R</span><b>Rute<span>Aman</span></b></a><p>{{ __('Informasi berasal dari laporan komunitas. Tetap perhatikan kondisi di lapangan.') }}</p></footer>
</div>
<script>
(function(){
  // Pencarian institusi pada bar hero: ketik nama -> pilih hasil -> id institusi
  // terkirim sebagai parameter school (peta langsung memakai id itu, tanpa
  // pencocokan nama persis / NPSN seperti sebelumnya).
  var form=document.querySelector('.floating-search');
  if(!form){return;}
  var input=form.querySelector('[data-hero-school-input]');
  var hidden=form.querySelector('[data-hero-school-value]');
  var results=form.querySelector('[data-hero-school-results]');
  var endpoint=form.getAttribute('data-search-endpoint');
  if(!input||!hidden||!results||!endpoint){return;}
  var timer=null,items=[],activeIdx=-1;
  function close(){results.hidden=true;input.setAttribute('aria-expanded','false');activeIdx=-1;}
  var clearBtn=form.querySelector('[data-hero-school-clear]');
  function syncClear(){if(clearBtn){clearBtn.hidden=!input.value;}}
  function pick(item){hidden.value=item.id;input.value=item.name;close();syncClear();}
  if(clearBtn){clearBtn.addEventListener('click',function(){input.value='';hidden.value='';items=[];render();syncClear();input.focus();});}
  syncClear();
  function render(){
    results.replaceChildren();
    items.forEach(function(item){
      var li=document.createElement('li');li.setAttribute('role','option');
      var b=document.createElement('button');b.type='button';b.className='hero-search__result-btn';
      var n=document.createElement('span');n.textContent=item.name;b.appendChild(n);
      if(item.meta){var m=document.createElement('small');m.textContent=item.meta;b.appendChild(m);}
      b.addEventListener('click',function(){pick(item);});
      li.appendChild(b);results.appendChild(li);
    });
    results.hidden=items.length===0;
    input.setAttribute('aria-expanded',String(items.length>0));
  }
  input.addEventListener('input',function(){
    hidden.value='';syncClear();
    var q=input.value.trim();clearTimeout(timer);
    if(q.length<2){items=[];render();return;}
    timer=setTimeout(function(){
      var u=new URL(endpoint,location.href);u.searchParams.set('q',q);
      fetch(u,{headers:{Accept:'application/json'}})
        .then(function(r){return r.ok?r.json():[];})
        .then(function(data){items=data;render();})
        .catch(function(){});
    },250);
  });
  input.addEventListener('keydown',function(e){
    if(e.key==='Escape'){close();return;}
    if(e.key==='ArrowDown'&&items.length){e.preventDefault();activeIdx=Math.min(activeIdx+1,items.length-1);return;}
    if(e.key==='ArrowUp'&&items.length){e.preventDefault();activeIdx=Math.max(activeIdx-1,0);return;}
    if(e.key==='Enter'&&!results.hidden&&items.length){e.preventDefault();pick(items[Math.max(0,activeIdx)]);}
  });
  document.addEventListener('click',function(e){if(!input.closest('label').contains(e.target)){close();}});

  // Saran tempat untuk titik awal: pola geocoding yang sama dengan pencarian
  // titik awal di halaman peta (OpenStreetMap Nominatim, khusus Indonesia).
  var oInput=form.querySelector('[data-hero-origin-input]');
  var oResults=form.querySelector('[data-hero-origin-results]');
  if(oInput&&oResults){
    var oTimer=null,oItems=[],oSeq=0;
    function oClose(){oResults.hidden=true;oInput.setAttribute('aria-expanded','false');}
    var oClearBtn=form.querySelector('[data-hero-origin-clear]');
    var oLat=form.querySelector('[data-hero-origin-lat]');
    var oLng=form.querySelector('[data-hero-origin-lng]');
    var oPicked='';
    function oSyncClear(){if(oClearBtn){oClearBtn.hidden=!oInput.value;}}
    function oPick(item){
      oInput.value=item.full3;
      if(oLat){oLat.value=item.lat||'';}
      if(oLng){oLng.value=item.lon||'';}
      oPicked=item.full3;
      oClose();oSyncClear();
    }
    if(oClearBtn){oClearBtn.addEventListener('click',function(){oInput.value='';oPicked='';if(oLat){oLat.value='';}if(oLng){oLng.value='';}oItems=[];oRender();oSyncClear();oInput.focus();});}
    oSyncClear();
    // Pindah kolom menutup panel kolom lainnya: hanya satu panel terbuka.
    oInput.addEventListener('focus',function(){close();});
    input.addEventListener('focus',function(){oClose();});
    function oRender(){
      oResults.replaceChildren();
      oItems.forEach(function(item){
        var li=document.createElement('li');li.setAttribute('role','option');
        var b=document.createElement('button');b.type='button';b.className='hero-search__result-btn';
        var n=document.createElement('span');n.textContent=item.name;b.appendChild(n);
        if(item.meta){var m=document.createElement('small');m.textContent=item.meta;b.appendChild(m);}
        b.addEventListener('click',function(){oPick(item);});
        li.appendChild(b);oResults.appendChild(li);
      });
      oResults.hidden=oItems.length===0;
      oInput.setAttribute('aria-expanded',String(oItems.length>0));
    }
    oInput.addEventListener('input',function(){
      oSyncClear();
      if(oInput.value!==oPicked){oPicked='';if(oLat){oLat.value='';}if(oLng){oLng.value='';}}
      var q=oInput.value.trim();clearTimeout(oTimer);
      if(q.length<3){oItems=[];oRender();return;}
      oTimer=setTimeout(function(){
        var seq=++oSeq;
        fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&countrycodes=id&q='+encodeURIComponent(q),{headers:{Accept:'application/json'}})
          .then(function(r){return r.ok?r.json():[];})
          .then(function(data){
            if(seq!==oSeq){return;}
            oItems=(data||[]).map(function(item){
              var parts=String(item.display_name||'').split(',').map(function(s){return s.trim();}).filter(Boolean);
              return {name:parts[0]||'Tempat tanpa nama',meta:parts.slice(1,3).join(', '),full3:parts.slice(0,3).join(', '),lat:item.lat,lon:item.lon};
            });
            oRender();
          })
          .catch(function(){});
      },450);
    });
    oInput.addEventListener('keydown',function(e){
      if(e.key==='Escape'){oClose();return;}
      if(e.key==='Enter'&&!oResults.hidden&&oItems.length){e.preventDefault();oPick(oItems[0]);}
    });
    document.addEventListener('click',function(e){if(!oInput.closest('label').contains(e.target)){oClose();}});
  }
})();
</script>
@endsection