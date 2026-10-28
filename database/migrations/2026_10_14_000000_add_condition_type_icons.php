<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::table('hazard_types',function(Blueprint $t){$t->string('icon_key',40)->default('warning');$t->string('icon_path')->nullable();});
  foreach(['accident'=>'accident','pothole'=>'road_damage','road_damage'=>'road_damage','damaged_road'=>'road_damage','flood'=>'flood','fallen_tree'=>'fallen_tree','road_closed'=>'road_closed','road_closure'=>'road_closed','traffic'=>'traffic','poor_lighting'=>'street_light','street_light'=>'street_light','obstruction'=>'obstruction','slippery'=>'slippery','damaged_sidewalk'=>'damaged_sidewalk','handled'=>'handled'] as $code=>$icon)DB::table('hazard_types')->where('code',$code)->update(['icon_key'=>$icon]);
 }
 public function down():void {Schema::table('hazard_types',fn(Blueprint $t)=>$t->dropColumn(['icon_key','icon_path']));}
};
