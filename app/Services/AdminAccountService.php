<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\AdminException;
use App\Mail\AdminInvitationMail;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AdminAccountService
{
    public function __construct(private PermissionService $permissions, private AdminAccessService $access, private AdminAuditService $audit) {}

    public function alias(string $value): string
    {
        $value = strtolower(trim($value));
        $parts = explode('@', $value);
        if (count($parts) !== 2 || $parts[1] !== strtolower(config('ruteaman_admin.alias_domain')) || ! preg_match('/^[a-z0-9]+(?:[.\-][a-z0-9]+)*$/', $parts[0]) || strlen($value) > 254) {
            throw ValidationException::withMessages(['email' => 'Gunakan alias huruf kecil pada domain '.config('ruteaman_admin.alias_domain').'.']);
        }
        foreach (explode('.', str_replace('-', '.', $parts[0])) as $word) {
            if (in_array($word, config('ruteaman_admin.reserved'), true)) {
                throw ValidationException::withMessages(['email' => 'Alias memakai kata yang dicadangkan. Gunakan misalnya slug.pengelola.']);
            }
        }
        if (User::where('email', $value)->exists()) {
            throw ValidationException::withMessages(['email' => 'Alias sudah digunakan.']);
        }

        return $value;
    }

    public function create(User $actor, array $data, bool $provision = false): User
    {
        return DB::transaction(function () use ($actor, $data, $provision) {
            $this->access->lockOwners();
            $a = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $role = Role::from($data['role']);
            if (! in_array($role->value, [...config('ruteaman_admin.admin_tiers'), 'student', 'verifier'], true)) {
                throw new AdminException('VALIDATION_FAILED', 'Tingkat ini tidak tersedia untuk akun baru.', 422);
            }if (in_array(mb_strtolower(trim($data['display_name'])), config('ruteaman_admin.reserved'), true)) {
                throw ValidationException::withMessages(['display_name' => 'Nama tampilan dicadangkan.']);
            }
            $school = isset($data['school_id']) ? (int) $data['school_id'] : null;
            $this->permissions->require($a, $provision ? 'schools.provision' : 'users.manage_admins', $school ? School::findOrFail($school) : null, true);
            if ($provision && ! in_array($role, [Role::SchoolAdmin, Role::SchoolStaff], true)) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Provisioning hanya membuat akun sekolah.');
            }
            if ((! $role->admin() && $a->role !== Role::Owner) || ($a->role->tier() >= $role->tier() && ! ($a->role === Role::Owner && $role === Role::Owner))) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Hanya tingkat lebih rendah dapat dibuat.');
            }
            $this->access->scope($a, $role, $school, $data['school_scope'] ?? []);
            $direct = ($data['creation_method'] ?? 'invite') === 'password_set';
            if ($direct) {
                app(AdminPasswordPolicy::class)->validate($data['initial_password'] ?? '', $data['display_name'], $data['email']);
            }
            if ($role === Role::Owner && User::where('role', 'owner')->whereIn('status', ['active', 'pending'])->lockForUpdate()->get()->count() >= config('ruteaman_admin.owner_max')) {
                throw new AdminException('VALIDATION_FAILED', 'Batas Owner aktif dan undangan menunggu telah tercapai.', 422);
            }
            $list = $this->permissions->grant($a, $role, $data['permissions'] ?? []);
            $u = (new User)->forceFill(['display_name' => $data['display_name'], 'email' => $role->admin() ? $this->alias($data['email']) : mb_strtolower(trim($data['email'])), 'contact_email' => $data['contact_email'] ?? null, 'password' => $direct ? $data['initial_password'] : Str::random(80), 'role' => $role, 'school_id' => in_array($role, [Role::SchoolAdmin, Role::SchoolStaff, Role::Student, Role::Verifier], true) ? $school : null, 'school_scope' => $role === Role::PlatformAdmin ? ($data['school_scope'] ?? []) : null, 'permissions' => $list, 'status' => $direct ? 'active' : 'pending', 'active' => $direct, 'must_change_password' => $direct ? (bool) ($data['must_change_password'] ?? true) : true, 'created_by' => $a->id]);
            $u->save();
            $this->audit->write($a, 'admin.created', 'user', $u->id, ['role' => $role->value, 'method' => $direct ? 'password_set' : 'invite', 'must_change_password' => $u->must_change_password], $u->school_id, ['role' => ['before' => null, 'after' => $role->value], 'status' => ['before' => null, 'after' => $u->status]], $data['reason'] ?? null);
            if ($role === Role::Owner) {
                $this->audit->write($a, 'owner.created', 'user', $u->id);
            }
            if (in_array($role, [Role::Owner, Role::PlatformAdmin], true)) {
                DB::afterCommit(fn () => $this->audit->notifyOwners($role->label().' baru #'.$u->id.' ditambahkan oleh akun #'.$a->id.'. 2FA belum tersedia pada instalasi ini.'));
            }
            if ($direct && $u->contact_email && ! in_array(config('mail.default'), ['log', 'array'], true)) {
                DB::afterCommit(function () use ($u) {
                    try {
                        Mail::raw('Akun RuteAman dibuat dengan alias '.$u->email.'. Kata sandi awal disampaikan Admin Utama melalui kanal privat, bukan melalui email. Ganti kata sandi saat login pertama jika diwajibkan.', fn ($m) => $m->to($u->contact_email)->subject('Akun RuteAman dibuat'));
                        app(MailLogRecorder::class)->recordOutcome($u, $u->contact_email, 'account.created_notice', 'Akun RuteAman dibuat', true);
                    } catch (\Throwable $e) {/* Do not log mail transport payloads. */
                        app(MailLogRecorder::class)->recordOutcome($u, $u->contact_email, 'account.created_notice', 'Akun RuteAman dibuat', false, $e);
                    }
                });
            }

            return $u;
        });
    }

    public function issue(User $actor, User $target, bool $reset = false, bool $new = false): array
    {
        $result = DB::transaction(function () use ($actor, $target, $reset, $new) {
            $this->access->lockOwners();
            $a = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $u = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            if ($new) {
                $this->permissions->require($a, $this->permissions->can($a, 'users.manage_admins', $u->school) ? 'users.manage_admins' : 'schools.provision', $u->school);
                if ((int) $u->created_by !== $a->id) {
                    throw new AdminException('FORBIDDEN_SCOPE', 'Undangan bukan dibuat oleh akun ini.');
                }
            } else {
                $this->access->manageable($a, $u);
            }
            if ((! $reset && $u->status !== 'pending') || ($reset && $u->status !== 'active')) {
                throw new AdminException('VALIDATION_FAILED', 'Status akun tidak sesuai untuk tautan ini.', 422);
            }
            $token = Password::broker('invites')->createToken($u);
            $url = route('admin.invite.show', ['token' => $token, 'email' => $u->email]);
            $this->audit->write($a, $reset ? 'admin.reset_link_issued' : 'admin.invite_resent', 'user', $u->id, [], $u->school_id);

            return ['url' => $url, 'recipient' => $u->routeNotificationForMail()];
        });
        // Do not use a persisted queue job: it would serialize a raw one-time URL.
        $sent = false;
        if (! in_array(config('mail.default'), ['log', 'array'], true)) {
            try {
                Mail::to($result['recipient'])->send(new AdminInvitationMail($result['url']));
                $sent = true;
            } catch (\Throwable) {/* deliberately do not report SMTP exceptions carrying a URL */
            }
        }
        if (! in_array(config('mail.default'), ['log', 'array'], true)) {
            app(MailLogRecorder::class)->recordOutcome($target, $result['recipient'], 'account.invitation', 'Setel kata sandi admin RuteAman', $sent);
        }
        unset($result['recipient']);

        return [...$result, 'mail_sent' => $sent];
    }

    public function accept(string $email, string $token, string $password): User
    {
        app(AdminPasswordPolicy::class)->validate($password, '', $email);

        return DB::transaction(function () use ($email, $token, $password) {
            $this->access->lockOwners();
            $u = User::where('email', $email)->lockForUpdate()->first();
            if (! $u || ! in_array($u->status, ['pending', 'active'], true) || ! Password::broker('invites')->tokenExists($u, $token)) {
                throw new AdminException('INVITE_INVALID', 'Tautan kedaluwarsa atau sudah digunakan. Minta pembuat atau atasan menerbitkan tautan baru.', 422);
            }
            if ($u->role === Role::Owner && $u->status === 'pending' && User::where('role', 'owner')->where('status', 'active')->where('active', true)->lockForUpdate()->get()->count() >= config('ruteaman_admin.owner_max')) {
                throw new AdminException('VALIDATION_FAILED', 'Batas Owner aktif telah tercapai.', 422);
            }
            Password::broker('invites')->deleteToken($u);
            $u->forceFill(['password' => $password, 'status' => 'active', 'active' => true, 'must_change_password' => false, 'email_verified_at' => now()])->save();
            $this->access->revoke($u);
            $this->audit->write($u, 'admin.password_set', 'user', $u->id, [], $u->school_id);

            return $u;
        });
    }
}
