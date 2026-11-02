<?php

namespace App\Services;

use App\Models\Panier;
use App\Models\Product;
use Illuminate\Support\Collection;

class SmartPanierService
{
    public function catalogue(Panier $panier): Collection
    {
        return Product::query()
            ->where('status', 'published')
            ->with(['user', 'category'])
            ->withSum(['lots as stock_disponible' => fn ($query) => $this->stockQuery($query)], 'quantite')
            ->orderBy('name')
            ->get()
            ->values();
    }

    public function stockForDisplay(Product $product, Panier $panier): int
    {
        return $this->stockRestant($product, $panier);
    }

    public function propose(array $data, Panier $panier): array
    {
        $requested = collect($data['requests'])->mapWithKeys(fn ($line) => [
            (int) $line['product_id'] => (int) $line['quantite'],
        ]);
        $products = Product::query()
            ->whereIn('id', $requested->keys())
            ->where('status', 'published')
            ->with('user')
            ->get()
            ->keyBy('id');

        if ($products->count() !== $requested->count()) {
            throw new \InvalidArgumentException('Un des produits demandés n’est plus disponible.');
        }

        $lines = [];
        $unavailable = [];
        foreach ($requested as $productId => $quantity) {
            $product = $products->get((int) $productId);
            $offers = $this->offersFor($product, $panier);
            if ($offers->isEmpty()) {
                $unavailable[] = $product->name;
                continue;
            }

            $lines[] = [
                'requested_product_id' => (int) $productId,
                'name' => $product->name,
                'requested_quantity' => $quantity,
                'offers' => $offers->values()->all(),
            ];
        }

        if ($unavailable) {
            throw new \InvalidArgumentException('Aucune offre disponible pour : '.implode(', ', $unavailable).'.');
        }

        $preference = $data['preference'] ?? 'cheapest';
        $manualChoices = collect($data['choices'] ?? [])->map(fn ($id) => (int) $id)->all();
        $proposals = [];

        $cheapest = $this->buildProposal($lines, 'cheapest', $data['budget'], $manualChoices, $data['selection_mode'] ?? 'auto');
        $proposals[] = $this->labelProposal($cheapest, 'Prix minimum', 'Les offres les moins chères disponibles pour les quantités demandées.');

        $preferred = $this->buildProposal($lines, $preference, $data['budget'], $manualChoices, $data['selection_mode'] ?? 'auto');
        if ($this->signature($preferred) !== $this->signature($cheapest)) {
            $proposals[] = $this->labelProposal($preferred, $this->preferenceLabel($preference), 'Une proposition classée selon votre préférence.');
        }

        $alternative = $this->buildAlternative($lines, $cheapest, $data['budget']);
        if ($alternative && ! in_array($this->signature($alternative), array_map(fn ($proposal) => $this->signature($proposal), $proposals), true)) {
            $proposals[] = $this->labelProposal($alternative, 'Alternative producteur', 'Une autre combinaison de producteurs, si les données le permettent.');
        }

        return ['proposals' => array_slice($proposals, 0, 3), 'requested' => $requested->all()];
    }

