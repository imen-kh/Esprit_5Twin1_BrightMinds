<?php

namespace App\Services\Assistant;

use App\Models\AlerteQualite;
use App\Models\AssistantConversation;
use App\Models\Avis;
use App\Models\Commande;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use App\Services\AiReclamationService;
use App\Services\GestionAvisService;
use App\Services\GestionReclamationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Outils de l'agent Sahbi (« function calling » DeepSeek).
 *
 * Sécurité : chaque outil s'exécute côté Laravel avec l'utilisateur connecté et ses droits. Le modèle ne
 * fournit que des arguments, toujours revalidés ici ; il ne voit jamais les données d'un autre client.
 * Les outils qui modifient des données (ACTIONS) ne s'exécutent qu'après confirmation sur une carte.
 */
class AssistantOutils
{
    public const ACTIONS = ['creer_reclamation', 'ajouter_message', 'publier_avis', 'escalader'];

    public function __construct(
        private GestionReclamationService $reclamations,
        private GestionAvisService $avis,
        private AiReclamationService $ia,
    ) {}

    public function profil(User $user): string
    {
        return match (true) {
            $user->isAdmin() => 'admin',
            $user->isActeur() => 'acteur',
            default => 'client',
        };
    }

    /**
     * Définitions des outils proposés au modèle, selon le rôle.
     *
     * @return list<array<string, mixed>>
     */
    public function definitions(User $user): array
    {
        $client = [
            $this->outil('mes_commandes', 'Liste les dernières commandes de l\'utilisateur avec leurs produits (identifiants inclus).', [
                'limite' => ['type' => 'integer', 'description' => 'Nombre de commandes, 5 par défaut'],
            ]),
            $this->outil('mes_reclamations', 'Liste les réclamations de l\'utilisateur : référence, statut, délai restant, dernière réponse.', [
                'statut' => ['type' => 'string', 'enum' => Reclamation::STATUTS],
            ]),
            $this->outil('details_reclamation', 'Détail d\'une réclamation et de ses réponses, par référence (REC-AAAA-NNNN).', [
                'reference' => ['type' => 'string'],
            ], ['reference']),
            $this->outil('rechercher_produit', 'Recherche un produit publié du catalogue par son nom.', [
                'nom' => ['type' => 'string'],
            ], ['nom']),
            $this->outil('produits_a_evaluer', 'Produits reçus (commandes livrées) que l\'utilisateur n\'a pas encore notés.'),
            $this->outil('creer_reclamation', 'ACTION (confirmée par l\'utilisateur) : crée une réclamation. Identifie d\'abord le produit avec mes_commandes ou rechercher_produit.', [
                'produit_id' => ['type' => 'integer'],
                'commande_id' => ['type' => 'integer', 'description' => 'Commande concernée, si connue'],
                'code_lot' => ['type' => 'string', 'description' => 'Code du lot imprimé sur l\'emballage (LOT-…), si connu'],
                'sujet' => ['type' => 'string', 'description' => 'Titre court en français, 80 caractères maximum'],
                'description' => ['type' => 'string', 'description' => 'Description fidèle et complète du problème, avec les mots du client'],
                'priorite' => ['type' => 'string', 'enum' => Reclamation::PRIORITES],
            ], ['produit_id', 'sujet', 'description']),
            $this->outil('ajouter_message', 'ACTION : ajoute un message ou une précision à une réclamation ouverte de l\'utilisateur.', [
                'reference' => ['type' => 'string'],
                'message' => ['type' => 'string'],
            ], ['reference', 'message']),
            $this->outil('publier_avis', 'ACTION : publie l\'avis de l\'utilisateur sur un produit (note de 1 à 5).', [
                'produit_id' => ['type' => 'integer'],
                'note' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                'titre' => ['type' => 'string'],
                'commentaire' => ['type' => 'string', 'description' => 'Avis rédigé avec les mots du client'],
            ], ['produit_id', 'note', 'commentaire']),
            $this->outil('escalader', 'ACTION : transmet une réclamation ouverte à un conseiller humain, avec un résumé de la conversation.', [
                'reference' => ['type' => 'string'],
                'resume' => ['type' => 'string', 'description' => 'Résumé de la demande en français'],
            ], ['reference', 'resume']),
        ];

        $acteur = [
            $this->outil('reclamations_mes_produits', 'Réclamations des clients sur les produits de l\'acteur connecté.', [
                'statut' => ['type' => 'string', 'enum' => Reclamation::STATUTS],
            ]),
            $this->outil('avis_mes_produits', 'Avis publiés sur les produits de l\'acteur connecté.', [
                'note_max' => ['type' => 'integer', 'description' => 'Ne garder que les avis de note inférieure ou égale'],
            ]),
            $this->outil('synthese_qualite', 'Synthèse qualité par produit de l\'acteur : note moyenne, aspects, réclamations ouvertes.'),
        ];

        $admin = [
            $this->outil('reclamations_prioritaires', 'Réclamations ouvertes en retard sur leur délai, puis urgentes.'),
            $this->outil('statistiques', 'Indicateurs du service client sur une période : volumes, catégories, respect des délais, satisfaction, avis.', [
                'jours' => ['type' => 'integer', 'description' => '30 par défaut'],
            ]),
            $this->outil('rechercher_reclamations', 'Recherche de réclamations par mot-clé (sujet, description, client, produit).', [
                'q' => ['type' => 'string'],
            ], ['q']),
            $this->outil('details_reclamation', 'Détail d\'une réclamation par référence.', ['reference' => ['type' => 'string']], ['reference']),
            $this->outil('alertes_qualite', 'Alertes qualité ouvertes : produits visés par plusieurs signalements récents.'),
            $this->outil('avis_a_moderer', 'Avis retenus en modération (IA ou signalements).'),
            $this->outil('rediger_reponse', 'Rédige un brouillon de réponse à une réclamation (l\'admin le relira).', [
                'reference' => ['type' => 'string'],
                'ton' => ['type' => 'string', 'enum' => array_keys(AiReclamationService::TONS)],
            ], ['reference']),
        ];

        return match ($this->profil($user)) {
            'admin' => $admin,
            'acteur' => [...$acteur, ...$client],
            default => $client,
        };
    }

