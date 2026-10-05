{{--
  Modal de confirmation de suppression, partagé par les lots et les étapes.
  Un bouton l'ouvre avec : data-bs-toggle="modal" data-bs-target="#modalSuppression"
                           data-action="URL de suppression" data-libelle="ce qui va être supprimé"
--}}
<div class="modal fade" id="modalSuppression" tabindex="-1" aria-labelledby="modalSuppressionTitre" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="#" id="formSuppression">
      @csrf
      @method('DELETE')
      <div class="modal-header">
        <h5 class="modal-title" id="modalSuppressionTitre">Confirmer la suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Voulez-vous vraiment supprimer <strong id="modalSuppressionLibelle"></strong> ?</p>
        <p class="mb-0 text-danger">Cette action est définitive.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-danger">Supprimer</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
  <script>
    // Renseigne le modal avec l'élément choisi : URL du formulaire DELETE et libellé affiché
    document.getElementById('modalSuppression').addEventListener('show.bs.modal', function (event) {
      const bouton = event.relatedTarget;
      document.getElementById('formSuppression').action = bouton.dataset.action;
      document.getElementById('modalSuppressionLibelle').textContent = bouton.dataset.libelle;
    });
  </script>
@endpush
