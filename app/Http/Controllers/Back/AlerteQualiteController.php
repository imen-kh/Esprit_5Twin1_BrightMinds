<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\AlerteQualite;
use App\Models\Lot;
use App\Services\AlerteQualiteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Alertes qualité : détection des problèmes récurrents, analyse DeepSeek, rappel de lot.
 */
class AlerteQualiteController extends Controller
{
    public function index(Request $request, AlerteQualiteService $service): View
    {
        // Détection locale à chaque visite : pas d'appel IA, seulement des requêtes
        $service->detecter();

        $statut = array_key_exists((string) $request->query('statut'), AlerteQualite::STATUTS) ? $request->query('statut') : 'ouverte';

        $alertes = AlerteQualite::query()->with(['produit.lots', 'lot', 'traiteePar'])
            ->where('statut', $statut)
            ->orderByRaw("case gravite when 'elevee' then 0 when 'moyenne' then 1 else 2 end")
            ->latest('updated_at')
            ->get();

        $compteurs = AlerteQualite::query()->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return view('back.alertes.index', compact('alertes', 'statut', 'compteurs'));
    }

    public function analyser(AlerteQualite $alerte, AlerteQualiteService $service): RedirectResponse
    {
        $service->analyser($alerte);

        return redirect()->route('admin.alertes.index')->with(
            $alerte->analyse_ia ? 'success' : 'warning',
            $alerte->analyse_ia ? 'Analyse DeepSeek terminée.' : 'DeepSeek est indisponible : le résumé automatique reste affiché.'
        );
    }

    public function rappel(Request $request, AlerteQualite $alerte, AlerteQualiteService $service): RedirectResponse
    {
        $data = $request->validate([
            'lot_id' => ['required', Rule::exists('lots', 'id')->where('produit_id', $alerte->produit_id)],
            'motif_rappel' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'lot_id.required' => 'Choisissez le lot à rappeler.',
            'motif_rappel.min' => 'Le motif du rappel doit contenir au moins :min caractères : il est affiché aux consommateurs.',
        ]);

        $notifies = $service->declencherRappel($alerte, Lot::query()->findOrFail($data['lot_id']), $data['motif_rappel'], $request->user());

        return redirect()->route('admin.alertes.index')->with('success', 'Lot rappelé. '.$notifies.' client(s) prévenu(s).');
    }

    public function classer(Request $request, AlerteQualite $alerte, AlerteQualiteService $service): RedirectResponse
    {
        $service->classer($alerte, $request->user());

        return redirect()->route('admin.alertes.index')->with('success', 'Alerte classée.');
    }
}
