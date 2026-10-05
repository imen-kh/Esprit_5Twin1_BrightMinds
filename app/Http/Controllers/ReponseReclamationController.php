<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReponseReclamationRequest;
use App\Models\Reclamation;
use App\Models\ReponseReclamation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReponseReclamationController extends Controller
{
    public function store(StoreReponseReclamationRequest $request, Reclamation $reclamation): RedirectResponse
    {
        abort_unless(
            $request->user()->isAdmin() || $reclamation->user_id === $request->user()->id,
            403
        );

        $reclamation->reponses()->create([
            'user_id' => $request->user()->id,
            'message' => $request->validated()['message'],
        ]);

        if ($request->user()->isAdmin() && $reclamation->statut === 'en_attente') {
            $reclamation->update(['statut' => 'en_cours']);
        }

        return back()->with('success', 'Réponse enregistrée.');
    }

    public function update(Request $request, ReponseReclamation $reponse): RedirectResponse
    {
        $this->autoriser($request, $reponse);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $reponse->update($data);

        return back()->with('success', 'Réponse mise à jour.');
    }

    public function destroy(Request $request, ReponseReclamation $reponse): RedirectResponse
    {
        $this->autoriser($request, $reponse);
        $reponse->delete();

        return back()->with('success', 'Réponse supprimée.');
    }

    private function autoriser(Request $request, ReponseReclamation $reponse): void
    {
        abort_unless(
            $request->user()->isAdmin() || $reponse->user_id === $request->user()->id,
            403
        );
    }
}
