<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSession
{
    public function handle(Request $r, Closure $next)
    {
        if ($u = $r->user()) {
            $u = $u->fresh();
            Auth::setUser($u);
            $invalid = ! $u->active || $u->status !== 'active' || (int) $r->session()->get('admin.session_version', 0) !== $u->session_version;
            $last = (int) $r->session()->get('admin.last_seen', time());
            if ($u->role->admin() && time() - $last > config('ruteaman_admin.idle_minutes') * 60) {
                $invalid = true;
            }
            if ($invalid) {
                Auth::logout();
                $r->session()->invalidate();
                $r->session()->regenerateToken();
                if ($r->expectsJson()) {
                    return response()->json(['error' => ['code' => 'SESSION_EXPIRED', 'message' => 'Sesi berakhir atau akun dinonaktifkan.'], 'message' => 'Masuk kembali.'], 401);
                }

                return to_route('login')->withErrors(['email' => 'Sesi berakhir atau akun dinonaktifkan.']);
            }
            if ($u->must_change_password && ! $r->routeIs('password.force.*', 'logout')) {
                if ($r->expectsJson()) {
                    return response()->json(['message' => 'Ganti kata sandi awal terlebih dahulu.', 'error' => ['code' => 'PASSWORD_CHANGE_REQUIRED']], 428);
                }

                return to_route('password.force.show');
            }
            if ($u->role->admin()) {
                $r->session()->put('admin.last_seen', time());
            }
        }

        return $next($r);
    }
}
