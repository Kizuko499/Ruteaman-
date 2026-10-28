<?php

namespace Tests\Feature;

use App\Jobs\DeliverEventMail;
use App\Mail\DigestSummaryMail;
use App\Mail\RecipientVerificationMail;
use App\Mail\TestMail;
use App\Models\EmailDeliveryLog;
use App\Models\NotificationDigestRun;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\MailLogRecorder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NotificationRecipientTest extends TestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('display_name');
            $table->string('email');
            $table->string('contact_email')->nullable();
            $table->string('password');
            $table->unsignedBigInteger('school_id')->nullable();
            $table->boolean('active')->default(true);
            $table->string('status')->default('active');
            $table->string('role');
            $table->json('permissions')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->integer('session_version')->default(0);
            $table->boolean('must_change_password')->default(false);
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('actor_role')->nullable();
            $table->string('action');
            $table->string('category')->nullable();
            $table->string('severity')->nullable();
            $table->string('result')->nullable();
            $table->string('entity')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_label')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->json('meta_json')->nullable();
            $table->json('meta')->nullable();
            $table->json('changes')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_id')->nullable();
            $table->string('route_name')->nullable();
            $table->string('http_method')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
        Schema::create('email_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('notification_recipient_id')->nullable();
            $table->char('recipient_hash', 64);
            $table->string('recipient_masked');
            $table->string('category', 48);
            $table->string('subject');
            $table->string('status', 16)->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->json('payload')->nullable();
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('institution_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('new');
            $table->timestamps();
        });
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->char('verification_token_hash', 64)->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            $table->string('mode', 16)->default('realtime');
            $table->string('frequency_type', 16)->default('weekly');
            $table->unsignedSmallInteger('interval_days')->nullable();
            $table->unsignedTinyInteger('weekly_day')->nullable();
            $table->string('monthly_mode', 8)->nullable();
            $table->unsignedTinyInteger('monthly_day')->nullable();
            $table->time('send_time')->default('08:00:00');
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->time('quiet_start')->nullable();
            $table->time('quiet_end')->nullable();
            $table->boolean('send_empty_digest')->default(false);
            $table->boolean('receive_all_reports')->default(false);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('notification_recipient_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipient_id');
            $table->string('category', 48);
        });
        Schema::create('notification_digest_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipient_id');
            $table->string('period_key', 64);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 16)->default('queued');
            $table->unsignedBigInteger('email_log_id')->nullable();
            $table->timestamps();
        });
        $this->owner = new User;
        $this->owner->forceFill([
            'display_name' => 'Owner', 'email' => 'owner-recipient@test.invalid',
            'password' => 'FixturePassword123!', 'school_id' => null, 'active' => true,
            'status' => 'active', 'role' => 'owner', 'permissions' => [], 'email_verified_at' => now(),
        ]);
        $this->owner->save();
        Mail::fake();
        Queue::fake();
    }

    /** actingAs + sesi admin yang selaras dengan AdminSession (session_version & last_seen). */
    private function asAdmin(User $user): static
    {
        $this->actingAs($user);

        return $this->withSession(['admin.session_version' => 0, 'admin.last_seen' => time()]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kepala Sekolah',
            'email' => 'kepala@contoh.sch.id',
            'description' => 'Kepala sekolah',
            'scope' => 'platform',
            'mode' => 'both',
            'frequency_type' => 'weekly',
            'weekly_day' => 1,
            'send_time' => '08:00',
            'timezone' => 'Asia/Jakarta',
            'categories' => ['report.submitted', 'digest.system'],
        ], $overrides);
    }

    public function test_owner_creates_recipient_and_verification_is_sent_and_logged(): void
    {
        $response = $this->asAdmin($this->owner)->post(route('admin.email.recipients.store'), $this->payload());
        $response->assertRedirect(route('admin.email.recipients.index'));
        $recipient = NotificationRecipient::where('email', 'kepala@contoh.sch.id')->firstOrFail();
        $this->assertNull($recipient->verified_at);
        $this->assertNotNull($recipient->next_run_at);
        $this->assertTrue($recipient->subscribesTo('report.submitted'));
        Mail::assertSent(RecipientVerificationMail::class, fn ($m) => $m->hasTo('kepala@contoh.sch.id'));
        $log = EmailDeliveryLog::where('category', 'recipient.verification')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($recipient->id, $log->notification_recipient_id);
        $this->assertSame('sent', $log->status);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->asAdmin($this->owner)->post(route('admin.email.recipients.store'), $this->payload());
        $response = $this->asAdmin($this->owner)->post(route('admin.email.recipients.store'), $this->payload());
        $response->assertSessionHasErrors('email');
        $this->assertSame(1, NotificationRecipient::count());
    }

    public function test_signed_link_verifies_recipient_once(): void
    {
        $recipient = NotificationRecipient::create([
            'name' => 'Operator', 'email' => 'operator@contoh.sch.id', 'is_active' => true,
            'mode' => 'realtime', 'frequency_type' => 'weekly', 'send_time' => '08:00:00', 'timezone' => 'Asia/Jakarta',
            'verification_token_hash' => hash('sha256', 'token-uji'), 'verification_sent_at' => now(),
        ]);
        $url = URL::temporarySignedRoute('notification-recipients.verify', now()->addHours(1), ['recipient' => $recipient->id, 'token' => 'token-uji']);
        $this->get($url)->assertOk();
        $this->assertNotNull($recipient->fresh()->verified_at);
        // Token sudah dibersihkan: tautan yang sama tidak berlaku lagi.
        $this->get($url)->assertForbidden();
    }

    public function test_student_cannot_open_recipients_page(): void
    {
        $student = new User;
        $student->forceFill([
            'display_name' => 'Siswa', 'email' => 'siswa-recipient@test.invalid', 'password' => 'FixturePassword123!',
            'school_id' => null, 'active' => true, 'status' => 'active', 'role' => 'student', 'permissions' => [], 'email_verified_at' => now(),
        ]);
        $student->save();
        $this->asAdmin($student)->get(route('admin.email.recipients.index'))->assertForbidden();
    }

    public function test_unverified_recipient_gets_no_digest(): void
    {
        NotificationRecipient::create([
            'name' => 'Belum Verif', 'email' => 'belum@contoh.sch.id', 'is_active' => true, 'verified_at' => null,
            'mode' => 'digest', 'frequency_type' => 'interval', 'interval_days' => 1, 'send_time' => '08:00:00',
            'timezone' => 'Asia/Jakarta', 'next_run_at' => now()->subHour(), 'send_empty_digest' => true,
        ]);
        $this->artisan('notifications:dispatch-digests')->assertSuccessful();
        Mail::assertNotSent(DigestSummaryMail::class);
        $this->assertSame(0, EmailDeliveryLog::where('category', 'digest.summary')->count());
    }

    public function test_digest_dispatches_once_and_advances_schedule(): void
    {
        $recipient = NotificationRecipient::create([
            'name' => 'Kepsek', 'email' => 'kepsek@contoh.sch.id', 'is_active' => true, 'verified_at' => now()->subDay(),
            'mode' => 'digest', 'frequency_type' => 'interval', 'interval_days' => 7, 'send_time' => '08:00:00',
            'timezone' => 'Asia/Jakarta', 'next_run_at' => now()->subHour(), 'send_empty_digest' => true,
        ]);
        $recipient->syncCategories(['digest.system']);
        $this->artisan('notifications:dispatch-digests')->assertSuccessful();
        $this->artisan('notifications:dispatch-digests')->assertSuccessful();
        $this->assertSame(1, EmailDeliveryLog::where('category', 'digest.summary')->count());
        $run = NotificationDigestRun::where('recipient_id', $recipient->id)->firstOrFail();
        $this->assertNotNull($run->email_log_id);
        $this->assertTrue($recipient->fresh()->next_run_at->isFuture());
    }

    public function test_empty_digest_is_skipped_when_configured(): void
    {
        $recipient = NotificationRecipient::create([
            'name' => 'Kepsek', 'email' => 'kepsek2@contoh.sch.id', 'is_active' => true, 'verified_at' => now()->subDay(),
            'mode' => 'digest', 'frequency_type' => 'interval', 'interval_days' => 7, 'send_time' => '08:00:00',
            'timezone' => 'Asia/Jakarta', 'next_run_at' => now()->subHour(), 'send_empty_digest' => false,
        ]);
        $recipient->syncCategories(['digest.accounts']);
        $this->artisan('notifications:dispatch-digests')->assertSuccessful();
        $run = NotificationDigestRun::where('recipient_id', $recipient->id)->firstOrFail();
        $this->assertSame('skipped_empty', $run->status);
        $this->assertSame(0, EmailDeliveryLog::where('category', 'digest.summary')->count());
    }

    public function test_guide_shows_email_chapters_to_owner(): void
    {
        $response = $this->asAdmin($this->owner)->get(route('admin.guide'));
        $response->assertOk();
        $response->assertSee('Penerima Notifikasi: alamat di luar akun');
        $response->assertSee('Uji Koneksi SMTP');
    }

    public function test_export_requires_manage_permission_not_just_monitor(): void
    {
        $monitor = new User;
        $monitor->forceFill([
            'display_name' => 'Pemantau', 'email' => 'pemantau@test.invalid', 'password' => 'FixturePassword123!',
            'school_id' => null, 'active' => true, 'status' => 'active', 'role' => 'platform_admin',
            'permissions' => ['email.monitor'], 'email_verified_at' => now(),
        ]);
        $monitor->save();
        $this->asAdmin($monitor)->get(route('admin.email.export'))->assertForbidden();
        $this->asAdmin($this->owner)->get(route('admin.email.export'))->assertOk();
    }

    public function test_cancelled_log_is_not_sent_by_job(): void
    {
        $recorder = app(MailLogRecorder::class);
        $log = $recorder->start(null, 'tujuan@contoh.com', 'admin.test', 'Email uji RuteAman', null, ['mailable' => TestMail::class, 'args' => ['Penguji']]);
        $log->forceFill(['status' => 'cancelled'])->save();
        (new DeliverEventMail($log->id, 'tujuan@contoh.com', TestMail::class, ['Penguji']))->handle($recorder);
        Mail::assertNotSent(TestMail::class);
        $this->assertSame('cancelled', $log->fresh()->status);
    }

    public function test_job_cancels_when_recipient_deactivated_after_queueing(): void
    {
        $recipient = NotificationRecipient::create([
            'name' => 'Kepsek', 'email' => 'kepsek3@contoh.sch.id', 'is_active' => true, 'verified_at' => now(),
            'mode' => 'both', 'frequency_type' => 'weekly', 'send_time' => '08:00:00', 'timezone' => 'Asia/Jakarta',
        ]);
        $recorder = app(MailLogRecorder::class);
        $log = $recorder->start(null, $recipient->email, 'digest.summary', 'Ringkasan', null, ['mailable' => DigestSummaryMail::class, 'args' => ['Kepsek', 'Periode', [], 'https://example.test']], $recipient->id);
        $recipient->forceFill(['is_active' => false])->save();
        (new DeliverEventMail($log->id, $recipient->email, DigestSummaryMail::class, ['Kepsek', 'Periode', [], 'https://example.test']))->handle($recorder);
        Mail::assertNotSent(DigestSummaryMail::class);
        $this->assertSame('cancelled', $log->fresh()->status);
    }
}
