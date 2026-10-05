<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Panier extends Model
{
    protected $fillable = ['user_id', 'statut'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'panier_produit')->withPivot('quantite')->withTimestamps();
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}
