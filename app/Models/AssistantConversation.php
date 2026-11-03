<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conversation d'un utilisateur avec l'agent Sahbi.
 */
class AssistantConversation extends Model
{
    protected $fillable = ['user_id', 'cloturee_at'];

    protected function casts(): array
    {
        return ['cloturee_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'conversation_id')->orderBy('id');
    }

    /**
     * Conversation en cours de l'utilisateur, créée au besoin.
     */
    public static function courantePour(User $user): self
    {
        return self::query()->where('user_id', $user->id)->whereNull('cloturee_at')->latest('id')->first()
            ?? self::query()->create(['user_id' => $user->id]);
    }

    /**
     * Texte brut de la conversation, joint à une réclamation lors d'une escalade.
     */
    public function transcription(): string
    {
        return $this->messages
            ->filter(fn (AssistantMessage $message) => filled($message->contenu))
            ->map(fn (AssistantMessage $message) => ($message->role === 'user' ? 'Client' : 'Sahbi').' : '.$message->contenu)
            ->implode("\n");
    }
}
