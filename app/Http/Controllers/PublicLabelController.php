<?php

namespace App\Http\Controllers;

use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicLabelController extends Controller
{
    public function index(Request $request): mixed
    {
        $query = Label::active()->withCount(['certifications as certifications_valides_count' => fn ($certifications) => $certifications->verified()])
            ->with(['certifications' => fn ($query) => $query->verified()->with(['product', 'acteur'])]);
        if ($request->filled('q')) {
            $term = '%'.addcslashes((string) $request->input('q'), '%_').'%';
            $query->where(fn ($query) => $query->where('nom', 'like', $term)->orWhere('organisme', 'like', $term));
        }
        $labels = $query->orderBy('nom')->paginate(12);
        return $request->expectsJson() ? response()->json($labels) : view('labels.index', compact('labels'));
    }

    public function show(Request $request, Label $label): mixed
    {
        abort_unless($label->actif, 404);
        $label->load(['certifications' => fn ($query) => $query->verified()->with(['product', 'acteur'])]);
        return $request->expectsJson() ? response()->json($label) : view('labels.show', compact('label'));
    }
}
