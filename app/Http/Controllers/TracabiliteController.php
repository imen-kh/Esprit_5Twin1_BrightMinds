<?php

namespace App\Http\Controllers;

use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Pages publiques de traçabilité (front office, sans connexion) : le consommateur
 * suit un lot « de la ferme à l'assiette » en saisissant son code ou en scannant son QR code.
 */
class TracabiliteController extends Controller
{
    /**
     * Accueil de la traçabilité : recherche d'un lot par son code (?code=…) et lots récents.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $code = strtoupper(trim((string) $request->query('code')));
        $introuvable = false;

        if ($code !== '') {
            $lot = Lot::where('code', $code)->first();

            if ($lot) {
                return redirect()->route('tracabilite.show', $lot);
            }

            $introuvable = true;
        }

        return view('front.tracabilite.index', [
            'code' => $code,
            'introuvable' => $introuvable,
            'lotsRecents' => Lot::with('produit.category')
                ->where('statut', 'disponible')
                ->withCount('etapes')
                ->orderByDesc('date_production')
                ->take(6)
                ->get(),
        ]);
    }

    /**
     * Fiche publique d'un lot, trouvé par son code : chaîne complète, carte, statistiques et QR code.
     */
    public function show(Lot $lot): View
    {
        $lot->load(['produit.category', 'produit.user', 'lotOrigine.produit.user']);

        // Chaîne complète : étapes des lots d'origine puis du lot courant, triées par date
        $chaine = $lot->chaineComplete();

        // Un bloc par lot de la lignée : « Matière première » puis « Produit transformé »
        $lignee = $lot->lignee();
        $segments = $lignee->map(fn (Lot $maillon) => [
            'lot' => $maillon,
            'titre' => $lignee->count() === 1
                ? 'Parcours du lot'
                : ($maillon->is($lot) ? 'Produit transformé' : 'Matière première'),
            'etapes' => $chaine->where('lot_id', $maillon->id)->values(),
        ]);

        // Points de la carte, dans l'ordre chronologique (étapes géolocalisées uniquement)
        $points = $chaine
            ->filter(fn (EtapeTracabilite $e) => $e->latitude !== null && $e->longitude !== null)
            ->values()
            ->map(fn (EtapeTracabilite $e, int $rang) => [
                'rang' => $rang + 1,
                'lat' => (float) $e->latitude,
                'lng' => (float) $e->longitude,
                'titre' => $e->titre,
                'type' => $e->type['label'],
                'couleur' => $e->type['couleur'],
                'lieu' => $e->lieu,
                'date' => $e->date_etape->format('d/m/Y H:i'),
                'acteur' => $e->acteur?->nom_entreprise ?: $e->acteur?->name,
                'lot' => $e->lot->code,
            ]);

        return view('front.tracabilite.show', [
            'lot' => $lot,
            'chaine' => $chaine,
            'segments' => $segments,
            'points' => $points,
            'stats' => $lot->statistiquesChaine($chaine),
            'image' => $this->imagePour($lot->produit),
            // QR code en SVG pointant vers cette page
            'qrCode' => (string) QrCode::size(190)->margin(1)->generate(route('tracabilite.show', $lot)),
        ]);
    }

    /**
     * Image du produit : celle enregistrée dans le Catalogue si elle existe, sinon une photo
     * du template choisie selon le nom du produit, sinon une image générique.
     */
    private function imagePour(Product $produit): string
    {
        if ($produit->image) {
            return asset('storage/'.$produit->image);
        }

        $nom = mb_strtolower($produit->name);

        $images = [
            'fraise' => 'fruite-item-2.jpg',
            'confiture' => 'fruite-item-2.jpg',
            'orange' => 'fruite-item-1.jpg',
            'banane' => 'fruite-item-3.jpg',
            'abricot' => 'fruite-item-4.jpg',
            'raisin' => 'fruite-item-5.jpg',
            'pomme de terre' => 'vegetable-item-5.jpg',
            'pomme' => 'fruite-item-6.jpg',
            'harissa' => 'vegetable-item-4.jpg',
            'piment' => 'vegetable-item-4.jpg',
            'poivron' => 'vegetable-item-4.jpg',
        ];

        foreach ($images as $motCle => $fichier) {
            if (str_contains($nom, $motCle)) {
                return asset('front/img/'.$fichier);
            }
        }

        return asset('front/img/banner-fruits.jpg');
    }
}
