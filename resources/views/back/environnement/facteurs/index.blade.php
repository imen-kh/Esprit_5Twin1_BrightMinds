@extends('layouts.back')

@section('title', 'Facteurs d\'émission')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Facteurs d'Émission Carbone</h4>
    <p class="text-muted mb-0">Base de référence des facteurs d'émission (transport, production, emballage, énergie, eau).</p>
  </div>
  <a href="{{ route('admin.facteurs-emission.create') }}" class="btn btn-primary">
    <i class="bx bx-plus me-1"></i> Nouveau facteur
  </a>
</div>

<!-- Filtres par catégorie -->
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" action="{{ route('admin.facteurs-emission.index') }}" class="row g-3 align-items-center">
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher par nom, code ou source...">
        </div>
      </div>
      <div class="col-md-4">
        <select name="categorie" class="form-select" onchange="this.form.submit()">
          <option value="">Toutes les catégories</option>
          @foreach ($categories as $cat)
            <option value="{{ $cat }}" @selected(request('categorie') === $cat)>{{ ucfirst($cat) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-outline-primary w-100">Filtrer</button>
        @if(request()->hasAny(['q', 'categorie']))
          <a href="{{ route('admin.facteurs-emission.index') }}" class="btn btn-outline-secondary">Effacer</a>
        @endif
      </div>
    </form>
  </div>
</div>

<!-- Liste des facteurs -->
<div class="card">
  <div class="table-responsive text-nowrap">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>Code</th>
          <th>Nom du facteur</th>
          <th>Catégorie</th>
          <th>Valeur</th>
          <th>Unité</th>
          <th>Source</th>
          <th>Statut</th>
          <th>Bilans liés</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @forelse ($facteurs as $facteur)
          <tr>
            <td><code>{{ $facteur->code }}</code></td>
            <td>
              <strong>{{ $facteur->nom }}</strong>
              @if ($facteur->description)
                <div class="text-muted small text-truncate" style="max-width: 280px;" title="{{ $facteur->description }}">
                  {{ $facteur->description }}
                </div>
              @endif
            </td>
            <td>
              <span class="badge bg-label-{{ $facteur->badgeColor() }}">
                {{ ucfirst($facteur->categorie) }}
              </span>
            </td>
            <td><strong class="text-primary">{{ number_format($facteur->valeur, 4, ',', ' ') }}</strong></td>
            <td><small class="text-muted">{{ $facteur->unite }}</small></td>
            <td><small>{{ $facteur->source ?: '—' }}</small></td>
            <td>
              @if ($facteur->actif)
                <span class="badge bg-label-success">Actif</span>
              @else
                <span class="badge bg-label-secondary">Inactif</span>
              @endif
            </td>
            <td>
              <span class="badge bg-label-dark">{{ $facteur->bilans_count }}</span>
            </td>
            <td class="text-end">
              <a href="{{ route('admin.facteurs-emission.edit', $facteur) }}" class="btn btn-sm btn-outline-primary" title="Modifier">
                <i class="bx bx-edit-alt"></i>
              </a>
              <form action="{{ route('admin.facteurs-emission.destroy', $facteur) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirmer la suppression de ce facteur d\'émission ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                  <i class="bx bx-trash"></i>
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center py-5">
              <p class="text-muted mb-0">Aucun facteur d'émission trouvé.</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if ($facteurs->hasPages())
    <div class="card-footer">
      {{ $facteurs->links() }}
    </div>
  @endif
</div>
@endsection
