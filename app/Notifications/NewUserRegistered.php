<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    public function __construct(public User $user)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuova registrazione su '.config('app.name'))
            ->line("Nuovo utente registrato: {$this->user->name} ({$this->user->email})")
            ->line('Registrato il: '.$this->user->created_at->format('d/m/Y H:i'));
    }
}