    private function offersFor(Product $requested, Panier $panier): Collection
    {
        return Product::query()
            ->where('status', 'published')
            ->where('category_id', $requested->category_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($requested->name))])
            ->with(['user', 'certifications' => fn ($query) => $query->verified()->with('label')])
            ->withSum(['lots as stock_disponible' => fn ($query) => $this->stockQuery($query)], 'quantite')
            ->orderBy('price')
            ->get()
            ->filter(fn (Product $offer) => $this->stockRestant($offer, $panier) > 0)
            ->map(fn (Product $offer) => $this->offerData($offer, $panier))
            ->values();
    }

    private function offerData(Product $offer, Panier $panier): array
    {
        $certifications = $offer->certifications->map(fn ($certification) => [
            'name' => $certification->label?->nom ?? 'Certification validée',
            'number' => $certification->numero,
        ])->values()->all();

        return [
            'product_id' => $offer->id,
            'producer_id' => $offer->user_id,
            'producer' => $offer->user?->nom_entreprise ?: ($offer->user?->name ?? 'Producteur non renseigné'),
            'image' => $offer->imageUrl(),
            'price' => (float) $offer->price,
            'price_millimes' => $this->millimes($offer->price),
            'stock' => $this->stockRestant($offer, $panier),
            'origin' => $offer->origin,
            'certifications' => $certifications,
            'certified' => count($certifications) > 0,
        ];
    }

    private function buildProposal(array $lines, string $strategy, float|int|string $budget, array $manualChoices, string $mode): array
    {
        $items = [];
        foreach ($lines as $line) {
            $offers = collect($line['offers']);
            $selected = null;
            if ($mode === 'manual' && isset($manualChoices[$line['requested_product_id']])) {
                $selected = $offers->firstWhere('product_id', $manualChoices[$line['requested_product_id']]);
            }
            $selected ??= $this->rankOffers($offers, $strategy)->first();
            if (! $selected) {
                continue;
            }

            $items[] = [
                'name' => $line['name'],
                'requested_product_id' => $line['requested_product_id'],
                'requested_quantity' => $line['requested_quantity'],
                'quantite' => min($line['requested_quantity'], $selected['stock']),
                'offer' => $selected,
                'offers' => $line['offers'],
            ];
        }

        return $this->fitBudget($items, $this->millimes($budget));
    }

    private function rankOffers(Collection $offers, string $strategy): Collection
    {
        return match ($strategy) {
            'certified' => $offers->sortBy([['certified', 'desc'], ['price_millimes', 'asc']])->values(),
            'local' => $offers->sortBy([['origin', 'desc'], ['price_millimes', 'asc']])->values(),
            default => $offers->sortBy('price_millimes')->values(),
        };
    }

    private function fitBudget(array $items, int $budget): array
    {
        $total = $this->total($items);
        foreach ($items as $index => $item) {
            if ($total <= $budget) {
                break;
            }
            $cheaper = collect($item['offers'])->filter(fn ($offer) => $offer['price_millimes'] < $item['offer']['price_millimes'])->sortBy('price_millimes')->first();
            if ($cheaper) {
                $items[$index]['offer'] = $cheaper;
                $items[$index]['quantite'] = min($item['requested_quantity'], $cheaper['stock']);
                $total = $this->total($items);
            }
        }

        $total = $this->total($items);
        while ($total > $budget) {
            $index = collect($items)->filter(fn ($item) => $item['quantite'] > 0)->sortByDesc(fn ($item) => $item['offer']['price_millimes'])->keys()->first();
            if ($index === null) {
                break;
            }
            $total -= $items[$index]['offer']['price_millimes'];
            $items[$index]['quantite']--;
        }

        return [
            'items' => array_values(array_filter($items, fn ($item) => $item['quantite'] > 0)),
            'total' => max(0, $this->total($items)),
            'budget' => $budget,
            'complete' => collect($items)->every(fn ($item) => $item['quantite'] >= $item['requested_quantity']),
        ];
    }

    private function buildAlternative(array $lines, array $base, float|int|string $budget): ?array
    {
        foreach ($base['items'] as $index => $item) {
            $alternative = collect($item['offers'])->first(fn ($offer) => $offer['product_id'] !== $item['offer']['product_id']);
            if (! $alternative) {
                continue;
            }
            $items = $base['items'];
            $items[$index]['offer'] = $alternative;
            $items[$index]['quantite'] = min($items[$index]['requested_quantity'], $alternative['stock']);
            return $this->fitBudget($items, $this->millimes($budget));
        }
        return null;
    }

    private function labelProposal(array $proposal, string $label, string $description): array
    {
        return array_merge($proposal, ['label' => $label, 'description' => $description]);
    }

    private function signature(array $proposal): string
    {
        return collect($proposal['items'])
            ->map(fn ($item) => $item['offer']['product_id'].':'.$item['offer']['producer_id'].':'.$item['quantite'])
            ->join('|');
    }

    private function total(array $items): int
    {
        return (int) collect($items)->sum(fn ($item) => $item['offer']['price_millimes'] * $item['quantite']);
    }

    private function stockRestant(Product $product, Panier $panier): int
    {
        $alreadyInCart = (int) ($panier->products->firstWhere('id', $product->id)?->pivot?->quantite ?? 0);
        $stock = $product->getAttribute('stock_disponible');
        $stock ??= $this->stockQuery($product->lots())->sum('quantite');

        return max(0, (int) $stock - $alreadyInCart);
    }

    private function stockQuery($query)
    {
        return $query->where('statut', 'disponible')
            ->where(fn ($query) => $query->whereNull('date_peremption')->orWhereDate('date_peremption', '>=', today()));
    }

    private function millimes(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 1000);
    }

    private function preferenceLabel(string $preference): string
    {
        return match ($preference) {
            'certified' => 'Priorité certification',
            'local' => 'Priorité origine renseignée',
            default => 'Prix minimum',
        };
    }
}
