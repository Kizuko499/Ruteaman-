<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\AdminException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class GlobalFeatureSettings
{
    private ?array $memo = null;

    public function defaults(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }
        $base = config('ruteaman_admin.features');
        if (! Schema::hasTable('platform_feature_settings')) {
            return $base;
        }
        $row = DB::table('platform_feature_settings')->where('id', 1)->first();
        $stored = json_decode($row?->features ?? '', true);
        if (! is_array($stored)) {
            throw new AdminException('CONFIG_INVALID', 'Pengaturan umum tidak valid. Hubungi pengelola.', 503);
        }

        return $this->memo = array_replace($base, array_map(fn ($v) => (bool) $v, array_intersect_key($stored, $base)));
    }

    public function enabled(string $key): bool
    {
        return (bool) ($this->defaults()[$key] ?? false);
    }

    public function forget(): void
    {
        $this->memo = null;
    }

    public function updatedAt(): ?string
    {
        return Schema::hasTable('platform_feature_settings') ? DB::table('platform_feature_settings')->where('id', 1)->value('updated_at') : null;
    }

    public function apply(User $actor, array $features): void
    {
        if (array_diff_key($features, config('ruteaman_admin.features'))) {
            throw ValidationException::withMessages(['features' => 'Fitur tidak dikenal. Muat ulang halaman.']);
        }
        if (! Schema::hasTable('platform_feature_settings')) {
            throw new AdminException('MIGRATION_REQUIRED', 'Jalankan migrasi pengaturan umum terlebih dahulu.', 503);
        }
        DB::transaction(function () use ($actor, $features) {
            app(AdminAccessService::class)->lockOwners();
            $u = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            if ($u->role !== Role::Owner) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Pengaturan umum hanya dapat diubah Admin Utama.');
            }
            app(PermissionService::class)->require($u, 'schools.features');
            $row = DB::table('platform_feature_settings')->where('id', 1)->lockForUpdate()->first();
            if (! $row) {
                throw new AdminException('CONFIG_INVALID', 'Pengaturan umum belum tersedia.', 503);
            }
            $this->forget();
            $before = $this->defaults();
            $next = array_replace($before, array_map(fn ($v) => (bool) $v, $features));
            app(ReportPublicationPolicy::class)->validateSettings($next);
            // Apply the same complete profile to existing schools, then store defaults for future schools.
            app(SchoolProvisioningService::class)->all($u, $next);
            DB::table('platform_feature_settings')->where('id', 1)->update(['features' => json_encode($next, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
            $changes = [];
            foreach ($next as $key => $value) {
                if ($before[$key] !== $value) {
                    $changes['features.'.$key] = ['before' => $before[$key], 'after' => $value];
                }
            }
            app(AdminAuditService::class)->write($u, 'platform.features_updated', 'platform_features', 1, ['applied_to_all_schools' => true], null, $changes);
        });
        $this->forget();
    }
}
