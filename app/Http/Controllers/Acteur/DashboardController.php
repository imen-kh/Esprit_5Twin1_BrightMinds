<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Services\DashboardAgricoleService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardAgricoleService $tableau) {}

    public function index(): View
    {
        abort_unless(auth()->user()->isProducteur(), 403);

        return view('acteur.tableau.index', [
            'tableau' => $this->tableau->producteur(auth()->user()),
        ]);
    }
}