    public function estAction(string $nom): bool
    {
        return in_array($nom, self::ACTIONS, true);
    }

    public function estAutorise(User $user, string $nom): bool
    {
        return collect($this->definitions($user))->contains(fn ($outil) => $outil['function']['name'] === $nom);
    }

    /**
     * Exécute un outil de lecture. Le résultat est renvoyé au modèle ; « _liens » est affiché en boutons.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function executer(User $user, string $nom, array $args): array
    {
        if (! $this->estAutorise($user, $nom) || $this->estAction($nom)) {
            return ['erreur' => 'Outil non disponible pour ce profil.'];
        }

        return match ($nom) {
            'mes_commandes' => $this->mesCommandes($user, (int) ($args['limite'] ?? 5)),
            'mes_reclamations' => $this->listeReclamations(Reclamation::query()->where('user_id', $user->id), $args['statut'] ?? null, $user),
            'details_reclamation' => $this->detailsReclamation($user, (string) ($args['reference'] ?? '')),
            'rechercher_produit' => $this->rechercherProduit((string) ($args['nom'] ?? '')),
            'produits_a_evaluer' => $this->produitsAEvaluer($user),
            'reclamations_mes_produits' => $this->listeReclamations(
                Reclamation::query()->whereHas('produit', fn (Builder $q) => $q->where('user_id', $user->id)),
                $args['statut'] ?? null,
                $user
            ),
            'avis_mes_produits' => $this->avisMesProduits($user, isset($args['note_max']) ? (int) $args['note_max'] : null),
            'synthese_qualite' => $this->syntheseQualite($user),
            'reclamations_prioritaires' => $this->reclamationsPrioritaires(),
            'statistiques' => $this->statistiques((int) ($args['jours'] ?? 30)),
            'rechercher_reclamations' => $this->rechercherReclamations((string) ($args['q'] ?? '')),
            'alertes_qualite' => $this->alertesQualite(),
            'avis_a_moderer' => $this->avisAModerer(),
            'rediger_reponse' => $this->redigerReponse($user, (string) ($args['reference'] ?? ''), (string) ($args['ton'] ?? 'empathique')),
            default => ['erreur' => 'Outil inconnu.'],
        };
    }

    /**
     * Valide une action et prépare la carte de confirmation. Rien n'est enregistré à ce stade.
     *
     * @param  array<string, mixed>  $args
     * @return array{titre: string, details: array<string, string>, arguments: array<string, mixed>}|array{erreur: string}
     */
    public function preparer(User $user, string $nom, array $args): array
    {
        if (! $this->estAutorise($user, $nom) || ! $this->estAction($nom)) {
            return ['erreur' => 'Action non disponible pour ce profil.'];
        }

        return match ($nom) {
            'creer_reclamation' => $this->preparerReclamation($user, $args),
            'ajouter_message' => $this->preparerMessage($user, $args),
            'publier_avis' => $this->preparerAvis($user, $args),
            'escalader' => $this->preparerEscalade($user, $args),
        };
    }

