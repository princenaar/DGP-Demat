@extends('layouts.app')

@section('content')
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="mb-0">Liste des demandes</h3>
        </div>
        <div class="card-body">
            <table id="demandes-table" class="table table-bordered">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Structure</th>
                    <th>Type</th>
                    <th>État</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
            </table>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment-with-locales.min.js"
            integrity="sha512-4F1cxYdMiAW98oomSLaygEwmCnIP38pb4Kx70yQYqRwLVCs3DbRumfBq82T08g/4LJ/smbFGFpmeFlQgoDccgg=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        $(function () {
            $('#demandes-table').DataTable({
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
                        render: function (data) {
                            moment.locale('fr');
                            return moment(data).format('DD/MM/YYYY');
                        }
                    }
                ],

            });
        });
    </script>
@endpush
