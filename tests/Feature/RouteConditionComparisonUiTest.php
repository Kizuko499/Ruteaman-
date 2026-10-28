<?php
namespace Tests\Feature;
use Tests\TestCase;
class RouteConditionComparisonUiTest extends TestCase {
 public function test_route_comparison_component_is_imported_and_provider_label_is_not_rendered(): void {
  $js=file_get_contents(resource_path('js/app.js'));
  $this->assertStringContainsString('renderConditionComparison',$js);
  $this->assertStringNotContainsString('alternatif dianalisis · sumber',$js);
  $this->assertStringNotContainsString('route.source||',$js);
  $this->assertStringContainsString('SIMULASI DEMO — belum audit lapangan',$js);
  $this->assertStringContainsString('route-condition-comparison.css',file_get_contents(resource_path('css/app.css')));
 }
 public function test_shared_component_has_real_evidence_and_single_route_explanation(): void {
  $js=file_get_contents(resource_path('js/features/route-condition-comparison.mjs'));
  $this->assertStringContainsString('Hanya satu rute tersedia.',$js);
  $this->assertStringContainsString('Kondisi aktif',$js);
  $this->assertStringContainsString('Beban Kondisi',$js);
  $this->assertStringContainsString('textContent',$js);
  $this->assertStringNotContainsString('openrouteservice',$js);
 }
}
