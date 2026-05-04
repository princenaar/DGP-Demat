@extends('layouts.app')

@section('header')
    Workflow
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="mb-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-senegal-green">Type de demande</p>
        <div class="mt-2 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-ink-900">{{ $typeDocument->nom }}</h2>
                <p class="mt-1 text-sm text-ink-600">Code : <span class="font-semibold text-ink-800">{{ $typeDocument->code ?? 'Non renseigné' }}</span></p>
                <p class="mt-3 max-w-3xl text-sm text-ink-700">Le workflow définit les changements d’état possibles, le rôle autorisé à les déclencher et les transitions automatiques du circuit.</p>
            </div>
            <a href="{{ route('settings.type-documents.index') }}" class="inline-flex w-fit rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700 hover:text-senegal-green">
                Retour aux types
            </a>
        </div>
    </div>

    <section class="mb-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-ink-900">Ajouter une transition</h2>
            <p class="mt-1 text-sm text-ink-600">Définissez une action possible entre deux états pour ce type de demande.</p>
        </div>

        <form method="POST" action="{{ route('settings.type-documents.workflow.store', $typeDocument) }}" class="space-y-5">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                @include('settings.workflow.fields', ['transition' => $transition])
            </div>
            <button class="rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800" type="submit">Ajouter la transition</button>
        </form>
    </section>

    <section class="rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-ink-900">Transitions configurées</h2>
            <p class="mt-1 text-sm text-ink-600">{{ $typeDocument->workflowTransitions->count() }} transition(s) définie(s) pour {{ $typeDocument->nom }}.</p>
        </div>

        @forelse($typeDocument->workflowTransitions as $transition)
            <form method="POST" action="{{ route('settings.type-documents.workflow.update', [$typeDocument, $transition]) }}" class="mb-4 rounded-lg border border-ink-100 bg-white p-4">
                @csrf
                @method('PUT')
                <div class="grid gap-4 md:grid-cols-2">
                    @include('settings.workflow.fields', ['transition' => $transition])
                </div>
                <div class="mt-4 flex gap-3">
                    <button class="font-semibold text-senegal-green" type="submit">Enregistrer</button>
                    <button class="font-semibold text-senegal-red" type="submit" form="delete-transition-{{ $transition->id }}">Supprimer</button>
                </div>
            </form>
            <form id="delete-transition-{{ $transition->id }}" method="POST" action="{{ route('settings.type-documents.workflow.destroy', [$typeDocument, $transition]) }}">
                @csrf
                @method('DELETE')
            </form>
        @empty
            <div class="rounded-md border border-dashed border-ink-200 p-6 text-sm text-ink-600">
                Aucune transition n’est encore configurée pour ce type de demande.
            </div>
        @endforelse
    </section>
@endsection
