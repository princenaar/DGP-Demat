@extends('layouts.document')

@section('title')
    ATTESTATION DE NON ENGAGEMENT
@endsection
@section('content')
    @php
        // Render the shared identity partial as a string so trim() removes its trailing newline before the CNI segment.
        $identityHtml = trim(view('demandes.pdf.partials.identity', [
            'demande' => $demande,
            'includeBirthInfo' => true,
            'trailingPunctuation' => '',
        ])->render());
    @endphp
    <p class="administrative-paragraph keep-together">
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que {!! $identityHtml !!}, <span class="nowrap">CNI n°&nbsp;<strong>{{ $demande->nin }}</strong></span>, n’est ni boursier(ère), ni contractuel(le) du ministère de la Santé et de l'Hygiène publique.
    </p>
    <p class="administrative-paragraph keep-together">
        En foi de quoi, la présente attestation est établie pour servir et valoir <span class="nowrap">ce que de droit</span>.
    </p>
@endsection
