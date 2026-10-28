<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,AuditLog};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
class OwnerConsoleInputTest extends TestCase {
 use DatabaseTransactions;
 protected function setUp():void {if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Dedicated spatial database required.');parent::setUp();}
 public function test_explicit_visible_input_creates_owner_with_hash_and_no_password_in_audit():void {
  $pw='VisibleFixtureOnly123!';
  $this->artisan('ruteaman:owner:create',['--show-password'=>true])
   ->expectsOutputToContain('Mode password terlihat aktif')
   ->expectsQuestion('Nama tampilan','Operator CLI')
   ->expectsQuestion('Alias login pada domain '.config('ruteaman_admin.alias_domain'),'cli.pengelola@'.config('ruteaman_admin.alias_domain'))
   ->expectsQuestion('Email kontak asli','cli@example.test')
   ->expectsQuestion('Kata sandi (minimal 12 karakter) [terlihat]',$pw)
   ->expectsQuestion('Ulangi kata sandi [terlihat]',$pw)->assertSuccessful();
  $u=User::where('display_name','Operator CLI')->firstOrFail();$this->assertTrue(Hash::check($pw,$u->password));$this->assertNotSame($pw,$u->password);$this->assertStringNotContainsString($pw,AuditLog::all()->toJson());
 }
 public function test_visible_reset_and_default_hidden_prompts_remain_supported():void {
  $u=(new User)->forceFill(['display_name'=>'Owner Fixture','email'=>'input.chief@ruteaman.my.id','contact_email'=>'chief@example.test','password'=>'OldFixturePassword123!','role'=>'owner','status'=>'active','active'=>true]);$u->save();
  $pw='NewFixturePassword123!';$this->artisan('ruteaman:owner:reset',['--show-password'=>true])->expectsOutputToContain('Mode password terlihat aktif')->expectsQuestion('Alias Owner yang dipulihkan',$u->email)->expectsQuestion('Kata sandi baru (minimal 12 karakter) [terlihat]',$pw)->expectsQuestion('Ulangi kata sandi [terlihat]',$pw)->assertSuccessful();$this->assertTrue(Hash::check($pw,$u->fresh()->password));
  $hidden='HiddenFixturePassword123!';$this->artisan('ruteaman:owner:reset')->expectsQuestion('Alias Owner yang dipulihkan',$u->email)->expectsQuestion('Kata sandi baru (minimal 12 karakter)',$hidden)->expectsQuestion('Ulangi kata sandi',$hidden)->assertSuccessful();$this->assertTrue(Hash::check($hidden,$u->fresh()->password));$this->assertStringNotContainsString($hidden,AuditLog::all()->toJson());
 }
}
