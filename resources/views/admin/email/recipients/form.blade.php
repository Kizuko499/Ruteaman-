@extends('admin.layout')
@section('title', $recipient ? __('Ubah Penerima Notifikasi') : __('Tambah Penerima Notifikasi'))
@section('content')
<div class="admin-page-head"><p class="admin-eyebrow">{{ __('EMAIL & NOTIFIKASI') }}</p><h1>{{ $recipient ? __('Ubah Penerima Notifikasi') : __('Tambah Penerima Notifikasi') }}</h1><p>{{ __('Penerima baru menerima email verifikasi terlebih dahulu; notifikasi dan ringkasan mulai mengalir setelah alamat terverifikasi.') }}</p></div>

@include('admin.email._tabs', ['tab' => 'penerima'])

@if($errors->any())<div class="admin-alert error" role="alert"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

@php($selectedCats = old('categories', $recipient?->categoryKeys() ?? []))
<form method="post" action="{{ $recipient ? route('admin.email.recipients.update', $recipient) : route('admin.email.recipients.store') }}" class="admin-panel admin-editor" data-dirty-watch>@csrf @if($recipient) @method('PATCH') @endif

<h2>{{ __('Identitas penerima') }}</h2>
<div class="admin-field-grid">
<label>{{ __('Nama penerima') }}<input name="name" required maxlength="100" value="{{ old('name', $recipient?->name) }}"></label>
<label>{{ __('Alamat email') }}<input type="email" name="email" required maxlength="190" value="{{ old('email', $recipient?->email) }}"><small>{{ __('Mengubah alamat email mewajibkan verifikasi ulang.') }}</small></label>
<label>{{ __('Jabatan / keterangan') }}<input name="description" maxlength="190" value="{{ old('description', $recipient?->description) }}" placeholder="{{ __('mis. Kepala sekolah') }}"></label>
@if($platformActor)
<label>{{ __('Cakupan') }}<select name="scope"><option value="platform" @selected(old('scope', $recipient ? ($recipient->school_id ? 'school' : 'platform') : 'platform')==='platform')>{{ __('Semua institusi (platform)') }}</option><option value="school" @selected(old('scope', $recipient ? ($recipient->school_id ? 'school' : 'platform') : 'platform')==='school')>{{ __('Satu institusi') }}</option></select></label>
<label>{{ __('Institusi (NPSN atau nama persis)') }}<input name="school_query" maxlength="190" value="{{ old('school_query', $recipient?->school?->npsn ?? $recipient?->school?->name) }}" placeholder="{{ __('Isi bila cakupan satu institusi') }}">@if($recipient?->school)<small>{{ __('Saat ini:') }} {{ $recipient->school->name }}</small>@endif</label>
@else
<input type="hidden" name="scope" value="school">
<p class="admin-muted">{{ __('Cakupan penerima ini adalah institusimu sendiri.') }}</p>
@endif
</div>

