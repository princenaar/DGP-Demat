@extends('layouts.app')

@section('header')
    Pièces à fournir
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="mb-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-senegal-green">Type de demande</p>
        <div class="mt-2 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-ink-900">{{ $typeDocument->nom }}</h2>
                <p class="mt-1 text-sm text-ink-600">
                    Code : <span class="font-semibold text-ink-800">{{ $typeDocument->code ?? 'Non renseigné' }}</span>
                    @if($typeDocument->eligibilite)
                        <span class="mx-2 text-ink-300">|</span>
                        Éligibilité : <span class="font-semibold text-ink-800">{{ ucfirst($typeDocument->eligibilite) }}</span>
                    @endif
                </p>
                @if($typeDocument->description)
                    <p class="mt-3 max-w-3xl text-sm text-ink-700">{{ $typeDocument->description }}</p>
                @endif
            </div>
            <a href="{{ route('settings.type-documents.index') }}" class="inline-flex w-fit rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700 hover:text-senegal-green">
                Retour aux types
            </a>
        </div>
    </div>

    <section class="mb-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-ink-900">Ajouter une pièce à fournir</h2>
            <p class="mt-1 text-sm text-ink-600">Définissez les documents que le demandeur devra joindre pour ce type de demande.</p>
        </div>

        <form method="POST" action="{{ route('settings.type-documents.pieces.store', $typeDocument) }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block text-sm font-semibold text-ink-700">
                    Libellé de la pièce
                    <input name="libelle" value="{{ old('libelle') }}" placeholder="Ex. Copie de la carte nationale d’identité" class="mt-1 w-full rounded-md border-gray-300" required>
                    <span class="mt-1 block text-xs font-normal text-ink-500">Nom affiché au demandeur dans le formulaire public.</span>
                    @error('libelle')
                        <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block text-sm font-semibold text-ink-700">
                    Ordre d’affichage
                    <input name="ordre" type="number" min="0" value="{{ old('ordre', $pieceRequise->ordre ?? 0) }}" class="mt-1 w-full rounded-md border-gray-300" required>
                    <span class="mt-1 block text-xs font-normal text-ink-500">Plus le nombre est petit, plus la pièce apparaît haut dans la liste.</span>
                    @error('ordre')
                        <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                    @enderror
                </label>
            </div>

            <label class="block text-sm font-semibold text-ink-700">
                Description ou consigne
                <textarea name="description" rows="3" placeholder="Ex. Document lisible, recto verso, au format PDF." class="mt-1 w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
                <span class="mt-1 block text-xs font-normal text-ink-500">Précisez le format attendu, les pages nécessaires ou toute consigne utile.</span>
                @error('description')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>

            <label class="flex items-start gap-3 rounded-md border border-ink-100 bg-ink-50 p-4 text-sm text-ink-700">
                <input type="checkbox" name="obligatoire" value="1" class="mt-1" @checked(old('obligatoire'))>
                <span>
                    <span class="block font-semibold text-ink-900">Pièce obligatoire</span>
                    <span class="block text-ink-600">Si cochée, cette pièce sera considérée comme requise pour la validation du dossier.</span>
                </span>
            </label>

            <button class="rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800" type="submit">
                Ajouter la pièce
            </button>
        </form>
    </section>

    <section class="rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-ink-900">Pièces configurées</h2>
            <p class="mt-1 text-sm text-ink-600">{{ $typeDocument->piecesRequises->count() }} pièce(s) définie(s) pour {{ $typeDocument->nom }}.</p>
        </div>

        @forelse($typeDocument->piecesRequises as $piece)
            <form method="POST" action="{{ route('settings.type-documents.pieces.update', [$typeDocument, $piece]) }}" class="mb-4 grid gap-4 rounded-lg border border-ink-100 bg-white p-4 md:grid-cols-6">
                @csrf
                @method('PUT')

                <label class="block text-sm font-semibold text-ink-700 md:col-span-2">
                    Libellé
                    <input name="libelle" value="{{ old("pieces.{$piece->id}.libelle", $piece->libelle) }}" class="mt-1 w-full rounded-md border-gray-300" required>
                </label>
                <label class="block text-sm font-semibold text-ink-700 md:col-span-2">
                    Description
                    <input name="description" value="{{ old("pieces.{$piece->id}.description", $piece->description) }}" class="mt-1 w-full rounded-md border-gray-300">
                </label>
                <label class="block text-sm font-semibold text-ink-700">
                    Ordre
                    <input name="ordre" type="number" min="0" value="{{ old("pieces.{$piece->id}.ordre", $piece->ordre) }}" class="mt-1 w-full rounded-md border-gray-300" required>
                </label>
                <label class="flex items-center gap-2 pt-6 text-sm font-semibold text-ink-700">
                    <input type="checkbox" name="obligatoire" value="1" @checked($piece->obligatoire)>
                    Obligatoire
                </label>
                <div class="flex gap-3 md:col-span-6">
                    <button class="font-semibold text-senegal-green" type="submit">Enregistrer</button>
                    <button class="font-semibold text-senegal-red" type="submit" form="delete-piece-{{ $piece->id }}">Supprimer</button>
                </div>
            </form>
            <form id="delete-piece-{{ $piece->id }}" method="POST" action="{{ route('settings.type-documents.pieces.destroy', [$typeDocument, $piece]) }}">
                @csrf
                @method('DELETE')
            </form>
        @empty
            <div class="rounded-md border border-dashed border-ink-200 p-6 text-sm text-ink-600">
                Aucune pièce à fournir n’est encore configurée pour ce type de demande.
            </div>
        @endforelse
    </section>
@endsection
