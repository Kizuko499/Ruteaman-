@extends('admin.layout')
@section('title',__('Pengiriman Email'))
@section('content')
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('EMAIL & NOTIFIKASI') }}</p><h1>{{ __('Pengiriman Email') }}</h1><p>{{ __('Status konfigurasi, antrean, penerima, dan jadwal notifikasi. Kredensial SMTP tidak pernah ditampilkan di halaman ini.') }}</p></div>

@include('admin.email._tabs', ['tab' => 'ringkasan'])

@if(!$config['ready'])
<p class="admin-alert error" role="alert">{{ __('Konfigurasi email belum lengkap: mailer masih "') }}{{ $config['mailer'] }}{{ __('" atau alamat pengirim/host kosong. Email notifikasi belum benar-benar terkirim. Atur SMTP pada konfigurasi server lalu uji dari tab Konfigurasi.') }}</p>
@endif

<section class="admin-stat-grid" aria-label="{{ __('Ringkasan pengiriman') }}">
<div class="admin-stat"><span>{{ __('Dalam antrean') }}</span><strong>{{ $stats['queued'] }}</strong><small>{{ __('Menunggu diproses antrean') }}</small></div>
<div class="admin-stat"><span>{{ __('Terkirim') }}</span><strong>{{ $stats['sent'] }}</strong><small>{{ __('Berhasil dikirim oleh server email') }}</small></div>
<div class="admin-stat"><span>{{ __('Gagal') }}</span><strong>{{ $stats['failed'] }}</strong><small>{{ __('Perlu ditinjau atau dikirim ulang') }}</small></div>
<div class="admin-stat"><span>{{ __('Dibatalkan') }}</span><strong>{{ $stats['cancelled'] }}</strong><small>{{ __('Dibatalkan sebelum terkirim') }}</small></div>
<div class="admin-stat"><span>{{ __('Penerima aktif') }}</span><strong>{{ $stats['recipients_active'] }}</strong><small>{{ __('Penerima mandiri terverifikasi & aktif') }}</small></div>
<div class="admin-stat"><span>{{ __('Jadwal aktif') }}</span><strong>{{ $stats['schedules_active'] }}</strong><small>{{ __('Ringkasan berkala terjadwal') }}</small></div>
<div class="admin-stat"><span>{{ __('Terakhir terkirim') }}</span><strong>{{ $stats['last_sent_at'] ? \Illuminate\Support\Carbon::parse($stats['last_sent_at'])->format('d M H:i') : '—' }}</strong><small>{{ __('Waktu server') }}</small></div>
</section>

<h2>{{ __('Pengiriman berikutnya') }}</h2>
<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>{{ __('Penerima') }}</th><th>{{ __('Jenis') }}</th><th>{{ __('Frekuensi') }}</th><th>{{ __('Pengiriman berikutnya') }}</th></tr></thead>
<tbody>
@forelse($nextDeliveries as $r)
<tr><td><strong>{{ $r->name }}</strong><br><small>{{ $r->email }} · {{ $r->school?->name ?? __('Semua institusi (platform)') }}</small></td><td>{{ $r->modeLabel() }}</td><td>{{ $r->frequencyLabel() }}</td><td>{{ $r->next_run_at?->format('d M Y H:i') }} <small>({{ $r->timezone }})</small></td></tr>
@empty
<tr><td colspan="4">{{ __('Belum ada jadwal ringkasan aktif. Tambahkan penerima dengan mode Ringkasan atau Kombinasi di tab Penerima Notifikasi.') }}</td></tr>
@endforelse
</tbody></table></div>

<h2>{{ __('Konfigurasi aktif') }}</h2>
<div class="admin-table-wrap"><table class="admin-table"><tbody>
<tr><th>{{ __('Mailer') }}</th><td>{{ $config['mailer'] }}</td><th>{{ __('Antrean') }}</th><td>{{ $config['queue'] }}</td></tr>
<tr><th>{{ __('Host SMTP') }}</th><td>{{ $config['host'] ?: '—' }}</td><th>{{ __('Port / Enkripsi') }}</th><td>{{ $config['port'] ?: '—' }} / {{ $config['encryption'] ?: '—' }}</td></tr>
<tr><th>{{ __('Pengirim') }}</th><td colspan="3">{{ $config['from_name'] }} &lt;{{ $config['from'] ?: '—' }}&gt;</td></tr>
</tbody></table></div>
<p class="admin-muted">{{ __('Uji koneksi SMTP, kirim email uji, dan petunjuk perbaikan error ada di tab Konfigurasi Email.') }}</p>
@endsection
