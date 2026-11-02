<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Recherche du catalogue : phrase naturelle, filtres exacts, puis Meilisearch ou le SQL Laravel.
 * Aucun attribut absent de la fiche (bio, sans sucre) n'est inventé.
 */
class RechercheIntelligenteService
{
    private const SEUIL_SEMANTIQUE = 0.82;

    /** @var array<string, string> */
    private const VILLES = [
        'cap bon' => 'Cap Bon',
        'le kef' => 'Le Kef',
        'nabeul' => 'Nabeul',
        'sfax' => 'Sfax',
        'sousse' => 'Sousse',
        'tunis' => 'Tunis',
        'bizerte' => 'Bizerte',
        'ariana' => 'Ariana',
        'kasserine' => 'Kasserine',
        'gafsa' => 'Gafsa',
        'gabes' => 'Gabès',
        'kairouan' => 'Kairouan',
        'beja' => 'Béja',
        'jendouba' => 'Jendouba',
        'tozeur' => 'Tozeur',
        'monastir' => 'Monastir',
        'mahdia' => 'Mahdia',
        'zaghouan' => 'Zaghouan',
        'siliana' => 'Siliana',
        'manouba' => 'Manouba',
        'medenine' => 'Médenine',
        'tataouine' => 'Tataouine',
        'kebili' => 'Kébili',
        'djerba' => 'Djerba',
    ];

    /** @var array<string, list<string>> */
    private const FAMILLES = [
        'legume' => ['khodhra', 'khodra', 'khodhret', 'legume', 'legumes', 'verdure'],
        'fruit' => ['fruit', 'fruits', 'ghalla', 'fawakih', 'ghallet'],
    ];

    /** @var array<string, list<string>> */
    private const NOMS_FAMILLE = [
        'legume' => ['tomate', 'piment', 'oignon', 'salade', 'carotte', 'pomme de terre', 'concombre', 'courgette', 'poivron', 'navet', 'epinard', 'laitue', 'haricot', 'artichaut', 'chou', 'ail', 'persil'],
        'fruit' => ['pomme', 'orange', 'citron', 'fraise', 'banane', 'abricot', 'raisin', 'peche', 'poire', 'pasteque', 'melon', 'datte', 'figue', 'grenade', 'cerise', 'mangue'],
    ];

    public function __construct(private RechercheSemantiqueService $semantique) {}

    /**
     * @param  Collection<int, Category>  $categories
     * @return array{phrase: string, category_ids: list<int>, origin: ?string, price_min: ?float, price_max: ?float, prix_bas: bool, termes: list<string>, familles: list<string>, resume: string}
     */
    public function interpreter(string $phrase, Collection $categories): array
    {
        $phrase = trim($phrase);
        $texte = $this->normaliser($phrase);
        $prixMin = null;
        $prixMax = null;

        if (preg_match('/entre\s+(\d+(?:[.,]\d+)?)\s+et\s+(\d+(?:[.,]\d+)?)/', $texte, $prix)) {
            $bas = $this->nombre($prix[1]);
            $haut = $this->nombre($prix[2]);
            $prixMin = min($bas, $haut);
            $prixMax = max($bas, $haut);
            $texte = str_replace($prix[0], ' ', $texte);
        }

        if (preg_match('/(?:ma\s*tfou?te?ch|moins\s+de|a\s+moins\s+de|pas\s+plus\s+de|maximum|max|sous)\s+(\d+(?:[.,]\d+)?)/', $texte, $prix)) {
            $prixMax = $this->nombre($prix[1]);
            $texte = str_replace($prix[0], ' ', $texte);
        }

        if (preg_match('/(?:a\s+partir\s+de|plus\s+de|au\s+moins|minimum|min)\s+(\d+(?:[.,]\d+)?)/', $texte, $prix)) {
            $prixMin = $this->nombre($prix[1]);
            $texte = str_replace($prix[0], ' ', $texte);
        }

        $prixBas = (bool) preg_match('/\b(?:pas\s+cher(?:e|es)?|rkhis|r5is|arkhas)\b/', $texte);
        $origin = null;
        $originCle = null;

        foreach (self::VILLES as $cle => $nom) {
            if (str_contains(' '.$texte.' ', ' '.$cle.' ')) {
                $origin = $nom;
                $originCle = $cle;
                $texte = str_replace($cle, ' ', $texte);
                break;
            }
        }

        $famille = null;

        foreach (self::FAMILLES as $cle => $mots) {
            foreach ($mots as $mot) {
                if (preg_match('/\b'.preg_quote($mot, '/').'\b/', $texte)) {
                    $famille = $cle;
                    $texte = preg_replace('/\b'.preg_quote($mot, '/').'\b/', ' ', $texte) ?? $texte;
                    break 2;
                }
            }
        }

        $categoryIds = [];
        $familles = [];

        if ($famille !== null) {
            $categoryIds = $categories
                ->filter(fn (Category $categorie) => str_contains($this->normaliser($categorie->name), $famille))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($categoryIds === []) {
                $familles = self::NOMS_FAMILLE[$famille];
            }
        }

        $stop = [
            'nheb', 'n7eb', 'a3tini', 'aatini', 'je', 'veux', 'voudrais', 'cherche', 'des', 'les', 'une',
            'pour', 'svp', 'stp', 'vous', 'plait', 'men', 'mel', 'min', 'fil', 'dans', 'avec', 'sans',
            'du', 'de', 'la', 'le', 'un', 'et', 'ou', 'en', 'au', 'aux', 'qui', 'que', 'sur', 'par',
            'pas', 'trop', 'tres', 'dt', 'tnd', 'dinar', 'dinars', 'frais', 'fraiche', 'fraiches',
            'locaux', 'local', 'locale', 'tounsi', 'tounsia', 'beldi', 'beldia', 'cher', 'chere',
            'chers', 'cheres', 'rkhis', 'produit', 'produits',
        ];

        $termes = collect(preg_split('/\s+/', trim(preg_replace('/\s+/', ' ', $texte) ?? '')))
            ->filter(fn ($mot) => is_string($mot) && strlen($mot) >= 3 && ! in_array($mot, $stop, true) && ! is_numeric($mot))
            ->map(fn (string $mot) => str_ends_with($mot, 's') && strlen($mot) > 4 ? substr($mot, 0, -1) : $mot)
            ->unique()
            ->values()
            ->all();

        $criteres = [
            'phrase' => $phrase,
            'category_ids' => $categoryIds,
            'origin' => $origin,
            'origin_cle' => $originCle,
            'price_min' => $prixMin,
            'price_max' => $prixMax,
            'prix_bas' => $prixBas,
            'termes' => $termes,
            'familles' => $familles,
            'resume' => '',
        ];
        $criteres['resume'] = $this->decrire($criteres, $categories);

        return $criteres;
    }

