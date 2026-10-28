<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Validation\ValidationException;

final class ReportPublicationPolicy
{
    public function requiresReview(School $school, bool $guest): bool
    {
        return (bool) ($school->features[$guest ? 'require_guest_review' : 'require_account_review'] ?? app(GlobalFeatureSettings::class)->enabled($guest ? 'require_guest_review' : 'require_account_review'));
    }

    public function validateSettings(array $features): void
    {
        if (($features['require_guest_review'] || $features['require_account_review']) && ! $features['verification']) {
            throw ValidationException::withMessages(['features.verification' => 'Aktifkan fitur Verifikasi bila laporan wajib diperiksa sebelum publikasi.']);
        }
    }
}
