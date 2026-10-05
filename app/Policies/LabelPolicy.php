<?php

namespace App\Policies;

use App\Models\User;

class LabelPolicy
{
    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }
}
