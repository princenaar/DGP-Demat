@extends('layouts.app')

@section('header')
    Paramètres
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('settings.type-documents.index') }}" class="rounded-lg border border-ink-100 bg-white p-5 shadow-sm hover:border-senegal-green">
            <p class="text-sm text-ink-600">Types de demandes</p>
            <p class="mt-2 text-3xl font-bold text-ink-900">{{ $typeDocumentsCount }}</p>
            <p class="mt-2 text-sm text-ink-600">{{ $piecesCount }} pièces, {{ $workflowTransitionsCount }} transitions</p>
        </a>
        <a href="{{ route('settings.referentiels.index') }}" class="rounded-lg border border-ink-100 bg-white p-5 shadow-sm hover:border-senegal-green">
            <p class="text-sm text-ink-600">Référentiels</p>
            <p class="mt-2 text-3xl font-bold text-ink-900">{{ $structuresCount + $categoriesCount + $etatsCount }}</p>
            <p class="mt-2 text-sm text-ink-600">Structures, catégories, états figés</p>
        </a>
        <a href="{{ route('settings.users.index') }}" class="rounded-lg border border-ink-100 bg-white p-5 shadow-sm hover:border-senegal-green">
            <p class="text-sm text-ink-600">Utilisateurs</p>
            <p class="mt-2 text-3xl font-bold text-ink-900">{{ $usersCount }}</p>
            <p class="mt-2 text-sm text-ink-600">{{ $inactiveUsersCount }} compte(s) désactivé(s)</p>
        </a>
    </div>

    <section class="mt-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <div class="max-w-xl">
            <h2 class="text-lg font-semibold text-ink-900">Liens de compléments</h2>
            <p class="mt-1 text-sm text-ink-600">Durée de validité des liens envoyés aux demandeurs pour compléter leur dossier.</p>

            <form method="POST" action="{{ route('settings.application.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="complement_link_validity_days" class="block text-sm font-medium text-ink-700">Validité des liens (jours)</label>
                    <x-text-input
                        id="complement_link_validity_days"
                        name="complement_link_validity_days"
                        type="number"
                        min="1"
                        max="15"
                        class="mt-1 block w-40"
                        :value="old('complement_link_validity_days', $complementLinkValidityDays)"
                        required
                    />
                    <x-input-error :messages="$errors->get('complement_link_validity_days')" class="mt-2" />
                    <p class="mt-2 text-xs text-ink-500">Valeur autorisée : 1 à 15 jours.</p>
                </div>

                <x-primary-button>Enregistrer</x-primary-button>
            </form>
        </div>
    </section>
@endsection
