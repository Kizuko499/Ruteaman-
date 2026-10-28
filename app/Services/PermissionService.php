<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\AdminException;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class PermissionService
{
    public function selectedSchool(Request $r, string $key): School
    {
        $d = $r->validate(['school' => 'nullable|integer|exists:schools,id']);
        $q = $this->scope(School::query(), $r->user());
        $id = $d['school'] ?? $r->user()->school_id;
        if ($id) {
            $q->whereKey($id);
        }$s = $q->orderBy('id')->first();
        if (! $s) {
            throw new AdminException('FORBIDDEN_SCOPE', 'Tidak ada sekolah dalam cakupan yang dipilih.');
        }$this->require($r->user(), $key, $s, ! $r->isMethod('GET'));

        return $s;
    }

    public function fresh(User $u): User
    {
        return $u->exists ? User::findOrFail($u->id) : $u;
    }

    public function allScope(User $u): bool
    {
        return $u->role === Role::Owner || ($u->role === Role::PlatformAdmin && in_array('*', $u->school_scope ?? [], true));
    }

    public function inScope(User $u, School|int $school): bool
    {
        $id = $school instanceof School ? $school->id : $school;

        return $this->allScope($u) || ($u->role === Role::PlatformAdmin ? in_array((int) $id, array_map('intval', $u->school_scope ?? []), true) : (int) $u->school_id === (int) $id);
    }

    public function scope(Builder $q, User $u, string $column = 'id'): Builder
    {
        if ($this->allScope($u)) {
            return $q;
        }

return $q->whereIn($column, $u->role === Role::PlatformAdmin ? array_map('intval', $u->school_scope ?? []) : [$u->school_id ?? 0]);
    }

    public function granted(User $u): array
    {
        if ($u->role === Role::Owner) {
            return array_keys(config('ruteaman_permissions'));
        }$list = $u->permissions ?? ($u->role === Role::Verifier ? config('ruteaman_roles.ceilings.verifier') : []);

        return array_values(array_intersect($list, config('ruteaman_roles.ceilings.'.$u->role->value, [])));
    }

    public function feature(School $s, string $key, bool $write = false): bool
    {
        $s = $s->fresh() ?? $s;
        if (! array_key_exists($key, config('ruteaman_admin.features'))) {
            return false;
        }

return (! $write || $s->status === 'active') && ($s->features[$key] ?? app(GlobalFeatureSettings::class)->enabled($key));
    }

    public function can(User $u, string $key, ?School $s = null, bool $write = false): bool
    {
        $u = $this->fresh($u);
        // Owner selalu punya akses penuh (bypass semua cek)
        if ($u->role === Role::Owner && $u->active && $u->status === 'active') {
            return true;
        }
        if (! $u->active || $u->status !== 'active' || $u->must_change_password) {
            return false;
        }
        if (! isset(config('ruteaman_permissions')[$key])) {
            return false;
        }
        if ($s && ! $this->inScope($u, $s)) {
            return false;
        }
        if (! in_array($key, $this->granted($u), true)) {
            return false;
        }
        foreach ((config('ruteaman_permissions')[$key]['dependencies'] ?? []) as $d) {
            if (! in_array($d, $this->granted($u), true)) {
                return false;
            }
        }
        if ($s) {
            $s = $s->fresh();
            if ($write && $s->status !== 'active' && in_array($u->role, [Role::SchoolAdmin, Role::SchoolStaff, Role::Verifier], true)) {
                return false;
            }foreach ((config('ruteaman_permissions')[$key]['features'] ?? []) as $f) {
                if (! $this->feature($s, $f)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function require(User $u, string $key, ?School $s = null, bool $write = false): void
    {
        $u = $this->fresh($u);
        // Owner selalu lolos
        if ($u->role === Role::Owner && $u->active && $u->status === 'active') {
            return;
        }
        if ($s && ! $this->inScope($u, $s)) {
            $this->deny($u, 'FORBIDDEN_SCOPE', 'Sekolah berada di luar cakupan akun.', $s);
        }
        if (! in_array($key, $this->granted($u), true)) {
            $this->deny($u, 'FORBIDDEN_SCOPE', 'Izin tindakan ini tidak tersedia.', $s);
        }
        if ($s) {
            foreach ((config('ruteaman_permissions')[$key]['features'] ?? []) as $f) {
                $this->requireFeature($s, $f);
            }if ($write && $s->status !== 'active' && in_array($u->role, [Role::SchoolAdmin, Role::SchoolStaff, Role::Verifier], true)) {
                throw new AdminException('FEATURE_DISABLED', 'Sekolah dijeda; akun sekolah hanya dapat membaca.');
            }
        }
        if (! $this->can($u, $key, $s, $write)) {
            $this->deny($u, 'FORBIDDEN_SCOPE', 'Tindakan tidak diizinkan.', $s);
        }
    }

    public function requireFeature(School $s, string $key, bool $write = false): void
    {
        if (! $this->feature($s, $key, $write)) {
            throw new AdminException('FEATURE_DISABLED', 'Fitur ini sedang dinonaktifkan atau pemantauan sekolah dijeda.');
        }
    }

    private function deny(User $u, string $code, string $message, ?School $s): never
    {
        throw new AdminException($code, $message);
    }

    public function normalize(array $requested, ?array $previous = null): array
    {
        $catalog = config('ruteaman_permissions');
        foreach ($requested as $key) {
            if (! isset($catalog[$key])) {
                throw new AdminException('VALIDATION_FAILED', 'Izin tidak dikenal.', 422);
            }
        }
        $set = array_fill_keys(array_unique($requested), true);
        // Explicitly removed parents remove their descendants; newly selected children add ancestors.
        if ($previous !== null) {
            $removed = array_diff($previous, $requested);
            do {
                $changed = false;
                foreach (array_keys($set) as $k) {
                    if (array_intersect($catalog[$k]['dependencies'], $removed)) {
                        unset($set[$k]);
                        $removed[] = $k;
                        $changed = true;
                    }
                }
            } while ($changed);
        }
        do {
            $changed = false;
            foreach (array_keys($set) as $k) {
                foreach ($catalog[$k]['dependencies'] as $d) {
                    if (! isset($set[$d])) {
                        $set[$d] = true;
                        $changed = true;
                    }
                }
            }
        } while ($changed);

        return array_keys($set);
    }

    public function grant(User $actor, Role $role, array $requested, ?array $previous = null): array
    {
        $list = $this->normalize($requested, $previous);
        $ceiling = $role === Role::Owner ? array_keys(config('ruteaman_permissions')) : config('ruteaman_roles.ceilings.'.$role->value,[]);
        if (array_diff($list,$ceiling) || array_diff($list,$this->granted($actor))) {
            throw new AdminException('GRANT_CEILING_EXCEEDED','Izin melampaui tingkat penerima atau hak pemberi.');
        }

return $list;
    }
}
