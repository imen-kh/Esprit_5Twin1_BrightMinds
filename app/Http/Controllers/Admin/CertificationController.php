<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCertificationRequest;
use App\Http\Requests\ValidateCertificationRequest;
use App\Models\Certification;
use App\Models\Label;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CertificationIAService;

class CertificationController extends Controller
{
    public function create(): View
    {
        return view('admin.certifications.create', [
            'labels' => Label::active()->orderBy('nom')->get(),
            'products' => Product::orderBy('name')->get(),
            'acteurs' => User::where('role', 'acteur')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCertificationRequest $request): mixed
    {
        $data = $request->validated();
        $data['fichier_pdf'] = $request->file('fichier_pdf')->store('certifications', 'public');
        if ($data['statut'] !== 'refusee') {
            $data['motif_refus'] = null;
        }

        $certification = Certification::create($data);

        return $request->expectsJson()
            ? response()->json($certification, 201)
            : redirect()->route('admin.certifications.index')->with('success', 'Certification créée.');
    }

    public function index(Request $request): mixed
    {
        $query = Certification::with(['label', 'product', 'acteur']);
        if ($request->filled('q')) {
            $term = '%'.addcslashes((string) $request->input('q'), '%_').'%';
            $query->where(function ($query) use ($term): void {
                $query->where('numero', 'like', $term)
                    ->orWhereHas('label', fn ($label) => $label->where('nom', 'like', $term))
                    ->orWhereHas('product', fn ($product) => $product->where('name', 'like', $term));
            });
        }
        if ($request->filled('label_id')) $query->where('label_id', $request->integer('label_id'));
        if ($request->boolean('verified')) $query->verified();
        if ($request->boolean('expired')) $query->expired();
        if ($request->filled('statut')) $query->where('statut', $request->string('statut'));
        $certifications = $query->latest()->paginate(15);
        $stats = [
            'total' => Certification::count(),
            'en_attente' => Certification::pending()->count(),
            'validees' => Certification::validated()->count(),
            'expirant' => Certification::whereDate('date_expiration', '>=', today())->whereDate('date_expiration', '<=', today()->addDays(30))->count(),
        ];
        return $request->expectsJson() ? response()->json(['data' => $certifications, 'stats' => $stats]) : view('admin.certifications.index', compact('certifications', 'stats'));
    }

    public function show(Request $request, Certification $certification): mixed
    {
        $certification->load(['label', 'product', 'acteur']);
        return $request->expectsJson() ? response()->json($certification) : view('admin.certifications.show', compact('certification'));
    }

    public function analyser(Request $request, Certification $certification, CertificationIAService $service): mixed
    {
        $analyse = $service->analyser($certification);
        return $request->expectsJson() ? response()->json($analyse) : back()->with('analyse_ia', $analyse);
    }

    public function expiring(Request $request): mixed
    {
        $certifications = Certification::with(['label', 'product', 'acteur'])
            ->whereDate('date_expiration', '>=', today())
            ->whereDate('date_expiration', '<=', today()->addDays(30))
            ->latest('date_expiration')->paginate(15);
        return $request->expectsJson() ? response()->json($certifications) : view('admin.certifications.index', compact('certifications'))->with('expiring', true);
    }

    public function validate(ValidateCertificationRequest $request, Certification $certification): mixed
    {
        $data = $request->validated();
        $certification->update([
            'statut' => $data['statut'],
            'motif_refus' => $data['statut'] === 'refusee' ? ($data['motif_refus'] ?? null) : null,
        ]);
        $certification = $certification->fresh();
        return $request->expectsJson() ? response()->json($certification) : back()->with('success', 'Certification traitée.');
    }

    public function destroy(Request $request, Certification $certification): mixed
    {
        if ($certification->fichier_pdf) Storage::disk('public')->delete($certification->fichier_pdf);
        $certification->delete();

        return $request->expectsJson()
            ? response()->json(null, 204)
            : redirect()->route('admin.certifications.index')->with('success', 'Certification supprimée.');
    }
}
