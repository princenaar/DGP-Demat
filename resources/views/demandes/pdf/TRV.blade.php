@extends('layouts.document')

@section('title')
    ATTESTATION DE TRAVAIL
@endsection
@section('content')
    <p class="administrative-paragraph keep-together">
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que @include('demandes.pdf.partials.identity', ['demande' => $demande, 'trailingPunctuation' => ',']) est en service au Ministère de la Santé et de l'Hygiène publique depuis le
        <span class="nowrap">{{ $demande->date_prise_service->isoFormat($demande->date_prise_service->day == 1 ? 'Do MMMM YYYY' : 'D MMMM YYYY') }}</span>
        jusqu’à ce jour.
    </p>
    <p class="administrative-paragraph keep-together">
        En foi de quoi, la présente attestation lui est délivrée pour servir et valoir <span class="nowrap">ce que de droit</span>.
    </p>
@endsection
