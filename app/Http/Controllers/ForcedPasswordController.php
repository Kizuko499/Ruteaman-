<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAccessService;
use App\Services\AdminPasswordPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ForcedPasswordController extends Controller
{
    public function show(Request $r)
    {
        if (! $r->user()->must_change_password) {
            return to_route($r->user()->role->admin() ? 'admin.index' : 'map');
        }

        return response()->view('auth.force-password')->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $r, AdminPasswordPolicy $policy)
    {
        $d = $r->validate(['current_password' => 'required|string', 'password' => ['required', 'confirmed', 'different:current_password', 'max:128', $policy->rule()]]);
        if (! Hash::check($d['current_password'], $r->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Kata sandi awal tidak sesuai.']);
        }$policy->validate($d['password'], $r->user()->display_name, $r->user()->email);
        $u = DB::transaction(function () use ($r, $d) {
            $u = User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $u->forceFill(['password' => $d['password'], 'must_change_password' => false])->save();
            app(AdminAccessService::class)->revoke($u);
            app(AuditLogger::class)->record($u, 'user.password_changed', 'user', $u->id, ['must_change_password' => false], $u->school_id);

            return $u;
        });
        Auth::setUser($u);
        $r->session()->regenerate();
        $r->session()->put('admin.session_version', $u->session_version);
        $r->session()->put('password_hash_web', $u->password);
        $r->session()->forget('admin.reauth_at');

        return to_route($u->role->admin() ? 'admin.index' : 'map')->with('success', 'Kata sandi awal sudah diganti.');
    }
}
