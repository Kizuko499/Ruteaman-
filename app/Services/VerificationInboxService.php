<?php

namespace App\Services;

use App\Community\RegionAccess;
use App\Models\Hazard;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class VerificationInboxService
{
    public function authorize(User $u, Hazard $h): void
    {
        $p = app(PermissionService::class);
        $p->require($u, 'verification.decide');
        abort_unless(($h->publication_control ?? 'normal') === 'normal', 409, 'Laporan tidak tersedia untuk verifikasi.');
        if ($h->getAttribute('is_community')) {
            abort_unless(class_exists('App\\Community\\RegionAccess'), 403);
            app(RegionAccess::class)->require($u, $h, 'verification.decide');
            abort_unless(app(GlobalFeatureSettings::class)->enabled('verification'), 403);

            return;
        }
        $schools = app(HazardSchoolContext::class)->reviewSchools($h);
        $school = $schools->first(fn ($s) => $p->can($u, 'verification.decide', $s, true));
        if ($school) {
            $p->require($u, 'verification.decide', $school, true);

            return;
        }
        abort_unless($u->role->value === 'owner' && $schools->isEmpty() && app(GlobalFeatureSettings::class)->enabled('verification'), 403, 'Laporan berada di luar kewenangan verifikasi.');
    }

    public function records(Request $r): array
    {
        $user = $r->user();
        $p = app(PermissionService::class);
        $p->require($user, 'verification.decide');
        $owner = $user->role->value === 'owner';
        $hasCommunity = Schema::hasColumn('hazards', 'is_community');
        $d = $r->validate(['school' => 'nullable|integer|exists:schools,id']);
        $school = null;
        if (! empty($d['school'])) {
            $school = School::findOrFail($d['school']);
            abort_unless($p->inScope($user, $school), 403);
        } elseif (! $owner) {
            $school = $p->selectedSchool($r, 'verification.decide');
        }
        $q = Hazard::where('publication_control', 'normal')->whereIn('status', ['new', 'confirmed', 'disputed', 'expired'])
            ->whereRaw("COALESCE((SELECT decision FROM verifications WHERE hazard_id=hazards.id ORDER BY id DESC LIMIT 1),'') != 'rejected'")
            ->with('type')->withCount(['reports', 'confirmations'])->orderByDesc('requires_moderation')->orderByDesc('severity')->orderBy('first_reported_at')->orderBy('id');
        if ($school) {
            if ($hasCommunity) {
                $q->where('is_community', false);
            }
            $id = $school->id;
            $q->where(fn ($q) => $q->whereHas('reports', fn ($r) => $r->where('school_id', $id)->orWhere(fn ($r) => $r->whereNull('school_id')->whereHas('reporter', fn ($u) => $u->where('school_id', $id))))
                ->orWhere(fn ($q) => $q->whereDoesntHave('reports', fn ($r) => $r->whereNotNull('school_id')->orWhereHas('reporter', fn ($u) => $u->whereNotNull('school_id')))->whereRaw('EXISTS (SELECT 1 FROM schools s WHERE s.id=? AND ST_Intersects(s.catchment,hazards.location))', [$id])));
            $q->with(['reports' => fn ($q) => $q->where(fn ($q) => $q->where('school_id', $id)->orWhere(fn ($q) => $q->whereNull('school_id')->whereHas('reporter', fn ($u) => $u->where('school_id', $id))))->with(['school', 'reporter.school'])]);
        } else {
            $q->with(['reports.school', 'reports.reporter.school']);
        }
        $hazards = $q->paginate(20)->withQueryString();
        foreach ($hazards as $h) {
            $community = (bool) $h->getAttribute('is_community');
            $s = $school ?? app(HazardSchoolContext::class)->reviewSchools($h)->first(fn ($s) => $p->can($user, 'verification.decide', $s, true));
            $h->setRelation('verificationSchool', $s);
            $h->setAttribute('can_review', $community ? ($owner && class_exists('App\\Community\\RegionAccess') && app(RegionAccess::class)->allowed($user, 'verification.decide') && app(GlobalFeatureSettings::class)->enabled('verification')) : ($s ? $p->can($user, 'verification.decide', $s, true) : ($owner && app(GlobalFeatureSettings::class)->enabled('verification'))));
        }

        return ['hazards' => $hazards, 'schoolModel' => $school, 'allSchools' => $owner && $school === null];
    }
}
