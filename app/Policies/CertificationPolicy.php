<?php

namespace App\Policies;

use App\Models\Certification;
use App\Models\User;

class CertificationPolicy
{
    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }

    public function view(User $user, Certification $certification): bool
    {
        return $certification->acteur_id === $user->id;
    }

    public function update(User $user, Certification $certification): bool
    {
        return $certification->acteur_id === $user->id
            && in_array($certification->statut, ['en_attente', 'refusee'], true);
    }

    public function delete(User $user, Certification $certification): bool
    {
        return $certification->acteur_id === $user->id && $certification->statut !== 'validee';
    }
}
