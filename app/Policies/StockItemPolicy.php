<?php

namespace App\Policies;

use App\Models\StockItem;
use App\Models\User;

class StockItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isProducteur();
    }

    public function view(User $user, StockItem $stockItem): bool
    {
        return $user->isProducteur() && $user->id === $stockItem->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isProducteur();
    }

    public function update(User $user, StockItem $stockItem): bool
    {
        return $this->view($user, $stockItem);
    }
}
