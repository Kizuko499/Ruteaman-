@extends('admin.layout')
@section('title',__('Penerima Notifikasi'))
@section('content')
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('EMAIL & NOTIFIKASI') }}</p><h1>{{ __('Penerima Notifikasi') }}</h1><p>{{ __('Alamat email di luar akun pengguna yang menerima notifikasi dan ringkasan. Alamat belum terverifikasi tidak menerima kiriman apa pun selain email verifikasi dan email uji admin.') }}</p></div>

@include('admin.email._tabs', ['tab' => 'penerima'])

<section class="admin-stat-grid" aria-label="{{ __('Ringkasan penerima') }}">
<div class="admin-stat"><span>{{ __('Penerima aktif & terverifikasi') }}</span><strong>{{ $activeCount }}</strong><small>{{ __('Siap menerima kiriman') }}</small></div>
<div class="admin-stat"><span>{{ __('Jadwal ringkasan aktif') }}</span><strong>{{ $scheduleCount }}</strong><small>{{ __('Mode ringkasan / kombinasi') }}</small></div>
</section>

<p><a class="admin-primary" href="{{ route('admin.email.recipients.create') }}">{{ __('Tambah penerima notifikasi') }}</a></p>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>{{ __('Penerima') }}</th><th>{{ __('Cakupan') }}</th><th>{{ __('Langganan') }}</th><th>{{ __('Mode / Frekuensi') }}</th><th>{{ __('Terkirim bulan ini') }}</th><th>{{ __('Status') }}</th><th>{{ __('Terakhir / Berikutnya') }}</th><th></th></tr></thead>
<tbody>
@forelse($recipients as $r)
<tr>
<td><strong>{{ $r->name }}</strong><br>{{ $r->email }}@if($r->description)<br><small>{{ $r->description }}</small>@endif</td>
<td>{{ $r->school?->name ?? __('Semua institusi (platform)') }}</td>
<td>{{ $r->category_rows_count }} {{ __('kategori') }}<br><small>@foreach($r->categoryKeys() as $k){{ \App\Models\NotificationRecipient::categoryLabel($k) }}@if(!$loop->last), @endif @endforeach</small></td>
<td>{{ $r->modeLabel() }}
@if($r->receive_all_reports)<br><small><strong>{{ __('Terima setiap laporan baru') }}</strong></small>@endif
@if($r->mode!=='realtime')<br><small>{{ $r->frequencyLabel() }}</small>@endif</td>
<td>{{ $r->sent_this_month ?? 0 }} {{ __('email') }}</td>
<td>{{ $r->is_active ? __('Aktif') : __('Nonaktif') }}<br><small>{{ $r->verified_at ? __('Terverifikasi') : __('Belum terverifikasi') }}</small></td>
<td><small>{{ __('Terakhir:') }} {{ $r->last_sent_at?->format('d M Y H:i') ?? '—' }}<br>{{ __('Berikutnya:') }} {{ $r->next_run_at?->format('d M Y H:i') ?? '—' }}</small></td>
<td><div class="admin-actions">
<a class="admin-secondary" href="{{ route('admin.email.recipients.edit', $r) }}">{{ __('Ubah') }}</a>
<form method="post" action="{{ route('admin.email.recipients.test', $r) }}">@csrf<button class="admin-secondary" type="submit">{{ __('Email uji') }}</button></form>
@if(!$r->verified_at)<form method="post" action="{{ route('admin.email.recipients.verify-resend', $r) }}">@csrf<button class="admin-secondary" type="submit">{{ __('Kirim ulang verifikasi') }}</button></form>@endif
<form method="post" action="{{ route('admin.email.recipients.toggle', $r) }}">@csrf<button class="admin-secondary" type="submit">{{ $r->is_active ? __('Nonaktifkan') : __('Aktifkan') }}</button></form>
<form method="post" action="{{ route('admin.email.recipients.destroy', $r) }}" data-confirm="{{ __('Hapus penerima ini? Jadwal dan langganannya ikut terhapus.') }}">@csrf @method('DELETE')<button class="admin-danger" type="submit">{{ __('Hapus') }}</button></form>
</div></td>
</tr>
@empty
<tr><td colspan="7">{{ __('Belum ada penerima notifikasi. Tambahkan alamat pertama, misalnya kepala sekolah atau operator, lalu minta pemilik alamat memverifikasinya dari email yang diterima.') }}</td></tr>
@endforelse
</tbody></table></div>
{{ $recipients->links() }}
@endsection
