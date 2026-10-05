@extends('layouts.back')
@section('title', 'Labels')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="fw-bold">Labels</h4><a href="{{ route('admin.labels.create') }}" class="btn btn-primary">Nouveau label</a></div>
<div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Nom</th><th>Organisme</th><th>Validité</th><th>État</th><th></th></tr></thead><tbody>
@forelse($labels as $label)<tr><td>{{ $label->nom }}</td><td>{{ $label->organisme }}</td><td>{{ $label->duree_validite_mois }} mois</td><td><span class="badge bg-label-{{ $label->actif ? 'success' : 'secondary' }}">{{ $label->actif ? 'Actif' : 'Inactif' }}</span></td><td><a href="{{ route('admin.labels.edit',$label) }}" class="btn btn-sm btn-outline-primary">Modifier</a></td></tr>
@empty<tr><td colspan="5">Aucun label.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $labels->links() }}</div></div>
@endsection
