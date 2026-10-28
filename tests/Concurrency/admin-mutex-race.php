<?php
// Empty-owner and pre-existing transaction snapshot race, on a disposable DB only.
require __DIR__.'/../../vendor/autoload.php';$app=require __DIR__.'/../../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
use App\Services\AdminAccessService;
use Illuminate\Support\Facades\DB;
if(!str_ends_with(DB::connection()->getDatabaseName(),'_tests'))throw new RuntimeException('Requires disposable database ending _tests.');
if(($argv[1]??'')==='worker'){
 DB::transaction(function()use($argv){
  // Intentionally establish a snapshot BEFORE acquiring the mutex.
  User::where('role','owner')->count();usleep(250000);
  app(AdminAccessService::class)->lockOwners();usleep(200000);
  if(User::where('role','owner')->lockForUpdate()->get()->count()>=1){echo 'blocked';return;}
  $u=(new User)->forceFill(['display_name'=>'First owner fixture','email'=>'first-'.$argv[2].'@ruteaman.my.id','password'=>'FixturePasswordOnly123!','role'=>'owner','status'=>'active','active'=>true]);$u->save();echo 'created';
 });exit;
}
if(User::where('role','owner')->exists())throw new RuntimeException('Fresh database with no owners required.');
$procs=[];foreach(['a','b'] as $key){$proc=proc_open([PHP_BINARY,__FILE__,'worker',$key],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$procs[]=[$proc,$pipes];}
$out=[];foreach($procs as [$proc,$pipes]){$out[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);foreach($pipes as $pipe)fclose($pipe);if(proc_close($proc)||$err)throw new RuntimeException('Race worker failed.');}
sort($out);if($out!==['blocked','created']||User::where('role','owner')->count()!==1)throw new RuntimeException('Empty-owner mutex invariant failed.');
echo "Empty-owner mutex with stale snapshot: PASS\n";
