<?php
namespace App\Http\Controllers;
use App\Models\{School,User};
use App\Services\{PermissionService,SitePresentationService,AdminAuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AdminSchoolContactController extends Controller {
 public function index(Request $r,PermissionService $p){$p->require($r->user(),'schools.view');$schools=$p->scope(School::query(),$r->user())->orderBy('name')->get();return response()->view('admin.school-contacts',compact('schools'))->header('Cache-Control','private, no-store');}
 public function update(Request $r,School $school,PermissionService $p,SitePresentationService $service,AdminAuditService $audit){$p->require($r->user(),'schools.manage',$school,true);$r->validate(['help_contact'=>'nullable|string|max:300','publication_consent'=>'accepted']);$contact=$service->validateContact($r->input('help_contact'));DB::transaction(function()use($r,$school,$p,$contact,$audit){$u=User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();$s=School::whereKey($school->id)->lockForUpdate()->firstOrFail();$p->require($u,'schools.manage',$s,true);$settings=$s->settings??[];if($contact===null)unset($settings['help_contact']);else $settings['help_contact']=$contact;$s->update(['settings'=>$settings]);$audit->write($u,'school.help-contact.update','school',$s->id,['enabled'=>(bool)$contact],$s->id);});return to_route('admin.school-contacts')->with('success','Kontak sekolah disimpan. Pengaturan sekolah lainnya tetap dipertahankan.');}
}
