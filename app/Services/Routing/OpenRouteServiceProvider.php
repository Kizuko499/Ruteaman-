<?php
namespace App\Services\Routing;
use Illuminate\Support\Facades\{Http,Log};
use RuntimeException;
final class OpenRouteServiceProvider implements RoutingProviderInterface
{
 public function routes(array $origin,array $destination,string $profile):array
 {
  $key=config('ruteaman.routing.api_key');if(!$key)throw new RuntimeException('ROUTING_API_KEY belum diatur.');
  $providerProfile=$profile==='driving-motorcycle'?'driving-car':$profile;
  $coords=[[(float)$origin['lng'],(float)$origin['lat']],[(float)$destination['lng'],(float)$destination['lat']]];
  $alternatives=(int)config('ruteaman.routing.alternatives');
  $timeout=(int)config('ruteaman.routing.timeout');
  if($alternatives>1){
   try{
    // weight_factor 1.4 terbukti terlalu ketat untuk jaringan jalan koridor
    // (alternatif nyata 4,5 km vs rute utama 3,6 km ditolak ORS walau HTTP 200,
    // sehingga hanya Rute A yang tampil). 1.6 membuka alternatif yang wajar.
    $features=$this->request($key,$providerProfile,$coords,['target_count'=>$alternatives,'share_factor'=>.6,'weight_factor'=>1.6],$timeout,true);
    if($features!==null){
     if(count($features)<$alternatives)Log::info('ORS mengembalikan rute lebih sedikit dari target alternatif.',['profile'=>$providerProfile,'diminta'=>$alternatives,'diterima'=>count($features)]);
     return $this->mapFeatures($features);
    }
    Log::warning('Permintaan rute alternatif ORS gagal; jatuh ke rute tunggal.',['profile'=>$providerProfile]);
   }catch(\Throwable $e){Log::warning('Permintaan rute alternatif ORS melempar pengecualian; jatuh ke rute tunggal.',['profile'=>$providerProfile,'error'=>$e->getMessage()]);}
  }
  try{
   $features=$this->request($key,$providerProfile,$coords,null,max($timeout,25),false);
  }catch(\Throwable $e){$features=null;}
  if($features===null)throw new RuntimeException('Rute tidak dapat dihitung provider (kemungkinan terlalu jauh atau provider sibuk). Coba titik awal yang lebih dekat.');
  return $this->mapFeatures($features);
 }
 private function request(string $key,string $providerProfile,array $coords,?array $alt,int $timeout,bool $retry):?array
 {
  $payload=['coordinates'=>$coords,'instructions'=>true,'language'=>'id'];
  if($alt)$payload['alternative_routes']=$alt;
  $http=Http::withHeaders(['Authorization'=>$key,'Accept'=>'application/geo+json'])->timeout($timeout);
  if($retry)$http=$http->retry(1,250);
  $response=$http->post(rtrim(config('ruteaman.routing.base_url'),'/').'/v2/directions/'.$providerProfile.'/geojson',$payload);
  if(!$response->successful()){Log::warning('ORS directions tidak sukses.',['status'=>$response->status(),'alternatif'=>$alt!==null,'error'=>$response->json('error')]);return null;}
  return $response->json('features',[]);
 }
 private function mapFeatures(array $features):array
 {
  return collect($features)->values()->map(function(array $feature,int $index){
   $segment=$feature['properties']['segments'][0]??[];
   return ['id'=>chr(65+$index),'distance_m'=>(int)round($segment['distance']??0),'duration_s'=>(int)round($segment['duration']??0),'geometry'=>$feature['geometry'],'steps'=>collect($segment['steps']??[])->map(fn($step)=>['instruction'=>$step['instruction']??'Lanjut','distance_m'=>(int)round($step['distance']??0),'duration_s'=>(int)round($step['duration']??0)])->all(),'source'=>'openrouteservice'];
  })->all();
 }
 public function healthCheck():bool{return filled(config('ruteaman.routing.api_key'));}
 public function getProviderName():string{return 'openrouteservice';}
}
