<?php

use App\Models\Hazard;
use App\Models\HazardReport;
use App\Models\User;
use App\Services\AdminAccessService;
use App\Services\AdminAccountService;
use App\Services\AdminAuditService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

Artisan::command('ruteaman:status', function (): void {
    $this->info('RuteAman siap. Gunakan /api/status untuk status provider eksternal.');
});

Artisan::command('ruteaman:expire', function (): void {
    $total = 0;
    Hazard::whereIn('status', ['new', 'confirmed', 'verified', 'disputed'])->with('type')->chunkById(100, function ($hazards) use (&$total) {
        foreach ($hazards as $h) {
            if (($h->last_confirmed_at ?? $h->first_reported_at)->diffInMinutes(now()) >= 3 * $h->type->ttl_minutes) {
                $h->update(['status' => 'expired']);
                $total++;
            }
        }
    });
    $this->info("$total hazard kedaluwarsa.");
});
Schedule::command('ruteaman:expire')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
Schedule::command('notifications:dispatch-digests')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('ruteaman:demo-reset {--yes : Konfirmasi reset data khusus demo}', function (): void {
    if (! app()->environment('local', 'testing')) {
        $this->error('Hanya untuk APP_ENV=local/testing.');

        return;
    }
    if (! $this->option('yes') && ! $this->confirm('Hapus laporan akun demo khusus dan reset skenario simulasi? Data akun lain tidak dihapus.')) {
        return;
    }
    $ids = User::whereIn('email', ['siswa_1@ruteaman.test', 'siswa_2@ruteaman.test', 'siswa_3@ruteaman.test'])->pluck('id');
    $hazards = Hazard::whereHas('reports', fn ($q) => $q->whereIn('reporter_id', $ids))->get();
    DB::transaction(function () use ($hazards, $ids) {
        foreach ($hazards as $h) {
            // Never remove a hazard that has evidence submitted by a non-demo user.
            if ($h->public_slug !== 'demo-lubang-jl-mawar' && ($h->reports()->whereNotIn('reporter_id', $ids)->exists() || $h->confirmations()->whereNotIn('user_id', $ids)->exists())) {
                continue;
            }
            // Preserve immutable audit history when resetting local demo fixtures.
            $h->delete();
        }
    });
    $this->call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
    $this->info('Skenario demo direset. Genangan depan pasar: -6.1768, 106.8250.');
});

Artisan::command('ruteaman:privatize-hazard-photos', function (): void {
    $count = 0;
    $disk = Storage::disk('hazard_photos');
    $public = Storage::disk('public');
    HazardReport::whereNotNull('photo_path')->orderBy('id')->chunkById(100, function ($reports) use (&$count, $disk, $public) {
        foreach ($reports as $r) {
            $path = $r->photo_path;
            if (! str_starts_with($path, 'hazards/') || str_contains($path, '..')) {
                throw new RuntimeException('Chemin foto tidak valid.');
            }if ($public->exists($path)) {
                $image = @imagecreatefromstring($public->get($path));
                if (! $image) {
                    throw new RuntimeException('Foto lama tidak dapat diproses; jangan lanjutkan publikasi.');
                }ob_start();
                imagewebp($image, null, 82);
                $bytes = ob_get_clean();
                imagedestroy($image);
                $disk->put($path, $bytes);
                if (hash('sha256', $disk->get($path)) !== hash('sha256', $bytes)) {
                    throw new RuntimeException('Salinan privat tidak cocok.');
                }$public->delete($path);
                $count++;
            }
        }
    });
    $this->info("$count foto dipindahkan dan disanitasi. Periksa salinan lama/CDN/backup publik sebelum membuka situs.");
});

