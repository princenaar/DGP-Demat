<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemandeNotification extends Notification
{
    use Queueable;

    public string $objet;
    public string $message;
    public ?string $lien;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $objet, string $message, ?string $lien = null)
    {
        $this->objet = $objet;
        $this->message = $message;
        $this->lien = $lien;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->objet)
            ->line($this->message);

        if ($this->lien) {
            $mail->action('Voir plus...', $this->lien);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
