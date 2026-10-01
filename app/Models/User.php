<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }
}
