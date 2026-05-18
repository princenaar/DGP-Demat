@include('settings.partials.datatables-language')

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const etatFilter = document.getElementById(@js($filterId));
        const table = new window.DataTable(@js('#' . $tableId), {
            processing: true,
            serverSide: true,
            ajax: {
                url: @js($ajaxUrl),
                data(data) {
                    data.etat_id = etatFilter?.value ?? '';
                }
            },
            columns: [
                {data: 'prenom', name: 'prenom'},
                {data: 'nom', name: 'nom'},
                {data: 'statut_label', name: 'statut'},
                {data: 'type', name: 'typeDocument.code', orderable: false, searchable: false},
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
            language: window.dgpDataTablesLanguage
        });

        etatFilter?.addEventListener('change', () => table.ajax.reload());
    });
</script>
