<?php
namespace Tests\Feature;
use App\Models\{User,School,HazardReport,HazardConfirmation,AuditLog,Hazard,HazardType};
use App\Services\AccountNotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{DB,Storage,Mail,URL};
use Illuminate\Http\UploadedFile;
use App\Mail\AccountVerificationMail;
use Tests\TestCase;
class AccountFeatureTest extends TestCase {
 use DatabaseTransactions;
 private User $owner;private User $peer;private User $admin;private User $outsider;
 protected function setUp():void {
  if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Memerlukan DB spatial uji.');
  parent::setUp();config(['app.key'=>'base64:'.base64_encode(str_repeat('a',32))]);
  $s=DB::table('schools')->insertGetId(['name'=>'Akun Sekolah A','location'=>DB::raw("ST_GeomFromText('POINT(106.82 -6.17)',4326)"),'catchment'=>DB::raw("ST_GeomFromText('POLYGON((106.8 -6.2,106.9 -6.2,106.9 -6.1,106.8 -6.1,106.8 -6.2))',4326)")]);
  $t=DB::table('schools')->insertGetId(['name'=>'Akun Sekolah B','location'=>DB::raw("ST_GeomFromText('POINT(106.82 -6.17)',4326)"),'catchment'=>DB::raw("ST_GeomFromText('POLYGON((106.8 -6.2,106.9 -6.2,106.9 -6.1,106.8 -6.1,106.8 -6.2))',4326)")]);
  foreach(['owner','peer','admin','outsider'] as $name){$this->{$name}=(new User)->forceFill(['display_name'=>'Fixture '.$name,'email'=>$name.'-account@test.invalid','password'=>'FixturePassword123!','school_id'=>$name==='outsider'?$t:$s,'role'=>$name==='admin'?'school_admin':'student','active'=>true,'parental_consent_at'=>now(),'permissions'=>$name==='admin'?config('ruteaman_roles.ceilings.school_admin'):[]]);$this->{$name}->save();}
  DB::table('schools')->whereIn('id',[$s,$t])->update(['features'=>json_encode(array_replace(config('ruteaman_admin.features'),['avatar_upload'=>true]))]);Storage::fake('avatars');Mail::fake();
 }
 public function test_guest_sheet_and_account_protection():void{$this->get('/help')->assertOk()->assertSee('Masuk atau daftar')->assertDontSee('Lapor tanpa login')->assertDontSee(route('account.guide'),false);$this->getJson('/api/account')->assertUnauthorized()->assertJsonPath('error.code','SESSION_EXPIRED');}
 public function test_account_pages_and_role_menu():void{
  foreach(['profile','settings','security','privacy','notifications'] as $page)$this->actingAs($this->owner)->get('/account/'.$page)->assertOk()->assertDontSee('Manajemen Pengguna');
  $this->actingAs($this->admin)->get('/account/profile')->assertSee('id="admin-sidebar"',false)->assertSee('Pengguna Sekolah')->assertSee('Antrean Verifikasi');
  $this->peer->forceFill(['role'=>'verifier','permissions'=>config('ruteaman_roles.ceilings.verifier')])->save();$this->actingAs($this->peer)->get('/account/profile')->assertDontSee('Manajemen Pengguna')->assertSee('Dashboard Institusi');
  $this->get('/school/users')->assertForbidden();
 }
 public function test_name_validation_and_school_uniqueness():void{
  $this->actingAs($this->owner)->patchJson('/account/profile',['display_name'=>'<b>New Nick</b>'])->assertOk()->assertJsonPath('account.display_name','New Nick');
  $this->patchJson('/account/profile',['display_name'=>mb_strtoupper($this->peer->display_name)])->assertUnprocessable()->assertJsonPath('error.code','VALIDATION_ERROR');
  $this->patchJson('/account/profile',['display_name'=>'admin'])->assertUnprocessable();
  $this->patchJson('/account/profile',['display_name'=>'a'])->assertUnprocessable();
  $this->patchJson('/account/profile',['display_name'=>['invalid']])->assertUnprocessable();
 }
 public function test_private_avatar_upload_thumbnails_and_permissions():void{
  $this->actingAs($this->owner)->postJson('/account/avatar',['avatar'=>UploadedFile::fake()->image('portrait.jpg',280,350)])->assertOk();$path=$this->owner->fresh()->avatar_path;
  Storage::disk('avatars')->assertExists([$path,str_replace('.webp','-96.webp',$path),str_replace('.webp','-256.webp',$path)]);
  foreach([512,96,256] as $size){$file=$size===512?$path:str_replace('.webp','-'.$size.'.webp',$path);$info=getimagesize(Storage::disk('avatars')->path($file));$this->assertSame([$size,$size],array_slice($info,0,2));$this->assertSame('image/webp',$info['mime']);$this->assertStringNotContainsString('Exif',Storage::disk('avatars')->get($file));}
  $route=route('account.avatar.show',$this->owner);$this->get($route)->assertOk()->assertHeader('Cache-Control','no-store, private');
  $this->actingAs($this->peer)->get($route)->assertForbidden();$this->actingAs($this->outsider)->get($route)->assertForbidden();$this->actingAs($this->admin)->get($route)->assertOk();
  $this->actingAs($this->owner)->patchJson('/account/avatar-preset',['avatar_preset'=>'preset-3'])->assertOk();Storage::disk('avatars')->assertMissing($path);$this->assertNull($this->owner->fresh()->avatar_path);
  $this->deleteJson('/account/avatar')->assertOk();$this->assertNull($this->owner->fresh()->avatar_preset);
 }
 public function test_avatar_consent_size_type_and_no_path_leak():void{
  $this->owner->update(['parental_consent_at'=>null]);$this->actingAs($this->owner)->postJson('/account/avatar',['avatar'=>UploadedFile::fake()->image('photo.jpg',200,200)])->assertUnprocessable();
  $this->patchJson('/account/avatar-preset',['avatar_preset'=>'../../secret'])->assertUnprocessable();
  $this->patchJson('/account/avatar-preset',['avatar_preset'=>'preset-8'])->assertOk();
  $this->postJson('/account/avatar',['avatar'=>UploadedFile::fake()->create('bad.jpg',10,'text/plain')])->assertUnprocessable();
  $this->postJson('/account/avatar',['avatar'=>UploadedFile::fake()->image('small.png',50,50)])->assertUnprocessable();
  $api=$this->getJson('/api/account')->assertOk()->json('data');$this->assertArrayNotHasKey('avatar_path',$api);$this->assertArrayNotHasKey('email_verification_token',$api);
 }
 public function test_email_changes_only_after_signed_verified_link():void{
  $this->actingAs($this->owner)->patchJson('/account/email',['email'=>'new-account@test.invalid','current_password'=>'wrong'])->assertUnprocessable();
  $this->patchJson('/account/email',['email'=>'new-account@test.invalid','current_password'=>'FixturePassword123!'])->assertOk();$this->assertSame('owner-account@test.invalid',$this->owner->fresh()->email);
  $url='';Mail::assertSent(AccountVerificationMail::class,function($m)use(&$url){$url=$m->verificationUrl;return $m->hasTo('new-account@test.invalid');});
  $this->actingAs($this->peer)->get($url)->assertForbidden();$this->actingAs($this->owner)->get($url)->assertRedirect(route('account.profile'));
  $this->assertSame('new-account@test.invalid',$this->owner->fresh()->email);$this->assertNotNull($this->owner->fresh()->email_verified_at);$this->get($url)->assertForbidden();
  $log=AuditLog::where('action','user.email_changed')->firstOrFail();$this->assertNull($log->meta_json);
 }
 public function test_password_rules_and_preferences():void{
  $this->actingAs($this->owner)->putJson('/account/password',['current_password'=>'FixturePassword123!','password'=>'weak','password_confirmation'=>'weak'])->assertUnprocessable();
  $this->putJson('/account/password',['current_password'=>'FixturePassword123!','password'=>'NewFixturePassword456!','password_confirmation'=>'NewFixturePassword456!'])->assertOk();
  $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewFixturePassword456!',$this->owner->fresh()->password));
  $this->patchJson('/account/preferences',['appearance'=>'dark','locale'=>'en'])->assertUnprocessable();$this->patchJson('/account/preferences',['appearance'=>'light','locale'=>'id'])->assertOk();
  $this->assertDatabaseHas('audit_logs',['action'=>'user.password_changed','meta_json'=>null]);
 }
 private function report(User $user):Hazard {
  $type=HazardType::firstOrCreate(['code'=>'account_fixture'],['label'=>'Hazard Fixture','severity_weight'=>1,'ttl_minutes'=>60]);
  $id=DB::table('hazards')->insertGetId(['type_id'=>$type->id,'severity'=>1,'status'=>'new','confidence'=>0,'first_reported_at'=>now(),'public_slug'=>'account-'.\Illuminate\Support\Str::random(12),'location'=>DB::raw("ST_GeomFromText('POINT(106.82 -6.17)',4326)"),'created_at'=>now(),'updated_at'=>now()]);
  HazardReport::create(['hazard_id'=>$id,'reporter_id'=>$user->id,'description'=>'Own description','observed_at'=>now()]);return Hazard::findOrFail($id);
 }
 public function test_notifications_are_own_reports_and_seen_is_idempotent():void{
  $h=$this->report($this->owner);$other=$this->report($this->peer);
  foreach([$h,$other] as $hazard)AuditLog::create(['actor_id'=>$this->admin->id,'action'=>'verification.verified','entity'=>'hazard','entity_id'=>$hazard->id,'created_at'=>now()]);
  $this->assertSame(1,app(AccountNotificationService::class)->unread($this->owner));
  $this->actingAs($this->owner)->get('/account/notifications')->assertOk()->assertSee('Diverifikasi oleh verifikator sekolah');
  $this->postJson('/account/notifications/seen')->assertOk()->assertJsonPath('account.unread_count',0);$this->postJson('/account/notifications/seen')->assertOk()->assertJsonPath('account.unread_count',0);
 }
 public function test_export_and_delete_preserve_anonymous_evidence():void{
  $h=$this->report($this->owner);$this->report($this->peer);HazardConfirmation::create(['hazard_id'=>$h->id,'user_id'=>$this->owner->id,'vote'=>'still_there','created_at'=>now()]);
  $this->actingAs($this->owner)->postJson('/account/avatar',['avatar'=>UploadedFile::fake()->image('profile.png',200,200)])->assertOk();$path=$this->owner->fresh()->avatar_path;
  $response=$this->get('/account/export')->assertOk();$export=json_decode($response->streamedContent(),true);$this->assertCount(1,$export['reports']);$this->assertStringNotContainsString($this->peer->email,$response->streamedContent());
  $this->deleteJson('/account',['current_password'=>'wrong','confirmation'=>'HAPUS'])->assertUnprocessable();
  $this->deleteJson('/account',['current_password'=>'FixturePassword123!','confirmation'=>'HAPUS'])->assertOk();$deleted=$this->owner->fresh();$this->assertFalse($deleted->active);$this->assertSame('Pengguna dihapus',$deleted->display_name);$this->assertNull($deleted->school_id);Storage::disk('avatars')->assertMissing($path);$this->assertDatabaseHas('hazard_reports',['reporter_id'=>$deleted->id]);$this->assertDatabaseHas('hazard_confirmations',['user_id'=>$deleted->id]);$this->assertGuest();
 }

