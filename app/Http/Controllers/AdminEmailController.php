<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Jobs\DeliverEventMail;
use App\Mail\BroadcastMail;
use App\Mail\TestMail;
use App\Models\EmailDeliveryLog;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Support\EmailAddressList;
use App\Services\AdminAuditService;
use App\Services\MailLogRecorder;
use App\Services\SmtpDiagnostics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dashboard Email & Notifikasi (tab): Ringkasan, Penerima (controller terpisah),
 * Riwayat, Konfigurasi. Kredensial SMTP tidak pernah ditampilkan; yang tampil
 * hanya konfigurasi aman dari environment. Semua pengiriman tercatat di
 * email_delivery_logs dan halaman ini hanya membaca data aktual.
 */
class AdminEmailController extends Controller
{
    public function __construct(private MailLogRecorder $recorder) {}

    /** Tab Ringkasan: statistik aktual + penerima & jadwal. */
    public function index()
    {
        $lastSent = EmailDeliveryLog::where('status', 'sent')->latest('sent_at')->first();

        return view('admin.email', [
            'tab' => 'ringkasan',
            'stats' => [
                'queued' => EmailDeliveryLog::where('status', 'queued')->count(),
                'sent' => EmailDeliveryLog::where('status', 'sent')->count(),
                'failed' => EmailDeliveryLog::where('status', 'failed')->count(),
                'cancelled' => EmailDeliveryLog::where('status', 'cancelled')->count(),
                'last_sent_at' => $lastSent?->sent_at,
                'recipients_active' => NotificationRecipient::deliverable()->count(),
                'schedules_active' => NotificationRecipient::deliverable()->whereIn('mode', ['digest', 'both'])->count(),
            ],
            'nextDeliveries' => NotificationRecipient::deliverable()
                ->whereNotNull('next_run_at')
                ->with('school')
                ->orderBy('next_run_at')
                ->limit(5)
                ->get(),
            'config' => $this->safeConfig(),
        ]);
    }

    /** Tab Riwayat: filter lengkap + pencarian subjek. */
    public function history(Request $request)
    {
        $filters = $request->only(['status', 'category', 'date_from', 'date_to', 'q']);
        $logs = $this->filtered($request)->latest('id')->paginate(25)->withQueryString();
        $categories = EmailDeliveryLog::query()->select('category')->distinct()->orderBy('category')->pluck('category')->all();

        return view('admin.email.history', [
            'tab' => 'riwayat',
            'logs' => $logs,
            'filters' => $filters,
            'categories' => $categories,
        ]);
    }

    /** Detail satu catatan pengiriman (payload peristiwa aman menurut desain log). */
    public function show(EmailDeliveryLog $log)
    {
        $log->load(['user', 'notificationRecipient']);

        return view('admin.email.show', ['tab' => 'riwayat', 'log' => $log]);
    }

    /** Batalkan pengiriman yang masih mengantre. Job menghormati status ini. */
    public function cancel(Request $request, EmailDeliveryLog $log)
    {
        if ($log->status !== 'queued') {
            return back()->with('status', 'Hanya pengiriman yang masih dalam antrean yang dapat dibatalkan.');
        }
        $log->forceFill(['status' => 'cancelled', 'error' => 'Dibatalkan oleh administrator.'])->save();
        app(AdminAuditService::class)->write($request->user(), 'email.cancelled', 'email_delivery_log', $log->id);

        return back()->with('status', 'Pengiriman dibatalkan. Bila worker sudah mengambilnya, status batal tetap menghentikan pengiriman.');
    }

