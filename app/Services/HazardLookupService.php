<?php
namespace App\Services;
use App\Models\{Hazard,HazardType,User};
use Illuminate\Support\Facades\DB;
final class HazardLookupService {
 public function guardPoint(User $user,float $lat,float $lng):void {
  // Jangkauan laporan tidak dibatasi wilayah (permintaan pemilik platform):
  // titik hazard mana pun boleh dilaporkan untuk sekolah pengguna.
 }
 public function nearby(int $type,float $lat,float $lng,?int $schoolId=null):?Hazard {
  $pgsql=DB::getDriverName()==='pgsql';$distance=(int)config('ruteaman.hazard_merge_distance_meters',30);
  $query=Hazard::query()->active()->where('type_id',$type);if($schoolId)$query->inSchool($schoolId);
  return $query->whereRaw($pgsql?'ST_DWithin(location::geography,ST_SetSRID(ST_MakePoint(?,?),4326)::geography,?)':'ST_Distance_Sphere(location,'.SpatialSql::wkt().')<=?',$pgsql?[$lng,$lat,$distance]:["POINT($lng $lat)",$distance])->orderBy('id')->first();
 }
}
