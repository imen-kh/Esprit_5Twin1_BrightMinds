<?php

namespace Database\Seeders;

use App\Models\AlerteQualite;
use App\Models\Avis;
use App\Models\AvisReaction;
use App\Models\Category;
use App\Models\Commande;
use App\Models\HistoriqueReclamation;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Reclamation;
use App\Models\User;
use App\Notifications\ReclamationNotification;
use App\Services\AiReclamationService;
use App\Services\AlerteQualiteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Données de démonstration du module Réclamations & Avis, couvrant tous les cas d'usage :
 * statuts, délais respectés ou dépassés, sources (formulaire, Sahbi, avis converti), langues,
 * satisfaction, avis vérifiés / modérés / masqués, droit de réponse, alertes qualité (ouverte,
 * classée, rappel déclenché) et notifications.
 *
 * Aucun appel à DeepSeek ici : les analyses sont écrites à la main pour un résultat stable et rapide.
 * Comptes de démo : mot de passe « password ».
 */
class ReclamationSeeder extends Seeder
{
    /** @var array<string, User> */
    private array $u = [];

    /** @var array<string, Product> */
    private array $p = [];

    public function run(): void
    {
        if (Avis::query()->exists()) {
            $this->command?->warn('Réclamations & Avis : données déjà présentes, rien n\'est ajouté (php artisan migrate:fresh --seed pour repartir de zéro).');

            return;
        }

        $this->comptes();
        $this->produits();
        $this->commandes();
        $this->avis();
        $this->reclamations();
        $this->alertes();
        $this->notifications();

        $this->command?->info(sprintf(
            'Réclamations & Avis : %d réclamations, %d avis, %d alertes qualité.',
            Reclamation::count(), Avis::count(), AlerteQualite::count()
        ));
    }

    // ---------------------------------------------------------------- Comptes

    private function comptes(): void
    {
        $existants = [
            'admin' => 'admin@nutritrace.tn',
            'producteur' => 'producteur@nutritrace.tn',
            'transformateur' => 'transformateur@nutritrace.tn',
            'distributeur' => 'distributeur@nutritrace.tn',
            'client' => 'client@nutritrace.tn',
            'sami' => 'consommateur@nutritrace.tn',
        ];

        foreach ($existants as $cle => $email) {
            $this->u[$cle] = User::query()->where('email', $email)->first() ?? $this->compte($email, ucfirst($cle), $cle === 'admin' ? 'admin' : ($cle === 'client' || $cle === 'sami' ? 'consommateur' : 'acteur'), in_array($cle, ['producteur', 'transformateur', 'distributeur'], true) ? $cle : null);
        }

        // Clients supplémentaires, pour des avis et réclamations variés
        foreach ([
            'amira' => ['Amira Haddad', 'Ariana'],
            'youssef' => ['Youssef Gharbi', 'Sfax'],
            'leila' => ['Leila Mansour', 'Bizerte'],
            'karim' => ['Karim Jlassi', 'Monastir'],
            'nour' => ['Nour Ben Amor', 'Nabeul'],
        ] as $cle => [$nom, $ville]) {
            $this->u[$cle] = $this->compte($cle.'@nutritrace.tn', $nom, 'consommateur', null, $ville);
        }
    }

