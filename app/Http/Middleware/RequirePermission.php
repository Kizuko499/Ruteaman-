<?php

namespace App\Http\Middleware;

use App\Exceptions\AdminException;
use App\Models\Escalation;
use App\Models\Hazard;
use App\Models\School;
use App\Services\HazardSchoolContext;
use App\Services\PermissionService;
use App\Services\VerificationInboxService;
use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $r, Closure $next, string $key)
    {
        $s = $r->route('school');
        if (! $s instanceof School) {
            $s = null;
        }
        $h = $r->route('hazard');
        if (! $h instanceof Hazard && $r->route('escalation') instanceof Escalation) {
            $h = $r->route('escalation')->hazard;
        }
        if ($h instanceof Hazard) {
            $context = app(HazardSchoolContext::class);
            if ($key === 'verification.decide') {
                app(VerificationInboxService::class)->authorize($r->user(), $h);

                return $next($r);
            }
            $s = $this->permissions->scope($context->pointSchools($h), $r->user())->orderBy('id')->first();
            if (! $s) {
                throw new AdminException('FORBIDDEN_SCOPE', 'Hazard berada di luar cakupan.');
            }
        }
        if (! $s && $r->query('school')) {
            $s = $this->permissions->selectedSchool($r, $key);
        }if (! $s && $r->user()->school_id) {
            $s = $r->user()->school;
        }
        $this->permissions->require($r->user(), $key, $s, ! $r->isMethod('GET'));

        return $next($r);
    }
}
