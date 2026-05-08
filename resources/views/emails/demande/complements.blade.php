<x-mail::message>
# Demande de compléments

Bonjour {{ $demande->prenom }} {{ $demande->nom }},

Votre demande **{{ $demande->numero_affiche }}** pour le document **{{ $demande->typeDocument->nom }}** nécessite des compléments.

Cliquez sur le bouton ci-dessous pour compléter votre dossier. Ce lien est valable pendant 3 jours.

<x-mail::button :url="$url" color="success">
Compléter la demande
</x-mail::button>

Merci,<br>
L’équipe {{ config('app.name') }}
</x-mail::message>
