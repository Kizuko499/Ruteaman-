<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Support\CampusFixture;
use App\Models\{CampusCatalogEntry,School,User};
use App\Services\CampusCatalogLink;
class CampusCatalogLinkTest extends TestCase {
 use CampusFixture;
 protected function setUp():void{parent::setUp();$this->initializeCampusFixture();$m=require base_path('database/migrations/2026_10_14_000000_create_campus_catalog.php');$m->up();}
 private function entry():CampusCatalogEntry{return CampusCatalogEntry::create(['source_key'=>'ror:012345678','name'=>'Universitas Bina Nusantara QA','country_code'=>'ID','source'=>'ror','source_url'=>'https://ror.org/012345678','aliases'=>['BINUS QA'],'search_text'=>'universitas bina nusantara qa binus qa','coordinate_accuracy'=>'city_reference_only','source_status'=>'active_in_ror','data_release'=>'QA']);}
 private function user(string $role='owner',array $more=[]):User{$u=(new User)->forceFill(array_replace(['display_name'=>'QA User','email'=>$role.'@example.test','password'=>'FixtureSafe!7290','role'=>$role,'active'=>true,'status'=>'active','must_change_password'=>false,'locale'=>'id'],$more));$u->save();return $u;}
 public function test_link_preserves_campus_geometry_accounts_features_and_import_identity():void{
  $e=$this->entry();$owner=$this->user();$before=School::find(2)->getAttributes();$users=User::count();$schools=School::count();
  app(CampusCatalogLink::class)->connect($owner,$e,2,'Identitas dan cabang sudah diperiksa.');
  $this->assertSame(2,$e->fresh()->school_id);$this->assertSame($before,School::find(2)->getAttributes());$this->assertSame($users,User::count());$this->assertSame($schools,School::count());$this->assertDatabaseHas('audit_logs',['action'=>'school.catalog_linked','school_id'=>2]);
  $this->getJson('/api/reporting-campuses?school_query=BINUS')->assertOk()->assertJsonPath('data.0.id',2);$this->getJson('/api/campus-catalog?query=BINUS')->assertOk()->assertJsonPath('data.0.school_id',2)->assertJsonPath('data.0.can_link',false);
  app(CampusCatalogLink::class)->connect($owner,$e,2,'Tindakan ulang identik.');$this->assertSame(1,\Illuminate\Support\Facades\DB::table('audit_logs')->where('action','school.catalog_linked')->count());
 }
 public function test_wrong_type_status_country_and_conflicting_codes_are_rejected():void{
  $e=$this->entry();$owner=$this->user();foreach([1,3,4,5] as $id){try{app(CampusCatalogLink::class)->connect($owner,$e,$id,'Pemeriksaan QA.');$this->fail('Wrong target linked');}catch(\Illuminate\Validation\ValidationException){$this->assertNull($e->fresh()->school_id);}}
  $e->update(['official_code'=>'DIFFERENT']);try{app(CampusCatalogLink::class)->connect($owner,$e,2,'Pemeriksaan QA.');$this->fail('Wrong code linked');}catch(\Illuminate\Validation\ValidationException){$this->assertNull($e->fresh()->school_id);}
 }
 public function test_http_link_requires_admin_scope_confirmation_and_reauth():void{
  $e=$this->entry();$this->get(route('admin.campus-catalog.connect',$e))->assertRedirect(route('login'));
  $owner=$this->user();$this->actingAs($owner)->withSession(['admin.reauth_at'=>0,'admin.last_seen'=>time()]);
  $this->postJson(route('admin.campus-catalog.connect.store',$e),['school_id'=>2,'identity_confirmed'=>1,'reason'=>'Identitas diperiksa.'])->assertStatus(428);
  $this->withSession(['admin.reauth_at'=>time()])->postJson(route('admin.campus-catalog.connect.store',$e),['school_id'=>2,'reason'=>'Identitas diperiksa.'])->assertUnprocessable();
  $form=$this->get(route('admin.campus-catalog.connect',$e))->assertOk()->assertSee('Kampus QA 2')->assertDontSee('Kampus QA 3')->assertDontSee('Kampus QA 5');$this->saveCampusPreview('connect-preview.html',$form->getContent());
  $this->post(route('admin.campus-catalog.connect.store',$e),['school_id'=>2,'identity_confirmed'=>1,'reason'=>'Identitas dan cabang diperiksa.'])->assertRedirect();$this->assertSame(2,$e->fresh()->school_id);
  $restricted=$this->user('platform_admin',['school_scope'=>[2],'permissions'=>array_keys(config('ruteaman_permissions'))]);$this->actingAs($restricted)->get(route('admin.campus-catalog.connect',$e))->assertForbidden();
 }
 public function test_map_directory_link_is_inside_the_selection_panel():void{
  $html=$this->withCookie('ruteaman_locale','id')->get('/map?school=2')->assertOk()->getContent();$this->assertStringContainsString('map-campus-discovery',$html);$this->assertStringNotContainsString('class="text-link catalog-entry-link"',$html);$this->saveCampusPreview('map-preview.html',$html);
 }
 public function test_existing_link_is_never_overwritten():void{
  $e=$this->entry();$e->update(['school_id'=>2]);$owner=$this->user();try{app(CampusCatalogLink::class)->connect($owner,$e,5,'Permintaan penggantian ditolak.');$this->fail('Link overwritten');}catch(\Illuminate\Validation\ValidationException){$this->assertSame(2,$e->fresh()->school_id);}
 }
}
