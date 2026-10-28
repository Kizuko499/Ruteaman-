<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up():void {
  Schema::table('hazards',fn(Blueprint $t)=>$t->timestamp('published_at')->nullable()->index());
  // Historical exposure is explicit: expiry must not turn a private report public.
  DB::table('hazards')->whereIn('status',['confirmed','verified','disputed','resolved'])
   ->whereRaw("COALESCE((SELECT decision FROM verifications WHERE hazard_id=hazards.id ORDER BY id DESC LIMIT 1),'') != 'rejected'")
   ->update(['published_at'=>DB::raw('first_reported_at')]);
  DB::table('hazards')->where('status','expired')->where(function($q){$q->whereExists(fn($v)=>$v->selectRaw('1')->from('verifications')->whereColumn('hazard_id','hazards.id')->where('decision','verified'))->orWhereRaw("((SELECT COUNT(DISTINCT reporter_id) FROM hazard_reports WHERE hazard_id=hazards.id) + (SELECT COUNT(DISTINCT user_id) FROM hazard_confirmations hc WHERE hc.hazard_id=hazards.id AND hc.vote='still_there' AND NOT EXISTS(SELECT 1 FROM hazard_reports hr WHERE hr.hazard_id=hazards.id AND hr.reporter_id=hc.user_id))) >= 3");})
   ->whereRaw("COALESCE((SELECT decision FROM verifications WHERE hazard_id=hazards.id ORDER BY id DESC LIMIT 1),'') != 'rejected'")
   ->update(['published_at'=>DB::raw('first_reported_at')]);
  foreach([
   ['accident','Kecelakaan',3,180],['road_closed','Jalan ditutup',3,720],['traffic','Kemacetan',1,120],['street_light','Lampu jalan mati',2,20160],['warning','Bahaya umum',2,360],
  ] as [$code,$label,$weight,$ttl])DB::table('hazard_types')->insertOrIgnore(['code'=>$code,'label'=>$label,'severity_weight'=>$weight,'ttl_minutes'=>$ttl,'rain_sensitive'=>false,'night_only'=>$code==='street_light']);
 }
 public function down():void {Schema::table('hazards',fn(Blueprint $t)=>$t->dropColumn('published_at'));/* Preserve category rows and their dependent reports. */}
};
