<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

trait BasePolicyTrait
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($ability === 'viewAny' || $ability === 'view') {
            return null;
        }

        return false;
    }
}