Artisan::command('ruteaman:owner:create {--show-password : Tampilkan teks password saat diketik}', function () {
    $visible = (bool) $this->option('show-password');
    if ($visible) {
        $this->warn('Mode password terlihat aktif. Jangan bagikan layar, rekam terminal, atau kirim screenshot password.');
    }
    $readPassword = fn (string $label) => $visible ? $this->ask($label.' [terlihat]') : $this->secret($label);
    $name = $this->ask('Nama tampilan');
    $email = strtolower(trim($this->ask('Alias login pada domain '.config('ruteaman_admin.alias_domain'))));
    $contact = $this->ask('Email kontak asli');
    $password = $readPassword('Kata sandi (minimal 12 karakter)');
    $confirm = $readPassword('Ulangi kata sandi');
    if (strlen($password ?? '') < 12 || $password !== $confirm || ! filter_var($contact, FILTER_VALIDATE_EMAIL) || strlen($name ?? '') < 2 || in_array(mb_strtolower(trim($name ?? '')), config('ruteaman_admin.reserved'), true)) {
        $this->error('Isian tidak valid.');

        return 1;
    }
    try {
        DB::transaction(function () use ($name, $email, $contact, $password) {
            $access = app(AdminAccessService::class);
            $access->lockOwners();
            if (User::where('role', 'owner')->whereIn('status', ['active', 'pending'])->lockForUpdate()->get()->count() >= config('ruteaman_admin.owner_max')) {
                throw new RuntimeException('Batas Owner tercapai.');
            }$alias = app(AdminAccountService::class)->alias($email);
            $u = (new User)->forceFill(['display_name' => $name, 'email' => $alias, 'contact_email' => $contact, 'password' => $password, 'role' => 'owner', 'status' => 'active', 'active' => true, 'permissions' => [], 'must_change_password' => false]);
            $u->save();
            app(AdminAuditService::class)->write(null, 'owner.created', 'user', $u->id);
        });
        $this->info('Admin Utama dibuat. 2FA belum tersedia; lindungi akses console dan SMTP.');
    } catch (Throwable $e) {
        $this->error($e instanceof ValidationException ? 'Alias tidak valid atau sudah digunakan.' : $e->getMessage());

        return 1;
    }
});
Artisan::command('ruteaman:owner:reset {--show-password : Tampilkan teks password saat diketik}', function () {
    $visible = (bool) $this->option('show-password');
    if ($visible) {
        $this->warn('Mode password terlihat aktif. Jangan bagikan layar, rekam terminal, atau kirim screenshot password.');
    }
    $readPassword = fn (string $label) => $visible ? $this->ask($label.' [terlihat]') : $this->secret($label);
    $email = $this->ask('Alias Owner yang dipulihkan');
    $password = $readPassword('Kata sandi baru (minimal 12 karakter)');
    $confirm = $readPassword('Ulangi kata sandi');
    if (strlen($password ?? '') < 12 || $password !== $confirm) {
        $this->error('Kata sandi tidak sesuai.');

        return 1;
    }
    DB::transaction(function () use ($email, $password) {
        $a = app(AdminAccessService::class);
        $a->lockOwners();
        $u = User::where('email', $email)->where('role', 'owner')->lockForUpdate()->firstOrFail();
        if ($u->status !== 'active' && User::where('role', 'owner')->whereIn('status', ['active', 'pending'])->lockForUpdate()->get()->count() >= config('ruteaman_admin.owner_max')) {
            throw new RuntimeException('Batas Owner tercapai.');
        }$u->forceFill(['password' => $password, 'status' => 'active', 'active' => true, 'must_change_password' => false])->save();
        $a->revoke($u);
        Password::broker('invites')->deleteToken($u);
        app(AdminAuditService::class)->write(null, 'owner.recovered', 'user', $u->id);
    });
    $this->info('Owner dipulihkan; semua sesi lama dicabut.');
});

Artisan::command('ruteaman:admin:report-legacy-tiers', function (): int {
    $rows = User::whereIn('role', ['platform_admin', 'school_staff'])->orderBy('id')->get();
    if ($rows->isEmpty()) {
        $this->info('Tidak ada akun tingkat lama.');

        return 0;
    }
    $this->table(['ID', 'Nama', 'Alias', 'Tingkat lama', 'Status', 'Sekolah'], $rows->map(fn ($u) => [$u->id, $u->display_name, $u->email, $u->role->value, $u->status, $u->school_id ?? '—'])->all());
    $this->warn('Hanya baca: tidak ada tingkat, izin, atau akses yang diubah. Owner perlu meninjau dan memilih migrasi atau penangguhan.');

    return 0;
});
Artisan::command('ruteaman:audit:inspect', function (): int {
    $this->line(json_encode(['driver' => DB::getDriverName(), 'indexes' => Schema::getIndexes('audit_logs'), 'columns' => Schema::getColumns('audit_logs')], JSON_PRETTY_PRINT));

    return 0;
});

