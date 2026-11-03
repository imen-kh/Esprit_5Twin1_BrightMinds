<?php

namespace App\Services;

use App\Models\Avis;
use App\Models\Product;
use App\Models\User;

/**
 * Données de la section « Avis » d'une fiche produit : moyenne, répartition des étoiles,
 * synthèse IA, liste triée, et ce que l'utilisateur connecté peut faire.
 */
class AvisProduitService
{
    public const TRIS = [
        'recents' => 'Plus récents',
        'utiles' => 'Plus utiles',
        'meilleurs' => 'Meilleures notes',
        'critiques' => 'Plus critiques',
    ];

    public function __construct(private AvisIAService $ia) {}

    /**
     * @return array<string, mixed>
     */
    public function section(Product $produit, ?User $user, string $tri = 'recents'): array
    {
        $tri = array_key_exists($tri, self::TRIS) ? $tri : 'recents';
        $publies = $produit->avis()->publies();

        $repartition = (clone $publies)->selectRaw('note, count(*) as total')->groupBy('note')->pluck('total', 'note');
        $total = (int) $repartition->sum();

        $liste = (clone $publies)->with('user:id,name')
            ->when($tri === 'utiles', fn ($q) => $q->orderByDesc('utile_count'))
            ->when($tri === 'meilleurs', fn ($q) => $q->orderByDesc('note'))
            ->when($tri === 'critiques', fn ($q) => $q->orderBy('note'))
            ->latest()
            ->paginate(6, ['*'], 'page_avis')
            ->withQueryString()
            ->fragment('avis');

        $monAvis = $user ? Avis::query()->where('user_id', $user->id)->where('produit_id', $produit->id)->first() : null;
        $mesReactions = $user
            ? \App\Models\AvisReaction::query()->where('user_id', $user->id)->whereIn('avis_id', $liste->pluck('id'))->get()->groupBy('avis_id')->map->pluck('type')
            : collect();

        return [
            'moyenne' => $total ? round($repartition->reduce(fn ($somme, $nombre, $note) => $somme + $note * $nombre, 0) / $total, 1) : null,
            'total' => $total,
            'repartition' => collect(range(5, 1))->mapWithKeys(fn ($n) => [$n => (int) ($repartition[$n] ?? 0)]),
            'synthese' => $this->ia->synthese($produit),
            'liste' => $liste,
            'tri' => $tri,
            'monAvis' => $monAvis,
            'mesReactions' => $mesReactions,
            'peutPublier' => $user?->can('create', [Avis::class, $produit]) ?? false,
            'achatVerifie' => $user ? Avis::estAchatVerifie($user, $produit) : false,
        ];
    }
}
