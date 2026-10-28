<?php
namespace App\Services;
use App\Models\{AuditLog,Hazard,User};
use Illuminate\Database\Eloquent\Builder;
final class AccountNotificationService {
 public const LABELS=['confirmation.still_there'=>'Laporanmu dikonfirmasi komunitas','confirmation.gone'=>'Komunitas melaporkan hazard sudah surut','verification.verified'=>'Diverifikasi oleh verifikator sekolah','verification.needs_evidence'=>'Verifikator meminta bukti tambahan','verification.rejected'=>'Laporanmu ditolak','hazard.resolved'=>'Hazard sudah surut'];
 public function query(User $user):Builder{return AuditLog::where('entity','hazard')->whereIn('action',array_keys(self::LABELS))->whereExists(fn($q)=>$q->selectRaw('1')->from('hazard_reports')->whereColumn('hazard_reports.hazard_id','audit_logs.entity_id')->where('hazard_reports.reporter_id',$user->id)->whereColumn('hazard_reports.created_at','<=','audit_logs.created_at'));}
 public function unread(User $user):int{return $this->query($user)->when($user->notif_seen_at,fn($q)=>$q->where('created_at','>',$user->notif_seen_at))->count();}
 public function items(User $user):\Illuminate\Contracts\Pagination\LengthAwarePaginator {
  $items=$this->query($user)->orderByDesc('created_at')->orderByDesc('id')->paginate(20);
  $hazards=Hazard::with(['type','verifications'])->whereIn('id',$items->pluck('entity_id'))->get()->keyBy('id');
  $items->getCollection()->transform(function($item)use($hazards,$user){$hazard=$hazards->get($item->entity_id);$note=null;
   if($item->action==='verification.rejected')$note=$hazard?->verifications->where('decision','rejected')->filter(fn($v)=>$v->created_at->lessThanOrEqualTo($item->created_at))->sortByDesc('id')->first()?->note;
   return ['id'=>$item->id,'label'=>self::LABELS[$item->action],'hazard'=>$hazard?->type?->label,'url'=>$hazard?route('hazards.public',$hazard):null,'note'=>$note,'created_at'=>$item->created_at,'unread'=>!$user->notif_seen_at||$item->created_at->greaterThan($user->notif_seen_at)];
  });return $items;
 }
}
