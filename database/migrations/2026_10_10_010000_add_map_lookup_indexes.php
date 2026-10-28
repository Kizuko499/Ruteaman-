<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  // GIST location index already exists. These support public visibility/latest decision and counts.
  Schema::table('verifications',fn(Blueprint $t)=>$t->index(['hazard_id','id'],'map_verifications_hazard_id_idx'));
  Schema::table('hazard_reports',fn(Blueprint $t)=>$t->index('hazard_id','map_reports_hazard_idx'));
 }
 public function down():void {
  Schema::table('verifications',fn(Blueprint $t)=>$t->dropIndex('map_verifications_hazard_id_idx'));
  Schema::table('hazard_reports',fn(Blueprint $t)=>$t->dropIndex('map_reports_hazard_idx'));
 }
};
