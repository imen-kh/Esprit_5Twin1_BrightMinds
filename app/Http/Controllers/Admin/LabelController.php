<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LabelController extends Controller
{
    public function index(Request $request): mixed
    {
        $query = Label::withCount(['certifications as certifications_valides_count' => fn ($certifications) => $certifications->verified()]);
        if ($request->filled('q')) {
            $term = '%'.addcslashes((string) $request->input('q'), '%_').'%';
            $query->where(fn ($query) => $query->where('nom', 'like', $term)->orWhere('organisme', 'like', $term));
        }
        if ($request->has('actif') && $request->input('actif') !== '') $query->where('actif', $request->boolean('actif'));
        $labels = $query->latest()->paginate(15);
        return $request->expectsJson() ? response()->json($labels) : view('admin.labels.index', compact('labels'));
    }

    public function create(): \Illuminate\View\View { return view('admin.labels.create'); }

    public function store(StoreLabelRequest $request): mixed
    {
        $data = $request->safe()->except('logo');
        $data['logo'] = $request->file('logo')->store('labels', 'public');
        $data['actif'] = $request->boolean('actif', true);
        $label = Label::create($data);
        return $request->expectsJson() ? response()->json($label, 201) : redirect()->route('admin.labels.index')->with('success', 'Label créé.');
    }

    public function show(Request $request, Label $label): mixed
    {
        $label->load('certifications');
        return $request->expectsJson() ? response()->json($label) : view('admin.labels.show', compact('label'));
    }

    public function edit(Label $label): \Illuminate\View\View { return view('admin.labels.edit', compact('label')); }

    public function update(UpdateLabelRequest $request, Label $label): mixed
    {
        $data = $request->safe()->except('logo');
        if ($request->hasFile('logo')) {
            if ($label->logo) Storage::disk('public')->delete($label->logo);
            $data['logo'] = $request->file('logo')->store('labels', 'public');
        }
        $label->update($data);
        $label = $label->fresh();
        return $request->expectsJson() ? response()->json($label) : redirect()->route('admin.labels.index')->with('success', 'Label mis à jour.');
    }

    public function destroy(Request $request, Label $label): mixed
    {
        if ($label->certifications()->exists()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ce label est utilisé par une certification.'], 409);
            }

            return back()->with('error', 'Ce label est utilisé par une certification et ne peut pas être supprimé.');
        }
        if ($label->logo) Storage::disk('public')->delete($label->logo);
        $label->delete();

        return $request->expectsJson()
            ? response()->json(null, 204)
            : redirect()->route('admin.labels.index')->with('success', 'Label supprimé.');
    }
}