Artisan::command('ruteaman:campus-catalog-import {--file= : Berkas JSONL.GZ lokal} {--metadata= : Metadata checksum lokal}',function():void{
 try{$n=app(\App\Services\CampusCatalogImport::class)->run($this->option('file')?:resource_path('data/campus-catalog-v3.jsonl.gz'),$this->option('metadata')?:resource_path('data/campus-catalog-v3.metadata.json'));$this->info("$n entri sumber diimpor. Akun, pin, catchment, fitur, dan link kampus terdaftar tidak diubah.");}
 catch(\Throwable $e){$this->error($e->getMessage());$this->fail('Impor katalog gagal; transaksi dibatalkan.');}
});

Artisan::command('ruteaman:campus-ui-check', function () {
    $manifestPath = public_path('build/manifest.json');
    if (!is_file($manifestPath)) { $this->error('Manifest build belum terunggah.'); return 1; }
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (!is_array($manifest)) { $this->error('Manifest build tidak valid.'); return 1; }
    $missing = [];
    foreach ($manifest as $entry) {
        foreach (array_filter([$entry['file'] ?? null, ...($entry['css'] ?? [])]) as $file) {
            if (!is_file(public_path('build/'.$file))) $missing[] = $file;
        }
    }
    if (!is_file(public_path('css/campus-directory-v4.css'))) $missing[] = 'css/campus-directory-v4.css';
    if (!is_file(public_path('css/campus-map-v5.css'))) $missing[] = 'css/campus-map-v5.css';
    if (!is_file(public_path('css/map-auto-v7.css'))) $missing[] = 'css/map-auto-v7.css';
    if ($missing) { $this->error('Aset belum lengkap: '.implode(', ', array_unique($missing))); return 1; }
    $entry = $manifest['resources/js/app.js']['file'] ?? '';
    if (!$entry || !str_contains(file_get_contents(public_path('build/'.$entry)), 'searchVersion') || !str_contains(file_get_contents(public_path('build/'.$entry)), 'Tekan Tampilkan kondisi')) {
        $this->error('JavaScript peta/pencarian masih versi lama. Unggah seluruh build + manifest v6.'); return 1;
    }
    $this->info('Aset UI kampus/peta v6 lengkap. Bersihkan cache aplikasi dan CDN, lalu hard refresh browser.');
    return 0;
})->purpose('Memeriksa kelengkapan aset UI dan versi pencarian kampus');

Artisan::command('ruteaman:catalog-stats', function (): int {
    if (!Schema::hasTable('school_catalog_cache')) {
        $this->error('Tabel school_catalog_cache belum ada. Jalankan: php artisan migrate');
        return 1;
    }
    $total = DB::table('school_catalog_cache')->count();
    $this->info("=== STATISTIK KATALOG SEKOLAH LOKAL ===");
    $this->line("Total rekaman: " . number_format($total));
    $this->line("");
    $this->line("Per provinsi (10 teratas):");
    foreach (DB::table('school_catalog_cache')->select('province', DB::raw('COUNT(*) as c'))->groupBy('province')->orderByDesc('c')->limit(10)->get() as $row) {
        $this->line("  " . ($row->province ?: '(kosong)') . ": " . number_format($row->c));
    }
    $this->line("");
    $this->line("Per jenjang:");
    foreach (DB::table('school_catalog_cache')->select('level', DB::raw('COUNT(*) as c'))->groupBy('level')->orderByDesc('c')->get() as $row) {
        $this->line("  " . ($row->level ?: '(kosong)') . ": " . number_format($row->c));
    }
    $this->line("");
    $withCoords = DB::table('school_catalog_cache')->whereNotNull('latitude')->whereNotNull('longitude')->count();
    $this->line("Dengan koordinat: " . number_format($withCoords) . " (" . ($total ? round($withCoords/$total*100, 1) : 0) . "%)");
    $this->warn("CATATAN: Ini cache progresif dari hasil API, bukan klaim cakupan nasional penuh.");
    return 0;
})->purpose('Statistik kelengkapan katalog sekolah lokal');
