<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CodeDoubleAuthentification;
use App\Notifications\ReinitialisationMotDePasse;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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

    public function reclamations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reclamation::class);
    }

    public function reponsesReclamations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReponseReclamation::class);
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
     * Durée de validité, en minutes, d'un code de double authentification.
     */
    public const DUREE_CODE_DOUBLE_AUTH = 10;

    /**
     * Génère un code à 6 chiffres, l'enregistre haché avec sa date d'expiration et l'envoie par email.
     */
    public function envoyerCodeDoubleAuth(): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(self::DUREE_CODE_DOUBLE_AUTH),
        ])->save();

        $this->notify(new CodeDoubleAuthentification($code, self::DUREE_CODE_DOUBLE_AUTH));
    }

    /**
     * Indique si le code de double authentification en cours a expiré (ou n'existe pas).
     */
    public function codeDoubleAuthExpire(): bool
    {
        return ! $this->two_factor_expires_at || $this->two_factor_expires_at->isPast();
    }

    /**
     * Vérifie le code saisi : il doit correspondre au code haché et ne pas avoir expiré.
     */
    public function codeDoubleAuthValide(string $code): bool
    {
        return $this->two_factor_code
            && ! $this->codeDoubleAuthExpire()
            && Hash::check($code, $this->two_factor_code);
    }

    /**
     * Efface le code en cours (après usage ou après trop de tentatives).
     */
    public function effacerCodeDoubleAuth(): void
    {
        $this->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
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
        'two_factor_code',
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
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
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
