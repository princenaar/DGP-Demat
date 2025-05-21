@extends('layouts.public')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6 mx-auto">
                @if(session('success'))
                    <div class="card border-success mb-4">
                        <div class="card-header bg-success text-white">
                            Demande soumise
                        </div>
                        <div class="card-body text-success">
                            <p class="card-text">{{ session('success') }}</p>
                        </div>
                    </div>
                @else
                    <h2 class="text-center">Faire une demande</h2>
                    <form method="POST" action="{{ route('demandes.store') }}" enctype="multipart/form-data"
                          id="form-demande">
                        @csrf

                        <div class="mb-3">
                            <label for="type_document_id" class="form-label">Type de document</label>
                            <select class="form-select" name="type_document_id" id="type_document_id" required>
                                <option value="">-- Choisir --</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}"
                                            data-champs='@json($type->champs_requis)' {{ old('type_document_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->nom }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type_document_id')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom" id="prenom"
                                   value="{{ old('prenom') }}" required>
                            @error('prenom')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" id="nom" value="{{ old('nom') }}"
                                   required>
                            @error('nom')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" name="statut" id="statut" required>
                                <option value="">-- Choisir --</option>
                                <option value="étatique" {{ old('statut') == 'étatique' ? 'selected' : '' }}>Étatique
                                </option>
                                <option value="contractuel" {{ old('statut') == 'contractuel' ? 'selected' : '' }}>
                                    Contractuel
                                </option>
                            </select>
                            @error('statut')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nin" class="form-label">Numéro d'Identification National</label>
                            <input type="text" class="form-control" name="nin" id="nin" value="{{ old('nin') }}"
                                   required>
                            @error('nin')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="matricule" class="form-label">Matricule</label>
                            <input type="text" class="form-control" name="matricule" id="matricule"
                                   value="{{ old('matricule') }}">
                            @error('matricule')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="structure_id" class="form-label">Structure</label>
                            <select class="form-select" name="structure_id" id="structure_id" required>
                                <option value="">-- Choisir --</option>
                                @foreach ($structures as $structure)
                                    <option
                                        value="{{ $structure->id }}" {{ old('structure_id') == $structure->id ? 'selected' : '' }}>{{ $structure->nom }}</option>
                                @endforeach
                            </select>
                            @error('structure_id')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" value="{{ old('email') }}"
                                   required>
                            @error('email')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="telephone" class="form-label">Numéro de téléphone</label>
                            <input type="text" class="form-control" name="telephone" id="telephone"
                                   value="{{ old('telephone') }}" required>
                            @error('telephone')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Champs conditionnels --}}
                        <div class="mb-3 d-none" id="groupe_categorie">
                            <label for="categorie_socioprofessionnelle" class="form-label">Catégorie
                                socio-professionnelle</label>
                            <input type="text" class="form-control" name="categorie_socioprofessionnelle"
                                   id="categorie_socioprofessionnelle"
                                   value="{{ old('categorie_socioprofessionnelle') }}">
                            @error('categorie_socioprofessionnelle')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 d-none" id="groupe_date_prise_service">
                            <label for="date_prise_service" class="form-label">Date de prise de service</label>
                            <input type="date" class="form-control" name="date_prise_service" id="date_prise_service"
                                   value="{{ old('date_prise_service') }}">
                            @error('date_prise_service')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 d-none" id="groupe_date_fin_service">
                            <label for="date_fin_service" class="form-label">Date de fin de service</label>
                            <input type="date" class="form-control" name="date_fin_service" id="date_fin_service"
                                   value="{{ old('date_fin_service') }}">
                            @error('date_fin_service')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 d-none" id="groupe_date_retraite">
                            <label for="date_depart_retraite" class="form-label">Date de départ à la retraite</label>
                            <input type="date" class="form-control" name="date_depart_retraite"
                                   id="date_depart_retraite" value="{{ old('date_depart_retraite') }}">
                            @error('date_depart_retraite')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="fichiers" class="form-label">Fichiers justificatifs</label>
                            <input type="file" class="form-control" name="fichiers[]" id="fichiers" multiple>
                            @error('fichiers')
                            <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Soumettre la demande</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script>
        const champsMapping = {
            'categorie_socioprofessionnelle': 'groupe_categorie',
            'date_prise_service': 'groupe_date_prise_service',
            'date_fin_service': 'groupe_date_fin_service',
            'date_depart_retraite': 'groupe_date_retraite',
        };

        document.getElementById('type_document_id').addEventListener('change', function () {
            const option = this.selectedOptions[0];
            const champs = JSON.parse(option.dataset.champs || '{}');

            // Réinitialiser tous les champs conditionnels
            Object.values(champsMapping).forEach(id => {
                document.getElementById(id).classList.add('d-none');
                document.querySelector(`#${id} input`)?.removeAttribute('required');
            });

            // Activer les champs requis
            Object.entries(champs).forEach(([champ, requis]) => {
                const groupe = champsMapping[champ];
                if (groupe) {
                    document.getElementById(groupe).classList.remove('d-none');
                    if (requis) {
                        document.querySelector(`#${groupe} input`)?.setAttribute('required', 'required');
                    }
                }
            });
        });

        $('#statut').on('change', function () {
            const statut = $(this).val();

            if (statut === 'étatique') {
                $('input[name="matricule"]').prop('required', true).closest('.mb-3').removeClass('d-none');
            } else {
                $('input[name="matricule"]').prop('required', false).closest('.mb-3').addClass('d-none');
            }
        });

        // Cacher le champ matricule par défaut
        $(document).ready(function () {
            $('input[name="matricule"]').closest('.mb-3').addClass('d-none');
        });
    </script>
@endsection
