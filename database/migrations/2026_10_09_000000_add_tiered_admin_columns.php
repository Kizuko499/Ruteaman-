<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up():void {
  Schema::table('users',function(Blueprint $t){$t->string('status',20)->default('active')->index();$t->string('contact_email')->nullable();$t->json('permissions')->nullable();$t->json('school_scope')->nullable();$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('last_login_at')->nullable();$t->boolean('must_change_password')->default(false);$t->unsignedInteger('session_version')->default(0);$t->index('role');});
  Schema::table('schools',function(Blueprint $t){$t->string('status',20)->default('active')->index();$t->json('features')->nullable();$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();});
  DB::table('users')->where('active',false)->update(['status'=>'suspended']);
  foreach(['school_admin','verifier'] as $role)DB::table('users')->where('role',$role)->update(['permissions'=>json_encode(config('ruteaman_roles.ceilings.'.$role))]);
  DB::table('schools')->update(['features'=>json_encode(config('ruteaman_admin.features'))]);
 }
 public function down():void {
  Schema::table('schools',function(Blueprint $t){$t->dropConstrainedForeignId('created_by');$t->dropColumn(['status','features']);});
  Schema::table('users',function(Blueprint $t){$t->dropConstrainedForeignId('created_by');$t->dropIndex(['role']);$t->dropColumn(['status','contact_email','permissions','school_scope','last_login_at','must_change_password','session_version']);});
 }
};
