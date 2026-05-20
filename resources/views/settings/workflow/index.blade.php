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

    <section class="rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-ink-900">Transitions du circuit</h2>
            <p class="mt-1 text-sm text-ink-600">Les états, rôles et ordres sont fixes. Seul le déclenchement automatique peut être ajusté.</p>
        </div>

        @forelse($typeDocument->workflowTransitions as $transition)
            <form method="POST" action="{{ route('settings.type-documents.workflow.update', [$typeDocument, $transition]) }}" class="mb-3 rounded-lg border border-ink-100 bg-ink-50 p-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 lg:grid-cols-[4rem_1fr_1fr_12rem_16rem] lg:items-center">
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wide text-ink-500">Ordre</span>
                        <span class="mt-1 block text-sm font-bold text-ink-900">{{ $transition->ordre }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wide text-ink-500">État source</span>
                        <span class="mt-1 block text-sm font-semibold text-ink-900">{{ $transition->etatSource?->nom ?? 'N/A' }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wide text-ink-500">État cible</span>
                        <span class="mt-1 block text-sm font-semibold text-ink-900">{{ $transition->etatCible?->nom ?? 'N/A' }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wide text-ink-500">Rôle autorisé</span>
                        <span class="mt-1 block text-sm font-semibold text-ink-900">{{ $transition->role_requis ?: 'Aucun' }}</span>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <label class="flex items-start gap-3 text-sm text-ink-700">
                            <input type="checkbox" name="automatique" value="1" class="mt-1" @checked((bool) old('automatique', $transition->automatique))>
                            <span>
                                <span class="block font-semibold text-ink-900">Automatique</span>
                                <span class="block text-xs text-ink-600">Déclenchement sans action manuelle.</span>
                            </span>
                        </label>

                        <button class="w-fit rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800" type="submit">Enregistrer</button>
                    </div>
                </div>
            </form>
        @empty
            <div class="rounded-md border border-dashed border-ink-200 p-6 text-sm text-ink-600">
                Aucune transition n’est encore configurée pour ce type de demande.
            </div>
        @endforelse
    </section>
@endsection
