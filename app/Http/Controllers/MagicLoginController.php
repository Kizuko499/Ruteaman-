<?php
namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MagicLoginController extends Controller
{
    public function login(string $token): RedirectResponse
    {
        $userId = Cache::store('file')->pull('admin-login:'.$token);

        if (!$userId) {
            abort(404, 'Link tidak valid atau sudah kedaluwarsa.');
        }

        $user = User::whereKey($userId)->where('role', Role::Owner)->where('active', true)->first();

        if (!$user) {
            abort(404, 'Akun tidak ditemukan.');
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended('/admin');
    }
}
