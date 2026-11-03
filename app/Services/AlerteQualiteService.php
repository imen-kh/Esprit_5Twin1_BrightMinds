<?php

namespace App\Services;

use App\Models\AlerteQualite;
use App\Models\Avis;
use App\Models\Commande;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use App\Notifications\RappelProduitNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Alerte qualité : quand plusieurs réclamations et avis négatifs visent le même produit en peu de temps,
 * DeepSeek lit l'ensemble, dit s'il s'agit du même problème et conseille ou non un rappel du lot.
 * L'administrateur décide : déclencher le rappel (module Traçabilité) ou classer l'alerte.
 */
class AlerteQualiteService
{
    /** Catégories qui relèvent de la qualité du produit (une question ou un retard de livraison n'en font pas partie). */
    private const CATEGORIES_QUALITE = ['qualite', 'securite_alimentaire', 'emballage', 'quantite', 'autre'];

    public function __construct(private DeepSeekService $deepseek) {}

    /**
     * Recherche les produits qui dépassent le seuil et crée ou met à jour leurs alertes (sans appel IA).
     *
     * @return Collection<int, AlerteQualite>
     */
    public function detecter(?Product $seulement = null): Collection
    {
        $depuis = now()->subDays((int) config('reclamations.alerte_fenetre_jours', 14));
        $seuil = (int) config('reclamations.alerte_seuil', 3);

        $reclamations = Reclamation::query()
            ->where('created_at', '>=', $depuis)
            ->where('statut', '!=', 'rejetee')
            ->where(fn ($q) => $q->whereIn('categorie', self::CATEGORIES_QUALITE)->orWhereNull('categorie'))
            ->when($seulement, fn ($q) => $q->where('produit_id', $seulement->id))
            ->get(['id', 'produit_id', 'lot_id', 'categorie', 'created_at']);

        $avis = Avis::query()
            ->where('created_at', '>=', $depuis)
            ->where('note', '<=', 2)
            ->where('statut', '!=', 'masque')
            ->when($seulement, fn ($q) => $q->where('produit_id', $seulement->id))
            ->get(['id', 'produit_id', 'created_at']);

        $alertes = collect();

        foreach ($reclamations->pluck('produit_id')->merge($avis->pluck('produit_id'))->unique() as $produitId) {
            // Les signaux déjà couverts par une alerte traitée ne relancent pas d'alerte
            $derniereTraitee = AlerteQualite::query()->where('produit_id', $produitId)->where('statut', '!=', 'ouverte')->latest('traitee_at')->first();
            $apres = $derniereTraitee?->traitee_at;

            $sesReclamations = $reclamations->where('produit_id', $produitId)->filter(fn ($r) => ! $apres || $r->created_at->gt($apres));
            $sesAvis = $avis->where('produit_id', $produitId)->filter(fn ($a) => ! $apres || $a->created_at->gt($apres));

            if ($sesReclamations->count() + $sesAvis->count() < $seuil) {
                continue;
            }

            $alertes->push($this->enregistrer((int) $produitId, $sesReclamations, $sesAvis));
        }

        return $alertes;
    }

    /**
     * Analyse DeepSeek d'une alerte. Sans IA, le résumé local reste affiché.
     */
    public function analyser(AlerteQualite $alerte): AlerteQualite
    {
        $alerte->loadMissing('produit', 'lot');

        $signaux = $alerte->reclamations()
            ->map(fn (Reclamation $r) => '- Réclamation '.$r->reference.' ('.$r->categorieLibelle().', '.$r->created_at?->format('d/m').') : '.$r->sujet.'. '.mb_substr($r->description, 0, 300))
            ->merge($alerte->avisNegatifs()->map(fn (Avis $a) => '- Avis '.$a->note.'★ ('.$a->created_at?->format('d/m').') : '.mb_substr($a->commentaire, 0, 300)))
            ->implode("\n");

        $systeme = <<<'PROMPT'
        Tu es responsable qualité de NutriTrace. Plusieurs clients se plaignent du même produit sur une courte période.
        Lis les signalements et réponds uniquement par un objet JSON :
        {
          "meme_probleme": true | false,
          "titre": "intitulé du problème, 8 mots maximum",
          "resume": "2 phrases en français : ce que les clients décrivent, combien, depuis quand",
          "gravite": "faible" | "moyenne" | "elevee",
          "recommandation": "action concrète conseillée à l'administrateur, 1 à 2 phrases",
          "rappel_conseille": true | false,
          "motif_rappel": "texte de 20 à 200 caractères affiché aux consommateurs si rappel, sinon null"
        }
        "elevee" et rappel conseillé si un risque pour la santé est évoqué (intoxication, moisissure, corps étranger, produit avarié chez plusieurs clients).
        Un simple retard ou une remarque de goût isolée ne justifie pas de rappel.
        PROMPT;

        $contexte = 'Produit : '.$alerte->produit?->name.($alerte->lot ? ' — lot le plus cité : '.$alerte->lot->code : '')."\nSignalements :\n".$signaux;
        $ia = $this->deepseek->json($systeme, $contexte);

        if (! $ia || ! filled($ia['resume'] ?? null)) {
            return $alerte;
        }

        $gravite = in_array($ia['gravite'] ?? null, array_keys(AlerteQualite::GRAVITES), true) ? $ia['gravite'] : $alerte->gravite;
        $motif = is_string($ia['motif_rappel'] ?? null) ? mb_substr(trim($ia['motif_rappel']), 0, 500) : null;

        $alerte->update([
            'titre' => mb_substr((string) ($ia['titre'] ?? $alerte->titre), 0, 255),
            'resume' => mb_substr((string) $ia['resume'], 0, 1000),
            'gravite' => ($ia['meme_probleme'] ?? true) ? $gravite : 'faible',
            'recommandation' => mb_substr((string) ($ia['recommandation'] ?? ''), 0, 1000) ?: $alerte->recommandation,
            'rappel_conseille' => (bool) ($ia['rappel_conseille'] ?? false),
            'motif_rappel' => $motif && mb_strlen($motif) >= 10 ? $motif : $alerte->motif_rappel,
            'analyse_ia' => true,
        ]);

        return $alerte;
    }

