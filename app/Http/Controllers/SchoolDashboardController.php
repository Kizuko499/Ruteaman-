<?php
namespace App\Http\Controllers;
use App\Models\{AuditLog,Escalation,Hazard,School};
use App\Services\HazardEvidenceService;
use Illuminate\Http\{JsonResponse,RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class SchoolDashboardController extends Controller {
 public function index():View {
  $schoolModel=app(\App\Services\PermissionService::class)->selectedSchool(request(),request()->routeIs('school.metrics')?'metrics.view':'dashboard.view');$school=$schoolModel->id;
  $hazards=Hazard::with('type')->withCount(['reports','confirmations'])->active()->inSchool($school)->orderByDesc('severity')->get();
  $escalations=Escalation::with('hazard.type')->whereHas('hazard',fn($q)=>$q->inSchool($school))->latest('id')->take(20)->get();
  $logs=AuditLog::where('entity','hazard')->whereIn('entity_id',Hazard::inSchool($school)->select('id'))->latest('id')->take(20)->get();
  $p=app(\App\Services\PermissionService::class);if(!$p->can(request()->user(),'hazards.view',$schoolModel))$hazards=collect();if(!$p->can(request()->user(),'escalations.view',$schoolModel))$escalations=collect();if(!$p->can(request()->user(),'audit.view',$schoolModel))$logs=collect();$schools=$p->scope(School::query(),request()->user())->where('status','!=','archived')->orderBy('name')->get(['id','name']);return view('school.dashboard',compact('hazards','escalations','logs','schoolModel','schools'));
 }
 public function escalate(Request $request,Hazard $hazard,HazardEvidenceService $evidence):RedirectResponse {
  $evidence->guard($request->user(),$hazard);
  $d=$request->validate(['channel'=>'required|string|max:100']);
  DB::transaction(function()use($request,$hazard,$d,$evidence){
   Hazard::whereKey($hazard->id)->lockForUpdate()->firstOrFail();
   $item=Escalation::firstOrCreate(['hazard_id'=>$hazard->id,'channel'=>$d['channel'],'response_status'=>'draft']);
   if($item->wasRecentlyCreated)$evidence->audit($request->user()->id,'escalation.draft',$hazard->id,['escalation_id'=>$item->id]);
  });
  return to_route('hazards.package',$hazard)->with('success','Paket eskalasi siap. Tidak memuat identitas pelapor.');
 }
 public function updateEscalation(Request $request,Escalation $escalation,HazardEvidenceService $evidence):RedirectResponse {
  $evidence->guard($request->user(),$escalation->hazard);
  $d=$request->validate(['response_status'=>'required|in:draft,forwarded,responded,no_response','channel'=>'required|string|max:100','response_note'=>'nullable|string|max:1000']);
  DB::transaction(function()use($request,$escalation,$d,$evidence){
   $item=Escalation::whereKey($escalation->id)->lockForUpdate()->firstOrFail();
   $item->update([...$d,'forwarded_at'=>in_array($d['response_status'],['forwarded','responded','no_response'],true)?($item->forwarded_at??now()):null,'response_at'=>$d['response_status']==='responded'?now():null]);
   $evidence->audit($request->user()->id,'escalation.'.$d['response_status'],$item->hazard_id,['escalation_id'=>$item->id]);
  });
  return back()->with('success','Status tindak lanjut disimpan.');
 }
 public function package(Request $request,Hazard $hazard,HazardEvidenceService $evidence):View {
  $evidence->guard($request->user(),$hazard);
  $coords=\App\Services\SpatialSql::coordinates('latitude','longitude');
  $hazard=Hazard::whereKey($hazard->id)->select('hazards.*')->selectRaw($coords)->with(['type','reports','verifications','escalations'])->withCount('confirmations')->firstOrFail();
  $text="PAKET ESKALASI RUTEAMAN\nJenis: {$hazard->type->label}\nKoordinat: {$hazard->latitude}, {$hazard->longitude}\nLaporan: {$hazard->reports->count()} | Konfirmasi: {$hazard->confirmations_count}\nStatus: {$hazard->status_label}\nTerakhir dikonfirmasi: {$hazard->last_confirmed_at}\nFoto dan riwayat verifikasi: ".route('hazards.public',$hazard)."\n\nInformasi komunitas, bukan jaminan kondisi jalan. Tanpa identitas pelapor. Pengiriman ke kanal resmi dilakukan manual; belum ada integrasi dua arah pemerintah.";
  return view('school.package',compact('hazard','text'));
 }
 public function metrics():JsonResponse {
  $schoolModel=app(\App\Services\PermissionService::class)->selectedSchool(request(),request()->routeIs('school.metrics')?'metrics.view':'dashboard.view');$school=$schoolModel->id;
  return response()->json(['active_hazards'=>Hazard::active()->inSchool($school)->count(),'verified_hazards'=>Hazard::active()->inSchool($school)->where('status','verified')->count(),'forwarded'=>Escalation::whereHas('hazard',fn($q)=>$q->inSchool($school))->whereNotNull('forwarded_at')->count()]);
 }
}
