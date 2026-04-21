@extends('layouts.app')

@section('header')
    Liste des demandes
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow border border-gray-100">
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new window.DataTable('#demandes-table', {
                processing: true,
                serverSide: true,
                ajax: '{{ route('demandes.data') }}',
                columns: [
                    {data: 'nom', name: 'nom'},
                    {data: 'prenom', name: 'prenom'},
                    {data: 'structure', name: 'structure.nom', orderable: false, searchable: false},
                    {data: 'type', name: 'typeDocument.nom', orderable: false, searchable: false},
                    {data: 'etat', name: 'etatDemande.nom', orderable: false, searchable: false},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                columnDefs: [
                    {
                        targets: 5,
                        render(data) {
                            return new Intl.DateTimeFormat('fr-FR').format(new Date(data));
                        }
                    }
                ],
                language: {
                    search: 'Rechercher :',
                    lengthMenu: 'Afficher _MENU_ entrées',
                    info: 'Affichage de _START_ à _END_ sur _TOTAL_ entrées',
                    infoEmpty: 'Aucune entrée disponible',
                    zeroRecords: 'Aucun résultat trouvé',
                    processing: 'Chargement...',
                    paginate: {
                        first: 'Premier',
                        last: 'Dernier',
                        next: 'Suivant',
                        previous: 'Précédent'
                    }
                }
            });
        });
    </script>
@endpush
