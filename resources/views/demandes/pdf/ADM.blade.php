@extends('layouts.document')

@section('title')
    CERTIFICAT ADMINISTRATIF
@endsection
@section('content')
    <p class="administrative-paragraph keep-together">
        Je soussigné, Monsieur le Directeur des Ressources humaines, certifie que @include('demandes.pdf.partials.identity', ['demande' => $demande, 'trailingPunctuation' => ',']) est en service au Ministère de la Santé et de l'Hygiène publique depuis le
        <span class="nowrap">{{ $demande->date_prise_service->isoFormat($demande->date_prise_service->day == 1 ? 'Do MMMM YYYY' : 'D MMMM YYYY') }}</span>
        jusqu’à ce jour.
    </p>
    <p class="administrative-paragraph keep-together">
        En foi de quoi, le présent certificat lui est délivré pour servir et valoir <span class="nowrap">ce que de droit</span>.
    </p>
@endsection
