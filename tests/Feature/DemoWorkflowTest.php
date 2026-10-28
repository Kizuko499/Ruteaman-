<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Escalation;
use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\School;
use App\Models\User;
use App\Services\PhotoSanitizer;
use App\Services\SpatialSql;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Gunakan database spatial khusus dan RUTEAMAN_SPATIAL_TESTS=1 (lihat DEMO_SETUP).');
        }
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('q', 32))]);
        $this->seed(DemoSeeder::class);
    }

    public function test_full_demo_flow_and_privacy(): void
    {
        $a = School::where('name', 'Sekolah A — Demo Simulasi')->firstOrFail();
        $b = School::where('name', 'Sekolah B — Demo Simulasi')->firstOrFail();
        $one = User::where('email', 'siswa_1@ruteaman.test')->firstOrFail();
        $two = User::where('email', 'siswa_2@ruteaman.test')->firstOrFail();
        $three = User::where('email', 'siswa_3@ruteaman.test')->firstOrFail();
        $admin = User::where('email', 'admin_a@ruteaman.test')->firstOrFail();
        $floodType = HazardType::where('code', 'flood')->value('id');
        $board = '/api/routes/saved?school='.$a->id;
        $this->getJson($board)->assertOk()->assertJsonPath('routes.0.load.value', 22)->assertJsonPath('routes.1.load.label_code', 'no_report');
        $this->get('/map?school='.$a->id)->assertOk()->assertSee('rute tersimpan');
        $data = ['type_id' => $floodType, 'latitude' => -6.1768, 'longitude' => 106.8250, 'description' => 'Genangan simulasi', 'photo' => UploadedFile::fake()->image('genangan.jpg')];
        $this->actingAs($one)->post('/hazards', $data)->assertRedirect();
        $flood = Hazard::where('type_id', $floodType)->latest('id')->firstOrFail();
        $this->assertSame('new', $flood->status->value);
        $this->assertEqualsWithDelta(.3, $flood->confidence, .0001);
        $this->getJson($board)->assertJsonPath('routes.0.load.value', 37); // Direct public report contributes with its existing confidence, never verified.
        $duplicate = '/api/hazards/duplicate?'.http_build_query(['type_id' => $floodType, 'latitude' => -6.1768, 'longitude' => 106.8250]);
        $this->actingAs($two)->getJson($duplicate)->assertOk()->assertJsonPath('duplicate.confirm_url', route('hazards.confirm', $flood));
        $this->post('/hazards', ['type_id' => $floodType, 'latitude' => -6.1768, 'longitude' => 106.8250])->assertRedirect(route('hazards.public', $flood));
        $this->assertSame(1, $flood->reports()->count());
        $this->assertSame(0, $flood->confirmations()->count());
        $this->actingAs($one)->post(route('hazards.confirm', $flood), ['vote' => 'still_there'])->assertSessionHasErrors('vote');
        $this->actingAs($two)->post(route('hazards.confirm', $flood), ['vote' => 'still_there'])->assertRedirect();
        $confirmedAt = $flood->fresh()->last_confirmed_at->toIso8601String();
        $this->travel(1)->minutes();
        $this->post(route('hazards.confirm', $flood), ['vote' => 'still_there'])->assertRedirect();
        $this->assertSame($confirmedAt, $flood->fresh()->last_confirmed_at->toIso8601String());
        $this->assertSame('new', $flood->fresh()->status->value);
        $feed = $this->actingAs($three)->getJson('/api/feed/hazards.geojson?school='.$b->id)->assertOk()->json();
        $this->assertContains($flood->public_slug, array_column(array_column($feed['features'], 'properties'), 'id'));
        $this->post(route('hazards.confirm', $flood), ['vote' => 'still_there'])->assertRedirect();
        $this->assertSame('confirmed', $flood->fresh()->status->value);
        $this->assertEqualsWithDelta(.5, $flood->fresh()->confidence, .0001);
        $this->getJson($board)->assertJsonPath('routes.0.load.value', 47);
        $this->get('/verifier/queue')->assertForbidden();
        $this->actingAs($admin)->get('/verifier/queue')->assertOk();
        $this->post(route('hazards.verify', $flood), ['decision' => 'verified', 'note' => 'Bukti diperiksa, simulasi.'])->assertRedirect();
        $verificationCount = $flood->verifications()->count();
        $this->post(route('hazards.verify', $flood), ['decision' => 'verified', 'note' => 'Bukti diperiksa, simulasi.'])->assertRedirect();
        $this->assertSame($verificationCount, $flood->verifications()->count());
        $this->assertEqualsWithDelta(.9, $flood->fresh()->confidence, .0001);
        $this->getJson($board)->assertJsonPath('routes.0.load.value', 67)->assertJsonPath('routes.1.load.label_code', 'no_report');
        $this->get(route('conditions.show', $flood))->assertOk()->assertSee('Kenapa tingkatnya begini?')->assertSee('SIMULASI DEMO');
        $this->assertTrue(AuditLog::where('entity_id', $flood->id)->where('action', 'verification.verified')->exists());
        $this->post(route('hazards.escalate', $flood), ['channel' => 'Kanal resmi simulasi'])->assertRedirect(route('hazards.package', $flood));
        $this->get(route('hazards.package', $flood))->assertOk()->assertSee('Salin pesan WhatsApp');
        $escalation = Escalation::where('hazard_id', $flood->id)->firstOrFail();
        $this->patch(route('escalations.update', $escalation), ['channel' => 'Kanal resmi simulasi', 'response_status' => 'forwarded'])->assertRedirect();
        $this->assertNotNull($escalation->fresh()->forwarded_at);
        $this->get('/school/dashboard')->assertOk()->assertSee('Diteruskan')->assertSee('Audit log');
        $this->patch(route('escalations.update', $escalation), ['channel' => 'test', 'response_status' => 'anything'])->assertSessionHasErrors('response_status');
        $json = $this->getJson('/api/feed/hazards.geojson')->assertOk()->getContent();
        foreach (['reporter_id', 'user_id', 'verifier_id', 'email', 'display_name', 'ruteaman.test'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
        // Age exclusion is immediate, even if no cron process is running.
        $flood->update(['first_reported_at' => now()->subMinutes(600), 'last_confirmed_at' => now()->subMinutes(600)]);
        $this->getJson($board)->assertJsonPath('routes.0.load.value', 22);
        $json = $this->getJson('/api/feed/hazards.geojson')->assertOk()->getContent();
        $this->assertStringNotContainsString($flood->public_slug, $json);
        $this->travelBack();
    }

    public function test_catchment_scope_and_photo_sanitizer(): void
    {
        $admin = User::where('email', 'admin_a@ruteaman.test')->firstOrFail();
        $hazard = Hazard::where('public_slug', 'demo-lubang-jl-mawar')->firstOrFail();
        $schoolId = DB::table('schools')->insertGetId(['name' => 'Sekolah di luar area', 'timezone' => 'Asia/Jakarta', 'location' => SpatialSql::point(110, -7), 'catchment' => SpatialSql::expression('POLYGON((109.99 -7.01,110.01 -7.01,110.01 -6.99,109.99 -6.99,109.99 -7.01))')]);
        $admin->update(['school_id' => $schoolId]);
        $this->actingAs($admin)->post(route('hazards.verify', $hazard), ['decision' => 'verified'])->assertForbidden();
        $this->get(route('hazards.package', $hazard))->assertForbidden();
        $item = Escalation::create(['hazard_id' => $hazard->id, 'channel' => 'test', 'response_status' => 'draft']);
        $this->patch(route('escalations.update', $item), ['channel' => 'test', 'response_status' => 'forwarded'])->assertForbidden();
        $path = app(PhotoSanitizer::class)->store(UploadedFile::fake()->image('foto.jpg'));
        $bytes = Storage::disk('hazard_photos')->get($path);
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($bytes)[2]);
        $this->assertStringNotContainsString('Exif', $bytes);
    }
}
