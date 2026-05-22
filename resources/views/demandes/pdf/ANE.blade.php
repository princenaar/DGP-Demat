@extends('layouts.document')

@section('title')
    ATTESTATION DE NON ENGAGEMENT
@endsection
@section('content')
    <p class="administrative-paragraph keep-together">
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que @include('demandes.pdf.partials.identity', ['demande' => $demande, 'includeBirthInfo' => true, 'trailingPunctuation' => ',']) n’est ni boursier(ère), ni contractuel(le) du ministère de la Santé et de l'Hygiène publique.
    </p>
    <p class="administrative-paragraph keep-together">
        En foi de quoi, la présente attestation est établie pour servir et valoir <span class="nowrap">ce que de droit</span>.
    </p>
@endsection
