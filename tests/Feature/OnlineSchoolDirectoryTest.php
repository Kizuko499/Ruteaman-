<?php

namespace Tests\Feature;

use App\Services\DapodikSchoolDirectory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnlineSchoolDirectoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.dapodik.base_url' => 'https://dapo.kemendikdasmen.go.id', 'services.dapodik.fallback_url' => 'https://sekolah.devapi.id', 'services.dapodik.region_url' => 'https://wilayah.id/api', 'services.dapodik.token' => 'SyntheticPublicApiFixture', 'services.dapodik.verify_ssl' => true]);
        Cache::flush();
        Http::preventStrayRequests();
    }

    private function school(string $name, string $npsn = '10101010'): array
    {
        return ['nama' => $name, 'npsn' => $npsn, 'alamat' => ['nama_provinsi' => 'Jawa Tengah']];
    }

    public function test_eight_letter_name_is_not_misclassified_as_npsn(): void
    {
        Http::fake(['dapo.kemendikdasmen.go.id/*' => Http::response(['data' => []]), 'sekolah.devapi.id/*' => Http::response(['data' => [$this->school('SDN SUKAMAJU')]])]);
        $items = app(DapodikSchoolDirectory::class)->searchNationwide('Sukamaju');
        $this->assertCount(1, $items);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sekolah.devapi.id') && $request['nama'] === 'Sukamaju' && ! isset($request['npsn']));
    }

    public function test_unrelated_primary_results_do_not_suppress_fallback_matches(): void
    {
        Http::fake(['dapo.kemendikdasmen.go.id/*' => Http::response(['data' => [$this->school('Different School')]]), 'sekolah.devapi.id/*' => Http::response(['data' => [$this->school('MAN 1 REMBANG', '20363196')]])]);
        $this->assertSame('MAN 1 REMBANG', app(DapodikSchoolDirectory::class)->searchNationwide('MAN 1 REMBANG')[0]['name']);
    }

    public function test_both_online_sources_are_merged_and_deduplicated(): void
    {
        Http::fake(['dapo.kemendikdasmen.go.id/*' => Http::response(['data' => [$this->school('SD NEGERI REMBANG')]]), 'sekolah.devapi.id/*' => Http::response(['data' => [$this->school('SD NEGERI REMBANG'), $this->school('MAN REMBANG', '20202020')]])]);
        $this->assertCount(2, app(DapodikSchoolDirectory::class)->searchNationwide('REMBANG'));
    }

    public function test_sman_abbreviation_retries_expanded_name(): void
    {
        Http::fake(fn ($request) => Http::response(['data' => ($request['nama'] ?? '') === 'SMA NEGERI 1 TEST' ? [$this->school('SMA NEGERI 1 TEST')] : []]));
        $this->assertSame('SMA NEGERI 1 TEST', app(DapodikSchoolDirectory::class)->searchNationwide('SMAN 1 TEST')[0]['name']);
    }

    public function test_npsn_lookup_only_returns_exact_id_and_uses_npsn_parameter(): void
    {
        Http::fake(['dapo.kemendikdasmen.go.id/*' => Http::response(['data' => [$this->school('Wrong School', '20202020')]]), 'sekolah.devapi.id/*' => Http::response(['data' => [$this->school('Selected School')]])]);
        $items = app(DapodikSchoolDirectory::class)->searchNationwide('10101010');
        $this->assertCount(1, $items);
        $this->assertSame('10101010', $items[0]['npsn']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sekolah.devapi.id') && $request['npsn'] === '10101010');
    }

    public function test_public_http_user_agent_and_positive_cache(): void
    {
        Http::fake(['*' => Http::response(['data' => [$this->school('SD SUKAMAJU')]])]);
        $service = app(DapodikSchoolDirectory::class);
        $this->assertCount(1, $service->searchNationwide('Sukamaju'));
        $this->assertCount(1, $service->searchNationwide('Sukamaju'));
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', 'Mozilla/5.0 (compatible; RuteAmanSchoolDirectory/1.0)'));
    }

    public function test_empty_results_are_not_cached_as_success(): void
    {
        Http::fake(['*' => Http::response(['data' => []])]);
        $service = app(DapodikSchoolDirectory::class);
        $this->assertSame([], $service->searchNationwide('No matching school'));
        $this->assertSame([], $service->searchNationwide('No matching school'));
        Http::assertSentCount(4);
    }

    public function test_both_sources_unavailable_throw_instead_of_fake_empty_result(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Unavailable'], 503)]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Kedua sumber sekolah online');
        app(DapodikSchoolDirectory::class)->searchNationwide('Sukamaju');
    }

    public function test_older_missing_source_configuration_uses_known_online_defaults(): void
    {
        config(['services.dapodik.base_url' => null, 'services.dapodik.fallback_url' => null]);
        Http::fake(['*' => Http::response(['data' => [$this->school('SD SUKAMAJU')]])]);
        $this->assertCount(1, app(DapodikSchoolDirectory::class)->searchNationwide('Sukamaju'));
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://sekolah.devapi.id/'));
    }
}
