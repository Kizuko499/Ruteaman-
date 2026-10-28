<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('campus_catalog',function(Blueprint $t){$t->id();$t->string('source_key',191)->unique();$t->string('name',255);$t->char('country_code',2);$t->string('official_code',64)->nullable();$t->text('aliases');$t->text('search_text');$t->string('source',20);$t->text('source_url');$t->string('city',255)->nullable();$t->string('region',255)->nullable();$t->double('reference_latitude')->nullable();$t->double('reference_longitude')->nullable();$t->string('coordinate_accuracy',30);$t->string('source_status',60);$t->string('data_release',100);$t->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();$t->timestamps();$t->index(['country_code','name'],'campus_catalog_country_name');});}
 public function down():void {Schema::dropIfExists('campus_catalog');}
};
