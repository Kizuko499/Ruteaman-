<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Mail\RecipientVerificationMail;
use App\Mail\TestMail;
use App\Models\NotificationRecipient;
use App\Models\School;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\DigestScheduleCalculator;
use App\Services\MailLogRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mengelola penerima notifikasi email mandiri (di luar akun pengguna).
 * Cakupan: Owner/Admin Platform mengelola semua penerima; Admin Sekolah
 * hanya penerima milik sekolahnya dan tidak dapat membuat penerima platform.
 * Semua perubahan tercatat di log audit.
 */
class AdminNotificationRecipientController extends Controller
{
    public function __construct(
        private DigestScheduleCalculator $calculator,
        private MailLogRecorder $recorder,
        private AdminAuditService $audit,
    ) {}

    public function index(Request $request)
    {
        $recipients = $this->visibleQuery($request->user())
            ->withCount('categoryRows')
            ->withCount(['deliveryLogs as sent_this_month' => fn ($q) => $q->where('status', 'sent')->where('created_at', '>=', now()->startOfMonth())])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.email.recipients.index', [
            'recipients' => $recipients,
            'activeCount' => $this->visibleQuery($request->user())->deliverable()->count(),
            'scheduleCount' => $this->visibleQuery($request->user())->deliverable()->whereIn('mode', ['digest', 'both'])->count(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.email.recipients.form', [
            'recipient' => null,
            'platformActor' => $this->isPlatformActor($request->user()),
            'categoryGroups' => NotificationRecipient::categoryGroups($this->isPlatformActor($request->user())),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $schoolId = $this->resolveSchoolId($request, $data);
        $recipient = new NotificationRecipient;
        $recipient->forceFill($this->recipientAttributes($data, $schoolId));
        $recipient->forceFill(['created_by' => $request->user()->id]);
        $recipient->save();
        $recipient->syncCategories(NotificationRecipient::filterCategoriesForScope($data['categories'] ?? [], $schoolId === null));
        $recipient->forceFill(['next_run_at' => $this->calculator->initialNextRun($recipient)])->save();
        $this->audit->write($request->user(), 'notification_recipient.created', 'notification_recipient', $recipient->id);
        $sent = $this->sendVerification($recipient);

        return redirect()->route('admin.email.recipients.index')
            ->with('status', $sent
                ? 'Penerima ditambahkan. Email verifikasi sudah dikirim; penerima aktif menerima notifikasi setelah alamatnya terverifikasi.'
                : 'Penerima ditambahkan, tetapi email verifikasi gagal dikirim. Periksa halaman Riwayat lalu kirim ulang verifikasi.');
    }

    public function edit(Request $request, NotificationRecipient $recipient)
    {
        $this->findVisible($request->user(), $recipient->id);

        return view('admin.email.recipients.form', [
            'recipient' => $recipient,
            'platformActor' => $this->isPlatformActor($request->user()),
            'categoryGroups' => NotificationRecipient::categoryGroups($this->isPlatformActor($request->user())),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function update(Request $request, NotificationRecipient $recipient)
    {
        $recipient = $this->findVisible($request->user(), $recipient->id);
        $data = $this->validateData($request, $recipient->id);
        $schoolId = $this->resolveSchoolId($request, $data, $recipient);
        $emailChanged = mb_strtolower(trim($data['email'])) !== $recipient->email;
        $scheduleChanged = $this->scheduleChanged($recipient, $data);
        $recipient->forceFill($this->recipientAttributes($data, $schoolId));
        if ($emailChanged) {
            $recipient->forceFill(['verified_at' => null, 'verification_token_hash' => null]);
        }
        $recipient->save();
        $recipient->syncCategories(NotificationRecipient::filterCategoriesForScope($data['categories'] ?? [], $schoolId === null));
        if ($recipient->mode === 'realtime') {
            $recipient->forceFill(['next_run_at' => null])->save();
        } elseif ($scheduleChanged || $recipient->next_run_at === null) {
            $recipient->forceFill(['next_run_at' => $this->calculator->initialNextRun($recipient->fresh())])->save();
        }
        $this->audit->write($request->user(), 'notification_recipient.updated', 'notification_recipient', $recipient->id);
        if ($emailChanged) {
            $this->sendVerification($recipient);

            return redirect()->route('admin.email.recipients.index')
                ->with('status', 'Penerima diperbarui. Alamat email berubah sehingga verifikasi dikirim ulang ke alamat baru.');
        }

        return redirect()->route('admin.email.recipients.index')->with('status', 'Penerima diperbarui.');
    }

    public function destroy(Request $request, NotificationRecipient $recipient)
    {
        $recipient = $this->findVisible($request->user(), $recipient->id);
        $id = $recipient->id;
        $recipient->delete();
        $this->audit->write($request->user(), 'notification_recipient.deleted', 'notification_recipient', $id);

        return redirect()->route('admin.email.recipients.index')->with('status', 'Penerima dihapus. Pengiriman yang sudah mengantre untuk penerima ini dibatalkan otomatis.');
    }

    public function toggle(Request $request, NotificationRecipient $recipient)
    {
        $recipient = $this->findVisible($request->user(), $recipient->id);
        $recipient->forceFill(['is_active' => ! $recipient->is_active])->save();
        if ($recipient->is_active && $recipient->mode !== 'realtime' && $recipient->next_run_at === null) {
            $recipient->forceFill(['next_run_at' => $this->calculator->initialNextRun($recipient)])->save();
        }
        $this->audit->write($request->user(), $recipient->is_active ? 'notification_recipient.activated' : 'notification_recipient.deactivated', 'notification_recipient', $recipient->id);

        return back()->with('status', $recipient->is_active ? 'Penerima diaktifkan.' : 'Penerima dinonaktifkan; pengiriman berikutnya tidak lagi diarahkan kepadanya.');
    }

    public function resendVerification(Request $request, NotificationRecipient $recipient)
    {
        $recipient = $this->findVisible($request->user(), $recipient->id);
        if ($recipient->verified_at) {
            return back()->with('status', 'Alamat ini sudah terverifikasi.');
        }
        $sent = $this->sendVerification($recipient);

        return back()->with('status', $sent ? 'Email verifikasi dikirim ulang.' : 'Email verifikasi gagal dikirim; periksa konfigurasi SMTP di tab Konfigurasi.');
    }

    public function test(Request $request, NotificationRecipient $recipient)
    {
        $recipient = $this->findVisible($request->user(), $recipient->id);
        $actor = $request->user();
        try {
            Mail::to($recipient->email)->send(new TestMail($actor->display_name));
            $this->recorder->recordOutcome($actor, $recipient->email, 'admin.test', 'Email uji RuteAman', true, null, $recipient->id);

            return back()->with('status', 'Email uji terkirim ke '.$recipient->email.'. Periksa kotak masuk dan folder spam penerima.');
        } catch (\Throwable $e) {
            $this->recorder->recordOutcome($actor, $recipient->email, 'admin.test', 'Email uji RuteAman', false, $e, $recipient->id);

            return back()->with('status', 'Email uji gagal dikirim: '.\App\Services\MailLogRecorder::sanitizeError($e));
        }
    }

    // ----- Bantuan cakupan & validasi -----

    private function isPlatformActor(User $user): bool
    {
        return $user->role === Role::Owner || $user->role === Role::PlatformAdmin;
    }

    private function visibleQuery(User $user)
    {
        $query = NotificationRecipient::query()->with('school');
        if (! $this->isPlatformActor($user)) {
            // Aktor non-platform tanpa sekolah tidak boleh melihat penerima platform.
            $query->where('school_id', $user->school_id ?? -1);
        }

        return $query;
    }

    private function findVisible(User $user, int $id): NotificationRecipient
    {
        return $this->visibleQuery($user)->findOrFail($id);
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('notification_recipients', 'email')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:190'],
            'school_query' => ['nullable', 'string', 'max:190'],
            'scope' => ['required', Rule::in(['platform', 'school'])],
            'mode' => ['required', Rule::in(array_keys(NotificationRecipient::MODES))],
            'frequency_type' => ['required', Rule::in(array_keys(NotificationRecipient::FREQUENCIES))],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'weekly_day' => ['nullable', 'integer', 'min:1', 'max:7'],
            'monthly_mode' => ['nullable', Rule::in(['date', 'first', 'last'])],
            'monthly_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'send_time' => ['required', 'date_format:H:i'],
            'timezone' => ['required', Rule::in(\DateTimeZone::listIdentifiers())],
            'quiet_start' => ['nullable', 'date_format:H:i'],
            'quiet_end' => ['nullable', 'date_format:H:i'],
            'send_empty_digest' => ['nullable', 'boolean'],
            'receive_all_reports' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:48'],
        ]);
    }

    /** Tentukan school_id dari pilihan cakupan + isian sekolah (NPSN atau nama persis). */
    private function resolveSchoolId(Request $request, array $data, ?NotificationRecipient $existing = null): ?int
    {
        $user = $request->user();
        if (! $this->isPlatformActor($user)) {
            return $user->school_id;
        }
        if (($data['scope'] ?? 'platform') === 'platform') {
            return null;
        }
        $query = trim((string) ($data['school_query'] ?? ''));
        if ($query === '' && $existing) {
            return $existing->school_id;
        }
        $school = School::query()
            ->where('npsn', $query)
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($query)])
            ->first();
        if (! $school) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'school_query' => 'Sekolah tidak ditemukan. Isi NPSN atau nama sekolah persis seperti di data institusi.',
            ]);
        }

        return $school->id;
    }

    private function recipientAttributes(array $data, ?int $schoolId): array
    {
        return [
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'description' => $data['description'] ?? null,
            'school_id' => $schoolId,
            'mode' => $data['mode'],
            'frequency_type' => $data['frequency_type'],
            'interval_days' => $data['frequency_type'] === 'interval' ? (int) ($data['interval_days'] ?? 1) : null,
            'weekly_day' => $data['frequency_type'] === 'weekly' ? (int) ($data['weekly_day'] ?? 1) : null,
            'monthly_mode' => $data['frequency_type'] === 'monthly' ? ($data['monthly_mode'] ?? 'date') : null,
            'monthly_day' => $data['frequency_type'] === 'monthly' && ($data['monthly_mode'] ?? 'date') === 'date' ? (int) ($data['monthly_day'] ?? 1) : null,
            'send_time' => $data['send_time'].':00',
            'timezone' => $data['timezone'],
            'quiet_start' => ! empty($data['quiet_start']) ? $data['quiet_start'].':00' : null,
            'quiet_end' => ! empty($data['quiet_end']) ? $data['quiet_end'].':00' : null,
            'send_empty_digest' => (bool) ($data['send_empty_digest'] ?? false),
            'receive_all_reports' => (bool) ($data['receive_all_reports'] ?? false),
        ];
    }

    private function scheduleChanged(NotificationRecipient $recipient, array $data): bool
    {
        $next = $this->recipientAttributes($data, $recipient->school_id);
        foreach (['mode', 'frequency_type', 'interval_days', 'weekly_day', 'monthly_mode', 'monthly_day', 'send_time', 'timezone', 'quiet_start', 'quiet_end'] as $key) {
            if ((string) ($recipient->{$key} ?? '') !== (string) ($next[$key] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /** Kirim email verifikasi (sinkron, seperti email keamanan akun) dan catat hasilnya. */
    private function sendVerification(NotificationRecipient $recipient): bool
    {
        $token = Str::random(64);
        $recipient->forceFill([
            'verification_token_hash' => hash('sha256', $token),
            'verification_sent_at' => now(),
        ])->save();
        $url = URL::temporarySignedRoute('notification-recipients.verify', now()->addHours(72), ['recipient' => $recipient->id, 'token' => $token]);
        try {
            Mail::to($recipient->email)->send(new RecipientVerificationMail($recipient->name, $url));
            $this->recorder->recordOutcome(null, $recipient->email, 'recipient.verification', 'Verifikasi alamat penerima notifikasi — RuteAman', true, null, $recipient->id);

            return true;
        } catch (\Throwable $e) {
            $this->recorder->recordOutcome(null, $recipient->email, 'recipient.verification', 'Verifikasi alamat penerima notifikasi — RuteAman', false, $e, $recipient->id);

            return false;
        }
    }
}
