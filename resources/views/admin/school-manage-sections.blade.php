@if($permissions->can($viewer, 'schools.features', $school))
<section class="admin-panel"><h2>{{ __('Fitur institusi') }}</h2>
<p>{{ __('Saklar fitur khusus institusi ini — sama seperti di halaman Fitur Umum & Sekolah, tersimpan hanya untuk institusi ini.') }}</p>
<form method="post" action="{{ route('admin.schools.features', $school) }}" data-sensitive data-dirty-watch>@csrf @method('PATCH')
@include('admin.feature-switches', ['school' => $school, 'defaultFeatures' => $generalFeatures])
<div class="admin-actions"><button class="admin-primary">{{ __('Simpan fitur institusi') }}</button></div>
</form></section>
@endif

<section class="admin-panel"><h2>{{ __('Akun pengelola') }}</h2>
@if($accounts->isEmpty())
@if($permissions->can($viewer, 'schools.provision') && $permissions->allScope($viewer))
<p class="admin-notice">{{ __('Institusi ini belum memiliki akun pengelola sama sekali. Buat akun pertamanya dengan formulir di bawah ini — akun dibuat sebagai Menunggu undangan dan tautan undangannya hanya ditampilkan sekali.') }}</p>
@else
<p class="admin-notice">{{ __('Institusi ini belum memiliki akun pengelola sama sekali. Pembuatan akun pertamanya dilakukan oleh Admin Utama dari halaman ini.') }}</p>
@endif
@else
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>{{ __('Nama') }}</th><th>{{ __('Tingkat') }}</th><th>{{ __('Alias login') }}</th><th>{{ __('Email kontak') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
@foreach($accounts as $acc)
<tr><td><strong>{{ $acc->display_name }}</strong></td><td>{{ $acc->role->label() }}</td><td>{{ $acc->email }}</td><td>{{ $acc->contact_email ?: '—' }}</td><td>{{ $acc->status === 'pending' ? __('Menunggu undangan') : ($acc->active ? __('Aktif') : __('Nonaktif')) }}</td></tr>
@endforeach
</tbody></table></div>
@endif
@if($accounts->isEmpty() && $permissions->can($viewer, 'schools.provision') && $permissions->allScope($viewer))
<h3>{{ __('Buat akun pengelola') }}</h3>
<form method="post" action="{{ route('admin.schools.account.store', $school) }}" data-sensitive>
@csrf
<div class="admin-field-grid">
<label>{{ __('Nama tampilan') }}<input name="display_name" required minlength="2" maxlength="100" value="{{ old('display_name') }}"></label>
<label>{{ __('Alias login') }}<input type="email" name="email" required value="{{ old('email') }}" placeholder="{{ 'pengelola@'.config('ruteaman_admin.alias_domain') }}"><small>{{ __('Identitas login, bukan kotak surat.') }}</small></label>
<label>{{ __('Email kontak asli') }}<input type="email" name="contact_email" required value="{{ old('contact_email') }}"><small>{{ __('Alamat penerima undangan dan pemberitahuan.') }}</small></label>
</div>
@include('admin.permission-editor', ['target' => null])
<div class="admin-actions"><button class="admin-primary">{{ __('Buat akun & kirim undangan') }}</button></div>
</form>
@endif
</section>

@if($permissions->allScope($viewer) && in_array($viewer->role, [\App\Enums\Role::Owner, \App\Enums\Role::PlatformAdmin], true) && $permissions->can($viewer, 'schools.manage', $school))
<section class="admin-panel admin-danger-zone"><h2>{{ __('Zona berbahaya') }}</h2>
<p>{{ __('Hapus permanen institusi ini beserta riwayat perubahannya — tidak bisa dibatalkan. Untuk institusi yang masih dipakai, cukup arsipkan lewat formulir data di atas.') }}</p>
@error('school_delete')<p class="admin-notice" role="alert">{{ $message }}</p>@enderror
@if(($deleteBlockers['accounts'] ?? 0) > 0 || ($deleteBlockers['reports'] ?? 0) > 0)
<p class="admin-notice">{{ __('Belum dapat dihapus: masih ada :accounts akun dan :reports laporan yang terkait institusi ini. Pindahkan atau hapus keterkaitan itu dulu — atau arsipkan institusinya.', ['accounts' => $deleteBlockers['accounts'] ?? 0, 'reports' => $deleteBlockers['reports'] ?? 0]) }}</p>
@else
<form method="post" action="{{ route('admin.schools.delete', $school) }}" data-confirm="{{ __('Hapus PERMANEN institusi ini? Record dan riwayat perubahannya akan hilang dan tidak bisa dibatalkan.') }}">@csrf<button class="admin-danger">{{ __('Hapus permanen institusi ini') }}</button></form>
@endif
</section>
@endif
