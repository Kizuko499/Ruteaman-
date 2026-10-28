<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Hazard;
use App\Models\HazardType;
use App\Models\SavedRoute;
use App\Models\School;
use App\Models\User;
use App\Models\Verification;
use App\Models\WeatherSnapshot;
use App\Services\HazardEvidenceService;
use App\Services\SpatialSql;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('DemoSeeder hanya boleh dijalankan pada APP_ENV=local/testing.');
        }
        // Idempotent for this demo fixture only; NEVER clears unrelated reports or schools.
        DB::transaction(function () {
            $definitions = [
                ['Sekolah A — Demo Simulasi', 106.8272, -6.1754, 'POLYGON((106.80 -6.20,106.85 -6.20,106.85 -6.15,106.80 -6.15,106.80 -6.20))', 'DEMO-A'],
                ['Sekolah B — Demo Simulasi', 106.8370, -6.1800, 'POLYGON((106.81 -6.20,106.86 -6.20,106.86 -6.16,106.81 -6.16,106.81 -6.20))', 'DEMO-B'],
            ];
            $schools = [];
            foreach ($definitions as [$name,$lng,$lat,$polygon,$area]) {
                $school = School::where('name', $name)->first();
                if (! $school) {
                    $point = SpatialSql::wkt(DB::getPdo()->quote("POINT($lng $lat)"));
                    $poly = SpatialSql::wkt();
                    $id = DB::table('schools')->insertGetId(['name' => $name, 'timezone' => 'Asia/Jakarta', 'bmkg_area_code' => $area, 'location' => DB::raw($point), 'catchment' => DB::raw(str_replace('?', "'".$polygon."'", $poly))]);
                    $school = School::findOrFail($id);
                }$schools[] = $school;
            }
            $users = [];
            foreach ([
                ['siswa_1', 'siswa_1@ruteaman.test', 0, Role::Student], ['siswa_2', 'siswa_2@ruteaman.test', 0, Role::Student],
                ['siswa_3', 'siswa_3@ruteaman.test', 1, Role::Student], ['admin_A', 'admin_a@ruteaman.test', 0, Role::SchoolAdmin],
            ] as [$name,$email,$index,$role]) {
                $users[] = User::updateOrCreate(['email' => $email], ['display_name' => $name, 'school_id' => $schools[$index]->id, 'password' => 'RuteAman123!', 'role' => $role, 'permissions' => config('ruteaman_roles.ceilings.'.($role instanceof Role ? $role->value : $role), []), 'status' => 'active', 'parental_consent_at' => now(), 'active' => true]);
            }
            $types = [['flood', 'Genangan / banjir', 3, 180, true], ['pothole', 'Jalan berlubang', 2, 43200, false]];
            foreach ($types as [$code,$label,$weight,$ttl,$rain]) {
                HazardType::updateOrCreate(['code' => $code], ['label' => $label, 'severity_weight' => $weight, 'ttl_minutes' => $ttl, 'rain_sensitive' => $rain, 'night_only' => false]);
            }
            $geometries = [
                ['Rute A', 1800, ['type' => 'LineString', 'coordinates' => [[106.819, -6.181], [106.825, -6.181], [106.825, -6.1754], [106.8272, -6.1754]]]],
                ['Rute B', 2100, ['type' => 'LineString', 'coordinates' => [[106.819, -6.181], [106.819, -6.172], [106.8272, -6.172], [106.8272, -6.1754]]]],
            ];
            foreach ($geometries as [$name,$distance,$geometry]) {
                SavedRoute::updateOrCreate(['school_id' => $schools[0]->id, 'name' => $name], ['geometry' => $geometry, 'distance_m' => $distance, 'duration_s' => null, 'source' => 'Simulasi demo — jarak skenario, belum audit lapangan', 'is_demo' => true]);
            }
            $slug = 'demo-lubang-jl-mawar';
            $old = Hazard::where('public_slug', $slug)->first();
            if ($old) {/* Immutable audit history is retained during a demo reset. */ $old->delete();
            }
            $location = SpatialSql::wkt(DB::getPdo()->quote('POINT(106.825 -6.178)'));
            $id = DB::table('hazards')->insertGetId(['type_id' => HazardType::where('code', 'pothole')->value('id'), 'location' => DB::raw($location), 'is_demo' => true, 'severity' => 2, 'status' => 'verified', 'location_label' => 'Jl. Mawar (simulasi)', 'confidence' => 1, 'first_reported_at' => now()->subDays(4), 'last_confirmed_at' => now()->subDays(3), 'public_slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
            Storage::disk('hazard_photos')->makeDirectory('hazards');
            Storage::disk('hazard_photos')->put('hazards/demo-lubang.webp', file_get_contents(public_path('images/jalan-berlubang.webp')));
            $hazard = Hazard::findOrFail($id);
            // Historical fixture predates duplicate detection; reporters are separate from both confirmers.
            $legacy = User::updateOrCreate(['email' => 'audit_demo@ruteaman.test'], ['display_name' => 'Pelapor audit simulasi', 'school_id' => $schools[0]->id, 'password' => 'RuteAman123!', 'role' => Role::Student, 'parental_consent_at' => now(), 'active' => false]);
            foreach ([$users[0], $legacy] as $reporter) {
                $hazard->reports()->create(['reporter_id' => $reporter->id, 'description' => 'SIMULASI: jalan berlubang, Jl. Mawar. Foto ilustrasi; bukan bukti audit lokasi ini.', 'photo_path' => 'hazards/demo-lubang.webp', 'observed_at' => now()->subDays(3)]);
            }
            foreach ([$users[1], $users[2]] as $u) {
                $hazard->confirmations()->create(['user_id' => $u->id, 'vote' => 'still_there', 'created_at' => now()->subDays(3)]);
            }
            Verification::create(['hazard_id' => $id, 'verifier_id' => $users[3]->id, 'decision' => 'verified', 'note' => 'Simulasi verifikasi, bukan audit nyata.', 'created_at' => now()->subDays(3)]);
            $evidence = app(HazardEvidenceService::class);
            $evidence->refresh($hazard);
            $evidence->audit($users[3]->id, 'verification.verified', $id, ['demo' => true]);
            foreach (['DEMO-A', 'DEMO-B'] as $area) {
                WeatherSnapshot::where('area_code', $area)->delete();
                WeatherSnapshot::create(['area_code' => $area, 'condition' => 'Hujan simulasi manual', 'rain_flag' => true, 'source' => 'seed demo manual, bukan BMKG', 'fetched_at' => now(), 'valid_until' => now()->addHours(8)]);
            }
        });
    }
}
