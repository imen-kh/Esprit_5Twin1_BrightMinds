<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Livraison extends Model
{
    public const STATUTS = ['en_preparation', 'expediee', 'en_transit', 'livree', 'annulee'];

    protected $fillable = [
        'commande_id', 'distributeur_id', 'adresse_livraison', 'ville', 'code_postal',
        'telephone', 'statut', 'numero_suivi', 'date_expedition', 'date_livraison_prevue',
        'date_livraison', 'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'date_expedition' => 'datetime',
            'date_livraison_prevue' => 'datetime',
            'date_livraison' => 'datetime',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function distributeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributeur_id');
    }

    public function historique()
    {
        return $this->hasMany(HistoriqueLivraison::class)->orderBy('date_changement');
    }
}
