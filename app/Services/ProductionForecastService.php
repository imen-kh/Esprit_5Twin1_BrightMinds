<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class ProductionForecastService
{
    private const EXCLUDED_STATUSES = ['annulee', 'refusee'];

    public function productsFor(User $user): Collection
    {
        return Product::query()
            ->where('user_id', $user->id)
            ->where('status', 'published')
            ->where('type_produit', $user->isTransformateur() ? 'transforme' : 'matiere_premiere')
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    public function forecast(User $user, Product $product, int $horizon): array
    {
        $history = $this->history($product);
        $stock = $this->availableStock($product);
        $materials = $this->materials($user, $product);

        $payload = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'horizon' => $horizon,
            'history' => $history,
        ];

        if (count($history) < 7 || count(array_unique(array_column($history, 'date'))) < 5) {
            return $this->insufficient($payload, $stock, 'Il faut au moins 7 observations réparties sur 5 dates pour entraîner une prévision fiable.', $materials);
        }

        try {
            $result = config('services.forecast.mode', 'cli') === 'http'
                ? $this->callHttp($history, $horizon)
                : $this->callPythonCli($history, $horizon);
            if (($result['status'] ?? null) !== 'ok') {
                return $this->insufficient($payload, $stock, $result['message'] ?? 'Historique insuffisant pour cette prévision.');
            }

            $predictions = collect($result['predictions'] ?? [])
                ->map(fn (array $prediction) => [
                    'date' => $prediction['date'],
                    'quantity' => round(max(0, (float) $prediction['quantity']), 3),
                ])
                ->values()
                ->all();

            return array_merge($payload, [
                'status' => 'ok',
                'predictions' => $predictions,
                'metrics' => $result['metrics'] ?? [],
                'stock' => $stock,
                'summary' => $this->summary($history, $predictions, $stock),
                'materials' => $materials,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            if (config('services.forecast.fallback', true)) {
                return $this->localFallback($payload, $history, $horizon, $stock, $materials, $exception->getMessage());
            }

            return $this->unavailable($payload, $stock, 'Le service Python est indisponible. Démarrez-le puis réessayez.');
        }
    }

    private function callHttp(array $history, int $horizon): array
    {
        $url = (string) config('services.forecast.url');
        if ($url === '') {
            throw new \RuntimeException('FORECAST_API_URL est vide.');
        }

        $response = Http::timeout((int) config('services.forecast.timeout', 15))
            ->acceptJson()
            ->post(rtrim($url, '/'), ['history' => $history, 'horizon' => $horizon]);

        if (! $response->successful()) {
            throw new \RuntimeException('Le service HTTP de prévision a refusé la demande.');
        }

        return $response->json();
    }

    private function callPythonCli(array $history, int $horizon): array
    {
        $process = new Process([
            $this->pythonExecutable(),
            base_path('python/forecast_cli.py'),
        ], base_path(), null, json_encode([
            'history' => $history,
            'horizon' => $horizon,
        ], JSON_THROW_ON_ERROR));
        $process->setTimeout((int) config('services.forecast.timeout', 15));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'Python n’a pas pu exécuter la prévision.');
        }

        return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function pythonExecutable(): string
    {
        $configured = (string) config('services.forecast.python', 'auto');
        if ($configured !== '' && $configured !== 'auto') {
            return $configured;
        }

        $candidates = ['python', 'py'];
        if (PHP_OS_FAMILY === 'Windows') {
            $roots = array_filter([
                getenv('LOCALAPPDATA'),
                getenv('USERPROFILE') ? getenv('USERPROFILE').'\\AppData\\Local' : null,
            ]);
            foreach (array_unique($roots) as $root) {
                $installed = glob($root.'\\Programs\\Python\\Python*\\python.exe') ?: [];
                rsort($installed, SORT_NATURAL);
                $candidates = array_merge($candidates, $installed);
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            $check = new Process([$candidate, '--version']);
            $check->setTimeout(3);
            $check->run();
            if ($check->isSuccessful()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Aucun interpréteur Python utilisable n’a été trouvé.');
    }

    private function localFallback(array $payload, array $history, int $horizon, float $stock, array $materials, string $reason): array
    {
        $values = collect($history)->pluck('quantity')->map(fn ($value) => (float) $value)->values();
        $window = $values->slice(-3)->values();
        $average = $window->avg() ?: 0;
        $trend = $window->count() > 1 ? (($window->last() - $window->first()) / ($window->count() - 1)) : 0;
        $lastDate = \Carbon\CarbonImmutable::parse(collect($history)->last()['date']);
        $predictions = [];

        for ($day = 1; $day <= $horizon; $day++) {
            $predictions[] = [
                'date' => $lastDate->addDays($day)->toDateString(),
                'quantity' => round(max(0, $average + ($trend * $day)), 3),
            ];
        }

        return array_merge($payload, [
            'status' => 'ok',
            'source' => 'local_fallback',
            'message' => 'Random Forest est momentanément indisponible. Une estimation locale de secours basée sur les dernières commandes est affichée.',
            'technical_reason' => $reason,
            'predictions' => $predictions,
            'metrics' => [],
            'stock' => $stock,
            'summary' => $this->summary($history, $predictions, $stock),
            'materials' => $materials,
        ]);
    }

    private function history(Product $product): array
    {
        return $product->commandes()
            ->whereNotIn('commandes.statut', self::EXCLUDED_STATUSES)
            ->whereNotNull('commandes.date_commande')
            ->selectRaw('DATE(commandes.date_commande) as date, SUM(commande_produit.quantite) as quantity')
            ->groupByRaw('DATE(commandes.date_commande)')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'quantity' => (float) $row->quantity,
            ])
            ->values()
            ->all();
    }

    private function availableStock(Product $product): float
    {
        return (float) $product->lots()
            ->where('statut', 'disponible')
            ->where(fn ($query) => $query->whereNull('date_peremption')->orWhereDate('date_peremption', '>=', today()))
            ->sum('quantite');
    }

    private function summary(array $history, array $predictions, float $stock): array
    {
        $historicalAverage = collect($history)->avg('quantity') ?: 0;
        $forecastTotal = collect($predictions)->sum('quantity');
        $forecastAverage = collect($predictions)->avg('quantity') ?: 0;

        return [
            'historical_average' => round($historicalAverage, 3),
            'forecast_total' => round($forecastTotal, 3),
            'forecast_average' => round($forecastAverage, 3),
            'stock_gap' => round(max(0, $forecastTotal - $stock), 3),
            'trend' => $forecastAverage > $historicalAverage * 1.1
                ? 'hausse'
                : ($forecastAverage < $historicalAverage * 0.9 ? 'baisse' : 'stable'),
        ];
    }

    private function materials(User $user, Product $product): array
    {
        if (! $user->isTransformateur()) {
            return ['status' => 'not_applicable', 'message' => null, 'origins' => []];
        }

        $origins = $product->lots()
            ->whereNotNull('lot_origine_id')
            ->with('lotOrigine.produit')
            ->get()
            ->map(fn ($lot) => $lot->lotOrigine?->produit?->name)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'status' => empty($origins) ? 'missing' : 'provenance_only',
            'message' => empty($origins)
                ? 'Aucune recette ou relation de matière première exploitable n’est enregistrée.'
                : 'Une provenance de lot existe, mais aucun rendement ne permet de calculer une quantité nécessaire.',
            'origins' => $origins,
        ];
    }

    private function insufficient(array $payload, float $stock, string $message, array $materials = []): array
    {
        return array_merge($payload, [
            'status' => 'insufficient',
            'message' => $message,
            'stock' => $stock,
            'predictions' => [],
            'metrics' => [],
            'summary' => null,
            'materials' => $materials,
        ]);
    }

    private function unavailable(array $payload, float $stock, string $message): array
    {
        return array_merge($payload, [
            'status' => 'unavailable',
            'message' => $message,
            'stock' => $stock,
            'predictions' => [],
            'metrics' => [],
            'summary' => null,
            'materials' => [],
        ]);
    }
}
