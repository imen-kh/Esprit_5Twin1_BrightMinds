<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote « utile » ou signalement d'un avis par un utilisateur (un de chaque au plus).
 */
class AvisReaction extends Model
{
    public const MOTIFS_SIGNALEMENT = [
        'insulte' => 'Propos injurieux',
        'spam' => 'Publicité ou spam',
        'hors_sujet' => 'Hors sujet',
        'faux' => 'Avis qui semble faux',
    ];

    protected $fillable = ['avis_id', 'user_id', 'type', 'motif'];

    public function avis(): BelongsTo
    {
        return $this->belongsTo(Avis::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
