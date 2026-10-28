<?php
namespace App\Services;
use App\Models\{HomeGalleryGroup,HomeGalleryPhoto,HazardReport};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{Schema,Storage,Cache};
final class HomeGalleryService {
 public function groups(){
  $saved=Schema::hasTable('home_gallery_groups')?HomeGalleryGroup::whereIn('key',array_keys(config('home_gallery.groups')))->get()->keyBy('key'):collect();
  return collect(config('home_gallery.groups'))->map(fn($d,$key)=>$saved->get($key)??(new HomeGalleryGroup)->forceFill(['key'=>$key,'orientation'=>$d['orientation'],'source'=>'admin','mode'=>'stay','fixed_token'=>'static-1','interval_value'=>30,'interval_unit'=>'seconds','category_ids'=>[],'rotation_anchor'=>now()]));
 }
 public function title(HomeGalleryGroup $g):string{return config('home_gallery.groups.'.$g->key.'.title',$g->key);}
 public function pool(HomeGalleryGroup $g):array {
  $out=[];$categories=array_map('intval',$g->category_ids??[]);
  if($g->exists&&in_array($g->source,['admin','mixed'],true)){
   $q=$g->photos()->where('enabled',true)->where('orientation',$g->orientation)->orderBy('sort_order')->orderBy('id');if($categories)$q->whereIn('type_id',$categories);
   foreach($q->limit(60)->get() as $p)if(Storage::disk('local')->exists($p->path))$out[]=['token'=>'admin-'.$p->id,'url'=>route('home-gallery.media',[$g->key,'admin-'.$p->id]),'alt'=>$p->caption,'source'=>'admin','path'=>$p->path,'disk'=>'local','label'=>'Foto unggahan admin · ilustrasi'];
  }
  if($g->exists&&$g->orientation==='landscape'&&in_array($g->source,['verified','mixed'],true)){
   // Stricter than the general condition policy: current verification, public eligibility, non-demo, no moderation.
   $q=HazardReport::with('hazard.type')->whereNotNull('photo_path')->whereHas('hazard',function($h)use($categories,$g){
    $h->publicListed()->active()->where('status','verified')->where('is_demo',false)->whereNotNull('published_at')->whereRaw("(SELECT decision FROM verifications WHERE hazard_id=hazards.id ORDER BY id DESC LIMIT 1) = 'verified'");
    if($categories)$h->whereIn('type_id',$categories);
    if($g->school_id)$h->whereRaw("EXISTS (SELECT 1 FROM schools s WHERE s.id=? AND s.status='active' AND ST_Intersects(s.catchment,hazards.location))",[$g->school_id]);
   })->orderByDesc('observed_at')->orderByDesc('id')->limit(100);
   $seen=[];
   foreach($q->get() as $p){$path=$p->photo_path;if(isset($seen[$path])||!str_starts_with($path,'hazards/')||str_contains($path,'..')||!Storage::disk('hazard_photos')->exists($path))continue;$seen[$path]=true;
    $size=$this->size('hazard_photos',$path);if(!$size||$size[0]<$size[1])continue;
    $out[]=['token'=>'report-'.$p->id,'url'=>route('home-gallery.media',[$g->key,'report-'.$p->id]),'alt'=>'Foto kondisi terverifikasi: '.$p->hazard->type->label,'source'=>'verified','path'=>$path,'disk'=>'hazard_photos','label'=>'Foto laporan terverifikasi · kondisi dapat berubah'];
   }
  }
  // Public illustrations fill empty slots only; they are never labelled as actual verified evidence.
  if(count($out)<3)foreach(config('home_gallery.groups.'.$g->key.'.fallback',[]) as $i=>$file)$out[]=['token'=>'static-'.$i,'url'=>asset('images/'.$file),'alt'=>'Ilustrasi penggunaan RuteAman','source'=>'illustration','label'=>'Foto ilustrasi · bukan bukti kondisi terkini'];
  if($g->mode==='stay'&&preg_match('/^static-([0-2])$/',(string)$g->fixed_token,$m)&&!in_array($g->fixed_token,array_column($out,'token'),true)){
   $file=config('home_gallery.groups.'.$g->key.'.fallback.'.(int)$m[1]);$out[]=['token'=>$g->fixed_token,'url'=>asset('images/'.$file),'alt'=>'Ilustrasi penggunaan RuteAman','source'=>'illustration','label'=>'Foto ilustrasi · bukan bukti kondisi terkini'];
  }
  return $out;
 }
 public function size(string $disk,string $path):?array {
  $store=Storage::disk($disk);if(!$store->exists($path))return null;
  return Cache::remember('home-gallery-size-'.hash('sha256',$disk.$path.$store->lastModified($path)),86400,function()use($store,$path){$s=@getimagesize($store->path($path));return $s&&$s[0]*$s[1]<=12000000?[$s[0],$s[1]]:null;});
 }
 public function timing(HomeGalleryGroup $g,?CarbonImmutable $at=null):array {
  $at=($at??CarbonImmutable::now())->setTimezone(config('home_gallery.timezone'));$anchor=($g->rotation_anchor??$at)->setTimezone(config('home_gallery.timezone'));$amount=max(1,(int)$g->interval_value);$unit=$g->interval_unit;
  if($g->mode!=='rotate')return ['index'=>0,'next'=>null];
  if($unit==='months'){
   $months=max(0,($at->year-$anchor->year)*12+$at->month-$anchor->month);
   if($at->lessThan($anchor->addMonthsNoOverflow($months)))$months=max(0,$months-1);
   $index=intdiv($months,$amount);$next=$anchor->addMonthsNoOverflow(($index+1)*$amount);
  }else{
   $seconds=$amount*(['seconds'=>1,'minutes'=>60,'hours'=>3600,'days'=>86400,'weeks'=>604800][$unit]??30);
   $index=intdiv(max(0,$at->getTimestamp()-$anchor->getTimestamp()),$seconds);$next=$anchor->addSeconds(($index+1)*$seconds);
  }
  return ['index'=>$index,'next'=>$next->toIso8601String()];
 }
 public function feed():array {
  $data=[];
  foreach($this->groups() as $key=>$g){$pool=$this->pool($g);$n=count($pool);$t=$this->timing($g);$index=$g->mode==='stay'?array_search($g->fixed_token,array_column($pool,'token'),true):$t['index']%max(1,$n);if($index===false)$index=0;
   $frames=[];if($n)foreach([-1,0,1] as $offset){$p=$pool[($index+$offset+$n)%$n];$frames[]=array_intersect_key($p,array_flip(['token','url','alt','source','label']));}
   $data[$key]=['key'=>$key,'title'=>$this->title($g),'orientation'=>$g->orientation,'mode'=>$g->mode,'frames'=>$frames,'next_change_at'=>$t['next'],'pool_tokens'=>array_column($pool,'token'),'version'=>$g->updated_at?->toIso8601String()];
  }
  return ['server_time'=>CarbonImmutable::now()->toIso8601String(),'groups'=>$data];
 }
 public function media(HomeGalleryGroup $group,string $token){
  $photo=collect($this->pool($group))->firstWhere('token',$token);abort_unless($photo&&isset($photo['path']),404);
  return $this->image($photo['disk'],$photo['path']);
 }
 public function image(string $disk,string $path){
  abort_if(str_contains($path,'..'),404);$store=Storage::disk($disk);abort_unless($store->exists($path),404);$size=$this->size($disk,$path);abort_unless($size,404);
  // Eligibility is checked before reusing a derivative; changing verification/source immediately revokes new requests.
  $key='home-gallery/derivatives/'.hash('sha256',$disk.$path.$store->lastModified($path)).'.webp';$target=Storage::disk('local');
  if(!$target->exists($key)){$im=@imagecreatefromstring($store->get($path));abort_unless($im,404);$w=min(1280,imagesx($im));$h=max(1,(int)round(imagesy($im)*$w/imagesx($im)));$scaled=imagescale($im,$w,$h);imagedestroy($im);ob_start();imagewebp($scaled,null,78);$bytes=ob_get_clean();imagedestroy($scaled);abort_unless($target->put($key,$bytes),500);}
  return response($target->get($key))->header('Content-Type','image/webp')->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');
 }
}
