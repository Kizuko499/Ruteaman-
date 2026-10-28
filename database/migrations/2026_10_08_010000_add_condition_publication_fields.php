<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up():void {
  Schema::table('hazards',function(Blueprint $t){$t->string('location_label',120)->nullable();});
  Schema::table('schools',function(Blueprint $t){$t->json('settings')->nullable();$t->string('public_slug',100)->nullable()->unique();});
  DB::table('schools')->orderBy('id')->each(fn($s)=>DB::table('schools')->where('id',$s->id)->update(['public_slug'=>'sekolah-'.$s->id]));
  if(DB::getDriverName()==='pgsql')DB::statement('CREATE INDEX hazards_activity_idx ON hazards ((COALESCE(last_confirmed_at,first_reported_at)) DESC,id DESC)');
  elseif(DB::getDriverName()==='mysql'){DB::statement('ALTER TABLE hazards ADD activity_at DATETIME GENERATED ALWAYS AS (COALESCE(last_confirmed_at,first_reported_at)) STORED');DB::statement('CREATE INDEX hazards_activity_idx ON hazards(activity_at DESC,id DESC)');}
 }
 public function down():void {
  if(DB::getDriverName()==='pgsql')DB::statement('DROP INDEX IF EXISTS hazards_activity_idx');
  elseif(DB::getDriverName()==='mysql'){Schema::table('hazards',fn(Blueprint $t)=>$t->dropIndex('hazards_activity_idx'));Schema::table('hazards',fn(Blueprint $t)=>$t->dropColumn('activity_at'));}
  Schema::table('hazards',fn(Blueprint $t)=>$t->dropColumn('location_label'));Schema::table('schools',fn(Blueprint $t)=>$t->dropColumn(['settings','public_slug']));
 }
};
