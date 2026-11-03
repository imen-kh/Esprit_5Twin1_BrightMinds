<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Client de l'API DeepSeek (format « chat completions » compatible OpenAI) du module Réclamations & Avis.
 *
 * Aucune erreur n'atteint l'utilisateur : sans clé, sans réseau ou en cas de quota dépassé, les méthodes
 * renvoient null et chaque service appelant bascule sur ses règles locales.
 */
class DeepSeekService
{
    /** Clé de cache posée après un échec : les pages n'attendent pas l'API pendant quelques minutes. */
    private const CLE_PAUSE = 'deepseek:pause';

    public function estConfigure(): bool
    {
        return filled(config('services.deepseek.key'));
    }

    /**
     * L'API est-elle utilisable maintenant (clé présente et pas de pause après un échec récent) ?
     */
    public function estDisponible(): bool
    {
        return $this->estConfigure() && ! Cache::has(self::CLE_PAUSE);
    }

    /**
     * Réponse JSON structurée. Le prompt système doit décrire le schéma attendu.
     *
     * @return array<string, mixed>|null
     */
    public function json(string $systeme, string $utilisateur, float $temperature = 0.2): ?array
    {
        $message = $this->discuter([
            ['role' => 'system', 'content' => $systeme],
            ['role' => 'user', 'content' => $utilisateur],
        ], ['response_format' => ['type' => 'json_object'], 'temperature' => $temperature]);

        return $this->lireJson($message['content'] ?? null);
    }

    /**
     * Texte libre (brouillon de réponse, résumé…). Null si l'API est indisponible.
     */
    public function texte(string $systeme, string $utilisateur, float $temperature = 0.6): ?string
    {
        $message = $this->discuter([
            ['role' => 'system', 'content' => $systeme],
            ['role' => 'user', 'content' => $utilisateur],
        ], ['temperature' => $temperature]);

        $texte = trim((string) ($message['content'] ?? ''));

        return $texte !== '' ? $texte : null;
    }

    /**
     * Appel brut : renvoie le message de l'assistant (content et éventuels tool_calls), ou null.
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options  tools, response_format, temperature, max_tokens
     * @return array<string, mixed>|null
     */
    public function discuter(array $messages, array $options = []): ?array
    {
        if (! $this->estDisponible()) {
            return null;
        }

        $corps = array_filter([
            'model' => config('services.deepseek.model') ?: 'deepseek-chat',
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.4,
            'max_tokens' => $options['max_tokens'] ?? 1200,
            'response_format' => $options['response_format'] ?? null,
            'tools' => $options['tools'] ?? null,
        ], fn ($valeur) => $valeur !== null);

        try {
            $reponse = Http::timeout((int) config('services.deepseek.timeout', 30))
                ->acceptJson()
                ->withToken((string) config('services.deepseek.key'))
                ->post(config('services.deepseek.base_url').'/chat/completions', $corps);

            if ($reponse->failed()) {
                Log::warning('DeepSeek : réponse en erreur', ['statut' => $reponse->status(), 'corps' => mb_substr($reponse->body(), 0, 300)]);
                $this->mettreEnPause($reponse->status());

                return null;
            }

            $message = $reponse->json('choices.0.message');

            return is_array($message) ? $message : null;
        } catch (Throwable $e) {
            Log::warning('DeepSeek indisponible : '.$e->getMessage());
            $this->mettreEnPause(0);

            return null;
        }
    }

    /**
     * Le modèle entoure parfois le JSON de ```json … ``` : on récupère l'objet quoi qu'il arrive.
     *
     * @return array<string, mixed>|null
     */
    public function lireJson(?string $texte): ?array
    {
        if ($texte === null || trim($texte) === '') {
            return null;
        }

        $texte = trim($texte);
        $debut = strpos($texte, '{');
        $fin = strrpos($texte, '}');

        if ($debut === false || $fin === false || $fin < $debut) {
            return null;
        }

        $donnees = json_decode(substr($texte, $debut, $fin - $debut + 1), true);

        return is_array($donnees) ? $donnees : null;
    }

    /**
     * Erreur d'authentification ou de quota : pause plus longue. Erreur passagère : courte pause.
     */
    private function mettreEnPause(int $statut): void
    {
        $minutes = in_array($statut, [401, 402, 403], true) ? 15 : 2;

        Cache::put(self::CLE_PAUSE, true, now()->addMinutes($minutes));
    }
}
