@extends('layouts.back')

@section('title', $bilan->exists ? 'Modifier le Bilan Environnemental' : 'Nouveau Bilan Environnemental')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">{{ $bilan->exists ? 'Modifier le Bilan Environnemental' : 'Nouveau Bilan Environnemental' }}</h4>
    <p class="text-muted mb-0">Renseignez les paramètres du produit puis utilisez l'IA pour estimer l'Éco-score et obtenir une alternative locale.</p>
  </div>
  <a href="{{ route('admin.bilans-environnementaux.index') }}" class="btn btn-outline-secondary">
    <i class="bx bx-arrow-back me-1"></i> Retour à la liste
  </a>
</div>

<!-- Bloc IA interactif -->
<div class="card border-primary mb-4 shadow-sm">
  <div class="card-header bg-label-primary d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <i class="bx bx-bot bx-sm text-primary"></i>
      <strong class="text-primary fs-6">Module IA — Estimation Éco-Score & Alternative Locale</strong>
    </div>
    <span class="badge bg-primary">Intelligence Environnementale</span>
  </div>
  <div class="card-body pt-3">
    <p class="text-muted small mb-3">
      Remplissez les informations de production, transport et emballage ci-dessous, puis cliquez sur le bouton pour lancer l'analyse prédictive IA.
    </p>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <button type="button" class="btn btn-primary" id="btn-ia-estimer">
        <i class="bx bx-magic-wand me-1"></i> Estimer l'Éco-Score & Suggérer une Alternative Locale (IA)
      </button>
      <span id="ia-loading" class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
      <span id="ia-status" class="small text-muted"></span>
    </div>

    <!-- Aperçu du résultat IA -->
    <div id="ia-preview-box" class="alert alert-success mt-3 d-none">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div class="d-flex align-items-center gap-3">
          <span class="fs-4 fw-bold" id="preview-badge-container"></span>
          <div>
            <h6 class="mb-0 fw-bold" id="preview-appreciation">Éco-Score Estimé</h6>
            <small class="text-muted" id="preview-points"></small>
          </div>
        </div>
        <div class="text-end">
          <span class="badge bg-label-primary fs-6" id="preview-co2-total">0.000 kg CO2e</span>
          <div class="small text-muted" id="preview-gain"></div>
        </div>
      </div>
      <div class="p-2 bg-white rounded border mt-2">
        <strong class="text-success"><i class="bx bx-bulb me-1"></i> Proposition de substitution locale :</strong>
        <p class="mb-0 small text-dark mt-1" id="preview-alternative-texte"></p>
      </div>
    </div>
  </div>
</div>

