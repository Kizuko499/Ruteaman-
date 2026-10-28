<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Exceptions\AdminException;
class RequireReauth {
 public function handle(Request $r,Closure $next){if(time()-(int)$r->session()->get('admin.reauth_at',0)>config('ruteaman_admin.reauth_minutes')*60)throw new AdminException('REAUTH_REQUIRED','Konfirmasi kata sandi ulang sebelum melanjutkan.',428);return $next($r);}
}
