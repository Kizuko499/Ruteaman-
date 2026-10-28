<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  $columns=['avatar_path'=>'string','avatar_preset'=>'string','appearance'=>'string','locale'=>'string','notif_seen_at'=>'timestamp','pending_email'=>'string','email_verification_token'=>'string','email_verification_expires_at'=>'timestamp'];
  foreach($columns as $name=>$type)if(!Schema::hasColumn('users',$name))Schema::table('users',function(Blueprint $t)use($name,$type){
   $column=$t->{$type}($name);if($name==='appearance')$column->default('system');elseif($name==='locale')$column->default('id');else $column->nullable();
  });
 }
 public function down():void {foreach(['avatar_path','avatar_preset','appearance','locale','notif_seen_at','pending_email','email_verification_token','email_verification_expires_at'] as $name)if(Schema::hasColumn('users',$name))Schema::table('users',fn(Blueprint $t)=>$t->dropColumn($name));}
};
