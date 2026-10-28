<?php

namespace App\Services;

use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class AdminPasswordPolicy
{
    public function rule(): Password
    {
        $p = Password::min(config('ruteaman_admin.password_min', 12))->mixedCase()->numbers()->symbols();

        return config('ruteaman_admin.password_uncompromised') ? $p->uncompromised() : $p;
    }

    public function validate(string $password, string $name, string $email): void
    {
        validator(['password' => $password], ['password' => ['required', 'string', 'max:128', $this->rule()]])->validate();
        $pw = mb_strtolower($password);
        foreach ([$name, $email, explode('@', $email)[0]] as $v) {
            if (mb_strlen(trim($v)) >= 3 && ($pw === mb_strtolower(trim($v)) || str_contains($pw, mb_strtolower(trim($v))))) {
                throw ValidationException::withMessages(['initial_password' => 'Kata sandi tidak boleh memuat nama atau alias akun.']);
            }
        }
    }

    public function generate(): string
    {
        $sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%*-_'];
        $out = '';
        foreach ($sets as $s) {
            $out .= $s[random_int(0, strlen($s) - 1)];
        }$all = implode('', $sets);
        while (strlen($out) < 20) {
            $out .= $all[random_int(0, strlen($all) - 1)];
        }
        $chars = str_split($out);
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i],$chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}
