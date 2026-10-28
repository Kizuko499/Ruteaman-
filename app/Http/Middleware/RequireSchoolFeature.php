<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use App\Models\School;
class RequireSchoolFeature {
 public function __construct(private PermissionService $permissions){}
 public function handle(Request $r,Closure $next,string $key){$s=$r->user()?->school;
  if(!$s&&$r->filled('school'))$s=School::find($r->input('school'));
  if(!$s)throw new \App\Exceptions\AdminException('FEATURE_DISABLED','Pilih sekolah dengan fitur yang aktif.');
  $this->permissions->requireFeature($s,$key,!$r->isMethod('GET'));return $next($r);
 }
}
