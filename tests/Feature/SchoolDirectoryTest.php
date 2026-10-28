<?php
namespace Tests\Feature;
use App\Services\DapodikSchoolDirectory;
use Illuminate\Support\Facades\{Cache,DB,Http};
use Tests\TestCase;
class SchoolDirectoryTest extends TestCase {
 protected function setUp():void {
  parent::setUp();config(['app.key'=>'base64:'.base64_encode(str_repeat('s',32)),'cache.default'=>'array','services.dapodik.token'=>'public-fixture-token']);
  Cache::put('school.directory.online-provinces.v1',[['code'=>'33','name'=>'Jawa Tengah']]);
  Cache::put('school.directory.online-regencies.v1.33',[['code'=>'33.17','name'=>'Kabupaten Rembang']]);
  Cache::put('school.directory.online-districts.v1.33.17',[['code'=>'33.17.14','name'=>'Lasem'],['code'=>'33.17.13','name'=>'Pancur']]);
  Http::preventStrayRequests();
 }
 private function fixture():array{return json_decode(file_get_contents(__DIR__.'/../Fixtures/man-2-rembang.json'),true);}
 private function search(string $query='20363192',string $district='33.17.14'):array{return app(DapodikSchoolDirectory::class)->search($query,'33','33.17',$district);}
 public function test_empty_dapodik_uses_madrasah_fallback():void {
  Cache::put('dapodik.directory.search.'.sha1('20363192|33|33.17'),[]);
  Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>Http::response(['data'=>[]]),'https://sekolah.devapi.id/*'=>Http::response($this->fixture())]);
  $result=$this->search();$this->assertCount(1,$result);$this->assertSame('MAN 2 REMBANG',$result[0]['name']);$this->assertSame('20363192',$result[0]['npsn']);
  Http::assertSent(fn($r)=>str_starts_with($r->url(),'https://sekolah.devapi.id/sekolah')&&$r['npsn']==='20363192');
 }
 public function test_name_search_also_uses_fallback():void {
  Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>Http::response(['data'=>[]]),'https://sekolah.devapi.id/*'=>Http::response($this->fixture())]);
  $this->assertSame('20363192',$this->search('MAN 2 REMBANG')[0]['npsn']);
  Http::assertSent(fn($r)=>str_starts_with($r->url(),'https://sekolah.devapi.id/sekolah')&&$r['nama']==='MAN 2 REMBANG');
 }
 public function test_wrong_region_is_not_silently_accepted():void {
  Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>Http::response(['data'=>[]]),'https://sekolah.devapi.id/*'=>Http::response($this->fixture())]);
  $this->assertSame([],$this->search('20363192','33.17.13'));
  $this->assertCount(1,$this->search()); // district is part of cache key
 }
 public function test_filter_rejection_triggers_fallback_even_with_primary_rows():void {
  Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>Http::response(['data'=>[['npsn'=>'20363192','nama'=>'MAN 2 REMBANG']]]),'https://sekolah.devapi.id/*'=>Http::response($this->fixture())]);
  $this->assertCount(1,$this->search());
 }
 public function test_upstream_failure_is_not_a_false_not_found():void {
  Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>Http::response(['data'=>[]]),'https://sekolah.devapi.id/*'=>Http::response(['message'=>'Unavailable'],503)]);
  $this->getJson('/api/school-directory/schools?query=20363192&province_code=33&regency_code=33.17&district_code=33.17.14')->assertStatus(503);
 }
 public function test_import_validates_exact_npsn_and_region_before_database_write():void {
  Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>Http::response(['data'=>[]]),'https://sekolah.devapi.id/*'=>Http::response($this->fixture())]);
  $this->expectException(\RuntimeException::class);
  app(DapodikSchoolDirectory::class)->import('20363192','33','33.17','33.17.13');
 }
 public function test_import_on_spatial_database_after_empty_primary_detail():void {
  if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Memerlukan database spatial uji.');
  Http::fake(['https://dapo.kemendikdasmen.go.id/*'=>Http::response(['data'=>[]]),'https://sekolah.devapi.id/*'=>Http::response($this->fixture())]);
  DB::beginTransaction();try{
   $school=app(DapodikSchoolDirectory::class)->import('20363192','33','33.17','33.17.14');
   $this->assertSame('MAN 2 REMBANG',$school->name);$this->assertSame('20363192',$school->npsn);$this->assertSame('Lasem',$school->district_name);
   $columns=\App\Services\SpatialSql::coordinates('latitude','longitude');
   $coords=DB::table('schools')->where('id',$school->id)->selectRaw($columns)->first();
   $this->assertEqualsWithDelta(-6.6935544,$coords->latitude,.00001);$this->assertEqualsWithDelta(111.451965,$coords->longitude,.00001);
  }finally{DB::rollBack();}
 }
}
