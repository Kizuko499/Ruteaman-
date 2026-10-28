@extends('admin.layout')
@section('title',__('Semua Pengguna'))
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
<div class="admin-page-head admin-head-actions"><div><p class="admin-eyebrow">{{ __('KENDALI ADMIN UTAMA') }}</p><h1>{{ __('Semua Pengguna') }}</h1><p>{{ __('Semua peran dan institusi. Akun sendiri dan Admin Utama terakhir dilindungi dari penghapusan.') }}</p></div><a class="admin-primary" href="{{ route('admin.users.create') }}">{{ __('Tambah pengguna') }}</a></div>
<form method="get" class="admin-filter"><label>{{ __('Cari nama / alias') }}<input name="q" value="{{ request('q') }}" maxlength="100"></label><label>{{ __('Peran') }}<select name="role"><option value="">{{ __('Semua') }}</option>@foreach(['owner'=>__('Admin Utama'),'school_admin'=>__('Admin Sekolah'),'verifier'=>__('Verifikator'),'student'=>__('Siswa')] as $k=>$v)<option value="{{ $k }}" @selected(request('role')===$k)>{{ $v }}</option>@endforeach</select></label><label>{{ __('Institusi') }}<select name="school_id"><option value="">{{ __('Semua institusi') }}</option>@foreach($schools as $s)<option value="{{ $s->id }}" @selected(request('school_id')==$s->id)>{{ $s->name }}</option>@endforeach</select></label><label>{{ __('Status') }}<select name="status"><option value="">{{ __('Semua') }}</option>@foreach(['active'=>__('Aktif'),'pending'=>'Menunggu','suspended'=>__('Ditangguhkan')] as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select></label><button class="admin-secondary">{{ __('Terapkan') }}</button></form>
<section class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>{{ __('Nama / alias') }}</th><th>{{ __('Peran') }}</th><th>{{ __('Institusi') }}</th><th>{{ __('Status') }}</th><th>{{ __('Terakhir masuk') }}</th><th>{{ __('Aksi') }}</th></tr></thead><tbody>@forelse($users as $u)<tr><td data-label="Nama / alias"><strong>{{ $u->display_name }}</strong><small>{{ $u->email }}</small></td><td data-label="Peran"><x-admin-role-badge :role="$u->role" /></td><td data-label="Institusi">{{ $u->school?->name??'—' }}</td><td data-label="Status">{{ match($u->status){'active'=>__('● Aktif'),'pending'=>__('◷ Menunggu'),default=>__('⊘ Ditangguhkan')} }}</td><td data-label="Terakhir masuk">{{ $u->last_login_at?->diffForHumans()??'—' }}</td><td data-label="Aksi"><details><summary aria-label="Aksi untuk {{ $u->display_name }}">{{ __('⋮ Aksi') }}</summary><a class="admin-secondary" href="{{ route('admin.users.show',$u) }}">{{ __('Lihat & kelola akun') }}</a></details></td></tr>@empty<tr><td colspan="6">{{ __('Tidak ada pengguna sesuai filter.') }}</td></tr>@endforelse</tbody></table></div>{{ $users->links() }}</section>
@endsection
