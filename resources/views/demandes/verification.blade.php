@extends('layouts.public')

@section('content')
    <div class="container">
        <h2 class="mb-4">Vérification de l'authenticité de la demande</h2>

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @else
            <div class="alert alert-success">
                Cette demande est authentique et a bien été signée.
            </div>

            <ul class="list-group">
                <li class="list-group-item"><strong>Nom :</strong> {{ $demande->nom }}</li>
                <li class="list-group-item"><strong>Prénom :</strong> {{ $demande->prenom }}</li>
                <li class="list-group-item"><strong>Type de document
                        :</strong> {{ $demande->typeDocument->nom ?? 'N/A' }}</li>
                <li class="list-group-item"><strong>Date de signature
                        :</strong> {{ $demande->updated_at->format('d/m/Y H:i') }}</li>
            </ul>
        @endif
    </div>
@endsection
