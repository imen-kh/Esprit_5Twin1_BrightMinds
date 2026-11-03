<?php

namespace App\Http\Controllers;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Services\Assistant\SahbiAgent;
use App\Services\DeepSeekService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON du widget Sahbi (agent après-vente).
 */
class AssistantController extends Controller
{
    public function etat(Request $request, SahbiAgent $agent, DeepSeekService $deepseek): JsonResponse
    {
        $user = $request->user();
        $conversation = AssistantConversation::courantePour($user);
        $accueil = $agent->accueil($user);

        return response()->json([
            'accueil' => $accueil['message'],
            'suggestions' => $accueil['suggestions'],
            'messages' => $conversation->messages->map->pourWidget()->values(),
            'en_ligne' => $deepseek->estDisponible(),
        ]);
    }

    public function message(Request $request, SahbiAgent $agent): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $conversation = AssistantConversation::courantePour($request->user());

        $reponse = $agent->repondre($conversation, $request->user(), trim($data['message']));

        return response()->json(['message' => $reponse->pourWidget()]);
    }

    /**
     * Confirmation ou annulation d'une carte d'action.
     */
    public function decider(Request $request, AssistantMessage $message, SahbiAgent $agent): JsonResponse
    {
        abort_unless($message->conversation?->user_id === $request->user()->id, 403);

        $data = $request->validate(['decision' => ['required', 'in:confirmer,annuler']]);
        $suite = $agent->decider($message, $request->user(), $data['decision'] === 'confirmer');

        return response()->json([
            'carte' => $message->fresh()->pourWidget(),
            'message' => $suite->is($message) ? null : $suite->pourWidget(),
        ]);
    }

    public function reinitialiser(Request $request): JsonResponse
    {
        AssistantConversation::query()->where('user_id', $request->user()->id)->whereNull('cloturee_at')->update(['cloturee_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
