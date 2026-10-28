<?php
namespace Tests\Feature;
use Tests\TestCase;
use Tests\Support\CampusFixture;
class MapZoomUiTest extends TestCase {
 use CampusFixture;
 protected function setUp():void{parent::setUp();$this->initializeCampusFixture();}
 public function test_focus_controls_and_zoom_styles_render_with_actual_manifest():void{
  $r=$this->withCookie('ruteaman_locale','id')->get('/map?school=1')->assertOk()->assertSee('fit-condition-markers')->assertSee('map-tile-status')->assertSee('css/map-auto-v7.css')->assertSee('Laporan kondisi di peta');$this->saveCampusPreview('zoom-map-preview.html',$r->getContent());
 }
}
