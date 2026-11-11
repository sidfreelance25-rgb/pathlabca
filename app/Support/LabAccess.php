<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

class LabAccess
{
    public static function allows(?User $user, string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        $role = $user->getAttribute('role');

        if (! $role instanceof Role) {
            return false;
        }

        $permissions = $role->permissions();

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