    /** Ekspor riwayat (CSV) mengikuti filter yang sedang aktif, maks 5.000 baris. */
    public function export(Request $request): StreamedResponse
    {
        $logs = $this->filtered($request)->latest('id')->limit(5000)->get();

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Waktu antre', 'Kategori', 'Penerima', 'Subjek', 'Status', 'Percobaan', 'Terkirim pada', 'Kesalahan']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->queued_at?->format('Y-m-d H:i:s'),
                    $log->categoryText(),
                    $log->recipient_masked,
                    $log->subject,
                    $log->statusText(),
                    $log->attempts,
                    $log->sent_at?->format('Y-m-d H:i:s'),
                    $log->error,
                ]);
            }
            fclose($out);
        }, 'riwayat-email-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Tab Konfigurasi: panel SMTP aman + diagnostik koneksi terakhir. */
    public function config()
    {
        return view('admin.email.config', [
            'tab' => 'konfigurasi',
            'config' => $this->safeConfig(),
            'diag' => SmtpDiagnostics::last(),
            'mailerWarning' => in_array(config('mail.default'), ['log', 'array'], true)
                ? 'Mailer saat ini "'.config('mail.default').'" — email tidak benar-benar dikirim. Ubah MAIL_MAILER menjadi smtp di konfigurasi server.'
                : null,
        ]);
    }

    /** Uji koneksi SMTP nyata (EHLO + verifikasi sertifikat), tanpa mengirim email. */
    public function smtpTest(SmtpDiagnostics $diagnostics)
    {
        $result = $diagnostics->check();

        return redirect()->route('admin.email.config')
            ->with('status', $result['ok']
                ? 'Uji koneksi SMTP berhasil: sertifikat cocok untuk host '.$result['host'].'.'
                : 'Uji koneksi SMTP gagal pada tahap '.$result['stage'].': '.$result['message']);
    }

    public function test(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        $actor = $request->user();
        try {
            Mail::to($data['email'])->send(new TestMail($actor->display_name));
            $this->recorder->recordOutcome($actor, $data['email'], 'admin.test', 'Email uji RuteAman', true);

            return back()->with('status', 'Email uji terkirim ke '.$data['email'].'. Periksa kotak masuk dan folder spam penerima.');
        } catch (\Throwable $e) {
            $this->recorder->recordOutcome($actor, $data['email'], 'admin.test', 'Email uji RuteAman', false, $e);

            return back()->with('status', 'Email uji gagal dikirim: '.MailLogRecorder::sanitizeError($e));
        }
    }

    public function retry(EmailDeliveryLog $log)
    {
        $payload = $log->payload ?? [];
        $mailable = $payload['mailable'] ?? null;
        $args = $payload['args'] ?? null;
        if ($log->status !== 'failed' || ! is_string($mailable) || ! class_exists($mailable) || ! is_array($args)) {
            return back()->with('status', 'Email ini tidak dapat dikirim ulang dari dashboard (bukan email peristiwa antrean atau payload tidak lengkap).');
        }
        $recipient = $log->user?->routeNotificationForMail();
        if (! $recipient && $log->notification_recipient_id) {
            $recipient = $log->notificationRecipient?->email;
        }
        if (! $recipient) {
            return back()->with('status', 'Alamat penerima asli tidak tersimpan demi keamanan; pengiriman ulang tidak dapat dilakukan.');
        }
        $log->forceFill(['status' => 'queued', 'queued_at' => now(), 'error' => null, 'failed_at' => null])->save();
        DeliverEventMail::dispatch($log->id, $recipient, $mailable, $args)->afterCommit();

        return back()->with('status', 'Pengiriman ulang dijadwalkan untuk '.$log->recipient_masked.'.');
    }

    private function filtered(Request $request)
    {
        $query = EmailDeliveryLog::query()->with(['user', 'notificationRecipient']);
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($from = $request->query('date_from')) {
            $query->whereDate('queued_at', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('queued_at', '<=', $to);
        }
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(fn ($sub) => $sub
                ->where('recipient_masked', 'like', '%'.$q.'%')
                ->orWhere('subject', 'like', '%'.$q.'%'));
        }

        return $query;
    }

    private function safeConfig(): array
    {
        $eff = SmtpDiagnostics::effectiveEncryption((string) (config('mail.mailers.smtp.encryption') ?? ''), (int) config('mail.mailers.smtp.port', 0));

        return [
            'mailer' => (string) config('mail.default'),
            'host' => (string) config('mail.mailers.smtp.host'),
            'port' => (string) config('mail.mailers.smtp.port'),
            'encryption' => $eff['value'] === '' ? '' : $eff['value'].($eff['inferred'] ? ' (tersirat dari port)' : ''),
            'username_set' => filled(config('mail.mailers.smtp.username')),
            'from' => (string) config('mail.from.address'),
            'from_name' => (string) config('mail.from.name'),
            'queue' => (string) config('queue.default'),
            'ready' => ! in_array(config('mail.default'), ['log', 'array'], true)
                && filled(config('mail.from.address'))
                && filled(config('mail.mailers.smtp.host')),
        ];
    }

    /** Tab Kirim Informasi: formulir kirim email bebas tanpa mendaftarkan penerima. */
    public function compose()
    {
        $categoryOptions = [['key' => 'admin.broadcast', 'label' => 'Tulis sendiri (tanpa jenis — isi bebas)', 'subs' => null]];
        foreach (NotificationRecipient::categoryGroups(true) as $items) {
            foreach ($items as $key => $label) {
                $categoryOptions[] = ['key' => $key, 'label' => $label, 'subs' => NotificationRecipient::deliverable()->whereHas('categoryRows', fn ($q) => $q->where('category', $key))->count()];
            }
        }

        foreach (NotificationRecipient::broadcastTypes() as $key => $label) {
            $categoryOptions[] = ['key' => $key, 'label' => $label, 'subs' => null, 'all' => true];
        }

        return view('admin.email.compose', ['categoryOptions' => $categoryOptions, 'templates' => (array) config('notification_mail.templates', []), 'counts' => [
            'recipients' => NotificationRecipient::deliverable()->count(),
            'admins' => User::whereIn('role', [Role::Owner, Role::PlatformAdmin, Role::SchoolAdmin, Role::SchoolStaff])->where('active', true)->whereNotNull('email_verified_at')->count(),
            'users' => User::where('active', true)->whereNotNull('email_verified_at')->count(),
        ]]);
    }

    /**
     * Mengirim email informasi ke target terpilih secara langsung. Setiap
     * alamat menerima email sendiri-sendiri dan hasilnya tercatat sebagai
     * kategori admin.broadcast; tidak ada daftar CC yang bocor antar penerima.
     */
    public function sendBroadcast(Request $request)
    {
        $actor = $request->user();
        $target = (string) $request->input('target', 'manual');
        $category = (string) $request->input('category', 'admin.broadcast');
        $allowedCategories = ['admin.broadcast', ...array_keys(NotificationRecipient::broadcastTypes())];
        foreach (NotificationRecipient::categoryGroups(true) as $items) {
            $allowedCategories = [...$allowedCategories, ...array_keys($items)];
        }
        if (! in_array($category, $allowedCategories, true)) {
            return back()->withInput()->with('status', 'Jenis informasi tidak dikenal. Belum ada email yang dikirim.');
        }
        $subject = trim((string) $request->input('subject', ''));
        $body = trim((string) $request->input('body', ''));
        if (! in_array($target, ['manual', 'recipients', 'admins', 'users'], true)) {
            return back()->withInput()->with('status', 'Target penerima tidak dikenal. Belum ada email yang dikirim.');
        }
        if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) {
            return back()->withInput()->with('status', 'Subjek wajib diisi (3–150 karakter). Belum ada email yang dikirim.');
        }
        if (mb_strlen($body) < 10 || mb_strlen($body) > 5000) {
            return back()->withInput()->with('status', 'Isi informasi wajib diisi (10–5.000 karakter). Belum ada email yang dikirim.');
        }

        $targets = [];
        if ($target === 'manual') {
            $parsed = EmailAddressList::parse((string) $request->input('addresses', ''));
            if ($parsed['invalid'] !== []) {
                return back()->withInput()->with('status', 'Ada alamat tidak valid: '.implode(', ', array_slice($parsed['invalid'], 0, 5)).'. Perbaiki dulu — belum ada email yang dikirim.');
            }
            if (count($parsed['valid']) > 200) {
                return back()->withInput()->with('status', 'Alamat manual terlalu banyak ('.count($parsed['valid']).'); batas sekali kirim 200 alamat.');
            }
            foreach ($parsed['valid'] as $email) {
                $targets[] = [$email, null, null];
            }
        } elseif ($target === 'recipients') {
            $recipients = NotificationRecipient::deliverable();
            if ($category !== 'admin.broadcast' && ! array_key_exists($category, NotificationRecipient::broadcastTypes())) {
                $recipients->whereHas('categoryRows', fn ($q) => $q->where('category', $category));
            }
            foreach ($recipients->get() as $recipient) {
                $targets[] = [strtolower(trim($recipient->email)), null, $recipient->id];
            }
        } else {
            $users = User::where('active', true)->whereNotNull('email_verified_at');
            if ($target === 'admins') {
                $users->whereIn('role', [Role::Owner, Role::PlatformAdmin, Role::SchoolAdmin, Role::SchoolStaff]);
            }
            foreach ($users->get() as $user) {
                $targets[] = [$user->routeNotificationForMail(), $user, null];
            }
        }

        $seen = [];
        $unique = [];
        foreach ($targets as $row) {
            $key = strtolower(trim((string) $row[0]));
            if ($key !== '' && ! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $row;
            }
        }
        if ($unique === []) {
            return back()->withInput()->with('status', 'Tidak ada penerima yang memenuhi syarat untuk target ini. Belum ada email yang dikirim.');
        }
        if (count($unique) > 200) {
            return back()->withInput()->with('status', 'Penerima terlalu banyak ('.count($unique).'); karena dikirim langsung, batas sekali kirim 200 alamat. Belum ada email yang dikirim.');
        }

        // Kirim langsung (sinkron) sesuai permintaan: hasil tiap alamat
        // langsung final dan tercatat; kegagalan satu alamat tidak
        // menghentikan alamat berikutnya dan dapat dikirim ulang dari Riwayat.
        $args = [$actor->display_name, $subject, $body];
        $sent = 0;
        $failed = 0;
        foreach ($unique as [$email, $user, $recipientId]) {
            $log = $this->recorder->start($user, $email, $category, $subject, null, ['mailable' => BroadcastMail::class, 'args' => $args], $recipientId);
            try {
                Mail::to($email)->send(new BroadcastMail(...$args));
                $this->recorder->markSent($log);
                $sent++;
            } catch (\Throwable $e) {
                $this->recorder->markFailed($log, $e);
                $failed++;
            }
        }

        return redirect()->route('admin.email.compose')
            ->with('status', $failed === 0
                ? 'Informasi terkirim ke '.$sent.' alamat dengan subjek "'.$subject.'".'
                : 'Informasi terkirim ke '.$sent.' alamat; '.$failed.' gagal. Lihat tab Riwayat Pengiriman (kategori Informasi admin) untuk detail dan kirim ulang yang gagal.');
    }
}
