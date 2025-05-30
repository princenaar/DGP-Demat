@extends('layouts.document')

@section('numero')
    N° <b>{{ sprintf("%05d", $demande->id) }}</b> MSAS/DRH/DGP/cald
@endsection
@section('title')
    ATTESTATION DE TRAVAIL
@endsection
@section('content')
    <p>
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que <strong>M./Mme {{ $demande->prenom }}
            {{ $demande->nom }}</strong>, {{ $demande->categorie_socioprofessionnelle }}
        , @if($demande->statut === 'contractuel')
            contractuel,
        @elseif($demande->statut === 'étatique')
            matricule de solde n° <strong>{{ $demande->matricule }},</strong>
        @endif est en service au Ministère de la Santé et de l’Action sociale depuis le
        {{ $demande->date_prise_service->isoFormat($demande->date_prise_service->day == 1 ? 'Do MMMM YYYY' : 'D MMMM YYYY') }}
        jusqu’à ce jour.
    </p>
    <p>
        En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de
        droit.
    </p>
@endsection
