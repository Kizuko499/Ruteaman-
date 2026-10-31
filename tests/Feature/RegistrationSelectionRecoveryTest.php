<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{Cache,Http};
use Tests\TestCase;
class RegistrationSelectionRecoveryTest extends TestCase {
 use DatabaseTransactions;
 protected function setUp():void {
  if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Memerlukan DB spatial uji.');
  parent::setUp();config(['app.key'=>'base64:'.base64_encode(str_repeat('c',32)),'services.dapodik.token'=>'public-fixture-token']);
  Cache::put('school.directory.online-provinces.v1',[['code'=>'33','name'=>'Jawa Tengah']]);
  Cache::put('school.directory.online-regencies.v1.33',[['code'=>'33.17','name'=>'Kabupaten Rembang']]);
  Cache::put('school.directory.online-districts.v1.33.17',[['code'=>'33.17.14','name'=>'Lasem'],['code'=>'33.17.99','name'=>'Rembang']]);
  $fixture=json_decode(file_get_contents(__DIR__.'/../Fixtures/man-2-rembang.json'),true);
  Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>Http::response(['data'=>[]]),'https://sekolah.devapi.id/*'=>Http::response($fixture)]);
 }
 private function data():array {return ['display_name'=>'Siswa Fixture','province_code'=>'33','regency_code'=>'33.17','district_code'=>'33.17.14','school_name'=>'MAN 2 REMBANG','email'=>'selection-recovery@ruteaman.test','password'=>'SourceFixture123!','password_confirmation'=>'SourceFixture123!','parental_consent'=>'1'];}
 public function test_selected_backup_recovers_a_cleared_hidden_npsn():void {
  $this->post('/register',[...$this->data(),'school_npsn'=>'','selected_school_npsn'=>'20363192'])->assertRedirect();
  $user=User::where('email','selection-recovery@ruteaman.test')->firstOrFail();$this->assertAuthenticatedAs($user);$this->assertSame('20363192',$user->school->npsn);
 }
 public function test_exact_typed_npsn_is_verified_when_hidden_fields_are_absent():void {
  $this->post('/register',[...$this->data(),'school_query'=>'20363192'])->assertRedirect();
  $user=User::where('email','selection-recovery@ruteaman.test')->firstOrFail();$this->assertSame('MAN 2 REMBANG',$user->school->name);
 }
 public function test_recovery_does_not_bypass_school_region_verification():void {
  $this->post('/register',[...$this->data(),'selected_school_npsn'=>'20363192','district_code'=>'33.17.99'])->assertSessionHasErrors('school_npsn');
  $this->assertFalse(User::where('email','selection-recovery@ruteaman.test')->exists());
 }
 public function test_nonempty_primary_is_not_overridden_by_a_different_backup():void {
  $this->post('/register',[...$this->data(),'school_npsn'=>'99999991','selected_school_npsn'=>'20363192'])->assertSessionHasErrors('school_npsn');
  $this->assertFalse(User::where('email','selection-recovery@ruteaman.test')->exists());
 }
 public function test_other_validation_errors_preserve_recovered_school_choice():void {
  $this->post('/register',[...$this->data(),'selected_school_npsn'=>'20363192','password_confirmation'=>'DifferentFixture123!'])
   ->assertSessionHasErrors('password')->assertSessionHasInput('school_npsn','20363192')->assertSessionHasInput('school_name','MAN 2 REMBANG')->assertSessionHasInput('district_code','33.17.14');
 }
 public function test_nationwide_selection_registers_without_region_codes():void {
  $data=$this->data();unset($data['province_code'],$data['regency_code'],$data['district_code']);
  $this->post('/register',[...$data,'school_npsn'=>'20363192'])->assertRedirect();
  $user=User::where('email','selection-recovery@ruteaman.test')->firstOrFail();$this->assertAuthenticatedAs($user);$this->assertSame('20363192',$user->school->npsn);$this->assertSame('33.17.14',$user->school->district_code);
 }
 public function test_missing_selection_has_an_indonesian_error_message():void {
  $this->post('/register',$this->data())->assertSessionHasErrors(['school_npsn'=>'Pilihan sekolah belum tersimpan. Pilih sekolah dari hasil pencarian atau masukkan NPSN yang lengkap.']);
 }
}
