<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Exceptions\AdminException;
class MinimumAdminTier {public function handle(Request $r,Closure $next,string $tier='4'){if(!$r->user()?->role->admin()||$r->user()->role->tier()>(int)$tier)throw new AdminException('FORBIDDEN_SCOPE','Konsol hanya untuk tingkat admin yang diizinkan.');return $next($r);}}
