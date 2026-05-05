@component('mail::message')
# Résumé quotidien

Bonjour {{ $user->name }},

Vous avez {{ $demandes->count() }} demande(s) à traiter aujourd’hui.

@component('mail::table')
| N° | Demandeur | Type | État |
| :- | :-------- | :--- | :--- |
@foreach($demandes as $demande)
| [{{ $demande->numero_affiche }}]({{ route('demandes.show', $demande) }}) | {{ $demande->prenom }} {{ $demande->nom }} | {{ $demande->typeDocument->nom }} | {{ $demande->etatDemande->nom }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => route('dashboard')])
Ouvrir le tableau de bord
@endcomponent

Cordialement,<br>
{{ config('app.name') }}
@endcomponent
