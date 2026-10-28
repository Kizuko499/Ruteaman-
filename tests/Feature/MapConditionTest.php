<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Hazard,HazardType,School,User,Verification};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
class MapConditionTest extends TestCase {
 use DatabaseTransactions;
 protected function setUp():void {if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Dedicated spatial database required.');parent::setUp();$this->seed(\Database\Seeders\DemoSeeder::class);}
 private function school():School{return School::where('name','Sekolah A — Demo Simulasi')->firstOrFail();}
 private function hazard(string $slug,string $status='confirmed',int $days=0):Hazard {
  $id=DB::table('hazards')->insertGetId(['type_id'=>HazardType::where('code','flood')->value('id'),'location'=>\App\Services\SpatialSql::point(106.825,-6.178),'severity'=>3,'status'=>$status,'confidence'=>.5,'first_reported_at'=>now()->subDays($days),'published_at'=>in_array($status,['new','expired'])?null:now()->subDays($days),'public_slug'=>$slug,'location_label'=>'Label publik','created_at'=>now(),'updated_at'=>now()]);return Hazard::findOrFail($id);
 }
 public function test_history_survives_ttl_expiry_and_resolved_window_without_changing_route_feed():void {
  foreach([1,3,7,30] as $days){$h=$this->hazard('persistent-'.$days,'confirmed',$days);$this->getJson('/api/map/conditions?school='.$this->school()->id)->assertOk()->assertSee('persistent-'.$days);$h->update(['status'=>'expired']);$this->getJson('/api/map/conditions?school='.$this->school()->id)->assertOk()->assertSee('persistent-'.$days);$this->get(route('conditions.show',$h))->assertOk();}
  $h=$this->hazard('resolved-old','resolved',30);$h->update(['resolved_at'=>now()->subDays(29)]);$this->getJson('/api/map/conditions?school='.$this->school()->id)->assertSee('resolved-old');$this->getJson('/api/feed/hazards.geojson?school='.$this->school()->id)->assertDontSee('persistent-')->assertDontSee('resolved-old');
 }
 public function test_private_and_rejected_never_exposed_and_publication_is_recorded():void {
  $n=$this->hazard('private-new','new',30);$e=$this->hazard('private-expired','expired',30);$r=$this->hazard('rejected-history','resolved',30);Verification::create(['hazard_id'=>$r->id,'verifier_id'=>User::first()->id,'decision'=>'rejected','created_at'=>now()]);
  $url='/api/map/conditions?school='.$this->school()->id;$this->getJson($url)->assertOk()->assertDontSee('private-new')->assertDontSee('private-expired')->assertDontSee('rejected-history');
  $n->update(['status'=>'confirmed']);$this->assertNotNull($n->fresh()->published_at);$n->update(['status'=>'expired']);$this->getJson($url)->assertSee('private-new')->assertDontSee('private-expired');$this->get(route('conditions.show',$e))->assertNotFound();
 }
 public function test_cursor_scope_pagination_and_invalid_inputs():void {
  for($i=0;$i<5;$i++)$this->hazard('paged-'.$i,'confirmed',30);$url='/api/map/conditions?school='.$this->school()->id.'&limit=2';$first=$this->getJson($url)->assertOk()->assertJsonCount(2,'items')->json();$this->assertNotNull($first['next_cursor']);$next=$this->getJson($url.'&cursor='.urlencode($first['next_cursor']))->assertOk()->json();$this->assertEmpty(array_intersect(array_column($first['items'],'id'),array_column($next['items'],'id')));
  $other=School::where('name','Sekolah B — Demo Simulasi')->firstOrFail();$this->getJson('/api/map/conditions?school='.$other->id.'&cursor='.urlencode($first['next_cursor']))->assertUnprocessable();$this->getJson($url.'&cursor=broken')->assertUnprocessable();$this->getJson('/api/map/conditions')->assertUnprocessable();$this->getJson('/api/map/conditions?school='.$this->school()->id.'&limit=501')->assertUnprocessable();
 }
 public function test_public_payload_has_iso_timestamp_category_no_raw_identity_and_archived_scope_denied():void {
  $h=$this->hazard('payload-map','confirmed',30);$h->reports()->create(['reporter_id'=>User::first()->id,'description'=>'SECRET-report-person','observed_at'=>now()]);$r=$this->getJson('/api/map/conditions?school='.$this->school()->id)->assertOk();$d=collect($r->json('items'))->firstWhere('id','payload-map');$this->assertSame('flood',$d['type']);$this->assertTrue($d['historical']);$this->assertStringContainsString('T',$d['created_at']);foreach(['reporter_id','SECRET-report-person','verifier_id','photo_path','password','contact_email'] as $secret)$r->assertDontSee($secret);
  $this->get('/map?school='.$this->school()->id)->assertOk()->assertSee('condition-map-legend')->assertSee('payload-map');$this->school()->forceFill(['status'=>'archived'])->save();$this->getJson('/api/map/conditions?school='.$this->school()->id)->assertNotFound();
 }
}
