<?php

namespace Database\Seeders;

use App\Models\AlerteQualite;
use App\Models\Avis;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration pour les réclamations et alertes qualité.
 */
class ReclamationsAlertesDemoSeeder extends Seeder
{
    private const RECLAMATIONS = [
        [
            'sujet' => 'Produit reçu avec un problème de fraîcheur',
            'description' => 'Le produit est arrivé avec une fraîcheur inférieure à celle attendue. Merci de vérifier le stockage et le transport.',
            'categorie' => 'qualite',
            'priorite' => 'haute',
            'sentiment' => 'negatif',
            'priorite_suggeree' => 'haute',
            'statut' => 'en_cours',
        ],
        [
            'sujet' => 'Emballage et livraison à améliorer',
            'description' => 'Le colis était légèrement abîmé à la réception et la livraison a pris plus de temps que prévu.',
            'categorie' => 'livraison',
            'priorite' => 'moyenne',
            'sentiment' => 'negatif',
            'priorite_suggeree' => 'moyenne',
            'statut' => 'en_attente',
        ],
    ];

    public function run(): void
    {
        $clients = User::whereIn('email', [
            'amel.avis@nutritrace.tn',
            'youssef.avis@nutritrace.tn',
        ])->get()->keyBy('email');

        if ($clients->count() !== 2) {
            $this->call(AvisDemoSeeder::class);
            $clients = User::whereIn('email', [
                'amel.avis@nutritrace.tn',
                'youssef.avis@nutritrace.tn',
            ])->get()->keyBy('email');
        }

        foreach (Product::query()->get() as $produit) {
            $avisNegatifs = Avis::where('produit_id', $produit->id)
                ->where('note', '<=', 2)
                ->orderBy('id')
                ->get();
            $lotId = $produit->lots()->orderBy('id')->value('id');
            $reclamations = collect();

            foreach (self::RECLAMATIONS as $index => $definition) {
                $client = $clients->values()->get($index);
                $avis = $avisNegatifs->firstWhere('user_id', $client?->id);
                $reclamation = Reclamation::updateOrCreate(
                    [
                        'user_id' => $client->id,
                        'produit_id' => $produit->id,
                        'sujet' => $definition['sujet'],
                    ],
                    array_merge($definition, [
                        'avis_id' => $avis?->id,
                        'lot_id' => $lotId,
                        'source' => $avis ? 'avis' : 'formulaire',
                        'langue' => 'fr',
                        'suggestion_reponse' => $index === 0
                            ? 'Contrôler la chaîne du froid et proposer un remplacement après vérification.'
                            : 'Vérifier le conditionnement et informer le client du suivi de livraison.',
                        'resume_ia' => 'Réclamation de démonstration analysée localement pour tester le suivi qualité.',
                    ])
                );
                $reclamations->push($reclamation);
            }

            $signaux = [
                'reclamations' => $reclamations->pluck('id')->values()->all(),
                'avis' => $avisNegatifs->pluck('id')->values()->all(),
            ];
            $signature = md5(json_encode($signaux));

            AlerteQualite::updateOrCreate(
                ['produit_id' => $produit->id, 'titre' => 'Signal qualité - fraîcheur'],
                [
                    'lot_id' => $lotId,
                    'nb_signaux' => count($signaux['reclamations']) + count($signaux['avis']),
                    'signature' => $signature,
                    'signaux' => $signaux,
                    'resume' => 'Plusieurs retours signalent une fraîcheur ou une conservation à contrôler.',
                    'gravite' => 'elevee',
                    'recommandation' => 'Contrôler le lot, la température de transport et les conditions de stockage.',
                    'rappel_conseille' => false,
                    'motif_rappel' => null,
                    'analyse_ia' => false,
                    'statut' => 'ouverte',
                ]
            );

            AlerteQualite::updateOrCreate(
                ['produit_id' => $produit->id, 'titre' => 'Suivi qualité - livraison'],
                [
                    'lot_id' => $lotId,
                    'nb_signaux' => count($signaux['reclamations']),
                    'signature' => md5($signature.'-livraison'),
                    'signaux' => ['reclamations' => $reclamations->pluck('id')->take(1)->values()->all(), 'avis' => []],
                    'resume' => 'Des retours clients nécessitent un suivi du conditionnement ou de la livraison.',
                    'gravite' => 'moyenne',
                    'recommandation' => 'Vérifier l’emballage et le délai de livraison avant les prochains envois.',
                    'rappel_conseille' => false,
                    'motif_rappel' => null,
                    'analyse_ia' => false,
                    'statut' => 'ouverte',
                ]
            );
        }

        $this->command?->info(sprintf(
            'Données qualité : %d réclamations et %d alertes pour %d produits.',
            Reclamation::count(),
            AlerteQualite::count(),
            Product::count()
        ));
    }
}
