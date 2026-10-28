@extends('layouts.app')
@section('title',__('Lapor hazard'))
@section('content')
<div class="form-page" id="hazard-report-app" data-duplicate-url="{{ route('hazards.duplicate') }}" data-lat="{{ $school?->lat ?? -6.1754 }}" data-lng="{{ $school?->lng ?? 106.8272 }}">
 @include('partials.report-form-style')
 <a class="back" href="{{ route('map') }}">{{ __('← Kembali ke peta') }}</a>
 <p class="eyebrow">{{ __('Lapor ·') }} {{ auth()->user()->school?->name }}</p><h1>{{ __('Tandai kondisi yang kamu lihat') }}</h1>
 <p class="disclaimer">{{ __('Berhenti di tempat yang sesuai sebelum melapor. Jangan melapor sambil berkendara. Lokasi hanya untuk titik hazard, bukan riwayat perjalanan.') }}</p>
 <p class="disclaimer">{{ app(\App\Services\ReportPublicationPolicy::class)->requiresReview(auth()->user()->school,false)?__('Laporan baru menunggu pemeriksaan pengelola sebelum tampil publik. Kamu tetap bisa melacaknya di Laporanku.'):__('Laporan baru langsung tampil sebagai belum terverifikasi. Pengelola dapat menangguhkan atau menarik laporan yang keliru.') }}</p>
 @if($errors->any())<div class="error" role="alert">{{ __($errors->first()) }}</div>@endif
 <form method="post" id="hazard-report-form" action="{{ route('hazards.store') }}" enctype="multipart/form-data">@csrf
  <section><h2>{{ __('1. Tandai lokasi') }}</h2><p>{{ __('Ketuk titik hazard di peta. Jangan gunakan alamat rumah atau lokasi siswa.') }}</p><div id="report-location-map" class="report-location-map" aria-label="{{ __('Peta untuk memilih titik hazard') }}"></div>
   @include('partials.location-help')
   <button type="button" class="btn btn-soft" id="report-use-location">{{ __('Gunakan lokasi sekali') }}</button>
   <details open><summary>{{ __('Koordinat manual / persiapan demo') }}</summary><div class="two-fields"><label>{{ __('Latitude') }}<input name="latitude" id="report-lat" value="{{ old('latitude') }}" type="number" step="any" min="-90" max="90" required></label><label>{{ __('Longitude') }}<input name="longitude" id="report-lng" value="{{ old('longitude') }}" type="number" step="any" min="-180" max="180" required></label></div></details>
  </section>
  <section><h2>{{ __('2. Pilih jenis hazard') }}</h2><div class="type-grid">@foreach($types as $type)<label><input type="radio" name="type_id" value="{{ $type->id }}" @checked(old('type_id')==$type->id) required><span class="report-type-icon"><x-condition-symbol :condition="$type"/></span><b>{{ __($type->label) }}</b></label>@endforeach</div></section>
  <section><h2>{{ __('3. Tambahkan foto') }} <small>{{ __('opsional') }}</small></h2><p>{{ __('Hindari wajah dan plat nomor. Metadata GPS dibuang; jika pemrosesan foto gagal, foto ditolak, bukan disimpan mentah.') }}</p><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><small>{{ __('Maksimal 5 MB.') }}</small></section>
  <section><h2>{{ __('4. Kirim') }}</h2><label>{{ __('Label lokasi publik (opsional)') }}<input name="location_label" maxlength="120" value="{{ old('location_label') }}" placeholder="{{ __('Contoh: Jl. Mawar, depan pasar') }}"></label><p>{{ __('Hanya nama jalan atau patokan umum. Jangan tulis alamat rumah atau identitas siswa.') }}</p><label>{{ __('Keterangan') }} <small>{{ __('opsional') }}</small><textarea name="description" maxlength="500" placeholder="{{ __('Contoh: genangan menutup sisi kiri jalan. Tanpa nama siswa.') }}">{{ old('description') }}</textarea></label>
   <p id="duplicate-status" role="status" aria-live="polite"></p>
   <button class="btn btn-dark" id="report-submit">{{ __('Kirim laporan') }}</button>
  </section>
 </form>
 <section id="duplicate-panel" class="duplicate-panel" hidden><h2>{{ __('Sudah ada laporan serupa di dekat sini. Masih ada?') }}</h2><p>{{ __('Konfirmasi tidak menambah laporan baru. Satu pengguna dihitung satu kali.') }}</p><a id="duplicate-detail" class="text-link">{{ __('Lihat laporan') }}</a><form method="post" id="duplicate-confirm-form">@csrf<button class="btn btn-dark" name="vote" value="still_there">{{ __('◎ Masih ada') }}</button></form></section>
</div>
@endsection
