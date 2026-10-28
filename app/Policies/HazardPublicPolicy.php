<?php

namespace App\Policies;

use App\Community\RegionAccess;
use App\Models\Hazard;
use App\Models\User;
use App\Services\HazardEvidenceService;
use App\Services\HazardSchoolContext;
use App\Services\PermissionService;

final class HazardPublicPolicy
{
    public function view(?User $user, Hazard $hazard): bool
    {
        if (($hazard->publication_control ?? 'normal') !== 'normal' || $hazard->requires_moderation) {
            if (($hazard->publication_control ?? 'normal') === 'normal' && $user?->active && $hazard->reports()->where('reporter_id', $user->id)->exists()) {
                return true;
            }
            if (! $user || ! $user->role->admin()) {
                return false;
            }
            $p = app(PermissionService::class);
            $schools = app(HazardSchoolContext::class)->reviewSchools($hazard);

            return ($user->role->value === 'owner' && $schools->isEmpty() && $p->can($user, 'verification.decide')) || $schools->contains(fn ($s) => ($p->can($user, 'verification.decide', $s) || $p->can($user, 'hazards.reopen', $s)));
        }
        if ($hazard->verifications()->latest('id')->value('decision') === 'rejected') {
            return false;
        }
        if ($user?->role->admin() && app(PermissionService::class)->can($user, 'verification.decide')) {
            $context = app(HazardSchoolContext::class);
            if ($hazard->is_community && class_exists('App\\Community\\RegionAccess')) {
                $access = app(RegionAccess::class);
                if ($access->allowed($user, 'verification.decide') && $access->scope(Hazard::whereKey($hazard->id), $user)->exists()) {
                    return true;
                }
            } elseif ($context->reviewSchools($hazard)->contains(fn ($s) => app(PermissionService::class)->can($user, 'verification.decide', $s)) || ($user->role->value === 'owner' && $context->reviewSchools($hazard)->isEmpty())) {
                return true;
            }
        }
        // Legacy unpublished reports stay restricted; explicitly published reports may be unverified.
        $established = ($hazard->status->value === 'expired' && $hazard->published_at !== null) || in_array($hazard->status->value, ['confirmed', 'verified', 'disputed', 'resolved'], true)
         || $hazard->verifications()->where('decision', 'verified')->exists()
         || app(HazardEvidenceService::class)->counts($hazard)['independent'] >= 3;

        return $hazard->published_at !== null || $established || ($user && $user->active && $user->school_id && Hazard::whereKey($hazard->id)->inSchool($user->school_id)->exists());
    }
}
