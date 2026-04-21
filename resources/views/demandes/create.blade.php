@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 rounded bg-senegal-green/10 border-l-4 border-senegal-green p-4 text-senegal-green font-medium">
                {{ session('success') }}
            </div>
        @else
            <div class="bg-white rounded-lg shadow border border-gray-100 p-6 sm:p-8">
                <h1 class="text-2xl font-bold text-ink-900">Faire une demande</h1>

                <form method="POST" action="{{ route('demandes.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5"
                      x-data="{
                          champs: {},
                          statut: @js(old('statut', '')),
                          init() {
                              this.updateChamps(this.$refs.typeDocument);
                          },
                          updateChamps(select) {
                              this.champs = JSON.parse(select.selectedOptions[0]?.dataset.champs || '{}');
                          },
                          visible(champ) {
                              return Object.prototype.hasOwnProperty.call(this.champs, champ);
                          },
                          required(champ) {
                              return Boolean(this.champs[champ]);
                          },
                      }">
                    @csrf

                    <div>
                        <x-input-label for="type_document_id" value="Type de document" />
                        <select x-ref="typeDocument" x-on:change="updateChamps($event.target)" class="mt-1 block w-full rounded-md border-gray-300 focus:border-senegal-green focus:ring-senegal-green" name="type_document_id" id="type_document_id" required>
                            <option value="">-- Choisir --</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" data-champs='@json($type->champs_requis)' @selected(old('type_document_id') == $type->id)>
                                    {{ $type->nom }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type_document_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="prenom" value="Prénom" />
                            <x-text-input type="text" name="prenom" id="prenom" class="mt-1 block w-full" :value="old('prenom')" required />
                            <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="nom" value="Nom" />
                            <x-text-input type="text" name="nom" id="nom" class="mt-1 block w-full" :value="old('nom')" required />
                            <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="statut" value="Statut" />
                            <select x-model="statut" class="mt-1 block w-full rounded-md border-gray-300 focus:border-senegal-green focus:ring-senegal-green" name="statut" id="statut" required>
                                <option value="">-- Choisir --</option>
                                <option value="étatique">Étatique</option>
                                <option value="contractuel">Contractuel</option>
                            </select>
                            <x-input-error :messages="$errors->get('statut')" class="mt-2" />
                        </div>

                        <div x-show="statut === 'étatique'" x-cloak>
                            <x-input-label for="matricule" value="Matricule" />
                            <x-text-input type="text" name="matricule" id="matricule" class="mt-1 block w-full" :value="old('matricule')" x-bind:required="statut === 'étatique'" />
                            <x-input-error :messages="$errors->get('matricule')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="nin" value="Numéro d'Identification National" />
                        <x-text-input type="text" name="nin" id="nin" class="mt-1 block w-full" :value="old('nin')" required />
                        <x-input-error :messages="$errors->get('nin')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="structure_id" value="Structure" />
                        <select class="mt-1 block w-full rounded-md border-gray-300 focus:border-senegal-green focus:ring-senegal-green" name="structure_id" id="structure_id" required>
                            <option value="">-- Choisir --</option>
                            @foreach ($structures as $structure)
                                <option value="{{ $structure->id }}" @selected(old('structure_id') == $structure->id)>{{ $structure->nom }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('structure_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input type="email" name="email" id="email" class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="telephone" value="Numéro de téléphone" />
                            <x-text-input type="text" name="telephone" id="telephone" class="mt-1 block w-full" :value="old('telephone')" required />
                            <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
                        </div>
                    </div>

                    <div x-show="visible('categorie_socioprofessionnelle')" x-cloak>
                        <x-input-label for="categorie_socioprofessionnelle" value="Catégorie socio-professionnelle" />
                        <x-text-input type="text" name="categorie_socioprofessionnelle" id="categorie_socioprofessionnelle" class="mt-1 block w-full" :value="old('categorie_socioprofessionnelle')" x-bind:required="required('categorie_socioprofessionnelle')" />
                        <x-input-error :messages="$errors->get('categorie_socioprofessionnelle')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div x-show="visible('date_prise_service')" x-cloak>
                            <x-input-label for="date_prise_service" value="Date de prise de service" />
                            <x-text-input type="date" name="date_prise_service" id="date_prise_service" class="mt-1 block w-full" :value="old('date_prise_service')" x-bind:required="required('date_prise_service')" />
                            <x-input-error :messages="$errors->get('date_prise_service')" class="mt-2" />
                        </div>

                        <div x-show="visible('date_fin_service')" x-cloak>
                            <x-input-label for="date_fin_service" value="Date de fin de service" />
                            <x-text-input type="date" name="date_fin_service" id="date_fin_service" class="mt-1 block w-full" :value="old('date_fin_service')" x-bind:required="required('date_fin_service')" />
                            <x-input-error :messages="$errors->get('date_fin_service')" class="mt-2" />
                        </div>

                        <div x-show="visible('date_depart_retraite')" x-cloak>
                            <x-input-label for="date_depart_retraite" value="Date de départ à la retraite" />
                            <x-text-input type="date" name="date_depart_retraite" id="date_depart_retraite" class="mt-1 block w-full" :value="old('date_depart_retraite')" x-bind:required="required('date_depart_retraite')" />
                            <x-input-error :messages="$errors->get('date_depart_retraite')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="fichiers" value="Fichiers justificatifs" />
                        <x-text-input type="file" name="fichiers[]" id="fichiers" class="mt-1 block w-full" multiple />
                        <x-input-error :messages="$errors->get('fichiers')" class="mt-2" />
                    </div>

                    <x-primary-button>Soumettre la demande</x-primary-button>
                </form>
            </div>
        @endif
    </div>
@endsection
