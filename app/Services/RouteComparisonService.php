<?php
namespace App\Services;
use App\Models\{Hazard,SavedRoute};
use App\Services\Routing\RoutingProviderInterface;
use Illuminate\Support\Facades\{Cache,DB};
use RuntimeException;
final class RouteComparisonService {
 public function __construct(private RoutingProviderInterface $routing,private HazardLoadService $loads,private HazardEvidenceService $evidence){}
 public function compare(array $origin,array $destination,string $profile,?int $school=null):array {
  $key='routes:'.hash('sha256',implode(':',[round($origin['lat'],4),round($origin['lng'],4),round($destination['lat'],4),round($destination['lng'],4),$profile]));
  $routes=Cache::remember($key,config('ruteaman.routing.cache_seconds'),fn()=>$this->routing->routes($origin,$destination,$profile));
  if($routes===[])throw new RuntimeException('Tidak ada alternatif rute.');
  Cache::put('routing.last_success_at',now()->toIso8601String(),now()->addDay());
  return $this->decorate($routes,$this->routing->getProviderName(),$school);
 }
 public function saved(int $school):array {
  $routes=SavedRoute::where('school_id',$school)->orderBy('id')->get()->map(fn($r)=>[
   'id'=>$r->name,'geometry'=>$r->geometry,'distance_m'=>$r->distance_m,'duration_s'=>$r->duration_s,
   'source'=>$r->source,'is_demo'=>$r->is_demo,'steps'=>[],
  ])->all();
  return $this->decorate($routes,'Rute tersimpan — bukan navigasi langsung',$school);
 }
 private function decorate(array $routes,string $source,?int $school=null):array {
  $shortest=$routes?min(array_column($routes,'distance_m')):0;
  foreach($routes as &$route){
   $models=$this->hazardsAlong($route['geometry'],$school);
   $route['expired_nearby']=$this->expiredAlong($route['geometry'],$school,$models->count());
   $inputs=$models->map(fn($h)=>$this->evidence->inputs($h))->all();
   $load=$this->loads->calculate($inputs,(float)config('ruteaman.hazard_saturation',9));
   $included=array_column($load['details'],'public_slug');
   $route['load']=$load;
   $route['hazards']=$models->filter(fn($h)=>in_array($h->public_slug,$included,true))->map(fn($h)=>[
    'public_slug'=>$h->public_slug,'label'=>$h->type->label,'status'=>$h->status_label,'last_confirmed_at'=>($h->last_confirmed_at??$h->first_reported_at)->toIso8601String(),'url'=>route('hazards.public',$h),'severity'=>$h->severity,
   ])->values()->all();
   $route['extra_distance_m']=max(0,$route['distance_m']-$shortest);
  }
  return ['routes'=>$routes,'source'=>$source,'public_scope'=>app(PublicMapScope::class)->shared($school?\App\Models\School::find($school):null)?'cross_school':'selected_school','fetched_at'=>now()->toIso8601String(),'disclaimer'=>'Belum ada laporan aktif bukan jaminan kondisi aman. Data di rute ini bisa masih sedikit.'];
 }
 private function hazardsAlong(array $geometry,?int $school=null){
  $geojson=json_encode($geometry,JSON_THROW_ON_ERROR);
  $q=Hazard::with('type')->active()->publicListed();
  app(PublicMapScope::class)->apply($q,$school?\App\Models\School::find($school):null);
  if(DB::getDriverName()==='pgsql')$q->whereRaw('ST_DWithin(location::geography,ST_SetSRID(ST_GeomFromGeoJSON(?),4326)::geography,?)',[$geojson,(int)config('ruteaman.route_buffer_meters')]);
  else {
   // MariaDB ST_Distance on SRID 4326 returns degrees, NOT metres.
   // Filter in metres before applying the public hazard limit. Lazy batches keep memory bounded.
   $buffer=(float)config('ruteaman.route_buffer_meters');$geo=app(GeoGeometry::class);
   return $q->select('hazards.*')->selectRaw(SpatialSql::coordinates())->orderByDesc('last_confirmed_at')->orderByDesc('id')
    ->lazy(500)->filter(fn($h)=>$geo->routeDistance((float)$h->longitude,(float)$h->latitude,$geometry)<=$buffer)
    ->take((int)config('ruteaman.route_hazard_limit'))->collect();
  }
  return $q->orderByDesc('last_confirmed_at')->limit((int)config('ruteaman.route_hazard_limit'))->get();
 }

 /**
  * Hitung hazard kedaluwarsa di sekitar rute (info saja, tidak masuk skor).
  */
 private function expiredAlong(array $geometry,?int $school=null,int $activeCount=0):int{
  // "Riwayat" di sekitar rute = semua hazard yang TAMPIL di peta (definisi feed
  // MapConditionService: publicListed + expired terpublikasi) dikurangi yang aktif.
  // Sebelumnya hanya status 'expired' yang dihitung, sehingga laporan lama yang
  // statusnya belum beralih (mis. confirmed berusia > 3x TTL) tidak terhitung
  // di angka mana pun padahal ikonnya terlihat melewati rute di peta.
  try{
   $geojson=json_encode($geometry,JSON_THROW_ON_ERROR);
   $buffer=(float)config('ruteaman.route_buffer_meters');
   $q=Hazard::where('publication_control','normal')->where('requires_moderation',false)
    ->where(function($v){$v->publicListed()->orWhere(fn($h)=>$h->where('status','expired')->whereNotNull('published_at'));})
    ->whereRaw("COALESCE((SELECT decision FROM verifications WHERE hazard_id=hazards.id ORDER BY id DESC LIMIT 1),'') != 'rejected'");
   app(PublicMapScope::class)->apply($q,$school?\App\Models\School::find($school):null);
   if(DB::getDriverName()==='pgsql'){
    $total=(clone $q)->whereRaw('ST_DWithin(location::geography,ST_SetSRID(ST_GeomFromGeoJSON(?),4326)::geography,?)',[$geojson,(int)$buffer])->count();
   }else{
    $geo=app(GeoGeometry::class);
    $total=(clone $q)->select('hazards.*')->selectRaw(SpatialSql::coordinates())->lazy(500)
     ->filter(fn($h)=>$geo->routeDistance((float)$h->longitude,(float)$h->latitude,$geometry)<=$buffer)->count();
   }
   return max(0,$total-$activeCount);
  }catch(\Throwable $e){ return 0; }
 }
}
