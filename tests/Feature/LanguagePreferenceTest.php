<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\HazardType;
use App\Models\School;
use App\Models\User;
use App\Services\ConditionFeedService;
use App\Services\DapodikSchoolDirectory;
use App\Services\SpatialSql;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LanguagePreferenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Requires the isolated spatial test database.');
        }
        parent::setUp();
    }

    private function member(string $locale = 'id'): User
    {
        return User::create(['display_name' => 'Masuk', 'email' => 'language-fixture@test.invalid', 'password' => 'FixturePassword123!', 'role' => 'student', 'active' => true, 'locale' => $locale, 'appearance' => 'light', 'parental_consent_at' => now()]);
    }

    public function test_guest_can_choose_english_without_an_account(): void
    {
        $this->get('/login')->assertOk()->assertSee('lang="id"', false)->assertSee('Bahasa Indonesia');
        $this->post('/language', ['locale' => 'en', 'return_to' => '/login'])->assertRedirect('/login')->assertCookie('ruteaman_locale', 'en');
        $this->withCookie('ruteaman_locale', 'en')->get('/login')->assertOk()->assertHeader('Content-Language', 'en')->assertSee('lang="en"', false)->assertSee('Sign in')->assertSee('Remember me')->assertDontSee('<p>Gunakan akun sekolahmu.</p>', false);
        $this->assertGuest();
    }

    public function test_guest_can_switch_back_to_indonesian(): void
    {
        $this->withCookie('ruteaman_locale', 'en')->post('/language', ['locale' => 'id', 'return_to' => '/help'])->assertCookie('ruteaman_locale', 'id');
        $this->withCookie('ruteaman_locale', 'id')->get('/help')->assertHeader('Content-Language', 'id')->assertSee('Bantuan dan Kontak');
    }

    public function test_unknown_locales_are_rejected_and_bad_cookies_fall_back(): void
    {
        $this->postJson('/language', ['locale' => 'fr'])->assertUnprocessable();
        $this->withCookie('ruteaman_locale', 'fr')->get('/login')->assertHeader('Content-Language', 'id');
    }

    public function test_redirects_cannot_leave_the_application(): void
    {
        foreach (['https://evil.invalid', '//evil.invalid', '/\\evil.invalid', '/%2f%2fevil.invalid', '/%5cevil.invalid', "/\nevil.invalid"] as $target) {
            $this->post('/language', ['locale' => 'en', 'return_to' => $target])->assertRedirect('/');
        }
        $this->post('/language', ['locale' => 'en', 'return_to' => '/login?session_expired=1'])->assertRedirect('/login?session_expired=1');
    }

    public function test_signed_in_choice_is_saved_and_overrides_browser_cookie(): void
    {
        $user = $this->member();
        $this->actingAs($user)->post('/language', ['locale' => 'en', 'return_to' => '/account/settings'])->assertRedirect('/account/settings');
        $this->assertSame('en', $user->fresh()->locale);
        $this->assertSame('light', $user->fresh()->appearance);
        $this->withCookie('ruteaman_locale', 'id')->get('/account/settings')->assertOk()->assertHeader('Content-Language', 'en')->assertSee('Save language');
    }

    public function test_account_preferences_support_both_languages_without_resetting_appearance(): void
    {
        $user = $this->member();
        $this->actingAs($user)->patchJson('/account/preferences', ['locale' => 'en', 'appearance' => 'system'])->assertOk()->assertJsonPath('account.locale', 'en');
        $this->get('/account/settings')->assertSee('name="locale" value="en"', false);
        $this->patchJson('/account/preferences', ['locale' => 'en', 'appearance' => 'light'])->assertOk();
        $this->assertSame('en', $user->fresh()->locale);
        $this->patchJson('/account/preferences', ['locale' => 'id', 'appearance' => 'light'])->assertOk();
        $this->assertSame('id', $user->fresh()->locale);
    }

    public function test_identity_and_machine_values_are_not_translated(): void
    {
        $user = $this->member('en');
        $this->actingAs($user)->get('/account/profile')->assertOk()->assertSee('data-own-name>Masuk', false);
        $this->getJson('/api/account')->assertOk()->assertJsonPath('data.display_name', 'Masuk')->assertJsonPath('data.role', 'student');
        $this->assertSame('Masuk', $user->fresh()->display_name);
    }

    public function test_english_catalog_keeps_report_verification_meaning(): void
    {
        app()->setLocale('en');
        $this->assertSame('Unverified', __('Belum terverifikasi'));
        $this->assertSame('Verified', __('Terverifikasi'));
        $this->assertSame('Primary Administrator', Role::Owner->label());
        $this->assertSame(1, Role::Owner->tier());
        app()->setLocale('id');
    }

    private function school(): School
    {
        $id = DB::table('schools')->insertGetId(['name' => 'Sekolah Bahasa QA', 'status' => 'active', 'timezone' => 'Asia/Jakarta', 'location' => SpatialSql::point(110, -7), 'catchment' => SpatialSql::expression('POLYGON((109.98 -7.02,110.02 -7.02,110.02 -6.98,109.98 -6.98,109.98 -7.02))')]);

        return School::findOrFail($id);
    }

    public function test_registration_retains_guest_language_without_changing_school_data(): void
    {
        $school = $this->school();
        $this->mock(DapodikSchoolDirectory::class, function ($mock) use ($school) {
            $mock->shouldReceive('import')->once()->with('20363192', '33', '33.17', '33.17.14')->andReturn($school);
        });
        $this->withCookie('ruteaman_locale', 'en')->post('/register', ['display_name' => 'Language Guest QA', 'email' => 'language-register@test.invalid', 'password' => 'FixturePassword123!', 'password_confirmation' => 'FixturePassword123!', 'school_npsn' => '20363192', 'province_code' => '33', 'regency_code' => '33.17', 'district_code' => '33.17.14', 'parental_consent' => '1'])->assertRedirect();
        $user = User::where('email', 'language-register@test.invalid')->firstOrFail();
        $this->assertSame('en', $user->locale);
        $this->assertSame('Sekolah Bahasa QA', $school->fresh()->name);
        $this->assertAuthenticatedAs($user);
    }

    public function test_condition_feed_cache_is_separated_by_language_and_location_is_unchanged(): void
    {
        config(['cache.default' => 'array']);
        $school = $this->school();
        $type = HazardType::where('code', 'pothole')->firstOrFail();
        DB::table('hazards')->insert(['type_id' => $type->id, 'location' => SpatialSql::point(110, -7), 'severity' => 1, 'status' => 'verified', 'location_label' => 'Masuk', 'public_slug' => 'language-feed-fixture', 'first_reported_at' => now(), 'last_confirmed_at' => now(), 'published_at' => now(), 'requires_moderation' => false, 'publication_control' => 'normal', 'created_at' => now(), 'updated_at' => now()]);
        $feed = app(ConditionFeedService::class);
        app()->setLocale('en');
        $english = $feed->feed($school, ['kategori' => 'pothole']);
        $this->assertSame('Pothole', $english['items'][0]['type_label']);
        $this->assertSame('Masuk', $english['items'][0]['location_label']);
        $this->assertSame('Verified', $english['items'][0]['status_label']);
        app()->setLocale('id');
        $indonesian = $feed->feed($school, ['kategori' => 'pothole']);
        $this->assertSame('Jalan berlubang', $indonesian['items'][0]['type_label']);
        $this->assertSame('Masuk', $indonesian['items'][0]['location_label']);
    }

    public function test_guest_language_choice_is_directly_below_dashboard_in_account_menu(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $form = $xpath->query('//dialog[@id="account-menu"]//div[contains(@class,"account-guest")]/form[contains(@class,"language-switcher")]')->item(0);
        $this->assertNotNull($form);
        $previous = $xpath->query('preceding-sibling::*[1]', $form)->item(0);
        $this->assertSame('a', $previous->nodeName);
        $this->assertStringContainsString('Dashboard', $previous->textContent);
        $this->assertSame(route('home'), $previous->getAttribute('href'));
        $this->assertSame(0, $xpath->query('/html/body/form[contains(@class,"language-switcher")]')->length);
        $this->assertSame(6, $xpath->query('.//select[@name="locale"]/option', $form)->length);
    }

    public function test_signed_in_language_toolbar_remains_outside_guest_panel(): void
    {
        $this->actingAs($this->member())->get('/account/settings')->assertOk()->assertSee('class="language-switcher"', false)->assertDontSee('class="account-guest"', false)->assertDontSee('class="guest-language-fallback"', false);
    }
}
