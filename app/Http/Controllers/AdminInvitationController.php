<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class AdminInvitationController extends Controller
{
    public function show(Request $r, string $token)
    {
        $d = $r->validate(['email' => 'required|email|max:254']);
        $u = User::where('email', $d['email'])->first();
        $valid = $u && in_array($u->status, ['pending', 'active'], true) && Password::broker('invites')->tokenExists($u, $token);

        return response()->view('admin.accept-invite', compact('token', 'valid') + ['email' => $d['email']])->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function store(Request $r, string $token, AdminAccountService $accounts)
    {
        $d = $r->validate(['email' => 'required|email|max:254', 'password' => 'required|string|min:12|max:128|confirmed']);
        $u = $accounts->accept($d['email'], $token, $d['password']);
        Auth::login($u);
        $r->session()->regenerate();
        $r->session()->put('admin.session_version', $u->session_version);
        $r->session()->put('admin.last_seen', time());

        return to_route($u->role->admin() ? 'admin.index' : 'map')->with('success', 'Kata sandi disetel. Undangan tidak dapat dipakai lagi.');
    }
}