    private function compte(string $email, string $nom, string $role, ?string $type, string $ville = 'Tunis'): User
    {
        $user = User::query()->updateOrCreate(['email' => $email], [
            'name' => $nom, 'role' => $role, 'type_acteur' => $type, 'password' => 'password', 'ville' => $ville, 'telephone' => '22'.random_int(100000, 999999),
        ]);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    // ---------------------------------------------------------------- Produits et commandes

    private function produits(): void
    {
        $noms = [
            'oranges' => 'Oranges', 'citrons' => 'Citrons', 'fraises' => 'Fraises', 'dattes' => 'Dattes',
            'tomates' => 'Tomates', 'piments' => 'Piments', 'harissa' => 'Harissa', 'huile' => 'Huile',
        ];

        $categorie = Category::query()->first() ?? Category::query()->create(['name' => 'Produits locaux', 'description' => 'Démo', 'status' => Category::VALIDATED]);

        foreach ($noms as $cle => $motif) {
            $this->p[$cle] = Product::query()->where('status', Product::PUBLISHED)->where('name', 'like', $motif.'%')->first()
                ?? Product::query()->create([
                    'name' => $motif.' de démonstration', 'description' => 'Produit de démonstration du module Avis.',
                    'price' => 5, 'status' => Product::PUBLISHED, 'category_id' => $categorie->id, 'user_id' => $this->u['producteur']->id,
                ]);
        }
    }

    private function commandes(): void
    {
        // [client, jours écoulés, statut, produits] — livrées : achat vérifié, Sahbi demande un avis
        $scenarios = [
            ['client', 6, 'livree', ['oranges', 'fraises', 'dattes']],
            ['client', 2, 'en_transit', ['huile']],
            ['sami', 8, 'livree', ['tomates', 'harissa', 'fraises']],
            ['amira', 10, 'livree', ['fraises', 'oranges', 'citrons']],
            ['youssef', 12, 'livree', ['huile', 'dattes', 'piments']],
            ['leila', 5, 'livree', ['fraises', 'piments', 'tomates']],
            ['karim', 7, 'livree', ['harissa', 'oranges', 'fraises']],
            ['nour', 4, 'livree', ['citrons', 'tomates', 'huile']],
        ];

        foreach ($scenarios as $i => [$cle, $jours, $statut, $produits]) {
            $client = $this->u[$cle];
            $lignes = collect($produits)->mapWithKeys(fn ($p) => [$this->p[$p]->id => [
                'quantite' => 2, 'prix_unitaire' => $this->p[$p]->price, 'sous_total' => 2 * (float) $this->p[$p]->price,
            ]]);

            $commande = Commande::query()->create([
                'user_id' => $client->id,
                'distributeur_id' => $this->u['distributeur']->id,
                'nom' => explode(' ', $client->name)[1] ?? 'Client', 'prenom' => explode(' ', $client->name)[0],
                'email' => $client->email, 'telephone' => '22123456', 'adresse' => '12 avenue Habib Bourguiba',
                'ville' => $client->ville ?: 'Tunis', 'code_postal' => '1000',
                'commentaire' => 'Commande démo Réclamations & Avis n°'.($i + 1),
                'total' => $lignes->sum('sous_total') + 7, 'statut' => $statut, 'date_commande' => now()->subDays($jours),
            ]);
            $commande->products()->attach($lignes->all());
        }
    }

    private function commandeDe(string $client, string $produit): ?Commande
    {
        return $this->u[$client]->commandes()->whereHas('products', fn ($q) => $q->whereKey($this->p[$produit]->id))->latest('id')->first();
    }

    private function lot(string $produit, int $joursProduction): Lot
    {
        return Lot::query()->create([
            'code' => Lot::prochainCode(),
            'produit_id' => $this->p[$produit]->id,
            'date_production' => now()->subDays($joursProduction),
            'date_peremption' => now()->addDays(20),
            'quantite' => 400,
            'unite' => 'kg',
            'statut' => 'disponible',
            'notes' => 'Lot de démonstration du module Réclamations & Avis.',
        ]);
    }

    // ---------------------------------------------------------------- Avis

    private function avis(): void
    {
        // [client, produit, note, titre, commentaire, jours, aspects, statut, raison, réponse du producteur]
        $liste = [
            ['client', 'oranges', 5, 'Juteuses à souhait', 'Des oranges très juteuses et sucrées, parfaites pour le jus du matin. Livraison rapide.', 5, ['gout' => 5, 'fraicheur' => 5, 'livraison' => 5], 'publie', null, 'Merci beaucoup ! Elles ont été cueillies la veille de l\'expédition.'],
            ['amira', 'oranges', 4, 'Très bonnes', 'Bon goût et bien fraîches. Quelques fruits un peu petits mais rien de grave.', 9, ['gout' => 4, 'fraicheur' => 4, 'prix' => 4], 'publie', null, null],
            ['karim', 'oranges', 5, null, 'Excellent rapport qualité/prix, je recommande à toute la famille.', 6, ['prix' => 5, 'gout' => 5], 'publie', null, null],
            ['amira', 'citrons', 5, 'Parfumés', 'Citrons beldi très parfumés, idéaux pour la citronnade.', 8, ['gout' => 5, 'fraicheur' => 5], 'publie', null, null],
            ['nour', 'citrons', 3, 'Corrects', 'Bon parfum mais l\'emballage était un peu écrasé à la livraison.', 3, ['gout' => 4, 'emballage' => 2], 'publie', null, 'Désolés pour l\'emballage, nous passons à des cagettes rigides.'],
            ['sami', 'tomates', 4, 'Goût d\'antan', 'Des tomates qui ont vraiment du goût, rien à voir avec le supermarché.', 7, ['gout' => 5, 'fraicheur' => 4], 'publie', null, null],
            ['leila', 'tomates', 2, 'Trop mûres', 'Plusieurs tomates étaient trop mûres et molles à l\'arrivée.', 4, ['fraicheur' => 2, 'livraison' => 3], 'publie', null, null],
            ['nour', 'tomates', 4, null, 'Fraîches et savoureuses, livraison soignée.', 3, ['fraicheur' => 4, 'livraison' => 4], 'publie', null, null],
            ['youssef', 'huile', 5, 'Une huile exceptionnelle', 'Huile très fruitée, légèrement piquante. On sent la qualité de Sfax.', 11, ['gout' => 5, 'prix' => 4], 'publie', null, 'Merci Youssef, c\'est la récolte précoce de cette année.'],
            ['nour', 'huile', 4, null, 'Très bonne huile, un peu chère mais elle le vaut.', 3, ['gout' => 5, 'prix' => 3], 'publie', null, null],
            ['youssef', 'dattes', 5, 'Fondantes', 'Deglet Nour souples et sucrées, magnifiques.', 10, ['gout' => 5, 'fraicheur' => 5], 'publie', null, null],
            ['sami', 'harissa', 4, 'Bien relevée', 'Harissa artisanale bien relevée, parfaite avec le pain tabouna.', 6, ['gout' => 4], 'publie', null, null],
            ['karim', 'harissa', 3, null, 'Bon goût mais le bocal fuyait légèrement dans le colis.', 6, ['gout' => 4, 'emballage' => 2], 'publie', null, null],
            ['youssef', 'piments', 4, null, 'Piments bien rouges, parfaits pour ma harissa maison.', 10, ['fraicheur' => 4], 'publie', null, null],
            // Alerte qualité : fraises moisies sur la même semaine
            ['amira', 'fraises', 1, 'Fraises moisies', 'La moitié des fraises étaient moisies à l\'ouverture de la barquette. Très déçue.', 2, ['fraicheur' => 1], 'publie', null, null],
            ['karim', 'fraises', 2, 'Pas fraîches', 'Fraises molles avec des traces de moisissure, odeur bizarre.', 3, ['fraicheur' => 1, 'gout' => 2], 'publie', null, null],
            ['sami', 'fraises', 4, 'Bonnes', 'Fraises sucrées, quelques-unes abîmées mais globalement bien.', 7, ['gout' => 4, 'fraicheur' => 3], 'publie', null, null],
            // Modération : retenus par l'IA, signalé, masqué
            ['leila', 'piments', 1, null, 'Vendeur escroc !!! Appelez-moi au 22 333 444 pour en parler.', 1, [], 'en_moderation', 'Propos injurieux et numéro de téléphone', null],
            ['nour', 'dattes', 5, 'Super promo', 'Les mêmes dattes sont moins chères sur www.autre-boutique.tn, foncez !', 2, [], 'en_moderation', 'Contient un lien', null],
            ['karim', 'citrons', 1, null, 'Nul nul nul nul.', 5, [], 'masque', 'Masqué par l\'administrateur : avis sans contenu utile', null],
        ];

        foreach ($liste as [$cle, $produit, $note, $titre, $commentaire, $jours, $aspects, $statut, $raison, $reponse]) {
            $date = now()->subDays($jours)->setTime(random_int(8, 21), random_int(0, 59));
            $avis = new Avis([
                'user_id' => $this->u[$cle]->id,
                'produit_id' => $this->p[$produit]->id,
                'note' => $note,
                'titre' => $titre,
                'commentaire' => $commentaire,
                'statut' => $statut,
                'achat_verifie' => Avis::estAchatVerifie($this->u[$cle], $this->p[$produit]),
                'sentiment' => $note >= 4 ? 'positif' : ($note <= 2 ? 'negatif' : 'neutre'),
                'aspects' => $aspects,
                'moderation_raison' => $raison,
                'probleme_detecte' => $note <= 2 && $statut === 'publie',
                'reponse_producteur' => $reponse,
                'reponse_producteur_at' => $reponse ? $date->copy()->addDay() : null,
            ]);
            $avis->created_at = $date;
            $avis->updated_at = $date;
            $avis->save();
        }

        $this->avisEnVolume();

        // Votes « utile » et signalements
        $votants = collect($this->u)->only(['client', 'sami', 'amira', 'youssef', 'leila', 'karim', 'nour']);
        Avis::query()->where('statut', 'publie')->get()->each(function (Avis $avis) use ($votants) {
            $votants->reject(fn (User $u) => $u->id === $avis->user_id)->shuffle()->take($avis->note >= 4 ? random_int(1, 4) : random_int(0, 2))
                ->each(fn (User $u) => AvisReaction::query()->create(['avis_id' => $avis->id, 'user_id' => $u->id, 'type' => 'utile']));
        });

        $signale = Avis::query()->where('statut', 'en_moderation')->first();
        foreach (['amira', 'youssef'] as $cle) {
            AvisReaction::query()->create(['avis_id' => $signale->id, 'user_id' => $this->u[$cle]->id, 'type' => 'signalement', 'motif' => 'insulte']);
        }

        Avis::query()->get()->each(fn (Avis $a) => $a->forceFill([
            'utile_count' => $a->reactions()->where('type', 'utile')->count(),
            'signalements_count' => $a->reactions()->where('type', 'signalement')->count(),
        ])->saveQuietly());
    }

    /**
     * Volume : 12 clients supplémentaires et 3 à 6 avis par produit, pour des moyennes,
     * répartitions d'étoiles et notes par aspect réalistes.
     */
    private function avisEnVolume(): void
    {
        $prenoms = ['Ines', 'Mehdi', 'Rania', 'Walid', 'Salma', 'Hichem', 'Olfa', 'Bilel', 'Meriem', 'Aymen', 'Sarra', 'Fares'];
        $noms = ['Bouazizi', 'Chaabane', 'Dridi', 'Ferchichi', 'Hamdi', 'Kefi', 'Laabidi', 'Marzouki', 'Nasri', 'Riahi', 'Sassi', 'Zouari'];
        $clients = collect($prenoms)->map(fn ($prenom, $i) => $this->compte(mb_strtolower($prenom).'.'.mb_strtolower($noms[$i]).'@demo.nutritrace.tn', $prenom.' '.$noms[$i], 'consommateur', null));

        // [note, titre, commentaire, aspects]
        $modeles = [
            [5, 'Excellent', 'Qualité irréprochable, très frais et plein de goût. Je recommande sans hésiter.', ['gout' => 5, 'fraicheur' => 5]],
            [5, 'Parfait', 'Produit conforme à la description, livraison rapide et emballage soigné.', ['livraison' => 5, 'emballage' => 5]],
            [5, null, 'On sent le produit local et de saison, rien à voir avec la grande surface.', ['gout' => 5]],
            [4, 'Très bien', 'Très bon produit, juste un peu cher par rapport au marché.', ['gout' => 4, 'prix' => 3]],
            [4, null, 'Bonne fraîcheur, quelques pièces un peu abîmées mais rien de grave.', ['fraicheur' => 4]],
            [4, 'Je recommande', 'Bon rapport qualité/prix et livreur aimable.', ['prix' => 4, 'livraison' => 5]],
            [3, 'Moyen', 'Goût correct mais la livraison a pris un jour de plus que prévu.', ['gout' => 3, 'livraison' => 2]],
            [3, null, 'Correct sans plus, l\'emballage pourrait être plus solide.', ['emballage' => 2]],
        ];

        foreach (['oranges', 'citrons', 'dattes', 'tomates', 'harissa', 'huile', 'piments'] as $index => $produit) {
            $nombre = 3 + ($index % 4);
            $choisis = $clients->slice($index, $nombre)->merge($clients->take(max(0, $index + $nombre - $clients->count())));

            foreach ($choisis->values() as $rang => $client) {
                [$note, $titre, $commentaire, $aspects] = $modeles[($index * 3 + $rang) % count($modeles)];
                $date = now()->subDays(12 + $rang * 4 + $index)->setTime(random_int(8, 21), random_int(0, 59));

                $avis = new Avis([
                    'user_id' => $client->id, 'produit_id' => $this->p[$produit]->id, 'note' => $note, 'titre' => $titre,
                    'commentaire' => $commentaire, 'statut' => 'publie', 'achat_verifie' => $rang % 3 !== 2,
                    'sentiment' => $note >= 4 ? 'positif' : 'neutre', 'aspects' => $aspects, 'probleme_detecte' => false,
                ]);
                $avis->created_at = $date;
                $avis->updated_at = $date;
                $avis->save();
            }
        }
    }

    // ---------------------------------------------------------------- Réclamations

    private function reclamations(): void
    {
        $lotFraises = $this->lot('fraises', 9);
        $lotPiments = $this->lot('piments', 25);
        $avisFraises = Avis::query()->where('produit_id', $this->p['fraises']->id)->where('user_id', $this->u['amira']->id)->first();

        $scenarios = [
            // 1. Fraîche, en attente, livraison
            ['cle' => 'sami', 'produit' => 'tomates', 'heures' => 5, 'statut' => 'en_attente', 'priorite' => 'haute', 'categorie' => 'livraison', 'langue' => 'fr', 'sentiment' => 'negatif',
                'sujet' => 'Livraison incomplète', 'description' => 'J\'ai reçu ma commande mais le sachet de tomates (2 kg) est manquant. Pouvez-vous l\'envoyer ou me rembourser ?',
                'resume' => 'Sachet de tomates de 2 kg manquant dans la commande livrée.'],
            // 2. En retard sur son délai (démo relance + KPI)
            ['cle' => 'client', 'produit' => 'dattes', 'heures' => 96, 'statut' => 'en_attente', 'priorite' => 'moyenne', 'categorie' => 'emballage', 'langue' => 'fr', 'sentiment' => 'negatif',
                'sujet' => 'Boîte de dattes ouverte', 'description' => 'La boîte de dattes est arrivée ouverte, le film de protection était déchiré.',
                'resume' => 'Boîte de dattes reçue ouverte, film déchiré.'],
            // 3. En cours, échange admin + producteur, avec lot
            ['cle' => 'amira', 'produit' => 'oranges', 'heures' => 30, 'statut' => 'en_cours', 'priorite' => 'moyenne', 'categorie' => 'qualite', 'langue' => 'fr', 'sentiment' => 'negatif',
                'sujet' => 'Oranges sèches à l\'intérieur', 'description' => 'Sur le filet de 3 kg, cinq oranges étaient sèches à l\'intérieur, sans jus.',
                'resume' => 'Cinq oranges sèches sur un filet de 3 kg.',
                'reponses' => [['admin', 6, 'Bonjour Amira, nous sommes désolés. Pouvez-vous nous envoyer une photo des oranges concernées ?'], ['amira', 8, 'Voici, j\'ai gardé les oranges. Elles sont légères et sans jus.'], ['producteur', 20, 'Bonjour, ces oranges viennent d\'une parcelle touchée par le sirocco. Nous vous en renvoyons un filet.']]],
            // 4. Résolue dans les délais, satisfaction 5
            ['cle' => 'youssef', 'produit' => 'huile', 'heures' => 260, 'statut' => 'resolue', 'priorite' => 'haute', 'categorie' => 'emballage', 'langue' => 'fr', 'sentiment' => 'negatif',
                'sujet' => 'Bouteille d\'huile fissurée', 'description' => 'La bouteille d\'huile d\'olive était fissurée, une partie a coulé dans le carton.',
                'resume' => 'Bouteille d\'huile fissurée, fuite dans le colis.', 'resolue_h' => 20, 'satisfaction' => [5, 'Remplacement reçu en deux jours, merci !'],
                'reponses' => [['admin', 3, 'Bonjour Youssef, nous vous envoyons une nouvelle bouteille dès aujourd\'hui, sans frais.'], ['youssef', 19, 'Bien reçue, parfait.']]],
            // 5. Résolue hors délai, satisfaction 2
            ['cle' => 'leila', 'produit' => 'piments', 'heures' => 200, 'statut' => 'resolue', 'priorite' => 'urgente', 'categorie' => 'qualite', 'langue' => 'fr', 'sentiment' => 'negatif',
                'sujet' => 'Piments pourris', 'description' => 'Tous les piments étaient pourris, inutilisables. Je demande un remboursement.',
                'resume' => 'Piments entièrement pourris, demande de remboursement.', 'resolue_h' => 70, 'satisfaction' => [2, 'Remboursée, mais après presque trois jours.'],
                'reponses' => [['admin', 65, 'Bonjour Leila, toutes nos excuses pour ce délai. Le remboursement est effectué.']]],
            // 6. Rejetée
            ['cle' => 'karim', 'produit' => 'harissa', 'heures' => 150, 'statut' => 'rejetee', 'priorite' => 'faible', 'categorie' => 'information', 'langue' => 'fr', 'sentiment' => 'neutre',
                'sujet' => 'Harissa trop piquante', 'description' => 'La harissa est beaucoup trop piquante à mon goût, je voudrais un échange.',
                'resume' => 'Harissa jugée trop piquante, demande d\'échange.', 'resolue_h' => 30, 'satisfaction' => [3, null],
                'reponses' => [['admin', 28, 'Bonjour Karim, le niveau de piquant est indiqué sur la fiche. Nous ne pouvons pas échanger un produit conforme, mais voici un bon de 10 % pour votre prochaine commande.']]],
            // 7. Urgente, sécurité alimentaire, « Qui est responsable ? » renseigné
            ['cle' => 'leila', 'produit' => 'fraises', 'heures' => 20, 'statut' => 'en_cours', 'priorite' => 'urgente', 'categorie' => 'securite_alimentaire', 'langue' => 'fr', 'sentiment' => 'negatif', 'lot' => $lotFraises,
                'sujet' => 'Maux de ventre après les fraises', 'description' => 'Mon fils a eu mal au ventre après avoir mangé les fraises, certaines avaient de la moisissure blanche.',
                'resume' => 'Troubles digestifs d\'un enfant après des fraises moisies.',
                'responsable' => ['distributeur', 'Le lot a passé 30 heures en entrepôt à 11 °C avant la livraison : la rupture de la chaîne du froid explique la moisissure.'],
                'reponses' => [['admin', 2, 'Bonjour Leila, par précaution ne consommez plus ces fraises et gardez la barquette. Nous analysons le lot en priorité.']]],
            // 8. Créée avec l'assistant Sahbi
            ['cle' => 'client', 'produit' => 'fraises', 'heures' => 26, 'statut' => 'en_attente', 'priorite' => 'haute', 'categorie' => 'qualite', 'langue' => 'fr', 'sentiment' => 'negatif', 'source' => 'assistant', 'lot' => $lotFraises,
                'sujet' => 'Fraises abîmées et moisies', 'description' => 'Barquette de fraises reçue avec plusieurs fruits moisis et écrasés au fond.',
                'resume' => 'Fraises moisies et écrasées au fond de la barquette.'],
            // 9. Ouverte depuis un avis négatif
            ['cle' => 'amira', 'produit' => 'fraises', 'heures' => 46, 'statut' => 'en_attente', 'priorite' => 'haute', 'categorie' => 'qualite', 'langue' => 'fr', 'sentiment' => 'negatif', 'source' => 'avis', 'avis' => $avisFraises, 'lot' => $lotFraises,
                'sujet' => 'Fraises moisies', 'description' => 'La moitié des fraises étaient moisies à l\'ouverture de la barquette. Très déçue.',
                'resume' => 'Moitié des fraises moisies à l\'ouverture.'],
            // 10. En arabe
            ['cle' => 'karim', 'produit' => 'oranges', 'heures' => 14, 'statut' => 'en_attente', 'priorite' => 'moyenne', 'categorie' => 'quantite', 'langue' => 'ar', 'sentiment' => 'negatif',
                'sujet' => 'نقص في الوزن', 'description' => 'طلبت 3 كيلو برتقال ولكن الكيس كان يزن 2 كيلو فقط.',
                'resume' => 'Filet d\'oranges de 2 kg au lieu des 3 kg commandés.'],
            // 11. En derja tunisienne
            ['cle' => 'youssef', 'produit' => 'dattes', 'heures' => 40, 'statut' => 'en_cours', 'priorite' => 'faible', 'categorie' => 'qualite', 'langue' => 'tn', 'sentiment' => 'negatif',
                'sujet' => 'Dgla mouch behya', 'description' => 'El dgla jet chwaya yebsa w fama barcha mouch behya. Mouch kima el marra elli fetet, brabi chouf el mochkla.',
                'resume' => 'Dattes un peu sèches, qualité moindre que la commande précédente.',
                'reponses' => [['admin', 10, 'Asslema Youssef, sorry 3al mochkla. Bech nab3thoulek kilo jdid mel récolte el jdida.']]],
            // 12. En anglais
            ['cle' => 'sami', 'produit' => 'harissa', 'heures' => 60, 'statut' => 'en_cours', 'priorite' => 'moyenne', 'categorie' => 'emballage', 'langue' => 'en', 'sentiment' => 'negatif',
                'sujet' => 'Leaking jar', 'description' => 'The harissa jar was leaking when the order arrived, the lid was not sealed properly.',
                'resume' => 'Pot de harissa qui fuyait, couvercle mal scellé.',
                'reponses' => [['admin', 12, 'Hello Sami, we are sorry about the leaking jar. A new one is on its way.']]],
            // 13. Question d'information, résolue vite
            ['cle' => 'nour', 'produit' => 'citrons', 'heures' => 90, 'statut' => 'resolue', 'priorite' => 'faible', 'categorie' => 'information', 'langue' => 'fr', 'sentiment' => 'neutre',
                'sujet' => 'Citrons traités ?', 'description' => 'Les citrons beldi sont-ils traités après récolte ? Je veux utiliser le zeste.',
                'resume' => 'Question sur un éventuel traitement après récolte.', 'resolue_h' => 4, 'satisfaction' => [5, 'Réponse claire et rapide.'],
                'reponses' => [['producteur', 2, 'Bonjour Nour, nos citrons ne sont pas traités après récolte : vous pouvez utiliser le zeste sans crainte.']]],
            // 14 et 15. Piments : lot rappelé suite à une alerte
            ['cle' => 'youssef', 'produit' => 'piments', 'heures' => 120, 'statut' => 'en_cours', 'priorite' => 'urgente', 'categorie' => 'securite_alimentaire', 'langue' => 'fr', 'sentiment' => 'negatif', 'lot' => $lotPiments,
                'sujet' => 'Corps étranger dans les piments', 'description' => 'J\'ai trouvé un morceau de plastique dur dans le sachet de piments.',
                'resume' => 'Morceau de plastique trouvé dans un sachet de piments.'],
            ['cle' => 'leila', 'produit' => 'piments', 'heures' => 110, 'statut' => 'en_cours', 'priorite' => 'urgente', 'categorie' => 'securite_alimentaire', 'langue' => 'fr', 'sentiment' => 'negatif', 'lot' => $lotPiments,
                'sujet' => 'Plastique dans le sachet', 'description' => 'Des éclats de plastique bleu étaient mélangés aux piments.',
                'resume' => 'Éclats de plastique bleu mélangés aux piments.'],
            ['cle' => 'nour', 'produit' => 'piments', 'heures' => 100, 'statut' => 'en_attente', 'priorite' => 'haute', 'categorie' => 'securite_alimentaire', 'langue' => 'fr', 'sentiment' => 'negatif', 'lot' => $lotPiments,
                'sujet' => 'Sachet de piments contaminé', 'description' => 'Un fragment de plastique était dans mes piments, heureusement vu avant la cuisson.',
                'resume' => 'Fragment de plastique repéré dans les piments avant cuisson.'],
        ];

        foreach ($scenarios as $s) {
            $this->creerReclamation($s);
        }
    }

    /**
     * @param  array<string, mixed>  $s
     */
    private function creerReclamation(array $s): Reclamation
    {
        $client = $this->u[$s['cle']];
        $cree = now()->subHours($s['heures']);
        $source = $s['source'] ?? 'formulaire';

        $reclamation = new Reclamation([
            'user_id' => $client->id,
            'produit_id' => $this->p[$s['produit']]->id,
            'commande_id' => $this->commandeDe($s['cle'], $s['produit'])?->id,
            'lot_id' => isset($s['lot']) ? $s['lot']->id : null,
            'avis_id' => isset($s['avis']) ? $s['avis']->id : null,
            'source' => $source,
            'sujet' => $s['sujet'],
            'description' => $s['description'],
            'statut' => $s['statut'],
            'priorite' => $s['priorite'],
            'categorie' => $s['categorie'],
            'langue' => $s['langue'],
            'sentiment' => $s['sentiment'],
            'priorite_suggeree' => $s['priorite'],
            'resume_ia' => $s['resume'],
            'suggestion_reponse' => app(AiReclamationService::class)->analyseLocale($s['sujet'], $s['description'])['suggestion'],
            'responsable_suggere' => $s['responsable'][0] ?? null,
            'responsable_explication' => $s['responsable'][1] ?? null,
            'echeance_at' => Reclamation::echeancePour($s['priorite'], $cree),
            'resolue_at' => isset($s['resolue_h']) ? $cree->copy()->addHours($s['resolue_h']) : null,
            'satisfaction_note' => $s['satisfaction'][0] ?? null,
            'satisfaction_commentaire' => $s['satisfaction'][1] ?? null,
        ]);
        $reclamation->created_at = $cree;
        $reclamation->updated_at = isset($s['resolue_h']) ? $cree->copy()->addHours($s['resolue_h']) : $cree;
        $reclamation->save();

        // Chronologie
        $this->evenement($reclamation, $cree, 'creation', match ($source) {
            'assistant' => 'Réclamation créée avec l\'assistant Sahbi',
            'avis' => 'Réclamation ouverte à partir d\'un avis 1★',
            default => 'Réclamation déposée',
        }, $client);
        $this->evenement($reclamation, $cree->copy()->addSeconds(5), 'ia', 'Analyse DeepSeek : '.$reclamation->categorieLibelle().', sentiment '.$s['sentiment']);

        foreach ($s['reponses'] ?? [] as $i => [$auteur, $apresHeures, $message]) {
            $date = $cree->copy()->addHours($apresHeures);
            $user = $this->u[$auteur];
            $reponse = $reclamation->reponses()->create(['user_id' => $user->id, 'message' => $message]);
            $reponse->forceFill(['created_at' => $date, 'updated_at' => $date])->saveQuietly();
            $role = $user->isAdmin() ? 'Service client' : ($user->id === $client->id ? 'Client' : 'Producteur');
            $this->evenement($reclamation, $date, 'reponse', $role.' : nouveau message', $user);

            if ($i === 0 && $user->id !== $client->id) {
                $this->evenement($reclamation, $date->copy()->addSecond(), 'statut', 'Statut passé à « En cours »', $user);
            }
        }

        if (isset($s['responsable'])) {
            $this->evenement($reclamation, $cree->copy()->addHours(3), 'responsable', 'Maillon probable : '.$reclamation->responsableLibelle().' (confiance 78 %)', $this->u['admin']);
        }
        if ($reclamation->resolue_at) {
            $this->evenement($reclamation, $reclamation->resolue_at, 'statut', 'Statut passé à « '.$reclamation->statutLibelle().' »', $this->u['admin']);
        }
        if ($reclamation->satisfaction_note) {
            $this->evenement($reclamation, $reclamation->resolue_at->copy()->addHours(2), 'satisfaction', 'Traitement évalué '.$reclamation->satisfaction_note.'/5 par le client', $client);
        }

        return $reclamation;
    }

    private function evenement(Reclamation $reclamation, Carbon $date, string $type, string $libelle, ?User $user = null): void
    {
        $evenement = new HistoriqueReclamation(['reclamation_id' => $reclamation->id, 'type' => $type, 'libelle' => $libelle, 'user_id' => $user?->id]);
        $evenement->created_at = $date;
        $evenement->updated_at = $date;
        $evenement->save();
    }

    // ---------------------------------------------------------------- Alertes qualité

    private function alertes(): void
    {
        $service = app(AlerteQualiteService::class);
        $alertes = $service->detecter();

        // Fraises : alerte ouverte, analysée par l'IA (texte rédigé pour la démo)
        $fraises = $alertes->firstWhere('produit_id', $this->p['fraises']->id);
        $fraises?->update([
            'titre' => 'Moisissure sur les fraises du lot '.$fraises->lot?->code,
            'resume' => 'Quatre clients décrivent des fraises moisies ou molles reçues ces trois derniers jours, dont un enfant malade. Les signalements visent le même lot.',
            'gravite' => 'elevee',
            'recommandation' => 'Bloquer le lot, vérifier la chaîne du froid chez le distributeur et rappeler le lot si la moisissure est confirmée.',
            'rappel_conseille' => true,
            'motif_rappel' => 'Présence de moisissures signalée par plusieurs clients : ne consommez pas les fraises de ce lot.',
            'analyse_ia' => true,
        ]);

        // Piments : rappel déjà déclenché (le lot apparaît « Rappelé » sur sa page de traçabilité)
        $piments = $alertes->firstWhere('produit_id', $this->p['piments']->id);
        if ($piments && $piments->lot) {
            $piments->update([
                'titre' => 'Corps étrangers en plastique dans les piments',
                'resume' => 'Trois clients ont trouvé des fragments de plastique bleu dans des sachets du même lot de piments.',
                'gravite' => 'elevee',
                'recommandation' => 'Rappel immédiat du lot et contrôle de la ligne de conditionnement.',
                'rappel_conseille' => true,
                'analyse_ia' => true,
            ]);
            $service->declencherRappel($piments, $piments->lot, 'Fragments de plastique retrouvés dans plusieurs sachets : ne consommez pas ces piments.', $this->u['admin']);
            $piments->forceFill(['traitee_at' => now()->subHours(90)])->saveQuietly();
        }

        // Tomates : ancienne alerte classée sans suite (historique)
        AlerteQualite::query()->create([
            'produit_id' => $this->p['tomates']->id,
            'nb_signaux' => 3,
            'signature' => md5('demo-tomates'),
            'signaux' => ['reclamations' => [], 'avis' => []],
            'titre' => 'Tomates trop mûres à la livraison',
            'resume' => 'Trois clients ont reçu des tomates très mûres pendant la vague de chaleur de septembre.',
            'gravite' => 'faible',
            'recommandation' => 'Pas de risque sanitaire : demander au distributeur de livrer plus tôt dans la journée.',
            'rappel_conseille' => false,
            'analyse_ia' => true,
            'statut' => 'classee',
            'traitee_par' => $this->u['admin']->id,
            'traitee_at' => now()->subDays(30),
        ]);
    }

    // ---------------------------------------------------------------- Notifications

    private function notifications(): void
    {
        // Notifications en base seulement : aucun email n'est envoyé par le seeder
        $avecReponse = Reclamation::query()->whereHas('reponses', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('role', '!=', 'consommateur')))->get();

        foreach ($avecReponse as $reclamation) {
            $reclamation->user->notifyNow(new ReclamationNotification($reclamation, 'reponse'), ['database']);
        }

        foreach (Reclamation::query()->where('statut', 'en_attente')->get() as $reclamation) {
            $this->u['producteur']->notifyNow(new ReclamationNotification($reclamation, 'nouvelle'), ['database']);
        }
    }
}
