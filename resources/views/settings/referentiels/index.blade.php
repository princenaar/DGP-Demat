@extends('layouts.app')

@section('header')
    Référentiels
@endsection

@section('content')
    @include('settings.partials.nav')

    <div class="space-y-8">
        <section class="rounded-lg border border-ink-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-bold text-ink-900">Structures</h2>
                <a href="{{ route('settings.structures.create') }}" class="inline-flex w-fit rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Ajouter une structure</a>
            </div>
            <div class="overflow-x-auto">
                <table id="structures-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-senegal-green text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Nom</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-ink-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-bold text-ink-900">Catégories socioprofessionnelles</h2>
                <a href="{{ route('settings.categories.create') }}" class="inline-flex w-fit rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Ajouter une catégorie</a>
            </div>
            <div class="overflow-x-auto">
                <table id="categories-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-senegal-green text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Libellé</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Ordre</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-ink-100 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-ink-900">États des demandes</h2>
            <p class="mt-1 text-sm text-ink-600">Ces états sont figés pour éviter les comportements inattendus. Leur modification se fait dans le code.</p>
            <div class="mt-4 overflow-x-auto">
                <table id="etats-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-senegal-green text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Nom</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @include('settings.partials.datatables-language')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new window.DataTable('#structures-table', {
                processing: true,
                serverSide: true,
                ajax: '{{ route('settings.structures.data') }}',
                columns: [
                    {data: 'nom', name: 'nom'},
                    {data: 'code', name: 'code'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                language: window.dgpDataTablesLanguage
            });

            new window.DataTable('#categories-table', {
                processing: true,
                serverSide: true,
                ajax: '{{ route('settings.categories.data') }}',
                columns: [
                    {data: 'libelle', name: 'libelle'},
                    {data: 'code', name: 'code'},
                    {data: 'ordre', name: 'ordre'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                language: window.dgpDataTablesLanguage
            });

            new window.DataTable('#etats-table', {
                processing: true,
                serverSide: true,
                ajax: '{{ route('settings.etats.data') }}',
                columns: [
                    {data: 'nom', name: 'nom'}
                ],
                language: window.dgpDataTablesLanguage
            });
        });
    </script>
@endpush
