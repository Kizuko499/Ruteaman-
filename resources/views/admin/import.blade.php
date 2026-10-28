@extends('admin.layout')
@section('title',__('Impor CSV'))
@section('content')
<style data-ruteaman-admin-import="v1">
.admin-main{background:#F6F8F6}
.admin-main .admin-page-head a.admin-secondary{border:1px solid #CBDAD5;background:#fff;border-radius:12px;min-height:44px;padding:10px 18px;font-weight:600;color:#1F4A4C;text-decoration:none;display:inline-flex;align-items:center}
.admin-main .admin-page-head a.admin-secondary:hover{background:#F2F8F4}
.admin-main .admin-panel{border:1px solid #E3EFE9;border-radius:20px;box-shadow:0 1px 2px rgba(18,56,58,.05),0 10px 24px rgba(18,56,58,.06)}
.admin-main .admin-panel h2{font-size:17px}
.admin-main .admin-panel label{font-size:13px;font-weight:600;color:#33554F;display:grid;gap:8px}
.admin-main .admin-panel input[type=file]{border:1.5px dashed #B9CFC8;border-radius:14px;padding:12px 14px;background:#FBFDFC;width:100%;box-sizing:border-box;font-weight:400;color:#33554F}
.admin-main .admin-panel input[type=file]::file-selector-button{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:10px;padding:9px 16px;font-weight:600;margin-right:12px;cursor:pointer}
.admin-main .admin-panel form p code{display:block;background:#F7FAF7;border:1px solid #E3EFE9;border-radius:12px;padding:14px 16px;font-size:12.5px;line-height:1.7;overflow-x:auto;margin-top:8px}
.admin-main .admin-panel button.admin-primary{background:linear-gradient(135deg,#2A8C8E,#1F6E70);border:0;color:#fff;border-radius:12px;min-height:46px;padding:10px 22px;font-weight:600;box-shadow:0 8px 18px rgba(31,110,112,.26);cursor:pointer}
.admin-main .admin-panel a.admin-secondary{border:1px solid #CBDAD5;background:#fff;border-radius:12px;min-height:46px;padding:10px 20px;font-weight:600;color:#1F4A4C;text-decoration:none;display:inline-flex;align-items:center}
.admin-main .admin-panel a.admin-secondary:hover{background:#F2F8F4}
.admin-main .admin-table th{background:#F7FAF7;font-size:11.5px;letter-spacing:.05em;text-transform:uppercase;color:#51706B;padding:13px 16px}
.admin-main .admin-table td{padding:13px 16px;border-bottom:1px solid #EDF3EF}
.admin-main .admin-table tbody tr:last-child td{border-bottom:0}
.admin-main .imp-status{display:inline-block;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:600}
.admin-main .imp-status-err{background:#FDF0EA;color:#B4552D}
.admin-main .imp-status-dup{background:#FBF3E4;color:#8A6D1F}
.admin-main .imp-status-ok{background:#EAF4EE;color:#1F6E70}
.admin-main .admin-import-guide ol{list-style:decimal;margin:0 0 18px;padding-left:22px;font-size:14px;line-height:1.75;color:#2E4B47}
.admin-main .admin-import-guide ol li{margin-bottom:6px}
.admin-main .admin-import-guide ol li::marker{color:#1F6E70;font-weight:700}
.admin-main .admin-guide-table th{text-transform:none;letter-spacing:0;font-size:12.5px}
.admin-main .admin-guide-table td code{background:#F2F8F4;border:1px solid #E3EFE9;border-radius:8px;padding:2px 8px;font-size:12.5px}
.admin-main .admin-notice,.admin-main .admin-warning{border-radius:14px}
</style>
<div class="admin-page-head"><div><p class="admin-eyebrow">{{ __('MANAJEMEN DIREKTORI') }}</p><h1>{{ __('Impor / Ekspor CSV') }}</h1><p>{{ __('Tambahkan banyak institusi sekaligus dari satu berkas CSV. Format kolom: name, npsn, latitude, longitude — maksimal 3000 baris per berkas.') }}</p></div><a href="{{ route('admin.import.export') }}" class="admin-secondary">{{ __('Ekspor CSV') }}</a></div>

@if(session('success'))<p class="admin-notice">{{ session('success') }}</p>@endif
@if(session('error'))<p class="admin-warning">{{ session('error') }}</p>@endif

@if(empty($rows))
<section class="admin-panel">
<form method="post" action="{{ route('admin.import.preview') }}" enctype="multipart/form-data">@csrf
<label>{{ __('Berkas CSV') }}<input type="file" name="csv" accept=".csv,.txt" required></label>
<p class="admin-muted">{{ __('Contoh:') }}<br><code>name,npsn,latitude,longitude<br>MAN 2 Rembang,20314960,-6.7195,111.3425<br>SMA Negeri 1 Rembang,20314961,-6.7142,111.3380</code></p>
<button class="admin-primary">{{ __('Pratinjau') }}</button>
</form>
@if(!empty($previewErrors))<div class="admin-warning"><ul>@foreach($previewErrors as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
</section>
<section class="admin-panel admin-import-guide">
<h2>{{ __('Cara mengisi berkas CSV') }}</h2>
<ol>
<li>{{ __('Siapkan data di Excel atau Google Sheets, lalu simpan sebagai CSV (dipisahkan koma). Berkas dari tombol Ekspor CSV di kanan atas bisa dipakai sebagai contoh format.') }}</li>
<li>{{ __('Baris pertama wajib berisi nama kolom persis seperti ini: name,npsn,latitude,longitude — huruf kecil dan tanpa spasi. Kolom institution_type boleh ditambahkan bila perlu.') }}</li>
<li>{{ __('Isi satu institusi per baris mengikuti penjelasan kolom di bawah. Angka desimal memakai titik, bukan koma.') }}</li>
<li>{{ __('Unggah berkasnya, lalu klik Pratinjau. Periksa baris yang ditandai salah atau duplikat; perbaiki dulu di berkasnya bila perlu.') }}</li>
<li>{{ __('Klik Proses impor. Baris yang salah atau duplikat dilewati; baris yang benar langsung terdaftar sebagai institusi aktif dengan wilayah cakupan otomatis di sekitar titik lokasinya.') }}</li>
</ol>
<table class="admin-table admin-guide-table"><thead><tr><th>{{ __('Kolom') }}</th><th>{{ __('Isi') }}</th><th>{{ __('Contoh') }}</th></tr></thead><tbody>
<tr><td><code>name</code></td><td>{{ __('Nama resmi institusi. Wajib diisi.') }}</td><td>MAN 2 Rembang</td></tr>
<tr><td><code>npsn</code></td><td>{{ __('NPSN institusi. Boleh dikosongkan, misalnya untuk kampus.') }}</td><td>20314960</td></tr>
<tr><td><code>latitude</code></td><td>{{ __('Garis lintang titik lokasi. Untuk wilayah Indonesia umumnya bernilai negatif.') }}</td><td>-6.7195</td></tr>
<tr><td><code>longitude</code></td><td>{{ __('Garis bujur titik lokasi.') }}</td><td>111.3425</td></tr>
<tr><td><code>institution_type</code></td><td>{{ __('Opsional. Isi school untuk sekolah atau campus untuk kampus. Bila dikosongkan, dianggap school.') }}</td><td>school</td></tr>
</tbody></table>
<p class="admin-muted">{{ __('Batasan: maksimal 3000 baris dan ukuran berkas 2 MB. Institusi yang nama atau NPSN-nya sudah terdaftar akan ditandai duplikat dan dilewati saat diproses.') }}</p>
</section>
@else
<section class="admin-panel">
<h2>{{ __('Pratinjau') }} ({{ count($rows) }} baris)</h2>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>#</th><th>{{ __('Nama') }}</th><th>NPSN</th><th>{{ __('Koordinat') }}</th><th>{{ __('Tipe') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
@foreach($rows as $r)<tr>
<td>{{ $r['line'] }}</td>
<td><strong>{{ $r['name'] }}</strong></td>
<td>{{ $r['npsn'] }}</td>
<td>{{ $r['latitude'] }}, {{ $r['longitude'] }}</td>
<td>{{ (($r['institution_type'] ?? 'school') === 'campus') ? __('Kampus') : __('Sekolah') }}</td>
<td>@if($r['errors'])<span class="imp-status imp-status-err">{{ implode('; ', $r['errors']) }}</span>@elseif($r['duplicate'])<span class="imp-status imp-status-dup">{{ __('Duplikat:') }} {{ $r['duplicate'] }}</span>@else<span class="imp-status imp-status-ok">OK</span>@endif</td>
</tr>@endforeach
</tbody></table></div>
<form method="post" action="{{ route('admin.import.process') }}">@csrf<button class="admin-primary">{{ __('Proses impor') }}</button> <a href="{{ route('admin.import.show') }}" class="admin-secondary">{{ __('Batal') }}</a></form>
</section>
@endif
@endsection
