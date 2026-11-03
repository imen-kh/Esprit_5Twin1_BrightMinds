<?php

namespace App\Services;

use App\Models\EtapeTracabilite;
use App\Models\Product;
use App\Models\Reclamation;

/**
 * Intelligence du module Réclamations, via DeepSeek :
 *  - analyse d'une réclamation (sentiment, catégorie, priorité, langue, résumé) ;
 *  - copilote de réponse de l'administrateur (ton au choix, solution proposée, langue du client) ;
 *  - « Qui est responsable ? » : maillon de la chaîne probablement en cause, d'après le lot et ses étapes.
 *
 * Chaque méthode a un repli sur des règles locales : sans clé ou sans réseau, le module reste complet.
 */
class AiReclamationService
{
    public const TONS = [
        'empathique' => 'Empathique',
        'formel' => 'Formel',
        'bref' => 'Bref',
    ];

    public const LANGUES = [
        'fr' => 'Français',
        'ar' => 'Arabe',
        'en' => 'Anglais',
        'tn' => 'Derja tunisienne',
    ];

    public function __construct(private DeepSeekService $deepseek) {}

    /**
     * @return array{sentiment: string, priorite: string, suggestion: string, categorie: string, langue: string, resume: string, source: string}
     */
    public function analyserReclamation(string $sujet, string $description, ?Product $produit = null): array
    {
        $local = $this->analyseLocale($sujet, $description);

        $systeme = <<<'PROMPT'
        Tu es l'analyste du service client de NutriTrace, plateforme tunisienne de produits alimentaires locaux tracés.
        Analyse la réclamation d'un client. Elle peut être écrite en français, arabe, anglais ou derja tunisienne.
        Réponds uniquement par un objet JSON :
        {
          "sentiment": "negatif" | "neutre" | "positif",
          "priorite": "faible" | "moyenne" | "haute" | "urgente",
          "categorie": "qualite" | "securite_alimentaire" | "livraison" | "emballage" | "quantite" | "information" | "autre",
          "langue": "fr" | "ar" | "en" | "tn",
          "resume": "une phrase en français, 160 caractères maximum",
          "suggestion": "premier message de réponse au client, dans SA langue, 2 à 3 phrases, poli et concret"
        }
        Règles de priorité : "urgente" seulement si la santé est en jeu (intoxication, allergie, corps étranger, produit toxique).
        "haute" pour un produit inutilisable, manquant, ou une demande de remboursement. N'invente aucun fait.
        PROMPT;

        $contexte = 'Produit : '.($produit?->name ?? 'non précisé')."\nSujet : {$sujet}\nDescription : {$description}";
        $ia = $this->deepseek->json($systeme, $contexte);

        if (! $ia) {
            return $local;
        }

        return [
            'sentiment' => $this->valeur($ia['sentiment'] ?? null, ['negatif', 'neutre', 'positif'], $local['sentiment']),
            'priorite' => $this->valeur($ia['priorite'] ?? null, Reclamation::PRIORITES, $local['priorite']),
            'categorie' => $this->valeur($ia['categorie'] ?? null, array_keys(Reclamation::CATEGORIES), $local['categorie']),
            'langue' => $this->valeur($ia['langue'] ?? null, array_keys(self::LANGUES), $local['langue']),
            'resume' => $this->texte($ia['resume'] ?? null, 255) ?? $local['resume'],
            'suggestion' => $this->texte($ia['suggestion'] ?? null, 2000) ?? $local['suggestion'],
            'source' => 'ia',
        ];
    }

