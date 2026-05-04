@extends('layouts.app')

@section('header')
    Tableau de bord
@endsection

@section('content')
    <div class="space-y-6">
        <section class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-gray-100 bg-white p-5 shadow">
                <p class="text-sm font-medium text-ink-500">À traiter</p>
                <p class="mt-2 text-3xl font-bold text-ink-900">{{ $demandesATraiterCount }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-5 shadow">
                <p class="text-sm font-medium text-ink-500">États suivis</p>
                <p class="mt-2 text-3xl font-bold text-ink-900">{{ collect($countsByEtat)->sum('total') }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-5 shadow">
                <p class="text-sm font-medium text-ink-500">30 derniers jours</p>
                <p class="mt-2 text-3xl font-bold text-ink-900">{{ collect($countsByTypeLast30Days)->sum('total') }}</p>
            </div>
            <div class="rounded-lg border border-gray-100 bg-white p-5 shadow">
                <p class="text-sm font-medium text-ink-500">Délai signature moyen</p>
                <p class="mt-2 text-3xl font-bold text-ink-900">{{ $averageSignatureTime ?? 'N/A' }}</p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-100 bg-white p-6 shadow">
                <h2 class="text-lg font-semibold text-ink-900">Demandes par état</h2>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach($countsByEtat as $etat)
                        <div class="rounded-md border border-gray-100 bg-gray-50 p-4">
                            <p class="text-sm text-ink-600">{{ $etat['label'] }}</p>
                            <p class="mt-1 text-2xl font-semibold text-ink-900">{{ $etat['total'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-gray-100 bg-white p-6 shadow">
                <h2 class="text-lg font-semibold text-ink-900">Par type sur 30 jours</h2>
                <div class="mt-4 space-y-3">
                    @foreach($countsByTypeLast30Days as $type)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-ink-700">{{ $type['nom'] }}</span>
                                <span class="text-ink-500">{{ $type['total'] }}</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-gray-100">
                                <div class="h-2 rounded-full bg-senegal-green" style="width: {{ min(100, $type['total'] * 10) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-100 bg-white shadow">
            <div class="border-b border-gray-100 p-6 space-y-4">
                <h2 class="text-lg font-semibold text-ink-900">Mes demandes à traiter</h2>
                @include('demandes.partials.etat-filter', [
                    'filterId' => 'dashboard-etat-filter',
                    'etatOptions' => $etatOptions,
                ])
            </div>
            <div class="overflow-x-auto">
                <table id="dashboard-actions-table" class="min-w-full divide-y divide-gray-200">
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
        </section>
    </div>
@endsection

@push('scripts')
    @include('demandes.partials.datatable-script', [
        'tableId' => 'dashboard-actions-table',
        'filterId' => 'dashboard-etat-filter',
        'ajaxUrl' => route('dashboard.data'),
    ])
@endpush
