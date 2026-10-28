<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{School,Hazard};
use App\Services\{PublicMapScope,MapConditionService,RouteComparisonService,GlobalFeatureSettings};
use Illuminate\Support\Facades\{DB,Http,Cache};
class CrossCatchmentScopeTest extends TestCase {
 protected function setUp():void {
  parent::setUp();config(['app.key'=>'base64:'.base64_encode(str_repeat('a',32))]);
  $pdo=DB::connection()->getPdo();
  foreach(['ST_Latitude','ST_Y'] as $f)$pdo->sqliteCreateFunction($f,fn($v)=>(float)explode(',',$v)[1],1);
  foreach(['ST_Longitude','ST_X'] as $f)$pdo->sqliteCreateFunction($f,fn($v)=>(float)explode(',',$v)[0],1);
  $pdo->sqliteCreateFunction('ST_Intersects',function($b,$p){[$w,$s,$e,$n]=array_map('floatval',explode(',',$b));[$x,$y]=array_map('floatval',explode(',',$p));return (int)($x>=$w&&$x<=$e&&$y>=$s&&$y<=$n);},2);
  $pdo->sqliteCreateFunction('NOW',fn()=>now()->toDateTimeString(),0);
  $pdo->sqliteCreateFunction('TIMESTAMPDIFF',fn($unit,$a,$b)=>(strtotime($b)-strtotime($a))/60,3);
  // SQLite treats the MySQL unit token as an identifier: provide a constant in this isolated fixture only.
  DB::statement('CREATE TABLE schools (id INTEGER PRIMARY KEY,name TEXT,features TEXT,catchment TEXT,status TEXT)');
  DB::statement('CREATE TABLE hazard_types (id INTEGER PRIMARY KEY,code TEXT,label TEXT,ttl_minutes INTEGER,icon_key TEXT,icon_path TEXT)');
  DB::statement('CREATE TABLE hazards (id INTEGER PRIMARY KEY,type_id INTEGER,location TEXT, severity INTEGER,status TEXT,confidence REAL,publication_control TEXT,requires_moderation INTEGER,published_at TEXT,first_reported_at TEXT,last_confirmed_at TEXT,resolved_at TEXT,public_slug TEXT,location_label TEXT,is_demo INTEGER,created_at TEXT,updated_at TEXT,MINUTE TEXT DEFAULT "MINUTE")');
  DB::statement('CREATE TABLE hazard_reports (id INTEGER PRIMARY KEY,hazard_id INTEGER,reporter_id INTEGER,school_id INTEGER,description TEXT,photo_path TEXT,observed_at TEXT)');
  DB::statement('CREATE TABLE hazard_confirmations (id INTEGER PRIMARY KEY,hazard_id INTEGER,user_id INTEGER,vote TEXT,created_at TEXT)');
  DB::statement('CREATE TABLE verifications (id INTEGER PRIMARY KEY,hazard_id INTEGER,decision TEXT)');
  DB::table('schools')->insert([['id'=>1,'name'=>'MAN','features'=>'{"shared_layer":true}','catchment'=>'110,-7,110.1,-6.9','status'=>'active'],['id'=>2,'name'=>'SMA','features'=>'{"shared_layer":true}','catchment'=>'110.1,-7,110.3,-6.9','status'=>'active']]);
  DB::table('hazard_types')->insert(['id'=>1,'code'=>'road_damage','label'=>'Jalan rusak','ttl_minutes'=>60]);
  foreach(['man','sma','private','rejected','history','off-route'] as $i=>$slug)DB::table('hazards')->insert(['id'=>$i+1,'type_id'=>1,'location'=>($i===0?'110.05':($i===5?'111':'110.2')).',-6.95','severity'=>3,'status'=>$slug==='history'?'expired':'confirmed','publication_control'=>'normal','requires_moderation'=>$slug==='private'?1:0,'published_at'=>now()->toDateTimeString(),'first_reported_at'=>now()->subMinutes(5)->toDateTimeString(),'public_slug'=>$slug,'is_demo'=>0]);
  DB::table('verifications')->insert(['hazard_id'=>4,'decision'=>'rejected']);
  DB::table('hazard_reports')->insert(['hazard_id'=>2,'reporter_id'=>777,'description'=>'SECRET IDENTITY','photo_path'=>'private/path.jpg']);
  app(GlobalFeatureSettings::class)->forget();
 }
 private function feed(array $filters=[]):array {return app(MapConditionService::class)->page(School::findOrFail(1),$filters)['items'];}
 public function test_public_reports_cross_destination_boundary_without_identity_or_private_data():void {
  $data=$this->feed();$this->assertEqualsCanonicalizing(['man','sma','history','off-route'],array_column($data,'id'));$json=json_encode($data);foreach(['SECRET IDENTITY','reporter_id','private/path.jpg','777','rejected'] as $secret)$this->assertStringNotContainsString($secret,$json);
 }
 public function test_global_or_school_switch_off_restores_local_scope():void {
  config(['ruteaman_admin.features.cross_school_public_reports'=>false]);$this->assertSame(['man'],array_column($this->feed(),'id'));
  config(['ruteaman_admin.features.cross_school_public_reports'=>true]);DB::table('schools')->where('id',1)->update(['features'=>'{"shared_layer":true,"cross_school_public_reports":false}']);$this->assertSame(['man'],array_column($this->feed(),'id'));
 }
 public function test_pagination_cursor_cannot_cross_viewport_or_switch_scope():void {
  $service=app(MapConditionService::class);$s=School::find(1);$first=$service->page($s,['limit'=>1]);$this->assertNotNull($first['next_cursor']);$second=$service->page($s,['limit'=>1,'cursor'=>$first['next_cursor']]);$this->assertNotSame($first['items'][0]['id'],$second['items'][0]['id']);
  $this->expectException(\Illuminate\Validation\ValidationException::class);$service->page($s,['bbox'=>'110.1,-7,110.3,-6.9','cursor'=>$first['next_cursor']]);
 }
 public function test_route_corridor_keeps_active_cross_school_only():void {
  $service=app(RouteComparisonService::class);$method=new \ReflectionMethod($service,'hazardsAlong');$g=['type'=>'LineString','coordinates'=>[[110.04,-6.95],[110.25,-6.95]]];$rows=$method->invoke($service,$g,1);$this->assertEqualsCanonicalizing(['man','sma'],$rows->pluck('public_slug')->all());config(['ruteaman_admin.features.cross_school_public_reports'=>false]);$rows=$method->invoke($service,$g,1);$this->assertSame(['man'],$rows->pluck('public_slug')->all());
 }
 public function test_invalid_bbox_and_antimeridian():void {
  $scope=app(PublicMapScope::class);$this->assertSame([170.0,-10.0,-170.0,10.0],$scope->bounds('170,-10,-170,10'));$this->expectException(\Illuminate\Validation\ValidationException::class);$scope->bounds('181,0,10,20');
 }
 public function test_ttl_and_revoked_publication_are_excluded_from_route():void {
  DB::table('hazards')->where('public_slug','sma')->update(['first_reported_at'=>now()->subDay()->toDateTimeString()]);$g=['type'=>'LineString','coordinates'=>[[110.04,-6.95],[110.25,-6.95]]];$m=new \ReflectionMethod(app(RouteComparisonService::class),'hazardsAlong');$rows=$m->invoke(app(RouteComparisonService::class),$g,1);$this->assertSame(['man'],$rows->pluck('public_slug')->all());
  DB::table('hazards')->where('public_slug','man')->update(['publication_control'=>'suspended']);$this->assertEmpty($m->invoke(app(RouteComparisonService::class),$g,1)->all());$this->assertNotContains('man',array_column($this->feed(),'id'));$this->assertContains('sma',array_column($this->feed(),'id'));
 }
 public function test_school_admin_scope_is_not_expanded_by_public_sharing():void {
  $u=new \App\Models\User();$u->forceFill(['role'=>'school_admin','school_id'=>1]);$p=app(\App\Services\PermissionService::class);$this->assertTrue(app(PublicMapScope::class)->shared(School::find(1)));$this->assertTrue($p->inScope($u,1));$this->assertFalse($p->inScope($u,2));
 }
 public function test_viewport_sql_is_bound_and_handles_dateline():void {
  $q=app(PublicMapScope::class)->viewport(Hazard::query(),[170,-10,-170,10]);$this->assertStringContainsString('(ST_Longitude(hazards.location) >= ? OR ST_Longitude(hazards.location) <= ?)',$q->toSql());$this->assertSame([-10,10,170,-170],$q->getBindings());
 }
 public function test_cursor_invalid_after_switch_change():void {
  $service=app(MapConditionService::class);$s=School::find(1);$first=$service->page($s,['limit'=>1]);config(['ruteaman_admin.features.cross_school_public_reports'=>false]);$this->expectException(\Illuminate\Validation\ValidationException::class);$service->page($s,['limit'=>1,'cursor'=>$first['next_cursor']]);
 }
 public function test_campus_directory_safe_payload_and_outage():void {
  Http::preventStrayRequests();Http::fake(['api.ror.org/*'=>Http::response(['number_of_results'=>1,'items'=>[['id'=>'https://ror.org/012345678','status'=>'active','types'=>['education'],'names'=>[['value'=>'Kampus QA','types'=>['ror_display']]],'locations'=>[['geonames_details'=>['lat'=>-6.95,'lng'=>110.2,'name'=>'Kota QA','country_code'=>'ID']]]]]])]);
  $this->getJson('/api/campus-directory?query=Kampus&country=ID')->assertOk()->assertJsonPath('data.0.requires_destination_pin',true)->assertJsonPath('data.0.coordinate_accuracy','city_reference_only');
  Http::assertSent(fn($r)=>$r['filter']==='types:education,status:active,locations.geonames_details.country_code:ID');
 }
 public function test_map_template_campus_controls():void {
  $schools=collect([(object)['id'=>1,'name'=>'MAN QA','latitude'=>-6.95,'longitude'=>110.05,'npsn'=>'00000001','status'=>'active','features'=>'{}','catchment_json'=>'null']]);
  $v=$this->view('map',['schools'=>$schools,'school'=>$schools->first(),'hazards'=>collect([]),'markerPage'=>['items'=>[]]]);$v->assertSee('campus-query')->assertSee('campus-pin')->assertSee('belum mencakup semua kampus');if($out=getenv('RUTEAMAN_QA_OUTPUT_DIR')){if(!is_dir($out))mkdir($out,0770,true);file_put_contents(rtrim($out,'/').'/crossarea-map-preview.html',(string)$v);}
 }
 public function test_campus_directory_outage():void {
  Http::preventStrayRequests();Http::fake(['api.ror.org/*'=>Http::response([],503)]);$this->getJson('/api/campus-directory?query=TidakAda')->assertStatus(503)->assertJsonPath('error.code','CAMPUS_DIRECTORY_UNAVAILABLE');
 }
}
