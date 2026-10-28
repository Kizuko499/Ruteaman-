@extends('admin.layout')
@section('title',__('Kirim Informasi'))
@section('content')
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('EMAIL & NOTIFIKASI') }}</p><h1>{{ __('Kirim Informasi') }}</h1><p>{{ __('Kirim email informasi ke alamat mana pun tanpa harus mendaftarkannya sebagai penerima notifikasi. Setiap penerima menerima emailnya sendiri-sendiri, langsung dikirim saat tombol ditekan, dan hasilnya tercatat di Riwayat Pengiriman sesuai jenis informasinya.') }}</p></div>

@include('admin.email._tabs', ['tab' => 'kirim'])

<form method="post" action="{{ route('admin.email.compose.send') }}" class="admin-editor" data-sensitive data-confirm="{{ __('Kirim email informasi ini sekarang?') }}" style="max-width:840px">
@csrf
<label>{{ __('Jenis informasi') }}
<select name="category" id="broadcast-category" required>
@foreach($categoryOptions as $opt)
<option value="{{ $opt['key'] }}" @selected(old('category', 'admin.broadcast') === $opt['key'])>{{ $opt['label'] }}@if($opt['all'] ?? false) — {{ __('semua penerima aktif') }}@elseif($opt['subs'] !== null) — {{ $opt['subs'] }} {{ __('penerima berlangganan') }}@endif</option>
@endforeach
</select></label>
<p class="admin-muted">{{ __('Pilih "Tulis sendiri" bila informasinya tidak termasuk jenis mana pun — isi bebas diketik di Isi informasi. Jenis lain sama seperti kategori langganan di Penerima Notifikasi dan menjadi label catatan di Riwayat. Bila targetnya "Penerima notifikasi", jenis berlangganan hanya dikirim ke penerima yang berlangganan jenis itu. Jenis umum situs (Eskalasi, Usulan institusi, Pengumuman, dan lainnya) tidak terikat langganan: email dikirim ke SEMUA penerima aktif yang terverifikasi.') }}</p>
<p><button type="button" class="admin-secondary" id="broadcast-template-btn">{{ __('Pakai template jenis ini') }}</button> <small class="admin-muted">{{ __('Mengisi Subjek & Isi dari pola siap pakai untuk jenis terpilih — teks dalam [kurung siku] tinggal kamu ganti dengan isi sebenarnya. Memilih jenis juga otomatis mengisi template bila Subjek dan Isi masih kosong.') }}</small></p>
<label>{{ __('Target penerima') }}
<select name="target" id="broadcast-target" required>
<option value="" @selected(!old('target'))>{{ __('Pilih target penerima…') }}</option>
<option value="manual" @selected(old('target') === 'manual')>{{ __('Alamat manual (ketik di bawah)') }}</option>
<option value="recipients" @selected(old('target') === 'recipients')>{{ __('Semua penerima notifikasi terverifikasi') }} ({{ $counts['recipients'] }})</option>
<option value="admins" @selected(old('target') === 'admins')>{{ __('Semua admin & staf aktif') }} ({{ $counts['admins'] }})</option>
<option value="users" @selected(old('target') === 'users')>{{ __('Semua pengguna terdaftar aktif') }} ({{ $counts['users'] }})</option>
</select></label>
<div id="manual-address-fields" style="{{ old('target') === 'manual' ? '' : 'display:none' }}">
<label>{{ __('Alamat manual') }}
<textarea name="addresses" rows="4" maxlength="20000" placeholder="nama@contoh.com, lain@contoh.com">{{ old('addresses') }}</textarea></label>
<p class="admin-muted">{{ __('Pisahkan dengan koma, titik koma, atau satu alamat per baris. Maksimal 200 alamat unik; alamat yang tidak valid akan dilaporkan dan tidak ada email yang terkirim sebelum diperbaiki.') }}</p>
</div>
<div id="compose-body-fields" style="{{ old('target') ? '' : 'display:none' }}">
<label>{{ __('Subjek') }}
<input name="subject" id="broadcast-subject" required maxlength="150" value="{{ old('subject') }}" placeholder="{{ __('cth. Pengumuman jadwal pemeliharaan') }}"></label>
<label>{{ __('Isi informasi') }}
<textarea name="body" id="broadcast-body" rows="12" required maxlength="5000" placeholder="{{ __('Tulis isi informasi di sini. Baris baru tampil sebagai baris baru di email.') }}">{{ old('body') }}</textarea></label>
<div class="admin-actions"><button class="admin-primary">{{ __('Kirim informasi') }}</button></div>
</div>
</form>
<script type="application/json" id="broadcast-templates">{!! json_encode($templates ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) !!}</script>
<script>
(function () {
    var target = document.getElementById('broadcast-target');
    var manual = document.getElementById('manual-address-fields');
    if (!target || !manual) { return; }
    var body = document.getElementById('compose-body-fields');
    function sync() {
        manual.style.display = target.value === 'manual' ? '' : 'none';
        if (body) { body.style.display = target.value !== '' ? '' : 'none'; }
    }
    target.addEventListener('change', sync);
    sync();
    var cat = document.getElementById('broadcast-category');
    var subj = document.getElementById('broadcast-subject');
    var bodyText = document.getElementById('broadcast-body');
    var tplBtn = document.getElementById('broadcast-template-btn');
    var templates = {};
    try { templates = JSON.parse(document.getElementById('broadcast-templates').textContent || '{}'); } catch (e) { templates = {}; }
    function applyTemplate(force) {
        if (!cat || !subj || !bodyText) { return; }
        var t = templates[cat.value];
        if (!t) { return; }
        if (force || (subj.value.trim() === '' && bodyText.value.trim() === '')) {
            subj.value = t.subject || '';
            bodyText.value = t.body || '';
        }
    }
    if (cat) { cat.addEventListener('change', function () { applyTemplate(false); }); }
    if (tplBtn) { tplBtn.addEventListener('click', function () { applyTemplate(true); }); }
})();
</script>
<p class="admin-muted" style="max-width:840px">{{ __('Email dikirim langsung satu per satu saat tombol ditekan — jangan tutup halaman sampai proses selesai. Karena dikirim langsung, sekali kirim dibatasi 200 alamat. Hasil tiap alamat (terkirim/gagal) tercatat di tab Riwayat Pengiriman; yang gagal dapat dikirim ulang dari sana.') }}</p>
@endsection
