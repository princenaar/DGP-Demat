@extends('layouts.app')

@section('header')
    {{ $structure->exists ? 'Modifier la structure' : 'Ajouter une structure' }}
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="mb-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-senegal-green">Référentiel organisationnel</p>
        <h2 class="mt-2 text-xl font-bold text-ink-900">{{ $structure->exists ? $structure->nom : 'Nouvelle structure' }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-ink-700">Les structures servent à rattacher les demandes au service ou à la direction concernée.</p>
    </div>

    <form method="POST" action="{{ $structure->exists ? route('settings.structures.update', $structure) : route('settings.structures.store') }}" class="space-y-5 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        @csrf
        @if($structure->exists)
            @method('PUT')
        @endif

        <label class="block text-sm font-semibold text-ink-700">
            Nom de la structure
            <input name="nom" value="{{ old('nom', $structure->nom) }}" placeholder="Ex. Direction des Ressources Humaines" class="mt-1 w-full rounded-md border-gray-300" required>
            <span class="mt-1 block text-xs font-normal text-ink-500">Nom complet affiché dans les formulaires et les tableaux de suivi.</span>
            @error('nom')
                <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
            @enderror
        </label>

        <label class="block text-sm font-semibold text-ink-700">
            Code interne
            <input name="code" value="{{ old('code', $structure->code) }}" placeholder="Ex. DRH" class="mt-1 w-full rounded-md border-gray-300" required>
            <span class="mt-1 block text-xs font-normal text-ink-500">Abréviation stable utilisée pour identifier rapidement la structure.</span>
            @error('code')
                <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
            @enderror
        </label>

        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
            <a href="{{ route('settings.referentiels.index') }}" class="rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700">Annuler</a>
        </div>
    </form>
@endsection
