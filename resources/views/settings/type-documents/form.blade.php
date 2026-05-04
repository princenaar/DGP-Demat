@extends('layouts.app')

@section('header')
    {{ $typeDocument->exists ? 'Modifier le type de demande' : 'Ajouter un type de demande' }}
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="mb-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-senegal-green">Paramétrage métier</p>
        <h2 class="mt-2 text-xl font-bold text-ink-900">{{ $typeDocument->exists ? $typeDocument->nom : 'Nouveau type de demande' }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-ink-700">
            Le type de demande pilote le formulaire public, les pièces à fournir, l’agent d’imputation automatique et les conditions utilisées pour la validation automatique.
        </p>
    </div>

    <form method="POST" action="{{ $typeDocument->exists ? route('settings.type-documents.update', $typeDocument) : route('settings.type-documents.store') }}" class="space-y-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        @csrf
        @if($typeDocument->exists)
            @method('PUT')
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm font-semibold text-ink-700">
                Nom affiché au demandeur
                <input name="nom" value="{{ old('nom', $typeDocument->nom) }}" placeholder="Ex. Attestation de travail" class="mt-1 w-full rounded-md border-gray-300" required>
                <span class="mt-1 block text-xs font-normal text-ink-500">Intitulé visible dans le portail public et les tableaux de suivi.</span>
                @error('nom')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>

            <label class="block text-sm font-semibold text-ink-700">
                Code métier
                <input name="code" value="{{ old('code', $typeDocument->code) }}" placeholder="Ex. TRV" class="mt-1 w-full rounded-md border-gray-300">
                <span class="mt-1 block text-xs font-normal text-ink-500">Code court utilisé pour identifier le type, notamment les modèles PDF.</span>
                @error('code')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>

            <label class="block text-sm font-semibold text-ink-700">
                Icône d’affichage
                <input name="icone" value="{{ old('icone', $typeDocument->icone) }}" placeholder="Ex. briefcase" class="mt-1 w-full rounded-md border-gray-300">
                <span class="mt-1 block text-xs font-normal text-ink-500">Nom d’icône utilisé par l’interface, si le type est présenté sous forme de carte.</span>
                @error('icone')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>

            <label class="block text-sm font-semibold text-ink-700">
                Statut éligible
                <select name="eligibilite" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Tous statuts</option>
                    <option value="etatique" @selected(in_array(old('eligibilite', $typeDocument->eligibilite), ['etatique', 'étatique'], true))>Étatique</option>
                    <option value="contractuel" @selected(old('eligibilite', $typeDocument->eligibilite) === 'contractuel')>Contractuel</option>
                </select>
                <span class="mt-1 block text-xs font-normal text-ink-500">Restreint la demande à un statut précis. Laissez vide si le type concerne tout le monde.</span>
                @error('eligibilite')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>

            <label class="block text-sm font-semibold text-ink-700 md:col-span-2">
                Agent par défaut pour l’imputation automatique
                <select name="default_agent_id" class="mt-1 w-full rounded-md border-gray-300">
                    <option value="">Aucun agent par défaut</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((int) old('default_agent_id', $typeDocument->default_agent_id) === $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-xs font-normal text-ink-500">Quand la demande est réceptionnée, elle peut être imputée automatiquement à cet agent actif.</span>
                @error('default_agent_id')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>

            <label class="block text-sm font-semibold text-ink-700 md:col-span-2">
                Description publique
                <textarea name="description" class="mt-1 w-full rounded-md border-gray-300" rows="3" placeholder="Expliquez brièvement à quoi sert cette demande.">{{ old('description', $typeDocument->description) }}</textarea>
                <span class="mt-1 block text-xs font-normal text-ink-500">Texte d’aide court destiné aux agents ou aux demandeurs selon l’écran d’affichage.</span>
                @error('description')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <fieldset class="rounded-md border border-ink-100 bg-ink-50 p-4">
            <legend class="px-1 text-sm font-semibold text-ink-900">Champs requis pour la validation automatique</legend>
            <p class="mb-3 mt-1 text-xs text-ink-600">Cochez uniquement les informations qui doivent être renseignées pour que la validation automatique puisse s’appliquer.</p>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($fields as $field => $label)
                    <label class="flex items-center gap-2 text-sm text-ink-700">
                        <input type="checkbox" name="champs_requis[{{ $field }}]" value="1" @checked((bool) old("champs_requis.$field", $typeDocument->champs_requis[$field] ?? false))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('champs_requis')
                <span class="mt-2 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
            @enderror
        </fieldset>

        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
            <a href="{{ route('settings.type-documents.index') }}" class="rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700">Annuler</a>
        </div>
    </form>
@endsection
