<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  if(!Schema::hasTable('site_presentations'))return;
  Schema::table('site_presentations',function(Blueprint $t){
   if(!Schema::hasColumn('site_presentations','register_photo_path'))$t->string('register_photo_path')->nullable();
   if(!Schema::hasColumn('site_presentations','register_photo_alt'))$t->string('register_photo_alt',140)->nullable();
  });
 }
 public function down():void {
  if(!Schema::hasTable('site_presentations'))return;
  Schema::table('site_presentations',function(Blueprint $t){
   $cols=[];
   if(Schema::hasColumn('site_presentations','register_photo_path'))$cols[]='register_photo_path';
   if(Schema::hasColumn('site_presentations','register_photo_alt'))$cols[]='register_photo_alt';
   if($cols)$t->dropColumn($cols);
  });
 }
};
