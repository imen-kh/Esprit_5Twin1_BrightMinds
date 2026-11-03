<?php

namespace Database\Seeders;

use App\Models\Avis;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Avis de démonstration pour tester le catalogue, la modération et les alertes qualité.
 */
class AvisDemoSeeder extends Seeder
{
    private const CLIENTS = [
        ['name' => 'Amel Ben Salah', 'email' => 'amel.avis@nutritrace.tn'],
        ['name' => 'Youssef Trabelsi', 'email' => 'youssef.avis@nutritrace.tn'],
        ['name' => 'Sarra Gharbi', 'email' => 'sarra.avis@nutritrace.tn'],
        ['name' => 'Mehdi Khelifi', 'email' => 'mehdi.avis@nutritrace.tn'],
    ];

    private const AVIS = [
        [
            'note' => 5,
            'titre' => 'Très bon produit',
            'commentaire' => 'Produit frais, savoureux et conforme à la description. Je le recommande.',
            'sentiment' => 'positif',
            'aspects' => ['gout' => 5, 'fraicheur' => 5, 'emballage' => 4, 'prix' => 4, 'livraison' => 5],
            'probleme_detecte' => false,
        ],
        [
            'note' => 4,
            'titre' => 'Bonne qualité',
            'commentaire' => 'Bonne qualité générale et emballage correct. Le rapport qualité-prix est satisfaisant.',
            'sentiment' => 'positif',
            'aspects' => ['gout' => 4, 'fraicheur' => 4, 'emballage' => 4, 'prix' => 4, 'livraison' => 4],
            'probleme_detecte' => false,
        ],
        [
            'note' => 3,
            'titre' => 'Correct mais perfectible',
            'commentaire' => 'Le produit est correct, mais la livraison pourrait être plus rapide et mieux suivie.',
            'sentiment' => 'neutre',
            'aspects' => ['gout' => 3, 'fraicheur' => 3, 'emballage' => 3, 'prix' => 3, 'livraison' => 2],
            'probleme_detecte' => false,
        ],
        [
            'note' => 2,
            'titre' => 'Problème de fraîcheur',
            'commentaire' => 'La fraîcheur n’était pas satisfaisante à la réception. Merci de vérifier le transport.',
            'sentiment' => 'negatif',
            'aspects' => ['gout' => 3, 'fraicheur' => 2, 'emballage' => 3, 'prix' => 2, 'livraison' => 2],
            'probleme_detecte' => true,
        ],
    ];

    public function run(): void
    {
        $clients = collect(self::CLIENTS)->mapWithKeys(function (array $definition): array {
            $client = User::firstOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'role' => 'consommateur',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if ($client->role !== 'consommateur') {
                $client->update(['role' => 'consommateur', 'type_acteur' => null]);
            }

            return [$definition['email'] => $client];
        });

        $produits = Product::query()->get();

        foreach ($produits as $produit) {
            foreach (self::AVIS as $index => $definition) {
                $client = $clients[self::CLIENTS[$index]['email']];

                Avis::updateOrCreate(
                    ['user_id' => $client->id, 'produit_id' => $produit->id],
                    array_merge($definition, [
                        'statut' => 'publie',
                        'achat_verifie' => true,
                        'utile_count' => $definition['note'] >= 4 ? 3 : 1,
                        'signalements_count' => 0,
                    ])
                );
            }
        }

        $this->command?->info(sprintf(
            'Avis de démonstration : %d avis pour %d produits.',
            Avis::whereIn('user_id', $clients->pluck('id'))->count(),
            $produits->count()
        ));
    }
}
