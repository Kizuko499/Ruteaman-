@extends('admin.layout')
@section('title',__('Detail Pengiriman Email'))
@section('content')
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('EMAIL & NOTIFIKASI') }}</p><h1>{{ __('Detail Pengiriman') }}</h1><p>{{ $log->subject }}</p></div>

@include('admin.email._tabs', ['tab' => 'riwayat'])

<section class="admin-panel">
<div class="admin-table-wrap"><table class="admin-table"><tbody>
<tr><th>{{ __('Status') }}</th><td>{{ $log->statusText() }}</td><th>{{ __('Kategori') }}</th><td>{{ $log->categoryText() }}</td></tr>
<tr><th>{{ __('Penerima') }}</th><td>{{ $log->recipient_masked }}@if($log->notificationRecipient)<br><small>{{ __('Penerima mandiri:') }} {{ $log->notificationRecipient->name }}</small>@elseif($log->user)<br><small>{{ __('Akun:') }} {{ $log->user->display_name }}</small>@endif</td><th>{{ __('Percobaan') }}</th><td>{{ $log->attempts }}</td></tr>
<tr><th>{{ __('Masuk antrean') }}</th><td>{{ $log->queued_at?->format('d M Y H:i:s') ?? '—' }}</td><th>{{ __('Terkirim') }}</th><td>{{ $log->sent_at?->format('d M Y H:i:s') ?? '—' }}</td></tr>
<tr><th>{{ __('Gagal terakhir') }}</th><td>{{ $log->failed_at?->format('d M Y H:i:s') ?? '—' }}</td><th>{{ __('Kunci anti-duplikat') }}</th><td>{{ $log->dedupe_key ?? '—' }}</td></tr>
@if($log->error)<tr><th>{{ __('Pesan kesalahan') }}</th><td colspan="3">{{ $log->error }}</td></tr>@endif
</tbody></table></div>

@if($log->payload)
<h2>{{ __('Detail teknis pengiriman') }}</h2>
<p class="admin-muted">{{ __('Bagian ini hanya berisi kelas pembentuk email dan argumen skalar (label, nama institusi, tautan publik). URL bertanda tangan, token, dan kredensial tidak pernah disimpan di catatan ini.') }}</p>
<pre style="white-space:pre-wrap;word-break:break-word;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
@endif

<div class="admin-actions">
<a class="admin-secondary" href="{{ route('admin.email.history') }}">{{ __('Kembali ke riwayat') }}</a>
@if($log->status==='failed' && $log->payload)<form method="post" action="{{ route('admin.email.retry', $log) }}">@csrf<button class="admin-secondary" type="submit">{{ __('Kirim ulang') }}</button></form>@endif
@if($log->status==='queued')<form method="post" action="{{ route('admin.email.cancel', $log) }}" data-confirm="{{ __('Batalkan pengiriman ini?') }}">@csrf<button class="admin-secondary" type="submit">{{ __('Batalkan pengiriman') }}</button></form>@endif
</div>
</section>
@endsection
