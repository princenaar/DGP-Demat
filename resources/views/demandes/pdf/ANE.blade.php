@extends('layouts.document')

@section('title')
    ATTESTATION DE NON ENGAGEMENT
@endsection
@section('content')
    <p class="administrative-paragraph keep-together">
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que @include('demandes.pdf.partials.identity', ['demande' => $demande]) n’est lié(e) par aucun engagement avec le ministère de la Santé.
    </p>
    <p class="administrative-paragraph keep-together">
        En foi de quoi, la présente attestation lui est délivrée pour servir et valoir <span class="nowrap">ce que de droit</span>.
    </p>
@endsection
