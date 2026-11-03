<?php

namespace App\Services;

use App\Models\Avis;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\ReponseReclamation;
use App\Models\User;
use App\Notifications\ReclamationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Cycle de vie d'une réclamation, partagé par les formulaires, l'agent Sahbi et la conversion d'avis :
 * création analysée par l'IA, réponses, statuts, escalade et évaluation, avec chronologie et notifications.
 */
class GestionReclamationService
{
    private const RANG_PRIORITE = ['faible' => 1, 'moyenne' => 2, 'haute' => 3, 'urgente' => 4];

    public function __construct(private AiReclamationService $ia) {}

    /**
     * @param  array{produit_id: int, sujet: string, description: string, commande_id?: ?int, lot_id?: ?int, priorite?: ?string}  $data
     */
    public function creer(User $auteur, array $data, string $source = 'formulaire', ?Avis $avis = null): Reclamation
    {
        $produit = Product::query()->findOrFail($data['produit_id']);
        $analyse = $this->ia->analyserReclamation($data['sujet'], $data['description'], $produit);

        $choisie = $data['priorite'] ?? null;
        // Le client garde sa priorité, sauf si l'IA détecte plus grave (risque sanitaire, produit inutilisable)
        $priorite = $choisie && self::RANG_PRIORITE[$choisie] >= self::RANG_PRIORITE[$analyse['priorite']] ? $choisie : $analyse['priorite'];

        $reclamation = DB::transaction(function () use ($auteur, $data, $source, $avis, $analyse, $priorite, $choisie) {
            $reclamation = Reclamation::query()->create([
                'user_id' => $auteur->id,
                'produit_id' => $data['produit_id'],
                'commande_id' => $data['commande_id'] ?? null,
                'lot_id' => $data['lot_id'] ?? null,
                'avis_id' => $avis?->id,
                'source' => $source,
                'sujet' => $data['sujet'],
                'description' => $data['description'],
                'statut' => 'en_attente',
                'priorite' => $priorite,
                'categorie' => $analyse['categorie'],
                'langue' => $analyse['langue'],
                'sentiment' => $analyse['sentiment'],
                'priorite_suggeree' => $analyse['priorite'],
                'suggestion_reponse' => $analyse['suggestion'],
                'resume_ia' => $analyse['resume'],
            ]);

            $reclamation->journaliser('creation', match ($source) {
                'assistant' => 'Réclamation créée avec l\'assistant Sahbi',
                'avis' => 'Réclamation ouverte à partir d\'un avis '.$avis?->note.'★',
                default => 'Réclamation déposée',
            }, $auteur);

            $libelleIa = ($analyse['source'] === 'ia' ? 'Analyse DeepSeek' : 'Analyse automatique').' : '.$reclamation->categorieLibelle().', sentiment '.$analyse['sentiment'];
            if ($choisie && $priorite !== $choisie) {
                $libelleIa .= ', priorité relevée à « '.$reclamation->prioriteLibelle().' »';
            }
            $reclamation->journaliser('ia', $libelleIa);

            return $reclamation;
        });

        // L'acteur qui vend le produit est prévenu dans son espace
        $vendeur = $produit->user;
        if ($vendeur && $vendeur->id !== $auteur->id && $vendeur->isActeur()) {
            $vendeur->notify(new ReclamationNotification($reclamation, 'nouvelle'));
        }

        return $reclamation->fresh();
    }

    public function repondre(Reclamation $reclamation, User $auteur, string $message): ReponseReclamation
    {
        $reponse = $reclamation->reponses()->create([
            'user_id' => $auteur->id,
            'message' => $message,
        ]);

        $equipe = $auteur->isAdmin() || ($auteur->isActeur() && $auteur->id !== $reclamation->user_id);
        $role = $auteur->isAdmin() ? 'Service client' : ($equipe ? 'Producteur' : 'Client');
        $reclamation->journaliser('reponse', $role.' : nouveau message', $auteur);

        if ($equipe && $reclamation->statut === 'en_attente') {
            $reclamation->update(['statut' => 'en_cours']);
            $reclamation->journaliser('statut', 'Statut passé à « En cours »', $auteur);
        }

        if ($auteur->id !== $reclamation->user_id) {
            $reclamation->user?->notify(new ReclamationNotification($reclamation, 'reponse'));
        }

        return $reponse;
    }

    public function changerStatut(Reclamation $reclamation, string $statut, User $auteur): void
    {
        if ($reclamation->statut === $statut) {
            return;
        }

        $reclamation->update(['statut' => $statut]);
        $reclamation->journaliser('statut', 'Statut passé à « '.$reclamation->statutLibelle().' »', $auteur);

        if ($auteur->id !== $reclamation->user_id) {
            $reclamation->user?->notify(new ReclamationNotification($reclamation, 'statut'));
        }
    }

    /**
     * Le client relance un dossier qui n'avance pas : la priorité monte d'un cran.
     */
    public function escalader(Reclamation $reclamation, User $auteur, string $raison): void
    {
        $rangs = array_flip(self::RANG_PRIORITE);
        $nouvelle = $rangs[min(4, self::RANG_PRIORITE[$reclamation->priorite] + 1)];

        $reclamation->update(['priorite' => $nouvelle]);
        $reclamation->journaliser('escalade', 'Demande de contact humain : '.mb_strimwidth($raison, 0, 180, '…'), $auteur);

        $admins = User::query()->where('role', 'admin')->get();
        Notification::send($admins, new ReclamationNotification($reclamation, 'escalade'));
    }

    public function evaluer(Reclamation $reclamation, User $auteur, int $note, ?string $commentaire): void
    {
        $reclamation->update([
            'satisfaction_note' => $note,
            'satisfaction_commentaire' => $commentaire,
        ]);

        $reclamation->journaliser('satisfaction', 'Traitement évalué '.$note.'/5 par le client', $auteur);
    }

    /**
     * Enregistre l'analyse « Qui est responsable ? » sur le dossier.
     *
     * @return array{responsable: string, explication: string, confiance: int, source: string}
     */
    public function analyserResponsabilite(Reclamation $reclamation, User $auteur): array
    {
        $resultat = $this->ia->analyserResponsabilite($reclamation);

        $reclamation->update([
            'responsable_suggere' => $resultat['responsable'],
            'responsable_explication' => $resultat['explication'],
        ]);

        $reclamation->journaliser('responsable', 'Maillon probable : '.$reclamation->responsableLibelle().' (confiance '.$resultat['confiance'].' %)', $auteur);

        return $resultat;
    }
}
