<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {Schema::create('site_presentations',function(Blueprint $t){$t->id();$t->string('login_photo_path')->nullable();$t->string('login_photo_alt',140)->nullable();$t->string('help_contact',300)->nullable();$t->timestamps();});DB::table('site_presentations')->insert(['id'=>1,'created_at'=>now(),'updated_at'=>now()]);}
 public function down():void {Schema::dropIfExists('site_presentations');}
};
