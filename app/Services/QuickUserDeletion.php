<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class QuickUserDeletion
{
    public function issue(Request $request, User $target): string
    {
        app(OwnerUserService::class)->owner($request->user());
        $token = Str::random(64);
        $entries = array_filter($request->session()->get('owner_delete_confirmations', []), fn ($entry) => $entry['expires'] > time());
        $entries[$target->id] = [
            'hash' => hash('sha256', $token), 'actor' => $request->user()->id,
            'version' => $request->user()->session_version, 'target' => $this->fingerprint($target), 'expires' => time() + 600,
        ];
        $request->session()->put('owner_delete_confirmations', array_slice($entries, -10, null, true));

        return $token;
    }

    public function consume(Request $request, User $target, string $token): void
    {
        $entries = $request->session()->get('owner_delete_confirmations', []);
        $entry = $entries[$target->id] ?? null;
        if (! $entry || $entry['expires'] <= time() || (int) $entry['actor'] !== (int) $request->user()->id
            || (int) $entry['version'] !== (int) $request->user()->session_version
            || ! hash_equals($entry['target'], $this->fingerprint($target)) || ! hash_equals($entry['hash'], hash('sha256', $token))) {
            throw ValidationException::withMessages(['delete_token' => 'Konfirmasi halaman sudah berubah atau kedaluwarsa. Muat ulang halaman akun lalu coba lagi.']);
        }
        if (! Cache::add('owner-delete-used:'.$entry['hash'], true, 600)) {
            throw ValidationException::withMessages(['delete_token' => 'Konfirmasi ini sudah digunakan. Muat ulang daftar pengguna.']);
        }
        unset($entries[$target->id]);
        $request->session()->put('owner_delete_confirmations', $entries);
    }

    private function fingerprint(User $target): string
    {
        return hash('sha256', json_encode([$target->id, $target->display_name, $target->email, $target->role->value, $target->status, $target->school_id, $target->session_version]));
    }
}
