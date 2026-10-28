<?php
namespace App\Services;
use App\Models\School;
use Illuminate\Validation\ValidationException;
final class PublicMapScope {
 public function shared(?School $school):bool {
  return app(GlobalFeatureSettings::class)->enabled('cross_school_public_reports') && ($school?->features['cross_school_public_reports'] ?? true);
 }
 public function apply($query,?School $school){
  if(!$this->shared($school)){if($school)$query->inSchool($school->id);else $query->whereRaw('1=0');}return $query;
 }
 public function bounds(?string $value):?array {
  if($value===null||$value==='')return null;$p=explode(',',$value);
  if(count($p)!==4||array_filter($p,fn($n)=>!is_numeric($n)||!is_finite((float)$n)))throw ValidationException::withMessages(['bbox'=>'Batas peta tidak valid.']);
  [$w,$s,$e,$n]=array_map('floatval',$p);if($w< -180||$w>180||$e< -180||$e>180||$s< -90||$n>90||$s>=$n||$w==$e)throw ValidationException::withMessages(['bbox'=>'Batas peta tidak valid.']);return [$w,$s,$e,$n];
 }
 public function viewport($q,?array $bbox){
  if(!$bbox)return $q;[$w,$s,$e,$n]=$bbox;
  $cartesian=\Illuminate\Support\Facades\DB::getDriverName()==='pgsql'||SpatialSql::isMariaDb();$lat=$cartesian?'ST_Y(hazards.location)':'ST_Latitude(hazards.location)';$lng=$cartesian?'ST_X(hazards.location)':'ST_Longitude(hazards.location)';
  $q->whereRaw("$lat BETWEEN ? AND ?",[$s,$n]);return $q->where(function($q)use($w,$e,$lng){if($w<$e)$q->whereRaw("$lng BETWEEN ? AND ?",[$w,$e]);else $q->whereRaw("($lng >= ? OR $lng <= ?)",[$w,$e]);});
 }
}
