<?php

namespace Tests\Feature;

use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\School;
use App\Models\User;
use App\Services\RouteComparisonService;
use App\Services\SpatialSql;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HotfixHazardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Spatial QA database required');
        }parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('q', 32))]);
        $this->seed(DemoSeeder::class);
        Cache::flush();
    }

    private function owner(): User
    {
        $u = (new User)->forceFill(['display_name' => 'Hotfix QA', 'email' => 'hotfix-owner@example.test', 'password' => 'SyntheticFixtureOnly123!', 'role' => 'owner', 'status' => 'active', 'active' => true]);
        $u->save();

        return $u;
    }

    private function school(): School
    {
        return School::where('name', 'Sekolah A — Demo Simulasi')->firstOrFail();
    }

    private function hazard(string $slug, int $minutes = 1, bool $pending = false, bool $community = false): Hazard
    {
        $values = ['type_id' => HazardType::where('code', 'pothole')->value('id'), 'location' => SpatialSql::point(106.825, -6.178), 'severity' => 2, 'status' => 'new', 'requires_moderation' => $pending, 'published_at' => $pending ? null : now(), 'confidence' => .1, 'first_reported_at' => now()->subMinutes($minutes), 'public_slug' => $slug, 'created_at' => now(), 'updated_at' => now()];
        if (Schema::hasColumn('hazards', 'is_community')) {
            $values['is_community'] = $community;
        }
        $h = Hazard::findOrFail(DB::table('hazards')->insertGetId($values));
        $h->reports()->create(['school_id' => $community ? null : $this->school()->id, 'guest_name' => 'PRIVATE QA GUEST', 'description' => 'PRIVATE QA DESCRIPTION', 'observed_at' => now()->subMinutes($minutes)]);

        return $h;
    }

    public function test_connection_avoids_read_uncommitted_and_detail_is_accessible(): void
    {
        $this->assertSame('READ-COMMITTED', DB::selectOne('SELECT @@session.tx_isolation AS isolation')->isolation);
        $h = $this->hazard('hotfix-public');
        $this->get(route('conditions.show', $h))->assertOk()->assertSee('Belum terverifikasi')->assertDontSee('PRIVATE QA GUEST')->assertDontSee('PRIVATE QA DESCRIPTION');
        $this->getJson('/api/conditions/'.$h->public_slug)->assertOk();
        $this->getJson('/api/conditions/'.$h->public_slug.'/related')->assertOk();
    }

    public function test_owner_queue_is_not_first_school_or_ttl_limited(): void
    {
        $h = $this->hazard('hotfix-old', 1000000);
        $b = School::where('name', 'Sekolah B — Demo Simulasi')->firstOrFail();
        $h->reports()->update(['school_id' => $b->id]);
        DB::table('hazards')->where('id', $h->id)->update(['location' => SpatialSql::point(110, -7)]);
        $this->actingAs($this->owner())->get(route('verifier.queue'))->assertOk()->assertSee('hotfix-old')->assertSee('Seluruh cakupan');
        $this->get(route('verifier.queue', ['school' => $this->school()->id]))->assertOk()->assertDontSee('hotfix-old');
        $this->get(route('conditions.show', $h))->assertOk();
        $observed = $h->first_reported_at->toIso8601String();
        $this->post(route('hazards.verify', $h), ['decision' => 'verified'])->assertRedirect();
        $this->assertSame('verified', $h->fresh()->status->value);
        $this->assertSame($observed, $h->fresh()->first_reported_at->toIso8601String());
        $this->assertNull($h->fresh()->last_confirmed_at);
        $this->assertFalse(Hazard::whereKey($h->id)->active()->exists());
    }

    public function test_moderated_and_rejected_reports_keep_privacy(): void
    {
        $h = $this->hazard('hotfix-private', 1, true);
        $this->get(route('conditions.show', $h))->assertNotFound();
        $this->actingAs($this->owner())->get(route('verifier.queue'))->assertOk()->assertSee('hotfix-private');
        $this->get(route('conditions.show', $h))->assertOk();
        $this->post(route('hazards.verify', $h), ['decision' => 'rejected'])->assertRedirect();
        $this->get(route('verifier.queue'))->assertDontSee('hotfix-private');
    }

    public function test_school_admin_cannot_verify_another_school(): void
    {
        $h = $this->hazard('hotfix-other-school');
        $b = School::where('name', 'Sekolah B — Demo Simulasi')->firstOrFail();
        $h->reports()->update(['school_id' => $b->id]);
        $admin = User::where('email', 'admin_a@ruteaman.test')->firstOrFail();
        $this->actingAs($admin)->get(route('verifier.queue'))->assertOk()->assertDontSee('hotfix-other-school');
        $this->post(route('hazards.verify', $h), ['decision' => 'verified'])->assertForbidden();
    }

    public function test_unverified_active_report_is_in_route_but_stale_report_is_not(): void
    {
        $h = $this->hazard('hotfix-route-public');
        $old = $this->hazard('hotfix-route-stale', 1000000);
        config(['ruteaman.route_buffer_meters' => 50]);
        $routes = app(RouteComparisonService::class)->saved($this->school()->id)['routes'];
        $slugs = array_column($routes[0]['hazards'], 'public_slug');
        $this->assertContains($h->public_slug, $slugs);
        $this->assertNotContains($old->public_slug, $slugs);
        $this->assertSame('new', $h->fresh()->status->value);
    }

    public function test_community_report_appears_in_owner_queue_and_can_be_verified(): void
    {
        if (! class_exists('App\\Community\\RegionAccess')) {
            $this->markTestSkipped('Community build only');
        }$h = $this->hazard('hotfix-community', 1, true, true);
        $this->actingAs($this->owner())->get(route('verifier.queue'))->assertOk()->assertSee('hotfix-community')->assertSee('Laporan warga');
        $this->get(route('conditions.show', $h))->assertOk();
        $this->withSession(['admin.reauth_at' => time(), 'admin.last_seen' => time()])->post(route('community.moderate', $h), ['action' => 'verify', 'reason' => 'Bukti telah diperiksa'])->assertRedirect();
        $this->assertSame('verified',$h->fresh()->status->value);
        $this->assertFalse($h->fresh()->requires_moderation);
    }
}
