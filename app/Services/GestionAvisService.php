<?php

namespace App\Services;

use App\Models\Avis;
use App\Models\AvisReaction;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cycle de vie d'un avis : publication analysée par l'IA, réactions, droit de réponse du producteur,
 * conversion d'un avis négatif en réclamation.
 */
class GestionAvisService
{
    public function __construct(
        private AvisIAService $ia,
        private GestionReclamationService $reclamations,
    ) {}

    /**
     * @param  array{note: int, commentaire: string, titre?: ?string}  $data
     */
    public function publier(User $auteur, Product $produit, array $data): Avis
    {
        $analyse = $this->ia->analyser((int) $data['note'], $data['titre'] ?? null, $data['commentaire'], $produit);

        return Avis::query()->create([
            'user_id' => $auteur->id,
            'produit_id' => $produit->id,
            'note' => (int) $data['note'],
            'titre' => $data['titre'] ?? null,
            'commentaire' => $data['commentaire'],
            'achat_verifie' => Avis::estAchatVerifie($auteur, $produit),
            ...$this->champsAnalyse($analyse),
        ]);
    }

    /**
     * @param  array{note: int, commentaire: string, titre?: ?string}  $data
     */
    public function modifier(Avis $avis, array $data): Avis
    {
        $analyse = $this->ia->analyser((int) $data['note'], $data['titre'] ?? null, $data['commentaire'], $avis->produit);

        // Un avis masqué par l'administrateur le reste, même modifié
        $champs = $this->champsAnalyse($analyse);
        if ($avis->statut === 'masque') {
            unset($champs['statut']);
        }

        $avis->update([
            'note' => (int) $data['note'],
            'titre' => $data['titre'] ?? null,
            'commentaire' => $data['commentaire'],
            ...$champs,
        ]);

        return $avis;
    }

    /**
     * Vote « utile » (bascule) ou signalement (une fois). Trop de signalements : retour en modération.
     */
    public function reagir(Avis $avis, User $user, string $type, ?string $motif = null): void
    {
        DB::transaction(function () use ($avis, $user, $type, $motif) {
            $existante = $avis->reactions()->where('user_id', $user->id)->where('type', $type)->first();

            if ($type === 'utile' && $existante) {
                $existante->delete();
            } elseif (! $existante) {
                AvisReaction::query()->create(['avis_id' => $avis->id, 'user_id' => $user->id, 'type' => $type, 'motif' => $motif]);
            }

            $avis->utile_count = $avis->reactions()->where('type', 'utile')->count();
            $avis->signalements_count = $avis->reactions()->where('type', 'signalement')->count();

            if ($avis->statut === 'publie' && $avis->signalements_count >= config('reclamations.avis_seuil_signalements', 3)) {
                $avis->statut = 'en_moderation';
                $avis->moderation_raison = 'Signalé par '.$avis->signalements_count.' utilisateurs';
            }

            $avis->save();
        });
    }

    public function repondreProducteur(Avis $avis, string $reponse): void
    {
        $avis->update([
            'reponse_producteur' => $reponse,
            'reponse_producteur_at' => now(),
        ]);
    }

    /**
     * Avis négatif → réclamation pré-remplie, liée à l'avis.
     */
    public function convertirEnReclamation(Avis $avis, User $auteur): Reclamation
    {
        if ($existante = $avis->reclamation) {
            return $existante;
        }

        $commande = $auteur->commandesLivreesAvec($avis->produit_id)->first();

        return $this->reclamations->creer($auteur, [
            'produit_id' => $avis->produit_id,
            'commande_id' => $commande?->id,
            'sujet' => $avis->titre ?: 'Problème signalé dans mon avis',
            'description' => $avis->commentaire,
        ], 'avis', $avis);
    }

    /**
     * @param  array{decision: string, raison: ?string, sentiment: string, aspects: array<string, int>, probleme_detecte: bool, source: string}  $analyse
     * @return array<string, mixed>
     */
    private function champsAnalyse(array $analyse): array
    {
        return [
            'statut' => $analyse['decision'] === 'moderer' ? 'en_moderation' : 'publie',
            'moderation_raison' => $analyse['raison'],
            'sentiment' => $analyse['sentiment'],
            'aspects' => $analyse['aspects'],
            'probleme_detecte' => $analyse['probleme_detecte'],
        ];
    }
}
