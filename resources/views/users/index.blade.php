@extends('layouts.app')

@section('header')
    Liste des utilisateurs
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-senegal-green text-white">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Nom</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Rôle</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                @foreach ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-ink-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-sm text-ink-700">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-sm text-ink-700">
                            {{ $user->roles->pluck('name')->join(', ') ?: 'Aucun rôle' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex gap-3">
                                <a href="#" class="font-medium text-senegal-green hover:text-green-800">Modifier</a>
                                <a href="#" class="font-medium text-senegal-red hover:text-red-700">Supprimer</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
