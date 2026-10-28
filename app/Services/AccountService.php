<?php

namespace App\Services;

use App\Exceptions\AdminException;
use App\Mail\AccountVerificationMail;
use App\Models\User;
use App\Policies\AccountPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AccountService
{
    public function audit(User $user, string $action, ?int $target = null): void
    {
        app(AuditLogger::class)->record($user, $action, 'user', $target ?? $user->id, [], $user->school_id);
    }

    public function profile(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) { // serialize same-school nickname choices without an extra table
            if ($user->school_id) {
                DB::table('schools')->where('id', $user->school_id)->lockForUpdate()->first();
            }
            if (User::where('school_id', $user->school_id)->where('active', true)->whereKeyNot($user->id)->whereRaw('LOWER(display_name) = ?', [mb_strtolower($data['display_name'])])->exists()) {
                throw ValidationException::withMessages(['display_name' => 'Nama panggilan sudah digunakan di sekolahmu.']);
            }
            $user->update(['display_name' => $data['display_name']]);
        });
    }

    public function verification(User $user, ?string $email = null): void
    {
        if ($user->role->admin() && in_array(config('mail.default'), ['log', 'array'], true)) {
            throw ValidationException::withMessages(['email' => 'Konfigurasikan SMTP untuk verifikasi email kontak admin; tautan tidak boleh ditulis ke log.']);
        }
        $log = app(MailLogRecorder::class)->start($user, $email ?? $user->pending_email ?? $user->routeNotificationForMail(), 'account.verification', 'Verifikasi email RuteAman');
        try {
            DB::transaction(function () use ($user, $email) {
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $token = Str::random(64);
            $u->forceFill(['pending_email' => $email ?? $u->pending_email, 'email_verification_token' => hash('sha256', $token), 'email_verification_expires_at' => now()->addHour()])->save();
            $url = URL::temporarySignedRoute('account.email.verify', now()->addHour(), ['user' => $u->id, 'token' => $token]);
            try {
                Mail::to($u->pending_email ?? $u->routeNotificationForMail())->send(new AccountVerificationMail($url));
            } catch (\Throwable) {
                throw ValidationException::withMessages(['email' => 'Email verifikasi gagal dikirim. Periksa konfigurasi pengiriman.']);
            }
            });
            app(MailLogRecorder::class)->markSent($log);
        } catch (\Throwable $e) {
            app(MailLogRecorder::class)->markFailed($log, $e);
            throw $e;
        }
        $user->refresh();
    }

    public function verify(User $actor, User $target, string $token): void
    {
        abort_unless($actor->id === $target->id, 403);
        DB::transaction(function () use ($actor, $token) {
            $u = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            abort_unless($u->email_verification_token && $u->email_verification_expires_at?->isFuture() && hash_equals($u->email_verification_token, hash('sha256', $token)), 403, 'Tautan tidak berlaku. Minta tautan baru.');
            if ($u->pending_email && User::where('email', $u->pending_email)->whereKeyNot($u->id)->exists()) {
                throw ValidationException::withMessages(['email' => 'Email sudah digunakan. Ajukan email lain.']);
            }
            $changed = (bool) $u->pending_email;
            $u->forceFill([($u->role->admin() ? 'contact_email' : 'email') => $u->pending_email ?? $u->routeNotificationForMail(), 'email_verified_at' => now(), 'pending_email' => null, 'email_verification_token' => null, 'email_verification_expires_at' => null])->save();
            if ($changed) {
                $this->audit($u, 'user.email_changed');
            }
        });
    }

    public function password(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $this->audit($user, 'user.password_changed');
        });
        Auth::logoutOtherDevices($password);
    }

    public function sessions(User $user): array
    {
        if (config('session.driver') !== 'database' || ! Schema::hasTable(config('session.table', 'sessions'))) {
            return [];
        }

        return DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->orderByDesc('last_activity')->get(['id', 'user_agent', 'last_activity'])->map(fn ($s) => ['current' => $s->id === session()->getId(), 'device' => str_contains($s->user_agent ?? '', 'Android') ? 'Android' : (str_contains($s->user_agent ?? '', 'iPhone') ? 'iPhone' : 'Browser / komputer'), 'last_active' => Carbon::createFromTimestamp($s->last_activity)])->all();
    }

    public function logoutSessions(User $user, string $password): void
    {
        Auth::logoutOtherDevices($password);
        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', session()->getId())->delete();
        }
    }

    public function export(User $user): array
    {
        $this->audit($user, 'data.exported');

        return ['exported_at' => now()->toIso8601String(), 'profile' => ['display_name' => $user->display_name, 'email' => $user->email, 'school' => $user->school?->name, 'role' => $user->role->value, 'created_at' => $user->created_at, 'parental_consent_at' => $user->parental_consent_at, 'appearance' => $user->appearance, 'locale' => $user->locale, 'avatar_preset' => $user->avatar_preset],
            'reports' => $user->reports()->with('hazard.type')->get()->map(fn ($r) => ['id' => $r->id, 'description' => $r->description, 'observed_at' => $r->observed_at, 'hazard' => ['slug' => $r->hazard?->public_slug, 'type' => $r->hazard?->type?->label, 'status' => $r->hazard?->status->value]]),
            'confirmations' => $user->confirmations()->get(['hazard_id', 'vote', 'created_at'])];
    }

    public function delete(User $user, AvatarService $avatars): void
    {
        if ($user->role->admin()) {
            DB::transaction(function () use ($user) {
                app(AdminAccessService::class)->lockOwners();
                app(AdminAccessService::class)->lastOwner(User::whereKey($user->id)->lockForUpdate()->firstOrFail());
            });
            throw new AdminException('FORBIDDEN_SCOPE', 'Admin tidak dapat mengubah status atau menghapus akun sendiri. Hubungi atasan.');
        }
        $path = $user->avatar_path;
        DB::transaction(function () use ($user) {
            $u = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            // Retain unique anonymous contributor IDs, preserving independent evidence/FKs.
            $u->forceFill(['display_name' => 'Pengguna dihapus', 'email' => Str::ulid().'@deleted.invalid', 'password' => Str::random(80), 'remember_token' => null, 'active' => false, 'role' => 'student', 'status' => 'suspended', 'school_id' => null, 'parental_consent_at' => null, 'email_verified_at' => null, 'avatar_path' => null, 'avatar_preset' => null, 'pending_email' => null, 'email_verification_token' => null, 'email_verification_expires_at' => null, 'notif_seen_at' => null, 'locale' => 'id', 'appearance' => 'system'])->save();
            $this->audit($u, 'account.deleted');
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
                DB::table(config('session.table', 'sessions'))->where('user_id', $u->id)->delete();
            }
        });
        $avatars->removeFiles($path);
    }

    public function manage(User $actor, User $target, array $data): void
    {
        DB::transaction(function () use ($actor, $target, $data) {
            $u = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            abort_unless((new AccountPolicy)->manage($actor, $u), 403);
            $roleChanged = $u->role->value !== $data['role'];
            $activeChanged = $u->active !== (bool) $data['active'];
            if ($u->role->admin()) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Gunakan konsol admin untuk mengelola admin.');
            }$p = app(PermissionService::class);
            $p->require($actor, ($u->role->value === 'verifier' || $data['role'] === 'verifier') ? 'users.manage_verifiers' : 'users.manage_students', $u->school, true);
            $u->forceFill(['role' => $data['role'], 'permissions' => $data['role'] === 'verifier' ? config('ruteaman_roles.ceilings.verifier') : [], 'active' => (bool) $data['active'], 'status' => $data['active'] ? 'active' : 'suspended'])->save();
            if (! $data['active']) {
                app(AdminAccessService::class)->revoke($u);
            }if ($roleChanged) {
                $this->audit($actor, 'user.role_changed', $u->id);
            }if ($activeChanged) {
                $this->audit($actor, 'user.status_changed', $u->id);
            }
        });
    }
}
