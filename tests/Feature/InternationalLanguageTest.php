<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LanguagePreferences;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InternationalLanguageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        if (getenv('RUTEAMAN_SPATIAL_TESTS') !== '1') {
            $this->markTestSkipped('Requires the isolated test database.');
        }
        parent::setUp();
    }

    public function test_catalog_uses_native_names_and_supported_locale_codes(): void
    {
        $this->assertSame(['id', 'en', 'ar', 'zh-CN', 'ja', 'ko'], LanguagePreferences::supported());
        $this->assertSame('rtl', LanguagePreferences::options()['ar']['direction']);
        $this->assertSame('日本語', LanguagePreferences::options()['ja']['label']);
        $this->assertFalse(LanguagePreferences::valid('../en'));
        $this->assertFalse(LanguagePreferences::valid(['en']));
    }

    public function test_each_language_can_be_used_by_guests_without_external_api_calls(): void
    {
        Http::preventStrayRequests();
        foreach (['ar' => 'تسجيل الدخول', 'zh-CN' => '登录', 'ja' => 'ログイン', 'ko' => '로그인'] as $locale => $signIn) {
            $direction = $locale === 'ar' ? 'rtl' : 'ltr';
            $this->withCookie('ruteaman_locale', $locale)->get('/login')->assertOk()->assertHeader('Content-Language', $locale)->assertSee('lang="'.$locale.'" dir="'.$direction.'"', false)->assertSee($signIn)->assertSee('Rute')->assertSee('Aman');
            $this->post('/language', ['locale' => $locale, 'return_to' => '/login'])->assertRedirect('/login')->assertCookie('ruteaman_locale', $locale);
        }
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_account_preferences_save_all_supported_languages_and_preserve_identity(): void
    {
        $user = User::create(['display_name' => 'Masuk', 'email' => 'international-fixture@test.invalid', 'password' => 'FixturePassword123!', 'role' => 'student', 'active' => true, 'locale' => 'id', 'appearance' => 'light', 'parental_consent_at' => now()]);
        $this->actingAs($user);
        foreach (['ar', 'zh-CN', 'ja', 'ko'] as $locale) {
            $this->patchJson('/account/preferences', ['locale' => $locale, 'appearance' => 'light'])->assertOk()->assertJsonPath('account.locale', $locale)->assertJsonPath('account.display_name', 'Masuk');
            $this->assertSame($locale, $user->fresh()->locale);
            $this->get('/account/settings')->assertHeader('Content-Language', $locale)->assertSee('name="locale" value="'.$locale.'"', false);
            $this->assertSame('student', $user->fresh()->role->value);
        }
        $this->patchJson('/account/preferences', ['locale' => 'xx', 'appearance' => 'light'])->assertUnprocessable();
        $this->assertSame('ko', $user->fresh()->locale);
    }

    public function test_dictionaries_have_complete_keys_and_preserve_placeholders_and_brand(): void
    {
        $english = json_decode(file_get_contents(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach (['ar', 'zh-CN', 'ja', 'ko'] as $locale) {
            $dictionary = json_decode(file_get_contents(lang_path($locale.'.json')), true, 512, JSON_THROW_ON_ERROR);
            $this->assertEmpty(array_diff_key($english, $dictionary));
            foreach ($english as $key => $value) {
                preg_match_all('/:v\d+:/', $key, $original);
                preg_match_all('/:v\d+:/', $dictionary[$key], $translated);
                sort($original[0]);
                sort($translated[0]);
                $this->assertSame($original[0], $translated[0], $locale.' '.$key);
                if (str_contains($value, 'RuteAman')) {
                    $this->assertStringContainsString('RuteAman', $dictionary[$key]);
                }
                if (str_contains($value, 'NPSN')) {
                    $this->assertStringContainsString('NPSN', $dictionary[$key]);
                }
                if (str_contains($key, 'HAPUS')) {
                    $this->assertStringContainsString('HAPUS', $dictionary[$key]);
                }
            }
            $this->assertNotSame($dictionary['Belum terverifikasi'], $dictionary['Terverifikasi']);
            $this->assertNotSame($dictionary['Dikonfirmasi Komunitas'], $dictionary['Terverifikasi']);
        }
    }

    public function test_chinese_dates_and_arabic_direction_use_correct_locale_mapping(): void
    {
        app()->setLocale('zh-CN');
        $this->assertSame('zh_CN', LanguagePreferences::carbonLocale());
        $this->assertSame('ltr', LanguagePreferences::direction());
        app()->setLocale('ar');
        $this->assertSame('ar', LanguagePreferences::carbonLocale());
        $this->assertSame('rtl', LanguagePreferences::direction());
        app()->setLocale('id');
    }

    public function test_native_validation_preserves_field_placeholders(): void
    {
        foreach (['ar', 'zh-CN', 'ja', 'ko'] as $locale) {
            $this->withCookie('ruteaman_locale', $locale)->postJson('/language', ['locale' => 'xx'])->assertUnprocessable();
            $values = require lang_path($locale.'/validation.php');
            $this->assertStringContainsString(':attribute', $values['required']);
            $this->assertStringNotContainsString('must', $values['required']);
            app()->setLocale($locale);
            $this->assertSame(__('Bahasa'), $values['attributes']['locale']);
        }
    }

    public function test_icon_geometry_is_never_translated(): void
    {
        $expected = 'M20 21v-2a7 7 0 0 0-14 0v2 M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0';
        foreach (['id', 'en', 'ar', 'zh-CN', 'ja', 'ko'] as $locale) {
            app()->setLocale($locale);
            $html = Blade::render('<x-account-icon name="user" />');
            $this->assertStringContainsString('d="'.$expected.'"', $html);
        }
        app()->setLocale('id');
    }
}
