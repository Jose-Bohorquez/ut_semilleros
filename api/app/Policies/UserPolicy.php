<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Solo admin puede gestionar usuarios.
     */
    public function manage(User $authUser): bool
    {
        return $authUser->role === 'ADMIN_SISTEMA';
    }
}