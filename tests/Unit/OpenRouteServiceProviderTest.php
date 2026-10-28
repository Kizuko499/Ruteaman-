<?php
namespace Tests\Unit;
use App\Services\Routing\OpenRouteServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class OpenRouteServiceProviderTest extends TestCase {
 private function feature(float $distance,float $duration):array {
  return ['geometry'=>['type'=>'LineString','coordinates'=>[[111.44,-6.67],[111.45,-6.69]]],
   'properties'=>['segments'=>[['distance'=>$distance,'duration'=>$duration,'steps'=>[['instruction'=>'Lurus','distance'=>$distance,'duration'=>$duration]]]]]];
 }
 protected function setUp():void {
  parent::setUp();
  config(['ruteaman.routing.api_key'=>'kunci-uji','ruteaman.routing.base_url'=>'https://ors.invalid','ruteaman.routing.alternatives'=>3,'ruteaman.routing.timeout'=>5]);
 }
 public function test_alternatives_are_requested_and_all_features_are_mapped(): void {
  Http::fake(['ors.invalid/*'=>Http::response(['features'=>[$this->feature(3600,600),$this->feature(4500,760)]],200)]);
  $routes=app(OpenRouteServiceProvider::class)->routes(['lat'=>-6.679,'lng'=>111.4472],['lat'=>-6.7018,'lng'=>111.4468],'driving-motorcycle');
  $this->assertSame(['A','B'],array_column($routes,'id'));
  $this->assertSame(4500,$routes[1]['distance_m']);
  Http::assertSent(function(Request $request){
   $alt=$request->data()['alternative_routes']??null;
   return $alt!==null && $alt['target_count']===3 && $alt['share_factor']===.6 && $alt['weight_factor']===1.6
    && str_contains($request->url(),'driving-car'); // driving-motorcycle dipetakan ke driving-car
  });
 }
 public function test_failed_alternatives_request_falls_back_to_single_route(): void {
  Http::fake(['ors.invalid/*'=>function(Request $request){
   return ($request->data()['alternative_routes']??null)!==null
    ? Http::response(['error'=>['code'=>2004,'message'=>'ditolak']],400)
    : Http::response(['features'=>[$this->feature(3600,600)]],200);
  }]);
  $routes=app(OpenRouteServiceProvider::class)->routes(['lat'=>-6.679,'lng'=>111.4472],['lat'=>-6.7018,'lng'=>111.4468],'foot-walking');
  $this->assertSame(['A'],array_column($routes,'id'));
 }
 public function test_partial_alternatives_response_is_used_without_fallback(): void {
  Http::fake(['ors.invalid/*'=>Http::response(['features'=>[$this->feature(3600,600)]],200)]);
  $routes=app(OpenRouteServiceProvider::class)->routes(['lat'=>-6.679,'lng'=>111.4472],['lat'=>-6.7018,'lng'=>111.4468],'foot-walking');
  $this->assertCount(1,$routes);
  Http::assertSentCount(1); // HTTP 200 dengan 1 feature diterima apa adanya, tanpa permintaan kedua
 }
}
