@extends('layouts.app')

@section('header')
    {{ $user->exists ? 'Modifier l’utilisateur' : 'Ajouter un utilisateur' }}
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="mb-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-senegal-green">Compte applicatif</p>
        <h2 class="mt-2 text-xl font-bold text-ink-900">{{ $user->exists ? $user->name : 'Nouvel utilisateur' }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-ink-700">
            Créez ou modifiez un compte interne. À la création, un lien de réinitialisation est envoyé pour permettre à l’utilisateur de définir son mot de passe.
        </p>
    </div>

    <form method="POST" action="{{ $user->exists ? route('settings.users.update', $user) : route('settings.users.store') }}" class="space-y-6 rounded-lg border border-ink-100 bg-white p-6 shadow-sm">
        @csrf
        @if($user->exists)
            @method('PUT')
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm font-semibold text-ink-700">
                Nom complet
                <input name="name" value="{{ old('name', $user->name) }}" placeholder="Ex. Awa Diop" class="mt-1 w-full rounded-md border-gray-300" required>
                <span class="mt-1 block text-xs font-normal text-ink-500">Nom affiché dans les historiques, les imputations et les tableaux de suivi.</span>
                @error('name')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>

            <label class="block text-sm font-semibold text-ink-700">
                Adresse email professionnelle
                <input name="email" type="email" value="{{ old('email', $user->email) }}" placeholder="Ex. awa.diop@sante.gouv.sn" class="mt-1 w-full rounded-md border-gray-300" required>
                <span class="mt-1 block text-xs font-normal text-ink-500">Adresse utilisée pour la connexion et les liens de réinitialisation.</span>
                @error('email')
                    <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <fieldset class="rounded-md border border-ink-100 bg-ink-50 p-4">
            <legend class="px-1 text-sm font-semibold text-ink-900">Rôles applicatifs</legend>
            <p class="mb-3 mt-1 text-xs text-ink-600">Attribuez uniquement les rôles correspondant aux responsabilités réelles de l’utilisateur.</p>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($roles as $role)
                    @php
                        $description = match ($role) {
                            'ADMIN' => 'Accès aux paramètres, aux utilisateurs et à la supervision.',
                            'ACCUEIL' => 'Réception des nouvelles demandes à l’entrée du circuit.',
                            'CHEF_DE_DIVISION' => 'Validation, refus et imputation des demandes réceptionnées.',
                            'AGENT' => 'Traitement des demandes qui lui sont imputées.',
                            'DRH' => 'Signature ou suspension des demandes envoyées en signature.',
                            default => 'Rôle applicatif.',
                        };
                    @endphp
                    <label class="flex items-start gap-3 rounded-md bg-white p-3 text-sm text-ink-700">
                        <input type="checkbox" name="roles[]" value="{{ $role }}" class="mt-1" @checked(in_array($role, old('roles', $user->exists ? $user->roles->pluck('name')->all() : []), true))>
                        <span>
                            <span class="block font-semibold text-ink-900">{{ $role }}</span>
                            <span class="block text-xs text-ink-600">{{ $description }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('roles')
                <span class="mt-2 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
            @enderror
            @error('roles.*')
                <span class="mt-2 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
            @enderror
        </fieldset>

        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
            <a href="{{ route('settings.users.index') }}" class="rounded-md border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700">Annuler</a>
        </div>
    </form>
@endsection
