<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Support\CampusFixture;
use App\Models\CampusCatalogEntry;
use Illuminate\Support\Facades\Http;
class CampusSearchUiTest extends TestCase {
 use CampusFixture;
 protected function setUp():void {parent::setUp();$this->initializeCampusFixture();$m=require base_path('database/migrations/2026_10_14_000000_create_campus_catalog.php');$m->up();}
 public function test_dedicated_endpoint_never_returns_national_schools_even_with_wrong_kind():void {
  $this->getJson('/api/reporting-campuses?school_query=Sekolah&institution_type=school')->assertOk()->assertJsonCount(0,'data');
  $this->getJson('/api/reporting-campuses?school_query=Kampus')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.institution_type','campus');
  $this->getJson('/api/reporting-campuses?institution_id=1')->assertStatus(422);
  $this->getJson('/api/reporting-campuses?school_npsn=11111111')->assertStatus(422);Http::assertNothingSent();
 }
 public function test_active_registered_campus_is_searchable_by_code_and_linked_alias():void {
  CampusCatalogEntry::create(['source_key'=>'qa:alias','name'=>'Universitas QA','country_code'=>'ID','source'=>'ror','source_url'=>'https://ror.org/012345678','aliases'=>['BINUS QA'],'search_text'=>'universitas qa binus qa','coordinate_accuracy'=>'unavailable','source_status'=>'test','data_release'=>'QA','school_id'=>2]);
  $this->getJson('/api/reporting-campuses?school_query=BINUS')->assertOk()->assertJsonPath('data.0.id',2);
  CampusCatalogEntry::first()->update(['school_id'=>null,'official_code'=>'C-2']);
  $this->getJson('/api/reporting-campuses?school_query=BINUS')->assertOk()->assertJsonPath('data.0.id',2);
  $this->getJson('/api/reporting-campuses?school_query=C-2')->assertOk()->assertJsonPath('data.0.id',2);
  $this->getJson('/api/reporting-campuses?school_query=%25%25%25')->assertOk()->assertJsonCount(0,'data');
 }
 public function test_catalog_and_report_pages_load_independent_versioned_style_and_safe_search_endpoint():void {
  $r=$this->get('/kampus')->assertOk()->assertSee('css/campus-directory-v4.css')->assertSee('Temukan kampus tujuanmu');$this->saveCampusPreview('catalog-empty.html',$r->getContent());
  $r=$this->get('/lapor?school=2')->assertOk()->assertSee('data-campus-url=',false)->assertSee('/api/reporting-campuses')->assertSee('guest-search-assets-warning');$this->saveCampusPreview('guest-preview.html',$r->getContent());
 }
}
