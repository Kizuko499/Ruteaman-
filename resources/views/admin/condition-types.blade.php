@extends('admin.layout')
@section('title',__('Jenis Kondisi'))
@section('content')
<style data-ruteaman-admin-condition-types="v1">
.admin-main{background:#F6F8F6}
.admin-main .admin-notice{border-radius:14px}
.admin-main .admin-panel{border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06)}
.admin-main .admin-panel h2{font-size:17px}
.admin-main .condition-type-form-grid label,.admin-main .condition-logo-upload{font-size:13px;font-weight:600;color:#33554F;display:grid;gap:8px}
.admin-main .condition-type-form-grid label small,.admin-main .condition-logo-upload small{font-size:12px;color:#6B837C;font-weight:400;line-height:1.55}
.admin-main .admin-panel input:not([type=radio]):not([type=file]),.admin-main .admin-panel select{border:1px solid #CBDAD5;border-radius:12px;min-height:48px;padding:10px 12px;background:#fff;width:100%;box-sizing:border-box}
.admin-main .admin-panel input:focus,.admin-main .admin-panel select:focus{outline:0;border-color:#359FA0;box-shadow:0 0 0 4px rgba(53,159,160,.13)}
.admin-main .admin-panel input[type=file]{border:1.5px dashed #B9CFC8;border-radius:14px;padding:12px 14px;background:#FBFDFC;width:100%;box-sizing:border-box;font-weight:400;color:#33554F}
.admin-main .admin-panel input[type=file]::file-selector-button{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:10px;padding:9px 16px;font-weight:600;margin-right:12px;cursor:pointer}
.admin-main .condition-icon-picker{border:1px solid #E3EFE9;border-radius:16px;background:#FBFDFC;padding:18px}
.admin-main .condition-icon-picker legend{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#1F6E70;padding:0 8px}
.admin-main .condition-icon-picker>p{font-size:13px;color:#51706B}
.admin-main .condition-icon-options label{border:1px solid #DFE9E4;border-radius:14px;background:#fff;padding:14px 10px;box-shadow:0 1px 2px rgba(18,56,58,.04);cursor:pointer}
.admin-main .condition-icon-options label:hover{border-color:#9FC6C0}
.admin-main .condition-icon-options label:has(input:checked){background:#EAF4EE;border-color:#359FA0;box-shadow:0 0 0 3px rgba(53,159,160,.14)}
.admin-main .condition-type-advanced{border:1px solid #E3EFE9;border-radius:16px;background:#FBFDFC;padding:18px;margin-top:20px}
.admin-main .condition-type-advanced>summary{font-weight:700;color:#1F4A4C;cursor:pointer}
.admin-main .condition-type-advanced>p{font-size:13px;color:#51706B;line-height:1.6}
.admin-main .admin-panel button.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:12px;min-height:46px;padding:10px 22px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-main .condition-type-record{border:1px solid #E3EFE9;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(18,56,58,.04);overflow:hidden}
.admin-main .condition-type-record>summary{padding:14px 18px;cursor:pointer}
.admin-main .condition-type-record .condition-type-current-icon{border-radius:12px;border-color:#E3EFE9;background:#F7FAF7}
.admin-main .condition-type-edit-label{border:1px solid #CBDAD5;background:#fff;border-radius:999px;padding:6px 14px;font-size:12.5px;font-weight:600;color:#1F6E70}
.admin-main .condition-type-record form{padding:4px 18px 18px}
.admin-main .condition-report-preview{display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center}
.admin-main .condition-report-preview a.admin-secondary{border:1px solid #CBDAD5;background:#fff;border-radius:12px;min-height:44px;padding:10px 18px;font-weight:600;color:#1F4A4C;text-decoration:none;display:inline-flex;align-items:center}
.admin-main .condition-report-preview a.admin-secondary:hover{background:#F2F8F4}
</style>
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('ADMINISTRATOR UTAMA') }}</p><h1>{{ __('Jenis Kondisi') }}</h1><p>{{ __('Tambahkan kondisi jalan dan pilih logo yang mudah dikenali. Perubahan tersedia untuk seluruh institusi.') }}</p></div>
<div class="admin-notice"><strong>{{ __('Kelola kategori, bukan laporan.') }}</strong> {{ __('Kondisi baru tersedia di formulir laporan, filter kondisi, galeri beranda, dan legenda peta. Mengubah nama atau logo tidak menghapus laporan lama. Hanya Administrator Utama dapat mengubah kategori.') }}</div>
<section class="admin-panel" id="new-condition"><h2>{{ __('Tambah jenis kondisi') }}</h2><p>{{ __('Contoh: Longsor, Jalan licin, atau Trotoar rusak.') }}</p>
<form method="post" action="{{ route('admin.condition-types.store') }}" enctype="multipart/form-data" data-sensitive data-dirty-watch>@csrf
@include('admin.partials.condition-type-fields',['type'=>null,'prefix'=>'new'])
<div class="admin-actions"><button class="admin-primary">{{ __('Tambahkan kondisi') }}</button></div>
</form></section>
<section class="admin-panel"><div class="admin-section-head"><h2>{{ __('Jenis kondisi yang tersedia') }}</h2><span>{{ $types->count() }} {{ __('kategori') }}</span></div><p>{{ __('Buka kategori untuk mengubah nama, ikon, atau pengaturan penilaian.') }}</p>
<div class="condition-type-records">@foreach($types as $type)
<details class="condition-type-record"><summary><span class="condition-type-current-icon"><x-condition-symbol :condition="$type"/></span><span><strong>{{ __($type->label) }}</strong><small>{{ $type->code }} · {{ $type->hazards_count }} {{ __('laporan kondisi') }}</small></span><span class="condition-type-edit-label">{{ __('Ubah') }}</span></summary>
<form method="post" action="{{ route('admin.condition-types.update',$type) }}" enctype="multipart/form-data" data-sensitive data-dirty-watch>@csrf @method('PATCH')
@include('admin.partials.condition-type-fields',['type'=>$type,'prefix'=>'type-'.$type->id])
<div class="admin-actions"><button class="admin-primary">{{ __('Simpan perubahan') }}</button></div>
</form></details>
@endforeach</div></section>
@if($reportSchool ?? null)<p class="condition-report-preview"><a class="admin-secondary" href="{{ route('hazards.create',['school'=>$reportSchool->id]) }}">{{ __('Lihat pilihan kondisi di formulir laporan') }}</a> <span class="admin-muted">{{ __('Dibuka dengan konteks institusi') }} {{ $reportSchool->name }} {{ __('karena formulir laporan akun memerlukan institusi yang fitur laporannya menyala.') }}</span></p>@else<p class="admin-muted">{{ __('Pratinjau formulir laporan belum tersedia: belum ada institusi aktif yang fitur laporannya menyala.') }}</p>@endif
@endsection
