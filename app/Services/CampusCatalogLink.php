<?php
namespace App\Services;
use App\Models\{CampusCatalogEntry,School,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class CampusCatalogLink {
 public function authorize(User $actor):void {
  $p=app(PermissionService::class);$actor=$p->fresh($actor);
  abort_unless($p->can($actor,'schools.provision')&&$p->allScope($actor),403,'Penghubung katalog hanya untuk admin platform berwenang dengan cakupan semua institusi.');
 }
 public function connect(User $actor,CampusCatalogEntry $entry,int $schoolId,string $reason):void {
  $this->authorize($actor);
  DB::transaction(function()use($actor,$entry,$schoolId,$reason){
   app(AdminAccessService::class)->lockOwners();$actor=User::whereKey($actor->id)->lockForUpdate()->firstOrFail();$this->authorize($actor);
   $e=CampusCatalogEntry::whereKey($entry->id)->lockForUpdate()->firstOrFail();$s=School::whereKey($schoolId)->lockForUpdate()->firstOrFail();
   app(PermissionService::class)->require($actor,'schools.manage',$s,true);
   if($e->school_id!==null){if((int)$e->school_id===$s->id)return;throw ValidationException::withMessages(['school_id'=>'Entri sudah terhubung. Tidak mengganti tautan institusi yang sudah ada.']);}
   if($s->institution_type!=='campus'||$s->status!=='active')throw ValidationException::withMessages(['school_id'=>'Pilih kampus terdaftar aktif, bukan sekolah, kampus dijeda, atau arsip.']);
   if(strtoupper($s->country_code)!==strtoupper($e->country_code))throw ValidationException::withMessages(['school_id'=>'Negara kampus dan entri sumber harus sama.']);
   if($e->official_code&&$s->campus_code&&trim($e->official_code)!==trim($s->campus_code))throw ValidationException::withMessages(['school_id'=>'Kode institusi berbeda. Periksa identitas/cabang; jangan menghubungkan institusi lain.']);
   $e->school_id=$s->id;$e->save();
   app(AdminAuditService::class)->write($actor,'school.catalog_linked','school',$s->id,['catalog_id'=>$e->id,'source_key'=>$e->source_key],$s->id,['catalog_school_id'=>['before'=>null,'after'=>$s->id]],$reason);
  });
 }
}
