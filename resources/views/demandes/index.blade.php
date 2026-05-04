@extends('layouts.app')

@section('header')
    Liste des demandes
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="border-b border-gray-100 p-6">
            @include('demandes.partials.etat-filter', [
                'filterId' => 'demandes-etat-filter',
                'etatOptions' => $etatOptions,
            ])
        </div>
        <div class="overflow-x-auto">
            <table id="demandes-table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-senegal-green text-white">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Nom</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Prénom</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Structure</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">État</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @include('demandes.partials.datatable-script', [
        'tableId' => 'demandes-table',
        'filterId' => 'demandes-etat-filter',
        'ajaxUrl' => route('demandes.data'),
    ])
@endpush
