<?php
namespace Tests\Unit;
use App\Services\GeoGeometry;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class GeoGeometryTest extends TestCase {
 private function square():array {return ['type'=>'Polygon','coordinates'=>[[[106.82,-6.18],[106.84,-6.18],[106.84,-6.16],[106.82,-6.16],[106.82,-6.18]]]];}
 public function test_valid_area_reversed_ring_and_boundary_are_accepted():void {
  $g=new GeoGeometry;$p=$this->square();$v=$g->polygon($p,106.83,-6.17);
  $this->assertGreaterThan(4800000,$v['area_m2']);$this->assertLessThan(5000000,$v['area_m2']);
  $this->assertStringStartsWith('POLYGON((', $v['wkt']);
  $g->polygon($p,106.82,-6.17);$p['coordinates'][0]=array_reverse($p['coordinates'][0]);$this->assertEqualsWithDelta($v['area_m2'],$g->polygon($p,106.83,-6.17)['area_m2'],1);
 }
 public function test_invalid_polygons_are_rejected():void {
  $p=$this->square();$bad=[];
  $open=$p;array_pop($open['coordinates'][0]);$bad[]=$open;
  $hole=$p;$hole['coordinates'][]=$p['coordinates'][0];$bad[]=$hole;
  $bad[]=['type'=>'Polygon','coordinates'=>[[[106.82,-6.18],[106.84,-6.16],[106.84,-6.18],[106.82,-6.16],[106.82,-6.18]]]];
  $bad[]=['type'=>'Polygon','coordinates'=>[[[106.7,-6.3],[106.9,-6.3],[106.9,-6.1],[106.7,-6.1],[106.7,-6.3]]]];
  $duplicate=$p;array_splice($duplicate['coordinates'][0],2,0,[$duplicate['coordinates'][0][1]]);$bad[]=$duplicate;
  $infinite=$p;$infinite['coordinates'][0][1][0]=INF;$bad[]=$infinite;
  $bad[]='invalid-json';$bad[]=['type'=>'Point','coordinates'=>[106.83,-6.17]];
  foreach($bad as $input){try{(new GeoGeometry)->polygon($input,106.83,-6.17);$this->fail('Invalid catchment accepted.');}catch(ValidationException $e){$this->assertArrayHasKey('catchment',$e->errors());}}
 }
 public function test_pin_outside_is_rejected():void {$this->expectException(ValidationException::class);(new GeoGeometry)->polygon($this->square(),106.9,-6.17);}
 public function test_routes_measure_metres_not_degrees_with_endpoints_and_degenerate_edges():void {
  $g=new GeoGeometry;$line=['type'=>'LineString','coordinates'=>[[106.82,-6.18],[106.84,-6.18]]];
  $this->assertEqualsWithDelta(0,$g->routeDistance(106.83,-6.18,$line),.02);
  $this->assertEqualsWithDelta(111.2,$g->routeDistance(106.83,-6.179,$line),.2);
  $this->assertGreaterThan(1000,$g->routeDistance(106.85,-6.18,$line));
  $line['coordinates']=[[106.82,-6.18],[106.82,-6.18]];$this->assertEqualsWithDelta(111.2,$g->routeDistance(106.82,-6.179,$line),.2);
 }
}