    /**
     * Brouillon de réponse pour l'administrateur. Il le relit et le modifie avant l'envoi.
     *
     * @return array{message: string, solution: string, langue: string, source: string}
     */
    public function redigerReponse(Reclamation $reclamation, string $ton = 'empathique'): array
    {
        $ton = array_key_exists($ton, self::TONS) ? $ton : 'empathique';
        $reclamation->loadMissing(['produit', 'commande', 'reponses.user', 'user']);
        $langue = $reclamation->langue ?: 'fr';

        $echanges = $reclamation->reponses
            ->map(fn ($reponse) => ($reponse->user?->isAdmin() ? 'Service client' : 'Client').' : '.$reponse->message)
            ->implode("\n");

        $montant = $reclamation->commande ? number_format((float) $reclamation->commande->total, 3, ',', ' ').' DT' : 'inconnu';

        $systeme = <<<PROMPT
        Tu rédiges, pour l'équipe du service client de NutriTrace, la réponse à une réclamation.
        Ton demandé : {$ton}. Langue de la réponse : celle du client (code « {$langue} » ; "tn" = derja tunisienne en alphabet latin).
        Propose UNE solution adaptée parmi : remplacement du produit, remboursement partiel, remboursement total,
        bon de réduction, simple explication, demande d'informations complémentaires (photo, numéro de lot).
        Pour un risque sanitaire, demande de ne plus consommer le produit et de conserver l'emballage.
        Ne promets jamais un délai ou un montant précis. Signe « L'équipe NutriTrace ».
        Réponds uniquement par un objet JSON : {"message": "la réponse complète", "solution": "la solution choisie, en français, 6 mots maximum"}
        PROMPT;

        $contexte = implode("\n", [
            'Référence : '.$reclamation->reference,
            'Client : '.($reclamation->user?->name ?? 'client'),
            'Produit : '.($reclamation->produit?->name ?? 'non précisé'),
            'Montant de la commande : '.$montant,
            'Catégorie : '.$reclamation->categorieLibelle().' — priorité '.$reclamation->prioriteLibelle(),
            'Sujet : '.$reclamation->sujet,
            'Description : '.$reclamation->description,
            $echanges !== '' ? "Échanges précédents :\n".$echanges : 'Aucun échange précédent.',
        ]);

        $ia = $this->deepseek->json($systeme, $contexte, 0.6);
        $message = $this->texte($ia['message'] ?? null, 4000);

        if ($message) {
            return [
                'message' => $message,
                'solution' => $this->texte($ia['solution'] ?? null, 80) ?? 'Réponse personnalisée',
                'langue' => $langue,
                'source' => 'ia',
            ];
        }

        return $this->reponseLocale($reclamation, $ton);
    }

    /**
     * Maillon de la chaîne probablement en cause, d'après la réclamation et la traçabilité du lot.
     *
     * @return array{responsable: string, explication: string, confiance: int, source: string}
     */
    public function analyserResponsabilite(Reclamation $reclamation): array
    {
        $reclamation->loadMissing(['produit.user', 'lot.etapes.user']);
        $local = $this->responsabiliteLocale($reclamation);

        $etapes = $reclamation->lot?->etapes
            ?->sortBy('date_etape')
            ->map(fn (EtapeTracabilite $etape) => sprintf(
                '- %s | %s | %s | %s%s',
                $etape->date_etape?->format('d/m/Y H:i') ?? '?',
                $etape->type_etape,
                $etape->lieu ?? 'lieu inconnu',
                $etape->user?->name ?? 'acteur inconnu',
                $etape->temperature !== null ? ' | '.$etape->temperature.' °C' : ''
            ))
            ->implode("\n");

        $anomalies = $reclamation->lot
            ? collect($reclamation->lot->anomalies())->pluck('message')->implode(' ; ')
            : '';

        $systeme = <<<'PROMPT'
        Tu es auditeur qualité de la chaîne d'approvisionnement NutriTrace (production → transformation → transport/stockage → distribution).
        À partir de la réclamation et de la traçabilité du lot, indique le maillon le plus probablement responsable.
        Réponds uniquement par un objet JSON :
        {"responsable": "producteur" | "transformateur" | "distributeur" | "plateforme",
         "confiance": entier de 0 à 100,
         "explication": "2 phrases en français qui citent les indices utilisés (étape, température, délai…)"}
        "distributeur" couvre le transport, le stockage et la livraison. "plateforme" : erreur de commande ou d'information.
        Sans données de traçabilité, baisse la confiance. N'invente aucune étape.
        PROMPT;

        $contexte = implode("\n", [
            'Produit : '.($reclamation->produit?->name ?? '?').' (acteur : '.($reclamation->produit?->user?->type_acteur ?? '?').')',
            'Catégorie : '.$reclamation->categorieLibelle(),
            'Sujet : '.$reclamation->sujet,
            'Description : '.$reclamation->description,
            $reclamation->lot ? 'Lot '.$reclamation->lot->code." — étapes :\n".($etapes ?: 'aucune') : 'Aucun lot renseigné.',
            $anomalies !== '' ? 'Anomalies détectées automatiquement : '.$anomalies : '',
        ]);

        $ia = $this->deepseek->json($systeme, $contexte);

        if (! $ia || ! array_key_exists((string) ($ia['responsable'] ?? ''), Reclamation::RESPONSABLES)) {
            return $local;
        }

        return [
            'responsable' => $ia['responsable'],
            'explication' => $this->texte($ia['explication'] ?? null, 600) ?? $local['explication'],
            'confiance' => max(0, min(100, (int) ($ia['confiance'] ?? 50))),
            'source' => 'ia',
        ];
    }

