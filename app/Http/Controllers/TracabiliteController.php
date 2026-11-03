<?php

namespace App\Http\Controllers;

use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Services\TracabiliteIAService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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
                ->whereIn('statut', ['disponible', 'en_transit', 'rappele'])
                ->has('etapes')
                ->withCount('etapes')
                ->orderByDesc('etapes_count')
                ->orderByDesc('date_production')
                ->take(6)
                ->get(),
        ]);
    }

    /**
     * Fiche publique d'un lot, trouvé par son code : chaîne complète, carte, statistiques, QR code,
     * histoire rédigée par IA, score de transparence et alerte de chaîne du froid.
     */
    public function show(Lot $lot, TracabiliteIAService $ia): View
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
            ...$this->donneesFiche($lot, $chaine),
            // Histoire en cache, régénérée seulement si la chaîne a changé ; null si l'IA est indisponible
            'histoire' => $chaine->isNotEmpty() ? $ia->histoire($lot) : null,
            'segments' => $segments,
            'points' => $points,
            'image' => $lot->produit->imageUrl(),
            // QR code vers une adresse accessible depuis le téléphone (pas « localhost »)
            'qrCode' => (string) QrCode::size(190)->margin(1)->generate($this->urlPourQr($lot)),
            'iaConfiguree' => $ia->estConfigure(),
        ]);
    }

    /**
     * Fiche de traçabilité en PDF (dompdf). Sans le paquet barryvdh/laravel-dompdf,
     * la même fiche s'ouvre en version imprimable (Ctrl+P > Enregistrer en PDF).
     */
    public function pdf(Lot $lot): Response|View
    {
        $lot->load(['produit.category', 'produit.user', 'lotOrigine.produit.user']);
        $chaine = $lot->chaineComplete();

        $donnees = [
            ...$this->donneesFiche($lot, $chaine),
            // L'histoire n'est reprise que si elle est déjà en cache : le PDF n'attend pas l'API
            'histoire' => $lot->histoireAJour() ? $lot->histoire_ia : null,
            // dompdf affiche le SVG via une image en base64
            'qrCode' => base64_encode((string) QrCode::format('svg')->size(130)->margin(0)->generate($this->urlPourQr($lot))),
            'genereLe' => now()->format('d/m/Y à H:i'),
        ];

        if (! class_exists(Pdf::class)) {
            return view('front.tracabilite.pdf', [...$donnees, 'impression' => true]);
        }

        return Pdf::loadView('front.tracabilite.pdf', [...$donnees, 'impression' => false])
            ->setPaper('a4')
            ->download("fiche-tracabilite-{$lot->code}.pdf");
    }

    /**
     * Assistant traçabilité : répond en JSON à la question d'un consommateur, uniquement à partir
     * des données du lot. Route limitée à 10 questions par minute (throttle:10,1).
     */
    public function assistant(Request $request, Lot $lot, TracabiliteIAService $ia): JsonResponse
    {
        $donnees = $request->validate(
            ['question' => ['required', 'string', 'max:300']],
            [
                'question.required' => 'Posez votre question.',
                'question.max' => 'Votre question ne peut pas dépasser 300 caractères.',
            ]
        );

        $reponse = $ia->repondreQuestion($lot, trim($donnees['question']));

        if ($reponse === null) {
            return response()->json([
                'reponse' => 'L\'assistant est momentanément indisponible. Les informations du lot restent consultables sur cette page.',
                'ia' => false,
            ], 503);
        }

        return response()->json(['reponse' => $reponse, 'ia' => true]);
    }

    /**
     * Données communes à la page publique et au PDF : chaîne, statistiques, contrôles et alertes.
     *
     * @param  Collection<int, EtapeTracabilite>  $chaine
     * @return array<string, mixed>
     */
    private function donneesFiche(Lot $lot, Collection $chaine): array
    {
        $anomalies = $lot->anomalies($chaine);

        return [
            'lot' => $lot,
            'chaine' => $chaine,
            'stats' => $lot->statistiquesChaine($chaine),
            'anomalies' => $anomalies,
            'score' => $lot->scoreTransparence($chaine, $anomalies),
            'alertesFroid' => array_values(array_filter($anomalies, fn (array $a) => $a['type'] === 'temperature')),
            'alertePeremption' => $lot->alertePeremption(),
        ];
    }

    private function urlPourQr(Lot $lot): string
    {
        $base = trim((string) config('app.qr_public_url'));

        if ($base === '') {
            $base = request()->getSchemeAndHttpHost();
        }

        return rtrim($base, '/').route('tracabilite.show', $lot, false);
    }
}
