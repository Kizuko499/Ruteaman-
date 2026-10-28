<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
class RateLimitIsolationTest extends TestCase {
 protected function setUp():void {
  parent::setUp();config(['app.key'=>'base64:'.base64_encode(str_repeat('r',32)),'cache.default'=>'array']);
  Cache::put('school.directory.online-provinces.v1',[['code'=>'33','name'=>'Jawa Tengah']]);
 }
 public function test_directory_requests_do_not_consume_registration_budget():void {
  for($i=0;$i<12;$i++)$this->getJson('/api/school-directory/provinces')->assertOk();
  $this->post('/register',[])->assertRedirect()->assertSessionHasErrors('display_name')->assertSessionDoesntHaveErrors('rate_limit');
 }
 public function test_login_and_register_do_not_share_counters():void {
  for($i=0;$i<6;$i++)$this->post('/login',[])->assertSessionHasErrors('email');
  $this->post('/register',[])->assertSessionHasErrors('display_name')->assertSessionDoesntHaveErrors('rate_limit');
 }
 public function test_real_registration_limit_preserves_form_but_not_passwords():void {
  for($i=0;$i<6;$i++)$this->post('/register',[])->assertSessionHasErrors('display_name');
  $this->post('/register',['display_name'=>'Afik','school_npsn'=>'20363192','password'=>'source-fixture-password','password_confirmation'=>'source-fixture-password'])
   ->assertRedirect(route('register'))->assertHeader('Retry-After')->assertSessionHasErrors('rate_limit')
   ->assertSessionHasInput('display_name','Afik')->assertSessionHasInput('school_npsn','20363192');
  $this->assertNull(session()->getOldInput('password'));$this->assertNull(session()->getOldInput('password_confirmation'));
  // HTML redirect and flashed errors are asserted above; a real cookie-jar HTTP check covers the visible message.
  $this->get('/register')->assertOk();
  // Form reading is not throttled even when its POST budget is exhausted.
  $this->get('/register')->assertOk();
 }
 public function test_json_registration_limit_still_returns_429():void {
  for($i=0;$i<6;$i++)$this->post('/register',[]);
  $this->postJson('/register',[])->assertStatus(429)->assertHeader('Retry-After')->assertJsonStructure(['message','retry_after']);
 }
 public function test_exhausted_directory_does_not_block_register_or_login():void {
  for($i=0;$i<60;$i++)$this->getJson('/api/school-directory/provinces')->assertOk();
  $this->getJson('/api/school-directory/provinces')->assertStatus(429)->assertHeader('Retry-After');
  $this->post('/register',[])->assertSessionHasErrors('display_name')->assertSessionDoesntHaveErrors('rate_limit');
  $this->post('/login',[])->assertSessionHasErrors('email')->assertSessionDoesntHaveErrors('rate_limit');
 }
 public function test_valid_registration_succeeds_after_many_directory_requests():void {
  if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Memerlukan DB spatial uji.');
  config(['services.dapodik.token'=>'public-fixture-token']);
  Cache::put('school.directory.online-regencies.v1.33',[['code'=>'33.17','name'=>'Kabupaten Rembang']]);
  Cache::put('school.directory.online-districts.v1.33.17',[['code'=>'33.17.14','name'=>'Lasem']]);
  $fixture=json_decode(file_get_contents(__DIR__.'/../Fixtures/man-2-rembang.json'),true);
  \Illuminate\Support\Facades\Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>\Illuminate\Support\Facades\Http::response(['data'=>[]]),'https://sekolah.devapi.id/*'=>\Illuminate\Support\Facades\Http::response($fixture)]);
  \Illuminate\Support\Facades\DB::beginTransaction();try{
   for($i=0;$i<8;$i++)$this->getJson('/api/school-directory/provinces')->assertOk();
   $this->getJson('/api/school-directory/schools?query=20363192&province_code=33&regency_code=33.17&district_code=33.17.14')->assertOk()->assertJsonPath('data.0.name','MAN 2 REMBANG');
   $this->post('/register',['display_name'=>'Afik','province_code'=>'33','regency_code'=>'33.17','district_code'=>'33.17.14','school_npsn'=>'20363192','school_name'=>'MAN 2 REMBANG','email'=>'new-registration-fixture@ruteaman.test','password'=>'SourceTestPassword123!','password_confirmation'=>'SourceTestPassword123!','parental_consent'=>'1'])->assertRedirect();
   $user=\App\Models\User::where('email','new-registration-fixture@ruteaman.test')->firstOrFail();
   $this->assertAuthenticatedAs($user);$this->assertSame('20363192',$user->school->npsn);$this->assertSame('student',$user->role->value);
  }finally{\Illuminate\Support\Facades\DB::rollBack();}
 }

}
