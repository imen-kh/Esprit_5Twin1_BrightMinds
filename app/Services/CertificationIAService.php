<?php

namespace App\Services;

use App\Models\Certification;
use Illuminate\Support\Facades\Cache;

/**
 * Explainable local AI-style audit for certifications.
 *
 * This intentionally uses deterministic signals from the certification record
 * instead of sending documents or personal data to an external API.
 */
class CertificationIAService
{
    /**
     * @return array{
     *     score:int,
     *     niveau:string,
     *     resume:string,
     *     points_forts:list<string>,
     *     recommandations:list<string>,
     *     facteurs:list<array{nom:string,impact:string,details:string}>
     * }
     */
    public function analyser(Certification $certification): array
    {
        $certification->loadMissing(['label', 'product']);
        $cacheKey = 'certification:ia:'.$certification->id.':'.$certification->updated_at?->timestamp;

        return Cache::remember($cacheKey, now()->addDay(), function () use ($certification): array {
            $score = 100;
            $forces = [];
            $recommendations = [];
            $factors = [];

            if ($certification->statut !== 'validee') {
                $score -= 30;
                $factors[] = ['nom' => 'Statut', 'impact' => 'negatif', 'details' => 'La certification n’est pas encore validée.'];
                $recommendations[] = 'Faire vérifier la certification par un administrateur.';
            } else {
                $forces[] = 'Certification validée côté serveur.';
                $factors[] = ['nom' => 'Statut', 'impact' => 'positif', 'details' => 'La certification est validée.'];
            }

            if ($certification->is_expired) {
                $score -= 45;
                $factors[] = ['nom' => 'Expiration', 'impact' => 'critique', 'details' => 'La date d’expiration est dépassée.'];
                $recommendations[] = 'Renouveler la certification avant toute nouvelle publication.';
            } elseif ($certification->date_expiration?->lte(today()->addDays(30))) {
                $score -= 20;
                $factors[] = ['nom' => 'Expiration', 'impact' => 'attention', 'details' => 'La certification expire dans moins de 30 jours.'];
                $recommendations[] = 'Préparer le renouvellement dans les 30 prochains jours.';
            } else {
                $forces[] = 'Période de validité suffisamment longue.';
            }

            if ($certification->label?->actif) {
                $forces[] = 'Label actif et reconnu dans le catalogue.';
            } else {
                $score -= 15;
                $factors[] = ['nom' => 'Label', 'impact' => 'negatif', 'details' => 'Le label associé est inactif ou introuvable.'];
                $recommendations[] = 'Vérifier l’état du label associé.';
            }

            if (blank($certification->fichier_pdf) || ! is_string($certification->fichier_pdf)) {
                $score -= 15;
                $factors[] = ['nom' => 'Document', 'impact' => 'negatif', 'details' => 'Aucun justificatif PDF exploitable n’est associé.'];
                $recommendations[] = 'Joindre un justificatif PDF lisible et à jour.';
            } else {
                $forces[] = 'Justificatif PDF présent.';
            }

            if (blank($certification->numero) || mb_strlen(trim($certification->numero)) < 4) {
                $score -= 10;
                $factors[] = ['nom' => 'Référence', 'impact' => 'attention', 'details' => 'Le numéro de certification est trop court.'];
                $recommendations[] = 'Contrôler la référence officielle de la certification.';
            } else {
                $forces[] = 'Référence officielle renseignée.';
            }

            $score = max(0, min(100, $score));
            $niveau = match (true) {
                $score >= 80 => 'fiable',
                $score >= 55 => 'à vérifier',
                default => 'risquée',
            };

            return [
                'score' => $score,
                'niveau' => $niveau,
                'resume' => match ($niveau) {
                    'fiable' => 'Les signaux disponibles sont cohérents avec une certification exploitable.',
                    'à vérifier' => 'Certains signaux nécessitent une vérification avant de considérer cette certification comme pleinement fiable.',
                    default => 'Plusieurs signaux importants indiquent qu’une action de conformité est nécessaire.',
                },
                'points_forts' => $forces,
                'recommandations' => array_values(array_unique($recommendations)),
                'facteurs' => $factors,
            ];
        });
    }
}