 public function test_avatar_metadata_removed_replacement_cleanup_and_guest_rejected():void {
  $fake=UploadedFile::fake()->image('original.jpg',300,180);$bytes=file_get_contents($fake->getRealPath());
  $exif="Exif\0\0GPS confidential fixture";$jpeg=substr($bytes,0,2)."\xff\xe1".pack('n',strlen($exif)+2).$exif.substr($bytes,2);
  $upload=UploadedFile::fake()->createWithContent('with-exif.jpg',$jpeg);
  $this->actingAs($this->owner)->postJson('/account/avatar',['avatar'=>$upload])->assertOk();$old=$this->owner->fresh()->avatar_path;
  $this->assertStringNotContainsString('GPS confidential fixture',Storage::disk('avatars')->get($old));
  $this->postJson('/account/avatar',['avatar'=>UploadedFile::fake()->image('replace.jpg',200,200)])->assertOk();
  Storage::disk('avatars')->assertMissing([$old,str_replace('.webp','-96.webp',$old),str_replace('.webp','-256.webp',$old)]);
  $this->actingAsGuest()->get(route('account.avatar.show',$this->owner))->assertRedirect(route('login'));
 }
 public function test_email_signature_expiry_and_rate_limit():void {
  $this->actingAs($this->owner)->patchJson('/account/email',['email'=>'pending@test.invalid','current_password'=>'FixturePassword123!'])->assertOk();
  $url='';Mail::assertSent(AccountVerificationMail::class,function($m)use(&$url){$url=$m->verificationUrl;return true;});
  $this->get($url.'x')->assertForbidden();$this->travel(61)->minutes();$this->get($url)->assertForbidden();$this->assertSame('owner-account@test.invalid',$this->owner->fresh()->email);
  for($i=0;$i<3;$i++)$this->postJson('/email/verification-notification')->assertOk();$this->postJson('/email/verification-notification')->assertStatus(429)->assertJsonPath('error.code','RATE_LIMITED');
 }
 public function test_verifier_dashboard_is_read_only():void {
  $this->peer->forceFill(['role'=>'verifier','permissions'=>config('ruteaman_roles.ceilings.verifier')])->save();$this->actingAs($this->peer)->get('/school/dashboard')->assertOk()->assertSee('Verifikator')->assertDontSee('Paket eskalasi');
  $this->get('/school/users')->assertForbidden();$this->get('/verifier/queue')->assertOk();
  $h=$this->report($this->owner);$this->post(route('hazards.escalate',$h),['channel'=>'Fixture'])->assertForbidden();
 }
 public function test_resolved_and_gone_events_are_report_updates():void {
  $h=$this->report($this->owner);$h->update(['status'=>'resolved','resolved_at'=>now()]);
  $this->assertDatabaseHas('audit_logs',['entity_id'=>$h->id,'action'=>'hazard.resolved']);$this->assertSame(1,app(AccountNotificationService::class)->unread($this->owner));
 }

