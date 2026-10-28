<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up(): void {
  Schema::create('saved_routes',function(Blueprint $t){
   $t->id();$t->foreignId('school_id')->constrained()->cascadeOnDelete();$t->string('name');
   $t->json('geometry');$t->unsignedInteger('distance_m');$t->unsignedInteger('duration_s')->nullable();
   $t->string('source')->default('audit sekolah');$t->boolean('is_demo')->default(false);$t->timestamps();
  });
  Schema::table('hazards',fn(Blueprint $t)=>$t->boolean('is_demo')->default(false));
  DB::table('users')->where('role','verifier')->update(['role'=>'school_admin']);
 }
 public function down(): void {Schema::dropIfExists('saved_routes');Schema::table('hazards',fn(Blueprint $t)=>$t->dropColumn('is_demo'));}
};
