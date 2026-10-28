<?php
namespace Tests\Feature;
use App\Models\{Hazard,HazardType,School,User,Verification};
use App\Services\{ConditionFeedService,FeaturedHazardService,PhotoSanitizer};
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Cache,DB,Storage};
use Tests\TestCase;
class ConditionPublicationTest extends TestCase {
 use DatabaseTransactions;
 protected function setUp():void {if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Dedicated spatial database required.');parent::setUp();$this->seed(DemoSeeder::class);Cache::flush();}
 private function school():School{return School::where('name','Sekolah A — Demo Simulasi')->firstOrFail();}
 private function fixture(string $slug,string $status='confirmed',int $minutes=10):Hazard {
  $id=DB::table('hazards')->insertGetId(['type_id'=>HazardType::where('code','pothole')->value('id'),'location'=>\App\Services\SpatialSql::point(106.825,-6.178),'severity'=>2,'confidence'=>.5,'first_reported_at'=>now()->subMinutes($minutes),'status'=>$status,'public_slug'=>$slug,'location_label'=>'Depan pasar','is_demo'=>false,'created_at'=>now(),'updated_at'=>now()]);ConditionFeedService::invalidate();return Hazard::findOrFail($id);
 }
 public function test_order_cursor_and_insertion_are_stable():void {
  $this->fixture('publication-a');$this->fixture('publication-b');$this->fixture('publication-c');$s=$this->school();$f=app(ConditionFeedService::class);$first=$f->feed($s,['limit'=>2]);$this->assertSame(['publication-c','publication-b'],array_column($first['items'],'public_slug'));$this->fixture('publication-new','confirmed',1);$next=$f->feed($s,['limit'=>2,'cursor'=>$first['next_cursor']]);$this->assertContains('publication-a',array_column($next['items'],'public_slug'));$this->assertNotContains('publication-new',array_column($next['items'],'public_slug'));$this->assertEmpty(array_intersect(array_column($first['items'],'public_slug'),array_column($next['items'],'public_slug')));
  $this->getJson('/api/conditions?cursor=not-a-cursor')->assertUnprocessable();$this->getJson('/api/conditions?limit=25')->assertUnprocessable();
 }
 public function test_rotation_wrap_missing_single_and_empty():void {
  $school=$this->school();Hazard::query()->update(['status'=>'expired']);$this->fixture('rotate-a');$this->fixture('rotate-b');$service=app(FeaturedHazardService::class);$this->assertSame('rotate-b',$service->next($school)['hazard']['public_slug']);$this->assertSame('rotate-a',$service->next($school,'rotate-b')['hazard']['public_slug']);$this->assertSame('rotate-b',$service->next($school,'rotate-a')['hazard']['public_slug']);$this->assertSame('rotate-b',$service->next($school,'missing')['hazard']['public_slug']);Hazard::where('public_slug','rotate-b')->first()->update(['status'=>'expired']);$this->assertSame('rotate-a',$service->next($school,'rotate-a')['hazard']['public_slug']);Hazard::where('public_slug','rotate-a')->first()->update(['status'=>'expired']);$this->assertNull($service->next($school)['hazard']);
 }
 public function test_visibility_privacy_and_resolved_window():void {
  $new=$this->fixture('secret-new','new');$rejected=$this->fixture('secret-rejected','resolved');Verification::create(['hazard_id'=>$rejected->id,'verifier_id'=>User::first()->id,'decision'=>'rejected','note'=>'private test note','created_at'=>now()]);$old=$this->fixture('old-sur ut','resolved');$old->update(['resolved_at'=>now()->subHours(25)]);$recent=$this->fixture('recent-surut','resolved');$recent->update(['resolved_at'=>now()->subHour()]);
  $this->get(route('conditions.show',$new))->assertNotFound();$this->get(route('conditions.show',$rejected))->assertNotFound();$this->get('/kondisi/not-real')->assertNotFound();$this->getJson('/api/conditions')->assertOk()->assertDontSee('secret-new')->assertDontSee('secret-rejected')->assertDontSee('recent-surut');$this->getJson('/api/conditions?tampil=surut')->assertOk()->assertSee('recent-surut')->assertDontSee('old-sur')->assertDontSee('secret-rejected');
  $public=$this->fixture('publication-public');$html=$this->get(route('conditions.show',$public))->assertOk()->assertSee('noindex,nofollow')->getContent();$json=$this->getJson('/api/conditions')->assertOk()->getContent();foreach(['reporter_id','verifier_id','display_name','siswa_1@ruteaman.test','private test note'] as $private){$this->assertStringNotContainsString($private,$html);$this->assertStringNotContainsString($private,$json);}foreach(['latitude','longitude','coordinates'] as $private)$this->assertStringNotContainsString($private,$json);
  $this->actingAs(User::where('email','siswa_1@ruteaman.test')->first())->get(route('conditions.show',$new))->assertOk()->assertSee('Menunggu konfirmasi');
 }
 public function test_guarded_photos_sizes_and_metadata():void {
  Storage::fake('hazard_photos');Storage::fake('public');$h=$this->fixture('photo-test');$path=app(PhotoSanitizer::class)->store(UploadedFile::fake()->image('image.jpg',1800,1200));$h->reports()->create(['reporter_id'=>User::first()->id,'photo_path'=>$path,'observed_at'=>now()]);$this->assertFalse(Storage::disk('public')->exists($path));
  foreach([640=>120*1024,1280=>200*1024] as $w=>$limit){$bytes=$this->get(route('conditions.media',['hazard'=>$h,'index'=>0,'w'=>$w]))->assertOk()->assertHeader('Content-Type','image/webp')->getContent();$this->assertLessThanOrEqual($limit,strlen($bytes));$this->assertLessThanOrEqual($w,getimagesizefromstring($bytes)[0]);$this->assertStringNotContainsString('Exif',$bytes);}
  $jpeg=$this->get(route('conditions.media',['hazard'=>$h,'index'=>0,'w'=>1280,'format'=>'jpeg']))->assertOk()->assertHeader('Content-Type','image/jpeg')->getContent();$this->assertSame(IMAGETYPE_JPEG,getimagesizefromstring($jpeg)[2]);$this->assertLessThanOrEqual(200*1024,strlen($jpeg));$this->assertStringNotContainsString('Exif',$jpeg);
  $h->update(['status'=>'new']);$this->get(route('conditions.media',['hazard'=>$h,'index'=>0]))->assertNotFound();$this->get(route('conditions.og',$h))->assertNotFound();
 }
 public function test_etag_invalidation_catchment_and_rate_limit():void {
  $h=$this->fixture('etag-test');$url='/api/hazards/featured?school='.$this->school()->id;$r=$this->getJson($url)->assertOk();$this->withHeader('If-None-Match',$r->headers->get('ETag'))->getJson($url)->assertStatus(304);$h->update(['status'=>'expired']);$this->getJson($url)->assertOk();
  $this->withHeader('If-None-Match','');$far=$this->fixture('far-hazard');DB::table('hazards')->where('id',$far->id)->update(['location'=>\App\Services\SpatialSql::point(110,-7)]);ConditionFeedService::invalidate();$this->getJson('/api/conditions?school='.$this->school()->id)->assertDontSee('far-hazard');
  Cache::flush();for($i=0;$i<60;$i++)$this->getJson($url)->assertOk();$this->getJson($url)->assertStatus(429);
 }
 public function test_expired_reconfirmation_and_resolved_guard():void {
  $h=$this->fixture('expired-condition','expired');$u=User::where('email','siswa_2@ruteaman.test')->first();$this->actingAs($u)->post(route('hazards.confirm',$h),['vote'=>'still_there'])->assertRedirect();$this->assertSame('disputed',$h->fresh()->status->value);$this->assertNotNull($h->fresh()->last_confirmed_at);$h->update(['status'=>'resolved','resolved_at'=>now()]);$this->post(route('hazards.confirm',$h),['vote'=>'still_there'])->assertStatus(409);
 }
 public function test_related_distance_and_media_rejection():void {
  $self=$this->fixture('related-self');$near=$this->fixture('related-near');$other=$this->fixture('related-far');DB::table('hazards')->where('id',$near->id)->update(['location'=>\App\Services\SpatialSql::point(106.826,-6.178)]);DB::table('hazards')->where('id',$other->id)->update(['location'=>\App\Services\SpatialSql::point(106.84,-6.178)]);$items=app(\App\Services\RelatedHazardService::class)->nearest($self,$this->school());$slugs=array_column($items,'public_slug');$this->assertContains('related-near',$slugs);$this->assertNotContains('related-self',$slugs);$this->assertNotContains('related-far',$slugs);$this->get(route('conditions.media',['hazard'=>$near,'index'=>99]))->assertNotFound();
 }
 public function test_pending_expiry_and_legacy_routes_remain_private():void {
  $new=$this->fixture('unconfirmed-private','new');$expired=$this->fixture('unconfirmed-expired-private','expired');
  foreach([$new,$expired] as $h){$this->get(route('conditions.show',$h))->assertNotFound();$this->get(route('hazards.public',$h))->assertNotFound();$this->get(route('conditions.og',$h))->assertNotFound();$this->getJson('/api/conditions/'.$h->public_slug)->assertNotFound();}
  $this->getJson('/api/feed/hazards.geojson')->assertOk()->assertDontSee('unconfirmed-private')->assertDontSee('unconfirmed-expired-private');
 }
 public function test_legacy_photos_move_private_and_are_reencoded():void {
  Storage::fake('public');Storage::fake('hazard_photos');$h=$this->fixture('legacy-photo-private');$path='hazards/old-original.jpg';$file=UploadedFile::fake()->image('old.jpg',300,200);Storage::disk('public')->put($path,file_get_contents($file->getRealPath()).'ExifGPS-secret');$h->reports()->create(['reporter_id'=>User::first()->id,'photo_path'=>$path,'observed_at'=>now()]);
  $this->artisan('ruteaman:privatize-hazard-photos')->assertExitCode(0);$this->assertFalse(Storage::disk('public')->exists($path));$bytes=Storage::disk('hazard_photos')->get($path);$this->assertStringNotContainsString('ExifGPS-secret',$bytes);$this->assertSame(IMAGETYPE_WEBP,getimagesizefromstring($bytes)[2]);$this->get(route('conditions.media',['hazard'=>$h,'index'=>0]))->assertOk();
 }
 public function test_timeline_never_discloses_notes_or_actors_and_escalation_is_opt_in():void {
  $h=$this->fixture('timeline-private');$school=$this->school();$admin=User::where('email','admin_a@ruteaman.test')->first();
  \App\Models\AuditLog::create(['actor_id'=>$admin->id,'entity'=>'hazard','entity_id'=>$h->id,'action'=>'verification.verified','meta_json'=>['note'=>'SENSITIVE-reviewer-identity'],'created_at'=>now()]);
  \App\Models\AuditLog::create(['actor_id'=>$admin->id,'entity'=>'hazard','entity_id'=>$h->id,'action'=>'private.internal.note','meta_json'=>['name'=>'PRIVATE-report-name'],'created_at'=>now()]);
  $h->escalations()->create(['channel'=>'PRIVATE-phone-number','response_status'=>'forwarded','response_note'=>'PRIVATE-phone-response','forwarded_at'=>now()]);
  $page=$this->get(route('conditions.show',$h))->assertOk()->assertDontSee('Diteruskan ke pihak berwenang');foreach(['SENSITIVE-reviewer-identity','PRIVATE-report-name','PRIVATE-phone-number','PRIVATE-phone-response',$admin->email] as $private)$page->assertDontSee($private);
  $school->update(['settings'=>['public_escalation_status'=>true]]);$this->get(route('conditions.show',$h))->assertOk()->assertSee('Diteruskan ke pihak berwenang')->assertDontSee('PRIVATE-phone-number');
 }

}
