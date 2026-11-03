<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReclamationRequest;
use App\Http\Requests\UpdateReclamationRequest;
use App\Models\Product;
use App\Models\Reclamation;
use App\Services\AiReclamationService;
use App\Services\GestionReclamationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReclamationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Reclamation::query()->with(['user', 'produit']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('priorite')) {
            $query->where('priorite', $request->string('priorite'));
        }

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->string('categorie'));
        }

        if ($request->boolean('retard')) {
            $query->enRetard();
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($sub) use ($q) {
                $sub->where('sujet', 'like', $q)
                    ->orWhere('reference', 'like', $q)
                    ->orWhere('description', 'like', $q)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $q))
                    ->orWhereHas('produit', fn ($produit) => $produit->where('name', 'like', $q));
            });
        }

        // Tri : dossiers en retard, puis urgents, puis les plus récents
        $reclamations = $query
            ->orderByRaw("case when statut in ('resolue', 'rejetee') then 1 else 0 end")
            ->orderByRaw('case when echeance_at is not null and echeance_at < ? and statut not in (?, ?) then 0 else 1 end', [now(), 'resolue', 'rejetee'])
            ->orderByRaw("case priorite when 'urgente' then 0 when 'haute' then 1 when 'moyenne' then 2 else 3 end")
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('back.reclamations.index', [
            'reclamations' => $reclamations,
            'kpis' => $this->indicateurs(),
        ]);
    }

    public function create(): View
    {
        return view('back.reclamations.edit', [
            'reclamation' => new Reclamation(['priorite' => 'moyenne', 'statut' => 'en_attente']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreReclamationRequest $request, GestionReclamationService $service): RedirectResponse
    {
        $reclamation = $service->creer($request->user(), $request->donnees());

        return redirect()->route('admin.reclamations.show', $reclamation)
            ->with('success', 'Réclamation '.$reclamation->reference.' enregistrée.');
    }

    public function show(Reclamation $reclamation): View
    {
        $reclamation->load(['user', 'produit.user', 'lot', 'commande', 'avis', 'reponses.user', 'historique.user']);

        $autres = Reclamation::query()
            ->where('produit_id', $reclamation->produit_id)
            ->whereKeyNot($reclamation->id)
            ->latest()
            ->take(5)
            ->get();

        return view('back.reclamations.show', [
            'reclamation' => $reclamation,
            'autres' => $autres,
            'tons' => AiReclamationService::TONS,
        ]);
    }

    public function edit(Reclamation $reclamation): View
    {
        return view('back.reclamations.edit', [
            'reclamation' => $reclamation,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateReclamationRequest $request, Reclamation $reclamation): RedirectResponse
    {
        $avant = $reclamation->statut;
        $reclamation->update($request->donnees());

        if ($reclamation->statut !== $avant) {
            $reclamation->journaliser('statut', 'Statut passé à « '.$reclamation->statutLibelle().' »', $request->user());
        }

        return redirect()->route('admin.reclamations.show', $reclamation)
            ->with('success', 'Réclamation mise à jour.');
    }

    public function updateStatus(Request $request, Reclamation $reclamation, GestionReclamationService $service): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'string', Rule::in(Reclamation::STATUTS)],
        ]);

        $service->changerStatut($reclamation, $data['statut'], $request->user());

        return redirect()->route('admin.reclamations.show', $reclamation)
            ->with('success', 'Statut mis à jour.');
    }

    public function destroy(Reclamation $reclamation): RedirectResponse
    {
        $reclamation->delete();

        return redirect()->route('admin.reclamations.index')
            ->with('success', 'Réclamation supprimée.');
    }

    /**
     * Copilote : brouillon de réponse DeepSeek dans le ton choisi, dans la langue du client.
     */
    public function copilote(Request $request, Reclamation $reclamation, AiReclamationService $ia): JsonResponse
    {
        $data = $request->validate(['ton' => ['nullable', Rule::in(array_keys(AiReclamationService::TONS))]]);

        $brouillon = $ia->redigerReponse($reclamation, $data['ton'] ?? 'empathique');

        return response()->json([
            'message' => $brouillon['message'],
            'solution' => $brouillon['solution'],
            'langue' => AiReclamationService::LANGUES[$brouillon['langue']] ?? 'Français',
            'source' => match ($brouillon['source']) {
                'ia' => 'DeepSeek',
                'anthropic' => 'Anthropic',
                default => 'Modèle local',
            },
        ]);
    }

    /**
     * « Qui est responsable ? » : maillon de la chaîne probablement en cause, d'après la traçabilité du lot.
     */
    public function responsabilite(Request $request, Reclamation $reclamation, GestionReclamationService $service): RedirectResponse
    {
        $resultat = $service->analyserResponsabilite($reclamation, $request->user());

        return redirect()->route('admin.reclamations.show', $reclamation)
            ->with('success', 'Maillon probable : '.$reclamation->responsableLibelle().' (confiance '.$resultat['confiance'].' %).');
    }

    /**
     * Relance l'analyse IA (catégorie, sentiment, résumé, suggestion) sur le texte actuel.
     */
    public function reanalyser(Request $request, Reclamation $reclamation, AiReclamationService $ia): RedirectResponse
    {
        $analyse = $ia->analyserReclamation($reclamation->sujet, $reclamation->description, $reclamation->produit);

        $reclamation->update([
            'categorie' => $analyse['categorie'],
            'langue' => $analyse['langue'],
            'sentiment' => $analyse['sentiment'],
            'priorite_suggeree' => $analyse['priorite'],
            'suggestion_reponse' => $analyse['suggestion'],
            'resume_ia' => $analyse['resume'],
        ]);
        $source = match ($analyse['source']) {
            'ia' => 'DeepSeek',
            'anthropic' => 'Anthropic',
            default => 'automatique',
        };
        $reclamation->journaliser('ia', 'Nouvelle analyse '.$source.' : '.$reclamation->categorieLibelle(), $request->user());

        return redirect()->route('admin.reclamations.show', $reclamation)->with('success', 'Analyse mise à jour.');
    }

    /**
     * Dossier complet en PDF : réclamation, analyse, échanges et chronologie.
     */
    public function pdf(Reclamation $reclamation): Response
    {
        $reclamation->load(['user', 'produit', 'lot', 'commande', 'reponses.user', 'historique.user']);

        return Pdf::loadView('back.reclamations.pdf', compact('reclamation'))
            ->setPaper('a4')
            ->download('dossier-'.$reclamation->reference.'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function indicateurs(): array
    {
        $cloturees = Reclamation::query()->whereIn('statut', Reclamation::STATUTS_CLOTURES)->where('created_at', '>=', now()->subDays(90))->get();
        $respectees = $cloturees->filter(fn (Reclamation $r) => $r->slaRespecte())->count();
        $heures = $cloturees->filter(fn ($r) => $r->resolue_at)->avg(fn (Reclamation $r) => $r->created_at->diffInMinutes($r->resolue_at) / 60);

        return [
            'ouvertes' => Reclamation::query()->ouvertes()->count(),
            'en_retard' => Reclamation::query()->enRetard()->count(),
            'urgentes' => Reclamation::query()->ouvertes()->where('priorite', 'urgente')->count(),
            'respect_sla' => $cloturees->isEmpty() ? null : (int) round(100 * $respectees / $cloturees->count()),
            'satisfaction' => ($note = Reclamation::query()->whereNotNull('satisfaction_note')->avg('satisfaction_note')) ? round((float) $note, 1) : null,
            'delai_moyen' => $heures ? (int) round($heures) : null,
        ];
    }
}
