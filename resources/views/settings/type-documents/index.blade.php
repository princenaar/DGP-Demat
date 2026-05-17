@extends('layouts.app')

@section('header')
    Types de demandes
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="mb-4 flex justify-end">
        <a href="{{ route('settings.type-documents.create') }}" class="rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Ajouter un type</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-ink-100 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-ink-100">
            <thead class="bg-senegal-green text-white">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Nom</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Agents par défaut</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @foreach($typeDocuments as $typeDocument)
                    <tr>
                        <td class="px-4 py-3 text-sm font-semibold text-ink-900">{{ $typeDocument->nom }}</td>
                        <td class="px-4 py-3 text-sm text-ink-700">{{ $typeDocument->code }}</td>
                        <td class="px-4 py-3 text-sm text-ink-700">{{ $typeDocument->defaultAgents->pluck('name')->join(', ') ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex flex-wrap gap-3">
                                <a class="font-semibold text-senegal-green" href="{{ route('settings.type-documents.edit', $typeDocument) }}">Modifier</a>
                                <a class="font-semibold text-senegal-green" href="{{ route('settings.type-documents.pieces.index', $typeDocument) }}">Pièces</a>
                                <a class="font-semibold text-senegal-green" href="{{ route('settings.type-documents.workflow.index', $typeDocument) }}">Workflow</a>
                                <form method="POST" action="{{ route('settings.type-documents.destroy', $typeDocument) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="font-semibold text-senegal-red" type="submit">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
