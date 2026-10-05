<?php

namespace App\Services;

/**
 * Analyse locale du texte d'une réclamation.
 * Aucune API externe : la méthode pourra être remplacée par un vrai modèle plus tard.
 */
class AiReclamationService
{
    /**
     * @return array{sentiment: string, priorite: string, suggestion: string}
     */
    public function analyserReclamation(string $sujet, string $description): array
    {
        $texte = mb_strtolower($sujet.' '.$description);

        $urgent = $this->contient($texte, ['urgent', 'danger', 'toxique', 'malade', 'intoxication', 'hôpital', 'hopital', 'blessé', 'blesse']);
        $negatif = $this->contient($texte, ['cassé', 'casse', 'pourri', 'retard', 'remboursement', 'manquante', 'abîmé', 'abime', 'erreur', 'colère', 'colere', 'déçu', 'decu', 'mauvais']);
        $positif = $this->contient($texte, ['merci', 'excellent', 'satisfait', 'parfait', 'bravo', 'content']);

        $sentiment = match (true) {
            $urgent || $negatif => 'negatif',
            $positif && ! $negatif => 'positif',
            default => 'neutre',
        };

        $priorite = match (true) {
            $urgent => 'urgente',
            $negatif && $this->contient($texte, ['remboursement', 'retard', 'manquante', 'cassé', 'casse']) => 'haute',
            $negatif => 'moyenne',
            $positif => 'faible',
            default => 'moyenne',
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
        ];
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
