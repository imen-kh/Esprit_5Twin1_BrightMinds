<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Modération des avis : file « en modération » (IA ou signalements), publication, masquage.
 */
class AvisController extends Controller
{
    public function index(Request $request): View
    {
        $avis = Avis::query()->with(['user', 'produit'])
            ->when(array_key_exists((string) $request->query('statut'), Avis::STATUTS), fn ($q) => $q->where('statut', $request->query('statut')))
            ->when($request->filled('note'), fn ($q) => $q->where('note', (int) $request->query('note')))
            ->when($request->boolean('signales'), fn ($q) => $q->where('signalements_count', '>', 0))
            ->when($request->filled('q'), function ($q) use ($request) {
                $motif = '%'.$request->string('q').'%';
                $q->where(fn ($sub) => $sub->where('commentaire', 'like', $motif)->orWhere('titre', 'like', $motif)
                    ->orWhereHas('produit', fn ($p) => $p->where('name', 'like', $motif))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $motif)));
            })
            ->orderByRaw("case statut when 'en_moderation' then 0 when 'publie' then 1 else 2 end")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Avis::query()->count(),
            'moderation' => Avis::query()->where('statut', 'en_moderation')->count(),
            'moyenne' => ($m = Avis::query()->publies()->avg('note')) ? round((float) $m, 2) : null,
            'verifies' => Avis::query()->where('achat_verifie', true)->count(),
            'repartition' => Avis::query()->publies()->selectRaw('note, count(*) as total')->groupBy('note')->pluck('total', 'note'),
        ];

        return view('back.avis.index', compact('avis', 'stats'));
    }

    public function publier(Avis $avis): RedirectResponse
    {
        $avis->update(['statut' => 'publie', 'moderation_raison' => null]);

        return back()->with('success', 'Avis publié.');
    }

    public function masquer(Request $request, Avis $avis): RedirectResponse
    {
        $data = $request->validate(['raison' => ['nullable', 'string', 'max:250']]);

        $avis->update(['statut' => 'masque', 'moderation_raison' => $data['raison'] ?? 'Masqué par l\'administrateur']);

        return back()->with('success', 'Avis masqué.');
    }

    public function destroy(Avis $avis): RedirectResponse
    {
        $avis->delete();

        return back()->with('success', 'Avis supprimé.');
    }
}
