<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
class UserGuideTest extends TestCase {
 use DatabaseTransactions;
 protected function setUp():void {
  if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Dedicated spatial demo database required.');
  parent::setUp();$this->seed(\Database\Seeders\DemoSeeder::class);
 }
 public function test_guest_must_login_to_read_guide():void {$this->get('/account/guide')->assertRedirect(route('login'));$this->get('/login')->assertOk()->assertDontSee('Panduan Pengguna');}
 public function test_public_guide_is_readable_without_login_and_linked_at_bottom_of_home():void {
  $this->get('/panduan')->assertOk()->assertHeader('Cache-Control','no-store, private')->assertSee('Panduan ini bisa dibaca tanpa login')->assertSee('Kirim laporan tanpa akun')->assertSee('Kembali ke beranda')->assertDontSee('id="verifikasi"',false)->assertDontSee('id="sekolah"',false)->assertDontSee('id="admin"',false)->assertDontSee('href="'.route('account.profile').'"',false);
  $this->get('/')->assertOk()->assertSee('id="panduan-pengguna"',false)->assertSee('href="'.route('guide.public').'"',false);
 }
 public function test_public_guide_remains_general_even_for_admin():void {
  $u=User::where('email','admin_a@ruteaman.test')->firstOrFail();$this->actingAs($u)->get('/panduan')->assertOk()->assertDontSee('id="verifikasi"',false)->assertDontSee('id="admin"',false);
 }
 public function test_member_has_guide_link_and_private_basic_content():void {
  $u=User::where('email','siswa_1@ruteaman.test')->firstOrFail();$this->actingAs($u);
  $this->get('/account/profile')->assertOk()->assertSee('Buka panduan pengguna')->assertSee(route('account.guide'),false);
  $this->get('/account/guide')->assertOk()->assertHeader('Cache-Control','no-store, private')->assertSee('Panduan Pengguna')->assertSee('Kirim laporan tanpa akun')->assertSee('data simulasi')->assertDontSee('Pengelola: periksa antrean laporan')->assertDontSee('Administrator: kelola sistem');
 }
 public function test_admin_sections_match_permissions():void {
  $u=User::where('email','admin_a@ruteaman.test')->firstOrFail();$this->actingAs($u)->get('/account/guide')->assertOk()->assertSee('Pengelola: periksa antrean laporan')->assertSee('Pengelola: tindak lanjut sekolah')->assertSee('Administrator: kelola sistem');
  $u->forceFill(['permissions'=>[]])->save();$this->actingAs($u->fresh())->get('/account/guide')->assertOk()->assertDontSee('Pengelola: periksa antrean laporan')->assertDontSee('Pengelola: tindak lanjut sekolah');
 }
 public function test_inactive_account_cannot_read_guide():void {$u=User::where('email','siswa_1@ruteaman.test')->firstOrFail();$u->update(['active'=>false]);$this->actingAs($u)->get('/account/guide')->assertRedirect(route('login'));}
 public function test_all_sections_have_existing_screenshots():void {
  foreach(config('user_guide.sections') as $s){$this->assertNotEmpty($s['steps']);$this->assertFileExists(public_path('images/user-guide/'.$s['image']));foreach($s['extra_images']??[] as $image)$this->assertFileExists(public_path('images/user-guide/'.$image));}
 }
}
