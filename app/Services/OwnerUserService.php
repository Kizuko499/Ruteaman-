<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\AdminException;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class OwnerUserService
{
    public function __construct(private AdminAccessService $access, private AuditLogger $audit) {}

    public function owner(User $u): void
    {
        if ($u->fresh()->role !== Role::Owner || ! app(PermissionService::class)->can($u, 'users.manage_admins')) {
            throw new AdminException('FORBIDDEN_SCOPE', 'Semua Pengguna hanya untuk Admin Utama.');
        }
    }

    public function profile(User $a, User $target, array $d): void
    {
        $this->owner($a);
        DB::transaction(function () use ($a, $target, $d) {
            $this->access->lockOwners();
            $u = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $school = $u->role === Role::Owner ? null : (isset($d['school_id']) ? (int) $d['school_id'] : null);
            $this->access->scope($a, $u->role, $school, $u->school_scope ?? []);
            $changes = [];
            foreach (['display_name', 'email', 'contact_email', 'school_id'] as $k) {
                $v = $k === 'school_id' ? $school : ($d[$k] ?? null);
                if ($v !== $u->$k) {
                    $changes[$k] = ['before' => $u->$k, 'after' => $v];
                }
            }
            if ($u->role->admin() && $d['email'] !== $u->email) {
                $d['email'] = app(AdminAccountService::class)->alias($d['email']);
            }
            $u->forceFill(['display_name' => $d['display_name'], 'email' => $d['email'], 'contact_email' => $d['contact_email'] ?? null, 'school_id' => $school])->save();
            if ($changes) {
                $this->access->revoke($u);
            }$this->audit->record($a, 'user.profile_changed', 'user', $u->id, [], $u->school_id, $changes, $d['reason'] ?? null);
        });
    }

    public function password(User $a, User $target, array $d): void
    {
        $this->owner($a);
        DB::transaction(function () use ($a, $target, $d) {
            $this->access->lockOwners();
            $u = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->access->manageable($a, $u);
            app(AdminPasswordPolicy::class)->validate($d['initial_password'], $u->display_name, $u->email);
            $old = $u->must_change_password;
            $u->forceFill(['password' => $d['initial_password'], 'must_change_password' => (bool) ($d['must_change_password'] ?? true)])->save();
            $this->access->revoke($u);
            Password::broker('invites')->deleteToken($u);
            DB::table('password_reset_tokens')->where('email', $u->email)->delete();
            $this->audit->record($a, 'user.password_set', 'user', $u->id, ['must_change_password' => $u->must_change_password], $u->school_id, ['must_change_password' => ['before' => $old, 'after' => $u->must_change_password]], $d['reason'] ?? null);
        });
    }

    public function related(User $u): bool
    {
        return $u->reports()->exists() || $u->confirmations()->exists() || DB::table('verifications')->where('verifier_id', $u->id)->exists() || AuditLog::where('actor_id', $u->id)->exists() || User::where('created_by', $u->id)->exists() || School::where('created_by', $u->id)->exists() || DB::table('home_gallery_photos')->where('uploaded_by', $u->id)->exists();
    }

    public function delete(User $a, User $target, array $d): void
    {
        $this->performDelete($a, $target, $d, true);
    }

    public function quickDelete(Request $request, User $target, string $token): void
    {
        $this->owner($request->user());
        DB::transaction(function () use ($request, $target, $token) {
            $this->access->lockOwners();
            $locked = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->owner($request->user());
            app(QuickUserDeletion::class)->consume($request, $locked, $token);
            // Quick confirmation always uses anonymization, never permanent deletion.
            $this->performDelete($request->user(), $locked, [], false);
        });
    }

    private function performDelete(User $a, User $target, array $d, bool $requireTypedCredentials): void
    {
        $this->owner($a);
        $avatar = null;
        DB::transaction(function () use ($a, $target, $d, $requireTypedCredentials, &$avatar) {
            $this->access->lockOwners();
            $actor = User::whereKey($a->id)->lockForUpdate()->firstOrFail();
            $u = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->access->lastOwner($u);
            $this->access->manageable($actor, $u);
            if ($requireTypedCredentials && ! Hash::check($d['delete_password'], $actor->password)) {
                throw ValidationException::withMessages(['delete_password' => 'Kata sandi Admin Utama tidak sesuai.']);
            }if ($requireTypedCredentials && ! in_array($d['confirmation'], [$u->display_name, $u->email], true)) {
                throw ValidationException::withMessages(['confirmation' => 'Ketik nama atau alias akun yang tepat.']);
            }
            if (! empty($d['permanent'])) {
                if ($u->status !== 'pending' || $this->related($u)) {
                    throw new AdminException('VALIDATION_FAILED', 'Hapus permanen hanya untuk akun menunggu tanpa catatan terkait.', 422);
                }$this->audit->record($actor, 'user.permanently_deleted', 'user', $u->id, [], $u->school_id, [], $d['reason'] ?? null);
                $old = $u->email;
                $u->delete();
                DB::table('password_reset_tokens')->where('email', $old)->delete();

                return;
            }
            $avatar = $u->avatar_path;
            $oldName = $u->display_name;
            $school = $u->school_id;
            $this->access->lifecycle($actor, $u, 'delete');
            $u->refresh();
            $u->forceFill(['parental_consent_at' => null, 'email_verified_at' => null, 'notif_seen_at' => null])->save();
            $this->audit->record($actor, 'user.anonymized', 'user', $u->id, [], $school, ['display_name' => ['before' => $oldName, 'after' => 'Pengguna dihapus']], $d['reason'] ?? null);
        });
        if ($avatar) {
            DB::afterCommit(fn () => app(AvatarService::class)->removeFiles($avatar));
        }
    }
}
