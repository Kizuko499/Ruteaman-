<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::create('home_gallery_groups',function(Blueprint $t){
   $t->id();$t->string('key',30)->unique();$t->string('orientation',12);$t->string('source',12)->default('admin');$t->string('mode',12)->default('stay');$t->unsignedInteger('interval_value')->default(30);$t->string('interval_unit',12)->default('seconds');$t->json('category_ids')->nullable();$t->foreignId('school_id')->nullable()->constrained()->nullOnDelete();$t->string('fixed_token',80)->nullable();$t->timestamp('rotation_anchor');$t->timestamps();
  });
  Schema::create('home_gallery_photos',function(Blueprint $t){
   $t->uuid('id')->primary();$t->foreignId('group_id')->constrained('home_gallery_groups')->cascadeOnDelete();$t->foreignId('type_id')->nullable()->constrained('hazard_types')->nullOnDelete();$t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();$t->string('path');$t->string('caption',140);$t->string('orientation',12);$t->unsignedInteger('width');$t->unsignedInteger('height');$t->unsignedInteger('sort_order')->default(0);$t->boolean('enabled')->default(true);$t->timestamps();$t->index(['group_id','enabled','sort_order']);
  });
  foreach(['routes'=>'landscape','reporting'=>'portrait','followup'=>'portrait'] as $key=>$orientation)DB::table('home_gallery_groups')->insert(['key'=>$key,'orientation'=>$orientation,'source'=>'admin','mode'=>'stay','fixed_token'=>'static-1','rotation_anchor'=>now(),'created_at'=>now(),'updated_at'=>now()]);
 }
 public function down():void {Schema::dropIfExists('home_gallery_photos');Schema::dropIfExists('home_gallery_groups');}
};
