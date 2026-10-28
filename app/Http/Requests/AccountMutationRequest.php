<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Policies\AccountPolicy;
use App\Services\LanguagePreferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountMutationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && (new AccountPolicy)->update($this->user(), $this->user());
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('display_name'))) {
            $this->merge(['display_name' => preg_replace('/\s+/u', ' ', trim(strip_tags((string) $this->display_name)))]);
        }
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->email))]);
        }
    }

    public function rules(): array
    {
        return match ($this->route()->getName()) {
            'account.profile.update' => ['display_name' => ['bail', 'required', 'string', 'min:2', 'max:30', function ($attribute, $value, $fail) {
                if (preg_match('/[\p{C}]/u', $value)) {
                    $fail('Pilih nama panggilan lain.');
                }
                foreach (config('account.blocked_names') as $word) {
                    if (preg_match('/(?:^|[\s\pP])'.preg_quote($word, '/').'(?:$|[\s\pP])/iu', $value)) {
                        $fail('Pilih nama panggilan lain.');
                        break;
                    }
                }
                if (User::where('school_id', $this->user()->school_id)->where('active', true)->whereKeyNot($this->user()->id)->whereRaw('LOWER(display_name) = ?', [mb_strtolower($value)])->exists()) {
                    $fail('Nama panggilan sudah digunakan di sekolahmu.');
                }
            }]],
            'account.avatar.store' => ['avatar' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048', 'dimensions:min_width=128,min_height=128,max_width=4096,max_height=4096']],
            'account.avatar.preset' => ['avatar_preset' => ['required', Rule::in(array_map(fn ($i) => 'preset-'.$i, range(1, 8)))]],
            'account.email.update' => ['email' => ['bail', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')], 'current_password' => ['bail', 'required', 'string', 'current_password:web']],
            'account.preferences' => ['appearance' => ['required', 'in:light,system'], 'locale' => ['required', Rule::in(LanguagePreferences::supported())], 'notification_preferences' => ['sometimes', 'array'], 'notification_preferences.*' => ['sometimes', 'boolean']],
            'account.password' => ['current_password' => ['bail', 'required', 'string', 'current_password:web'], 'password' => ['bail', 'required', 'string', 'confirmed', Password::min($this->user()->role->admin() ? 12 : 10)->letters()->mixedCase()->numbers()->symbols()]],
            'account.sessions.destroy' => ['current_password' => ['bail', 'required', 'string', 'current_password:web']],
            'account.destroy' => ['current_password' => ['bail', 'required', 'string', 'current_password:web'], 'confirmation' => ['required', 'in:HAPUS']],
            default => [],
        };
    }

    public function messages(): array
    {
        return ['current_password.current_password' => __('Kata sandi saat ini tidak sesuai.'), 'password.confirmed' => __('Konfirmasi kata sandi harus sama.'), 'confirmation.in' => __('Ketik HAPUS untuk mengonfirmasi.'), 'avatar.max' => __('Foto maksimal 2 MB.'), 'avatar.mimetypes' => __('Gunakan foto JPEG, PNG, atau WebP.'), 'avatar.dimensions' => __('Foto minimal 128 × 128 dan maksimal 4096 × 4096 piksel.'), 'appearance.in' => __('Pilihan tampilan belum tersedia.')];
    }

    public function attributes(): array
    {
        return ['display_name' => __('nama tampilan'), 'email' => 'email', 'password' => __('kata sandi baru'), 'current_password' => __('kata sandi saat ini'), 'avatar' => __('foto profil')];
    }
}
