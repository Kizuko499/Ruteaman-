<?php
namespace Tests\Unit;
use App\Services\HazardLoadService;
use PHPUnit\Framework\TestCase;
class HazardLoadServiceTest extends TestCase
{
 public function test_examples_from_product_specification():void
 {
  $service=new HazardLoadService;
  $this->assertSame(22,$service->calculate([['severity'=>2,'confidence'=>1,'freshness'=>1]])['value']);
  $this->assertSame(47,$service->calculate([['severity'=>2,'confidence'=>1,'freshness'=>1],['severity'=>3,'reports'=>1,'confirmations'=>2,'has_photo'=>true,'verified'=>false,'freshness'=>1,'weather_multiplier'=>1.5]])['value']);
  $this->assertSame(67,$service->calculate([['severity'=>2,'confidence'=>1,'freshness'=>1],['severity'=>3,'reports'=>1,'confirmations'=>2,'has_photo'=>true,'verified'=>true,'freshness'=>1,'weather_multiplier'=>1.5]])['value']);
  $this->assertSame('no_report',$service->calculate([])['label_code']);
 }
 public function test_confidence_counts_real_evidence_and_caps():void {
  $s=new HazardLoadService;
  $this->assertEqualsWithDelta(.5,$s->confidence(1,2,true,false),.00001);
  $this->assertEqualsWithDelta(.9,$s->confidence(1,2,true,true),.00001);
  $this->assertSame(1.0,$s->confidence(2,2,true,true));
  $this->assertSame(1.0,$s->confidence(100,100,true,true));
  $this->assertSame(0.0,$s->confidence(-2,-1,false,false));
 }
 public function test_expired_hazards_are_not_zero_risk():void {
  $s=new HazardLoadService;$this->assertSame('no_report',$s->calculate([['severity'=>3,'freshness'=>0]])['label_code']);
 }
 public function test_invalid_saturation_is_rejected():void {
  $this->expectException(\InvalidArgumentException::class);(new HazardLoadService)->calculate([],0);
 }
 public function test_freshness_boundaries():void
 {
  $service=new HazardLoadService;$this->assertSame(1.0,$service->freshness(60,60));$this->assertEqualsWithDelta(.65,$service->freshness(90,60),.0001);$this->assertSame(.3,$service->freshness(120,60));$this->assertSame(0.0,$service->freshness(180,60));
 }
}