    /**
     * @param  array<string, mixed>  $criteres
     * @param  Collection<int, Category>  $categories
     */
    public function decrire(array $criteres, Collection $categories): string
    {
        $morceaux = [];
        $noms = $categories->whereIn('id', $criteres['category_ids'])->pluck('name')->implode(', ');

        if ($noms !== '') {
            $morceaux[] = $noms;
        }

        if ($criteres['origin']) {
            $morceaux[] = 'origine '.$criteres['origin'];
        }

        if ($criteres['price_min'] !== null) {
            $morceaux[] = 'prix minimum '.$this->afficherPrix($criteres['price_min']).' DT';
        }

        if ($criteres['price_max'] !== null) {
            $morceaux[] = 'prix maximum '.$this->afficherPrix($criteres['price_max']).' DT';
        }

        if ($criteres['prix_bas']) {
            $morceaux[] = 'les moins chers d’abord';
        }

        if ($criteres['termes'] !== []) {
            $morceaux[] = 'proche de « '.implode(' ', $criteres['termes']).' »';
        }

        return $morceaux === [] ? '' : 'Recherche comprise : '.implode(' · ', $morceaux);
    }

    /**
     * @param  array<string, mixed>  $criteres
     * @return Collection<int, Product>
     */
    public function resultats(array $criteres): Collection
    {
        if ($this->estVide($criteres)) {
            return Product::query()
                ->with(['category', 'user'])
                ->where('status', Product::PUBLISHED)
                ->latest()
                ->get();
        }

        $produits = Product::query()
            ->with(['category', 'user'])
            ->where('status', Product::PUBLISHED)
            ->when($criteres['category_ids'] !== [], fn ($query) => $query->whereIn('category_id', $criteres['category_ids']))
            ->when($criteres['price_min'] !== null, fn ($query) => $query->where('price', '>=', $criteres['price_min']))
            ->when($criteres['price_max'] !== null, fn ($query) => $query->where('price', '<=', $criteres['price_max']))
            ->get()
            ->filter(function (Product $produit) use ($criteres) {
                if ($criteres['origin_cle'] === null) {
                    return true;
                }

                return str_contains($this->normaliser((string) $produit->origin), (string) $criteres['origin_cle']);
            })
            ->values();

        $parMots = $produits->filter(fn (Product $produit) => $this->correspond($produit, $criteres))->values();
        $semantique = [];

        if ($parMots->isEmpty() && ($criteres['termes'] !== [] || $criteres['familles'] !== [])) {
            $semantique = $this->semantique->scores($produits, (string) $criteres['phrase']);
        }

        $scout = $this->identifiantsScout((string) $criteres['phrase']);
        $idsMots = $parMots->pluck('id');
        $filtreTexte = $criteres['termes'] !== [] || $criteres['familles'] !== [];

        $classe = $produits
            ->filter(function (Product $produit) use ($idsMots, $semantique, $scout, $filtreTexte) {
                if (! $filtreTexte) {
                    return true;
                }

                return $idsMots->contains($produit->id)
                    || in_array($produit->id, $scout, true)
                    || ($semantique[$produit->id] ?? 0) >= self::SEUIL_SEMANTIQUE;
            })
            ->map(function (Product $produit) use ($criteres, $semantique, $scout) {
                $note = $this->note($produit, $criteres);

                if (in_array($produit->id, $scout, true)) {
                    $note += 4;
                }

                $note += ($semantique[$produit->id] ?? 0) * 5;

                return ['produit' => $produit, 'note' => $note];
            })
            ->sort(function (array $a, array $b) use ($criteres) {
                if ($criteres['prix_bas'] && (float) $a['produit']->price !== (float) $b['produit']->price) {
                    return (float) $a['produit']->price <=> (float) $b['produit']->price;
                }

                if ($a['note'] !== $b['note']) {
                    return $b['note'] <=> $a['note'];
                }

                return $b['produit']->created_at <=> $a['produit']->created_at;
            })
            ->pluck('produit')
            ->values();

        return $classe;
    }

