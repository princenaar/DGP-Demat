@extends('layouts.document')

@section('title')
    ATTESTATION DE NON ENGAGEMENT
@endsection
@section('content')
    <p>
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que <strong>M./Mme {{ $demande->prenom }}
            {{ $demande->nom }}</strong>, {{ $demande->categorieSocioprofessionnelle?->libelle }}
        , @if($demande->statut === 'contractuel')
            contractuel,
        @elseif($demande->statut === 'étatique')
            matricule de solde n° <strong>{{ $demande->matricule }},</strong>
        @endif a fait valoir ses droits à une pension de retraite à la date du
        {{ $demande->date_depart_retraite->isoFormat($demande->date_depart_retraite->day == 1 ? 'Do MMMM YYYY' : 'D MMMM YYYY') }}.
    </p>
    <p>
        En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de
        droit.
    </p>
@endsection
