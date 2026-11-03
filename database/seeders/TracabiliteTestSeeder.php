<?php

namespace Database\Seeders;

use App\Models\EtapeTracabilite;
use App\Models\Lot;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\EtapeTracabiliteFactory;
use Illuminate\Database\Seeder;

/**
 * Scenarios complémentaires pour tester les contrôles du module Traçabilité.
 *
 * Ce seeder est séparé de TracabiliteSeeder afin de pouvoir enrichir une base
 * existante sans supprimer ni modifier les lots déjà présents.
 */
class TracabiliteTestSeeder extends Seeder
{
    private array $acteurs = [];

    public function run(): void
    {
        foreach (['producteur', 'transformateur', 'distributeur'] as $type) {
            $this->acteurs[$type] = User::where('email', $type.'@nutritrace.tn')->firstOrFail();
        }

        $fraises = Product::where('name', 'Fraises de Nabeul')
            ->where('user_id', $this->acteurs['producteur']->id)
            ->firstOrFail();
        $confiture = Product::where('name', 'Confiture de fraises')
            ->where('user_id', $this->acteurs['transformateur']->id)
            ->firstOrFail();
        $piments = Product::where('name', 'Piments de Korba')
            ->where('user_id', $this->acteurs['producteur']->id)
            ->firstOrFail();

        $lotOrigine = $this->lot('LOT-TEST-TRACE-001-ORIG', $fraises, [
            'date_production' => now()->subDays(12)->toDateString(),
            'date_peremption' => now()->addDays(8)->toDateString(),
            'quantite' => 180,
            'unite' => 'kg',
            'statut' => 'disponible',
            'notes' => 'Lot de test : matière première destinée à la transformation.',
        ]);
        $lotComplet = $this->lot('LOT-TEST-TRACE-001', $confiture, [
            'lot_origine_id' => $lotOrigine->id,
            'date_production' => now()->subDays(9)->toDateString(),
            'date_peremption' => now()->addDays(180)->toDateString(),
            'quantite' => 420,
            'unite' => 'unités',
            'statut' => 'disponible',
            'notes' => 'Scénario nominal : chaîne complète de la ferme au point de vente.',
        ]);
        $this->etapes($lotOrigine, [
            ['production', 'Nabeul', 0, '07:30', 18, 'Récolte et tri des fraises de test.'],
            ['transport', 'Grombalia', 1, '12:00', 4, 'Transport réfrigéré vers la conserverie.'],
        ]);
        $this->etapes($lotComplet, [
            ['transformation', 'Menzel Bouzelfa', 0, '09:00', 85, 'Cuisson, pasteurisation et mise en pots.'],
            ['transport', 'Tunis', 2, '10:00', 5, 'Transport réfrigéré vers la plateforme.'],
            ['stockage', 'Tunis', 3, '08:00', 4, 'Stockage en chambre froide.'],
            ['distribution', 'Tunis', 5, '09:00', 8, 'Mise en rayon chez le distributeur.'],
        ]);

        $lotRappel = $this->lot('LOT-TEST-TRACE-002', $piments, [
            'date_production' => now()->subDays(6)->toDateString(),
            'date_peremption' => now()->addDays(2)->toDateString(),
            'quantite' => 300,
            'unite' => 'kg',
            'statut' => 'rappele',
            'motif_rappel' => 'Rupture de la chaîne du froid détectée pendant le transport.',
            'notes' => 'Scénario d’alerte : température de transport non conforme.',
        ]);
        $this->etapes($lotRappel, [
            ['production', 'Nabeul', 0, '06:30', 22, 'Récolte des piments rouges de Korba.'],
            ['transport', 'Sfax', 0, '10:00', 14, 'Transport sans maintien de la température recommandée.'],
            ['stockage', 'Sfax', 1, '11:00', 9, 'Stockage temporaire avant contrôle qualité.'],
        ]);

        $lotIncomplet = $this->lot('LOT-TEST-TRACE-003', $fraises, [
            'date_production' => now()->subDays(10)->toDateString(),
            'date_peremption' => now()->addDays(1)->toDateString(),
            'quantite' => 90,
            'unite' => 'kg',
            'statut' => 'en_transit',
            'notes' => 'Scénario incomplet : aucune étape de distribution enregistrée.',
        ]);
        $this->etapes($lotIncomplet, [
            ['production', 'Nabeul', 0, '08:00', 20, 'Récolte enregistrée.'],
            ['transport', 'Bizerte', 2, '11:00', 6, 'Transport vers le centre de stockage.'],
        ]);

        $lotSmart = Lot::where('code', 'SMART-1C3F560BAC')->first();

        if ($lotSmart) {
            $this->etapes($lotSmart, [
                ['production', 'Nabeul', 0, '06:45', 19, 'Récolte des fraises du Cap Bon et contrôle visuel.'],
                ['transport', 'Grombalia', 1, '11:30', 5, 'Transport réfrigéré vers la plateforme logistique.'],
                ['stockage', 'Tunis', 2, '08:15', 4, 'Réception et stockage en chambre froide.'],
                ['distribution', 'Tunis', 3, '09:00', 7, 'Mise en rayon chez le distributeur.'],
            ]);
        }

        $this->command?->info('Traçabilité de test : 3 scénarios créés ou mis à jour.');
    }

    private function lot(string $code, Product $produit, array $attributes): Lot
    {
        return Lot::updateOrCreate(
            ['code' => $code],
            array_merge([
                'produit_id' => $produit->id,
                'lot_origine_id' => null,
            ], $attributes)
        );
    }

    /**
     * @param list<array{0: string, 1: string, 2: int, 3: string, 4: float, 5: string}> $definitions
     */
    private function etapes(Lot $lot, array $definitions): void
    {
        $production = CarbonImmutable::parse($lot->date_production);

        foreach ($definitions as [$type, $lieu, $jours, $heure, $temperature, $description]) {
            $acteur = in_array($type, ['production', 'transformation'], true)
                ? ($type === 'transformation' ? 'transformateur' : 'producteur')
                : 'distributeur';
            $date = $production->addDays($jours)->setTimeFromTimeString($heure);
            $coordonnees = EtapeTracabiliteFactory::LIEUX[$lieu];

            EtapeTracabilite::updateOrCreate(
                ['lot_id' => $lot->id, 'type_etape' => $type],
                [
                    'user_id' => $this->acteurs[$acteur]->id,
                    'titre' => EtapeTracabilite::TYPES[$type]['label'].' - scénario de test',
                    'lieu' => $lieu,
                    'latitude' => $coordonnees[0],
                    'longitude' => $coordonnees[1],
                    'date_etape' => $date,
                    'temperature' => $temperature,
                    'description' => $description,
                ]
            );
        }
    }
}
