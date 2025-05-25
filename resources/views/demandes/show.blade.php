@php use App\Models\EtatDemande;use Carbon\Carbon; @endphp
@extends('layouts.app')

@section('header')
    Détails de la demande
@endsection

@section('content')
    <div class="row">
        @if(session('success'))
            <div class="col-md-12">
                <div class="card border-success mb-4">
                    <div class="card-header bg-success text-white">
                        Demande traitée avec succès
                    </div>
                    <div class="card-body text-success">
                        <p class="card-text">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Détail de la demande #{{ $demande->id }}
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{ $demande->typeDocument->nom ?? 'N/A' }}</h5>
                    <dl class="row">
                        <dt class="col-sm-4">Nom :</dt>
                        <dd class="col-sm-8">{{ $demande->nom ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Prénom :</dt>
                        <dd class="col-sm-8">{{ $demande->prenom ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Email :</dt>
                        <dd class="col-sm-8">{{ $demande->email ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Téléphone :</dt>
                        <dd class="col-sm-8">{{ $demande->telephone ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Statut :</dt>
                        <dd class="col-sm-8">{{ ucfirst($demande->statut) ?? 'N/A' }}</dd>

                        @if($demande->statut === 'étatique')
                            <dt class="col-sm-4">Matricule :</dt>
                            <dd class="col-sm-8">{{ $demande->matricule ?? 'N/A' }}</dd>
                        @endif

                        <dt class="col-sm-4">NIN :</dt>
                        <dd class="col-sm-8">{{ $demande->nin ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Structure :</dt>
                        <dd class="col-sm-8">{{ $demande->structure->nom ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Catégorie socioprofessionnelle :</dt>
                        <dd class="col-sm-8">{{ $demande->categorie_socioprofessionnelle ?? 'N/A' }}</dd>

                        @if( $demande->date_naissance )
                            <dt class="col-sm-4">Date de naissance :</dt>
                            <dd class="col-sm-8">{{ Carbon::parse($demande->date_naissance)->format('d/m/Y') }}</dd>
                        @endif

                        @if( $demande->date_prise_service )
                            <dt class="col-sm-4">Date de prise de service :</dt>
                            <dd class="col-sm-8">{{ Carbon::parse($demande->date_prise_service)->format('d/m/Y') }}</dd>
                        @endif

                        @if( $demande->date_fin_service )
                            <dt class="col-sm-4">Date de fin de service :</dt>
                            <dd class="col-sm-8">{{Carbon::parse($demande->date_fin_service)->format('d/m/Y') }}</dd>
                        @endif

                        @if( $demande->date_depart_retraite )
                            <dt class="col-sm-4">Date de départ à la retraite :</dt>
                            <dd class="col-sm-8">{{ Carbon::parse($demande->date_depart_retraite)->format('d/m/Y') }}</dd>
                        @endif

                        <dt class="col-sm-4">État :</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-info text-dark">{{ $demande->etatDemande->nom ?? 'N/A' }}</span>
                        </dd>

                        <dt class="col-sm-4">Commentaire :</dt>
                        <!-- Affichage du commentaire avec un style de préservation des retours à la ligne -->
                        <dd class="col-sm-8" style="white-space: pre-wrap">{{ $demande->commentaire ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Date de création :</dt>
                        <dd class="col-sm-8">{{ $demande->created_at ? $demande->created_at->format('d/m/Y H:m') : 'N/A' }}</dd>

                        <dt class="col-sm-4">Dernière modification :</dt>
                        <dd class="col-sm-8">{{ $demande->updated_at ? $demande->updated_at->format('d/m/Y H:m') : 'N/A' }}</dd>

                        <dt class="col-sm-4">Fichiers justificatifs :</dt>
                        <dd class="col-sm-8">
                            @if($demande->justificatifs && $demande->justificatifs->count())
                                <a href="#" data-bs-toggle="modal" data-bs-target="#fichiersModal">Voir les fichiers
                                    ({{ $demande->justificatifs->count() }})</a>
                            @else
                                N/A
                            @endif
                        </dd>
                    </dl>
                    <!-- Modal -->
                    <div class="modal fade" id="fichiersModal" tabindex="-1" aria-labelledby="fichiersModalLabel"
                         aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="fichiersModalLabel">Fichiers justificatifs</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    @if($demande->justificatifs && $demande->justificatifs->count())
                                        <ul>
                                            @foreach($demande->justificatifs as $fichier)
                                                <li>
                                                    <a href="#" data-bs-toggle="collapse"
                                                       data-bs-target="#fichier-{{ $fichier->id }}">
                                                        {{ $fichier->nom }} ({{ $fichier->mime_type }}
                                                        , {{ number_format($fichier->taille / 1024, 2) }} Ko)
                                                    </a>
                                                    <div id="fichier-{{ $fichier->id }}" class="collapse mt-2">
                                                        <iframe src="{{ route('justificatifs.voir', $fichier->id) }}"
                                                                width="80%" height="400px" frameborder="0"></iframe>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p>Aucun fichier.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('demandes.index') }}" class="btn btn-secondary mt-3">Retour à la liste</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <!-- Boutons -->
                        @if($demande->etatDemande->nom == EtatDemande::EN_ATTENTE && auth()->user()->hasRole('ADMIN'))
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#etatModal"
                                    data-etat="RECEPTIONNEE">
                                Réceptionner la demande
                            </button>
                        @elseif($demande->etatDemande->nom == EtatDemande::RECEPTIONNEE && auth()->user()->hasRole('CHEF_DE_DIVISION'))

                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#etatModal"
                                    data-etat="VALIDEE">
                                Valider la demande
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                    data-bs-target="#etatModal"
                                    data-etat="REFUSEE">
                                Refuser la demande
                            </button>
                        @elseif($demande->etatDemande->nom === EtatDemande::VALIDEE && auth()->id() === $demande->agent_id)
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#etatModal"
                                    data-etat="EN SIGNATURE">
                                Envoyer en signature
                            </button>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#etatModal"
                                    data-etat="DEMANDE DE COMPLEMENTS">
                                Demander des compléments
                            </button>
                        @else
                            <span class="text-muted">Aucune action disponible.</span>
                        @endif
                    </div>
                    <!-- Modal -->
                    <div class="modal fade" id="etatModal" tabindex="-1" aria-labelledby="etatModalLabel"
                         aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('demandes.changerEtat', $demande->id) }}">
                                @csrf
                                <input type="hidden" name="nouvel_etat" id="nouvel_etat_input">

                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="etatModalLabel">Confirmation du changement
                                            d’état</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Fermer"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="agentSelectWrapper" class="mb-3 d-none">
                                            <label for="agent_id" class="form-label">Imputer à un agent</label>
                                            <select name="agent_id" id="agent_id" class="form-select">
                                                <option value="">Sélectionner un agent</option>
                                                @foreach($agents as $agent)
                                                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="commentaire" class="form-label">Commentaire</label>
                                            <textarea name="commentaire" id="commentaire" class="form-control"
                                                      required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success">Confirmer</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        const etatModal = document.getElementById('etatModal');
                        etatModal.addEventListener('show.bs.modal', event => {
                            const button = event.relatedTarget;
                            const etat = button.getAttribute('data-etat');
                            document.getElementById('nouvel_etat_input').value = etat;

                            // Affiche ou masque le select agent selon l'état
                            const agentSelectWrapper = document.getElementById('agentSelectWrapper');
                            if (etat === 'VALIDEE') {
                                agentSelectWrapper.classList.remove('d-none');
                            } else {
                                agentSelectWrapper.classList.add('d-none');
                            }

                        });
                    </script>

                </div>
            </div>
        </div>
        @if($pdfBase64)
            <div class="col-md-12 mt-4">
                <div class="card">
                    <div class="card-header">
                        Aperçu du document final
                    </div>
                    <div class="card-body text-center">
                        <a href="data:application/pdf;base64,{{ $pdfBase64 }}" download="Demande_{{ $demande->id }}.pdf"
                           class="btn btn-primary mb-3">
                            Télécharger le PDF
                        </a>

                        <iframe src="data:application/pdf;base64,{{ $pdfBase64 }}" width="100%" height="600px"></iframe>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
