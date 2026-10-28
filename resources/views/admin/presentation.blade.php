@extends('admin.layout')
@section('title',__('Tampilan & Kontak'))
@section('content')
<style data-ruteaman-admin-presentation="v1">
.admin-main{background:#F6F8F6}
.admin-main .admin-panel{border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06)}
.admin-main .admin-panel h2{font-size:17px}
.admin-main .admin-panel label{font-size:13px;font-weight:600;color:#33554F}
.admin-main .admin-panel label small{font-size:12px;color:#6B837C;font-weight:400}
.admin-main .admin-panel input:not([type=checkbox]):not([type=file]){border:1px solid #CBDAD5;border-radius:12px;min-height:48px;padding:10px 12px;background:#fff;width:100%;box-sizing:border-box}
.admin-main .admin-panel input:focus{outline:0;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-main .admin-panel input[type=file]{border:1.5px dashed #B9CFC8;border-radius:14px;padding:12px 14px;background:#FBFDFC;width:100%;box-sizing:border-box;font-weight:400;color:#33554F}
.admin-main .admin-panel input[type=file]::file-selector-button{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:10px;padding:9px 16px;font-weight:600;margin-right:12px;cursor:pointer}
.admin-main .admin-panel button.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:12px;min-height:46px;padding:10px 20px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-main .admin-panel button.admin-secondary,.admin-main .admin-panel a.admin-secondary{background:#fff;border:1px solid #CBDAD5;color:#1F4A4C;border-radius:12px;min-height:46px;padding:10px 18px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}
.admin-main .admin-panel button.admin-secondary:hover,.admin-main .admin-panel a.admin-secondary:hover{background:#F2F8F4}
.admin-main .admin-checkbox{display:flex;gap:10px;align-items:flex-start;background:#F2F8F4;border:1px solid #DDEAE4;border-radius:12px;padding:12px 14px;font-size:13.5px}
.admin-main .admin-checkbox input{accent-color:#1F6E70;width:18px;height:18px;margin-top:1px;flex:none}
.admin-main .presentation-reset{margin-top:14px}
.lp-badge{display:inline-flex;align-items:center;border-radius:999px;padding:5px 12px;font-size:12.5px;font-weight:700;background:#EAF4EE;color:#1F6E70;border:1px solid #CBDFD2}
.lp-badge.is-custom{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;border-color:transparent}
.lp-apply{display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;margin:4px 0 6px}
.lp-device{display:flex;gap:8px;margin:14px 0 10px}
.lp-devbtn{min-height:40px;padding:7px 18px;border-radius:999px;border:1px solid #CBDAD5;background:#fff;color:#1F4A4C;font-weight:600;font-size:13.5px;cursor:pointer}
.lp-devbtn[aria-pressed="true"]{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border-color:transparent;color:#fff;box-shadow:0 6px 14px rgba(31,110,112,.22)}
.lp-stage{display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;max-width:860px;transition:max-width .2s ease}
.lp-stage-mobile{max-width:380px;grid-template-columns:1fr}
.lp-fig{margin:0;min-width:0}
.lp-fig img{width:100%;height:auto;border-radius:16px;border:1px solid #E3EFE9;display:block}
.lp-fig figcaption{font-size:12.5px;color:#6B837C;margin-top:6px}
.lp-fig-new img{outline:3px dashed #D97B36;outline-offset:-3px}
.lp-dirty{display:none;margin-top:12px;padding:10px 14px;border-radius:12px;background:#FBF3E4;border:1px solid #EAD9B8;color:#8A6D1F;font-size:13.5px;font-weight:600}
.lp-dirty.is-on{display:inline-flex}
.lp-filemeta{font-size:13.5px;color:#51706B;margin:2px 0 0}
.lp-fileerror{display:none;color:#B4552D;font-size:13.5px;font-weight:600;margin:2px 0 0}
.lp-fileerror.is-on{display:block}
.lp-side{background:linear-gradient(180deg,#0F4447,#093033);color:#fff;border-radius:18px;padding:18px 12px;max-width:300px;box-shadow:0 16px 32px rgba(9,48,51,.22)}
.lp-side nav{display:grid;gap:4px}
.lp-side nav a{display:flex;align-items:center;gap:12px;min-height:46px;padding:10px 12px;border-radius:10px;color:#DCEBE6;font-size:14.5px;text-decoration:none}
.lp-side nav a:hover{background:#285658;color:#fff}
.lp-side nav a[aria-current="page"]{background:#E0EEE3;color:#163C3D;font-weight:700}
.lp-cols{display:grid;grid-template-columns:minmax(0,300px) minmax(0,1fr);gap:24px;align-items:start}
.admin-main .presentation-panel{grid-template-columns:1fr}
.admin-main .presentation-panel form{max-width:680px}
.admin-main #platform-contact form{max-width:760px}
.admin-main .admin-panel label.admin-checkbox{flex-direction:row;align-items:flex-start}
.admin-main .presentation-reset button{justify-self:start}
@media(min-width:1101px){
.admin-main .presentation-panel{grid-template-columns:minmax(0,6fr) minmax(0,5fr);column-gap:40px}
.admin-main .presentation-panel>.admin-section-head{grid-column:1/-1}
.admin-main .presentation-panel>.lp-apply{grid-column:1;grid-row:2}
.admin-main .presentation-panel>.lp-device{grid-column:1;grid-row:3}
.admin-main .presentation-panel>.lp-device-note{grid-column:1;grid-row:4;margin:0}
.admin-main .presentation-panel>.lp-stage{grid-column:1;grid-row:5;max-width:none}
.admin-main .presentation-panel>form:not(.presentation-reset){grid-column:2;grid-row:2/6;max-width:none;align-self:start}
.admin-main .presentation-panel>.presentation-reset{grid-column:1;grid-row:6}
.admin-main .presentation-panel>p.admin-muted:not(.lp-device-note){grid-column:1;grid-row:7}
}
.admin-main .lp-stage:has(> .lp-fig-new[hidden]){grid-template-columns:1fr}
.admin-main .lp-stage.lp-stage-mobile{grid-template-columns:1fr;max-width:380px}
.admin-main #platform-contact form{max-width:980px}
@media(max-width:760px){.lp-stage{grid-template-columns:1fr}.lp-cols{grid-template-columns:1fr}}
</style>
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('ADMINISTRATOR UTAMA') }}</p><h1>{{ __('Tampilan & Kontak') }}</h1><p>{{ __('Ganti foto halaman masuk dan daftar dengan pratinjau langsung sebelum disimpan, periksa pratinjau menu samping, dan isi saluran bantuan umum. Tidak memengaruhi koleksi Foto Beranda.') }}</p>@if($settings?->updated_at)<p class="admin-muted">{{ __('Pengaturan tampilan terakhir diubah:') }} {{ $settings->updated_at->format('d/m/Y H:i') }} WIB.</p>@endif</div>
@php($targets=[
 ['id'=>'login','title'=>__('Foto halaman masuk'),'where'=>__('Halaman masuk publik — gambar besar di sisi kiri formulir pada komputer; pada HP foto disembunyikan agar formulir ringkas.'),'page'=>route('login'),'pageLabel'=>__('Lihat halaman masuk'),'photo'=>$photo,'custom'=>(bool)($settings?->login_photo_path),'alt'=>old('alt',$settings?->login_photo_alt??'Ilustrasi perjalanan ke sekolah'),'action'=>route('admin.presentation.photo'),'reset'=>route('admin.presentation.reset'),'submit'=>__('Ganti foto halaman masuk'),'resetLabel'=>__('Kembali ke foto bawaan'),'confirm'=>__('Kembalikan foto halaman masuk ke ilustrasi bawaan?')],
 ['id'=>'register','title'=>__('Foto halaman daftar'),'where'=>__('Halaman daftar publik — gambar besar di sisi kiri formulir pada komputer; pada HP foto disembunyikan agar formulir ringkas.'),'page'=>route('register'),'pageLabel'=>__('Lihat halaman daftar'),'photo'=>$registerPhoto,'custom'=>(bool)($settings?->register_photo_path),'alt'=>old('alt',$settings?->register_photo_alt??'Ilustrasi perjalanan ke sekolah'),'action'=>route('admin.presentation.register-photo'),'reset'=>route('admin.presentation.register-photo.reset'),'submit'=>__('Ganti foto halaman daftar'),'resetLabel'=>__('Kembali ke foto bawaan'),'confirm'=>__('Kembalikan foto halaman daftar ke ilustrasi bawaan?')],
])
@foreach($targets as $t)
<section class="admin-panel presentation-panel" id="foto-{{ $t['id'] }}">
<div class="admin-section-head"><h2>{{ $t['title'] }}</h2><span class="lp-badge{{ $t['custom']?' is-custom':'' }}">{{ $t['custom']?__('Foto unggahan admin'):__('Foto bawaan') }}</span></div>
<p class="lp-apply"><strong>{{ __('Lokasi penerapan:') }}</strong><span>{{ $t['where'] }}</span><a class="admin-secondary" href="{{ $t['page'] }}" target="_blank" rel="noopener">{{ $t['pageLabel'] }}</a></p>
<div class="lp-device" role="group" aria-label="{{ __('Ukuran pratinjau') }}">
<button type="button" class="lp-devbtn" data-device-btn data-scope="{{ $t['id'] }}" data-device="desktop" aria-pressed="true">{{ __('Pratinjau lebar') }}</button>
<button type="button" class="lp-devbtn" data-device-btn data-scope="{{ $t['id'] }}" data-device="mobile" aria-pressed="false">{{ __('Pratinjau sempit') }}</button>
</div>
<p class="admin-muted lp-device-note">{{ __('Tombol ini hanya mengubah ukuran pratinjau di halaman ini — fotonya tetap satu. Di halaman publik, foto ini hanya tampil di komputer; di HP foto disembunyikan agar formulir ringkas.') }}</p>
<div class="lp-stage" data-stage="{{ $t['id'] }}">
<figure class="lp-fig"><img src="{{ $t['photo']['url'] }}" alt="{{ $t['photo']['alt'] }}" width="720" height="600"><figcaption>{{ __('Saat ini — foto yang tampil di halaman publik.') }}</figcaption></figure>
<figure class="lp-fig lp-fig-new" data-newfig="{{ $t['id'] }}" hidden><img data-newimg="{{ $t['id'] }}" alt="{{ __('Pratinjau foto baru') }}"><figcaption>{{ __('Pratinjau baru — belum disimpan, halaman publik belum berubah.') }}</figcaption></figure>
</div>
<form method="post" action="{{ $t['action'] }}" enctype="multipart/form-data" data-sensitive data-dirty-watch data-photo-form data-scope="{{ $t['id'] }}">@csrf
<label>{{ __('Foto baru') }}<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required data-photo-input="{{ $t['id'] }}"></label>
<p class="lp-filemeta" data-filemeta="{{ $t['id'] }}" hidden></p>
<p class="lp-fileerror" data-fileerror="{{ $t['id'] }}" role="alert"></p>
<p class="admin-muted">{{ __('JPEG/PNG/WebP, maksimal 5 MB dan 12 juta piksel. Foto diproses ulang menjadi WebP tanpa metadata. Foto yang terlalu lebar akan dipotong oleh tampilan; taruh objek penting di tengah.') }}</p>
<label>{{ __('Deskripsi gambar') }}<input name="alt" maxlength="140" value="{{ $t['alt'] }}" required data-alt-input="{{ $t['id'] }}"><small>{{ __('Deskripsi singkat untuk pembaca layar; bukan nama berkas.') }}</small></label>
<label class="admin-checkbox"><input type="checkbox" name="publication_consent" value="1" required>{{ __('Saya berhak menggunakan foto ini dan sudah memeriksa agar layak ditampilkan kepada publik.') }}</label>
<div class="admin-actions"><button class="admin-primary">{{ $t['submit'] }}</button><button type="button" class="admin-secondary" data-cancel-pick="{{ $t['id'] }}" hidden>{{ __('Batalkan pilihan foto') }}</button></div>
<span class="lp-dirty" data-dirty-badge="{{ $t['id'] }}">{{ __('Ada perubahan yang belum disimpan — pratinjau di atas belum berlaku di halaman publik.') }}</span>
</form>
<form method="post" action="{{ $t['reset'] }}" data-sensitive data-confirm="{{ $t['confirm'] }}" class="presentation-reset">@csrf @method('DELETE')<button class="admin-secondary">{{ $t['resetLabel'] }}</button></form>
<p class="admin-muted">{{ __('Penggantian ini manual, bukan berganti otomatis. Setiap unggahan menggantikan foto sebelumnya dan foto lama dihapus.') }}</p>
</section>
@endforeach
<section class="admin-panel" id="pratinjau-sidebar">
<div class="admin-section-head"><h2>{{ __('Pratinjau menu samping administrator') }}</h2><span class="lp-badge">{{ __('Simulasi') }}</span></div>
<div class="lp-cols">
<div class="lp-side" data-preview-nav><nav>
<x-admin-nav-item href="#lp-ringkasan" :label="__('Ringkasan')" icon="layout-dashboard" :active="true" />
<x-admin-nav-item href="#lp-sekolah" :label="__('Institusi')" icon="school" />
<x-admin-nav-item href="#lp-laporan" :label="__('Laporan / Kondisi')" icon="clipboard-list" />
<x-admin-nav-item href="#lp-email" :label="__('Email & Notifikasi')" icon="mail" />
<x-admin-nav-item href="#lp-usulan" :label="__('Usulan Institusi')" icon="inbox" />
<x-admin-nav-item href="#lp-tampilan" :label="__('Tampilan & Kontak')" icon="palette" />
<x-admin-nav-item href="#lp-foto" :label="__('Foto Beranda')" icon="images" />
<x-admin-nav-item href="#lp-akun" :label="__('Akun saya')" icon="user-round-cog" />
</nav></div>
<div>
<p>{{ __('Ini contoh tampilan menu samping dengan set ikon baru (Lucide) yang dipakai komponen navigasi sebenarnya — menu aktif ditandai seperti aslinya. Klik pada contoh ini tidak berpindah halaman.') }}</p>
<p class="admin-muted">{{ __('Lokasi penerapan: menu samping konsol administrator di semua halaman admin. Ikon ditetapkan di kode program agar konsisten dan tidak bisa tertukar antarmenu; mengganti ikon di luar set ini adalah perubahan kode oleh pengembang, bukan pengaturan dari halaman ini.') }}</p>
</div>
</div>
</section>
<section class="admin-panel" id="platform-contact"><h2>{{ __('Kontak bantuan platform') }}</h2><p>{{ __('Dipakai pada halaman Bantuan bila pengunjung tidak terhubung ke institusi atau institusinya belum mempunyai kontak khusus. Ini saluran yang terlihat publik, bukan alias login atau email privat akun admin.') }}</p><form method="post" action="{{ route('admin.presentation.contact') }}" data-sensitive data-dirty-watch>@csrf @method('PATCH')<label>{{ __('Kontak resmi') }}<input name="help_contact" maxlength="300" value="{{ old('help_contact',$settings?->help_contact) }}" placeholder="https://wa.me/nomor-resmi atau mailto:email-resmi"><small>{{ __('Isi tautan HTTPS (misalnya saluran WhatsApp resmi), mailto:alamat-email, atau nomor telepon sebagai teks. Kosongkan untuk kembali ke kontak konfigurasi lama bila ada.') }}</small></label><label class="admin-checkbox"><input type="checkbox" name="publication_consent" value="1" required>{{ __('Saya memastikan kontak ini resmi dan boleh ditampilkan di halaman bantuan.') }}</label><div class="admin-actions"><button class="admin-primary">{{ __('Simpan kontak platform') }}</button><a class="admin-secondary" href="{{ route('admin.school-contacts') }}">{{ __('Isi kontak per institusi') }}</a><a class="admin-secondary" href="{{ route('admin.guide') }}#kontak">{{ __('Cara mengisi kontak') }}</a></div></form></section>
<script>
(function(){
  var urls={};
  function q(sel){return document.querySelector(sel);}
  function clearPick(scope,input){
    if(urls[scope]){URL.revokeObjectURL(urls[scope]);delete urls[scope];}
    if(input){input.value='';}
    var f=q('[data-newfig="'+scope+'"]');if(f){f.hidden=true;}
    var m=q('[data-filemeta="'+scope+'"]');if(m){m.hidden=true;m.textContent='';}
    var b=q('[data-cancel-pick="'+scope+'"]');if(b){b.hidden=true;}
  }
  document.querySelectorAll('[data-photo-input]').forEach(function(input){
    var scope=input.getAttribute('data-photo-input');
    input.addEventListener('change',function(){
      var e=q('[data-fileerror="'+scope+'"]');e.classList.remove('is-on');e.textContent='';
      var file=input.files&&input.files[0];
      if(!file){clearPick(scope,null);return;}
      var okTypes=['image/jpeg','image/png','image/webp'];
      if(okTypes.indexOf(file.type)===-1){e.textContent='Format tidak didukung. Pilih berkas JPEG, PNG, atau WebP.';e.classList.add('is-on');clearPick(scope,input);return;}
      if(file.size>5*1024*1024){e.textContent='Ukuran foto melebihi 5 MB. Pilih berkas yang lebih kecil.';e.classList.add('is-on');clearPick(scope,input);return;}
      if(urls[scope]){URL.revokeObjectURL(urls[scope]);}
      urls[scope]=URL.createObjectURL(file);
      q('[data-newimg="'+scope+'"]').src=urls[scope];
      q('[data-newfig="'+scope+'"]').hidden=false;
      var m=q('[data-filemeta="'+scope+'"]');m.hidden=false;m.textContent='Berkas dipilih: '+file.name+' ('+Math.max(1,Math.round(file.size/1024))+' KB) — belum diunggah.';
      q('[data-cancel-pick="'+scope+'"]').hidden=false;
      q('[data-dirty-badge="'+scope+'"]').classList.add('is-on');
    });
  });
  document.querySelectorAll('[data-cancel-pick]').forEach(function(btn){
    var scope=btn.getAttribute('data-cancel-pick');
    btn.addEventListener('click',function(){
      clearPick(scope,q('[data-photo-input="'+scope+'"]'));
      q('[data-dirty-badge="'+scope+'"]').classList.remove('is-on');
    });
  });
  document.querySelectorAll('[data-alt-input]').forEach(function(input){
    var scope=input.getAttribute('data-alt-input');
    input.addEventListener('input',function(){q('[data-dirty-badge="'+scope+'"]').classList.add('is-on');});
  });
  document.querySelectorAll('[data-device-btn]').forEach(function(btn){
    btn.addEventListener('click',function(){
      var scope=btn.getAttribute('data-scope');
      var st=q('[data-stage="'+scope+'"]');if(!st){return;}
      st.classList.toggle('lp-stage-mobile',btn.getAttribute('data-device')==='mobile');
      document.querySelectorAll('[data-device-btn][data-scope="'+scope+'"]').forEach(function(b){b.setAttribute('aria-pressed',b===btn?'true':'false');});
    });
  });
  document.querySelectorAll('[data-preview-nav] a').forEach(function(a){
    a.addEventListener('click',function(ev){ev.preventDefault();});
  });
})();
</script>
@endsection
