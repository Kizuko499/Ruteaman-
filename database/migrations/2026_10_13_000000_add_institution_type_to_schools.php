<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {Schema::table('schools',function(Blueprint $t){$t->string('institution_type',12)->default('school');$t->char('country_code',2)->default('ID');$t->string('campus_code',64)->nullable();$t->index(['institution_type','status','country_code'],'schools_institution_status_country');});}
 public function down():void {if(DB::table('schools')->where('institution_type','campus')->exists())throw new \RuntimeException('Rollback diblokir: masih ada kampus. Pulihkan backup konsisten, jangan hilangkan jenis institusi pada data kampus.');Schema::table('schools',function(Blueprint $t){$t->dropIndex('schools_institution_status_country');$t->dropColumn(['institution_type','country_code','campus_code']);});}
};
