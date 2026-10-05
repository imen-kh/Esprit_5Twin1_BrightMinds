<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueLivraison extends Model
{
    protected $table = 'historique_livraisons';

    protected $fillable = [
        'livraison_id', 'ancien_statut', 'nouveau_statut', 'user_id', 'commentaire', 'date_changement',
    ];

    protected function casts(): array
    {
        return ['date_changement' => 'datetime'];
    }

    public function livraison()
    {
        return $this->belongsTo(Livraison::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
