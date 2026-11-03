<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductionForecastService;
use Illuminate\Http\Request;

class ProductionForecastController extends Controller
{
    public function __construct(private readonly ProductionForecastService $service) {}

    public function index(Request $request)
    {
        $this->authorizePlanner($request);

        return $this->page($request, $this->service->productsFor($request->user()));
    }

    public function predict(Request $request)
    {
        $this->authorizePlanner($request);
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'horizon' => ['required', 'integer', 'in:7,30'],
        ], [
            'product_id.required' => 'Sélectionnez un produit.',
            'horizon.in' => 'La période doit être de 7 ou 30 jours.',
        ]);

        $products = $this->service->productsFor($request->user());
        $product = $products->firstWhere('id', (int) $data['product_id']);
        abort_unless($product instanceof Product, 403, 'Ce produit ne vous appartient pas ou ne correspond pas à votre rôle.');

        return $this->page(
            $request,
            $products,
            $this->service->forecast($request->user(), $product, (int) $data['horizon']),
            (int) $data['horizon'],
        );
    }

    private function page(Request $request, $products, ?array $forecast = null, int $horizon = 7)
    {
        return view('acteur.production-planner', [
            'products' => $products,
            'forecast' => $forecast,
            'horizon' => $horizon,
            'actorLabel' => $request->user()->isTransformateur() ? 'transformateur' : 'producteur',
            'pageTitle' => $request->user()->isTransformateur() ? 'Prévision de production' : 'Prévision de la demande',
        ]);
    }

    private function authorizePlanner(Request $request): void
    {
        abort_unless($request->user()?->isProducteur() || $request->user()?->isTransformateur(), 403, 'Cette fonctionnalité est réservée aux producteurs et aux transformateurs.');
    }
}
