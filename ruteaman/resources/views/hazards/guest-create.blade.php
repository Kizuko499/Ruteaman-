@extends('layouts.app')
@section('title','Lapor tanpa login')
@section('content')
<div class="form-page guest-report-page" id="guest-report-app">
 <a class="back" href="{{ route('map') }}">← Kembali ke peta</a>
 <p class="eyebrow">Laporan tamu · tanpa membuat akun</p><h1>Tandai kondisi yang kamu lihat</h1>
 <p class="disclaimer">Berhenti dahulu sebelum melapor. Laporan tamu masuk antrean pengelola dan belum tampil publik sampai diverifikasi. Tidak ada akun yang dibuat otomatis.</p>
 @if($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
 @if($schools->isEmpty())<p class="notice">Belum ada sekolah aktif dengan fitur pelaporan. Hubungi pengelola untuk menambahkan atau mengaktifkan sekolah.</p>@endif
 <form method="post" id="guest-report-form" action="{{ route('guest-reports.store') }}" enctype="multipart/form-data">@csrf
  <input type="hidden" name="_form_token" value="{{ $formToken }}">
  <div aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden"><label>Biarkan kosong<input name="website" tabindex="-1" autocomplete="off"></label></div>
  <section><h2>1. Pilih sekolah terkait</h2>
   <label>Sekolah / wilayah pemantauan<select name="school_id" id="guest-report-school" required><option value="">Pilih sekolah</option>@foreach($schools as $s)<option value="{{ $s->id }}" @selected($selected===(string)$s->id)>{{ $s->name }}{{ $s->npsn?' · '.$s->npsn:'' }}</option>@endforeach</select></label>
   <p>Daftar berisi sekolah yang sudah terdaftar di RuteAman dan pelaporannya aktif. Memilih sekolah tidak membuktikan kamu siswa atau anggota sekolah tersebut.</p>
   <label>Nama / nama samaran <small>opsional</small><input name="guest_name" maxlength="80" value="{{ old('guest_name') }}" autocomplete="off" placeholder="Kosongkan untuk Pelapor anonim"></label>
   <p>Nama hanya terlihat oleh pengelola yang berwenang, bukan di peta publik. Hindari nama lengkap siswa, nomor telepon, email, atau informasi pribadi.</p>
  </section>
  <section><h2>2. Tandai lokasi kondisi</h2><p>Ketuk titik kondisi di dalam wilayah sekolah. Jangan menandai rumah atau lokasi pribadi siswa.</p>
   <div id="guest-report-map" class="report-location-map" aria-label="Peta untuk memilih lokasi kondisi"></div>
   <p id="guest-location-status" role="status" aria-live="polite">Pilih sekolah terlebih dahulu, kemudian tentukan titik kondisi.</p>
   @include('partials.location-help')
   <button type="button" class="btn btn-soft" id="guest-use-location">Gunakan lokasi sekali</button>
   <details open><summary>Koordinat manual</summary><div class="two-fields"><label>Latitude<input name="latitude" id="guest-report-lat" value="{{ old('latitude') }}" type="number" step="any" min="-90" max="90" required></label><label>Longitude<input name="longitude" id="guest-report-lng" value="{{ old('longitude') }}" type="number" step="any" min="-180" max="180" required></label></div></details>
  </section>
  <section><h2>3. Pilih jenis kondisi</h2><div class="type-grid">@foreach($types as $type)<label><input type="radio" name="type_id" value="{{ $type->id }}" @checked(old('type_id')==$type->id) required><span>{{ match($type->code){'flood'=>'≈','pothole'=>'⌁','poor_lighting'=>'☾','fallen_tree'=>'♧',default=>'△'} }}</span><b>{{ $type->label }}</b></label>@endforeach</div></section>
  <section><h2>4. Tambahkan bukti dan kirim</h2>
   <label>Foto <small>opsional · maksimal 5 MB</small><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><p>Hindari wajah dan plat nomor. Foto diproses menjadi WebP dan metadata dibuang; foto ditolak jika sanitasi gagal.</p>
   <label>Label lokasi publik <small>opsional</small><input name="location_label" maxlength="120" value="{{ old('location_label') }}" placeholder="Contoh: jalan dekat gerbang sekolah"></label><p>Gunakan patokan umum, bukan alamat rumah atau identitas siswa.</p>
   <label>Keterangan <small>opsional</small><textarea name="description" maxlength="500" placeholder="Jelaskan kondisi tanpa identitas pribadi.">{{ old('description') }}</textarea></label>
   <label class="check"><input type="checkbox" name="report_consent" value="1" @checked(old('report_consent')) required> Saya mengirim laporan dengan itikad baik, menghindari data pribadi, dan memahami bahwa nama/sekolah belum terverifikasi.</label>
   <p>Batas pengiriman diterapkan untuk mengurangi spam. Laporan serupa di lokasi yang sama tidak dibuat ulang. <a href="{{ route('privacy.policy') }}">Kebijakan privasi</a>.</p>
   <button class="btn btn-dark" id="guest-report-submit" @disabled($schools->isEmpty())>Kirim untuk diperiksa</button>
  </section>
 </form>
 <p>Punya akun? <a href="{{ route('login') }}">Masuk untuk laporan akun dan konfirmasi</a>. Laporan tamu tidak muncul di menu Laporanku akun.</p>
</div>
<script type="application/json" id="guest-report-school-data">{!! json_encode($schools->map(fn($s)=>['id'=>$s->id,'name'=>$s->name,'latitude'=>(float)($s->latitude??0),'longitude'=>(float)($s->longitude??0),'catchment'=>json_decode($s->catchment_json??'null',true)]),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
@endsection
