@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Liste des utilisateurs</h1>
        <table class="table-auto w-full border-collapse border border-gray-200">
            <thead>
            <tr>
                <th class="border px-4 py-2">Nom</th>
                <th class="border px-4 py-2">Email</th>
                <th class="border px-4 py-2">Rôle</th>
                <th class="border px-4 py-2">Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td class="border px-4 py-2">{{ $user->name }}</td>
                    <td class="border px-4 py-2">{{ $user->email }}</td>
                    <td class="border px-4 py-2">
                        {{ $user->roles->pluck('name')->join(', ') ?? 'Aucun rôle' }}
                    </td>
                    <td class="border px-4 py-2">
                        <a href="#" class="text-blue-500 hover:underline">Modifier</a>
                        <a href="#" class="text-red-500 hover:underline">Supprimer</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
