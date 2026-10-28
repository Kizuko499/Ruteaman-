<?php
namespace App\Services;
use App\Models\School;
use Illuminate\Support\Facades\Cache;
final class FeaturedHazardService {
 public function __construct(private ConditionFeedService $feed){}
 public function next(?School $school,?string $afterSlug=null,int $step=1):array {
  return Cache::remember('featured:'.Cache::get('conditions.version','0').':'.hash('sha256',json_encode([$school?->id,$afterSlug,$step,$school?->settings])),30,function()use($school,$afterSlug,$step){
   $rows=$this->feed->allQuery()->get();$at=$rows->search(fn($h)=>$h->public_slug===$afterSlug);$count=$rows->count();$index=$count===0?0:($at===false?($step<0?$count-1:0):(($at+$step)%$count+$count)%$count);$h=$rows->get($index);
   return ['hazard'=>$h?$this->feed->item($h):null,'position'=>$h?$index+1:0,'total_active'=>$rows->count(),'rotate_in_seconds'=>max(10,(int)($school?->settings['featured_rotate_seconds']??config('ruteaman.featured_rotate_seconds',120))),'summary'=>$this->feed->summary($school),'generated_at'=>now()->toIso8601String()];
  });
 }
}
