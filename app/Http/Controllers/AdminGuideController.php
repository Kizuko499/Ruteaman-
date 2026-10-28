<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Enums\Role;
use App\Services\PermissionService;
class AdminGuideController extends Controller {
 public function __invoke(Request $r,PermissionService $p){$sections=array_values(array_filter(config('admin_guide.sections'),function($s)use($r,$p){$gate=$s['gate'];return !$gate||($gate==='owner'?$r->user()->role===Role::Owner:$p->can($r->user(),$gate,$r->user()->school));}));return response()->view('admin.guide',compact('sections'))->header('Cache-Control','private, no-store');}
}
