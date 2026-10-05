<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Données de démo du module Traçabilité : des lots et leurs étapes, de la ferme à l'assiette.
 * À lancer après les comptes de démo (DatabaseSeeder) et le Catalogue.
 */
class TracabiliteSeeder extends Seeder
{
    /** Compteur des codes de lot (LOT-AAAA-NNNN). */
    private int $numero = 0;

    /** Comptes acteurs de démo, indexés par type d'acteur. */
    private array $acteurs = [];

    public function run(): void
    {
        // Les codes de lot sont uniques : on ne ressème pas une base qui contient déjà des lots
        if (Lot::query()->exists()) {
            $this->command?->warn('TracabiliteSeeder ignoré : des lots existent déjà.');

            return;
        }

        foreach (['producteur', 'transformateur', 'distributeur'] as $type) {
            $this->acteurs[$type] = User::where('email', $type.'@nutritrace.tn')->first();

            if (! $this->acteurs[$type]) {
                $this->command?->warn("TracabiliteSeeder ignoré : le compte {$type}@nutritrace.tn est introuvable.");

                return;
            }
        }

        $produits = $this->produitsDeDemo();

        // ===== Chaîne fraises -> confiture : le lot transformé référence son lot de matière première =====
        $lotFraises = $this->creerLot($produits['Fraises de Nabeul'], 24, [
            ['production', 'Nabeul', 0, 7, 'Récolte manuelle des fraises', 'Cueillette à maturité et tri à la ferme Ben Salah.'],
            ['transport', 'Grombalia', 0, 14, 'Transport réfrigéré vers la conserverie', 'Camion frigorifique, chaîne du froid contrôlée.'],
            ['stockage', 'Menzel Bouzelfa', 1, 9, 'Réception et stockage en chambre froide', 'Contrôle qualité à la réception du lot.'],
        ], ['quantite' => 800, 'unite' => 'kg', 'statut' => 'disponible', 'peremption' => 12]);

        $this->creerLot($produits['Confiture de fraises'], 20, [
            ['transformation', 'Menzel Bouzelfa', 0, 8, 'Cuisson et mise en pots de la confiture', 'Cuisson au chaudron, 60 % de fruits, pasteurisation des pots.'],
            ['transport', 'Tunis', 3, 6, 'Transport vers la plateforme logistique', null],
            ['stockage', 'Sousse', 4, 10, 'Stockage à l\'entrepôt du distributeur', null],
            ['distribution', 'Sousse', 6, 9, 'Mise en rayon à l\'Épicerie Bio Sousse', 'Lot disponible à la vente.'],
        ], ['quantite' => 1200, 'unite' => 'unités', 'statut' => 'disponible', 'peremption' => 365, 'origine' => $lotFraises,
            'notes' => 'Confiture préparée à partir du lot de fraises '.$lotFraises->code.'.']);

        // ===== Lot rappelé : alerte visible sur la page publique =====
        $this->creerLot($produits['Harissa du Cap Bon'], 40, [
            ['production', 'Nabeul', 0, 7, 'Récolte des piments rouges', 'Piments séchés au soleil avant livraison.'],
            ['transformation', 'Menzel Bouzelfa', 1, 8, 'Broyage et conditionnement de la harissa', null],
            ['transport', 'Tunis', 4, 6, 'Transport vers la plateforme logistique', null],
            ['stockage', 'Tunis', 5, 11, 'Stockage en entrepôt', 'Lot bloqué à la suite du rappel.'],
        ], ['quantite' => 600, 'unite' => 'unités', 'peremption' => 300, 'rappele' => true]);

        // ===== Pour chaque produit : 1 à 2 lots, avec 3 à 5 étapes chronologiques =====
        foreach (Product::with('user')->withCount('lots')->get() as $produit) {
            $objectif = random_int(1, 2);

            for ($i = $produit->lots_count; $i < $objectif; $i++) {
                $this->creerLotGenerique($produit);
            }
        }

        $this->command?->info(sprintf(
            'Traçabilité : %d lots et %d étapes créés.',
            Lot::count(),
            EtapeTracabilite::count()
        ));
    }

    /**
     * Le Catalogue n'a pas encore de seeder : on s'assure que les produits nécessaires à la
     * démo existent (producteur = produits bruts, transformateur = produits transformés).
     * firstOrCreate : rien n'est créé en double ni modifié si le produit existe déjà.
     *
     * @return array<string, \App\Models\Product>
     */
    private function produitsDeDemo(): array
    {
        $categories = [];

        foreach ([
            'Fruits' => 'Fruits frais de saison.',
            'Légumes' => 'Légumes frais cultivés en Tunisie.',
            'Produits transformés' => 'Conserves, confitures et préparations artisanales.',
        ] as $nom => $description) {
            $categories[$nom] = Category::firstOrCreate(['name' => $nom], ['description' => $description]);
        }

        $definitions = [
            ['Fraises de Nabeul', 'Fruits', 'producteur', 6.500, 'Nabeul', null,
                'Fraises cultivées en plein champ au Cap Bon, récoltées à la main.'],
            ['Oranges maltaises', 'Fruits', 'producteur', 2.800, 'Cap Bon', null,
                'Oranges maltaises demi-sanguines, récoltées à maturité.'],
            ['Tomates de Béja', 'Légumes', 'producteur', 1.900, 'Béja', null,
                'Tomates de plein champ, cultivées sans pesticides de synthèse.'],
            ['Confiture de fraises', 'Produits transformés', 'transformateur', 7.900, 'Menzel Bouzelfa',
                'Fraises (60 %), sucre de canne, jus de citron, pectine de fruits.',
                'Confiture artisanale cuite au chaudron.'],
            ['Harissa du Cap Bon', 'Produits transformés', 'transformateur', 4.500, 'Menzel Bouzelfa',
                'Piments rouges séchés, ail, huile d\'olive, sel, carvi, coriandre.',
                'Harissa traditionnelle préparée à partir de piments du Cap Bon.'],
        ];

        $produits = [];

        foreach ($definitions as [$nom, $categorie, $type, $prix, $origine, $ingredients, $description]) {
            $produits[$nom] = Product::firstOrCreate(
                ['name' => $nom, 'user_id' => $this->acteurs[$type]->id],
                [
                    'category_id' => $categories[$categorie]->id,
                    'description' => $description,
                    'price' => $prix,
                    'origin' => $origine,
                    'ingredients' => $ingredients,
                    'status' => 'published',
                    // Module Commandes : le transformateur vend des produits transformés
                    'type_produit' => $type === 'transformateur' ? 'transforme' : 'matiere_premiere',
                ]
            );
        }

        return $produits;
    }

