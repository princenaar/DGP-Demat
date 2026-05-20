@extends('layouts.document')

@section('title')
    CERTIFICAT DE TRAVAIL
@endsection
@section('content')
    <p class="administrative-paragraph keep-together">
        Je soussigné, Monsieur le Directeur des Ressources humaines, certifie que @include('demandes.pdf.partials.identity', ['demande' => $demande, 'trailingPunctuation' => ',']) était en service au Ministère de la Santé et de l'Hygiène publique du
        <span class="nowrap">{{ $demande->date_prise_service->isoFormat($demande->date_prise_service->day == 1 ? 'Do MMMM YYYY' : 'D MMMM YYYY') }}</span>
        au <span class="nowrap">{{ $demande->date_fin_service->isoFormat($demande->date_fin_service->day == 1 ? 'Do MMMM YYYY' : 'D MMMM YYYY') }}</span>.
    </p>
    <p class="administrative-paragraph keep-together">
        En foi de quoi, le présent certificat lui est délivré pour servir et valoir <span class="nowrap">ce que de droit</span>.
    </p>
@endsection
