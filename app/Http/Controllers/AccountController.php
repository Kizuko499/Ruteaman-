<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountMutationRequest;
use App\Http\Resources\AccountResource;
use App\Models\User;
use App\Policies\AccountPolicy;
use App\Services\AccountNotificationService;
use App\Services\AccountService;
use App\Services\AvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function __construct(private AccountService $accounts, private AvatarService $avatars, private AccountNotificationService $notifications) {}

    public function show(Request $r, string $section = 'profile')
    {
        abort_unless(in_array($section, ['profile', 'settings', 'security', 'privacy'], true), 404);

        return response()->view('pages.account.'.$section, ['user' => $r->user(), 'canUpload' => $this->avatars->allowed($r->user()), 'sessions' => $section === 'security' ? $this->accounts->sessions($r->user()) : [], 'hasSessions' => config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))])->header('Cache-Control', 'private, no-store');
    }

    public function api(Request $r)
    {
        return new AccountResource($r->user());
    }

    private function saved(Request $r, string $message = 'Perubahan tersimpan')
    {
        return $r->expectsJson() ? response()->json(['message' => $message, 'account' => new AccountResource($r->user()->fresh())]) : back()->with('success', $message);
    }

    public function profile(AccountMutationRequest $r)
    {
        $this->accounts->profile($r->user(), $r->validated());

        return $this->saved($r);
    }

    public function avatar(AccountMutationRequest $r)
    {
        $this->avatars->upload($r->user(), $r->file('avatar'));

        return $this->saved($r, 'Foto profil tersimpan');
    }

    public function preset(AccountMutationRequest $r)
    {
        $this->avatars->replace($r->user(), null, $r->validated('avatar_preset'));

        return $this->saved($r);
    }

    public function removeAvatar(AccountMutationRequest $r)
    {
        $this->avatars->replace($r->user(), null);

        return $this->saved($r, 'Kembali ke avatar inisial');
    }

    public function avatarImage(Request $r, User $user)
    {
        abort_unless((new AccountPolicy)->viewAvatar($r->user(), $user), 403);
        abort_unless($user->avatar_path, 404);
        $size = $r->integer('size', 256);
        abort_unless(in_array($size, [96, 256, 512], true), 404);
        $path = $size === 512 ? $user->avatar_path : str_replace('.webp', '-'.$size.'.webp', $user->avatar_path);
        abort_unless(Storage::disk('avatars')->exists($path), 404);

        return response()->file(Storage::disk('avatars')->path($path), ['Content-Type' => 'image/webp', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'])->setPrivate();
    }

    public function email(AccountMutationRequest $r)
    {
        $this->accounts->verification($r->user(), $r->validated('email'));

        return $this->saved($r, 'Tautan verifikasi dikirim ke email baru. Email lama tetap aktif sampai verifikasi.');
    }

    public function resend(AccountMutationRequest $r)
    {
        $this->accounts->verification($r->user());

        return $this->saved($r, 'Tautan verifikasi dikirim. Periksa kotak masuk atau folder spam.');
    }

    public function verify(Request $r, User $user, string $token)
    {
        $this->accounts->verify($r->user(), $user, $token);

        return to_route($r->user()->role->admin() ? 'admin.account.profile' : 'account.profile')->with('success', 'Email terverifikasi');
    }

    public function preferences(AccountMutationRequest $r)
    {
        $data = $r->validated();
        if ($r->has('notification_preferences')) {
            $prefs = [];
            foreach (['report_status', 'report_comments', 'report_new_in_scope', 'admin_activity'] as $key) {
                $prefs[$key] = (bool) $r->input('notification_preferences.'.$key, false);
            }
            $data['notification_preferences'] = $prefs;
        }
        $r->user()->update($data);
        app()->setLocale($r->user()->locale);

        return $this->saved($r);
    }

    public function password(AccountMutationRequest $r)
    {
        $this->accounts->password($r->user(), $r->validated('password'));
        $r->session()->regenerate();
        $r->session()->put('password_hash_web', $r->user()->password);

        return $this->saved($r, 'Kata sandi diperbarui. Perangkat lain akan diminta masuk kembali.');
    }

    public function sessions(Request $r)
    {
        return response()->json(['sessions' => $this->accounts->sessions($r->user())]);
    }

    public function logoutSessions(AccountMutationRequest $r)
    {
        $this->accounts->logoutSessions($r->user(), $r->validated('current_password'));
        $r->session()->put('password_hash_web', $r->user()->fresh()->password);

        return $this->saved($r, 'Perangkat lain telah dikeluarkan');
    }

    public function notifications(Request $r)
    {
        return response()->view('pages.account.notifications', ['user' => $r->user(), 'items' => $this->notifications->items($r->user())])->header('Cache-Control', 'private, no-store');
    }

    public function seen(AccountMutationRequest $r)
    {
        $r->user()->update(['notif_seen_at' => now()]);

        return $this->saved($r, 'Semua pembaruan ditandai sudah dibaca');
    }

    public function export(Request $r)
    {
        $data = $this->accounts->export($r->user());

        return response()->streamDownload(fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)), 'ruteaman-data-saya.json', ['Content-Type' => 'application/json', 'Cache-Control' => 'private, no-store']);
    }

    public function destroy(AccountMutationRequest $r)
    {
        $this->accounts->delete($r->user(), $this->avatars);
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return $r->expectsJson() ? response()->json(['message' => 'Akun dihapus. Laporan dipertahankan tanpa identitas.', 'redirect' => route('home')]) : to_route('home')->with('success','Akun dihapus');
    }
}
