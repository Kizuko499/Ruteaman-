<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Services\{SpatialSql,SchoolProvisioningService,RouteComparisonService};
use App\Models\{Hazard,HazardType,School,User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{DB,Cache};
class MariaDbCompatibilityTest extends TestCase {
 use DatabaseTransactions;
 protected function setUp():void {if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Dedicated spatial database required.');parent::setUp();}
 public function test_wgs84_point_roundtrip_and_spatial_scope():void {
  $p=SpatialSql::point(106.825,-6.178);$expr=$p->getValue(DB::connection()->getQueryGrammar());
  $row=DB::selectOne("SELECT ST_X($expr) AS lng,ST_Y($expr) AS lat,ST_SRID($expr) AS srid");
  $this->assertEqualsWithDelta(106.825,$row->lng,.000001);$this->assertEqualsWithDelta(-6.178,$row->lat,.000001);$this->assertSame(4326,(int)$row->srid);
 }
 public function test_route_buffer_filters_in_metres_before_limit():void {
  $this->seed(\Database\Seeders\DemoSeeder::class);$s=School::where('name','Sekolah A — Demo Simulasi')->firstOrFail();$type=HazardType::where('code','pothole')->value('id');
  foreach([['qa-route-near',106.82525],['qa-route-far',106.826]] as [$slug,$lng])DB::table('hazards')->insert(['type_id'=>$type,'location'=>SpatialSql::point($lng,-6.178),'severity'=>2,'status'=>'confirmed','published_at'=>now(),'confidence'=>.5,'first_reported_at'=>now(),'last_confirmed_at'=>now(),'public_slug'=>$slug,'created_at'=>now(),'updated_at'=>now()]);
  config(['ruteaman.route_buffer_meters'=>50,'ruteaman.route_hazard_limit'=>20]);Cache::flush();
  $routes=app(RouteComparisonService::class)->saved($s->id)['routes'];$slugs=array_column($routes[0]['hazards'],'public_slug');
  $this->assertContains('qa-route-near',$slugs);$this->assertNotContains('qa-route-far',$slugs);
  config(['ruteaman.route_hazard_limit'=>1]);$slugs=array_column(app(RouteComparisonService::class)->saved($s->id)['routes'][0]['hazards'],'public_slug');
  $this->assertSame(['qa-route-near'],$slugs);
 }
 public function test_provision_and_update_school_polygon_roundtrip():void {
  $owner=(new User)->forceFill(['display_name'=>'DB QA Owner','email'=>'db-chief@ruteaman.my.id','contact_email'=>'db-chief@example.test','password'=>'FixturePasswordOnly123!','role'=>'owner','status'=>'active','active'=>true]);$owner->save();
  $geo=['type'=>'Polygon','coordinates'=>[[[106.82,-6.18],[106.84,-6.18],[106.84,-6.16],[106.82,-6.16],[106.82,-6.18]]]];
  $d=['name'=>'MariaDB School','public_slug'=>'maria-school','bmkg_area_code'=>null,'timezone'=>'Asia/Jakarta','longitude'=>106.83,'latitude'=>-6.17,'catchment'=>$geo,'display_name'=>'Pengelola QA','email'=>'maria.pengelola@ruteaman.my.id','contact_email'=>'maria@example.test','role'=>'school_admin','permissions'=>[]];
  $result=app(SchoolProvisioningService::class)->create($owner,$d);$s=$result['school'];
  $row=DB::table('schools')->where('id',$s->id)->selectRaw(SpatialSql::coordinates())->selectRaw('ST_AsGeoJSON(catchment) AS geo')->first();
  $this->assertEqualsWithDelta(106.83,$row->longitude,.000001);$this->assertSame('Polygon',json_decode($row->geo,true)['type']);
  $this->assertSame('pending',$result['user']->status);$this->assertSame($s->id,$result['user']->school_id);
  app(SchoolProvisioningService::class)->update($owner,$s,[...$d,'longitude'=>106.831]);
  $this->assertEqualsWithDelta(106.831,DB::table('schools')->where('id',$s->id)->selectRaw('ST_X(location) AS lng')->first()->lng,.000001);
 }
}
