<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Support\CampusFixture;
use App\Models\{School,User,Hazard,HazardReport};
use App\Services\{GuestReportService,PermissionService,SchoolProvisioningService};
use Illuminate\Support\Facades\{DB,Http};
class CampusIntegrationTest extends TestCase {
 use CampusFixture;
 protected function setUp():void {parent::setUp();$this->initializeCampusFixture();}
 private function account(array $extra=[]):array {return array_replace(['institution_type'=>'campus','campus_id'=>2,'display_name'=>'Mahasiswa QA','email'=>'campus.member@example.test','password'=>'CampusSafe!7290','password_confirmation'=>'CampusSafe!7290','parental_consent'=>'1'],$extra);}
 private function report():array {return ['school_id'=>2,'institution_type'=>'campus','type_id'=>1,'latitude'=>-7,'longitude'=>110.2,'guest_name'=>'PRIVATE-GUEST-NAME','description'=>'PRIVATE-RAW-DESCRIPTION','report_consent'=>1];}
 public function test_migration_preserves_existing_school_and_does_not_fake_npsn_for_campus():void {$this->assertSame('school',School::find(1)->institution_type);$this->assertSame('11111111',School::find(1)->npsn);$this->assertSame('campus',School::find(2)->institution_type);$this->assertNull(School::find(2)->npsn);}
 public function test_campus_signup_has_no_npsn_region_or_admin_privilege():void {$this->post('/register',$this->account(['role'=>'owner','permissions'=>['*']]))->assertRedirect(route('map',['school'=>2]));$u=User::where('email','campus.member@example.test')->firstOrFail();$this->assertSame(2,$u->school_id);$this->assertSame(\App\Enums\Role::Student,$u->role);$this->assertAuthenticatedAs($u);Http::assertNothingSent();}
 public function test_campus_signup_rejects_school_paused_and_archived_ids():void {foreach([1,3,4] as $id)$this->post('/register',$this->account(['campus_id'=>$id]))->assertSessionHasErrors('campus_id');$this->assertSame(0,User::count());}
 public function test_school_branch_still_requires_npsn_and_regions():void {$this->post('/register',$this->account(['institution_type'=>'school']))->assertSessionHasErrors(['school_npsn','province_code','regency_code','district_code']);$this->assertSame(0,User::count());}
 public function test_register_form_offers_only_active_campuses():void {$r=$this->get('/register?institution_type=campus')->assertOk()->assertSee('Kampus QA 2')->assertSee('Kampus QA 5')->assertDontSee('Kampus QA 3')->assertDontSee('Kampus QA 4');$this->saveCampusPreview('register-preview.html',$r->getContent());}
 public function test_campus_guest_search_only_returns_registered_reporting_active_campuses():void {$r=$this->getJson('/lapor?school_query=Kampus&institution_type=campus')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.id',2)->assertJsonPath('data.0.institution_type','campus');$r->assertDontSee('PRIVATE');Http::assertNothingSent();}
 public function test_campus_guest_selection_returns_actual_admin_pin_without_ticket_or_import():void {$this->getJson('/lapor?institution_id=2&institution_type=campus')->assertOk()->assertJsonPath('data.latitude',-7)->assertJsonPath('data.longitude',110.2)->assertJsonMissingPath('ticket');$this->getJson('/lapor?institution_id=3&institution_type=campus')->assertStatus(422);Http::assertNothingSent();}
 public function test_guest_campus_form_lists_registered_campuses_and_school():void {$r=$this->get('/lapor?school=2')->assertOk()->assertSee('Kampus QA 2')->assertSee('Sekolah QA')->assertSee('guest-institution-type')->assertDontSee('Kampus QA 3')->assertDontSee('Kampus QA 5');$this->saveCampusPreview('guest-preview.html',$r->getContent());}
 public function test_guest_report_stores_campus_id_and_respects_moderation():void {$s=School::find(2);$s->features=array_replace($s->features,['require_guest_review'=>true]);$s->save();$result=app(GuestReportService::class)->submit($this->report(),null);$this->assertFalse($result['public']);$h=Hazard::firstOrFail();$this->assertTrue($h->requires_moderation);$this->assertNull($h->published_at);$this->assertSame(2,HazardReport::firstOrFail()->school_id);$this->assertFalse(app(PermissionService::class)->inScope((new User)->forceFill(['role'=>'school_admin','school_id'=>1]),2));}
 public function test_guest_report_rejects_point_outside_registered_campus():void {$d=$this->report();$d['longitude']=112;$this->expectException(\Illuminate\Validation\ValidationException::class);app(GuestReportService::class)->submit($d,null);}
 public function test_guest_http_submission_persists_campus_and_public_feed_excludes_identity():void {
  $c=School::find(2);$c->features=array_replace($c->features,['require_guest_review'=>false]);$c->save();$token=str_repeat('x',40);
  $this->withSession(['guest_report_form'=>['token'=>$token,'started'=>now()->subSeconds(3)->timestamp]])->post('/lapor',[...$this->report(),'_form_token'=>$token])->assertRedirect(route('guest-reports.receipt'));
  $this->assertSame(2,HazardReport::firstOrFail()->school_id);$json=json_encode(app(\App\Services\MapConditionService::class)->page(School::find(2),[])['items']);$this->assertStringNotContainsString('PRIVATE-GUEST-NAME',$json);$this->assertStringNotContainsString('PRIVATE-RAW-DESCRIPTION',$json);$this->assertStringNotContainsString('reporter_id',$json);
 }
 public function test_campus_selection_works_in_existing_map_controller_and_summary():void {
  $this->get('/map?school=2')->assertOk()->assertSee('Kampus QA 2')->assertSee('institution_type');$this->assertSame(2,app(\App\Services\ConditionFeedService::class)->school('kampus-qa-2')->id);$this->assertSame(0,app(\App\Services\ConditionFeedService::class)->summary(School::find(2))['total']);
 }
 public function test_admin_provision_creates_campus_in_existing_permission_scope():void {
  $owner=(new User)->forceFill(['role'=>'owner','display_name'=>'Owner QA','email'=>'owner.qa@example.test','password'=>'OwnerSafe!7290','active'=>true,'status'=>'active','must_change_password'=>false]);$owner->save();
  $d=['institution_type'=>'campus','country_code'=>'GB','campus_code'=>'QA-GLOBAL','name'=>'Kampus Baru QA','public_slug'=>'kampus-baru-qa','latitude'=>51.5,'longitude'=>-0.1,'catchment'=>json_encode(['type'=>'Polygon','coordinates'=>[[[-.105,51.495],[-.095,51.495],[-.095,51.505],[-.105,51.505],[-.105,51.495]]]]),'timezone'=>'Europe/London','features'=>config('ruteaman_admin.features'),'display_name'=>'Pengelola Kampus','role'=>'school_admin','email'=>'qakampus.pengelola@'.config('ruteaman_admin.alias_domain'),'contact_email'=>'campus.office@example.test','creation_method'=>'password_set','initial_password'=>'SafeRoute!729AlphaZ','must_change_password'=>false,'permissions'=>[]];
  $result=app(SchoolProvisioningService::class)->create($owner,$d);$this->assertSame('campus',$result['school']->institution_type);$this->assertSame('GB',$result['school']->country_code);$this->assertSame('Europe/London',$result['school']->timezone);$this->assertNull($result['school']->npsn);$this->assertSame($result['school']->id,$result['user']->school_id);
 }
 public function test_campus_guest_cannot_use_school_directory_ticket():void {
  $token=str_repeat('x',40);$d=$this->report();unset($d['school_id']);$this->withSession(['guest_report_form'=>['token'=>$token,'started'=>now()->subSeconds(3)->timestamp]])->post('/lapor',[...$d,'school_ticket'=>'forged-school-ticket','_form_token'=>$token])->assertSessionHasErrors('school_ticket');$this->assertSame(0,Hazard::count());Http::assertNothingSent();
 }
 public function test_institution_type_cannot_change_on_admin_update():void {
  $req=\App\Http\Requests\AdminMutationRequest::create('/admin/schools/2','PATCH',['institution_type'=>'school']);$route=new \Illuminate\Routing\Route('PATCH','admin/schools/{school}',fn()=>null);$route->name('admin.schools.update');$route->bind($req);$route->setParameter('school',School::find(2));$req->setRouteResolver(fn()=>$route);$rules=$req->rules();$v=\Illuminate\Support\Facades\Validator::make($req->all(),['institution_type'=>$rules['institution_type']]);$this->assertTrue($v->fails());
 }
 public function test_shared_dropdowns_and_home_summary_render_campus():void {
  $schools=School::where('status','!=','archived')->get()->map(function($s){$s=(object)$s->getAttributes();$s->latitude=-7;$s->longitude=$s->id===1?110:110.2;$s->catchment_json=null;return $s;});$s=$schools->firstWhere('id',2);
  $map=$this->view('map',['schools'=>$schools,'school'=>$s,'hazards'=>collect([]),'markerPage'=>['items'=>[]]]);$map->assertSee('Kampus QA 2')->assertSee('optgroup label="'.__('Kampus').'"',false);$this->saveCampusPreview('map-preview.html',(string)$map);
  $home=$this->view('pages.home-featured',['schools'=>$schools,'school'=>$s,'summary'=>['total'=>0,'categories'=>[]],'featured'=>['hazard'=>null,'rotate_in_seconds'=>30,'position'=>0,'total_active'=>0,'generated_at'=>now()->toIso8601String()]]);$home->assertSee('Kampus QA 2')->assertSee('kampus-qa-2')->assertSee(__('Ringkasan area institusi'))->assertSee(route('guest-reports.create',['school'=>2]))->assertSee(route('map',['school'=>2]));
  $this->saveCampusPreview('home-preview.html',(string)$this->view('landing',['errors'=>new \Illuminate\Support\ViewErrorBag(),'schools'=>$schools,'school'=>$s,'summary'=>['total'=>0,'categories'=>[]],'featured'=>['hazard'=>null,'rotate_in_seconds'=>30,'position'=>0,'total_active'=>0,'generated_at'=>now()->toIso8601String()]]));
 }
}
