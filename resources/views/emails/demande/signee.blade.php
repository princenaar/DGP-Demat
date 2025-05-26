@component('mail::message')

<p>Bonjour {{ $demande->prenom }} {{ $demande->nom }},</p>

<p>Votre demande a été traitée et signée avec succès.</p>

<p>Veuillez trouver le document en pièce jointe.</p>

<p>
    Cordialement,<br>
    L’équipe {{ config('app.name') }}
</p>
@endcomponent
