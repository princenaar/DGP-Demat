@extends('layouts.document')

@section('title')
    ATTESTATION
@endsection

@section('content')
    <p>
        Je soussigné, Monsieur le Directeur des Ressources humaines, atteste que Mme/M.
        <strong>{{ $demande->prenom }}</strong> <strong>{{ strtoupper($demande->nom) }}</strong>,
        {{ $demande->categorieSocioprofessionnelle?->libelle }}, @if($demande->statut === 'contractuel')
            contractuel(le),
        @elseif($demande->statut === 'étatique')
            matricule de solde n° <strong>{{ $demande->matricule }},</strong>
        @endif
        est bénéficiaire du fonds de motivation de la santé pour un montant qui s’élève à cent cinquante mille
        (150 000) francs CFA par trimestre
        @if($demande->statut === 'contractuel')
            pour la période du 1<sup>er</sup> janvier au 31 décembre {{ now()->format('Y') }}
        @endif.
    </p>
    <p>
        En foi de quoi, la présente attestation est établie pour servir et valoir ce que de droit.
    </p>
@endsection
