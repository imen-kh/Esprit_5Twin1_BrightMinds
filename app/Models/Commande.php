<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    public const STATUTS = ['en_attente', 'confirmee', 'preparee', 'expediee', 'en_transit', 'livree', 'refusee', 'annulee'];

    protected $fillable = [
        'user_id', 'distributeur_id', 'panier_id', 'nom', 'prenom', 'email', 'telephone',
        'adresse', 'ville', 'code_postal', 'commentaire', 'total', 'statut', 'date_commande',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:3',
            'date_commande' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function panier()
    {
        return $this->belongsTo(Panier::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'commande_produit')->withPivot('quantite', 'prix_unitaire', 'sous_total')->withTimestamps();
    }

    public function distributeur()
    {
        return $this->belongsTo(User::class, 'distributeur_id');
    }

    public function livraison()
    {
        return $this->hasOne(Livraison::class);
    }
}
