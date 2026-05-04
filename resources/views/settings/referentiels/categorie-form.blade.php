@extends('layouts.app')

@section('header')
    {{ $categorie->exists ? 'Modifier la catégorie' : 'Ajouter une catégorie' }}
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="mb-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-senegal-green">Référentiel métier</p>
        <h2 class="mt-2 text-xl font-bold text-ink-900">{{ $categorie->exists ? $categorie->libelle : 'Nouvelle catégorie socioprofessionnelle' }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-ink-700">Les catégories socioprofessionnelles qualifient le profil du demandeur et peuvent être utilisées dans les règles propres à certains types de demandes.</p>
    </div>

    <form method="POST" action="{{ $categorie->exists ? route('settings.categories.update', $categorie) : route('settings.categories.store') }}" class="space-y-5 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        @csrf
        @if($categorie->exists)
            @method('PUT')
        @endif

        <label class="block text-sm font-semibold text-ink-700">
            Libellé affiché
            <input name="libelle" value="{{ old('libelle', $categorie->libelle) }}" placeholder="Ex. Infirmier" class="mt-1 w-full rounded-md border-gray-300" required>
            <span class="mt-1 block text-xs font-normal text-ink-500">Nom visible dans les formulaires et les tableaux de suivi.</span>
            @error('libelle')
                <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
            @enderror
        </label>

        <label class="block text-sm font-semibold text-ink-700">
            Code interne
            <input name="code" value="{{ old('code', $categorie->code) }}" placeholder="Ex. INF" class="mt-1 w-full rounded-md border-gray-300" required>
            <span class="mt-1 block text-xs font-normal text-ink-500">Abréviation stable pour identifier cette catégorie dans les exports ou règles métier.</span>
            @error('code')
                <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
            @enderror
        </label>

        <label class="block text-sm font-semibold text-ink-700">
            Ordre d’affichage
            <input name="ordre" type="number" min="0" value="{{ old('ordre', $categorie->ordre ?? 0) }}" class="mt-1 w-full rounded-md border-gray-300" required>
            <span class="mt-1 block text-xs font-normal text-ink-500">Plus le nombre est petit, plus la catégorie apparaît haut dans les listes.</span>
            @error('ordre')
                <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
            @enderror
        </label>

        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
            <a href="{{ route('settings.referentiels.index') }}" class="rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700">Annuler</a>
        </div>
    </form>
@endsection
