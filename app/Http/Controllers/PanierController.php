<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Panier;
use App\Models\Product;
use App\Services\SmartPanierService;
use App\Services\GeminiSmartBasketService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PanierController extends Controller
{
    private function getCart()
    {
        if (Auth::check()) {
            $panier = Panier::firstOrCreate(
                ['user_id' => Auth::id(), 'statut' => 'actif']
            );
            // Link session cart to user if it exists and user cart is empty
            $session_panier_id = session('panier_id');
            if ($session_panier_id && $session_panier_id != $panier->id) {
                $sessionPanier = Panier::find($session_panier_id);
                if ($sessionPanier && $sessionPanier->statut === 'actif') {
                    foreach ($sessionPanier->products as $product) {
                        $existingProduct = $panier->products()->where('product_id', $product->id)->first();
                        if ($existingProduct) {
                            $panier->products()->updateExistingPivot($product->id, [
                                'quantite' => $existingProduct->pivot->quantite + $product->pivot->quantite
                            ]);
                        } else {
                            $panier->products()->attach($product->id, ['quantite' => $product->pivot->quantite]);
                        }
                    }
                    $sessionPanier->delete();
                    session()->forget('panier_id');
                }
            }
            return $panier;
        } else {
            $panier_id = session('panier_id');
            if ($panier_id) {
                $panier = Panier::where('id', $panier_id)->where('statut', 'actif')->first();
                if ($panier) {
                    return $panier;
                }
            }
            $panier = Panier::create(['statut' => 'actif']);
            session(['panier_id' => $panier->id]);
            return $panier;
        }
    }

    public function index()
    {
        $this->authorizeCartClient();
        $panier = $this->getCart();
        $panier->load('products');
        $catalogue = app(SmartPanierService::class)->catalogue($panier);

        return view('front.panier', [
            'panier' => $panier,
            'catalogue' => $catalogue,
            'categories' => $catalogue->pluck('category')->filter()->unique('id')->sortBy('name')->values(),
            'producerOptions' => $this->producerOptions($catalogue, $panier),
            'proposals' => [],
            'smartBudget' => null,
            'smartInput' => [],
            'aiSummary' => null,
            'parseMessage' => null,
            'naturalText' => null,
        ]);
    }

    public function parseSmartRequest(Request $request)
    {
        $this->authorizeCartClient();
        $data = $request->validate([
            'budget' => ['nullable', 'numeric', 'gt:0', 'max:9999999.999'],
            'need_description' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $panier = $this->getCart();
        $panier->load('products');
        $smartService = app(SmartPanierService::class);
        $catalogue = app(SmartPanierService::class)->catalogue($panier);
        $textBudget = isset($data['budget'])
            ? (float) $data['budget']
            : $this->extractBudgetLocally($data['need_description']);
        $extracted = app(GeminiSmartBasketService::class)->extractRequest($data['need_description'], $textBudget);
        $usedLocalFallback = false;
        if (! $extracted) {
            $extracted = $this->extractRequestLocally($data['need_description'], $textBudget, $catalogue->filter(fn (Product $product) => $smartService->stockForDisplay($product, $panier) > 0));
            $usedLocalFallback = $extracted !== null;
        }
        if (! $extracted) {
            $availableNames = $catalogue
                ->filter(fn (Product $product) => $smartService->stockForDisplay($product, $panier) > 0)
                ->pluck('name')
                ->unique()
                ->values();
            $availableMessage = $availableNames->isEmpty()
                ? 'Aucun lot non périmé n’est actuellement disponible.'
                : 'Produits disponibles : '.$availableNames->join(', ').'.';

            return back()->withInput()->withErrors([
                'need_description' => 'Gemini n’est pas configuré et aucun produit réel n’a été reconnu. '.$availableMessage.' Utilisez la sélection manuelle ci-dessous.',
            ]);
        }

        $requests = [];
        $unmatched = [];
        foreach ($extracted['products'] as $requestedProduct) {
            $name = $this->normalizeProductName($requestedProduct['name']);
            $product = $catalogue->filter(fn (Product $candidate) => $smartService->stockForDisplay($candidate, $panier) > 0)->first(function (Product $candidate) use ($name) {
                $candidateName = $this->normalizeProductName($candidate->name);
                return $candidateName === $name || str_contains($candidateName, $name) || str_contains($name, $candidateName);
            });
            if ($product) {
                $requests[] = ['product_id' => $product->id, 'quantite' => $requestedProduct['quantity']];
            } else {
                $unmatched[] = $requestedProduct['name'];
            }
        }

        $smartInput = [
            'budget' => $extracted['budget'],
            'preference' => $extracted['preferences'][0] ?? 'cheapest',
            'selection_mode' => 'auto',
            'requests' => $requests,
        ];
        $message = $unmatched
            ? 'Produits non trouvés dans le catalogue réel : '.implode(', ', $unmatched).'. Vérifiez la sélection avant de comparer.'
            : ($extracted['budget'] === null
                ? 'Produits et quantités compris. Saisissez maintenant le budget maximum dans la section « Composer ma demande », puis cliquez sur « Comparer et proposer ».'
                : 'Demande comprise. Vérifiez les produits et quantités, puis cliquez sur « Comparer et proposer ». ');
        if ($usedLocalFallback) {
            $message = 'Gemini est indisponible : extraction locale utilisée à partir du catalogue réel. '.$message;
        }

        return view('front.panier', [
            'panier' => $panier,
            'catalogue' => $catalogue,
            'categories' => $catalogue->pluck('category')->filter()->unique('id')->sortBy('name')->values(),
            'producerOptions' => $this->producerOptions($catalogue, $panier),
            'proposals' => [],
            'smartBudget' => $extracted['budget'],
            'smartInput' => $smartInput,
            'aiSummary' => null,
            'parseMessage' => $message,
            'naturalText' => $data['need_description'],
        ]);
    }

    public function smartAnalyze(Request $request)
    {
        $this->authorizeCartClient();
        $data = $request->validate([
            'budget' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'requests' => ['required', 'array', 'min:1'],
            'requests.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'requests.*.quantite' => ['required', 'integer', 'min:1', 'max:100000'],
            'preference' => ['required', 'in:cheapest,local,certified'],
            'selection_mode' => ['required', 'in:auto,manual'],
            'choices' => ['nullable', 'array'],
            // Une valeur vide signifie « sélection automatique du meilleur prix ».
            'choices.*' => ['nullable', 'integer', 'min:1'],
        ], [
            'budget.*' => 'Veuillez saisir un budget valide en DT.',
            'requests.required' => 'Sélectionnez au moins un produit.',
            'requests.*.quantite.min' => 'La quantité doit être supérieure à zéro.',
        ]);

        $panier = $this->getCart();
        $panier->load('products');

        $data['choices'] = collect($data['choices'] ?? [])->mapWithKeys(function ($offerId, $lineIndex) use ($data) {
            $productId = $data['requests'][$lineIndex]['product_id'] ?? null;
            return $productId ? [(int) $productId => (int) $offerId] : [];
        })->all();

        try {
            $result = app(SmartPanierService::class)->propose($data, $panier);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['requests' => $exception->getMessage()]);
        }

        $aiSummary = app(GeminiSmartBasketService::class)->summarize($result);

        $catalogue = app(SmartPanierService::class)->catalogue($panier);

        return view('front.panier', [
            'panier' => $panier,
            'catalogue' => $catalogue,
            'categories' => $catalogue->pluck('category')->filter()->unique('id')->sortBy('name')->values(),
            'producerOptions' => $this->producerOptions($catalogue, $panier),
            'proposals' => $result['proposals'],
            'smartBudget' => (float) $data['budget'],
            'smartInput' => $data,
            'aiSummary' => $aiSummary,
            'parseMessage' => null,
            'naturalText' => null,
        ]);
    }

    public function confirmSmart(Request $request)
    {
        $this->authorizeCartClient();
        $data = $request->validate([
            'budget' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantite' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $budget = $this->toMillimes($data['budget']);
        try {
            DB::transaction(function () use ($data, $budget) {
                $panier = $this->getCart();
                $ids = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->values();
                abort_if($ids->count() !== $ids->unique()->count(), 422, 'Un produit ne peut apparaître qu’une seule fois dans la sélection.');

                $products = Product::whereIn('id', $ids)->where('status', 'published')->lockForUpdate()->get()->keyBy('id');
                abort_if($products->count() !== $ids->count(), 422, 'Un produit sélectionné n’est plus publié.');

                $total = 0;
                foreach ($data['items'] as $item) {
                    $product = $products->get((int) $item['product_id']);
                    $stock = (int) $product->lots()->where('statut', 'disponible')
                        ->where(fn ($query) => $query->whereNull('date_peremption')->orWhereDate('date_peremption', '>=', today()))
                        ->lockForUpdate()->sum('quantite');
                    $existing = (int) ($panier->products()->whereKey($product->id)->first()?->pivot?->quantite ?? 0);
                    abort_if($existing + (int) $item['quantite'] > $stock, 422, "Le stock de {$product->name} est insuffisant.");
                    $total += $this->toMillimes($product->price) * (int) $item['quantite'];
                }
                abort_if($total > $budget, 422, 'La sélection dépasse le budget saisi.');

                foreach ($data['items'] as $item) {
                    $product = $products->get((int) $item['product_id']);
                    $existing = $panier->products()->whereKey($product->id)->first();
                    if ($existing) {
                        $panier->products()->updateExistingPivot($product->id, ['quantite' => $existing->pivot->quantite + (int) $item['quantite']]);
                    } else {
                        $panier->products()->attach($product->id, ['quantite' => (int) $item['quantite']]);
                    }
                }
            });
        } catch (HttpExceptionInterface $exception) {
            return redirect()->route('panier')->with('error', $exception->getMessage());
        }

        return redirect()->route('panier')->with('success', 'La proposition choisie a été ajoutée au panier.');
    }

    public function optimize(Request $request)
    {
        $this->authorizeCartClient();
        $request->validate([
            'budget' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
        ], [
            'budget.required' => 'Veuillez saisir un budget.',
            'budget.numeric' => 'Le budget doit être un montant valide en DT.',
            'budget.gt' => 'Le budget doit être supérieur à zéro.',
        ]);

        return redirect()->route('panier')->withErrors([
            'requests' => 'Sélectionnez les produits et leurs quantités avant de lancer la comparaison.',
        ]);
    }

    public function confirmOptimization(Request $request)
    {
        $this->authorizeCartClient();
        $data = $request->validate([
            'budget' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'integer', 'min:1'],
        ]);
        $budget = $this->toMillimes($data['budget']);

        DB::transaction(function () use ($data, $budget) {
            $panier = $this->getCart();
            $ids = array_map('intval', array_keys($data['items']));
            $products = Product::whereIn('id', $ids)->where('status', 'published')->lockForUpdate()->get()->keyBy('id');
            abort_if($products->count() !== count(array_unique($ids)), 422, 'Un produit sélectionné n’est plus disponible.');

            $total = 0;
            foreach ($data['items'] as $productId => $quantite) {
                $product = $products->get((int) $productId);
                abort_if(! $product, 422, 'Un produit sélectionné n’est plus disponible.');
                $stock = (int) $product->lots()->where('statut', 'disponible')
                    ->where(fn ($query) => $query->whereNull('date_peremption')->orWhereDate('date_peremption', '>=', today()))
                    ->lockForUpdate()->sum('quantite');
                $existing = (int) ($panier->products()->whereKey($product->id)->first()?->pivot?->quantite ?? 0);
                abort_if($existing + (int) $quantite > $stock, 422, "Le stock de {$product->name} est insuffisant.");
                $total += $this->toMillimes($product->price) * (int) $quantite;
                abort_if($total > $budget, 422, 'La sélection dépasse le budget saisi.');
            }

            foreach ($data['items'] as $productId => $quantite) {
                $product = $products->get((int) $productId);
                $existing = $panier->products()->whereKey($product->id)->first();
                if ($existing) {
                    $panier->products()->updateExistingPivot($product->id, ['quantite' => $existing->pivot->quantite + (int) $quantite]);
                } else {
                    $panier->products()->attach($product->id, ['quantite' => (int) $quantite]);
                }
            }
        });

        return redirect()->route('panier')->with('success', 'La sélection optimisée a été ajoutée au panier.');
    }

    private function toMillimes(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 1000);
    }

    private function producerOptions($catalogue, Panier $panier): array
    {
        $service = app(SmartPanierService::class);

        return $catalogue->mapWithKeys(function (Product $product) use ($catalogue, $panier, $service) {
            $offers = $catalogue->filter(fn (Product $offer) =>
                $offer->category_id === $product->category_id
                && mb_strtolower(trim($offer->name)) === mb_strtolower(trim($product->name))
            )->map(fn (Product $offer) => [
                'id' => $offer->id,
                'producer' => $offer->user?->nom_entreprise ?: ($offer->user?->name ?? 'Producteur non renseigné'),
                'price' => $offer->formattedPrice(),
                'stock' => $service->stockForDisplay($offer, $panier),
            ])->values()->all();

            return [$product->id => $offers];
        })->all();
    }

    private function normalizeProductName(string $name): string
    {
        $ascii = Str::ascii(mb_strtolower($name));
        return trim((string) preg_replace('/\s+/u', ' ', preg_replace('/[^a-z0-9]+/i', ' ', $ascii)));
    }

    private function extractRequestLocally(string $text, mixed $budget, $catalogue): ?array
    {
        $normalizedText = $this->normalizeProductName($text);
        $products = [];
        $matchedNames = [];
        $explicitNames = collect($catalogue)
            ->map(fn (Product $product) => $this->normalizeProductName($product->name))
            ->filter(fn (string $name) => str_contains($normalizedText, $name))
            ->values();

        foreach ($catalogue as $product) {
            $name = $this->normalizeProductName($product->name);
            if (isset($matchedNames[$name])) {
                continue;
            }
            $match = $this->catalogueNameMatch($normalizedText, $name);
            if ($match === null) {
                continue;
            }
            if ($match['score'] < 100 && $explicitNames->contains(fn (string $explicitName) => str_contains($explicitName, $match['term']))) {
                continue;
            }

            $escapedName = preg_quote($match['term'], '/');
            $quantity = 1;
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:kg|kilos?|unites?|l|litres?)?\s*(?:de|d)?\s*'.$escapedName.'/u', $normalizedText, $matches)) {
                $quantity = max(1, (int) round((float) str_replace(',', '.', $matches[1])));
            } elseif (preg_match('/'.$escapedName.'\s*[:\-]?\s*(\d+(?:[.,]\d+)?)/u', $normalizedText, $matches)) {
                $quantity = max(1, (int) round((float) str_replace(',', '.', $matches[1])));
            }

            $products[$product->id] = [
                'name' => $product->name,
                'quantity' => $quantity,
                'unit' => 'unité',
            ];
            $matchedNames[$name] = true;
        }

        if (! $products) {
            return null;
        }

        $preference = str_contains($normalizedText, 'certif') ? 'certified'
            : (str_contains($normalizedText, 'local') ? 'local' : 'cheapest');

        return [
            'budget' => is_numeric($budget) ? (float) $budget : null,
            'preferences' => [$preference],
            'products' => array_values($products),
        ];
    }

    private function extractBudgetLocally(string $text): ?float
    {
        $normalizedText = Str::ascii(mb_strtolower($text));

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:dt|dinars?|tnd)\b/u', $normalizedText, $matches)) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    /**
     * Reconnaît un nom complet ou le produit principal d'une formulation naturelle.
     * Exemple : « tomates », « tomates fraîches » et « 3 kg de tomates ».
     * Les résultats restent limités aux produits réellement présents dans le catalogue.
     */
    private function catalogueNameMatch(string $text, string $catalogueName): ?array
    {
        if (str_contains($text, $catalogueName)) {
            return ['term' => $catalogueName, 'score' => 100 + strlen($catalogueName)];
        }

        $ignored = ['de', 'du', 'des', 'la', 'le', 'les', 'et', 'au', 'aux'];
        $tokens = array_values(array_filter(explode(' ', $catalogueName), fn (string $token) => ! in_array($token, $ignored, true) && strlen($token) >= 4));
        $best = null;

        // Pour éviter qu'une demande « fraises » sélectionne aussi
        // « confiture de fraises », le premier mot important du produit
        // doit être présent lorsque le nom complet n'est pas écrit.
        $primaryToken = $tokens[0] ?? null;
        if ($primaryToken !== null) {
            $primaryStem = rtrim($primaryToken, 's');
            $primaryFound = preg_match('/(?:^|\s)'.preg_quote($primaryToken, '/').'(?=\s|$)/u', $text)
                || preg_match('/(?:^|\s)'.preg_quote($primaryStem, '/').'(?=\s|$)/u', $text);
            if (! $primaryFound) {
                return null;
            }
        }

        foreach ($tokens as $token) {
            $stem = rtrim($token, 's');
            if (! preg_match('/(?:^|\s)'.preg_quote($token, '/').'(?=\s|$)/u', $text)
                && ! preg_match('/(?:^|\s)'.preg_quote($stem, '/').'(?=\s|$)/u', $text)) {
                continue;
            }

            $score = 20 + strlen($token);
            if ($best === null || $score > $best['score']) {
                $best = ['term' => $token, 'score' => $score];
            }
        }

        return $best;
    }

    public function add(Request $request)
    {
        $this->authorizeCartClient();
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantite' => 'required|integer|min:1'
        ]);

        $panier = $this->getCart();
        $product_id = $request->product_id;
        $quantite = $request->quantite;

        $existingProduct = $panier->products()->where('product_id', $product_id)->first();

        if ($existingProduct) {
            $panier->products()->updateExistingPivot($product_id, [
                'quantite' => $existingProduct->pivot->quantite + $quantite
            ]);
        } else {
            $panier->products()->attach($product_id, ['quantite' => $quantite]);
        }

        return redirect()->back()->with('success', 'Produit ajouté au panier.');
    }

    public function update(Request $request, $product_id)
    {
        $this->authorizeCartClient();
        $request->validate([
            'quantite' => 'required|integer|min:1'
        ]);

        $panier = $this->getCart();
        
        $panier->products()->updateExistingPivot($product_id, [
            'quantite' => $request->quantite
        ]);

        return redirect()->route('panier')->with('success', 'Quantité modifiée.');
    }

    public function remove($product_id)
    {
        $this->authorizeCartClient();
        $panier = $this->getCart();
        $panier->products()->detach($product_id);

        return redirect()->route('panier')->with('success', 'Produit retiré du panier.');
    }

    private function authorizeCartClient(): void
    {
        abort_unless(
            Auth::check() && (Auth::user()->isConsommateur() || Auth::user()->isTransformateur()),
            403,
            'Le panier est réservé aux consommateurs et aux transformateurs.'
        );
    }
}
