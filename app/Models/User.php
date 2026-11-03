<?php

namespace App\Models;

use App\Notifications\ReinitialisationMotDePasse;
use App\Notifications\VerificationAdresseEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Rôle Spatie aligné sur les colonnes role / type_acteur, qui restent la référence des modules.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasRecentlyCreated || $user->wasChanged(['role', 'type_acteur'])) {
                $user->synchroniserRole();
            }
        });
    }

    /**
     * Nom du rôle Spatie correspondant : admin, consommateur, ou le type d'acteur (producteur…).
     */
    public function roleSpatie(): ?string
    {
        return match ($this->role) {
            'admin', 'consommateur' => $this->role,
            'acteur' => $this->type_acteur,
            default => null,
        };
    }

    /**
     * Donne à l'utilisateur son rôle Spatie (sans effet tant que RolePermissionSeeder n'a pas créé les rôles).
     */
    public function synchroniserRole(): void
    {
        $role = $this->roleSpatie();

        if ($role && Role::where('name', $role)->where('guard_name', 'web')->exists()) {
            $this->syncRoles([$role]);
        } elseif (! $role) {
            $this->syncRoles([]);
        }
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'type_acteur',
        'password',
        // Champs du profil
        'telephone',
        'avatar',
        'nom_entreprise',
        'adresse',
        'ville',
        'matricule_fiscal',
        'description',
        'site_web',
        'latitude',
        'longitude',
        'preferences',
        'allergies',
    ];

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function harvests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    public function stockItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function reclamations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reclamation::class);
    }

    public function reponsesReclamations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReponseReclamation::class);
    }

    public function avis(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Avis::class);
    }

    /**
     * Commandes livrées de l'utilisateur qui contiennent ce produit (achat vérifié, réclamation liée).
     */
    public function commandesLivreesAvec(int $produitId): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->commandes()
            ->where('statut', 'livree')
            ->whereHas('products', fn ($q) => $q->whereKey($produitId))
            ->latest('id');
    }

    public function isConsommateur(): bool
    {
        return $this->role === 'consommateur';
    }
    /**
     * Indique si l'utilisateur est un administrateur.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Indique si l'utilisateur est un acteur de la chaîne.
     */
    public function isActeur(): bool
    {
        return $this->role === 'acteur';
    }

    public function isProducteur(): bool
    {
        return $this->type_acteur === 'producteur';
    }

    public function isTransformateur(): bool
    {
        return $this->type_acteur === 'transformateur';
    }

    public function isDistributeur(): bool
    {
        return $this->type_acteur === 'distributeur';
    }

    public function espaceRoute(): string
    {
        if ($this->isAdmin()) {
            return 'admin.dashboard';
        }

        if ($this->isProducteur()) {
            return 'acteur.produits.index';
        }

        if ($this->isTransformateur()) {
            return 'acteur.transformation';
        }

        if ($this->isDistributeur()) {
            return 'acteur.distribution';
        }

        return 'produits';
    }

    /**
     * URL de l'avatar : fichier envoyé par l'utilisateur, sinon image par défaut du template.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar
            ? asset('storage/'.$this->avatar)
            : asset('back/assets/img/avatars/1.png'));
    }

    /**
     * Envoie l'email de réinitialisation du mot de passe (version française NutriTrace).
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ReinitialisationMotDePasse($token));
    }

    /**
     * Envoie l'email de vérification de l'adresse (version française NutriTrace).
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerificationAdresseEmail);
    }

    /**
     * La double authentification est-elle active ? (clé scannée ET premier code validé)
     */
    public function aDoubleAuthentification(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Une activation est-elle en cours ? (clé générée, en attente du premier code)
     */
    public function doubleAuthentificationEnAttente(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at === null;
    }

    /**
     * Utilise un code de récupération : valable une seule fois, il est retiré de la liste.
     */
    public function utiliserCodeRecuperation(string $code): bool
    {
        $code = strtoupper(trim($code));
        $codes = $this->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $valide) {
            if (hash_equals($valide, $code)) {
                unset($codes[$index]);
                $this->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Désactive complètement la double authentification.
     */
    public function desactiverDoubleAuthentification(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            // Double authentification : clé et codes chiffrés en base avec APP_KEY
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function paniers()
    {
        return $this->hasMany(Panier::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function commandesDistribuees()
    {
        return $this->hasMany(Commande::class, 'distributeur_id');
    }

    public function livraisons()
    {
        return $this->hasMany(Livraison::class, 'distributeur_id');
    }
}
