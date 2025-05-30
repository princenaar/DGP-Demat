@extends('layouts.public')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Bienvenue sur le portail {{ config('app.name') }}</div>

                    <div class="card-body">
                        <p>
                            La Direction des Ressources Humaines vous accompagne dans la gestion administrative et le
                            suivi de vos demandes.
                        </p>
                        <div class="d-grid gap-3">
                            <a href="{{ route('demandes.create') }}" class="btn btn-success">
                                Faire une demande
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-outline-danger">
                                Espace administrateur
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
