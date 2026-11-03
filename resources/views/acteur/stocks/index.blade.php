@extends('layouts.front')

@section('title', 'Gestion intelligente des stocks')

@section('content')
@include('partials.front.page-header', ['titre' => 'Gestion intelligente des stocks'])

<div class="container py-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <p class="mb-0">Quantités disponibles en kilogrammes. Les lots de traçabilité ne sont pas ajoutés ici, pour ne pas compter deux fois la même marchandise.</p>
        <a class="btn btn-primary rounded-pill px-4" href="{{ route('acteur.stocks.create') }}">Suivre une culture</a>
    </div>

    <div class="table-responsive bg-light rounded">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Culture</th>
                    <th>Disponible</th>
                    <th>Alerte</th>
                    <th>Dans 7 jours</th>
                    <th>Dans 14 jours</th>
                    <th>Dans 30 jours</th>
                    <th>Rupture estimée</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fiches as $ligne)
                    @php($fiche = $ligne['fiche'])
                    @php($prevision = $ligne['prevision'])
                    <tr>
                        <td><a href="{{ route('acteur.stocks.show', $fiche) }}">{{ $fiche->culture_name }}</a></td>
                        <td>{{ \App\Models\Harvest::formaterKg($prevision['disponible']) }} kg</td>
                        <td>
                            @if ($fiche->niveau() === 'rupture')
                                <span class="badge bg-danger">Rupture</span>
                            @elseif ($fiche->niveau() === 'faible')
                                <span class="badge bg-warning text-dark">Stock faible</span>
                            @else
                                <span class="badge bg-success">Disponible</span>
                            @endif
                        </td>
                        <td>{{ \App\Models\Harvest::formaterKg($prevision['j7']) }} kg</td>
                        <td>{{ \App\Models\Harvest::formaterKg($prevision['j14']) }} kg</td>
                        <td>{{ \App\Models\Harvest::formaterKg($prevision['j30']) }} kg</td>
                        <td>{{ $prevision['rupture'] ? $prevision['rupture']->format('d/m/Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4">Aucune culture suivie. Ouvrez un stock pour enregistrer les récoltes, les ventes et les pertes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