    /**
     * Règles locales : mots-clés, utilisées sans clé DeepSeek et dans les tests.
     *
     * @return array{sentiment: string, priorite: string, suggestion: string, categorie: string, langue: string, resume: string, source: string}
     */
    public function analyseLocale(string $sujet, string $description): array
    {
        $texte = mb_strtolower($sujet.' '.$description);

        $urgent = $this->contient($texte, ['urgent', 'danger', 'toxique', 'malade', 'intoxication', 'hôpital', 'hopital', 'blessé', 'blesse', 'allergie', 'vomi']);
        $negatif = $this->contient($texte, ['cassé', 'casse', 'pourri', 'retard', 'remboursement', 'manquant', 'abîmé', 'abime', 'erreur', 'colère', 'colere', 'déçu', 'decu', 'mauvais', 'moisi', 'périmé', 'perime']);
        $positif = $this->contient($texte, ['merci', 'excellent', 'satisfait', 'parfait', 'bravo', 'content']);

        $sentiment = match (true) {
            $urgent || $negatif => 'negatif',
            $positif => 'positif',
            default => 'neutre',
        };

        $priorite = match (true) {
            $urgent => 'urgente',
            $negatif && $this->contient($texte, ['remboursement', 'retard', 'manquant', 'cassé', 'casse']) => 'haute',
            $negatif => 'moyenne',
            $positif => 'faible',
            default => 'moyenne',
        };

        $categorie = match (true) {
            $urgent || $this->contient($texte, ['toxique', 'intoxication', 'allergie', 'corps étranger']) => 'securite_alimentaire',
            $this->contient($texte, ['retard', 'livraison', 'livreur', 'colis', 'reçu', 'recu']) => 'livraison',
            $this->contient($texte, ['emballage', 'carton', 'cassé', 'casse', 'bocal', 'fuite']) => 'emballage',
            $this->contient($texte, ['manquant', 'poids', 'quantité', 'quantite', 'incomplet']) => 'quantite',
            $this->contient($texte, ['origine', 'question', 'composition', 'ingrédient', 'ingredient', '?']) => 'information',
            $this->contient($texte, ['pourri', 'goût', 'gout', 'mou', 'moisi', 'abîmé', 'abime', 'qualité', 'qualite', 'périmé', 'perime']) => 'qualite',
            default => 'autre',
        };

        $langue = match (true) {
            (bool) preg_match('/\p{Arabic}/u', $texte) => 'ar',
            $this->contient($texte, [' the ', ' my ', 'please', 'order', 'broken']) => 'en',
            $this->contient($texte, ['barcha', 'mouch', 'fama', 'chwaya', 'brabi']) => 'tn',
            default => 'fr',
        };

        $suggestion = match ($sentiment) {
            'negatif' => 'Bonjour, nous sommes désolés pour cette expérience. Nous examinons votre réclamation et revenons vers vous avec une solution.',
            'positif' => 'Bonjour, merci pour votre retour. Nous le transmettons à l\'équipe concernée.',
            default => 'Bonjour, nous avons bien reçu votre message et nous l\'étudions.',
        };

        return [
            'sentiment' => $sentiment,
            'priorite' => $priorite,
            'suggestion' => $suggestion,
            'categorie' => $categorie,
            'langue' => $langue,
            'resume' => mb_strimwidth(trim($sujet).' — '.trim($description), 0, 160, '…'),
            'source' => 'regles',
        ];
    }

