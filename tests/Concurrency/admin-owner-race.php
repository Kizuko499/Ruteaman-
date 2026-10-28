<?php
// Run only on a disposable *_tests PostgreSQL database; no production data cleanup.
require __DIR__.'/../../vendor/autoload.php';$app=require __DIR__.'/../../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
use App\Services\{AdminAccessService,AdminAccountService};
use Illuminate\Support\Facades\DB;
if(!in_array(DB::getDriverName(),['pgsql','mysql','mariadb'],true)||!str_ends_with(DB::connection()->getDatabaseName(),'_tests'))throw new RuntimeException('Requires disposable spatial database ending _tests.');
config(['mail.default'=>'array','ruteaman_admin.owner_max'=>3]);
if(($argv[1]??'')==='worker'){
 $actor=User::findOrFail((int)$argv[2]);$key=$argv[3];try{
  DB::transaction(function()use($actor,$key){app(AdminAccessService::class)->lockOwners();usleep(200000);app(AdminAccountService::class)->create($actor,['display_name'=>'Race '.$key,'email'=>'race.'.$key.'.pengelola@ruteaman.my.id','contact_email'=>'race-'.$key.'@contact.invalid','role'=>'owner','school_id'=>null,'permissions'=>[]]);});echo 'created';
 }catch(App\Exceptions\AdminException $e){echo $e->errorCode;}exit;
}
if(($argv[1]??'')==='last-worker'){
 try{app(AdminAccessService::class)->lifecycle(User::findOrFail((int)$argv[2]),User::findOrFail((int)$argv[2]),'suspend');echo 'unexpected';}catch(App\Exceptions\AdminException $e){echo $e->errorCode;}exit;
}
if(User::where('role','owner')->exists())throw new RuntimeException('Use a fresh empty race test database.');
$make=function($email){$u=(new User)->forceFill(['display_name'=>'Race fixture','email'=>$email,'contact_email'=>'race@contact.invalid','role'=>'owner','status'=>'active','active'=>true,'password'=>'RaceFixturePassword123!']);$u->save();return $u;};
$a=$make('race-chief@ruteaman.my.id');$b=$make('race-second@ruteaman.my.id');
$run=function(array $tasks){$procs=[];foreach($tasks as $args){$cmd=array_merge([PHP_BINARY,__FILE__],$args);$proc=proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pipes);$procs[]=[$proc,$pipes];}$out=[];foreach($procs as [$proc,$pipes]){$out[]=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);foreach($pipes as $pipe)fclose($pipe);$code=proc_close($proc);if($code||$error)throw new RuntimeException('Worker failed (no sensitive payload printed).');}return $out;};
$result=$run([['worker',(string)$a->id,'first'],['worker',(string)$a->id,'second']]);sort($result);if($result!==['VALIDATION_FAILED','created']||User::where('role','owner')->whereIn('status',['active','pending'])->count()!==3)throw new RuntimeException('Owner limit race invariant failed.');
// Fixtures only: isolate the last-active-owner scenario, retaining pending records.
$b->forceFill(['status'=>'suspended','active'=>false])->save();$result=$run([['last-worker',(string)$a->id],['last-worker',(string)$a->id]]);if($result!==['LAST_OWNER','LAST_OWNER']||!$a->fresh()->active)throw new RuntimeException('Last-owner race invariant failed.');echo "Owner cap race: PASS\nLast-owner concurrent suspension: PASS\n";
