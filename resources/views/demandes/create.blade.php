@extends('layouts.public')

@php
    $requiredMark = '<span class="text-red-600" aria-hidden="true">*</span>';
    $oldStructureId = old('structure_id');
    $oldTypeId = old('type_document_id');
    $usesE2eDatabase = str_ends_with((string) config('database.connections.sqlite.database'), 'e2e.sqlite');
    $usesTestingRecaptcha = app()->environment('testing') || $usesE2eDatabase;
    $typePayload = $types->map(fn ($type) => [
        'id' => (string) $type->id,
        'nom' => $type->nom,
        'code' => $type->code,
        'description' => $type->description,
        'icone' => $type->icone,
        'champs' => $type->champs_requis ?? [],
        'pieces' => $type->piecesRequises->map(fn ($piece) => [
            'libelle' => $piece->libelle,
            'description' => $piece->description,
            'obligatoire' => $piece->obligatoire,
        ])->values(),
    ])->values();
@endphp

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 rounded bg-senegal-green/10 border-l-4 border-senegal-green p-4 text-senegal-green font-medium">
                {{ session('success') }}
            </div>
            @if(session('warning'))
                <div class="-mt-3 mb-6 rounded bg-amber-50 border-l-4 border-amber-400 p-4 text-amber-800 font-medium">
                    {{ session('warning') }}
                </div>
            @endif
        @else
            <div class="bg-white rounded-lg shadow border border-gray-100 p-6 sm:p-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-ink-900">Faire une demande</h1>
                        <p class="mt-1 text-sm text-gray-600">Les champs marqués d'un astérisque sont obligatoires.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div id="validation-errors" class="mt-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" role="alert" tabindex="-1">
                        <p class="font-semibold">Veuillez corriger les informations signalées.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('demandes.store') }}" enctype="multipart/form-data" class="mt-6"
                      x-data="{
                          step: @js($oldTypeId ? 2 : 1),
                          types: @js($typePayload),
                          selectedTypeId: @js((string) $oldTypeId),
                          champs: {},
                          statut: @js(old('statut', '')),
                          structures: @js($structures->map(fn ($structure) => ['id' => (string) $structure->id, 'nom' => $structure->nom])->values()),
                          structureSearch: '',
                          selectedStructureId: @js((string) $oldStructureId),
                          showStructureList: false,
                          files: [],
                          fieldErrors: @js($errors->keys()),
                          fieldState: {},
                          init() {
                              this.syncSelectedType();
                          },
                          get selectedType() {
                              return this.types.find((type) => type.id === this.selectedTypeId) || null;
                          },
                          get selectedPieces() {
                              return this.selectedType ? this.selectedType.pieces : [];
                          },
                          get selectedStructureName() {
                              const selected = this.structures.find((structure) => structure.id === this.selectedStructureId);

                              return selected ? selected.nom : '';
                          },
                          selectType(typeId) {
                              this.selectedTypeId = typeId;
                              this.syncSelectedType();
                              this.step = 2;
                              this.clearFieldState('type_document_id');
                          },
                          syncSelectedType() {
                              this.champs = this.selectedType ? this.selectedType.champs : {};
                          },
                          visible(champ) {
                              return Object.prototype.hasOwnProperty.call(this.champs, champ);
                          },
                          required(champ) {
                              return Boolean(this.champs[champ]);
                          },
                          requiredLabel(champ) {
                              return this.required(champ) ? '*' : '';
                          },
                          fieldClass(name) {
                              if (this.fieldState[name] === 'valid') {
                                  return 'border-green-500 focus:border-green-600 focus:ring-green-600';
                              }

                              if (this.fieldErrors.includes(name) && this.fieldState[name] !== 'valid') {
                                  return 'border-red-400 focus:border-red-500 focus:ring-red-500';
                              }

                              return '';
                          },
                          validateField(name, element) {
                              if (! element.offsetParent && element.type !== 'hidden') {
                                  return;
                              }

                              this.fieldState[name] = element.checkValidity() ? 'valid' : 'invalid';
                          },
                          clearFieldState(name) {
                              this.fieldState[name] = 'editing';
                          },
                          filteredStructures() {
                              const term = this.structureSearch.toLowerCase().trim();

                              if (! term) {
                                  return this.structures.slice(0, 8);
                              }

                              return this.structures.filter((structure) => structure.nom.toLowerCase().includes(term)).slice(0, 8);
                          },
                          openStructureList() {
                              this.showStructureList = true;
                              this.$nextTick(() => this.$refs.structureSearchInput?.focus());
                          },
                          selectStructure(structure) {
                              this.selectedStructureId = structure.id;
                              this.structureSearch = '';
                              this.showStructureList = false;
                              this.clearFieldState('structure_id');
                              this.$nextTick(() => this.validateField('structure_id', this.$refs.structureSelect));
                          },
                          updateFiles(event) {
                              this.files = Array.from(event.target.files).map((file) => ({
                                  name: file.name,
                                  type: file.type || 'Type inconnu',
                                  size: this.formatFileSize(file.size),
                              }));
                          },
                          formatFileSize(size) {
                              if (size < 1024) {
                                  return `${size} o`;
                              }

                              if (size < 1024 * 1024) {
                                  return `${Math.round(size / 1024)} Ko`;
                              }

                              return `${(size / 1024 / 1024).toFixed(1)} Mo`;
                          },
                          maskMatricule(event) {
                              const value = event.target.value.replace(/[^0-9a-z]/gi, '').toUpperCase();
                              const digits = value.replace(/\D/g, '').slice(0, 6);
                              const letter = value.replace(/[0-9]/g, '').slice(0, 1);
                              event.target.value = `${digits}${letter}`;
                          },
                          maskTelephone(event) {
                              const digits = event.target.value.replace(/\D/g, '').replace(/^221/, '').slice(0, 9);
                              const parts = [
                                  digits.slice(0, 2),
                                  digits.slice(2, 5),
                                  digits.slice(5, 7),
                                  digits.slice(7, 9),
                              ].filter(Boolean);
                              event.target.value = parts.length ? `+221 ${parts.join(' ')}` : '+221 ';
                          },
                      }">
                    @csrf
                    <input type="hidden" name="type_document_id" x-model="selectedTypeId">

                    <section x-show="step === 1" x-cloak>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach ($types as $type)
                                <button type="button" x-on:click="selectType(@js((string) $type->id))" class="group flex min-h-32 items-start gap-4 rounded-md border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-senegal-green hover:bg-senegal-green/5 focus:border-senegal-green focus:outline-none focus:ring-2 focus:ring-senegal-green/30">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-senegal-green/10 text-sm font-bold text-senegal-green">
                                        {{ $type->code }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-base font-semibold text-ink-900">{{ $type->nom }}</span>
                                        @if($type->description)
                                            <span class="mt-1 block text-sm text-gray-600">{{ $type->description }}</span>
                                        @endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('type_document_id')" class="mt-4 rounded bg-red-50 px-3 py-2" />
                    </section>

                    <section x-show="step === 2" x-cloak class="space-y-5">
                        <div class="flex items-start justify-between gap-4 rounded-md border border-gray-200 bg-gray-50 p-4">
                            <div>
                                <p class="text-xs font-semibold uppercase text-gray-500">Type de document</p>
                                <p class="mt-1 font-semibold text-ink-900" x-text="selectedType?.nom"></p>
                            </div>
                            <button type="button" class="text-sm font-medium text-senegal-green hover:text-green-800" x-on:click="step = 1">Changer</button>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="prenom" class="block text-sm font-medium text-gray-700">Prénom {!! $requiredMark !!}</label>
                                <x-text-input type="text" name="prenom" id="prenom" class="mt-1 block w-full" x-bind:class="fieldClass('prenom')" x-on:input="clearFieldState('prenom')" x-on:blur="validateField('prenom', $event.target)" :value="old('prenom')" required />
                                <x-input-error :messages="$errors->get('prenom')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                            </div>

                            <div>
                                <label for="nom" class="block text-sm font-medium text-gray-700">Nom {!! $requiredMark !!}</label>
                                <x-text-input type="text" name="nom" id="nom" class="mt-1 block w-full" x-bind:class="fieldClass('nom')" x-on:input="clearFieldState('nom')" x-on:blur="validateField('nom', $event.target)" :value="old('nom')" required />
                                <x-input-error :messages="$errors->get('nom')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                            </div>
                        </div>

                        <fieldset>
                            <legend class="block text-sm font-medium text-gray-700">Statut {!! $requiredMark !!}</legend>
                            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="flex cursor-pointer items-center rounded-md border border-gray-200 p-3 hover:border-senegal-green">
                                    <input type="radio" name="statut" value="étatique" x-model="statut" class="text-senegal-green focus:ring-senegal-green" required>
                                    <span class="ml-3 text-sm font-medium text-ink-900">Étatique</span>
                                </label>
                                <label class="flex cursor-pointer items-center rounded-md border border-gray-200 p-3 hover:border-senegal-green">
                                    <input type="radio" name="statut" value="contractuel" x-model="statut" class="text-senegal-green focus:ring-senegal-green" required>
                                    <span class="ml-3 text-sm font-medium text-ink-900">Contractuel</span>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('statut')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                        </fieldset>

                        <div x-show="statut === 'étatique'" x-cloak>
                            <label for="matricule" class="block text-sm font-medium text-gray-700">Matricule <span class="text-red-600" aria-hidden="true" x-show="statut === 'étatique'">*</span></label>
                            <x-text-input type="text" name="matricule" id="matricule" class="mt-1 block w-full uppercase" x-bind:class="fieldClass('matricule')" :value="old('matricule')" placeholder="000000A" maxlength="7" pattern="[0-9]{6}[A-Za-z]" x-on:input="maskMatricule($event); clearFieldState('matricule')" x-on:blur="validateField('matricule', $event.target)" x-bind:required="statut === 'étatique'" />
                            <x-input-error :messages="$errors->get('matricule')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                        </div>

                        <div>
                            <label for="nin" class="block text-sm font-medium text-gray-700">Numéro d'Identification National {!! $requiredMark !!}</label>
                            <x-text-input type="text" name="nin" id="nin" class="mt-1 block w-full" x-bind:class="fieldClass('nin')" :value="old('nin')" inputmode="numeric" minlength="13" maxlength="13" pattern="[0-9]{13}" x-on:input="clearFieldState('nin')" x-on:blur="validateField('nin', $event.target)" required />
                            <x-input-error :messages="$errors->get('nin')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                        </div>

                        <div class="relative" x-on:click.outside="showStructureList = false" x-on:keydown.escape.window="showStructureList = false">
                            <label for="structure_id" class="block text-sm font-medium text-gray-700">Structure {!! $requiredMark !!}</label>
                            <select name="structure_id" id="structure_id" x-ref="structureSelect" x-model="selectedStructureId" class="sr-only" required x-on:change="clearFieldState('structure_id')" x-on:blur="validateField('structure_id', $event.target)">
                                <option value="">Sélectionner une structure</option>
                                @foreach($structures as $structure)
                                    <option value="{{ $structure->id }}" @selected((string) $oldStructureId === (string) $structure->id)>{{ $structure->nom }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="mt-1 flex w-full items-center justify-between rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-senegal-green focus:outline-none focus:ring-1 focus:ring-senegal-green" x-bind:class="fieldClass('structure_id')" x-on:click="showStructureList ? showStructureList = false : openStructureList()" x-bind:aria-expanded="showStructureList.toString()" aria-controls="structure_options" role="combobox">
                                <span class="truncate" x-bind:class="selectedStructureName ? 'text-ink-900' : 'text-gray-500'" x-text="selectedStructureName || 'Sélectionner une structure'"></span>
                                <svg class="ml-3 h-5 w-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div id="structure_options" x-show="showStructureList" x-cloak class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg">
                                <div class="border-b border-gray-100 p-2">
                                    <input type="search" x-ref="structureSearchInput" class="block w-full rounded-md border-gray-300 text-sm focus:border-senegal-green focus:ring-senegal-green" placeholder="Rechercher une structure" x-model="structureSearch" x-on:input="clearFieldState('structure_id')">
                                </div>
                                <div class="max-h-60 overflow-auto py-1" role="listbox">
                                <template x-for="structure in filteredStructures()" :key="structure.id">
                                    <button type="button" class="flex w-full items-center justify-between px-4 py-2 text-left text-sm hover:bg-senegal-green/10 focus:bg-senegal-green/10 focus:outline-none" x-bind:class="selectedStructureId === structure.id ? 'bg-senegal-green/10 text-senegal-green' : 'text-ink-900'" x-on:click="selectStructure(structure)" role="option" x-bind:aria-selected="(selectedStructureId === structure.id).toString()">
                                        <span x-text="structure.nom"></span>
                                        <span x-show="selectedStructureId === structure.id" aria-hidden="true">✓</span>
                                    </button>
                                </template>
                                <p class="px-4 py-3 text-sm text-gray-500" x-show="filteredStructures().length === 0">Aucune structure trouvée.</p>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('structure_id')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email {!! $requiredMark !!}</label>
                                <x-text-input type="email" name="email" id="email" class="mt-1 block w-full" x-bind:class="fieldClass('email')" x-on:input="clearFieldState('email')" x-on:blur="validateField('email', $event.target)" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                            </div>

                            <div>
                                <label for="telephone" class="block text-sm font-medium text-gray-700">Numéro de téléphone {!! $requiredMark !!}</label>
                                <x-text-input type="text" name="telephone" id="telephone" class="mt-1 block w-full" x-bind:class="fieldClass('telephone')" :value="old('telephone', '+221 ')" placeholder="+221 00 000 00 00" maxlength="17" pattern="\\+221 [0-9]{2} [0-9]{3} [0-9]{2} [0-9]{2}" x-on:input="maskTelephone($event); clearFieldState('telephone')" x-on:blur="validateField('telephone', $event.target)" required />
                                <x-input-error :messages="$errors->get('telephone')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                            </div>
                        </div>

                        <div x-show="visible('categorie_socioprofessionnelle_id')" x-cloak>
                            <label for="categorie_socioprofessionnelle_id" class="block text-sm font-medium text-gray-700">Catégorie socio-professionnelle <span class="text-red-600" aria-hidden="true" x-text="requiredLabel('categorie_socioprofessionnelle_id')"></span></label>
                            <select name="categorie_socioprofessionnelle_id" id="categorie_socioprofessionnelle_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-senegal-green focus:ring-senegal-green" x-bind:class="fieldClass('categorie_socioprofessionnelle_id')" x-on:input="clearFieldState('categorie_socioprofessionnelle_id')" x-on:blur="validateField('categorie_socioprofessionnelle_id', $event.target)" x-bind:required="required('categorie_socioprofessionnelle_id')">
                                <option value="">Sélectionner une catégorie</option>
                                @foreach($categoriesSocioprofessionnelles as $categorie)
                                    <option value="{{ $categorie->id }}" @selected((string) old('categorie_socioprofessionnelle_id') === (string) $categorie->id)>{{ $categorie->libelle }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('categorie_socioprofessionnelle_id')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div x-show="visible('date_prise_service')" x-cloak>
                                <label for="date_prise_service" class="block text-sm font-medium text-gray-700">Date de prise de service <span class="text-red-600" aria-hidden="true" x-text="requiredLabel('date_prise_service')"></span></label>
                                <x-text-input type="date" name="date_prise_service" id="date_prise_service" class="mt-1 block w-full" x-bind:class="fieldClass('date_prise_service')" :value="old('date_prise_service')" x-on:input="clearFieldState('date_prise_service')" x-on:blur="validateField('date_prise_service', $event.target)" x-bind:required="required('date_prise_service')" />
                                <x-input-error :messages="$errors->get('date_prise_service')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                            </div>

                            <div x-show="visible('date_fin_service')" x-cloak>
                                <label for="date_fin_service" class="block text-sm font-medium text-gray-700">Date de fin de service <span class="text-red-600" aria-hidden="true" x-text="requiredLabel('date_fin_service')"></span></label>
                                <x-text-input type="date" name="date_fin_service" id="date_fin_service" class="mt-1 block w-full" x-bind:class="fieldClass('date_fin_service')" :value="old('date_fin_service')" x-on:input="clearFieldState('date_fin_service')" x-on:blur="validateField('date_fin_service', $event.target)" x-bind:required="required('date_fin_service')" />
                                <x-input-error :messages="$errors->get('date_fin_service')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                            </div>

                            <div x-show="visible('date_depart_retraite')" x-cloak>
                                <label for="date_depart_retraite" class="block text-sm font-medium text-gray-700">Date de départ à la retraite <span class="text-red-600" aria-hidden="true" x-text="requiredLabel('date_depart_retraite')"></span></label>
                                <x-text-input type="date" name="date_depart_retraite" id="date_depart_retraite" class="mt-1 block w-full" x-bind:class="fieldClass('date_depart_retraite')" :value="old('date_depart_retraite')" x-on:input="clearFieldState('date_depart_retraite')" x-on:blur="validateField('date_depart_retraite', $event.target)" x-bind:required="required('date_depart_retraite')" />
                                <x-input-error :messages="$errors->get('date_depart_retraite')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                            </div>
                        </div>

                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4" x-show="selectedPieces.length > 0" x-cloak>
                            <p class="text-sm font-semibold text-ink-900">Pièces à fournir</p>
                            <ul class="mt-3 space-y-2">
                                <template x-for="piece in selectedPieces" :key="piece.libelle">
                                    <li class="rounded border border-gray-200 bg-white p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-sm font-medium text-ink-900" x-text="piece.libelle"></span>
                                            <span class="rounded-full px-2 py-1 text-xs font-semibold" x-bind:class="piece.obligatoire ? 'bg-senegal-red/10 text-senegal-red' : 'bg-gray-100 text-gray-600'" x-text="piece.obligatoire ? 'Obligatoire' : 'Facultatif'"></span>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-600" x-show="piece.description" x-text="piece.description"></p>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div>
                            <label for="fichiers" class="block text-sm font-medium text-gray-700">Fichiers justificatifs</label>
                            <label for="fichiers" class="mt-1 flex cursor-pointer flex-col items-center justify-center rounded-md border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-8 text-center transition hover:border-senegal-green hover:bg-senegal-green/5">
                                <span class="text-sm font-semibold text-ink-900">Ajouter des pièces justificatives</span>
                                <span class="mt-1 text-xs text-gray-500">PDF, JPG ou PNG, 5 fichiers maximum, 5 Mo par fichier</span>
                            </label>
                            <input type="file" name="fichiers[]" id="fichiers" class="sr-only" multiple accept=".pdf,.jpg,.jpeg,.png" x-on:change="updateFiles($event)">
                            <div class="mt-3 grid gap-3" x-show="files.length > 0" x-cloak>
                                <template x-for="file in files" :key="file.name">
                                    <div class="flex items-center justify-between rounded-md border border-gray-200 bg-white px-4 py-3 shadow-sm">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-ink-900" x-text="file.name"></p>
                                            <p class="text-xs text-gray-500"><span x-text="file.type"></span> · <span x-text="file.size"></span></p>
                                        </div>
                                        <span class="ml-3 rounded-full bg-senegal-green/10 px-3 py-1 text-xs font-semibold text-senegal-green">Prêt</span>
                                    </div>
                                </template>
                            </div>
                            <x-input-error :messages="$errors->get('fichiers')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                            <x-input-error :messages="$errors->get('fichiers.*')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">reCAPTCHA {!! $requiredMark !!}</label>
                            @if ($usesTestingRecaptcha)
                                <input type="hidden" name="g-recaptcha-response" value="e2e-valid-recaptcha">
                                <div class="mt-2 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                                    Vérification automatisée active pour les tests.
                                </div>
                            @elseif ($recaptchaSiteKey)
                                <div class="mt-2 overflow-hidden rounded-md @error('g-recaptcha-response') ring-1 ring-red-400 @enderror">
                                    <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                                </div>
                            @else
                                <div class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    La clé publique reCAPTCHA n'est pas configurée.
                                </div>
                            @endif
                            <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-2 rounded bg-red-50 px-3 py-2" />
                        </div>

                        <x-primary-button>Soumettre la demande</x-primary-button>
                    </section>
                </form>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    @if ($errors->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                document.getElementById('validation-errors')?.focus();
            });
        </script>
    @endif

    @unless($usesTestingRecaptcha)
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endunless
@endpush
