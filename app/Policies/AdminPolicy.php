<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\School;
use App\Models\User;
use App\Services\PermissionService;

class AdminPolicy
{
    public function viewSchool(User $u, School $s): bool
    {
        return app(PermissionService::class)->can($u, 'schools.view', $s);
    }

    public function manageSchool(User $u, School $s): bool
    {
        return app(PermissionService::class)->can($u, 'schools.manage', $s, true);
    }

    public function viewUser(User $u, User $target): bool
    {
        $p = app(PermissionService::class);
        if ($u->id === $target->id) {
            return true;
        }if ($u->role === Role::Owner) {
            return $p->can($u, 'users.view');
        }

        return $u->role->tier() < $target->role->tier() && $p->can($u, 'users.view', $target->school) && ($target->school_id ? $p->inScope($u, $target->school_id) : $p->allScope($u));
    }
}