 public function test_events_before_my_report_are_not_counted():void {
  $h=$this->report($this->owner);AuditLog::create(['actor_id'=>null,'action'=>'verification.verified','entity'=>'hazard','entity_id'=>$h->id,'created_at'=>now()->subDay()]);
  $this->assertSame(0,app(AccountNotificationService::class)->unread($this->owner));
 }
 public function test_school_user_management_scope_and_audit():void{
  $this->actingAs($this->owner)->get('/school/users')->assertForbidden();$this->actingAs($this->admin)->get('/school/users')->assertOk()->assertDontSee('Fixture outsider');
  $this->patchJson(route('school.users.update',$this->outsider),['role'=>'verifier','active'=>true])->assertForbidden()->assertJsonPath('error.code','FORBIDDEN_SCOPE');
  $this->patchJson(route('school.users.update',$this->admin),['role'=>'student','active'=>false])->assertForbidden();
  $this->patchJson(route('school.users.update',$this->peer),['role'=>'verifier','active'=>true])->assertOk();$this->assertSame('verifier',$this->peer->fresh()->role->value);$this->assertDatabaseHas('audit_logs',['action'=>'user.role_changed','meta_json'=>null]);
  $this->patchJson(route('school.users.update',$this->peer),['role'=>'verifier','active'=>false])->assertOk();$this->actingAs($this->peer->fresh())->getJson('/api/account')->assertUnauthorized()->assertJsonPath('error.code','SESSION_EXPIRED');
 }
}
