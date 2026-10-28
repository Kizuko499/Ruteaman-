<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\School;
use App\Models\User;
use App\Services\GuestReportService;
use App\Services\HazardEvidenceService;
use App\Services\ReportPublicationPolicy;
use App\Services\SchoolProvisioningService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportPublicationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Dedicated spatial database required.');
        }
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('p', 32)), 'cache.default' => 'array']);
        Cache::flush();
        $this->seed(DemoSeeder::class);
    }

    private function school(): School
    {
        return School::where('name', 'Sekolah A — Demo Simulasi')->firstOrFail();
    }

    private function student(): User
    {
        return User::where('email', 'siswa_1@ruteaman.test')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin_a@ruteaman.test')->firstOrFail();
    }

    private function owner(): User
    {
        $u = (new User)->forceFill(['display_name' => 'Publication QA', 'email' => 'publication-qa@ruteaman.my.id', 'password' => 'SyntheticPasswordOnly!123', 'role' => 'owner', 'status' => 'active', 'active' => true]);
        $u->save();

        return $u;
    }

    private function data(): array
    {
        return ['school_id' => $this->school()->id, 'type_id' => HazardType::where('code', 'flood')->value('id'), 'latitude' => -6.1768, 'longitude' => 106.825, 'description' => 'DeskripsiPrivatQa', 'guest_name' => 'NamaPrivatQa'];
    }

    private function guest(): Hazard
    {
        app(GuestReportService::class)->submit($this->data(), null);

        return Hazard::latest('id')->firstOrFail();
    }

    private function settings(bool $guest, bool $account): void
    {
        $this->school()->forceFill(['features' => [...config('ruteaman_admin.features'), 'require_guest_review' => $guest, 'require_account_review' => $account]])->save();
    }

    private function publicAssertions(Hazard $h, bool $visible): void
    {
        Auth::logout();
        $this->get(route('conditions.show', $h))->assertStatus($visible ? 200 : 404);
        foreach (['/api/map/conditions', '/api/feed/hazards.geojson', '/api/conditions'] as $path) {
            $r = $this->getJson($path.'?school='.$this->school()->id)->assertOk()->assertDontSee('NamaPrivatQa')->assertDontSee('DeskripsiPrivatQa');
            if ($visible) {
                $r->assertSee($h->public_slug);
            } else {
                $r->assertDontSee($h->public_slug);
            }
        }
    }

    public static function modes(): array
    {
        return [[false, false], [false, true], [true, false], [true, true]];
    }

    #[DataProvider('modes')]
    public function test_guest_and_account_switches_are_independent(bool $guest, bool $account): void
    {
        $this->settings($guest, $account);
        $policy = app(ReportPublicationPolicy::class);
        $this->assertSame($guest, $policy->requiresReview($this->school(), true));
        $this->assertSame($account, $policy->requiresReview($this->school(), false));
        $h = $this->guest();
        $this->assertSame($guest, $h->requires_moderation);
        $this->assertSame($guest, $h->published_at === null);
        $this->publicAssertions($h, ! $guest);
        $d = $this->data();
        $d['longitude'] = 106.826;
        unset($d['guest_name']);
        $this->actingAs($this->student())->post(route('hazards.store'), $d)->assertRedirect();
        $a = Hazard::latest('id')->firstOrFail();
        $this->assertNotSame($h->id, $a->id);
        $this->assertSame($account, $a->requires_moderation);
        $this->assertSame($account, $a->published_at === null);
        $this->assertSame($this->school()->id, $a->reports()->first()->school_id);
        $this->publicAssertions($a, ! $account);
    }

    public function test_guest_default_is_public_unverified_and_no_account_created(): void
    {
        $before = User::count();
        $this->get('/lapor');
        $token = session('guest_report_form.token');
        $this->travel(3)->seconds();
        $this->post('/lapor', [...$this->data(), 'report_consent' => 1, '_form_token' => $token])->assertRedirect(route('guest-reports.receipt'));
        $h = Hazard::latest('id')->firstOrFail();
        $this->assertSame($before, User::count());
        $this->assertSame('new', $h->status->value);
        $this->assertSame('Belum terverifikasi', $h->status_label);
        $this->assertSame(0, app(HazardEvidenceService::class)->counts($h)['independent']);
        $this->assertNull($h->reports()->first()->reporter_id);
        $this->get('/lapor/terkirim')->assertSee('langsung tampil')->assertDontSee($h->public_slug);
        $this->publicAssertions($h, true);
    }

    public function test_pending_account_can_track_privately_and_admin_can_release(): void
    {
        $this->settings(false, true);
        $this->actingAs($this->student())->post(route('hazards.store'), $this->data())->assertRedirect(route('reports.mine'));
        $h = Hazard::latest('id')->firstOrFail();
        $this->get(route('reports.mine'))->assertOk();
        $this->get(route('conditions.show', $h))->assertOk();
        $this->publicAssertions($h, false);
        $this->actingAs($this->admin())->get('/verifier/queue')->assertOk()->assertSee('Laporan pengguna login');
        $this->post(route('hazards.verify', $h), ['decision' => 'verified'])->assertRedirect();
        $this->assertFalse($h->fresh()->requires_moderation);
        $this->publicAssertions($h->fresh(), true);
    }

    public function test_changing_switches_never_releases_pending_or_hides_existing_public(): void
    {
        $owner = $this->owner();
        $this->settings(true, true);
        $h = $this->guest();
        app(SchoolProvisioningService::class)->features($owner, $this->school(), ['require_guest_review' => false, 'require_account_review' => false]);
        $this->assertTrue($h->fresh()->requires_moderation);
        $this->publicAssertions($h, false);
        $d = $this->data();
        $d['longitude'] = 106.826;
        app(GuestReportService::class)->submit($d, null);
        $public = Hazard::latest('id')->firstOrFail();
        app(SchoolProvisioningService::class)->features($owner, $this->school(), ['require_guest_review' => true, 'require_account_review' => true]);
        $this->assertFalse($public->fresh()->requires_moderation);
        $this->publicAssertions($public, true);
    }

    private function moderate(User $u, Hazard $h, string $action)
    {
        return $this->actingAs($u)->withSession(['admin.reauth_at' => time(), 'admin.last_seen' => time()])->postJson(route('admin.hazards.moderation', ['hazard' => $h, 'action' => $action]), ['school' => $this->school()->id, 'reason' => 'Informasi keliru, ditinjau pengelola.']);
    }

    public function test_suspend_restore_and_soft_remove_hide_public_and_keep_history(): void
    {
        $h = $this->guest();
        $owner = $this->owner();
        $reports = $h->reports()->count();
        $this->publicAssertions($h, true);
        $this->moderate($owner, $h, 'suspend')->assertRedirect();
        $this->assertSame('suspended', $h->fresh()->publication_control);
        $this->publicAssertions($h, false);
        $this->get(route('conditions.og', $h))->assertNotFound();
        $this->get(route('conditions.media', ['hazard' => $h, 'index' => 0]))->assertNotFound();
        $this->moderate($owner, $h, 'restore')->assertRedirect();
        $this->publicAssertions($h, true);
        $this->moderate($owner, $h, 'remove')->assertRedirect();
        $this->assertSame($reports, $h->reports()->count());
        $this->assertSame('removed', $h->fresh()->publication_control);
        $this->assertTrue(AuditLog::where('action', 'report.remove')->where('entity_id', $h->id)->exists());
        $this->publicAssertions($h, false);
        $this->moderate($owner, $h, 'restore')->assertStatus(409);
    }

    public function test_scope_permission_reason_and_reauth_are_enforced(): void
    {
        $h = $this->guest();
        $this->moderate($this->student(), $h, 'suspend')->assertForbidden();
        $other = (new User)->forceFill(['display_name' => 'Outside admin', 'email' => 'outside-publication@test.invalid', 'password' => 'SyntheticOnly!123', 'role' => 'school_admin', 'school_id' => School::where('name', 'Sekolah B — Demo Simulasi')->value('id'), 'permissions' => config('ruteaman_roles.ceilings.school_admin'), 'active' => true, 'status' => 'active']);
        $other->save();
        $this->moderate($other, $h, 'remove')->assertForbidden();
        $owner = $this->owner();
        $url = route('admin.hazards.moderation', ['hazard' => $h, 'action' => 'suspend']);
        $this->actingAs($owner)->withSession(['admin.reauth_at' => 0, 'admin.last_seen' => time()])->postJson($url, ['school' => $this->school()->id, 'reason' => 'Salah'])->assertStatus(428);
        $this->withSession(['admin.reauth_at' => time()])->postJson($url, ['school' => $this->school()->id])->assertUnprocessable();
        $this->assertSame('normal', $h->fresh()->publication_control);
    }

    public function test_hidden_reports_cannot_be_verified_confirmed_or_reopened(): void
    {
        $h = $this->guest();
        $owner = $this->owner();
        $this->moderate($owner, $h, 'suspend')->assertRedirect();
        $this->actingAs($this->admin())->postJson(route('hazards.verify', $h), ['decision' => 'verified'])->assertStatus(409);
        $this->actingAs($this->student())->postJson(route('hazards.confirm', $h), ['vote' => 'still_there'])->assertStatus(409);
        $h->forceFill(['status' => 'expired'])->save();
        $this->actingAs($owner)->withSession(['admin.reauth_at' => time()])->postJson(route('admin.hazards.reopen', $h))->assertStatus(409);
        $this->assertSame('suspended', $h->fresh()->publication_control);
    }

    public function test_hidden_pending_stays_out_of_queue_and_expired_history(): void
    {
        $this->settings(true, false);
        $h = $this->guest();
        $owner = $this->owner();
        $this->moderate($owner, $h, 'suspend')->assertRedirect();
        $h->forceFill(['status' => 'expired', 'published_at' => now()->subDays(1), 'first_reported_at' => now()->subDays(2)])->save();
        $this->actingAs($this->admin())->get('/verifier/queue')->assertOk()->assertDontSee('NamaPrivatQa');
        $this->publicAssertions($h, false);
        $this->assertFalse(Hazard::whereKey($h->id)->publicListed()->exists());
    }

    public function test_required_review_cannot_disable_verification_tool(): void
    {
        $this->expectException(ValidationException::class);
        app(SchoolProvisioningService::class)->features($this->owner(), $this->school(), ['require_guest_review' => true, 'verification' => false]);
    }

    public function test_admin_has_school_selector_moderation_and_separate_switch_labels(): void
    {
        $this->guest();
        $this->actingAs($this->owner())->get('/admin/hazards?school='.$this->school()->id)->assertOk()->assertSee('Tangguhkan')->assertSee('Hapus dari publik')->assertSee('name="school"', false);
        $this->get('/admin/features')->assertOk()->assertSee('Wajib verifikasi laporan tamu')->assertSee('Wajib verifikasi laporan pengguna login')->assertSee('name="features[require_guest_review]"', false)->assertSee('name="features[require_account_review]"', false);
    }

    public function test_moderation_removes_condition_from_saved_route_scoring(): void
    {
        $h = Hazard::where('public_slug', 'demo-lubang-jl-mawar')->firstOrFail();
        $owner = $this->owner();
        $url = '/api/routes/saved?school='.$this->school()->id;
        $this->getJson($url)->assertOk()->assertJsonPath('routes.0.load.value', 22);
        $this->moderate($owner, $h, 'suspend')->assertRedirect();
        Auth::logout();
        $this->getJson($url)->assertOk()->assertJsonPath('routes.0.load.value', null)->assertJsonPath('routes.0.load.label_code', 'no_report');
        $this->moderate($owner, $h, 'restore')->assertRedirect();
        Auth::logout();
        $this->getJson($url)->assertOk()->assertJsonPath('routes.0.load.value', 22);
        $this->moderate($owner, $h, 'remove')->assertRedirect();
        Auth::logout();
        $this->getJson($url)->assertOk()->assertJsonPath('routes.0.load.value', null)->assertJsonPath('routes.0.load.label_code', 'no_report');
    }

    public function test_pending_duplicate_has_no_confirmation_action(): void
    {
        $this->settings(false, true);
        $this->actingAs($this->student())->post(route('hazards.store'), $this->data())->assertRedirect(route('reports.mine'));
        $this->getJson('/api/hazards/duplicate?'.http_build_query($this->data()))->assertOk()->assertJsonPath('duplicate.pending_review', true)->assertJsonPath('duplicate.confirm_url', null)->assertJsonPath('duplicate.url', route('reports.mine'));
    }

    public function test_other_school_cannot_moderate_pending_report_even_when_areas_overlap(): void
    {
        $this->settings(true, false);
        $h = $this->guest();
        $b = School::where('name', 'Sekolah B — Demo Simulasi')->firstOrFail();
        $u = (new User)->forceFill(['display_name' => 'Scoped B QA', 'email' => 'scoped-b-qa@test.invalid', 'password' => 'SyntheticOnly!123', 'role' => 'school_admin', 'school_id' => $b->id, 'permissions' => config('ruteaman_roles.ceilings.school_admin'), 'active' => true, 'status' => 'active']);
        $u->save();
        $this->actingAs($u)->withSession(['admin.reauth_at' => time(), 'admin.last_seen' => time()])->postJson(route('admin.hazards.moderation', ['hazard' => $h, 'action' => 'remove']), ['school' => $b->id, 'reason' => 'Wrong school scope test'])->assertForbidden();
        $this->assertSame('normal', $h->fresh()->publication_control);
    }

    public function test_saved_report_school_survives_reporter_anonymization_on_nonshared_layer(): void
    {
        $this->school()->forceFill(['features' => [...config('ruteaman_admin.features'), 'shared_layer' => false]])->save();
        $this->actingAs($this->student())->post(route('hazards.store'), $this->data())->assertRedirect();
        $h = Hazard::latest('id')->firstOrFail();
        $h->reports()->update(['reporter_id' => null]);
        $this->assertTrue(Hazard::whereKey($h->id)->inSchool($this->school()->id)->publicListed()->exists());
    }
}
