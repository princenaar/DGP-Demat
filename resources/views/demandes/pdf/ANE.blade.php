@extends('layouts.document')

@section('title')
    ATTESTATION DE NON ENGAGEMENT
@endsection
@section('content')
    <p>
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que <strong>M./Mme {{ $demande->prenom }}
            {{ $demande->nom }}</strong> n’est lié(e) par aucun engagement avec le ministère de la Santé.
    </p>
    <p>
        En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.
    </p>
@endsection
