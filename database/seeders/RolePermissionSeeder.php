<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions (spatie/laravel-permission). Les colonnes users.role et users.type_acteur
 * restent la référence des modules : chaque compte reçoit le rôle Spatie correspondant
 * (User::synchroniserRole, aussi appelé automatiquement à chaque création ou changement de rôle).
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions de chaque rôle. L'admin les reçoit toutes.
     */
    private const ROLES = [
        'producteur' => ['gerer-produits', 'gerer-lots', 'declarer-etapes', 'gerer-certifications', 'repondre-avis'],
        // Le transformateur achète des matières premières : il peut aussi réclamer et donner un avis
        'transformateur' => ['gerer-produits', 'gerer-lots', 'declarer-etapes', 'gerer-certifications', 'transformer-lots', 'repondre-avis', 'deposer-reclamations', 'publier-avis'],
        'distributeur' => ['gerer-lots', 'declarer-etapes', 'gerer-livraisons', 'gerer-certifications'],
        'consommateur' => ['passer-commandes', 'deposer-reclamations', 'publier-avis'],
    ];

    /**
     * Permissions réservées à l'administration.
     */
    private const PERMISSIONS_ADMIN = [
        'gerer-categories',
        'valider-produits',
        'valider-certifications',
        'gerer-labels',
        'gerer-commandes',
        'traiter-reclamations',
        'moderer-avis',
        'gerer-environnement',
    ];

    public function run(): void
    {
        // Spatie garde les permissions en cache : on repart d'un cache propre
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $toutes = collect(self::ROLES)->flatten()->merge(self::PERMISSIONS_ADMIN)->unique();

        foreach ($toutes as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('admin', 'web')->syncPermissions($toutes->all());

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Comptes déjà présents (créés avant les rôles) : rôle aligné sur role / type_acteur
        User::query()->each(fn (User $user) => $user->synchroniserRole());

        $this->command?->info(sprintf('Rôles et permissions : %d rôles, %d permissions.', Role::count(), Permission::count()));
    }
}
