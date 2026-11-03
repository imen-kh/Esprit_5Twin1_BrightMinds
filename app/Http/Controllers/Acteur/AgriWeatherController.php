<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Services\AgriWeatherService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgriWeatherController extends Controller
{
    public function index(Request $request, AgriWeatherService $meteo): View
    {
        abort_unless(auth()->user()->isProducteur(), 403, 'La météo agricole est réservée aux producteurs.');

        $donnees = $request->validate([
            'ville' => ['nullable', 'string', 'max:80'],
        ]);

        return view('acteur.meteo.index', [
            'tableau' => $meteo->tableau(auth()->user(), $donnees['ville'] ?? null),
        ]);
    }
}