<h2>{{ __('Mode & jadwal pengiriman') }}</h2>
<div class="admin-field-grid">
<label>{{ __('Mode') }}<select name="mode">@foreach(\App\Models\NotificationRecipient::MODES as $k=>$v)<option value="{{ $k }}" @selected(old('mode', $recipient?->mode ?? 'realtime')===$k)>{{ $v }}</option>@endforeach</select></label>
<label>{{ __('Frekuensi ringkasan') }}<select name="frequency_type">@foreach(\App\Models\NotificationRecipient::FREQUENCIES as $k=>$v)<option value="{{ $k }}" @selected(old('frequency_type', $recipient?->frequency_type ?? 'weekly')===$k)>{{ $v }}</option>@endforeach</select></label>
<label>{{ __('Interval (hari, untuk mode interval)') }}<input type="number" name="interval_days" min="1" max="365" value="{{ old('interval_days', $recipient?->interval_days ?? 1) }}"><small>{{ __('Dihitung dari jadwal terakhir, bukan dari tanggal dibuat.') }}</small></label>
<label>{{ __('Hari (untuk mode mingguan)') }}<select name="weekly_day">@foreach(\App\Models\NotificationRecipient::WEEKDAYS as $k=>$v)<option value="{{ $k }}" @selected((int) old('weekly_day', $recipient?->weekly_day ?? 1)===$k)>{{ $v }}</option>@endforeach</select></label>
<label>{{ __('Pola bulanan') }}<select name="monthly_mode"><option value="date" @selected(old('monthly_mode', $recipient?->monthly_mode ?? 'date')==='date')>{{ __('Tanggal tertentu') }}</option><option value="first" @selected(old('monthly_mode', $recipient?->monthly_mode)==='first')>{{ __('Hari pertama bulan') }}</option><option value="last" @selected(old('monthly_mode', $recipient?->monthly_mode)==='last')>{{ __('Hari terakhir bulan') }}</option></select></label>
<label>{{ __('Tanggal (untuk pola tanggal tertentu)') }}<input type="number" name="monthly_day" min="1" max="31" value="{{ old('monthly_day', $recipient?->monthly_day ?? 1) }}"><small>{{ __('Tanggal yang tidak ada pada suatu bulan dijepit ke hari terakhir bulan itu.') }}</small></label>
<label>{{ __('Jam pengiriman') }}<input type="time" name="send_time" required value="{{ old('send_time', $recipient ? substr((string) $recipient->send_time, 0, 5) : '08:00') }}"></label>
<label>{{ __('Zona waktu') }}<select name="timezone">@foreach($timezones as $tz)<option value="{{ $tz }}" @selected(old('timezone', $recipient?->timezone ?? 'Asia/Jakarta')===$tz)>{{ $tz }}</option>@endforeach</select></label>
<label>{{ __('Jam tenang mulai (opsional)') }}<input type="time" name="quiet_start" value="{{ old('quiet_start', $recipient?->quiet_start ? substr((string) $recipient->quiet_start, 0, 5) : '') }}"><small>{{ __('Ringkasan & notifikasi nonkritis tidak dikirim pada jam tenang; laporan baru tetap langsung.') }}</small></label>
<label>{{ __('Jam tenang selesai (opsional)') }}<input type="time" name="quiet_end" value="{{ old('quiet_end', $recipient?->quiet_end ? substr((string) $recipient->quiet_end, 0, 5) : '') }}"></label>
<label><input type="checkbox" name="send_empty_digest" value="1" @checked(old('send_empty_digest', $recipient?->send_empty_digest ?? false))> {{ __('Tetap kirim ringkasan walau tidak ada aktivitas baru') }}</label>
</div>

<div class="recipient-all-reports">
<label><input type="checkbox" name="receive_all_reports" value="1" @checked(old('receive_all_reports', $recipient?->receive_all_reports ?? false))> <strong>{{ __('Terima setiap laporan baru yang masuk') }}</strong></label>
<p class="admin-muted">{{ __('Bila aktif, penerima ini selalu menerima email untuk SETIAP laporan baru di cakupannya (mode Langsung/Kombinasi), tanpa perlu mencentang kategori "Laporan baru masuk" di bawah. Matikan bila penerima hanya boleh menerima laporan tertentu lewat langganan kategori.') }}</p>
</div>

<h2>{{ __('Langganan informasi') }}</h2>
<p class="admin-muted">{{ __('Hanya informasi terpilih yang dikirim ke penerima ini. Kategori khusus platform otomatis diabaikan untuk penerima bercakupan institusi.') }}</p>
@foreach($categoryGroups as $groupTitle => $items)
<fieldset class="admin-scope"><legend>{{ $groupTitle }}</legend>
@foreach($items as $key => $label)
<label><input type="checkbox" name="categories[]" value="{{ $key }}" @checked(in_array($key, $selectedCats, true))> {{ $label }}</label>
@endforeach
</fieldset>
@endforeach

<div class="admin-sticky-actions"><a class="admin-secondary" href="{{ route('admin.email.recipients.index') }}">{{ __('Batal') }}</a><button class="admin-primary" type="submit">{{ $recipient ? __('Simpan perubahan') : __('Tambah penerima notifikasi & kirim verifikasi') }}</button></div>
</form>
@endsection
