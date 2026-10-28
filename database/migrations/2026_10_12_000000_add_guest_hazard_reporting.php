<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('hazards',fn(Blueprint $t)=>$t->boolean('requires_moderation')->default(false)->index());
  Schema::table('hazard_reports',function(Blueprint $t){$t->boolean('is_guest')->default(false);$t->string('guest_name',80)->nullable();$t->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();$t->index(['school_id','is_guest']);});
 }
 public function down():void {
  Schema::table('hazard_reports',function(Blueprint $t){$t->dropIndex(['school_id','is_guest']);$t->dropConstrainedForeignId('school_id');$t->dropColumn(['is_guest','guest_name']);});
  Schema::table('hazards',function(Blueprint $t){$t->dropIndex(['requires_moderation']);$t->dropColumn('requires_moderation');});
 }
};
