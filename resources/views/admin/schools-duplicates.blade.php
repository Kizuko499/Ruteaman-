@extends('admin.layout')
@section('title',__('Duplikat Institusi'))
@section('content')
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('DIREKTORI PLATFORM') }}</p><h1>{{ __('Duplikat Institusi') }}</h1><p>{{ __('Kelompok institusi yang terindikasi ganda: NPSN yang sama persis, atau nama dan koordinat yang sama persis. Halaman ini hanya meninjau — tidak ada yang digabung atau dihapus otomatis. Pertahankan satu record (ada saran penanda), lalu arsipkan salinan berlebihnya langsung dari sini.') }}</p></div>

<p class="admin-muted">{{ __('Ditemukan') }} {{ $totalGroups }} {{ __('kelompok duplikat dari') }} {{ $totalInstitutions }} {{ __('institusi dalam cakupanmu.') }}</p>

@forelse($groups as $g)
<section class="admin-panel">
<div class="admin-section-head"><h2>{{ $g['kind'] === 'npsn' ? __('NPSN sama') : __('Nama + koordinat sama') }}</h2><span>{{ $g['label'] }} · {{ count($g['members']) }} {{ __('record') }}</span></div>
<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>{{ __('Institusi') }}</th><th>{{ __('Status') }}</th><th>{{ __('NPSN') }}</th><th>{{ __('Koordinat') }}</th><th>{{ __('Akun') }}</th><th>{{ __('Laporan') }}</th><th>{{ __('ID') }}</th><th>{{ __('Aksi') }}</th></tr></thead>
<tbody>
@foreach($g['members'] as $m)
<tr>
<td data-label="Institusi"><strong>{{ $m['school']->name }}</strong> @if($g['keep_id'] === $m['school']->id)<span class="admin-pill">{{ __('Disarankan dipertahankan') }}</span>@endif<small>{{ ($m['school']->institution_type ?? 'school') === 'campus' ? __('Kampus') : __('Sekolah') }} · {{ $m['school']->public_slug }}</small></td>
<td data-label="Status">{{ match($m['school']->status) {'active' => __('● Aktif'), 'paused' => __('◷ Dijeda'), default => __('⊘ Diarsipkan')} }}</td>
<td data-label="NPSN">{{ $m['school']->npsn ?: '—' }}</td>
<td data-label="Koordinat">@if($m['lat'] !== null)<span style="color:green">✓ {{ number_format((float) $m['lat'], 4) }}, {{ number_format((float) $m['lng'], 4) }}</span>@else<span style="color:red">✗ {{ __('Belum ada') }}</span>@endif</td>
<td data-label="Akun">{{ $m['accounts'] }}</td>
<td data-label="Laporan">{{ $m['reports'] }}</td>
<td data-label="ID">#{{ $m['school']->id }}</td>
<td data-label="Aksi"><div class="admin-actions"><a class="admin-secondary" href="{{ route('admin.schools.show', $m['school']) }}">{{ __('Lihat institusi') }}</a>@if($canManage)@if($m['school']->status !== 'archived')<form method="post" action="{{ route('admin.schools.archive', $m['school']) }}" data-confirm="{{ __('Arsipkan institusi ini?') }}">@csrf<button class="admin-danger">{{ __('Arsip') }}</button></form>@else<form method="post" action="{{ route('admin.schools.restore', $m['school']) }}" data-confirm="{{ __('Batalkan arsip dan aktifkan lagi institusi ini?') }}">@csrf<button class="admin-secondary">{{ __('Batal arsip') }}</button></form>@endif
@endif
@if($canManage)
@if($m['accounts'] > 0 || $m['reports'] > 0)
<small class="admin-muted">{{ __('Tidak bisa dihapus: masih ada akun/laporan terkait.') }}</small>
@else
<form method="post" action="{{ route('admin.schools.delete', $m['school']) }}" data-confirm="{{ __('Hapus PERMANEN institusi ini? Record dan riwayat perubahannya akan hilang dan tidak bisa dibatalkan.') }}">@csrf<button class="admin-danger">{{ __('Hapus') }}</button></form>
@endif
@endif
</div></td>
</tr>
@endforeach
</tbody></table></div>
</section>
@empty
<div class="admin-panel"><p>{{ __('Tidak ditemukan kelompok duplikat pada cakupanmu. Bagus — direktori bersih untuk dua aturan ini.') }}</p></div>
@endforelse

{{ $groups->links('pagination.admin') }}
@endsection
