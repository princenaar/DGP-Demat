@extends('layouts.document')

@section('title')
    CERTIFICAT ADMINISTRATIF
@endsection
@section('content')
    <p>
        Je soussignée, Madame le Directeur des Ressources humaines, certifie que <strong>M./Mme {{ $demande->prenom }}
            {{ $demande->nom }}</strong>, {{ $demande->categorieSocioprofessionnelle?->libelle }}
        , @if($demande->statut === 'contractuel')
            contractuel,
        @elseif($demande->statut === 'étatique')
            matricule de solde n° <strong>{{ $demande->matricule }},</strong>
        @endif est en service au Ministère de la Santé et de l'Hygiène publique depuis le
        {{ $demande->date_prise_service->isoFormat($demande->date_prise_service->day == 1 ? 'Do MMMM YYYY' : 'D MMMM YYYY') }}
        jusqu’à ce jour.
    </p>
    <p>
        En foi de quoi, le présent certificat lui est délivré pour servir et valoir ce que de
        droit.
    </p>
@endsection