    /**
     * Rappel du lot (statut « rappele » du module Traçabilité) et information des clients concernés.
     */
    public function declencherRappel(AlerteQualite $alerte, Lot $lot, string $motif, User $admin): int
    {
        $clients = DB::transaction(function () use ($alerte, $lot, $motif, $admin) {
            $lot->update(['statut' => 'rappele', 'motif_rappel' => $motif]);

            $alerte->update([
                'lot_id' => $lot->id,
                'motif_rappel' => $motif,
                'statut' => 'rappel_declenche',
                'traitee_par' => $admin->id,
                'traitee_at' => now(),
            ]);

            foreach ($alerte->reclamations() as $reclamation) {
                $reclamation->journaliser('rappel', 'Rappel du lot '.$lot->code.' déclenché suite à l\'alerte qualité', $admin);
            }

            // Clients à prévenir : auteurs des signalements et acheteurs récents du produit
            $acheteurs = Commande::query()
                ->whereIn('statut', ['confirmee', 'preparee', 'expediee', 'en_transit', 'livree'])
                ->where('created_at', '>=', now()->subDays(60))
                ->whereHas('products', fn ($q) => $q->whereKey($lot->produit_id))
                ->pluck('user_id');

            $ids = $alerte->reclamations()->pluck('user_id')
                ->merge($alerte->avisNegatifs()->pluck('user_id'))
                ->merge($acheteurs)
                ->filter()
                ->unique();

            return User::query()->whereIn('id', $ids)->get();
        });

        Notification::send($clients, new RappelProduitNotification($lot->fresh('produit'), $motif));

        return $clients->count();
    }

    public function classer(AlerteQualite $alerte, User $admin): void
    {
        $alerte->update(['statut' => 'classee', 'traitee_par' => $admin->id, 'traitee_at' => now()]);
    }

    /**
     * @param  Collection<int, Reclamation>  $reclamations
     * @param  Collection<int, Avis>  $avis
     */
    private function enregistrer(int $produitId, Collection $reclamations, Collection $avis): AlerteQualite
    {
        $signaux = ['reclamations' => $reclamations->pluck('id')->sort()->values()->all(), 'avis' => $avis->pluck('id')->sort()->values()->all()];
        $signature = md5(json_encode($signaux));
        $nombre = $reclamations->count() + $avis->count();

        $alerte = AlerteQualite::query()->firstOrNew(['produit_id' => $produitId, 'statut' => 'ouverte']);

        if ($alerte->exists && $alerte->signature === $signature) {
            return $alerte;
        }

        $produit = Product::query()->find($produitId);
        $categories = $reclamations->pluck('categorie')->filter()->countBy()->sortDesc();
        $principale = $categories->keys()->first();
        $sanitaire = $categories->has('securite_alimentaire');

        $alerte->fill([
            'lot_id' => $reclamations->pluck('lot_id')->filter()->countBy()->sortDesc()->keys()->first(),
            'nb_signaux' => $nombre,
            'signature' => $signature,
            'signaux' => $signaux,
            'titre' => $nombre.' signalements sur '.($produit?->name ?? 'un produit'),
            'resume' => sprintf(
                '%d réclamation(s) et %d avis négatif(s) en %d jours%s.',
                $reclamations->count(),
                $avis->count(),
                (int) config('reclamations.alerte_fenetre_jours', 14),
                $principale ? ', surtout : '.(Reclamation::CATEGORIES[$principale]['label'] ?? $principale) : ''
            ),
            'gravite' => $sanitaire || $nombre >= 6 ? 'elevee' : 'moyenne',
            'recommandation' => $sanitaire
                ? 'Risque sanitaire évoqué : vérifiez le lot concerné et envisagez un rappel.'
                : 'Contactez le producteur pour vérifier les derniers lots livrés.',
            'rappel_conseille' => $sanitaire,
            // Nouveaux signaux : l'analyse IA précédente n'est plus à jour
            'analyse_ia' => false,
        ])->save();

        return $alerte;
    }
}
