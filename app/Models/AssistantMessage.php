<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message de la conversation Sahbi. « action » contient une action proposée à confirmer :
 * {outil, arguments, resume, statut: en_attente|confirmee|annulee, resultat}.
 */
class AssistantMessage extends Model
{
    protected $fillable = ['conversation_id', 'role', 'contenu', 'action', 'liens'];

    protected function casts(): array
    {
        return [
            'action' => 'array',
            'liens' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'conversation_id');
    }

    /**
     * Forme envoyée au widget.
     *
     * @return array<string, mixed>
     */
    public function pourWidget(): array
    {
        $action = $this->action;

        return [
            'id' => $this->id,
            'role' => $this->role,
            'contenu' => $this->contenu,
            'liens' => $this->liens ?? [],
            'action' => $action ? [
                'titre' => $action['titre'] ?? 'Action proposée',
                'details' => $action['details'] ?? [],
                'statut' => $action['statut'] ?? 'en_attente',
                'resultat' => $action['resultat'] ?? null,
            ] : null,
            'heure' => $this->created_at?->format('H:i'),
        ];
    }
}
