<?php
namespace App\Services;
final class HazardLoadService
{
 public function confidence(int $reports,int $confirmations,bool $hasPhoto,bool $verified):float{return min(1,.30*min(max(0,$reports),3)/3+.30*min(max(0,$confirmations),3)/3+.20*($hasPhoto?1:0)+.40*($verified?1:0));}
 public function freshness(int $ageMinutes,int $ttlMinutes):float{if($ageMinutes<=$ttlMinutes)return 1;if($ageMinutes>=3*$ttlMinutes)return 0;if($ageMinutes<2*$ttlMinutes)return 1-(($ageMinutes-$ttlMinutes)/$ttlMinutes)*.7;return .3;}
 public function calculate(array $hazards,float $saturation=9):array
 {
  if($saturation<=0)throw new \InvalidArgumentException('Titik jenuh harus lebih dari nol.');
  $details=[];foreach($hazards as $hazard){$c=$hazard['confidence']??$this->confidence($hazard['reports']??0,$hazard['confirmations']??0,$hazard['has_photo']??false,$hazard['verified']??false);$f=$hazard['freshness']??$this->freshness($hazard['age_minutes']??0,$hazard['ttl_minutes']??1);if($f<=0)continue;$w=$hazard['weather_multiplier']??1;$h=$hazard['severity']*$c*$f*$w;$details[]=[...$hazard,'c'=>$c,'f'=>$f,'w'=>$w,'h'=>$h];}
  if($details===[])return ['saturation'=>$saturation,'value'=>null,'label_code'=>'no_report','label'=>'Belum ada laporan aktif. Ini bukan jaminan kondisi aman.','details'=>[]];
  $sum=array_sum(array_column($details,'h'));$value=min(100,(int)round(100*$sum/$saturation));return ['saturation'=>$saturation,'value'=>$value,'label_code'=>$value<30?'light':($value<60?'moderate':'heavy'),'label'=>$value<30?'Ringan':($value<60?'Sedang':'Berat'),'sum'=>$sum,'details'=>$details];
 }
}