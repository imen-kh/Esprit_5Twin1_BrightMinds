<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Événement de la chronologie d'une réclamation (création, statut, réponse, analyse IA…).
 */
class HistoriqueReclamation extends Model
{
    /**
     * Icône Boxicons (back), Font Awesome (front) et couleur de chaque type d'événement.
     */
    public const TYPES = [
        'creation' => ['icone' => 'bx-plus-circle', 'fa' => 'fa-plus-circle', 'couleur' => 'primary'],
        'statut' => ['icone' => 'bx-transfer', 'fa' => 'fa-exchange-alt', 'couleur' => 'info'],
        'reponse' => ['icone' => 'bx-message-dots', 'fa' => 'fa-comment-dots', 'couleur' => 'secondary'],
        'ia' => ['icone' => 'bx-bot', 'fa' => 'fa-robot', 'couleur' => 'success'],
        'responsable' => ['icone' => 'bx-git-branch', 'fa' => 'fa-project-diagram', 'couleur' => 'warning'],
        'escalade' => ['icone' => 'bx-up-arrow-circle', 'fa' => 'fa-level-up-alt', 'couleur' => 'danger'],
        'satisfaction' => ['icone' => 'bx-star', 'fa' => 'fa-star', 'couleur' => 'warning'],
        'rappel' => ['icone' => 'bx-error', 'fa' => 'fa-exclamation-triangle', 'couleur' => 'danger'],
    ];

    protected $table = 'historique_reclamations';

    protected $fillable = ['reclamation_id', 'user_id', 'type', 'libelle'];

    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function icone(): string
    {
        return self::TYPES[$this->type]['icone'] ?? 'bx-radio-circle';
    }

    public function iconeFront(): string
    {
        return self::TYPES[$this->type]['fa'] ?? 'fa-circle';
    }

    public function couleur(): string
    {
        return self::TYPES[$this->type]['couleur'] ?? 'secondary';
    }
}
