@extends('admin.layout')
@section('title',__('Tingkatan dan Izin'))
@section('content')
<style data-ruteaman-admin-roles="v1">
/* Tingkatan dan Izin: tabel referensi yang tenang — label tegas, kunci teknis
   sebagai teks mono kecil, status mudah dipindai. */
.admin-main{background:#F6F8F6}
.admin-page-head h1{font-size:26px;font-weight:600;letter-spacing:-.015em}
.admin-page-head p{font-size:14px;color:#5F7A76;max-width:72ch}
.admin-eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0}
.admin-panel{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:8px 22px 18px}
.admin-panel h2{font-size:17px;font-weight:600;letter-spacing:-.01em}
.admin-table th{font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;color:#7A918B;background:#F7FAF8;padding:12px 14px;border-bottom:1px solid #E7F0EA;text-align:left}
.admin-table td{padding:14px;border-top:1px solid #EAF1EC;vertical-align:top;font-size:14px}
.admin-table tbody tr{transition:background .15s ease}
.admin-table tbody tr:hover{background:#F8FBF9}
.admin-table td:first-child{font-weight:600;color:#12383A}
.admin-table td small{display:block;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px;font-weight:400;color:#7A918B;margin-top:3px}
.admin-table td:nth-child(2){font-weight:600;color:#1F6E70;white-space:nowrap}
.admin-table td:nth-child(3){color:#49696B;white-space:nowrap}
.admin-topline .admin-secondary{border-radius:999px;border:1.5px solid #DFE9E4;background:#fff;padding:8px 14px;font-size:13px;font-weight:600;color:#14343A}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
</style>
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('REFERENSI HAK AKSES') }}</p><h1>{{ __('Tingkatan dan Izin') }}</h1><p>{{ __('Dua tingkat aktif: Admin Utama dan Admin Sekolah. Admin Utama selalu memiliki seluruh izin. Akun dengan tingkat lama ditinjau Admin Utama dan tidak dipindahkan otomatis.') }}</p></div>
<section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>{{ __('Izin') }}</th><th>{{ __('♛ Admin Utama') }}</th><th>{{ __('▣ Admin Sekolah') }}</th></tr></thead><tbody>@foreach(config('ruteaman_permissions') as $key=>$item)<tr><td>{{ __($item['label']) }}<small>{{ $key }}</small></td><td>{{ __('✔ Tersedia') }}</td><td>{{ in_array($key,config('ruteaman_roles.ceilings.school_admin'),true)?__('✔ Dapat diberikan'):__('— Tidak tersedia') }}</td></tr>@endforeach</tbody></table></div></section>
<section class="admin-panel"><h2>{{ __('Preset izin') }}</h2>@foreach(config('ruteaman_roles.presets') as $preset)<p><strong>{{ __($preset['label']) }}</strong> · {{ count($preset['permissions']) }} {{ __('izin') }}</p>@endforeach<p>{{ __('Pilihan khusus: izin dipilih satu per satu di editor, tetap tidak melebihi batas maksimal tingkatnya. Admin Sekolah hanya mengelola pengguna di sekolahnya dan tidak bisa membuat admin lain.') }}</p></section>
@endsection