    /**
     * @return array{message: string, solution: string, langue: string, source: string}
     */
    private function reponseLocale(Reclamation $reclamation, string $ton): array
    {
        $nom = $reclamation->user?->name ? ' '.$reclamation->user->name : '';
        $produit = $reclamation->produit?->name ?? 'votre produit';

        $solution = match ($reclamation->categorie) {
            'securite_alimentaire' => 'Analyse du lot et remboursement',
            'livraison' => 'Suivi de livraison prioritaire',
            'emballage', 'qualite' => 'Remplacement du produit',
            'quantite' => 'Envoi du complément',
            'information' => 'Explication détaillée',
            default => 'Étude du dossier',
        };

        $corps = match ($reclamation->categorie) {
            'securite_alimentaire' => "Par précaution, ne consommez plus {$produit} et conservez l'emballage : notre équipe qualité analyse le lot concerné en priorité.",
            'livraison' => 'Nous avons relancé le distributeur et suivons votre colis en priorité.',
            'emballage', 'qualite' => "Nous vous proposons le remplacement de {$produit}. Une photo du produit nous aiderait à remonter le problème au producteur.",
            'quantite' => 'Nous vérifions la préparation de votre commande pour vous envoyer le complément manquant.',
            'information' => 'Vous trouverez le parcours complet du produit sur sa page de traçabilité, avec le code du lot imprimé sur l\'emballage.',
            default => 'Nous étudions votre dossier et revenons vers vous rapidement.',
        };

        $message = match ($ton) {
            'formel' => "Madame, Monsieur{$nom},\n\nNous accusons réception de votre réclamation {$reclamation->reference}. {$corps}\n\nVeuillez agréer nos salutations distinguées.\nL'équipe NutriTrace",
            'bref' => "Bonjour{$nom}, {$corps}\nL'équipe NutriTrace",
            default => "Bonjour{$nom},\n\nNous sommes sincèrement désolés pour ce désagrément et nous vous remercions de nous l'avoir signalé. {$corps}\n\nNous restons à votre écoute.\nL'équipe NutriTrace",
        };

        return ['message' => $message, 'solution' => $solution, 'langue' => 'fr', 'source' => 'regles'];
    }

    /**
     * @return array{responsable: string, explication: string, confiance: int, source: string}
     */
    private function responsabiliteLocale(Reclamation $reclamation): array
    {
        $anomalies = $reclamation->lot ? collect($reclamation->lot->anomalies()) : collect();
        $froid = $anomalies->contains(fn ($a) => str_contains(mb_strtolower($a['message'] ?? ''), 'froid') || str_contains(mb_strtolower($a['message'] ?? ''), 'température'));

        [$responsable, $raison] = match (true) {
            $froid => ['distributeur', 'La traçabilité du lot signale une rupture de la chaîne du froid pendant le transport ou le stockage.'],
            in_array($reclamation->categorie, ['livraison', 'emballage'], true) => ['distributeur', 'Le problème décrit concerne l\'acheminement ou l\'état du colis à la réception.'],
            $reclamation->categorie === 'quantite' => ['distributeur', 'Une quantité manquante vient le plus souvent de la préparation de la commande.'],
            $reclamation->categorie === 'information' => ['plateforme', 'La demande porte sur l\'information affichée, pas sur le produit lui-même.'],
            $reclamation->produit?->user?->type_acteur === 'transformateur' => ['transformateur', 'Le produit est transformé : un défaut de goût ou de qualité vient en priorité de la transformation.'],
            default => ['producteur', 'Le défaut décrit porte sur le produit lui-même, sans incident relevé après la production.'],
        };

        return [
            'responsable' => $responsable,
            'explication' => $raison.($reclamation->lot ? '' : ' Aucun lot n\'est renseigné : estimation faite sur la seule description.'),
            'confiance' => $reclamation->lot ? 60 : 35,
            'source' => 'regles',
        ];
    }

    /**
     * @param  list<string>  $autorisees
     */
    private function valeur(mixed $valeur, array $autorisees, string $defaut): string
    {
        return in_array($valeur, $autorisees, true) ? $valeur : $defaut;
    }

    private function texte(mixed $valeur, int $max): ?string
    {
        $texte = is_string($valeur) ? trim($valeur) : '';

        return $texte === '' ? null : mb_substr($texte, 0, $max);
    }

    /**
     * @param  list<string>  $mots
     */
    private function contient(string $texte, array $mots): bool
    {
        foreach ($mots as $mot) {
            if (str_contains($texte, $mot)) {
                return true;
            }
        }

        return false;
    }
}
