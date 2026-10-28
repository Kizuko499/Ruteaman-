@extends('admin.layout')
@section('title',__('Fitur Umum & Sekolah'))
@section('content')
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<style data-ruteaman-admin-features="v1">
/* Fitur Umum & Sekolah: panel tenang, saklar modern, daftar institusi
   sebagai kartu lipat yang rapi. Semua formulir & saklar dipertahankan. */
.admin-main{background:#F6F8F6}
.admin-page-head h1{font-size:26px;font-weight:600;letter-spacing:-.015em}
.admin-page-head p{font-size:14px;color:#5F7A76;max-width:78ch}
.admin-eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0}
.admin-panel{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:22px}
.admin-section-head h2{font-size:17px;font-weight:600;letter-spacing:-.01em}
.admin-section-head span{font-size:12px;font-weight:700;color:#1F6E70;background:#EAF4EE;border-radius:999px;padding:4px 10px}
.admin-panel>p{font-size:13.5px;color:#5F7A76}
.admin-notice{background:#EFF7F1;border:1px solid #D9EAE0;border-radius:14px;padding:12px 14px;font-size:13px;color:#33544C}
.admin-feature-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 32px}
.admin-switch-row{border-bottom:1px solid #EAF1EC;padding:16px 0;min-height:0;gap:14px}
.admin-switch-track{width:46px;height:27px;border:0;background:#DCE7E0;border-radius:999px}
.admin-switch-track:after{top:3px;left:3px;width:21px;height:21px;background:#fff;box-shadow:0 1px 3px rgba(18,56,58,.25)}
.admin-switch-row input:checked+.admin-switch-track{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0}
.admin-switch-row input:checked+.admin-switch-track:after{transform:translateX(19px)}
.admin-switch-copy strong{font-size:14px;font-weight:600;color:#12383A}
.admin-switch-copy small{font-size:12.5px;color:#7A918B;line-height:1.45}
.admin-switch-state{font-size:12.5px;font-weight:700;color:#7A918B}
.admin-switch-row:has(input:checked) .admin-switch-state{color:#1F6E70}
.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;border:0;border-radius:12px;min-height:44px;padding:0 18px;font-size:14px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26)}
.admin-secondary{background:#fff;border:1.5px solid #DFE9E4;border-radius:12px;min-height:44px;padding:0 16px;font-size:13.5px;font-weight:600;color:#14343A;text-decoration:none;display:inline-flex;align-items:center}
details.admin-panel{padding:0}
details.admin-panel>summary{cursor:pointer;list-style:none;padding:18px 22px;font-size:15px;font-weight:600;color:#12383A}
details.admin-panel>summary::-webkit-details-marker{display:none}
details.admin-panel>form{padding:0 22px 22px}
.admin-bulk-form{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end}
.admin-bulk-form label{font-size:12.5px;font-weight:600;color:#49696B;display:grid;gap:6px}
.admin-bulk-form select{height:46px;border:1.5px solid #DFE9E4;border-radius:12px;padding:0 12px;font-size:14px;background:#fff}
#khusus>h2{font-size:19px;font-weight:600;letter-spacing:-.01em}
#khusus>p{font-size:13.5px;color:#5F7A76}
#khusus .admin-filter{background:#fff;border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:16px;display:grid;grid-template-columns:1fr auto auto;gap:12px;align-items:end;margin:16px 0 6px}
#khusus .admin-filter label{font-size:12.5px;font-weight:600;color:#49696B;display:grid;gap:6px}
#khusus .admin-filter input{height:46px;border:1.5px solid #DFE9E4;border-radius:12px;padding:0 12px;font-size:14px;width:100%;box-sizing:border-box}
#khusus .admin-filter input:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-muted{font-size:13px;color:#7A918B}
.admin-pagination{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:18px}
.admin-page-btn{min-width:38px;height:38px;padding:0 10px;border-radius:10px;border:1.5px solid #DFE9E4;background:#fff;font-size:13px;font-weight:600;color:#14343A;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
.admin-page-btn.is-current{background:#1F6E70;border-color:#1F6E70;color:#fff}
.admin-page-btn.is-disabled{opacity:.45}
.admin-topline .admin-secondary{border-radius:999px;min-height:38px}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
@media(max-width:900px){.admin-feature-grid{grid-template-columns:1fr}.admin-bulk-form{grid-template-columns:1fr}}
</style>
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('KETERSEDIAAN FITUR') }}</p><h1>{{ __('Fitur Umum & Sekolah') }}</h1><p>{{ __('Atur fitur untuk semua institusi sekaligus, atau buat pengecualian untuk institusi tertentu. Membaca peta selalu aktif dan tidak bisa dimatikan. Saklar verifikasi laporan tamu dan pengguna login diatur terpisah. Perubahan hanya berlaku untuk laporan baru; antrean lama tidak otomatis diterbitkan.') }}</p></div>
@if($viewer->role===\App\Enums\Role::Owner)
<section class="admin-panel" id="general-features"><div class="admin-section-head"><h2>{{ __('Pengaturan umum · seluruh institusi') }}</h2><span>{{ $generalUpdatedAt?'Tersimpan':__('Default awal') }}</span></div>
<p>{{ __('Tekan Simpan & terapkan secara umum untuk menerapkan semua saklar berikut ke seluruh institusi terdaftar dan menyimpan default untuk institusi baru. Ini mengganti pengaturan fitur per institusi sebelumnya; status institusi yang dijeda/diarsipkan tidak berubah.') }}</p>
@if(!$generalUpdatedAt)<p class="admin-notice">{{ __('Panel ini menunjukkan default awal. Pengaturan institusi yang sudah ada belum berubah sampai pengaturan umum disimpan.') }}</p>@endif
<form method="post" action="{{ route('admin.features.general') }}" data-sensitive data-dirty-watch data-confirm="{{ __('Terapkan semua saklar ini ke seluruh institusi dan jadikan default institusi baru? Pengecualian fitur institusi sebelumnya akan diganti. Status institusi tidak berubah.') }}">@csrf @method('PATCH')
@include('admin.feature-switches',['school'=>null,'defaultFeatures'=>$generalFeatures])
<div class="admin-actions"><button class="admin-primary">{{ __('Simpan & terapkan secara umum') }}</button></div>
</form><p class="admin-muted">{{ __('Institusi baru dari direktori yang didaftarkan Admin Utama tetap dijeda dan pelaporannya nonaktif sampai lokasi, wilayah cakupan, dan fiturnya ditinjau. Pengaturan umum tidak otomatis mengaktifkan status institusi tersebut.') }}</p>
</section>
@endif
@if($permissions->allScope($viewer))<details class="admin-panel"><summary>{{ __('Terapkan satu fitur ke institusi yang sudah terdaftar') }}</summary><form method="post" action="{{ route('admin.features.all') }}" data-sensitive data-confirm="{{ __('Terapkan satu fitur ke semua institusi terdaftar, termasuk yang diarsipkan? Default institusi baru tidak berubah.') }}" class="admin-bulk-form">@csrf<label>{{ __('Fitur') }}<select data-bulk-feature>@foreach(config('ruteaman_admin.feature_labels') as $key=>$label)<option value="{{ $key }}">{{ __($label) }}</option>@endforeach</select></label><label>{{ __('Status') }}<select name="features[reporting]" data-bulk-value><option value="1">{{ __('Hidup') }}</option><option value="0">{{ __('Mati') }}</option></select></label><button class="admin-primary">{{ __('Terapkan ke institusi terdaftar') }}</button></form></details>@endif
<section id="khusus">
<h2>{{ __('Pengaturan khusus per institusi') }}</h2><p>{{ __('Gunakan panel berikut untuk membuat pengecualian setelah pengaturan umum disimpan. Perubahan pada satu institusi tidak mengubah default umum. Menyimpan pengaturan umum kembali akan mengganti pengecualian ini.') }}</p>
<form method="get" action="{{ route('admin.features') }}#khusus" class="admin-filter" role="search"><label>{{ __('Cari sekolah / kampus') }}<input type="search" name="q" value="{{ $q }}" maxlength="100" placeholder="{{ __('Ketik nama institusi…') }}"></label><button class="admin-primary" type="submit">{{ __('Cari') }}</button>@if($q !== '')<a class="admin-secondary" href="{{ route('admin.features') }}#khusus">{{ __('Reset') }}</a>@endif</form>
<p class="admin-muted">{{ __('Menampilkan') }} {{ $schools->count() }} {{ __('dari') }} {{ $schools->total() }} {{ __('institusi') }}@if($q !== '') {{ __('untuk pencarian') }} &quot;{{ $q }}&quot;@endif.</p>
@foreach($schools as $s)<details class="admin-panel"><summary>{{ $s->name }} · {{ match($s->status){'active'=>__('Aktif'),'paused'=>'Dijeda',default=>'Diarsipkan'} }}</summary><form method="post" action="{{ route('admin.schools.features',$s) }}" data-sensitive data-dirty-watch>@csrf @method('PATCH') @include('admin.feature-switches',['school'=>$s,'defaultFeatures'=>$generalFeatures])<div class="admin-actions"><button class="admin-primary">{{ __('Simpan khusus institusi ini') }}</button></div></form></details>@endforeach @if($schools->isEmpty())<div class="admin-panel"><p>{{ __('Tidak ada institusi yang cocok dengan pencarian ini.') }}</p></div>@endif
{{ $schools->links('pagination.admin') }}
</section>
@endsection
