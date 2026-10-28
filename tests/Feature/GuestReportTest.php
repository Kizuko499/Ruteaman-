<?php

namespace Tests\Feature;

use App\Models\Hazard;
use App\Models\HazardReport;
use App\Models\HazardType;
use App\Models\School;
use App\Models\User;
use App\Services\GlobalFeatureSettings;
use App\Services\HazardEvidenceService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestReportTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Dedicated spatial database required.');
        }
        parent::setUp();
        config(['ruteaman_admin.features.require_guest_review' => true, 'app.key' => 'base64:'.base64_encode(str_repeat('g', 32)), 'cache.default' => 'array']);
        Cache::flush();
        if (Schema::hasTable('platform_feature_settings')) {
            DB::table('platform_feature_settings')->where('id', 1)->update(['features' => json_encode(config('ruteaman_admin.features'))]);
            app(GlobalFeatureSettings::class)->forget();
        }
        $this->seed(DemoSeeder::class);
        School::query()->each(fn ($school) => $school->forceFill(['features' => [...($school->features ?? []), 'require_guest_review' => true]])->save());
    }

    private function school(): School
    {
        return School::where('name', 'Sekolah A — Demo Simulasi')->firstOrFail();
    }

    private function form(array $overrides = []): array
    {
        $this->get('/lapor?school='.$this->school()->id)->assertOk();
        $token = session('guest_report_form.token');
        $this->travel(3)->seconds();

        return [...['school_id' => $this->school()->id, 'type_id' => HazardType::where('code', 'flood')->value('id'), 'latitude' => -6.1768, 'longitude' => 106.825, 'report_consent' => 1, '_form_token' => $token], ...$overrides];
    }

    private function pending(): Hazard
    {
        return Hazard::where('requires_moderation', true)->latest('id')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin_a@ruteaman.test')->firstOrFail();
    }

    public function test_guest_form_links_and_nullable_name_without_creating_account(): void
    {
        $this->get('/lapor')->assertOk()->assertSee('Lapor tanpa login')->assertSee('name="school_id"', false)->assertSee('name="guest_name"', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/map?school='.$this->school()->id)->assertOk()->assertSee(route('guest-reports.create', ['school' => $this->school()->id]), false);
        $users = User::count();
        $data = $this->form();
        $this->post('/lapor', $data)->assertRedirect(route('guest-reports.receipt'));
        $h = $this->pending();
        $r = $h->reports()->firstOrFail();
        $this->assertNull($r->reporter_id);
        $this->assertNull($r->guest_name);
        $this->assertTrue($r->is_guest);
        $this->assertSame($this->school()->id, $r->school_id);
        $this->assertSame('new', $h->status->value);
        $this->assertNull($h->published_at);
        $this->assertSame($users, User::count());
        $this->get('/lapor/terkirim')->assertOk()->assertSee('Laporan diterima')->assertDontSee($h->public_slug);
        $this->post('/lapor', $data)->assertSessionHasErrors('_form_token');
        $this->assertSame(1, Hazard::where('requires_moderation', true)->count());
    }

    public function test_guest_name_is_private_and_admin_verification_publishes_only_condition(): void
    {
        $this->post('/lapor', $this->form(['guest_name' => 'NamaRahasiaQA', 'description' => 'Bukti privat pelapor']))->assertRedirect();
        $h = $this->pending();
        foreach (['/api/map/conditions?school='.$this->school()->id, '/api/feed/hazards.geojson?school='.$this->school()->id, '/api/conditions?school='.$this->school()->id] as $url) {
            $this->getJson($url)->assertOk()->assertDontSee($h->public_slug)->assertDontSee('NamaRahasiaQA');
        }
        $this->get(route('conditions.show', $h))->assertNotFound();
        $this->get(route('conditions.media', ['hazard' => $h, 'index' => 0]))->assertNotFound();
        $this->assertArrayNotHasKey('guest_name', $h->reports()->first()->toArray());
        $this->actingAs($this->admin())->get('/verifier/queue')->assertOk()->assertSee('NamaRahasiaQA')->assertSee('Nama/sekolah belum membuktikan identitas');
        $this->post(route('hazards.verify', $h), ['decision' => 'verified'])->assertRedirect();
        $h->refresh();
        $this->assertFalse($h->requires_moderation);
        $this->assertSame('verified', $h->status->value);
        $this->assertNotNull($h->published_at);
        Auth::logout();
        $this->get(route('conditions.show', $h))->assertOk()->assertDontSee('NamaRahasiaQA')->assertDontSee('Bukti privat pelapor');
        $this->getJson('/api/map/conditions?school='.$this->school()->id)->assertOk()->assertSee($h->public_slug)->assertDontSee('NamaRahasiaQA');
    }

    public function test_school_required_feature_and_scope_are_enforced(): void
    {
        $data = $this->form();
        unset($data['school_id']);
        $this->post('/lapor', $data)->assertSessionHasErrors('school_id');
        $this->post('/lapor', $this->form(['latitude' => -7, 'longitude' => 110]))->assertSessionHasErrors('latitude');
        $school = $this->school();
        $school->forceFill(['status' => 'paused'])->save();
        $data = $this->form();
        $this->postJson('/lapor', $data)->assertForbidden();
        $this->assertFalse(Hazard::where('requires_moderation', true)->exists());
    }

    public function test_disabled_reporting_and_invalid_school_are_rejected(): void
    {
        $this->post('/lapor', $this->form(['school_id' => 999999]))->assertSessionHasErrors('school_id');
        $school = $this->school();
        $school->forceFill(['features' => [...config('ruteaman_admin.features'), 'reporting' => false]])->save();
        $this->get('/lapor')->assertOk()->assertDontSee('<option value="'.$school->id.'"', false);
        $this->postJson('/lapor', $this->form())->assertForbidden();
    }

    public function test_no_duplicate_created_and_no_private_slug_disclosed(): void
    {
        $this->post('/lapor', $this->form(['guest_name' => 'NamaPertama']))->assertRedirect();
        $first = $this->pending();
        $before = HazardReport::count();
        $this->post('/lapor', $this->form(['guest_name' => 'NamaDuplikat']))->assertRedirect(route('guest-reports.receipt'));
        $this->assertSame($before, HazardReport::count());
        $this->assertSame(1, Hazard::where('requires_moderation', true)->count());
        $this->get('/lapor/terkirim')->assertOk()->assertSee('Kondisi serupa sudah tercatat')->assertDontSee($first->public_slug)->assertDontSee('NamaPertama');
    }

    public function test_guests_never_count_as_independent_and_cannot_confirm_pending(): void
    {
        $this->post('/lapor', $this->form())->assertRedirect();
        $h = $this->pending();
        $e = app(HazardEvidenceService::class);
        $this->assertSame(0, $e->counts($h)['independent']);
        foreach (User::where('role', 'student')->limit(3)->get() as $u) {
            $h->confirmations()->create(['user_id' => $u->id, 'vote' => 'still_there', 'created_at' => now()]);
        }
        $e->refresh($h);
        $this->assertSame('new', $h->fresh()->status->value);
        $this->assertNull($h->fresh()->published_at);
        $student = User::where('email', 'siswa_1@ruteaman.test')->firstOrFail();
        $this->actingAs($student)->get(route('conditions.show', $h))->assertNotFound();
        $this->postJson(route('hazards.confirm', $h), ['vote' => 'still_there'])->assertForbidden();
    }

    public function test_needs_evidence_rejection_and_reopen_do_not_publish_guest(): void
    {
        $this->post('/lapor', $this->form())->assertRedirect();
        $h = $this->pending();
        $this->actingAs($this->admin());
        $this->post(route('hazards.verify', $h), ['decision' => 'needs_evidence'])->assertRedirect();
        $h->refresh();
        $this->assertTrue($h->requires_moderation);
        $this->assertNull($h->published_at);
        $this->post(route('hazards.verify', $h), ['decision' => 'rejected'])->assertRedirect();
        $h->refresh();
        $this->assertTrue($h->requires_moderation);
        $this->assertNull($h->published_at);
        Auth::logout();
        $this->get(route('conditions.show', $h))->assertNotFound();
        $this->getJson('/api/map/conditions?school='.$this->school()->id)->assertDontSee($h->public_slug);
        $owner = (new User)->forceFill(['display_name' => 'Guest reopen QA', 'email' => 'guest-reopen@ruteaman.my.id', 'password' => 'FixturePasswordOnly123!', 'role' => 'owner', 'status' => 'active', 'active' => true]);
        $owner->save();
        $this->actingAs($owner)->withSession(['admin.reauth_at' => time(), 'admin.last_seen' => time()])->postJson(route('admin.hazards.reopen', $h))->assertOk();
        $this->assertTrue($h->fresh()->requires_moderation);
        $this->assertNull($h->fresh()->published_at);
        Auth::logout();
        $this->get(route('conditions.show', $h))->assertNotFound();
    }

    public function test_old_guest_stays_in_queue_and_can_be_verified(): void
    {
        $this->post('/lapor', $this->form())->assertRedirect();
        $h = $this->pending();
        $h->forceFill(['status' => 'expired', 'first_reported_at' => now()->subDays(10)])->save();
        $this->actingAs($this->admin())->get('/verifier/queue')->assertOk()->assertSee('Laporan tamu');
        $this->post(route('hazards.verify', $h), ['decision' => 'verified'])->assertRedirect();
        $this->assertFalse($h->fresh()->requires_moderation);
        // Verification is a review, not a new field observation. Old data stays stale.
        $this->assertFalse(Hazard::whereKey($h->id)->active()->exists());
    }

    public function test_spam_honeypot_consent_timing_and_names_are_validated(): void
    {
        $this->post('/lapor', $this->form(['website' => 'spam.invalid']))->assertSessionHasErrors('website');
        $this->post('/lapor', $this->form(['report_consent' => 0]))->assertSessionHasErrors('report_consent');
        $this->post('/lapor', $this->form(['guest_name' => '<script>bad</script>']))->assertSessionHasErrors('guest_name');
        $this->get('/lapor');
        $data = ['school_id' => $this->school()->id, 'type_id' => HazardType::first()->id, 'latitude' => -6.1768, 'longitude' => 106.825, 'report_consent' => 1, '_form_token' => session('guest_report_form.token')];
        $this->post('/lapor', $data)->assertStatus(429); // per-IP limit after three attempts
        $this->assertFalse(Hazard::where('requires_moderation', true)->exists());
    }

    public function test_two_second_form_guard_and_empty_name_array_rejection(): void
    {
        $data = $this->form(['guest_name' => ['invalid']]);
        $this->post('/lapor', $data)->assertSessionHasErrors('guest_name');
        $this->get('/lapor');
        $data['guest_name'] = '';
        $data['_form_token'] = session('guest_report_form.token');
        $this->post('/lapor', $data)->assertSessionHasErrors('_form_token');
    }

    public function test_guest_photo_is_sanitized_and_moderation_scope_survives_nonshared_layer(): void
    {
        Storage::fake('hazard_photos');
        $school = $this->school();
        $school->forceFill(['features' => [...config('ruteaman_admin.features'), 'shared_layer' => false]])->save();
        $this->post('/lapor', $this->form(['photo' => UploadedFile::fake()->image('bukti.jpg')]))->assertRedirect();
        $h = $this->pending();
        $report = $h->reports()->firstOrFail();
        Storage::disk('hazard_photos')->assertExists($report->photo_path);
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring(Storage::disk('hazard_photos')->get($report->photo_path))[2]);
        $this->assertTrue(Hazard::whereKey($h->id)->inSchool($school->id)->exists());
        $this->actingAs($this->admin())->get('/verifier/queue')->assertOk()->assertSee('Pelapor anonim');
    }

    public function test_guest_name_is_hidden_from_other_school_even_if_catchments_overlap(): void
    {
        $this->post('/lapor', $this->form(['guest_name' => 'HanyaSekolahAQa']))->assertRedirect();
        $h = $this->pending();
        $b = School::where('name', 'Sekolah B — Demo Simulasi')->firstOrFail();
        $u = (new User)->forceFill(['display_name' => 'School B admin', 'email' => 'bguest@example.test', 'password' => 'FixturePasswordOnly123!', 'role' => 'school_admin', 'school_id' => $b->id, 'permissions' => config('ruteaman_roles.ceilings.school_admin'), 'status' => 'active', 'active' => true]);
        $u->save();
        $this->actingAs($u)->get('/verifier/queue')->assertOk()->assertDontSee('HanyaSekolahAQa');
        $this->get(route('conditions.show', $h))->assertNotFound();
        $this->postJson(route('hazards.verify', $h), ['decision' => 'verified'])->assertForbidden();
    }

    public function test_logged_in_form_still_uses_account_and_guest_post_is_forbidden(): void
    {
        $data = $this->form();
        $u = User::where('email', 'siswa_1@ruteaman.test')->firstOrFail();
        $this->actingAs($u)->get('/lapor')->assertRedirect(route('hazards.create'));
        $this->postJson('/lapor', $data)->assertForbidden();
        unset($data['_form_token']);
        $this->post('/hazards', $data)->assertRedirect();
        $h = Hazard::latest('id')->firstOrFail();
        $this->assertFalse($h->requires_moderation);
        $this->assertSame($u->id, $h->reports()->first()->reporter_id);
    }

    public function test_guest_post_without_csrf_is_rejected_in_production_middleware(): void
    {
        $data = $this->form();
        $this->app['env'] = 'production';
        try {
            $this->post('/lapor', $data)->assertStatus(419);
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
