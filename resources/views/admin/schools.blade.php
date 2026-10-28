@extends('admin.layout')
@section('title',__('Institusi'))
@section('content')
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<style data-ruteaman-admin-schools="v1">
/* Daftar institusi: filter jadi kartu, tabel tenang berkepala lembut,
   tombol aksi ringkas. Semua fitur & tombol dipertahankan. */
.admin-main{background:#F6F8F6}
.admin-page-head h1{font-size:26px;font-weight:600;letter-spacing:-.015em}
.admin-page-head p{font-size:14px;color:#5F7A76}
.admin-eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0}
.admin-head-actions .admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;border:0;border-radius:12px;min-height:42px;padding:0 18px;font-size:14px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);text-decoration:none;display:inline-flex;align-items:center}
.admin-head-actions .admin-secondary,.admin-topline .admin-secondary{background:#fff;border:1.5px solid #DFE9E4;border-radius:12px;min-height:42px;padding:0 16px;font-size:13.5px;font-weight:600;color:#14343A;text-decoration:none;display:inline-flex;align-items:center}
.admin-topline .admin-secondary{border-radius:999px;min-height:38px}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-filter{background:#fff;border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:16px;display:grid;grid-template-columns:repeat(5,minmax(0,1fr)) auto;gap:12px;align-items:end;margin:18px 0 6px}
.admin-filter label{font-size:12.5px;font-weight:600;color:#49696B;display:grid;gap:6px}
.admin-filter input,.admin-filter select{height:46px;border:1.5px solid #DFE9E4;border-radius:12px;padding:0 12px;font-size:14px;font-weight:500;color:#14343A;background:#fff;width:100%;box-sizing:border-box}
.admin-filter input:focus,.admin-filter select:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-filter .admin-secondary{height:46px;border-radius:12px;background:#fff;border:1.5px solid #DFE9E4;font-size:14px;font-weight:600;color:#14343A;padding:0 20px}
.admin-muted{font-size:13px;color:#7A918B;margin:12px 2px 10px}
.admin-panel{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:8px 22px 18px}
.admin-table th{font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;color:#7A918B;background:#F7FAF8;padding:12px 14px;border-bottom:1px solid #E7F0EA;text-align:left}
.admin-table td{padding:16px 14px;border-top:1px solid #EAF1EC;vertical-align:top}
.admin-table tbody tr{transition:background .15s ease}
.admin-table tbody tr:hover{background:#F8FBF9}
.admin-table td strong{font-size:14.5px;font-weight:600;color:#12383A}
.admin-table td small{display:block;font-size:12px;color:#7A918B;margin-top:2px}
.admin-table td[data-label="Status"]{font-size:13px;font-weight:600;color:#1F6E70;white-space:nowrap}
.admin-table td[data-label="Koordinat"]{font-size:13px;font-variant-numeric:tabular-nums;white-space:nowrap}
.admin-table td[data-label="Zona waktu"]{font-size:13px;color:#49696B;white-space:nowrap}
.admin-table .admin-actions{display:flex;flex-wrap:wrap;gap:8px}
.admin-table .admin-actions .admin-secondary,.admin-table .admin-actions .admin-danger{min-height:36px;padding:0 12px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center}
.admin-table .admin-actions .admin-secondary{background:#fff;border:1.5px solid #DFE9E4;color:#14343A}
.admin-table .admin-actions .admin-danger{background:#FDF0EA;border:1.5px solid #F3D9C8;color:#B4552D}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
@media(max-width:1100px){.admin-filter{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:760px){.admin-filter{grid-template-columns:1fr 1fr}.admin-filter .admin-secondary{grid-column:1/-1}}
</style>
<div class="admin-page-head admin-head-actions"><div><p class="admin-eyebrow">{{ __('DIREKTORI PLATFORM') }}</p><h1>{{ __('Institusi') }}</h1><p>{{ __('Lokasi, wilayah cakupan, akun pengelola, dan fitur yang tersedia untuk tiap institusi.') }}</p></div><div class="admin-actions">@if($permissions->allScope($viewer))<a class="admin-secondary" href="{{ route('admin.schools.duplicates') }}">{{ __('Cek duplikat') }}</a>@endif @if($permissions->can($viewer,'schools.provision')&&$permissions->allScope($viewer))<a class="admin-primary" href="{{ route('admin.schools.create') }}">{{ __('+ Institusi dan akun') }}</a>@endif</div></div><form method="get" class="admin-filter"><label>{{ __('Cari institusi') }}<input name="q" value="{{ request('q') }}" maxlength="100" placeholder="{{ __('Nama / NPSN') }}"></label><label>{{ __('Jenis') }}<select name="type"><option value="">{{ __('Semua') }}</option><option value="school" @selected(request('type')==='school')>{{ __('Sekolah') }}</option><option value="campus" @selected(request('type')==='campus')>{{ __('Kampus') }}</option></select></label><label>{{ __('Status') }}<select name="status"><option value="">{{ __('Semua status') }}</option>@foreach(['active'=>__('Aktif'),'paused'=>'Dijeda','archived'=>'Diarsipkan'] as $k=>$l)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $l }}</option>@endforeach</select></label><label>{{ __('Kualitas data') }}<select name="issue"><option value="">{{ __('Semua') }}</option><option value="no_npsn" @selected(request('issue')==='no_npsn')>{{ __('Tanpa NPSN / kode resmi') }}</option><option value="no_coords" @selected(request('issue')==='no_coords')>{{ __('Tanpa koordinat') }}</option></select></label><label>{{ __('Urutkan') }}<select name="sort"><option value="name" @selected(request('sort','name')==='name')>{{ __('Nama A-Z') }}</option><option value="newest" @selected(request('sort')==='newest')>{{ __('Terbaru') }}</option><option value="oldest" @selected(request('sort')==='oldest')>{{ __('Terlama') }}</option></select></label><button class="admin-secondary">{{ __('Terapkan') }}</button></form><p class="admin-muted">{{ __('Menampilkan :n dari :t institusi', ['n' => $schools->count(), 't' => $schools->total()]) }}</p><section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>{{ __('Institusi') }}</th><th>{{ __('Status') }}</th><th>{{ __('Koordinat') }}</th><th>{{ __('Zona waktu') }}</th><th>{{ __('Aksi') }}</th></tr></thead><tbody>@forelse($schools as $s)<tr><td data-label="Institusi"><strong>{{ $s->name }}</strong><small>{{ ($s->institution_type??'school')==='campus'?__('Kampus'):__('Sekolah') }} · {{ $s->country_code??'ID' }} @if($s->npsn) · NPSN {{ $s->npsn }} @endif</small><small>{{ $s->public_slug }}</small></td><td data-label="Status">{{ match($s->status){'active'=>__('● Aktif'),'paused'=>__('◷ Dijeda'),default=>__('⊘ Diarsipkan')} }}</td><td data-label="Koordinat">@php($co = $coords[$s->id] ?? null) @if($co && $co->lat)<span style="color:green">✓ {{ number_format($co->lat,4) }}, {{ number_format($co->lng,4) }}</span>@else<span style="color:red">✗ {{ __('Belum ada') }}</span>@endif</td><td data-label="Zona waktu">{{ $s->timezone }}</td><td data-label="Aksi"><div class="admin-actions"><a class="admin-secondary" href="{{ route('admin.schools.show',$s) }}">{{ __('Lihat institusi') }}</a>@if($permissions->can($viewer,'hazards.view',$s))<a class="admin-secondary" href="{{ route('admin.hazards',['school'=>$s->id]) }}">{{ __('Kondisi') }}</a>@endif @if($permissions->can($viewer,'schools.manage',$s)&&$viewer->role->tier()<=2) @if($s->status==='active')<form method="post" action="{{ route('admin.schools.pause',$s) }}" data-confirm="{{ __('Jeda institusi ini?') }}">@csrf<button class="admin-secondary">{{ __('Jeda') }}</button></form>@elseif($s->status==='paused')<form method="post" action="{{ route('admin.schools.restore',$s) }}">@csrf<button class="admin-secondary">{{ __('Pulihkan') }}</button></form>@endif @if($s->status!=='archived')<form method="post" action="{{ route('admin.schools.archive',$s) }}" data-confirm="{{ __('Arsipkan institusi ini?') }}">@csrf<button class="admin-danger">{{ __('Arsip') }}</button></form>@endif @if($s->status==='archived')<form method="post" action="{{ route('admin.schools.restore',$s) }}" data-confirm="{{ __('Batalkan arsip dan aktifkan lagi institusi ini?') }}">@csrf<button class="admin-secondary">{{ __('Batal arsip') }}</button></form>@endif @endif</div></td></tr>@empty<tr><td colspan="4" class="admin-empty">{{ __('Belum ada institusi yang cocok dengan filter ini.') }}</td></tr>@endforelse</tbody></table></div>{{ $schools->links() }}</section>
@endsection
