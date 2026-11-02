<?php

namespace App\Policies;

use App\Models\Harvest;
use App\Models\User;

class HarvestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProducteur();
    }

    public function view(User $user, Harvest $harvest): bool
    {
        return $user->isProducteur() && $user->id === $harvest->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isProducteur();
    }

    public function update(User $user, Harvest $harvest): bool
    {
        return $this->view($user, $harvest);
    }

    public function delete(User $user, Harvest $harvest): bool
    {
        return $this->view($user, $harvest);
    }
}