<form id="form-bilan" method="POST" action="{{ $bilan->exists ? route('admin.bilans-environnementaux.update', $bilan) : route('admin.bilans-environnementaux.store') }}">
  @csrf
  @if ($bilan->exists)
    @method('PUT')
  @endif

  <div class="row">
    <!-- Colonne gauche : Paramètres du produit -->
    <div class="col-lg-7">
      <div class="card mb-4">
        <div class="card-header pb-2">
          <h5 class="card-title mb-0"><i class="bx bx-package me-1 text-primary"></i> Paramètres du Produit & Cycle de Vie</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="produit_id">Produit <span class="text-danger">*</span></label>
              <select class="form-select @error('produit_id') is-invalid @enderror" id="produit_id" name="produit_id" required>
                <option value="">Sélectionner un produit</option>
                @foreach ($products as $product)
                  <option value="{{ $product->id }}" data-name="{{ $product->name }}" @selected((string) old('produit_id', $bilan->produit_id) === (string) $product->id)>
                    {{ $product->name }}
                  </option>
                @endforeach
              </select>
              @error('produit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text">Le bilan est rattaché au produit du catalogue. Son nom n'est pas saisi à part.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="facteur_emission_id">Facteur d'émission <span class="text-danger">*</span></label>
              <select class="form-select @error('facteur_emission_id') is-invalid @enderror" id="facteur_emission_id" name="facteur_emission_id" required>
                <option value="">Sélectionner un facteur</option>
                @foreach ($facteurs as $facteur)
                  <option value="{{ $facteur->id }}" @selected((string) old('facteur_emission_id', $bilan->facteur_emission_id) === (string) $facteur->id)>
                    {{ $facteur->nom }} — {{ $facteur->categorie }} ({{ $facteur->valeur }} {{ $facteur->unite }})
                  </option>
                @endforeach
              </select>
              @error('facteur_emission_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-8">
              <label class="form-label" for="nom_produit">Nom repris du produit</label>
              <input type="text" class="form-control @error('nom_produit') is-invalid @enderror" id="nom_produit" name="nom_produit" value="{{ old('nom_produit', $bilan->product?->name ?? $bilan->nom_produit) }}" readonly placeholder="Sélectionnez un produit">
              @error('nom_produit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label" for="quantite_produit">Quantité unitaire <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" step="0.01" min="0.01" class="form-control @error('quantite_produit') is-invalid @enderror" id="quantite_produit" name="quantite_produit" value="{{ old('quantite_produit', $bilan->quantite_produit ?? 1.0) }}" required>
                <input type="text" class="form-control" style="max-width: 80px;" id="unite_mesure" name="unite_mesure" value="{{ old('unite_mesure', $bilan->unite_mesure ?? 'kg') }}" placeholder="kg">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="mode_culture">Mode de production / Culture</label>
              <select class="form-select @error('mode_culture') is-invalid @enderror" id="mode_culture" name="mode_culture">
                <option value="bio" @selected(old('mode_culture', $bilan->mode_culture) === 'bio')>Agriculture Biologique certifiée (Faible CO2)</option>
                <option value="plein_champ" @selected(old('mode_culture', $bilan->mode_culture) === 'plein_champ')>Culture plein champ raisonnée (Standard)</option>
                <option value="hydroponique" @selected(old('mode_culture', $bilan->mode_culture) === 'hydroponique')>Culture hydroponique sous abri</option>
                <option value="serre_chauffee" @selected(old('mode_culture', $bilan->mode_culture) === 'serre_chauffee')>Serre chauffée au gaz/fioul (Intensif)</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="consommation_eau_l">Eau d'irrigation estimée (Litres)</label>
              <input type="number" step="1" min="0" class="form-control" id="consommation_eau_l" name="consommation_eau_l" value="{{ old('consommation_eau_l', $bilan->consommation_eau_l ?? 50) }}" placeholder="Ex: 60">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="origine_geographique">Origine géographique</label>
              <input type="text" class="form-control" id="origine_geographique" name="origine_geographique" value="{{ old('origine_geographique', $bilan->origine_geographique ?? 'Cap Bon, Tunisie') }}" placeholder="Ex: Cap Bon, Sousse, Import Espagne...">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="distance_km">Distance de transport (km)</label>
              <div class="input-group">
                <input type="number" step="1" min="0" class="form-control" id="distance_km" name="distance_km" value="{{ old('distance_km', $bilan->distance_km ?? 35) }}">
                <span class="input-group-text">km</span>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="mode_transport">Mode de transport principal</label>
              <select class="form-select" id="mode_transport" name="mode_transport">
                <option value="camion" @selected(old('mode_transport', $bilan->mode_transport) === 'camion')>Camion routier (Diesel standard)</option>
                <option value="train" @selected(old('mode_transport', $bilan->mode_transport) === 'train')>Train / Ferroviaire</option>
                <option value="bateau" @selected(old('mode_transport', $bilan->mode_transport) === 'bateau')>Bateau / Maritime Cargo</option>
                <option value="avion" @selected(old('mode_transport', $bilan->mode_transport) === 'avion')>Avion / Fret aérien</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="type_emballage">Conditionnement & Emballage</label>
              <select class="form-select" id="type_emballage" name="type_emballage">
                <option value="vrac" @selected(old('type_emballage', $bilan->type_emballage) === 'vrac')>Vrac / Cagette réutilisable (0 déchet)</option>
                <option value="carton" @selected(old('type_emballage', $bilan->type_emballage) === 'carton')>Carton recyclé / Papier kraft</option>
                <option value="verre" @selected(old('type_emballage', $bilan->type_emballage) === 'verre')>Bocal / Bouteille en verre</option>
                <option value="plastique" @selected(old('type_emballage', $bilan->type_emballage) === 'plastique')>Barquette / Film plastique PET</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="poids_emballage_g">Poids de l'emballage (grammes)</label>
              <div class="input-group">
                <input type="number" step="0.5" min="0" class="form-control" id="poids_emballage_g" name="poids_emballage_g" value="{{ old('poids_emballage_g', $bilan->poids_emballage_g ?? 0) }}">
                <span class="input-group-text">g</span>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="statut">Statut de validation</label>
              <select class="form-select" id="statut" name="statut" required>
                <option value="valide" @selected(old('statut', $bilan->statut) === 'valide')>Validé (Public)</option>
                <option value="en_attente" @selected(old('statut', $bilan->statut) === 'en_attente')>En attente d'audit</option>
                <option value="archive" @selected(old('statut', $bilan->statut) === 'archive')>Archivé</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label" for="commentaires">Remarques ou spécificités produit</label>
              <textarea class="form-control" id="commentaires" name="commentaires" rows="2" placeholder="Notes complémentaires sur l'exploitation, les engrais organiques ou la traçabilité...">{{ old('commentaires', $bilan->commentaires) }}</textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Colonne droite : Résultats Carbone & Recommandations IA -->
    <div class="col-lg-5">
      <div class="card mb-4">
        <div class="card-header pb-2">
          <h5 class="card-title mb-0"><i class="bx bx-bar-chart-alt-2 me-1 text-success"></i> Résultats Carbone & Éco-Score</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="eco_score">Éco-Score</label>
              <select class="form-select fw-bold fs-5 text-center" id="eco_score" name="eco_score">
                <option value="A" @selected(old('eco_score', $bilan->eco_score) === 'A') class="text-success">Grade A (Optimal)</option>
                <option value="B" @selected(old('eco_score', $bilan->eco_score) === 'B') class="text-info">Grade B (Bon)</option>
                <option value="C" @selected(old('eco_score', $bilan->eco_score) === 'C') class="text-warning">Grade C (Moyen)</option>
                <option value="D" @selected(old('eco_score', $bilan->eco_score) === 'D') class="text-danger">Grade D (Élevé)</option>
                <option value="E" @selected(old('eco_score', $bilan->eco_score) === 'E') class="text-dark">Grade E (Critique)</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="score_numerique">Score Global (/100)</label>
              <input type="number" min="0" max="100" class="form-control fw-bold fs-5 text-center" id="score_numerique" name="score_numerique" value="{{ old('score_numerique', $bilan->score_numerique ?? 85) }}">
            </div>

            <div class="col-12">
              <label class="form-label" for="co2_total">Empreinte Carbone Totale (kg CO2e)</label>
              <div class="input-group">
                <input type="number" step="0.001" min="0" class="form-control fw-bold text-primary fs-5" id="co2_total" name="co2_total" value="{{ old('co2_total', $bilan->co2_total ?? 0.450) }}">
                <span class="input-group-text">kg CO2e</span>
              </div>
            </div>

            <div class="col-4">
              <label class="form-label small" for="co2_production">Production</label>
              <input type="number" step="0.001" class="form-control form-control-sm" id="co2_production" name="co2_production" value="{{ old('co2_production', $bilan->co2_production ?? 0.420) }}">
            </div>

            <div class="col-4">
              <label class="form-label small" for="co2_transport">Transport</label>
              <input type="number" step="0.001" class="form-control form-control-sm" id="co2_transport" name="co2_transport" value="{{ old('co2_transport', $bilan->co2_transport ?? 0.030) }}">
            </div>

            <div class="col-4">
              <label class="form-label small" for="co2_emballage">Emballage</label>
              <input type="number" step="0.001" class="form-control form-control-sm" id="co2_emballage" name="co2_emballage" value="{{ old('co2_emballage', $bilan->co2_emballage ?? 0.000) }}">
            </div>

            <div class="col-12 mt-3">
              <label class="form-label text-success fw-bold" for="alternative_locale">
                <i class="bx bx-bot me-1"></i> Alternative plus locale suggérée par l'IA
              </label>
              <textarea class="form-control border-success" id="alternative_locale" name="alternative_locale" rows="3" placeholder="L'IA formulera ici une alternative tunisienne locale et de saison...">{{ old('alternative_locale', $bilan->alternative_locale) }}</textarea>
            </div>

            <div class="col-12">
              <label class="form-label" for="recommandation_ia">Recommandations & Pistes d'amélioration IA</label>
              <textarea class="form-control" id="recommandation_ia" name="recommandation_ia" rows="3" placeholder="Leviers pour réduire les émissions...">{{ old('recommandation_ia', $bilan->recommandation_ia) }}</textarea>
            </div>

            <div class="col-12">
              <label class="form-label" for="reduction_co2_estimee_pourcent">Gain CO2 estimé avec alternative (%)</label>
              <div class="input-group">
                <span class="input-group-text text-success font-monospace">-</span>
                <input type="number" step="0.1" class="form-control" id="reduction_co2_estimee_pourcent" name="reduction_co2_estimee_pourcent" value="{{ old('reduction_co2_estimee_pourcent', $bilan->reduction_co2_estimee_pourcent) }}">
                <span class="input-group-text">%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body d-flex justify-content-between align-items-center">
      <div>
        @if ($bilan->exists)
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="recalculer_ia" name="recalculer_ia" value="1">
            <label class="form-check-label" for="recalculer_ia">Recalculer automatiquement les scores avec l'algorithme IA</label>
          </div>
        @endif
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.bilans-environnementaux.index') }}" class="btn btn-label-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary">
          <i class="bx bx-save me-1"></i> {{ $bilan->exists ? 'Mettre à jour le bilan' : 'Enregistrer le bilan' }}
        </button>
      </div>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const produitSelect = document.getElementById('produit_id');
  const nomInput = document.getElementById('nom_produit');

  if (produitSelect && nomInput) {
    const syncNomProduit = function () {
      const option = produitSelect.options[produitSelect.selectedIndex];
      nomInput.value = option?.dataset?.name || '';
    };

    produitSelect.addEventListener('change', syncNomProduit);

    if (produitSelect.value) {
      syncNomProduit();
    }
  }

  const btnIa = document.getElementById('btn-ia-estimer');
  const loading = document.getElementById('ia-loading');
  const status = document.getElementById('ia-status');
  const previewBox = document.getElementById('ia-preview-box');

  btnIa.addEventListener('click', async function () {
    const nom = document.getElementById('nom_produit').value.trim();
    if (!nom) {
      alert('Veuillez sélectionner un produit avant de lancer l\'estimation.');
      document.getElementById('produit_id').focus();
      return;
    }

    loading.classList.remove('d-none');
    status.textContent = 'Calcul de l\'impact carbone et recherche d\'alternatives locales par IA...';

    const payload = {
      nom_produit: nom,
      quantite_produit: document.getElementById('quantite_produit').value,
      unite_mesure: document.getElementById('unite_mesure').value,
      origine_geographique: document.getElementById('origine_geographique').value,
      distance_km: document.getElementById('distance_km').value,
      mode_transport: document.getElementById('mode_transport').value,
      mode_culture: document.getElementById('mode_culture').value,
      type_emballage: document.getElementById('type_emballage').value,
      poids_emballage_g: document.getElementById('poids_emballage_g').value,
      consommation_eau_l: document.getElementById('consommation_eau_l').value,
    };

    try {
      const response = await fetch("{{ route('admin.bilans-environnementaux.estimer-ia') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const res = await response.json();
      loading.classList.add('d-none');

      if (res.success && res.data) {
        const d = res.data;

        // Injection dans le formulaire
        document.getElementById('co2_production').value = d.co2_production;
        document.getElementById('co2_transport').value = d.co2_transport;
        document.getElementById('co2_emballage').value = d.co2_emballage;
        document.getElementById('co2_total').value = d.co2_total;
        document.getElementById('score_numerique').value = d.score_numerique;
        document.getElementById('eco_score').value = d.eco_score;
        document.getElementById('alternative_locale').value = d.alternative_locale;
        document.getElementById('recommandation_ia').value = d.recommandation_ia;
        document.getElementById('reduction_co2_estimee_pourcent').value = d.reduction_co2_estimee_pourcent;

        // Mise à jour de la boîte d'aperçu visuelle
        previewBox.classList.remove('d-none');
        const badgeColors = { 'A': 'bg-success', 'B': 'bg-info', 'C': 'bg-warning text-dark', 'D': 'bg-danger', 'E': 'bg-dark' };
        document.getElementById('preview-badge-container').innerHTML = `<span class="badge ${badgeColors[d.eco_score] || 'bg-primary'} px-3 py-2 fs-5">Éco-Score ${d.eco_score}</span>`;
        document.getElementById('preview-points').textContent = `Score de durabilité : ${d.score_numerique}/100`;
        document.getElementById('preview-co2-total').textContent = `${d.co2_total} kg CO2e`;
        document.getElementById('preview-gain').textContent = d.reduction_co2_estimee_pourcent ? `Gain potentiel : -${d.reduction_co2_estimee_pourcent}% CO2` : '';
        document.getElementById('preview-alternative-texte').textContent = d.alternative_locale;

        const sources = {
          openai: 'Texte rédigé par ChatGPT (OpenAI). Le score et le CO₂ viennent des facteurs d\'émission.',
          bilan: 'Texte déjà enregistré pour ce produit. ChatGPT n\'a pas été rappelé.',
          openai_indisponible: 'ChatGPT n\'a pas répondu. La formule locale a été utilisée.'
        };
        status.textContent = sources[d.source_ia] || 'Formule locale utilisée. Ajoutez OPENAI_API_KEY dans .env pour ChatGPT.';
      } else {
        status.textContent = '❌ Erreur lors de l\'estimation IA.';
      }
    } catch (e) {
      loading.classList.add('d-none');
      status.textContent = '❌ Erreur de communication réseau.';
      console.error(e);
    }
  });
});
</script>
@endpush
