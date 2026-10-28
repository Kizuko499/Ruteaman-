<?php

namespace App\Services;

use App\Models\Hazard;
use App\Models\School;
use App\Models\User;
use App\Models\WeatherSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HazardEvidenceService
{
    public function __construct(private HazardLoadService $loads) {}

    public function counts(Hazard $hazard): array
    {
        $reporters = $hazard->reports()->whereNotNull('reporter_id')->distinct()->pluck('reporter_id');
        $confirmers = $hazard->confirmations()->where('vote', 'still_there')->whereNotIn('user_id', $reporters)->pluck('user_id');

        return ['reports' => $reporters->count(), 'confirmations' => $confirmers->count(), 'has_photo' => $hazard->reports()->whereNotNull('photo_path')->exists(), 'verified' => $hazard->status->value === 'verified' && $hazard->verifications()->latest('id')->value('decision') === 'verified', 'independent' => $reporters->merge($confirmers)->unique()->count()];
    }

    public function refresh(Hazard $hazard): Hazard
    {
        $e = $this->counts($hazard);
        $state = $hazard->status->value;
        if ($hazard->requires_moderation && $state !== 'resolved') {
            $state = 'new';
        } elseif (in_array($state, ['new', 'confirmed', 'verified'], true)) {
            $state = $e['verified'] ? 'verified' : ($e['independent'] >= 3 ? 'confirmed' : 'new');
        }
        $hazard->update(['confidence' => $this->loads->confidence($e['reports'], $e['confirmations'], $e['has_photo'], $e['verified']), 'status' => $state]);

        return $hazard;
    }

    public function guard(User $user, Hazard $hazard): void
    {
        abort_unless(($hazard->publication_control ?? 'normal') === 'normal', 409, 'Laporan ini ditangguhkan atau dihapus dari publik.');
        if ($hazard->requires_moderation) {
            $school = School::find($hazard->reports()->value('school_id'));
            abort_unless($user->role->admin() && $school && app(PermissionService::class)->can($user, 'verification.decide', $school, true), 403, 'Laporan berada di luar kewenangan verifikasi.');
        }
        if ($user->role->admin()) {
            $p = app(PermissionService::class);
            $s = $p->scope(app(HazardSchoolContext::class)->pointSchools($hazard), $user)->first();
            abort_unless($s, 403, 'Hazard berada di luar cakupan.');
        } else {
            abort_unless($user->school_id && Hazard::whereKey($hazard->id)->inSchool($user->school_id)->exists(), 403, 'Hazard berada di luar wilayah sekolah.');
        }
    }

    public function confirm(User $user, Hazard $hazard, string $vote): void
    {
        abort_if($hazard->requires_moderation, 403, 'Laporan harus diverifikasi pengelola sebelum dikonfirmasi.');
        $this->guard($user, $hazard);
        if ($hazard->reports()->where('reporter_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['vote' => 'Pelapor tidak dihitung sebagai pengonfirmasi independen. Minta pengguna lain mengonfirmasi.']);
        }
        DB::transaction(function () use ($user, $hazard, $vote) {
            $hazard = Hazard::whereKey($hazard->id)->lockForUpdate()->firstOrFail();
            $this->guard($user, $hazard);
            abort_if($hazard->requires_moderation, 403, 'Laporan harus diverifikasi pengelola sebelum dikonfirmasi.');
            $expired = $hazard->status->value === 'expired' || (! Hazard::whereKey($hazard->id)->active()->exists() && $hazard->status->value !== 'resolved');
            abort_if($hazard->status->value === 'resolved' || $hazard->verifications()->latest('id')->value('decision') === 'rejected' || ($expired && $vote !== 'still_there'), 409, 'Konfirmasi kondisi ini ditutup.');
            if ($expired) {
                $hazard->update(['status' => 'disputed', 'last_confirmed_at' => now(), 'resolved_at' => null]);
            }
            $existing = $hazard->confirmations()->where('user_id', $user->id)->first();
            if ($existing?->vote === $vote && ! $expired) {
                return;
            } // retries do not refresh age or multiply evidence
            $hazard->confirmations()->updateOrCreate(['user_id' => $user->id], ['vote' => $vote, 'created_at' => now()]);
            if ($vote === 'still_there') {
                $hazard->update(['last_confirmed_at' => now()]);
            }
            $still = $hazard->confirmations()->where('vote', 'still_there')->count();
            $gone = $hazard->confirmations()->where('vote', 'gone')->count();
            if ($gone >= 2 && $gone > $still) {
                $hazard->update(['status' => 'disputed']);
            }
            $this->refresh($hazard);
            $this->audit($user->id, 'confirmation.'.$vote, $hazard->id);
        });
    }

    public function inputs(Hazard $hazard): array
    {
        $hazard->loadMissing('type');
        $e = $this->counts($hazard);
        $age = max(0, (int) ($hazard->last_confirmed_at ?? $hazard->first_reported_at)->diffInMinutes(now()));
        $schools = app(HazardSchoolContext::class)->pointSchools($hazard)->orderBy('id')->get(['bmkg_area_code', 'timezone']);
        $areas = $schools->pluck('bmkg_area_code')->filter()->unique();
        $timezone = $schools->first()?->timezone ?? 'Asia/Jakarta';
        $hour = now($timezone)->hour;
        $night = $hour >= 18 || $hour < 6;
        $rain = WeatherSnapshot::whereIn('area_code', $areas)->where('valid_until', '>', now())->where('rain_flag', true)->exists();

        return ['public_slug' => $hazard->public_slug, 'type' => $hazard->type->label, 'severity' => $hazard->severity,
            'reports' => $e['reports'], 'confirmations' => $e['confirmations'], 'has_photo' => $e['has_photo'], 'verified' => $e['verified'],
            'confidence' => $this->loads->confidence($e['reports'], $e['confirmations'], $e['has_photo'], $e['verified']),
            'freshness' => $hazard->type->night_only && ! $night ? 0 : $this->loads->freshness($age, $hazard->type->ttl_minutes), 'age_minutes' => $age, 'ttl_minutes' => $hazard->type->ttl_minutes,
            'weather_multiplier' => $hazard->type->rain_sensitive && $rain ? 1.5 : 1, 'weather_label' => $rain ? 'Hujan (cache/manual)' : 'Tidak ada cache hujan aktif', 'status' => $hazard->status_label];
    }

    public function audit(?int $actor, string $action, int $id, array $meta = [], array $changes = [], ?string $reason = null): void
    {
        app(AuditLogger::class)->record($actor ? User::find($actor) : null, $action, 'hazard', $id, $meta, $meta['school_id'] ?? null, $changes, $reason);
    }
}