    /**
     * @param  array<string, mixed>  $criteres
     */
    public function paginer(array $criteres, int $parPage = 12): LengthAwarePaginator
    {
        if ($this->estVide($criteres)) {
            return Product::query()
                ->with(['category', 'user'])
                ->where('status', Product::PUBLISHED)
                ->latest()
                ->paginate($parPage)
                ->withQueryString();
        }

        $produits = $this->resultats($criteres);
        $page = Paginator::resolveCurrentPage();

        return new Paginator(
            $produits->forPage($page, $parPage)->values(),
            $produits->count(),
            $parPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * @param  array<string, mixed>  $criteres
     */
    private function estVide(array $criteres): bool
    {
        return $criteres['phrase'] === ''
            && $criteres['category_ids'] === []
            && $criteres['origin'] === null
            && $criteres['price_min'] === null
            && $criteres['price_max'] === null
            && $criteres['termes'] === []
            && $criteres['familles'] === [];
    }

    /**
     * @param  array<string, mixed>  $criteres
     */
    private function correspond(Product $produit, array $criteres): bool
    {
        if ($criteres['termes'] === [] && $criteres['familles'] === []) {
            return false;
        }

        $texte = $this->texte($produit);

        foreach ($criteres['termes'] as $terme) {
            if (! $this->termePresent($texte, $terme)) {
                return false;
            }
        }

        if ($criteres['familles'] === []) {
            return true;
        }

        foreach ($criteres['familles'] as $nom) {
            if (str_contains($texte, $nom)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $criteres
     */
    private function note(Product $produit, array $criteres): float
    {
        $texte = $this->texte($produit);
        $nom = $this->normaliser($produit->name);
        $note = 0.0;

        foreach ($criteres['termes'] as $terme) {
            if (str_contains($nom, $terme)) {
                $note += 5;
            } elseif ($this->termePresent($texte, $terme)) {
                $note += 2;
            }
        }

        return $note;
    }

    private function termePresent(string $texte, string $terme): bool
    {
        if (str_contains($texte, $terme)) {
            return true;
        }

        if (strlen($terme) < 4) {
            return false;
        }

        $distanceMax = strlen($terme) >= 6 ? 2 : 1;

        foreach (preg_split('/\s+/', $texte) ?: [] as $mot) {
            if ($mot === '' || abs(strlen($mot) - strlen($terme)) > $distanceMax) {
                continue;
            }

            if (levenshtein($mot, $terme) <= $distanceMax) {
                return true;
            }
        }

        return false;
    }

    private function texte(Product $produit): string
    {
        return $this->normaliser(implode(' ', [
            $produit->name,
            $produit->description,
            $produit->origin,
            $produit->ingredients,
            $produit->category?->name,
        ]));
    }

    /**
     * @return list<int>
     */
    private function identifiantsScout(string $phrase): array
    {
        if (trim($phrase) === '' || config('scout.driver') !== 'meilisearch') {
            return [];
        }

        $disponible = Cache::remember('recherche.meilisearch', 30, function () {
            try {
                $hote = rtrim((string) config('scout.meilisearch.host'), '/');

                return $hote !== '' && Http::timeout(1)->connectTimeout(1)->get($hote.'/health')->successful();
            } catch (Throwable $e) {
                return false;
            }
        });

        if (! $disponible) {
            return [];
        }

        try {
            return Product::search($phrase)
                ->take(50)
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (Throwable $e) {
            Log::warning('Recherche : Meilisearch indisponible, recherche classique conservée.', ['erreur' => $e->getMessage()]);

            return [];
        }
    }

    private function normaliser(string $texte): string
    {
        $texte = Str::ascii(mb_strtolower($texte));
        $texte = preg_replace('/[^a-z0-9.\s]/', ' ', $texte) ?? '';

        return trim(preg_replace('/\s+/', ' ', $texte) ?? '');
    }

    private function nombre(string $valeur): float
    {
        return (float) str_replace(',', '.', $valeur);
    }

    private function afficherPrix(float $prix): string
    {
        $texte = number_format($prix, 3, ',', ' ');

        return str_ends_with($texte, ',000') ? substr($texte, 0, -4) : $texte;
    }
}
