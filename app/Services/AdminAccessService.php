<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\AdminException;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class AdminAccessService
{
    public function __construct(private PermissionService $permissions, private AdminAuditService $audit) {}

    public function lockOwners(): void
    {
        // Global transaction lock prevents concurrent first-owner creation and last-owner changes.
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Owner lock requires a transaction.');
        }
        if (DB::getDriverName() === 'pgsql') {
            DB::select('SELECT pg_advisory_xact_lock(82417001)');
        } else {
            DB::table('admin_transaction_locks')->where('id', 1)->lockForUpdate()->firstOrFail();
        }
        User::where('role', 'owner')->orderBy('id')->lockForUpdate()->get();
    }

    public function lastOwner(User $u): void
    {
        if ($u->role === Role::Owner && $u->status === 'active' && $u->active && User::where('role', 'owner')->where('status', 'active')->where('active', true)->lockForUpdate()->get()->count() <= 1) {
            throw new AdminException('LAST_OWNER', 'Admin Utama aktif terakhir harus tetap tersedia.');
        }
    }

    public function manageable(User $actor, User $target, ?Role $new = null): void
    {
        $this->permissions->require($actor, 'users.manage_admins', $target->school_id ? School::findOrFail($target->school_id) : null, true);
        if ($actor->id === $target->id) {
            throw new AdminException('FORBIDDEN_SCOPE', 'Tidak boleh mengubah hak, status, atau cakupan akun sendiri.');
        }
        if ($actor->role !== Role::Owner && (! $target->role->admin() || $actor->role->tier() >= $target->role->tier() || ($new && $actor->role->tier() >= $new->tier()))) {
            throw new AdminException('FORBIDDEN_SCOPE', 'Akun di luar tingkat yang dapat dikelola.');
        }
        $this->scope($actor, $target->role, $target->school_id, $target->school_scope ?? []);
    }

    public function scope(User $actor, Role $role, ?int $school, array $scope): void
    {
        if (in_array($role, [Role::SchoolAdmin, Role::SchoolStaff, Role::Student, Role::Verifier], true)) {
            if (! $school || ! School::whereKey($school)->exists() || ! $this->permissions->inScope($actor, $school)) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Sekolah penerima berada di luar cakupan.');
            }
        } elseif ($role === Role::PlatformAdmin) {
            if (! $scope) {
                throw new AdminException('VALIDATION_FAILED', 'Pilih cakupan Admin Platform.', 422);
            }
            if (in_array('*', $scope, true)) {
                if (count($scope) !== 1 || ! $this->permissions->allScope($actor)) {
                    throw new AdminException('FORBIDDEN_SCOPE', 'Cakupan semua sekolah tidak dapat diberikan.');
                }
            } else {
                foreach ($scope as $id) {
                    if (! is_numeric($id) || ! School::whereKey((int) $id)->exists() || ! $this->permissions->inScope($actor, (int) $id)) {
                        throw new AdminException('FORBIDDEN_SCOPE', 'Cakupan penerima melampaui pemberi.');
                    }
                }
            }
        } elseif ($role === Role::Owner && ! $this->permissions->allScope($actor)) {
            throw new AdminException('FORBIDDEN_SCOPE', 'Owner memiliki cakupan platform.');
        }
    }

    public function revoke(User $u): void
    {
        $u->forceFill(['session_version' => $u->session_version + 1, 'remember_token' => Str::random(60)])->save();
        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))->where('user_id', $u->id)->delete();
        }
    }

    public function updatePermissions(User $actor, User $target, array $requested, ?array $scope = null): void
    {
        DB::transaction(function () use ($actor, $target, $requested, $scope) {
            $this->lockOwners();
            $a = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $u = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->manageable($a, $u);
            if ($u->role === Role::Owner) {
                throw new AdminException('VALIDATION_FAILED', 'Admin Utama selalu memiliki seluruh izin.', 422);
            }
            $list = $this->permissions->grant($a, $u->role, $requested, $u->permissions ?? []);
            $changes = ['permissions' => $list];
            if ($scope !== null) {
                $this->scope($a, $u->role, $u->school_id, $scope);
                $changes['school_scope'] = $u->role === Role::PlatformAdmin ? $scope : null;
            }
            $before = $u->permissions ?? [];
            $u->forceFill($changes)->save();
            $this->revoke($u);
            $this->audit->write($a, 'admin.permissions_changed', 'user', $u->id, ['added' => array_values(array_diff($list, $before)), 'removed' => array_values(array_diff($before, $list)), 'school_scope' => $u->school_scope], $u->school_id, ['permissions' => ['before' => $before, 'after' => $list]], request()->input('reason'));
        });
    }

    public function tier(User $actor, User $target, Role $new, ?int $school, array $scope): void
    {
        DB::transaction(function () use ($actor, $target, $new, $school, $scope) {
            $this->lockOwners();
            $a = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $u = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            if (! in_array($new->value, [...config('ruteaman_admin.admin_tiers'), 'student', 'verifier'], true)) {
                throw new AdminException('VALIDATION_FAILED', 'Tingkat baru tidak aktif.', 422);
            }if ($new === Role::Owner && $u->role !== Role::Owner && User::where('role', 'owner')->whereIn('status', ['active', 'pending'])->count() >= config('ruteaman_admin.owner_max')) {
                throw new AdminException('VALIDATION_FAILED', 'Batas Admin Utama tercapai.', 422);
            }if ($u->role === $new) {
                throw new AdminException('VALIDATION_FAILED', 'Pilih tingkat yang berbeda.', 422);
            }$this->lastOwner($u);
            $this->manageable($a, $u, $new);
            $this->scope($a, $new, $school, $scope);
            $ceiling = config('ruteaman_roles.ceilings.'.$new->value, []);
            $list = $this->permissions->grant($a, $new, array_values(array_intersect($u->permissions ?? [], $ceiling)));
            $old = $u->role->value;
            $removed = array_values(array_diff($u->permissions ?? [], $list));
            $u->forceFill(['role' => $new, 'permissions' => $list, 'school_id' => in_array($new, [Role::SchoolAdmin, Role::SchoolStaff, Role::Verifier, Role::Student], true) ? $school : null, 'school_scope' => $new === Role::PlatformAdmin ? $scope : null])->save();
            $this->revoke($u);
            $this->audit->write($a, 'admin.tier_changed', 'user', $u->id, ['old_role' => $old, 'new_role' => $new->value, 'removed' => $removed], $u->school_id, ['role' => ['before' => $old, 'after' => $new->value]], request()->input('reason'));
            if ($old === 'owner' || $new === Role::Owner) {
                DB::afterCommit(fn () => $this->audit->notifyOwners('Tingkat akun #'.$u->id.' diubah oleh Admin Utama #'.$a->id.'.'));
            }
        });
    }

    public function lifecycle(User $actor, User $target, string $action): void
    {
        DB::transaction(function () use ($actor, $target, $action) {
            $this->lockOwners();
            $a = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $u = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            if (in_array($action, ['suspend', 'delete'], true)) {
                $this->lastOwner($u);
            }$this->manageable($a, $u);
            if ($action === 'activate' && $u->status === 'pending') {
                throw new AdminException('VALIDATION_FAILED', 'Akun harus menyetel sandi melalui undangan.', 422);
            }
            if ($action === 'activate' && $u->role === Role::Owner && $u->status !== 'active' && User::where('role', 'owner')->whereIn('status', ['active', 'pending'])->count() >= config('ruteaman_admin.owner_max')) {
                throw new AdminException('VALIDATION_FAILED', 'Batas Admin Utama tercapai.', 422);
            }
            $oldStatus = $u->status;
            if ($action === 'suspend') {
                $u->forceFill(['status' => 'suspended', 'active' => false])->save();
            } elseif ($action === 'activate') {
                $u->forceFill(['status' => 'active', 'active' => true])->save();
            } elseif ($action === 'delete') {
                $old = $u->email;
                $u->forceFill(['display_name' => 'Pengguna dihapus', 'email' => Str::ulid().'@deleted.invalid', 'contact_email' => null, 'password' => Str::random(80), 'status' => 'suspended', 'active' => false, 'role' => Role::Student, 'permissions' => [], 'school_scope' => null, 'school_id' => null, 'pending_email' => null, 'email_verification_token' => null, 'email_verification_expires_at' => null, 'avatar_path' => null, 'avatar_preset' => null])->save();
                DB::table('password_reset_tokens')->where('email', $old)->delete();
            } elseif ($action !== 'revoke-sessions') {
                throw new AdminException('VALIDATION_FAILED', 'Aksi tidak dikenal.', 422);
            }
            $this->revoke($u);
            $label = match ($action) {
                'suspend' => 'suspended','activate' => 'activated','delete' => 'deleted',default => 'sessions_revoked'
            };
            $this->audit->write($a, 'admin.'.$label, 'user', $u->id, [], $target->school_id, ['status' => ['before' => $oldStatus, 'after' => $u->status]], request()->input('reason'));
        });
    }
}
