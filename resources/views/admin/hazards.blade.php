@extends('admin.layout')
@section('title',__('Laporan / Kondisi'))
@section('content')
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<style data-ruteaman-admin-hazards="v1">
/* Laporan / Kondisi: panel pemilih tenang, baris kondisi sebagai kartu
   dengan kelompok aksi yang rapi. Semua formulir dipertahankan. */
.admin-main{background:#F6F8F6}
.admin-page-head h1{font-size:26px;font-weight:600;letter-spacing:-.015em}
.admin-page-head p{font-size:14px;color:#5F7A76;max-width:78ch}
.admin-eyebrow{font-size:11px;letter-spacing:.14em;color:#359FA0}
.admin-panel{background:#fff;border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);padding:22px}
.admin-panel label{font-size:12.5px;font-weight:600;color:#49696B;display:grid;gap:6px}
.admin-panel select,.admin-panel input[type="search"],.admin-panel input[name="reason"]{width:100%;box-sizing:border-box;height:46px;border:1.5px solid #DFE9E4;border-radius:12px;padding:0 12px;font-size:14px;background:#fff;color:#12383A}
.admin-panel select:focus,.admin-panel input:focus{outline:none;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-panel p,.admin-panel small{font-size:13px;color:#7A918B;line-height:1.55}
.admin-notice{background:#EFF7F1;border:1px solid #D9EAE0;border-radius:14px;padding:12px 14px;font-size:13px;color:#33544C;line-height:1.6}
.admin-secondary{background:#fff;border:1.5px solid #DFE9E4;border-radius:12px;min-height:42px;padding:0 16px;font-size:13.5px;font-weight:600;color:#14343A;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);color:#fff;border:0;border-radius:12px;min-height:42px;padding:0 16px;font-size:13.5px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-danger{background:#FDF0EA;border:1.5px solid #F0D5C8;border-radius:12px;min-height:42px;padding:0 16px;font-size:13.5px;font-weight:600;color:#B4552D;cursor:pointer}
.admin-hazard-row{display:flex;gap:24px;justify-content:space-between;border-bottom:1px solid #EAF1EC;padding:20px 2px}
.admin-hazard-row:last-of-type{border-bottom:0}
.admin-hazard-row h2{font-size:17px;font-weight:600;letter-spacing:-.01em;margin:0 0 4px}
.admin-hazard-row p{font-size:13px;color:#7A918B;margin:0}
.admin-hazard-row>div:last-child{display:grid;gap:12px;min-width:min(350px,100%);align-content:start}
.admin-hazard-row form{display:grid;gap:6px}
.admin-hazard-row form label{font-size:12.5px;font-weight:600;color:#49696B}
.admin-hazard-row form button{justify-self:start}
.admin-actions{display:flex;flex-wrap:wrap;gap:8px}
.admin-topline .admin-secondary{border-radius:999px;min-height:38px}
.admin-topline a{font-size:13px;font-weight:600;color:#1F6E70;text-decoration:none}
.admin-sidebar{background:linear-gradient(180deg,#0F4447,#093033)}
.admin-nav-item{border-radius:12px}
.admin-nav-item[aria-current="page"]{box-shadow:0 6px 14px rgba(0,0,0,.18)}
.admin-viewer small{color:#9DB8B4}
.admin-sidebar-note{font-size:11.5px;color:#8FA8A4}
@media(max-width:760px){.admin-hazard-row{flex-direction:column;gap:12px}}
</style>
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('KONDISI RUTE') }}</p><h1>{{ __('Laporan / Kondisi') }}</h1><p>{{ $school->name }} {{ __('· dalam wilayah dan cakupan izin akun. Laporan langsung tampil tetap belum terverifikasi.') }}</p></div>
@include('admin.report-school-picker')
@if($school->status==='paused')<p class="admin-notice">{{ __('Pemantauan institusi ini dijeda. Tinjau lokasi dan wilayah cakupannya melalui menu Institusi, dan pengaturannya melalui menu Fitur Umum & Sekolah, sebelum mengaktifkan pelaporan.') }}</p>@endif
<div class="admin-actions">@if($permissions->can($viewer,'verification.decide',$school))<a class="admin-secondary" href="{{ route('verifier.queue',['school'=>$school->id]) }}">{{ __('Antrean verifikasi') }}</a>@endif @if($permissions->can($viewer,'dashboard.view',$school))<a class="admin-secondary" href="{{ route('school.dashboard',['school'=>$school->id]) }}">{{ __('Dashboard institusi') }}</a>@endif</div>
<p class="admin-notice">{{ __('Tangguhkan menyembunyikan kondisi dari publik dan dapat dipulihkan. Hapus dari publik menyembunyikan permanen lewat menu ini, bukan menghapus histori. Tindakan berlaku untuk titik kondisi beserta laporan terkait; jejak audit tetap disimpan.') }}</p>
<section class="admin-panel">
@forelse($hazards as $h)
<article class="admin-hazard-row"><div><h2>{{ $h->type->label }}</h2><p>{{ $h->status_label }} · {{ $h->public_slug }}</p>
@if($h->publication_control==='removed')<p>{{ __('Dihapus dari publik. Histori dan audit dipertahankan.') }}</p>@endif
</div><div>
<div class="admin-actions"><a class="admin-secondary" href="{{ route('conditions.show',$h) }}">{{ __('Lihat kondisi') }}</a>
@if($h->publication_control==='normal'&&in_array($h->status->value,['resolved','expired'],true)&&$permissions->can($viewer,'hazards.reopen',$school,true))<form method="post" action="{{ route('admin.hazards.reopen',$h) }}" data-sensitive>@csrf<button class="admin-secondary">{{ __('Buka kembali') }}</button></form>@endif
@if($h->publication_control==='normal'&&$permissions->can($viewer,'escalations.manage',$school,true))<form method="post" action="{{ route('hazards.escalate',$h) }}">@csrf<input type="hidden" name="channel" value="manual"><button class="admin-primary">{{ __('Paket eskalasi') }}</button></form>@endif</div>
@if($h->publication_control!=='removed'&&$permissions->can($viewer,'hazards.reopen',$school,true))
@foreach(($h->publication_control==='suspended'?['restore'=>__('Pulihkan publikasi'),'remove'=>__('Hapus dari publik')]:['suspend'=>'Tangguhkan','remove'=>__('Hapus dari publik')]) as $action=>$label)
<form method="post" action="{{ route('admin.hazards.moderation',['hazard'=>$h,'action'=>$action]) }}" data-sensitive data-confirm="{{ $action==='remove'?'Tarik kondisi ini dari publik? Histori tetap disimpan dan tidak dapat dipulihkan melalui menu ini.':'Ubah publikasi kondisi ini? Status verifikasi tidak berubah.' }}">
@csrf<input type="hidden" name="school" value="{{ $school->id }}"><label>{{ __('Alasan') }} {{ strtolower($label) }}<input name="reason" required minlength="3" maxlength="500" placeholder="{{ __('Alasan tanpa data pribadi') }}"></label><button class="{{ $action==='remove'?'admin-danger':'admin-secondary' }}">{{ $label }}</button>
</form>
@endforeach
@endif
</div></article>
@empty<p class="admin-empty">{{ __('Belum ada kondisi yang tercatat di wilayah ini di RuteAman. Direktori online menyediakan data institusi, bukan laporan kondisi otomatis.') }}</p>@endforelse {{ $hazards->withQueryString()->links() }}
</section>
@endsection
