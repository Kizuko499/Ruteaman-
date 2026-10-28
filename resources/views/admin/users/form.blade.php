@extends('admin.layout')
@section('title',__('Kelola Pengguna'))
@section('content')
<style data-ruteaman-admin-users="v1">
.admin-main{background:#F6F8F6}
.admin-main .admin-page-head .admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:12px;box-shadow:0 8px 18px rgba(31,110,112,.26);font-weight:600;text-decoration:none}
.admin-main .admin-filter{background:#fff;border:1px solid #E3EFE9;border-radius:18px;padding:16px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06);display:grid;grid-template-columns:repeat(4,minmax(0,1fr)) auto;gap:12px;align-items:end}
.admin-main .admin-filter label{font-size:12.5px;font-weight:600;color:#33554F;display:grid;gap:6px}
.admin-main .admin-filter input,.admin-main .admin-filter select{height:46px;border:1px solid #CBDAD5;border-radius:12px;padding:0 12px;background:#fff;width:100%;box-sizing:border-box}
.admin-main .admin-filter .admin-secondary{height:46px;border-radius:12px;padding:0 20px;background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-main .admin-panel{border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06)}
.admin-main .admin-panel h2{font-size:17px}
.admin-main .admin-table th{background:#F7FAF7;font-size:11.5px;letter-spacing:.05em;text-transform:uppercase;color:#51706B;padding:13px 16px}
.admin-main .admin-table td{padding:13px 16px;border-bottom:1px solid #EDF3EF}
.admin-main .admin-table tbody tr:last-child td{border-bottom:0}
.admin-main .admin-table td small{color:#6B837C}
.admin-main .admin-table details summary{cursor:pointer;font-weight:600;color:#1F6E70;list-style:none}
.admin-main .admin-table details a.admin-secondary{display:inline-block;margin-top:8px}
.admin-main .admin-panel label{font-size:13px;font-weight:600;color:#33554F}
.admin-main .admin-panel small{font-size:12px;color:#6B837C;font-weight:400}
.admin-main .admin-panel input:not([type=checkbox]):not([type=hidden]),.admin-main .admin-panel select{border:1px solid #CBDAD5;border-radius:12px;min-height:48px;padding:10px 12px;background:#fff;width:100%;box-sizing:border-box}
.admin-main .admin-panel input:focus,.admin-main .admin-panel select:focus,.admin-main .admin-filter input:focus,.admin-main .admin-filter select:focus{outline:0;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-main .admin-field-grid{gap:16px}
.admin-main .admin-panel button.admin-primary,.admin-main .admin-panel button.admin-secondary,.admin-main .admin-panel button.admin-danger{border-radius:12px;min-height:46px;padding:10px 20px;font-weight:600;cursor:pointer}
.admin-main .admin-panel button.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;box-shadow:0 8px 18px rgba(31,110,112,.26)}
.admin-main .admin-panel button.admin-secondary{background:#fff;border:1px solid #CBDAD5;color:#1F4A4C}
.admin-main .admin-panel button.admin-secondary:hover{background:#F2F8F4}
.admin-main .admin-panel button.admin-danger,.admin-main button.admin-danger{background:#FDF0EA;border:1px solid #F0D9CE;color:#B4552D;border-radius:12px;min-height:46px;padding:10px 20px;font-weight:600;cursor:pointer}
.admin-main fieldset.admin-permission-group,.admin-main fieldset.admin-password-setup{border:1px solid #E3EFE9;border-radius:16px;padding:18px;margin:0 0 18px;background:#fff}
.admin-main fieldset>legend{font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#1F6E70;padding:0 8px}
.admin-main .admin-switch-row{padding:11px 2px;border-bottom:1px solid #EDF3EF}
.admin-main fieldset .admin-switch-row:last-child{border-bottom:0}
.admin-main .admin-switch-row input:checked+.admin-switch-track{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border-color:transparent}
.admin-main .admin-section-head h2{font-size:17px;margin:0}
.admin-main .admin-section-head select{min-height:44px;border-radius:12px}
.admin-main details.admin-preview{background:#F2F8F4;border:1px solid #E3EFE9;border-radius:14px;padding:12px 16px;margin-top:14px}
.admin-main details.admin-preview summary{cursor:pointer;font-weight:600;color:#1F6E70}
.admin-main .admin-check-row{display:flex;gap:10px;align-items:flex-start;background:#F2F8F4;border:1px solid #DDEAE4;border-radius:12px;padding:12px 14px}
.admin-main .admin-check-row input{accent-color:#1F6E70;width:18px;height:18px;margin-top:1px}
.admin-main .admin-notice{border-radius:14px}
.admin-main dialog#owner-delete-dialog{border:1px solid #E3EFE9;border-radius:18px;box-shadow:0 24px 48px rgba(18,56,58,.18)}
.admin-main dialog#owner-delete-dialog::backdrop{background:rgba(9,32,34,.42)}
@media (max-width:900px){.admin-main .admin-filter{grid-template-columns:1fr 1fr}}
@media (max-width:560px){.admin-main .admin-filter{grid-template-columns:1fr}}
</style>
@php($viewer=auth()->user())
@php($permissions=app(\App\Services\PermissionService::class))
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('IDENTITAS DAN AKSES') }}</p><h1>{{ $target?__('Kelola ').$target->display_name:__('Buat akun baru') }}</h1><p>{{ __('Perubahan sensitif memerlukan konfirmasi kata sandi. Tidak ada penyamaran (masuk sebagai akun lain) dan tidak ada akses untuk melihat kata sandi akun lain.') }}</p></div>
@if($target)<p><x-admin-role-badge :role="$target->role" /> · {{ $target->email }}</p>@if(in_array($target->role->value,['platform_admin','school_staff'],true))<div class="admin-notice">{{ __('Tingkat lama (nonaktif). Hak lama belum diubah. Gunakan formulir peran di bawah untuk memilih Admin Sekolah dan institusinya, atau tangguhkan akun.') }}</div>@endif @endif
@include('admin.users.delete-dialog')
<form class="admin-panel" method="post" action="{{ $target?route('admin.users.update',$target):route('admin.users.store') }}" data-sensitive data-dirty-watch>@csrf @if($target) @method('PATCH') @endif
<div class="admin-field-grid"><label>{{ __('Nama tampilan') }}<input name="display_name" required maxlength="100" value="{{ old('display_name',$target?->display_name) }}"></label><label>{{ __('Alias / email login') }}<input type="email" name="email" required maxlength="254" value="{{ old('email',$target?->email) }}"><small>{{ __('Untuk admin gunakan domain') }} {{ config('ruteaman_admin.alias_domain') }}.</small></label><label>{{ __('Email kontak') }}<input type="email" name="contact_email" value="{{ old('contact_email',$target?->contact_email) }}"></label>@include('admin.school-picker',['selectedSchoolId'=>old('school_id',$target?->school_id)])
@if(!$target)<label>{{ __('Peran') }}<select name="role" data-admin-role>@foreach(['school_admin'=>__('Admin Sekolah'),'verifier'=>__('Verifikator'),'student'=>__('Siswa'),'owner'=>__('Admin Utama')] as $k=>$v)<option value="{{ $k }}" @selected(old('role','school_admin')===$k)>{{ $v }}</option>@endforeach</select></label>@endif</div>
@if(!$target) @include('admin.initial-password') @include('admin.permission-editor') @endif
<label>{{ __('Alasan / catatan') }}<input name="reason" maxlength="500" placeholder="{{ __('Jangan tulis kata sandi, token, atau data pelapor') }}"></label><div class="admin-actions"><a class="admin-secondary" href="{{ route('admin.users.index') }}">{{ __('Batal') }}</a><button class="admin-primary">{{ $target?__('Simpan profil'):__('Buat akun') }}</button></div></form>
@if($target && $target->id!==$viewer->id)
<section class="admin-panel"><h2>{{ __('Peran dan institusi') }}</h2><form method="post" action="{{ route('admin.users.role',$target) }}" data-sensitive>@csrf @method('PATCH')<div class="admin-field-grid"><label>{{ __('Peran baru') }}<select name="role">@foreach(['school_admin'=>__('Admin Sekolah'),'owner'=>__('Admin Utama'),'verifier'=>__('Verifikator'),'student'=>__('Siswa')] as $k=>$v)<option value="{{ $k }}" @selected($target->role->value===$k)>{{ $v }}</option>@endforeach</select></label>@include('admin.school-picker',['selectedSchoolId'=>$target->school_id])</div><label>{{ __('Alasan') }}<input name="reason" maxlength="500"></label><button class="admin-secondary">{{ __('Ubah peran / pindahkan tingkat lama') }}</button></form></section>
<section class="admin-panel"><h2>{{ __('Izin pengguna') }}</h2>@if($target->role===\App\Enums\Role::Owner)<p>{{ __('Admin Utama selalu memiliki semua izin. Saklar terkunci; tidak perlu mengatur izin satu per satu.') }}</p>@else<form method="post" action="{{ route('admin.users.permissions',$target) }}" data-sensitive>@csrf @method('PATCH') @include('admin.permission-editor')<label>{{ __('Alasan') }}<input name="reason" maxlength="500"></label><button class="admin-primary">{{ __('Simpan izin') }}</button></form>@endif</section>
<section class="admin-panel"><h2>{{ __('Atur kata sandi') }}</h2><form method="post" action="{{ route('admin.users.password',$target) }}" data-sensitive>@csrf @method('PUT') @include('admin.initial-password',['passwordOnly'=>true])<button class="admin-primary">{{ __('Atur kata sandi dan cabut sesi') }}</button></form></section>
<section class="admin-panel"><h2>{{ __('Status dan sesi') }}</h2><div class="admin-actions">@foreach(['reset'=>__('Buat tautan reset'),'revoke-sessions'=>__('Cabut semua sesi'),'suspend'=>'Tangguhkan','activate'=>__('Aktifkan')] as $k=>$v)<form method="post" action="{{ route('admin.users.'.$k,$target) }}" data-sensitive data-confirm="{{ $v }} akun ini?">@csrf<button class="admin-secondary">{{ $v }}</button></form>@endforeach</div></section>
@elseif($target)<div class="admin-notice">{{ __('Anda tidak dapat mengubah peran/izin/status atau menghapus akun sendiri.') }}</div>@endif
@endsection
