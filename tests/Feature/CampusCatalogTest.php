<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Support\CampusFixture;
use App\Models\{CampusCatalogEntry,School,User};
use App\Services\{CampusCatalogImport,SchoolProvisioningService};
use Illuminate\Support\Facades\DB;
class CampusCatalogTest extends TestCase {
 use CampusFixture;
 protected function setUp():void {parent::setUp();$this->initializeCampusFixture();$m=require base_path('database/migrations/2026_10_14_000000_create_campus_catalog.php');$m->up();}
 private function record(array $extra=[]):array {return array_replace(['source_key'=>'pddikti:001999','name'=>'Universitas QA','country_code'=>'ID','official_code'=>'001999','aliases'=>['Kampus QA'],'source'=>'pddikti','source_url'=>'https://data.kemdiktisaintek.go.id/dataset/data-jumlah-perguruan-tinggi-dan-status-akreditasi-pada-tahun-2025','city'=>'Kota QA','region'=>'Provinsi QA','reference_latitude'=>null,'reference_longitude'=>null,'coordinate_accuracy'=>'unavailable','source_status'=>'status_operasional_tidak_disediakan','data_release'=>'QA fixture'],$extra);}
 private function import(array $records,int $expected=-1):int {
  $f=tempnam(sys_get_temp_dir(),'campus-qa-');$meta=$f.'.json';$h=gzopen($f,'wb');foreach($records as $r)gzwrite($h,json_encode($r)."\n");gzclose($h);file_put_contents($meta,json_encode(['format'=>'ruteaman-campus-catalog-v1','sha256'=>hash_file('sha256',$f),'row_count'=>$expected<0?count($records):$expected]));try{return app(CampusCatalogImport::class)->run($f,$meta);}finally{unlink($f);unlink($meta);}
 }
 public function test_full_real_snapshot_import_has_verified_counts_and_searches():void {
  $meta=json_decode(file_get_contents(resource_path('data/campus-catalog-v3.metadata.json')),true);$this->assertSame(33742,app(CampusCatalogImport::class)->run(resource_path('data/campus-catalog-v3.jsonl.gz'),resource_path('data/campus-catalog-v3.metadata.json')));$this->assertSame($meta['row_count'],CampusCatalogEntry::count());$this->assertSame(3568,CampusCatalogEntry::where('source','pddikti')->count());$this->assertSame(30174,CampusCatalogEntry::where('source','ror')->count());
  $this->getJson('/api/campus-catalog?query=Diponegoro&country=ID')->assertOk()->assertSee('Diponegoro');$this->getJson('/api/campus-catalog?query=BINUS&country=ID')->assertOk()->assertSee('Universitas Bina Nusantara');$this->saveCampusPreview('catalog-all.html',$this->get('/kampus?country=ID')->assertOk()->getContent());$this->saveCampusPreview('catalog-page2.html',$this->get('/kampus?country=ID&page=2')->assertOk()->getContent());$this->getJson('/api/campus-catalog?query=Cambridge&country=GB')->assertOk()->assertSee('Cambridge');$this->getJson('/api/campus-catalog?query=%25&country=')->assertOk()->assertJsonCount(0,'data');$this->assertSame(5,School::count());$this->assertSame(0,User::count());
  $view=$this->get('/kampus?query=Diponegoro&country=ID')->assertOk()->assertSee('Diponegoro')->assertSee(__('Entri katalog ini belum terhubung ke wilayah RuteAman. Pengelola perlu memeriksa institusi yang sudah ada, pin, catchment, dan fitur sebelum menerima akun/laporan.'));$this->saveCampusPreview('catalog-preview.html',$view->getContent());
 }
 public function test_import_is_idempotent_preserves_registration_link_and_account_state():void {
  $this->assertSame(1,$this->import([$this->record()]));$e=CampusCatalogEntry::firstOrFail();$e->school_id=2;$e->save();$this->assertSame(1,$this->import([$this->record(['name'=>'Universitas QA Updated'])]));$this->assertSame(1,CampusCatalogEntry::count());$this->assertSame(2,$e->fresh()->school_id);$this->assertSame(5,School::count());
  $this->getJson('/api/campus-catalog?query=QA')->assertOk()->assertJsonPath('data.0.school_id',2)->assertJsonPath('data.0.register_url',route('register',['institution_type'=>'campus','campus_id'=>2]));School::find(2)->forceFill(['status'=>'paused'])->save();$this->getJson('/api/campus-catalog?query=QA')->assertJsonPath('data.0.register_url',null)->assertJsonPath('data.0.report_url',null);
 }
 public function test_corrupt_or_incomplete_snapshot_rolls_back_every_record():void {
  $rows=[];for($i=0;$i<300;$i++)$rows[]=$this->record(['source_key'=>'pddikti:'.(600000+$i),'official_code'=>(string)(600000+$i)]);$rows[]=$this->record(['source_key'=>'pddikti:999999','official_code'=>'999999','source_url'=>'https://evil.example/']);try{$this->import($rows);$this->fail('Invalid source accepted');}catch(\RuntimeException){$this->assertSame(0,CampusCatalogEntry::count());}
  try{$this->import([$this->record()],2);$this->fail('Wrong count accepted');}catch(\RuntimeException){$this->assertSame(0,CampusCatalogEntry::count());}
 }
 public function test_country_selection_and_pagination_do_not_mix_entries():void {
  $rows=[];for($i=0;$i<22;$i++)$rows[]=$this->record(['source_key'=>'pddikti:'.(700000+$i),'official_code'=>(string)(700000+$i),'name'=>'Universitas QA '.str_pad((string)$i,2,'0',STR_PAD_LEFT)]);$rows[]=$this->record(['source_key'=>'ror:012345678','source'=>'ror','source_url'=>'https://ror.org/012345678','name'=>'University QA Global','country_code'=>'GB']);$this->import($rows);
  $first=$this->getJson('/api/campus-catalog')->assertJsonCount(20,'data')->assertJsonPath('country','ID')->json();$second=$this->getJson('/api/campus-catalog?page=2')->assertJsonCount(2,'data')->json();$this->assertEmpty(array_intersect(array_column($first['data'],'id'),array_column($second['data'],'id')));$this->getJson('/api/campus-catalog?country=GB')->assertJsonCount(1,'data');$this->getJson('/api/campus-catalog?country=&query=QA')->assertJsonPath('total_source_entries',23);
 }
 public function test_activation_uses_catalog_identity_but_admin_supplied_pin_and_permissions():void {
  $this->import([$this->record()]);$e=CampusCatalogEntry::firstOrFail();$owner=(new User)->forceFill(['role'=>'owner','display_name'=>'Owner QA','email'=>'catalog.owner@example.test','password'=>'OwnerSafe!7290','active'=>true,'status'=>'active','must_change_password'=>false]);$owner->save();
  $d=['catalog_id'=>$e->id,'institution_type'=>'campus','country_code'=>'GB','name'=>'Forged name','public_slug'=>'catalog-campus-qa','latitude'=>-7,'longitude'=>110.2,'catchment'=>json_encode(['type'=>'Polygon','coordinates'=>[[[110.195,-7.005],[110.205,-7.005],[110.205,-6.995],[110.195,-6.995],[110.195,-7.005]]]]),'timezone'=>'Asia/Jakarta','features'=>config('ruteaman_admin.features'),'display_name'=>'Pengelola Kampus','role'=>'school_admin','email'=>'catalog.pengelola@'.config('ruteaman_admin.alias_domain'),'contact_email'=>'office@example.test','creation_method'=>'password_set','initial_password'=>'SafeRoute!729AlphaZ','must_change_password'=>false,'permissions'=>[]];
  $result=app(SchoolProvisioningService::class)->create($owner,$d);$this->assertSame('Universitas QA',$result['school']->name);$this->assertSame('ID',$result['school']->country_code);$this->assertSame('001999',$result['school']->campus_code);$this->assertSame($result['school']->id,$e->fresh()->school_id);$this->assertSame($result['school']->id,$result['user']->school_id);
  $count=School::count();try{app(SchoolProvisioningService::class)->create($owner,$d);$this->fail('Duplicate activation accepted');}catch(\Illuminate\Validation\ValidationException){$this->assertSame($count,School::count());}
 }
 public function test_existing_campus_matches_only_unique_official_code_and_country():void {
  $this->import([$this->record()]);School::find(2)->forceFill(['campus_code'=>'001999'])->save();$this->getJson('/api/campus-catalog?query=QA')->assertJsonPath('data.0.school_id',2)->assertJsonPath('data.0.can_activate',false);$this->assertNull(CampusCatalogEntry::first()->school_id);
  School::find(5)->forceFill(['campus_code'=>'001999','country_code'=>'ID'])->save();$this->getJson('/api/campus-catalog?query=QA')->assertJsonPath('data.0.school_id',null);
 }
 public function test_import_command_runs_bundled_snapshot_and_missing_file_fails_cleanly():void {
  $this->artisan('ruteaman:campus-catalog-import')->expectsOutputToContain('33742')->assertExitCode(0);$this->assertSame(33742,CampusCatalogEntry::count());$this->artisan('ruteaman:campus-catalog-import',['--file'=>'/nonexistent/catalog.gz'])->assertExitCode(1);$this->assertSame(33742,CampusCatalogEntry::count());
 }
 public function test_catalog_listing_works_with_javascript_disabled():void {$this->import([$this->record()]);$this->get('/kampus')->assertOk()->assertSee('Universitas QA')->assertSee('Kode sumber:')->assertSee('001999')->assertDontSee('class="btn btn-soft" href="'.route('register',['institution_type'=>'campus','campus_id'=>2]).'"',false);}
}