    /**
     * Exécute une action confirmée par l'utilisateur. Les arguments sont revalidés.
     *
     * @param  array<string, mixed>  $args
     * @return array{message: string, liens: list<array{label: string, url: string}>}
     */
    public function realiser(User $user, AssistantConversation $conversation, string $nom, array $args): array
    {
        $preparation = $this->preparer($user, $nom, $args);

        if (isset($preparation['erreur'])) {
            return ['message' => 'Je n\'ai pas pu le faire : '.$preparation['erreur'], 'liens' => []];
        }

        $a = $preparation['arguments'];

        switch ($nom) {
            case 'creer_reclamation':
                $reclamation = $this->reclamations->creer($user, $a, 'assistant');

                return [
                    'message' => "C'est fait ✅ Votre réclamation {$reclamation->reference} est enregistrée (priorité {$reclamation->prioriteLibelle()}, {$reclamation->slaLibelle()}). Vous serez prévenu à chaque réponse.",
                    'liens' => [['label' => 'Voir '.$reclamation->reference, 'url' => route('reclamations.show', $reclamation)]],
                ];

            case 'ajouter_message':
                $reclamation = Reclamation::query()->findOrFail($a['reclamation_id']);
                $this->reclamations->repondre($reclamation, $user, $a['message']);

                return [
                    'message' => "Message ajouté à {$reclamation->reference} ✅",
                    'liens' => [['label' => 'Voir '.$reclamation->reference, 'url' => route('reclamations.show', $reclamation)]],
                ];

            case 'publier_avis':
                $produit = Product::query()->findOrFail($a['produit_id']);
                $avis = $this->avis->publier($user, $produit, $a);
                $texte = $avis->statut === 'publie'
                    ? 'Merci ! Votre avis '.$avis->note.'★ est publié ✅'
                    : 'Merci ! Votre avis est en relecture avant publication.';

                if ($avis->meriteReclamation()) {
                    $texte .= ' Il semble décrire un vrai problème : voulez-vous que j\'ouvre une réclamation pour le traiter ?';
                }

                return ['message' => $texte, 'liens' => [['label' => 'Voir le produit', 'url' => route('produit.detail', $produit)]]];

            case 'escalader':
                $reclamation = Reclamation::query()->findOrFail($a['reclamation_id']);
                $extrait = Str::limit($conversation->transcription(), 1500);
                $this->reclamations->repondre($reclamation, $user, "Demande de contact humain (via Sahbi) : {$a['resume']}\n\n— Extrait de la conversation —\n{$extrait}");
                $this->reclamations->escalader($reclamation, $user, $a['resume']);

                return [
                    'message' => "Je transmets {$reclamation->reference} à un conseiller 🙋 La priorité passe à « {$reclamation->fresh()->prioriteLibelle()} ».",
                    'liens' => [['label' => 'Voir '.$reclamation->reference, 'url' => route('reclamations.show', $reclamation)]],
                ];
        }

        return ['message' => 'Action inconnue.', 'liens' => []];
    }

