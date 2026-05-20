@extends('layouts.document')

@section('title')
    ATTESTATION
@endsection

@section('content')
    <p class="administrative-paragraph keep-together">
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que @include('demandes.pdf.partials.identity', ['demande' => $demande, 'uppercaseName' => true, 'trailingPunctuation' => ',']) est bénéficiaire du fonds de motivation de la santé pour un montant qui s’élève à cent cinquante mille
        (<span class="nowrap">150&nbsp;000</span>) <span class="nowrap">francs&nbsp;CFA</span> par
        @if($demande->statut === 'contractuel')
            trimestre pour la période du <span class="nowrap">1<sup>er</sup>&nbsp;janvier</span> au <span class="nowrap">31&nbsp;décembre&nbsp;{{ now()->format('Y') }}.</span>
        @else
            trimestre.
        @endif
    </p>
    <p class="administrative-paragraph keep-together">
        En foi de quoi, la présente attestation est établie pour servir et valoir <span class="nowrap">ce que de droit</span>.
    </p>
@endsection
