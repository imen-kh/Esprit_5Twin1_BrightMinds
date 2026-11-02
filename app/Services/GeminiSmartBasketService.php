<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeminiSmartBasketService
{
    /**
     * Extrait une demande naturelle une seule fois. Les noms retournés seront
     * ensuite rapprochés du catalogue réel par Laravel, jamais par Gemini.
     */
    public function extractRequest(string $text, ?float $budget = null): ?array
    {
        if (! config('services.gemini.enabled') || ! config('services.gemini.api_key')) {
            return null;
        }

        $cacheKey = 'gemini-smart-request:'.sha1($text.'|'.$budget);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($text, $budget) {
            $prompt = "Extrait uniquement un JSON valide depuis cette demande client NutriTrace. Ne crée aucun produit et ne complète pas les informations absentes. Format exact : {budget:number|null, preferences:string[], products:[{name:string, quantity:number, unit:string}]}. Les préférences autorisées sont cheapest, local, certified. Budget fourni séparément : ".json_encode($budget).". Demande : ".mb_substr($text, 0, 2000);

            try {
                $response = $this->request($prompt, [
                    'temperature' => 0,
                    'maxOutputTokens' => 300,
                    'responseMimeType' => 'application/json',
                ]);
                if (! $response) {
                    return null;
                }

                $json = trim($response);
                $json = preg_replace('/^```(?:json)?|```$/m', '', $json);
                $decoded = json_decode(trim($json), true);
                if (! is_array($decoded) || ! is_array($decoded['products'] ?? null)) {
                    return null;
                }

                return [
                    'budget' => is_numeric($decoded['budget'] ?? null) ? (float) $decoded['budget'] : $budget,
                    'preferences' => array_values(array_intersect((array) ($decoded['preferences'] ?? []), ['cheapest', 'local', 'certified'])),
                    'products' => collect($decoded['products'])->filter(fn ($product) => is_array($product) && filled($product['name'] ?? null) && (int) ($product['quantity'] ?? 0) > 0)->map(fn ($product) => [
                        'name' => trim((string) $product['name']),
                        'quantity' => (int) $product['quantity'],
                        'unit' => trim((string) ($product['unit'] ?? 'unité')),
                    ])->values()->all(),
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * Gemini ne calcule jamais le panier : il résume uniquement les résultats
     * déjà calculés et vérifiés localement par SmartPanierService.
     */
    public function summarize(array $result): ?string
    {
        if (! config('services.gemini.enabled') || ! config('services.gemini.api_key') || empty($result['proposals'])) {
            return null;
        }

        $payload = collect($result['proposals'])->map(fn (array $proposal) => [
            'label' => $proposal['label'],
            'total_dt' => round($proposal['total'] / 1000, 3),
            'remaining_dt' => round(($proposal['budget'] - $proposal['total']) / 1000, 3),
            'complete' => $proposal['complete'],
            'items' => collect($proposal['items'])->map(fn (array $item) => [
                'product' => $item['name'],
                'producer' => $item['offer']['producer'],
                'quantity' => $item['quantite'],
                'unit_price_dt' => $item['offer']['price'],
            ])->values()->all(),
        ])->values()->all();

        $cacheKey = 'gemini-smart-basket:'.sha1(json_encode($payload, JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($payload) {
            $prompt = "Tu es un assistant de présentation pour NutriTrace. Résume en français les propositions de paniers ci-dessous en 2 ou 3 phrases. Ne change aucun chiffre, produit, producteur ou quantité. Ne recommande pas d'achat et ne crée aucune information. Indique simplement quelle proposition est la moins chère et rappelle que les données viennent du serveur. Données vérifiées :\n".json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            try {
                return $this->request($prompt, [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 180,
                ]);
            } catch (\Throwable) {
                return null;
            }
        });
    }

    private function request(string $prompt, array $generationConfig): ?string
    {
        $response = Http::timeout(config('services.gemini.timeout', 8))
            ->withHeaders([
                'x-goog-api-key' => config('services.gemini.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->post(sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                config('services.gemini.model', 'gemini-2.5-flash-lite')
            ), [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => $generationConfig,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text')) ?: null;
    }
}