    /**
     * Crée un lot produit il y a $ilYAJours jours, avec ses étapes.
     *
     * @param  list<array{0: string, 1: string, 2: int, 3: int, 4: ?string, 5: ?string}>  $etapes
     *                                                                                             [type, ville, jours après la production, heure, titre, description]
     * @param  array<string, mixed>  $options  quantite, unite, statut, peremption (jours), origine (Lot), rappele, notes
     */
    private function creerLot(Product $produit, int $ilYAJours, array $etapes, array $options = []): Lot
    {
        $production = CarbonImmutable::today()->subDays($ilYAJours);

        $factory = Lot::factory();

        if (! empty($options['rappele'])) {
            $factory = $factory->rappele();
        }

        if (! empty($options['origine'])) {
            $factory = $factory->issuDe($options['origine']);
        }

        $attributs = [
            'code' => sprintf('LOT-%s-%04d', $production->format('Y'), ++$this->numero),
            'produit_id' => $produit->id,
            'date_production' => $production->toDateString(),
            'date_peremption' => $production->addDays($options['peremption'] ?? 30)->toDateString(),
            'quantite' => $options['quantite'] ?? 500,
            'unite' => $options['unite'] ?? 'kg',
        ];

        // Un lot rappelé garde le statut et la note fixés par la factory
        if (empty($options['rappele'])) {
            $attributs['statut'] = $options['statut'] ?? $this->statutSelonDerniereEtape(end($etapes)[0]);
            $attributs['notes'] = $options['notes'] ?? null;
        }

        $lot = $factory->create($attributs);

        foreach ($etapes as [$type, $ville, $joursApres, $heure, $titre, $description]) {
            $attributsEtape = [
                'lot_id' => $lot->id,
                'user_id' => $this->acteurPour($type)->id,
                'date_etape' => $production->addDays($joursApres)->setTime($heure, random_int(0, 59)),
                'description' => $description,
            ];

            if ($titre) {
                $attributsEtape['titre'] = $titre;
            }

            EtapeTracabilite::factory()->type($type)->a($ville)->create($attributsEtape);
        }

        return $lot;
    }

    /**
     * Lot générique : la chaîne dépend du propriétaire du produit (produit brut ou transformé).
     */
    private function creerLotGenerique(Product $produit): Lot
    {
        $transforme = $produit->user?->type_acteur === 'transformateur';

        $chaine = $transforme
            ? ['production', 'transformation', 'transport', 'stockage', 'distribution']
            : ['production', 'transport', 'stockage', 'transport', 'distribution'];

        $nombre = random_int(3, 5);

        // Produit brut à 3 ou 4 étapes : production, transport, stockage (puis distribution)
        if (! $transforme && $nombre < 5) {
            $chaine = ['production', 'transport', 'stockage', 'distribution'];
        }

        $chaine = array_slice($chaine, 0, $nombre);

        $fermes = ['Nabeul', 'Béja', 'Bizerte', 'Sfax', 'Grombalia'];
        $entrepots = ['Tunis', 'Sousse', 'Sfax'];
        $ferme = $fermes[array_rand($fermes)];
        $entrepot = $entrepots[array_rand($entrepots)];

        $villes = [
            'production' => $ferme,
            'transformation' => 'Menzel Bouzelfa',
            'transport' => 'Grombalia',
            'stockage' => $entrepot,
            'distribution' => $entrepot,
        ];

        $etapes = [];
        $jour = 0;

        foreach ($chaine as $rang => $type) {
            // Chaque étape a lieu 1 à 2 jours après la précédente : la chronologie est garantie
            $jour += $rang === 0 ? 0 : random_int(1, 2);
            // Le second transport (livraison finale) passe par Tunis
            $ville = ($type === 'transport' && $rang > 1) ? 'Tunis' : $villes[$type];
            $etapes[] = [$type, $ville, $jour, random_int(6, 16), null, null];
        }

        return $this->creerLot($produit, random_int(15, 60), $etapes, [
            'quantite' => random_int(2, 30) * 50,
            'unite' => $transforme ? 'unités' : (random_int(0, 1) ? 'kg' : 'caisses'),
            'peremption' => $transforme ? random_int(180, 365) : random_int(10, 25),
        ]);
    }

    /**
     * Compte de démo qui réalise un type d'étape :
     * production -> producteur, transformation -> transformateur, le reste -> distributeur.
     */
    private function acteurPour(string $type): User
    {
        return match ($type) {
            'production' => $this->acteurs['producteur'],
            'transformation' => $this->acteurs['transformateur'],
            default => $this->acteurs['distributeur'],
        };
    }

    /**
     * Statut cohérent avec la dernière étape enregistrée.
     */
    private function statutSelonDerniereEtape(string $type): string
    {
        return match ($type) {
            'production', 'transformation' => 'en_production',
            'transport' => 'en_transit',
            default => 'disponible',
        };
    }
}
