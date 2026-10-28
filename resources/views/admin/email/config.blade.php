@extends('admin.layout')
@section('title',__('Konfigurasi Email'))
@section('content')
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('EMAIL & NOTIFIKASI') }}</p><h1>{{ __('Konfigurasi Email') }}</h1><p>{{ __('Konfigurasi dibaca dari pengaturan server dan tidak dapat diubah dari halaman ini. Kata sandi SMTP tidak pernah ditampilkan.') }}</p></div>

@include('admin.email._tabs', ['tab' => 'konfigurasi'])

@if($mailerWarning)<p class="admin-alert error" role="alert">{{ $mailerWarning }}</p>@endif

<h2>{{ __('Konfigurasi aktif') }}</h2>
<div class="admin-table-wrap"><table class="admin-table"><tbody>
<tr><th>{{ __('Mailer') }}</th><td>{{ $config['mailer'] }}</td><th>{{ __('Antrean') }}</th><td>{{ $config['queue'] }}</td></tr>
<tr><th>{{ __('Host SMTP') }}</th><td>{{ $config['host'] ?: '—' }}</td><th>{{ __('Port / Enkripsi') }}</th><td>{{ $config['port'] ?: '—' }} / {{ $config['encryption'] ?: '—' }}</td></tr>
<tr><th>{{ __('Nama pengguna SMTP') }}</th><td>{{ $config['username_set'] ? __('Terisi (tidak ditampilkan)') : __('Belum terisi') }}</td><th>{{ __('Pengirim') }}</th><td>{{ $config['from_name'] }} &lt;{{ $config['from'] ?: '—' }}&gt;</td></tr>
</tbody></table></div>

<h2>{{ __('Diagnostik koneksi SMTP') }}</h2>
<section class="admin-panel">
@if($diag)
<p>{{ $diag['ok'] ? __('Hasil uji terakhir: BERHASIL') : __('Hasil uji terakhir: GAGAL') }} — {{ \Illuminate\Support\Carbon::parse($diag['checked_at'])->format('d M Y H:i') }} {{ __('(waktu server)') }}</p>
<div class="admin-table-wrap"><table class="admin-table"><tbody>
<tr><th>{{ __('Tahap') }}</th><td>{{ $diag['stage'] }}</td><th>{{ __('Host : Port') }}</th><td>{{ $diag['host'] }} : {{ $diag['port'] }} ({{ $diag['encryption'] }})</td></tr>
<tr><th>{{ __('Pesan') }}</th><td colspan="3">{{ $diag['message'] }}</td></tr>
@if($diag['cert_subject'])<tr><th>{{ __('Sertifikat untuk') }}</th><td>{{ $diag['cert_subject'] }}</td><th>{{ __('Berlaku sampai') }}</th><td>{{ $diag['cert_expires'] ?? '—' }}</td></tr>@endif
@if($diag['cert_sans'])<tr><th>{{ __('Nama alternatif sertifikat') }}</th><td colspan="3">{{ $diag['cert_sans'] }}</td></tr>@endif
</tbody></table></div>
@else
<p>{{ __('Belum ada hasil uji koneksi. Jalankan uji untuk memeriksa koneksi SMTP dan kecocokan sertifikat dengan nama host — tanpa mengirim email dan tanpa mematikan verifikasi TLS.') }}</p>
@endif
@if(app(\App\Services\PermissionService::class)->can(auth()->user(),'email.manage'))<form method="post" action="{{ route('admin.email.smtp-test') }}">@csrf<button class="admin-primary" type="submit">{{ __('Uji koneksi SMTP') }}</button></form>@else<p class="admin-muted">{{ __('Tombol uji koneksi memerlukan izin Kelola pengiriman email (email.manage).') }}</p>@endif
</section>

<h2>{{ __('Kirim email uji') }}</h2>
<form method="post" action="{{ route('admin.email.test') }}" class="admin-filter">@csrf
<label>{{ __('Alamat tujuan uji') }}<input type="email" name="email" required maxlength="190" placeholder="nama@contoh.com"></label>
<button class="admin-secondary" type="submit">{{ __('Kirim email uji') }}</button>
</form>

<h2>{{ __('Petunjuk error sertifikat') }}</h2>
<section class="admin-panel">
<p><strong>{{ __('"Peer certificate subjectAltName did not match expected name"') }}</strong></p>
<p>{{ __('Artinya sertifikat TLS server email diterbitkan untuk nama host yang berbeda dari MAIL_HOST. Ini BUKAN kesalahan kata sandi dan tidak boleh diatasi dengan mematikan verifikasi sertifikat. Perbaikannya:') }}</p>
<ul>
<li>{{ __('Gunakan nama host yang persis sama dengan yang tertera pada sertifikat server email (umumnya hostname server hosting, bukan nama domain yang melewati proxy CDN).') }}</li>
<li>{{ __('Periksa dengan tombol Uji Koneksi SMTP di atas: hasil uji menampilkan nama pada sertifikat yang diterima server.') }}</li>
<li>{{ __('Setelah mengganti MAIL_HOST di konfigurasi server, bersihkan cache konfigurasi lalu kirim email uji dan pastikan statusnya Terkirim di tab Riwayat.') }}</li>
</ul>
</section>
@endsection
