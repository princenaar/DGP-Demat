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
@endsection
