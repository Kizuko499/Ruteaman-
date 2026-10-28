<?php

namespace Tests\Feature;

use App\Jobs\DeliverEventMail;
use App\Mail\ReportCommentMail;
use App\Mail\ReportStatusMail;
use App\Mail\ReportSubmittedMail;
use App\Models\EmailDeliveryLog;
use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AuditLogger;
use App\Services\MailLogRecorder;
use App\Services\ReportMailNotifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use DatabaseTransactions;

    private User $reporter;
    private User $verifier;
    private User $owner;
    private Hazard $hazard;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Memerlukan DB spatial uji.');
        }
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        $s = DB::table('schools')->insertGetId(['name' => 'Sekolah Email', 'location' => DB::raw("ST_GeomFromText('POINT(106.82 -6.17)',4326)"), 'catchment' => DB::raw("ST_GeomFromText('POLYGON((106.8 -6.2,106.9 -6.2,106.9 -6.1,106.8 -6.1,106.8 -6.2))',4326)")]);
        $mk = function (array $a) use ($s) {
            $u = new User;
            $u->forceFill(array_merge(['display_name' => 'Fixture', 'password' => 'FixturePassword123!', 'school_id' => $s, 'active' => true, 'status' => 'active', 'permissions' => []], $a));
            $u->save();

            return $u;
        };
        $this->reporter = $mk(['display_name' => 'Pelapor', 'email' => 'pelapor-email@test.invalid', 'role' => 'student', 'email_verified_at' => now()]);
        $this->verifier = $mk(['display_name' => 'Verifikator', 'email' => 'verif-email@test.invalid', 'role' => 'verifier', 'email_verified_at' => now(), 'permissions' => config('ruteaman_roles.ceilings.verifier')]);
        $this->owner = $mk(['display_name' => 'Owner', 'email' => 'owner-email@test.invalid', 'role' => 'owner', 'school_id' => null, 'email_verified_at' => now()]);
        $type = HazardType::where('code', 'pothole')->firstOrFail();
        $id = DB::table('hazards')->insertGetId(['type_id' => $type->id, 'location' => DB::raw("ST_GeomFromText('POINT(106.82 -6.17)',4326)"), 'severity' => 1, 'status' => 'new', 'confidence' => 0, 'first_reported_at' => now(), 'public_slug' => 'email-'.Str::random(10), 'location_label' => 'Depan gerbang', 'created_at' => now(), 'updated_at' => now()]);
        $this->hazard = Hazard::findOrFail($id);
        $this->hazard->reports()->create(['reporter_id' => $this->reporter->id, 'school_id' => $s, 'observed_at' => now()]);
        Mail::fake();
    }

    public function test_verification_email_is_recorded_in_delivery_log(): void
    {
        app(AccountService::class)->verification($this->reporter, 'baru-email@test.invalid');
        $log = EmailDeliveryLog::where('category', 'account.verification')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame('b***@test.invalid', $log->recipient_masked);
    }

    public function test_new_report_emails_only_eligible_school_verifiers_once(): void
    {
        $unverified = new User;
        $unverified->forceFill(['display_name' => 'Belum Verif', 'email' => 'belum@test.invalid', 'password' => 'FixturePassword123!', 'school_id' => $this->reporter->school_id, 'role' => 'verifier', 'active' => true, 'status' => 'active', 'permissions' => config('ruteaman_roles.ceilings.verifier'), 'email_verified_at' => null]);
        $unverified->save();

        app(ReportMailNotifier::class)->submitted($this->hazard);
        app(ReportMailNotifier::class)->submitted($this->hazard);

        $logs = EmailDeliveryLog::where('category', 'report.submitted')->get();
        $this->assertCount(1, $logs);
        $this->assertSame($this->verifier->id, $logs->first()->user_id);
        Mail::assertSent(ReportSubmittedMail::class, fn ($m) => $m->hasTo('verif-email@test.invalid'));
        Mail::assertNotSent(ReportSubmittedMail::class, fn ($m) => $m->hasTo('belum@test.invalid'));
    }

    public function test_status_change_email_follows_audit_trail(): void
    {
        app(AuditLogger::class)->record($this->verifier, 'verification.verified', 'hazard', $this->hazard->id);
        $logs = EmailDeliveryLog::where('category', 'report.status_changed')->get();
        $this->assertCount(1, $logs);
        $this->assertSame($this->reporter->id, $logs->first()->user_id);
        Mail::assertSent(ReportStatusMail::class, fn ($m) => $m->hasTo('pelapor-email@test.invalid'));
    }

    public function test_comment_email_excludes_author_and_respects_preference(): void
    {
        $comment = $this->hazard->comments()->create(['user_id' => $this->verifier->id, 'body' => 'Sudah dicek.']);
        app(ReportMailNotifier::class)->commentAdded($comment);
        $this->assertSame(1, EmailDeliveryLog::where('category', 'report.comment_added')->count());
        Mail::assertSent(ReportCommentMail::class, fn ($m) => $m->hasTo('pelapor-email@test.invalid'));

        $this->reporter->forceFill(['notification_preferences' => ['report_comments' => false]])->save();
        $comment2 = $this->hazard->comments()->create(['user_id' => $this->verifier->id, 'body' => 'Komentar kedua.']);
        app(ReportMailNotifier::class)->commentAdded($comment2);
        $this->assertSame(1, EmailDeliveryLog::where('category', 'report.comment_added')->count());
    }

    public function test_preferences_endpoint_stores_notification_choices(): void
    {
        $this->actingAs($this->reporter)->patch(route('account.preferences'), [
            'appearance' => 'light', 'locale' => 'id',
            'notification_preferences' => ['report_status' => '1'],
        ])->assertOk();
        $fresh = $this->reporter->fresh();
        $this->assertTrue($fresh->wantsMail('report_status'));
        $this->assertFalse($fresh->wantsMail('report_comments'));
    }

    public function test_admin_email_dashboard_permission_and_test_mail(): void
    {
        $this->actingAs($this->reporter)->get(route('admin.email.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('admin.email.index'))->assertOk()->assertSee('Pengiriman Email');
        $this->actingAs($this->owner)->post(route('admin.email.test'), ['email' => 'tujuan@test.invalid'])->assertRedirect();
        $log = EmailDeliveryLog::where('category', 'admin.test')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
    }

    public function test_failed_event_mail_retry_requeues(): void
    {
        Queue::fake();
        $log = app(MailLogRecorder::class)->start($this->reporter, 'pelapor-email@test.invalid', 'report.status_changed', 'Perubahan status laporan — RuteAman', 'retry-test:'.$this->reporter->id, ['mailable' => ReportStatusMail::class, 'args' => ['Jalan rusak', 'Diverifikasi', null, 'https://ruteaman.test/kondisi/x']]);
        $log->forceFill(['status' => 'failed', 'failed_at' => now(), 'error' => 'SMTP mati'])->save();
        $this->actingAs($this->owner)->post(route('admin.email.retry', $log))->assertRedirect();
        $this->assertSame('queued', $log->fresh()->status);
        Queue::assertPushed(DeliverEventMail::class);
    }

    public function test_delivery_error_messages_are_sanitized(): void
    {
        $err = MailLogRecorder::sanitizeError(new \RuntimeException('Gagal ke budi@test.invalid via https://contoh.id/x?token='.str_repeat('a', 40)));
        $this->assertStringNotContainsString('budi@test.invalid', $err);
        $this->assertStringNotContainsString('https://', $err);
        $this->assertStringNotContainsString(str_repeat('a', 40), $err);
    }
}
