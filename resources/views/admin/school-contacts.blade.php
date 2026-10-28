@extends('admin.layout')
@section('title',__('Kontak Institusi'))
@section('content')
<style data-ruteaman-admin-contacts="v1">
/* Kontak Institusi: kartu per institusi yang tenang + bilah pencarian
   penyaring daftar. Semua formulir simpan per institusi dipertahankan. */
.admin-main{background:#F6F8F6}
.admin-page-head h1{font-size:26px;font-weight:600;letter-spacing:-.015em}
.admin-page-head p{font-size:14px;color:#5F7A76;max-width:78ch}
.admin-eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0}
.admin-notice{background:#EFF7F1;border:1px solid #D9EAE0;border-radius:16px;padding:14px 16px;font-size:13.5px;line-height:1.6;color:#24484B;margin-bottom:16px}
.admin-notice a{color:#1F6E70;font-weight:600;text-decoration:none}
.admin-contact-search{background:#fff;border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:14px 16px;margin-bottom:16px}
.admin-contact-search-field{position:relative}
.admin-contact-search-field svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#7A918B;pointer-events:none}
.admin-contact-search-field input{width:100%;box-sizing:border-box;height:48px;border:1.5px solid #DFE9E4;border-radius:12px;padding:0 14px 0 42px!important;font:inherit;font-size:14px;background:#fff;color:#12383A}
.admin-contact-search-field input:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-contact-count{font-size:12.5px;color:#7A918B;margin:8px 2px 0}
.admin-contact-none{font-size:13.5px;color:#5F7A76;margin:8px 2px 0}
.school-contact-panel{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:22px;margin-bottom:16px}
.school-contact-panel h2{font-size:19px;font-weight:600;letter-spacing:-.01em;margin:0 0 4px}
.school-contact-panel>p{font-size:13px;color:#7A918B;margin:0 0 14px}
.school-contact-panel label{font-size:13px;font-weight:600;color:#49696B;display:grid;gap:6px}
.school-contact-panel label small{font-weight:400;color:#7A918B;font-size:12.5px;line-height:1.6}
.school-contact-panel input[type="text"],.school-contact-panel input:not([type]){width:100%;box-sizing:border-box;height:46px;border:1.5px solid #DFE9E4;border-radius:12px;padding:0 12px;font:inherit;font-size:14px;font-weight:400;background:#fff;color:#12383A}
.school-contact-panel input:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.school-contact-panel .admin-checkbox{display:inline-flex;align-items:center;gap:8px;font-weight:500;color:#24484B;margin-top:12px}
.school-contact-panel .admin-checkbox input{width:18px;height:18px;accent-color:#1F6E70}
.school-contact-panel .admin-actions{margin-top:14px}
.school-contact-panel .admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;border:0;border-radius:12px;min-height:44px;padding:0 18px;font-size:14px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-warning{background:#FDF6E9;border:1px solid #EFE0BC;border-radius:12px;padding:10px 12px;font-size:13px;color:#7A5A17}
.admin-topline .admin-secondary{border-radius:999px;border:1.5px solid #DFE9E4;background:#fff;min-height:38px;padding:0 14px;font-size:13px;font-weight:600;color:#14343A;text-decoration:none;display:inline-flex;align-items:center}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
</style>
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('KANAL RESMI') }}</p><h1>{{ __('Kontak Institusi') }}</h1><p>{{ __('Isi kontak bantuan yang muncul untuk pengguna institusi. Hanya institusi dalam cakupan akun yang ditampilkan.') }}</p></div><div class="admin-notice">{{ __('Kontak institusi berbeda dari email kontak pribadi admin. Gunakan nomor atau alamat layanan resmi yang memang boleh dipublikasikan.') }} <a href="{{ route('admin.guide') }}#kontak">{{ __('Lihat panduan pengisian') }}</a></div>
@if($schools->count() > 1)
<div class="admin-contact-search"><div class="admin-contact-search-field"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><input type="search" id="contact-search" placeholder="{{ __('Cari institusi berdasarkan nama atau NPSN') }}" aria-label="{{ __('Cari institusi berdasarkan nama atau NPSN') }}" autocomplete="off"></div><p class="admin-contact-count" id="contact-search-count">{{ __('Menampilkan semua') }} {{ $schools->count() }} {{ __('institusi') }}</p><p class="admin-contact-none" id="contact-search-none" hidden>{{ __('Tidak ada institusi yang cocok dengan pencarian ini.') }}</p></div>
@endif
@foreach($schools as $school)
@php($writable=app(\App\Services\PermissionService::class)->can(auth()->user(),'schools.manage',$school,true))
<section class="admin-panel school-contact-panel" data-contact-search="{{ \Illuminate\Support\Str::lower($school->name.' '.($school->npsn ?? '')) }}"><h2>{{ $school->name }}</h2><p>{{ $school->npsn?__('NPSN ').$school->npsn:__('NPSN belum diisi') }} · {{ $school->status==='active'?__('Aktif'):($school->status==='inactive'?__('Nonaktif'):$school->status) }}</p>
<form method="post" action="{{ route('admin.school-contacts.update',$school) }}" data-sensitive data-dirty-watch>@csrf @method('PATCH')<fieldset @disabled(!$writable)><label>{{ __('Kontak bantuan institusi') }}<input name="help_contact" maxlength="300" value="{{ $school->settings['help_contact']??'' }}" placeholder="{{ __('Tautan kanal resmi, email, atau nomor telepon') }}"><small>Contoh format: https://wa.me/ diikuti nomor resmi dengan kode negara tanpa tanda +; mailto: diikuti email resmi; atau nomor telepon sebagai teks. Kosongkan untuk memakai kontak bawaan platform.</small></label><label class="admin-checkbox"><input type="checkbox" name="publication_consent" value="1" required>{{ __('Saya berwenang mempublikasikan kontak resmi ini.') }}</label>@if($writable)<div class="admin-actions"><button class="admin-primary">{{ __('Simpan kontak institusi') }}</button></div>@endif</fieldset></form>@if(!$writable)<p class="admin-warning">{{ __('Baca-saja. Mengubah kontak membutuhkan izin Kelola institusi dan status institusi yang mengizinkan perubahan.') }}</p>@endif
</section>
@endforeach
@if($schools->isEmpty())<section class="admin-panel"><h2>{{ __('Belum ada institusi dalam cakupan') }}</h2><p>{{ __('Hubungi Administrator Utama untuk mengatur cakupan akun.') }}</p></section>@endif
<script>
(function () {
    var input = document.getElementById('contact-search');
    if (!input) { return; }
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-contact-search]'));
    var count = document.getElementById('contact-search-count');
    var none = document.getElementById('contact-search-none');
    var total = panels.length;
    function apply() {
        var q = input.value.trim().toLowerCase();
        var shown = 0;
        panels.forEach(function (p) {
            var hit = q === '' || p.getAttribute('data-contact-search').indexOf(q) !== -1;
            p.style.display = hit ? '' : 'none';
            if (hit) { shown++; }
        });
        if (count) { count.textContent = q === '' ? ('{{ __('Menampilkan semua') }} ' + total + ' {{ __('institusi') }}') : ('{{ __('Menampilkan') }} ' + shown + ' {{ __('dari') }} ' + total + ' {{ __('institusi') }}'); }
        if (none) { none.hidden = shown !== 0; }
    }
    input.addEventListener('input', apply);
})();
</script>
@endsection
