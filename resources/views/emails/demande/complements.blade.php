@component('mail::message')
    # Demande de compléments

    Bonjour {{ $demande->nom }} {{ $demande->prenom }},

    Votre demande de **{{ $demande->typeDocument->nom }}** nécessite des compléments.

    Cliquez sur le bouton ci-dessous pour modifier votre demande :

    @component('mail::button', ['url' => $lien])
        Compléter la demande
    @endcomponent

    Merci,
    L’équipe {{ config('app.name') }}
@endcomponent
