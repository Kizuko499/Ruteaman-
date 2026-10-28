<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Requests\SchoolUserRequest;
use App\Services\AccountService;
use Illuminate\Http\Request;
class SchoolUserController extends Controller {
 public function index(Request $r){$school=app(\App\Services\PermissionService::class)->selectedSchool($r,'users.view');$query=trim((string)$r->query('q',''));abort_if(mb_strlen($query)>60,422);$users=User::where('school_id',$school->id)->whereIn('role',['student','verifier'])->when($query,fn($q)=>$q->whereRaw('LOWER(display_name) LIKE ?',['%'.mb_strtolower($query).'%']))->orderBy('display_name')->paginate(20)->withQueryString();return view('school.users',compact('users','query','school'));}
 public function update(SchoolUserRequest $r,User $user,AccountService $service){$service->manage($r->user(),$user,$r->validated());return $r->expectsJson()?response()->json(['message'=>'Pengguna diperbarui; perubahan tercatat di audit','member'=>['role'=>$user->fresh()->role->value,'active'=>$user->fresh()->active]]):back()->with('success','Pengguna diperbarui');}
}
