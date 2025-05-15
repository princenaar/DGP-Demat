<?php

namespace App\Services;

use App\Models\Demande;
use App\Notifications\DemandeNotification;
use Illuminate\Support\Facades\Notification;

class DemandeMailService
{
    public static function envoyer(Demande $demande, string $objet, string $message, ?string $lien = null): void
    {
        Notification::route('mail', $demande->email)
            ->notify(new DemandeNotification($objet, $message, $lien));
    }

}
