<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up():void {
  // A persistent row serializes owner creation even when no owners exist yet.
  Schema::create('admin_transaction_locks',function(Blueprint $t){$t->unsignedTinyInteger('id')->primary();});
  DB::table('admin_transaction_locks')->insert(['id'=>1]);
 }
 public function down():void {Schema::dropIfExists('admin_transaction_locks');}
};