    // ---------- Outils de lecture ----------

    /**
     * @return array<string, mixed>
     */
    private function mesCommandes(User $user, int $limite): array
    {
        $commandes = Commande::query()->with('products:id,name')->where('user_id', $user->id)
            ->latest('id')->take(max(1, min(10, $limite)))->get();

        return [
            'commandes' => $commandes->map(fn (Commande $c) => [
                'commande_id' => $c->id,
                'date' => ($c->date_commande ?? $c->created_at)?->format('d/m/Y'),
                'statut' => $c->statut,
                'total' => number_format((float) $c->total, 3, ',', ' ').' DT',
                'produits' => $c->products->map(fn ($p) => ['produit_id' => $p->id, 'nom' => $p->name])->all(),
            ])->all(),
            '_liens' => $commandes->isEmpty() ? [] : [['label' => 'Mes commandes', 'url' => route('commandes.index')]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listeReclamations(Builder $requete, mixed $statut, User $user): array
    {
        $reclamations = $requete->with(['produit:id,name', 'reponses' => fn ($q) => $q->latest()])
            ->when(in_array($statut, Reclamation::STATUTS, true), fn ($q) => $q->where('statut', $statut))
            ->latest()->take(8)->get();

        return [
            'reclamations' => $reclamations->map(fn (Reclamation $r) => [
                'reference' => $r->reference,
                'sujet' => $r->sujet,
                'produit' => $r->produit?->name,
                'statut' => $r->statutLibelle(),
                'priorite' => $r->prioriteLibelle(),
                'delai' => $r->slaLibelle(),
                'derniere_reponse' => $r->reponses->first() ? Str::limit($r->reponses->first()->message, 160) : null,
            ])->all(),
            '_liens' => $reclamations->take(3)->map(fn (Reclamation $r) => ['label' => $r->reference, 'url' => $this->urlReclamation($r, $user)])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailsReclamation(User $user, string $reference): array
    {
        $reclamation = $this->trouverReclamation($user, $reference);

        if (! $reclamation) {
            return ['erreur' => 'Aucune réclamation visible avec cette référence.'];
        }

        $reclamation->load(['produit', 'reponses.user', 'historique']);

        return [
            'reference' => $reclamation->reference,
            'sujet' => $reclamation->sujet,
            'description' => Str::limit($reclamation->description, 600),
            'produit' => $reclamation->produit?->name,
            'statut' => $reclamation->statutLibelle(),
            'priorite' => $reclamation->prioriteLibelle(),
            'categorie' => $reclamation->categorieLibelle(),
            'delai' => $reclamation->slaLibelle(),
            'reponses' => $reclamation->reponses->map(fn ($r) => [
                'auteur' => $r->user?->isAdmin() ? 'Service client' : ($r->user_id === $reclamation->user_id ? 'Client' : 'Producteur'),
                'date' => $r->created_at?->format('d/m H:i'),
                'message' => Str::limit($r->message, 300),
            ])->all(),
            '_liens' => [['label' => 'Ouvrir '.$reclamation->reference, 'url' => $this->urlReclamation($reclamation, $user)]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rechercherProduit(string $nom): array
    {
        $produits = $this->produitsCorrespondants($nom)->take(5)->get(['id', 'name', 'price']);

        return [
            'produits' => $produits->map(fn (Product $p) => ['produit_id' => $p->id, 'nom' => $p->name, 'prix' => $p->formattedPrice().' DT'])->all(),
            '_liens' => $produits->take(2)->map(fn (Product $p) => ['label' => $p->name, 'url' => route('produit.detail', $p)])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function produitsAEvaluer(User $user): array
    {
        $produits = $this->produitsNonEvalues($user);

        return [
            'produits' => $produits->map(fn (Product $p) => ['produit_id' => $p->id, 'nom' => $p->name])->all(),
            '_liens' => $produits->take(2)->map(fn (Product $p) => ['label' => 'Noter '.$p->name, 'url' => route('produit.detail', $p).'#avis'])->all(),
        ];
    }

    /**
     * Produits reçus (commandes livrées) sans avis de l'utilisateur. Sert aussi au message d'accueil proactif.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function produitsNonEvalues(User $user): \Illuminate\Support\Collection
    {
        $dejaNotes = Avis::query()->where('user_id', $user->id)->pluck('produit_id');

        return Product::query()
            ->where('status', Product::PUBLISHED)
            ->whereNotIn('id', $dejaNotes)
            ->whereHas('commandes', fn (Builder $q) => $q->where('user_id', $user->id)->where('statut', 'livree'))
            ->take(5)
            ->get(['id', 'name']);
    }

    /**
     * @return array<string, mixed>
     */
    private function avisMesProduits(User $user, ?int $noteMax): array
    {
        $avis = Avis::query()->publies()->with('produit:id,name')
            ->whereHas('produit', fn (Builder $q) => $q->where('user_id', $user->id))
            ->when($noteMax, fn ($q) => $q->where('note', '<=', $noteMax))
            ->latest()->take(10)->get();

        return [
            'avis' => $avis->map(fn (Avis $a) => [
                'produit' => $a->produit?->name,
                'note' => $a->note,
                'commentaire' => Str::limit($a->commentaire, 200),
                'repondu' => filled($a->reponse_producteur),
                'date' => $a->created_at?->format('d/m/Y'),
            ])->all(),
            '_liens' => [['label' => 'Répondre aux avis', 'url' => route('acteur.avis.index')]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function syntheseQualite(User $user): array
    {
        $produits = Product::query()->where('user_id', $user->id)
            ->withAvg(['avis as note_moyenne' => fn ($q) => $q->publies()], 'note')
            ->withCount(['avis as nb_avis' => fn ($q) => $q->publies()])
            ->withCount(['reclamations as reclamations_ouvertes' => fn ($q) => $q->ouvertes()])
            ->get();

        $aspects = app(\App\Services\AvisIAService::class);

        return [
            'produits' => $produits->map(fn (Product $p) => [
                'nom' => $p->name,
                'note_moyenne' => $p->note_moyenne ? round((float) $p->note_moyenne, 1) : null,
                'nb_avis' => $p->nb_avis,
                'reclamations_ouvertes' => $p->reclamations_ouvertes,
                'aspects' => $aspects->aspectsMoyens($p->avis()->publies()->get()),
            ])->all(),
            '_liens' => [['label' => 'Réclamations sur mes produits', 'url' => route('acteur.reclamations.index')]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reclamationsPrioritaires(): array
    {
        $reclamations = Reclamation::query()->ouvertes()->with(['produit:id,name', 'user:id,name'])
            ->orderByRaw('case when echeance_at < ? then 0 else 1 end', [now()])
            ->orderByRaw("case priorite when 'urgente' then 0 when 'haute' then 1 when 'moyenne' then 2 else 3 end")
            ->orderBy('echeance_at')
            ->take(8)->get();

        return [
            'reclamations' => $reclamations->map(fn (Reclamation $r) => [
                'reference' => $r->reference,
                'sujet' => $r->sujet,
                'client' => $r->user?->name,
                'produit' => $r->produit?->name,
                'priorite' => $r->prioriteLibelle(),
                'delai' => $r->slaLibelle(),
                'en_retard' => $r->estEnRetard(),
            ])->all(),
            '_liens' => $reclamations->take(3)->map(fn (Reclamation $r) => ['label' => $r->reference, 'url' => route('admin.reclamations.show', $r)])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statistiques(int $jours): array
    {
        $jours = max(1, min(365, $jours));
        $depuis = now()->subDays($jours);
        $periode = Reclamation::query()->where('created_at', '>=', $depuis)->get();
        $cloturees = $periode->filter->estCloturee();
        $respectees = $cloturees->filter(fn (Reclamation $r) => $r->slaRespecte());

        return [
            'periode_jours' => $jours,
            'reclamations' => $periode->count(),
            'par_statut' => $periode->countBy(fn ($r) => $r->statutLibelle())->all(),
            'par_categorie' => $periode->countBy(fn ($r) => $r->categorieLibelle())->sortDesc()->all(),
            'en_retard_actuellement' => Reclamation::query()->enRetard()->count(),
            'respect_delais_pourcent' => $cloturees->isEmpty() ? null : (int) round(100 * $respectees->count() / $cloturees->count()),
            'satisfaction_moyenne' => ($note = $periode->whereNotNull('satisfaction_note')->avg('satisfaction_note')) ? round($note, 1) : null,
            'avis_publies' => Avis::query()->publies()->where('created_at', '>=', $depuis)->count(),
            'note_moyenne_avis' => ($moyenne = Avis::query()->publies()->where('created_at', '>=', $depuis)->avg('note')) ? round((float) $moyenne, 1) : null,
            '_liens' => [['label' => 'Tableau des réclamations', 'url' => route('admin.reclamations.index')]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rechercherReclamations(string $q): array
    {
        $motif = '%'.trim($q).'%';
        $reclamations = Reclamation::query()->with(['produit:id,name', 'user:id,name'])
            ->where(fn ($sub) => $sub->where('sujet', 'like', $motif)->orWhere('description', 'like', $motif)->orWhere('reference', 'like', $motif)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $motif))
                ->orWhereHas('produit', fn ($p) => $p->where('name', 'like', $motif)))
            ->latest()->take(8)->get();

        return [
            'reclamations' => $reclamations->map(fn (Reclamation $r) => [
                'reference' => $r->reference, 'sujet' => $r->sujet, 'client' => $r->user?->name,
                'produit' => $r->produit?->name, 'statut' => $r->statutLibelle(), 'priorite' => $r->prioriteLibelle(),
            ])->all(),
            '_liens' => $reclamations->take(3)->map(fn (Reclamation $r) => ['label' => $r->reference, 'url' => route('admin.reclamations.show', $r)])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function alertesQualite(): array
    {
        $alertes = AlerteQualite::query()->with('produit:id,name')->where('statut', 'ouverte')->latest()->take(5)->get();

        return [
            'alertes' => $alertes->map(fn (AlerteQualite $a) => [
                'produit' => $a->produit?->name, 'titre' => $a->titre, 'signaux' => $a->nb_signaux,
                'gravite' => $a->graviteLibelle(), 'resume' => $a->resume, 'rappel_conseille' => $a->rappel_conseille,
            ])->all(),
            '_liens' => [['label' => 'Alertes qualité', 'url' => route('admin.alertes.index')]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function avisAModerer(): array
    {
        $avis = Avis::query()->with(['produit:id,name', 'user:id,name'])->where('statut', 'en_moderation')->latest()->take(8)->get();

        return [
            'avis' => $avis->map(fn (Avis $a) => [
                'produit' => $a->produit?->name, 'client' => $a->user?->name, 'note' => $a->note,
                'raison' => $a->moderation_raison, 'extrait' => Str::limit($a->commentaire, 120),
            ])->all(),
            '_liens' => [['label' => 'File de modération', 'url' => route('admin.avis.index', ['statut' => 'en_moderation'])]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function redigerReponse(User $user, string $reference, string $ton): array
    {
        $reclamation = $this->trouverReclamation($user, $reference);

        if (! $reclamation) {
            return ['erreur' => 'Référence introuvable.'];
        }

        $brouillon = $this->ia->redigerReponse($reclamation, $ton);

        return [
            'brouillon' => $brouillon['message'],
            'solution' => $brouillon['solution'],
            '_liens' => [['label' => 'Répondre sur '.$reclamation->reference, 'url' => route('admin.reclamations.show', $reclamation).'#reponse']],
        ];
    }

    // ---------- Préparation des actions ----------

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function preparerReclamation(User $user, array $args): array
    {
        $produit = $this->resoudreProduit($args);
        if (! $produit) {
            return ['erreur' => 'Produit introuvable : demande au client de préciser le produit, ou utilise rechercher_produit.'];
        }

        $sujet = trim((string) ($args['sujet'] ?? ''));
        $description = trim((string) ($args['description'] ?? ''));
        if (mb_strlen($sujet) < 3 || mb_strlen($description) < 10) {
            return ['erreur' => 'Sujet ou description trop courts : demande plus de détails au client.'];
        }

        $commande = null;
        if (! empty($args['commande_id'])) {
            $commande = Commande::query()->where('user_id', $user->id)->find((int) $args['commande_id']);
            if (! $commande) {
                return ['erreur' => 'Cette commande n\'appartient pas au client. Utilise mes_commandes.'];
            }
        }

        $lot = null;
        if (! empty($args['code_lot'])) {
            $lot = Lot::query()->where('code', strtoupper(trim((string) $args['code_lot'])))->where('produit_id', $produit->id)->first();
            if (! $lot) {
                return ['erreur' => 'Code de lot inconnu pour ce produit : demande au client de le vérifier, ou continue sans.'];
            }
        }

        $priorite = in_array($args['priorite'] ?? null, Reclamation::PRIORITES, true) ? $args['priorite'] : null;

        return [
            'titre' => 'Créer une réclamation',
            'details' => array_filter([
                'Produit' => $produit->name,
                'Commande' => $commande ? '#'.$commande->id.' du '.($commande->date_commande ?? $commande->created_at)?->format('d/m/Y') : null,
                'Lot' => $lot?->code,
                'Sujet' => Str::limit($sujet, 120),
                'Description' => Str::limit($description, 400),
                'Priorité' => $priorite ? Reclamation::prioritesLibelles()[$priorite] : 'Évaluée par l\'IA',
            ]),
            'arguments' => [
                'produit_id' => $produit->id,
                'commande_id' => $commande?->id,
                'lot_id' => $lot?->id,
                'code_lot' => $lot?->code,
                'sujet' => Str::limit($sujet, 250, ''),
                'description' => $description,
                'priorite' => $priorite,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function preparerMessage(User $user, array $args): array
    {
        $reclamation = Reclamation::query()->where('user_id', $user->id)->where('reference', strtoupper(trim((string) ($args['reference'] ?? ''))))->first()
            ?? (isset($args['reclamation_id']) ? Reclamation::query()->where('user_id', $user->id)->find((int) $args['reclamation_id']) : null);
        $message = trim((string) ($args['message'] ?? ''));

        if (! $reclamation || $reclamation->estCloturee()) {
            return ['erreur' => 'Réclamation introuvable ou déjà clôturée.'];
        }
        if (mb_strlen($message) < 3) {
            return ['erreur' => 'Message vide.'];
        }

        return [
            'titre' => 'Ajouter un message',
            'details' => ['Réclamation' => $reclamation->reference.' — '.Str::limit($reclamation->sujet, 60), 'Message' => Str::limit($message, 400)],
            'arguments' => ['reference' => $reclamation->reference, 'reclamation_id' => $reclamation->id, 'message' => $message],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function preparerAvis(User $user, array $args): array
    {
        $produit = $this->resoudreProduit($args);
        $note = (int) ($args['note'] ?? 0);
        $commentaire = trim((string) ($args['commentaire'] ?? ''));

        if (! $produit) {
            return ['erreur' => 'Produit introuvable.'];
        }
        if (! $user->can('publier-avis')) {
            return ['erreur' => 'Seuls les clients peuvent publier un avis.'];
        }
        if (Avis::query()->where('user_id', $user->id)->where('produit_id', $produit->id)->exists()) {
            return ['erreur' => 'Le client a déjà donné son avis sur ce produit : il peut le modifier depuis la page du produit.'];
        }
        if ($note < 1 || $note > 5 || mb_strlen($commentaire) < 10) {
            return ['erreur' => 'Il faut une note de 1 à 5 et un commentaire d\'au moins 10 caractères.'];
        }

        $titre = isset($args['titre']) ? Str::limit(trim((string) $args['titre']), 120, '') : null;

        return [
            'titre' => 'Publier un avis',
            'details' => array_filter([
                'Produit' => $produit->name,
                'Note' => str_repeat('★', $note).str_repeat('☆', 5 - $note),
                'Titre' => $titre,
                'Commentaire' => Str::limit($commentaire, 400),
            ]),
            'arguments' => ['produit_id' => $produit->id, 'note' => $note, 'titre' => $titre ?: null, 'commentaire' => $commentaire],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function preparerEscalade(User $user, array $args): array
    {
        $reclamation = Reclamation::query()->where('user_id', $user->id)->where('reference', strtoupper(trim((string) ($args['reference'] ?? ''))))->first()
            ?? (isset($args['reclamation_id']) ? Reclamation::query()->where('user_id', $user->id)->find((int) $args['reclamation_id']) : null);
        $resume = trim((string) ($args['resume'] ?? ''));

        if (! $reclamation || $reclamation->estCloturee()) {
            return ['erreur' => 'Réclamation ouverte introuvable : crée d\'abord une réclamation avec creer_reclamation.'];
        }

        return [
            'titre' => 'Parler à un conseiller',
            'details' => [
                'Réclamation' => $reclamation->reference.' — '.Str::limit($reclamation->sujet, 60),
                'Demande' => Str::limit($resume ?: 'Contact humain demandé', 300),
                'Effet' => 'Priorité relevée, conversation transmise',
            ],
            'arguments' => ['reference' => $reclamation->reference, 'reclamation_id' => $reclamation->id, 'resume' => $resume ?: 'Contact humain demandé'],
        ];
    }

    // ---------- Utilitaires ----------

    /**
     * @param  array<string, mixed>  $args
     */
    private function resoudreProduit(array $args): ?Product
    {
        if (! empty($args['produit_id'])) {
            $produit = Product::query()->where('status', Product::PUBLISHED)->find((int) $args['produit_id']);
            if ($produit) {
                return $produit;
            }
        }

        $nom = (string) ($args['produit'] ?? $args['nom_produit'] ?? '');

        return $nom !== '' ? $this->produitsCorrespondants($nom)->first() : null;
    }

    private function produitsCorrespondants(string $nom): Builder
    {
        $nom = trim($nom);

        return Product::query()->where('status', Product::PUBLISHED)
            ->where(function (Builder $q) use ($nom) {
                $q->where('name', 'like', '%'.$nom.'%');
                // « tomates » doit trouver « Tomate » : on essaie aussi le singulier
                if (mb_strlen($nom) > 3 && str_ends_with(mb_strtolower($nom), 's')) {
                    $q->orWhere('name', 'like', '%'.mb_substr($nom, 0, -1).'%');
                }
            })
            ->orderBy('name');
    }

    private function trouverReclamation(User $user, string $reference): ?Reclamation
    {
        return Reclamation::query()->visiblesPar($user)->where('reference', strtoupper(trim($reference)))->first();
    }

    private function urlReclamation(Reclamation $reclamation, User $user): string
    {
        if ($user->isAdmin()) {
            return route('admin.reclamations.show', $reclamation);
        }

        return $reclamation->user_id === $user->id ? route('reclamations.show', $reclamation) : route('acteur.reclamations.index');
    }

    /**
     * @param  array<string, array<string, mixed>>  $proprietes
     * @param  list<string>  $requis
     * @return array<string, mixed>
     */
    private function outil(string $nom, string $description, array $proprietes = [], array $requis = []): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $nom,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => (object) $proprietes,
                    'required' => $requis,
                ],
            ],
        ];
    }
}
