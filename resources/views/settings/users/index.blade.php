@extends('layouts.app')

@section('header')
    Utilisateurs
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="mb-4 flex justify-end">
        <a href="{{ route('settings.users.create') }}" class="rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Ajouter un utilisateur</a>
    </div>

    <div class="rounded-lg border border-ink-100 bg-white p-5 shadow-sm">
        <div class="overflow-x-auto">
            <table id="users-table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-senegal-green text-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Nom</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Rôles</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Créé le</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @include('settings.partials.datatables-language')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new window.DataTable('#users-table', {
                processing: true,
                serverSide: true,
                ajax: '{{ route('settings.users.data') }}',
                columns: [
                    {data: 'name', name: 'name'},
                    {data: 'email', name: 'email'},
                    {data: 'roles_label', name: 'roles_label', orderable: false},
                    {data: 'status_label', name: 'is_active'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                columnDefs: [
                    {
                        targets: 4,
                        render(data) {
                            return data ? new Intl.DateTimeFormat('fr-FR').format(new Date(data)) : '-';
                        }
                    }
                ],
                language: window.dgpDataTablesLanguage
            });
        });
    </script>
@endpush
