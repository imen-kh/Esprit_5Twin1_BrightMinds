<?php

namespace App\Http\Controllers\Acteur;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificationRequest;
use App\Http\Requests\UpdateCertificationRequest;
use App\Models\Certification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\CertificationIAService;

class CertificationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): mixed
    {
        $query = Certification::where('acteur_id', $request->user()->id)->with(['label', 'product']);
        if ($request->boolean('verified')) $query->verified();
        if ($request->boolean('expired')) $query->expired();
        $certifications = $query->latest()->paginate(15);
        return $request->expectsJson() ? response()->json($certifications) : view('acteur.certifications.index', compact('certifications'));
    }

    public function create(): mixed
    {
        return view('acteur.certifications.create', ['labels' => \App\Models\Label::active()->get(), 'products' => \App\Models\Product::orderBy('name')->get()]);
    }

    public function expiring(Request $request): mixed
    {
        $certifications = Certification::forActeur($request->user()->id)->with(['label', 'product'])
            ->whereDate('date_expiration', '<', today()->addDays(30))
            ->whereDate('date_expiration', '>=', today())->paginate(15);
        return $request->expectsJson() ? response()->json($certifications) : view('acteur.certifications.index', compact('certifications'))->with('expiring', true);
    }

    public function store(StoreCertificationRequest $request): mixed
    {
        $data = $request->safe()->except('fichier_pdf');
        $data['acteur_id'] = $request->user()->id;
        $data['fichier_pdf'] = $request->file('fichier_pdf')->store('certifications', 'public');
        $data['statut'] = 'en_attente';
        $certification = Certification::create($data);
        return $request->expectsJson() ? response()->json($certification, 201) : redirect()->route('acteur.certifications.index')->with('success', 'Certification envoyée.');
    }

    public function show(Request $request, Certification $certification): mixed
    {
        $this->authorize('view', $certification);
        $certification->load(['label', 'product']);
        return $request->expectsJson() ? response()->json($certification) : view('acteur.certifications.show', compact('certification'));
    }

    public function analyser(Request $request, Certification $certification, CertificationIAService $service): mixed
    {
        $this->authorize('view', $certification);
        $analyse = $service->analyser($certification);

        return $request->expectsJson() ? response()->json($analyse) : back()->with('analyse_ia', $analyse);
    }

    public function edit(Certification $certification): mixed
    {
        $this->authorize('update', $certification);
        return view('acteur.certifications.edit', ['certification' => $certification, 'labels' => \App\Models\Label::active()->get(), 'products' => \App\Models\Product::orderBy('name')->get()]);
    }

    public function update(UpdateCertificationRequest $request, Certification $certification): mixed
    {
        $data = $request->safe()->except('fichier_pdf');
        if ($request->hasFile('fichier_pdf')) {
            if ($certification->fichier_pdf) Storage::disk('public')->delete($certification->fichier_pdf);
            $data['fichier_pdf'] = $request->file('fichier_pdf')->store('certifications', 'public');
        }
        $data['statut'] = 'en_attente';
        $data['motif_refus'] = null;
        $certification->update($data);
        $certification = $certification->fresh();
        return $request->expectsJson() ? response()->json($certification) : redirect()->route('acteur.certifications.index')->with('success', 'Certification mise à jour.');
    }

    public function destroy(Certification $certification): JsonResponse
    {
        $this->authorize('delete', $certification);
        if ($certification->fichier_pdf) Storage::disk('public')->delete($certification->fichier_pdf);
        $certification->delete();
        return response()->json(null, 204);
    }
}
