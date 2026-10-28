<?php
namespace Tests\Feature;
use App\Models\School;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class LandingSchoolTest extends TestCase {
 use DatabaseTransactions;
 protected function setUp():void {
  if(getenv('RUTEAMAN_SPATIAL_TESTS')!=='1')$this->markTestSkipped('Memerlukan database spatial uji.');
  parent::setUp();config(['app.key'=>'base64:'.base64_encode(str_repeat('l',32))]);$this->seed(DemoSeeder::class);
 }
 public function test_school_input_is_empty_and_editable():void {
  $html=$this->get('/')->assertOk()->getContent();$dom=new \DOMDocument;@$dom->loadHTML($html);
  $input=(new \DOMXPath($dom))->query('//input[@name="to"]')->item(0);
  $this->assertNotNull($input);$this->assertSame('',$input->getAttribute('value'));$this->assertFalse($input->hasAttribute('readonly'));$this->assertFalse($input->hasAttribute('disabled'));
 }
 public function test_typed_school_name_selects_the_matching_destination():void {
  $b=School::where('name','Sekolah B — Demo Simulasi')->firstOrFail();
  $this->get('/map?to='.rawurlencode(mb_strtolower($b->name)))->assertOk()->assertViewHas('school',fn($s)=>$s->id===$b->id);
 }
 public function test_npsn_also_selects_the_matching_destination():void {
  $b=School::where('name','Sekolah B — Demo Simulasi')->firstOrFail();DB::table('schools')->where('id',$b->id)->update(['npsn'=>'99999992']);
  $this->get('/map?to=99999992')->assertOk()->assertViewHas('school',fn($s)=>$s->id===$b->id);
 }
 public function test_unknown_school_does_not_silently_select_demo_school():void {
  $this->get('/map?to=SekolahTidakAda')->assertRedirect(route('home'))->assertSessionHasErrors('to')->assertSessionHasInput('to','SekolahTidakAda');
 }
 public function test_duplicate_names_require_npsn_instead_of_guessing():void {
  $a=School::where('name','Sekolah A — Demo Simulasi')->firstOrFail();$b=School::where('name','Sekolah B — Demo Simulasi')->firstOrFail();
  DB::table('schools')->where('id',$b->id)->update(['name'=>$a->name]);
  $this->get('/map?to='.rawurlencode($a->name))->assertRedirect(route('home'))->assertSessionHasErrors('to');
 }
}
